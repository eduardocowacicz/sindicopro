<?php

namespace Tests\Feature\Financeiro;

use App\Models\CategoriaFinanceira;
use App\Models\ContaFinanceira;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CobrancasEFundoReservaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail());
    }

    public function test_filtro_de_pessoas_por_multiplos_tipos(): void
    {
        $this->postJson('/api/pessoas', [
            'nome_completo' => 'Pessoa Prestador Teste',
            'tipo' => 'PRESTADOR_SERVICO',
            'cpf' => '111.444.777-35',
            'email' => 'prestador.teste@teste.com',
            'telefone' => '(11) 98765-4321',
        ])->assertCreated();

        $this->postJson('/api/pessoas', [
            'nome_completo' => 'Pessoa Fornecedor Teste',
            'tipo' => 'FORNECEDOR',
            'cpf' => '222.555.888-46',
            'email' => 'fornecedor.teste@teste.com',
            'telefone' => '(11) 98765-4322',
        ])->assertCreated();

        $resposta = $this->getJson('/api/pessoas?tipos=PRESTADOR_SERVICO,FORNECEDOR&por_pagina=100')
            ->assertOk();

        $dados = $resposta->json('dados');
        $this->assertNotEmpty($dados);

        foreach ($dados as $pessoa) {
            $this->assertContains($pessoa['tipo'], ['PRESTADOR_SERVICO', 'FORNECEDOR']);
            $this->assertNotEquals('MORADOR', $pessoa['tipo']);
        }
    }

    public function test_lancamento_financeiro_com_fundo_de_reserva(): void
    {
        $conta = ContaFinanceira::query()->where('ativo', true)->firstOrFail();
        $categoria = CategoriaFinanceira::query()->where('direcao', 'SAIDA')->where('ativo', true)->firstOrFail();

        $resposta = $this->postJson('/api/financeiro/movimentacoes', [
            'conta_financeira_id' => $conta->id_publico,
            'categoria_financeira_id' => $categoria->id_publico,
            'direcao' => 'SAIDA',
            'descricao' => 'Reforma Estrutural Fachada - Fundo de Reserva',
            'data_competencia' => '2026-11-01',
            'data_efetiva' => '2026-11-05',
            'valor' => 5000.00,
            'utiliza_fundo_reserva' => true,
        ])->assertCreated();

        $this->assertTrue($resposta->json('dados.utiliza_fundo_reserva'));
        $this->assertSame('FUNDO_RESERVA', $resposta->json('dados.origem_recurso'));
    }

    public function test_fechamento_aplica_percentual_fundo_reserva_e_discrimina_despesas(): void
    {
        $conta = ContaFinanceira::query()->where('ativo', true)->firstOrFail();
        $categoria = CategoriaFinanceira::query()->where('direcao', 'SAIDA')->where('ativo', true)->firstOrFail();

        // 1. Cria despesa normal (ordinária) em mês aberto (novembro/2026)
        $despesaNormal = $this->postJson('/api/financeiro/movimentacoes', [
            'conta_financeira_id' => $conta->id_publico,
            'categoria_financeira_id' => $categoria->id_publico,
            'direcao' => 'SAIDA',
            'descricao' => 'Limpeza Ordinária Novembro',
            'data_competencia' => '2026-11-01',
            'data_efetiva' => '2026-11-10',
            'valor' => 1000.00,
            'utiliza_fundo_reserva' => false,
        ])->assertCreated()->json('dados');

        // 2. Cria despesa paga com Fundo de Reserva
        $despesaFundo = $this->postJson('/api/financeiro/movimentacoes', [
            'conta_financeira_id' => $conta->id_publico,
            'categoria_financeira_id' => $categoria->id_publico,
            'direcao' => 'SAIDA',
            'descricao' => 'Pintura emergencial do muro',
            'data_competencia' => '2026-11-01',
            'data_efetiva' => '2026-11-15',
            'valor' => 800.00,
            'utiliza_fundo_reserva' => true,
        ])->assertCreated()->json('dados');

        // 3. Executa fechamento com 10% de fundo de reserva
        $fechamentoResp = $this->postJson('/api/fechamentos/mensais', [
            'periodo' => '2026-11-01',
            'percentual_fundo_reserva' => 10.0,
            'despesas' => [
                ['movimentacao_id' => $despesaNormal['id_publico'], 'valor_cobrar' => 1000.00],
            ],
        ])->assertCreated();

        $dadosFechamento = $fechamentoResp->json('dados');
        $this->assertEquals(10.0, $dadosFechamento['percentual_fundo_reserva']);
        $this->assertEquals(100.0, $dadosFechamento['valor_fundo_reserva']);
        $this->assertEquals(1000.0, $dadosFechamento['valor_despesas_ordinarias']);
        $this->assertEquals(1100.0, $dadosFechamento['total_rateado']);

        // 4. Verifica se os boletos gerados contêm a cota ordinária e a taxa de reserva
        $this->assertNotEmpty($dadosFechamento['boletos']);
        $primeiroBoleto = $dadosFechamento['boletos'][0];
        $this->assertGreaterThan(0, $primeiroBoleto['valor_fundo_reserva']);
        $this->assertEquals($primeiroBoleto['valor'], round($primeiroBoleto['valor_cota_ordinaria'] + $primeiroBoleto['valor_fundo_reserva'], 2));
    }

    public function test_listagem_de_cobrancas_com_kpis_e_baixa_de_pagamento(): void
    {
        $conta = ContaFinanceira::query()->where('ativo', true)->firstOrFail();

        // 1. Lista cobranças geradas pelo seeder (Setembro/2026)
        $cobrancasResp = $this->getJson('/api/financeiro/cobrancas')
            ->assertOk();

        $this->assertArrayHasKey('kpis', $cobrancasResp->json());
        $this->assertArrayHasKey('total_aberto', $cobrancasResp->json('kpis'));
        $this->assertArrayHasKey('total_pago', $cobrancasResp->json('kpis'));
        $this->assertArrayHasKey('taxa_adimplencia', $cobrancasResp->json('kpis'));

        $boletos = $cobrancasResp->json('dados');
        $this->assertNotEmpty($boletos);
        $boletoParaBaixa = $boletos[0];
        $this->assertContains($boletoParaBaixa['situacao'], ['ABERTO', 'VENCIDO']);

        // 2. Registra pagamento no boleto
        $baixaResp = $this->postJson("/api/financeiro/cobrancas/{$boletoParaBaixa['id_publico']}/registrar-pagamento", [
            'data_pagamento' => '2026-10-12',
            'valor_pago' => $boletoParaBaixa['valor_original'],
            'conta_financeira_id' => $conta->id_publico,
            'forma_pagamento' => 'PIX',
            'observacoes' => 'Pago pontualmente via chave PIX',
        ])->assertOk();

        $this->assertSame('PAGO', $baixaResp->json('dados.situacao'));

        // 3. Verifica se as contas financeiras segregam saldo_capital_giro e saldo_fundo_reserva
        $contasResp = $this->getJson('/api/financeiro/contas')
            ->assertOk();

        $contaAtualizada = collect($contasResp->json('dados'))->firstWhere('id_publico', $conta->id_publico);
        $this->assertNotNull($contaAtualizada);
        $this->assertArrayHasKey('saldo_atual', $contaAtualizada);
        $this->assertArrayHasKey('saldo_capital_giro', $contaAtualizada);
        $this->assertArrayHasKey('saldo_fundo_reserva', $contaAtualizada);
    }
}
