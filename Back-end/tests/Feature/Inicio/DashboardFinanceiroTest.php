<?php

namespace Tests\Feature\Inicio;

use App\Models\CategoriaFinanceira;
use App\Models\ContaFinanceira;
use App\Models\MovimentacaoFinanceira;
use App\Models\PeriodoFinanceiro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class DashboardFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail());
    }

    public function test_retorna_12_meses_de_movimentacoes_contabilizadas_por_data_efetiva(): void
    {
        $fimDoDia = Carbon::now()->endOfDay();
        $this->travelTo($fimDoDia);
        $primeiroMes = $fimDoDia->copy()->startOfMonth()->subMonths(11);
        $mesAtual = $fimDoDia->copy()->startOfMonth();

        $this->criarMovimentacao('ENTRADA', $primeiroMes->copy()->subDay()->toDateString(), 900.00, 'CONTABILIZADO');
        $this->criarMovimentacao('ENTRADA', $primeiroMes->toDateString(), 125.25, 'CONTABILIZADO');
        $this->criarMovimentacao('SAIDA', $primeiroMes->copy()->addMonths(4)->addDays(11)->toDateString(), 75.50, 'CONTABILIZADO');
        $this->criarMovimentacao('ENTRADA', $mesAtual->copy()->addDays(2)->toDateString(), 80.00, 'CONTABILIZADO');
        $this->criarMovimentacao('SAIDA', $mesAtual->copy()->addDays(3)->toDateString(), 20.00, 'CONTABILIZADO');
        $this->criarMovimentacao('SAIDA', $mesAtual->copy()->addDays(4)->toDateString(), 40.00, 'RASCUNHO');
        $this->criarMovimentacao('ENTRADA', $mesAtual->copy()->addDays(5)->toDateString(), 60.00, 'CANCELADO');

        $resposta = $this->getJson('/api/dashboard/financeiro')->assertOk();

        $meses = $resposta->json('dados.meses');

        $this->assertCount(12, $meses);
        $this->assertSame($primeiroMes->format('Y-m'), $meses[0]['competencia']);
        $this->assertSame($mesAtual->format('Y-m'), $meses[11]['competencia']);
        $this->assertSame(125.25, $meses[0]['entradas']);
        $this->assertSame(75.5, $meses[4]['saidas']);
        $this->assertEquals(80.0, $meses[11]['entradas']);
        $this->assertEquals(20.0, $meses[11]['saidas']);
    }

    private function criarMovimentacao(string $direcao, string $data, float $valor, string $situacao): void
    {
        $usuario = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $conta = ContaFinanceira::query()->where('ativo', true)->firstOrFail();
        $categoria = CategoriaFinanceira::query()
            ->where('ativo', true)
            ->where('direcao', $direcao)
            ->firstOrFail();
        $periodo = PeriodoFinanceiro::query()->firstOrCreate(
            ['periodo' => Carbon::parse($data)->startOfMonth()->toDateString()],
            [
                'situacao' => 'ABERTO',
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ],
        );

        MovimentacaoFinanceira::query()->create([
            'periodo_financeiro_id' => $periodo->getKey(),
            'conta_financeira_id' => $conta->getKey(),
            'categoria_financeira_id' => $categoria->getKey(),
            'direcao' => $direcao,
            'natureza' => 'NORMAL',
            'situacao' => $situacao,
            'descricao' => 'Movimentação para teste do dashboard',
            'data_competencia' => $data,
            'data_efetiva' => $data,
            'valor' => $valor,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);
    }
}
