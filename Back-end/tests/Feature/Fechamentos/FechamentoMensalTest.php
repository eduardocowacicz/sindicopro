<?php

namespace Tests\Feature\Fechamentos;

use App\Models\PeriodoFinanceiro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FechamentoMensalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $usuario = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $this->actingAs($usuario);
    }

    public function test_lista_os_meses_do_ano_com_status_corretos(): void
    {
        $resposta = $this->getJson('/api/fechamentos/mensais?ano=2026')
            ->assertOk()
            ->json('dados');

        $this->assertCount(12, $resposta);

        // Julho, Agosto, Setembro devem estar PUBLICADO
        $mesJulho = collect($resposta)->firstWhere('mes', 7);
        $this->assertEquals('PUBLICADO', $mesJulho['situacao']);
        $this->assertGreaterThan(0, $mesJulho['total_entradas']);
        $this->assertGreaterThan(0, $mesJulho['total_saidas']);

        $mesAgosto = collect($resposta)->firstWhere('mes', 8);
        $this->assertEquals('PUBLICADO', $mesAgosto['situacao']);

        $mesSetembro = collect($resposta)->firstWhere('mes', 9);
        $this->assertEquals('PUBLICADO', $mesSetembro['situacao']);

        // Outubro deve estar ABERTO
        $mesOutubro = collect($resposta)->firstWhere('mes', 10);
        $this->assertEquals('ABERTO', $mesOutubro['situacao']);
        $this->assertGreaterThan(0, $mesOutubro['quantidade_movimentacoes']);
    }

    public function test_detalha_fechamento_publicado_com_rateio_e_movimentacoes(): void
    {
        $periodoJulho = PeriodoFinanceiro::query()->where('periodo', '2026-07-01')->firstOrFail();

        $dados = $this->getJson("/api/fechamentos/mensais/{$periodoJulho->id_publico}")
            ->assertOk()
            ->json('dados');

        $this->assertEquals('PUBLICADO', $dados['situacao']);
        $this->assertEquals(7, $dados['mes']);
        $this->assertEquals(2026, $dados['ano']);
        $this->assertNotEmpty($dados['movimentacoes']);
        $this->assertNotEmpty($dados['contas']);
        $this->assertNotEmpty($dados['rateio']['unidades']);
        $this->assertNotEmpty($dados['boletos']);
        $this->assertEquals(36, $dados['rateio']['unidades_participantes']);
    }

    public function test_permite_consultar_detalhes_usando_data_sem_erro_uuid(): void
    {
        $dados = $this->getJson('/api/fechamentos/mensais/2026-11-01')
            ->assertOk()
            ->json('dados');

        $this->assertEquals('ABERTO', $dados['situacao']);
        $this->assertEquals(11, $dados['mes']);
        $this->assertEquals(2026, $dados['ano']);
    }

    public function test_detalha_previa_de_periodo_aberto(): void
    {
        $periodoOutubro = PeriodoFinanceiro::query()->where('periodo', '2026-10-01')->firstOrFail();

        $dados = $this->getJson("/api/fechamentos/mensais/{$periodoOutubro->id_publico}")
            ->assertOk()
            ->json('dados');

        $this->assertEquals('ABERTO', $dados['situacao']);
        $this->assertEquals(10, $dados['mes']);
        $this->assertNull($dados['publicado_em']);
        $this->assertNotEmpty($dados['despesas_disponiveis']);
        $this->assertNotEmpty($dados['boletos']);
        $this->assertNotEmpty($dados['rateio']['unidades']);
    }

    public function test_consegue_fechar_periodo_aberto_e_reabrir(): void
    {
        // 1. Fecha outubro
        $fecharResp = $this->postJson('/api/fechamentos/mensais', [
            'periodo' => '2026-10-01',
        ])->assertCreated();

        $fechamentoId = $fecharResp->json('dados.id_publico');
        $this->assertEquals('PUBLICADO', $fecharResp->json('dados.situacao'));
        $this->assertEquals(36, $fecharResp->json('dados.rateio.unidades_participantes'));
        $this->assertNotEmpty($fecharResp->json('dados.boletos'));

        // 2. Reabre outubro com justificativa
        $this->postJson("/api/fechamentos/mensais/{$fechamentoId}/reabrir", [
            'motivo' => 'Necessário incluir nota fiscal de manutenção atrasada recebida pelo conselho.',
        ])->assertOk();

        // 3. Verifica que voltou a ABERTO
        $periodoOutubro = PeriodoFinanceiro::query()->where('periodo', '2026-10-01')->firstOrFail();
        $this->assertEquals('ABERTO', $periodoOutubro->situacao);

        $detalhesAposReabrir = $this->getJson("/api/fechamentos/mensais/{$periodoOutubro->id_publico}")
            ->assertOk()
            ->json('dados');

        $this->assertEquals('ABERTO', $detalhesAposReabrir['situacao']);
    }
}
