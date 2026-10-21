<?php

namespace Tests\Feature\Cadastros;

use App\Models\Bloco;
use App\Models\Pessoa;
use App\Models\Unidade;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BlocoVisualizacaoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $this->actingAs($this->usuario);
    }

    public function test_cria_bloco_e_gera_apartamentos_automaticamente(): void
    {
        $payload = [
            'nome' => 'Torre A',
            'apelido' => 'Edifício Solar',
            'andares' => 2,
            'apartamentos_por_andar' => 3,
            'observacoes' => 'Bloco de frente para a piscina',
        ];

        $resposta = $this->postJson('/api/blocos', $payload)
            ->assertCreated()
            ->assertJsonPath('dados.nome', 'Torre A')
            ->assertJsonPath('dados.apelido', 'Edifício Solar')
            ->assertJsonPath('dados.andares', 2)
            ->assertJsonPath('dados.apartamentos_por_andar', 3);

        $blocoId = $resposta->json('dados.id_publico');

        // Confirma no banco que 6 apartamentos foram gerados (101, 102, 103, 201, 202, 203)
        $unidades = Unidade::query()->whereHas('bloco', fn ($q) => $q->where('id_publico', $blocoId))->orderBy('codigo')->get();
        $this->assertCount(6, $unidades);
        $this->assertEquals(['101', '102', '103', '201', '202', '203'], $unidades->pluck('codigo')->all());

        // Andares correspondentes
        $this->assertEquals(1, $unidades->firstWhere('codigo', '101')->andar);
        $this->assertEquals(2, $unidades->firstWhere('codigo', '201')->andar);
    }

    public function test_detalhes_do_bloco_e_apartamentos_com_moradores_e_veiculos(): void
    {
        $blocoResposta = $this->postJson('/api/blocos', [
            'nome' => 'Bloco Beta',
            'apelido' => 'Torre Beta',
            'andares' => 1,
            'apartamentos_por_andar' => 1,
        ])->assertCreated();

        $blocoId = $blocoResposta->json('dados.id_publico');
        $unidadeId = $blocoResposta->json('dados.unidades.0.id_publico');

        // Cria pessoa e vincula como morador
        $pessoa = $this->postJson('/api/pessoas', [
            'nome_completo' => 'Carlos Alberto Silva',
            'email' => 'carlos@exemplo.com',
            'telefone' => '11988887777',
        ])->assertCreated()->json('dados');

        $this->postJson('/api/vinculos', [
            'unidade_id' => $unidadeId,
            'pessoa_id' => $pessoa['id_publico'],
            'tipo_vinculo' => 'MORADOR',
            'inicio_vigencia' => '2026-01-01',
        ])->assertCreated();

        // Cadastra um veículo para a unidade
        $this->postJson('/api/veiculos', [
            'unidade_id' => $unidadeId,
            'placa' => 'BRA2E19',
            'modelo' => 'Corolla',
            'cor' => 'Prata',
            'tipo' => 'CARRO',
        ])->assertCreated();

        // Consulta detalhes do bloco
        $detalhes = $this->getJson("/api/blocos/{$blocoId}")
            ->assertOk()
            ->json('dados');

        $this->assertCount(1, $detalhes['unidades']);
        $apt = $detalhes['unidades'][0];
        $this->assertEquals('101', $apt['codigo']);
        $this->assertCount(1, $apt['moradores']);
        $this->assertEquals('Carlos Alberto Silva', $apt['moradores'][0]['nome']);
        $this->assertEquals('carlos@exemplo.com', $apt['moradores'][0]['email']);
        $this->assertEquals('11988887777', $apt['moradores'][0]['telefone']);
        $this->assertCount(1, $apt['veiculos']);
        $this->assertEquals('BRA2E19', $apt['veiculos'][0]['placa']);
        $this->assertEquals('Corolla', $apt['veiculos'][0]['modelo']);

        // Consulta endpoint específico de apartamentos do bloco
        $aptsEndpoint = $this->getJson("/api/blocos/{$blocoId}/apartamentos")
            ->assertOk()
            ->json('dados');

        $this->assertCount(1, $aptsEndpoint);
        $this->assertEquals('101', $aptsEndpoint[0]['codigo']);
    }

    public function test_atualizacao_de_observacoes_do_apartamento(): void
    {
        $blocoResposta = $this->postJson('/api/blocos', [
            'nome' => 'Bloco Gama',
            'andares' => 1,
            'apartamentos_por_andar' => 1,
        ])->assertCreated();

        $unidadeId = $blocoResposta->json('dados.unidades.0.id_publico');

        // Atualização via PATCH /api/unidades/{id}/observacoes
        $resposta = $this->patchJson("/api/unidades/{$unidadeId}/observacoes", [
            'observacoes' => 'Possui vazamento no banheiro da suíte.',
        ])->assertOk();

        $this->assertEquals('Possui vazamento no banheiro da suíte.', $resposta->json('dados.observacoes'));

        // Atualização via rota alternativa PATCH /api/apartamentos/{id}/observacoes
        $respostaAlt = $this->patchJson("/api/apartamentos/{$unidadeId}/observacoes", [
            'observacoes' => 'Problema resolvido pela manutenção.',
        ])->assertOk();

        $this->assertEquals('Problema resolvido pela manutenção.', $respostaAlt->json('dados.observacoes'));
    }
}
