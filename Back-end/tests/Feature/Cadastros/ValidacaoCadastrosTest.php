<?php

namespace Tests\Feature\Cadastros;

use App\Models\Usuario;
use App\Rules\CpfValido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ValidacaoCadastrosTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail());
    }

    public function test_codigo_do_bloco_e_gerado_pelo_banco_em_sequencia(): void
    {
        $primeiro = $this->postJson('/api/blocos', ['nome' => 'Bloco A'])->assertCreated();
        $segundo = $this->postJson('/api/blocos', ['nome' => 'Bloco B', 'codigo' => 'XYZ'])->assertCreated();

        $codigoPrimeiro = (int) $primeiro->json('dados.codigo');
        $this->assertSame($codigoPrimeiro + 1, (int) $segundo->json('dados.codigo'));
    }

    public function test_codigo_do_bloco_nao_pode_ser_alterado(): void
    {
        $bloco = $this->postJson('/api/blocos', ['nome' => 'Bloco A'])->assertCreated()->json('dados');

        $this->putJson("/api/blocos/{$bloco['id_publico']}", ['nome' => 'Bloco A', 'codigo' => 'NOVO'])
            ->assertOk()
            ->assertJsonPath('dados.codigo', $bloco['codigo']);
    }

    public function test_bloco_rejeita_andares_fora_do_intervalo(): void
    {
        $this->postJson('/api/blocos', ['nome' => 'Bloco A', 'quantidade_andares' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantidade_andares']);
    }

    public function test_unidade_nao_aceita_bloco_inativo(): void
    {
        $bloco = $this->postJson('/api/blocos', ['nome' => 'Bloco A'])->assertCreated()->json('dados');
        $this->postJson("/api/blocos/{$bloco['id_publico']}/inativar")->assertOk();

        $this->postJson('/api/unidades', [
            'bloco_id' => $bloco['id_publico'],
            'codigo' => '101',
            'situacao_ocupacao' => 'VAGO',
        ])->assertUnprocessable()->assertJsonValidationErrors(['bloco_id']);
    }

    public function test_unidade_rejeita_andar_acima_do_bloco(): void
    {
        $bloco = $this->postJson('/api/blocos', ['nome' => 'Bloco A', 'quantidade_andares' => 5])->assertCreated()->json('dados');

        $this->postJson('/api/unidades', [
            'bloco_id' => $bloco['id_publico'],
            'codigo' => '101',
            'numero_andar' => 6,
            'situacao_ocupacao' => 'VAGO',
        ])->assertUnprocessable()->assertJsonValidationErrors(['numero_andar']);
    }

    public function test_unidade_rejeita_codigo_com_espacos(): void
    {
        $bloco = $this->postJson('/api/blocos', ['nome' => 'Bloco A'])->assertCreated()->json('dados');

        $this->postJson('/api/unidades', [
            'bloco_id' => $bloco['id_publico'],
            'codigo' => '101 A',
            'situacao_ocupacao' => 'VAGO',
        ])->assertUnprocessable()->assertJsonValidationErrors(['codigo']);
    }

    public function test_pessoa_rejeita_cpf_invalido(): void
    {
        $this->postJson('/api/pessoas', ['nome_completo' => 'Maria Silva', 'cpf' => '111.111.111-11'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_pessoa_rejeita_cpf_ja_cadastrado_mesmo_formatado_de_outra_forma(): void
    {
        $this->postJson('/api/pessoas', ['nome_completo' => 'Maria Silva', 'cpf' => '529.982.247-25'])->assertCreated();

        $this->postJson('/api/pessoas', ['nome_completo' => 'João Silva', 'cpf' => '52998224725'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_pessoa_rejeita_telefone_sem_ddd(): void
    {
        $this->postJson('/api/pessoas', ['nome_completo' => 'Maria Silva', 'telefone' => '99999-9999'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['telefone']);
    }

    public function test_veiculo_normaliza_placa_mercosul(): void
    {
        $unidade = $this->criarUnidade();

        $this->postJson('/api/veiculos', [
            'unidade_id' => $unidade,
            'placa' => 'abc1d23',
            'modelo' => 'Onix',
        ])->assertCreated()->assertJsonPath('dados.placa', 'ABC1D23');
    }

    public function test_veiculo_rejeita_placa_fora_do_formato(): void
    {
        $unidade = $this->criarUnidade();

        $this->postJson('/api/veiculos', [
            'unidade_id' => $unidade,
            'placa' => 'ABC-12',
            'modelo' => 'Onix',
        ])->assertUnprocessable()->assertJsonValidationErrors(['placa']);
    }

    public function test_vinculo_exige_papel_de_cobranca_para_responsavel_financeiro(): void
    {
        $unidade = $this->criarUnidade();
        $pessoa = $this->criarPessoa();

        $this->postJson('/api/vinculos', [
            'unidade_id' => $unidade,
            'pessoa_id' => $pessoa,
            'tipo_vinculo' => 'PROPRIETARIO',
            'responsavel_financeiro' => true,
            'inicio_vigencia' => '2026-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['papel_cobranca']);
    }

    public function test_vinculo_rejeita_segundo_responsavel_financeiro_vigente(): void
    {
        $unidade = $this->criarUnidade();

        $payload = [
            'unidade_id' => $unidade,
            'tipo_vinculo' => 'PROPRIETARIO',
            'papel_cobranca' => 'PROPRIETARIO',
            'responsavel_financeiro' => true,
            'inicio_vigencia' => '2026-01-01',
        ];

        $this->postJson('/api/vinculos', [...$payload, 'pessoa_id' => $this->criarPessoa()])->assertCreated();

        $this->postJson('/api/vinculos', [...$payload, 'pessoa_id' => $this->criarPessoa('Carlos Souza')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsavel_financeiro']);
    }

    public function test_mensagens_de_validacao_sao_retornadas_em_portugues(): void
    {
        $this->postJson('/api/pessoas', ['nome_completo' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nome_completo.0', 'O campo nome completo é obrigatório.');

        $this->postJson('/api/pessoas', ['nome_completo' => 'Maria', 'email' => 'nao-e-email'])
            ->assertJsonPath('errors.email.0', 'O campo e-mail deve ser um endereço de e-mail válido.');
    }

    public function test_cpf_valido_aceita_formatado_e_sem_mascara(): void
    {
        $this->assertTrue(CpfValido::valido('529.982.247-25'));
        $this->assertTrue(CpfValido::valido('52998224725'));
        $this->assertFalse(CpfValido::valido('52998224724'));
        $this->assertFalse(CpfValido::valido('00000000000'));
    }

    private function criarUnidade(): string
    {
        $bloco = $this->postJson('/api/blocos', ['nome' => 'Bloco A'])->assertCreated()->json('dados');

        return $this->postJson('/api/unidades', [
            'bloco_id' => $bloco['id_publico'],
            'codigo' => '101',
            'situacao_ocupacao' => 'VAGO',
        ])->assertCreated()->json('dados.id_publico');
    }

    private function criarPessoa(string $nome = 'Maria Silva'): string
    {
        return $this->postJson('/api/pessoas', ['nome_completo' => $nome])->assertCreated()->json('dados.id_publico');
    }
}
