<?php

namespace Tests\Feature\Financeiro;

use App\Models\CategoriaFinanceira;
use App\Models\ContaFinanceira;
use App\Models\Pessoa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinanceiroAjustesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail());
    }

    public function test_codigo_da_categoria_financeira_e_gerado_em_sequencia(): void
    {
        $primeira = $this->postJson('/api/financeiro/categorias', [
            'nome' => 'Categoria Teste 1',
            'direcao' => 'ENTRADA',
        ])->assertCreated();

        $segunda = $this->postJson('/api/financeiro/categorias', [
            'nome' => 'Categoria Teste 2',
            'direcao' => 'SAIDA',
            'codigo' => 'XYZ',
        ])->assertCreated();

        $codigoPrimeira = (int) $primeira->json('dados.codigo');
        $this->assertSame($codigoPrimeira + 1, (int) $segunda->json('dados.codigo'));
    }

    public function test_codigo_da_categoria_financeira_nao_pode_ser_alterado(): void
    {
        $categoria = $this->postJson('/api/financeiro/categorias', [
            'nome' => 'Categoria Original',
            'direcao' => 'ENTRADA',
        ])->assertCreated()->json('dados');

        $this->putJson("/api/financeiro/categorias/{$categoria['id_publico']}", [
            'nome' => 'Categoria Renomeada',
            'direcao' => 'ENTRADA',
            'codigo' => '9999',
        ])->assertOk()->assertJsonPath('dados.codigo', $categoria['codigo']);
    }

    public function test_codigo_do_tipo_cobranca_e_gerado_em_sequencia(): void
    {
        $primeiro = $this->postJson('/api/financeiro/tipos-cobranca', [
            'nome' => 'Tipo Cobranca 1',
            'papel_pagador' => 'PROPRIETARIO',
            'dia_vencimento_padrao' => 10,
        ])->assertCreated();

        $segundo = $this->postJson('/api/financeiro/tipos-cobranca', [
            'nome' => 'Tipo Cobranca 2',
            'papel_pagador' => 'MORADOR',
            'dia_vencimento_padrao' => 15,
            'codigo' => 'FORCADO',
        ])->assertCreated();

        $codigoPrimeiro = (int) $primeiro->json('dados.codigo');
        $this->assertSame($codigoPrimeiro + 1, (int) $segundo->json('dados.codigo'));
    }

    public function test_codigo_do_tipo_cobranca_nao_pode_ser_alterado(): void
    {
        $tipo = $this->postJson('/api/financeiro/tipos-cobranca', [
            'nome' => 'Tipo Cobranca Teste',
            'papel_pagador' => 'PROPRIETARIO',
            'dia_vencimento_padrao' => 10,
        ])->assertCreated()->json('dados');

        $this->putJson("/api/financeiro/tipos-cobranca/{$tipo['id_publico']}", [
            'nome' => 'Tipo Cobranca Atualizado',
            'papel_pagador' => 'PROPRIETARIO',
            'dia_vencimento_padrao' => 10,
            'codigo' => '9999',
        ])->assertOk()->assertJsonPath('dados.codigo', $tipo['codigo']);
    }

    public function test_pessoa_permite_definir_tipo_prestador_e_fornecedor(): void
    {
        $prestador = $this->postJson('/api/pessoas', [
            'nome_completo' => 'Eletricista Silva',
            'tipo' => 'PRESTADOR_SERVICO',
        ])->assertCreated()->json('dados');

        $this->assertSame('PRESTADOR_SERVICO', $prestador['tipo']);

        $fornecedor = $this->postJson('/api/pessoas', [
            'nome_completo' => 'Fornecedor de Materiais Ltda',
            'tipo' => 'FORNECEDOR',
        ])->assertCreated()->json('dados');

        $this->assertSame('FORNECEDOR', $fornecedor['tipo']);
    }

    public function test_pessoa_rejeita_tipo_invalido(): void
    {
        $this->postJson('/api/pessoas', [
            'nome_completo' => 'Invalido Teste',
            'tipo' => 'TIPO_INEXISTENTE',
        ])->assertUnprocessable()->assertJsonValidationErrors(['tipo']);
    }

    public function test_lancamento_financeiro_permite_vincular_pessoa(): void
    {
        $conta = ContaFinanceira::query()->where('ativo', true)->firstOrFail();
        $categoria = CategoriaFinanceira::query()->where('ativo', true)->firstOrFail();
        $prestador = Pessoa::query()->where('tipo', 'PRESTADOR_SERVICO')->firstOrFail();

        $resposta = $this->postJson('/api/financeiro/movimentacoes', [
            'conta_financeira_id' => $conta->id_publico,
            'categoria_financeira_id' => $categoria->id_publico,
            'pessoa_id' => $prestador->id_publico,
            'direcao' => 'SAIDA',
            'descricao' => 'Pagamento de serviço elétrico',
            'data_competencia' => '2026-10-01',
            'data_efetiva' => '2026-10-16',
            'valor' => 350.00,
        ])->assertCreated();

        $resposta->assertJsonPath('dados.pessoa.id_publico', $prestador->id_publico);
        $resposta->assertJsonPath('dados.pessoa.nome_completo', $prestador->nome_completo);
        $resposta->assertJsonPath('dados.nome_contraparte', $prestador->nome_completo);
    }
}
