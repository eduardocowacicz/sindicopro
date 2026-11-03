<?php

namespace Tests\Feature\Ocorrencias;

use App\Models\Ocorrencia;
use App\Models\TipoOcorrencia;
use App\Models\Unidade;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OcorrenciasTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private Usuario $sindico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sindico = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $this->actingAs($this->sindico);
    }

    public function test_listar_e_cadastrar_tipos_ocorrencia(): void
    {
        $respostaListar = $this->getJson('/api/ocorrencias/tipos');
        $respostaListar->assertOk();
        $this->assertNotEmpty($respostaListar->json('dados'));

        $respostaCriacao = $this->postJson('/api/ocorrencias/tipos', [
            'codigo' => 'SEGURANCA',
            'nome' => 'Segurança e Portaria',
            'descricao' => 'Ocorrências relativas a controle de acesso e segurança',
        ]);
        $respostaCriacao->assertCreated();
        $tipoId = $respostaCriacao->json('dados.id_publico');

        // Atualizar
        $this->putJson("/api/ocorrencias/tipos/{$tipoId}", [
            'nome' => 'Segurança, Ronda e Portaria',
        ])->assertOk();

        // Inativar e reativar
        $this->postJson("/api/ocorrencias/tipos/{$tipoId}/inativar")->assertOk();
        $this->assertFalse(TipoOcorrencia::query()->where('id_publico', $tipoId)->value('ativo'));

        $this->postJson("/api/ocorrencias/tipos/{$tipoId}/reativar")->assertOk();
        $this->assertTrue(TipoOcorrencia::query()->where('id_publico', $tipoId)->value('ativo'));
    }

    public function test_fluxo_ocorrencia_comentarios_e_situacao(): void
    {
        $tipo = TipoOcorrencia::query()->firstOrFail();
        $unidade = Unidade::query()->firstOrFail();

        $respostaCriacao = $this->postJson('/api/ocorrencias', [
            'tipo_id' => $tipo->id_publico,
            'unidade_id' => $unidade->id_publico,
            'motivo' => 'Infiltração na garagem',
            'descricao' => 'Gotejamento contínuo sobre a vaga 12 do subsolo.',
            'interna' => false,
        ]);

        $respostaCriacao->assertCreated();
        $ocorrenciaId = $respostaCriacao->json('dados.id_publico');
        $this->assertEquals('ABERTO', Ocorrencia::query()->where('id_publico', $ocorrenciaId)->value('situacao'));

        // Adicionar comentário administrativo
        $respostaComentario = $this->postJson("/api/ocorrencias/{$ocorrenciaId}/comentar", [
            'conteudo' => 'Manutenção predial acionada para inspecionar a tubulação.',
            'visibilidade' => 'TODOS_PARTICIPANTES',
        ]);
        $respostaComentario->assertCreated();

        // Alterar situação para EM_ANDAMENTO
        $respostaAndamento = $this->postJson("/api/ocorrencias/{$ocorrenciaId}/alterar-situacao", [
            'situacao' => 'EM_ANDAMENTO',
            'motivo' => 'Técnico de encanamento no local',
        ]);
        $respostaAndamento->assertOk();
        $this->assertEquals('EM_ANDAMENTO', Ocorrencia::query()->where('id_publico', $ocorrenciaId)->value('situacao'));

        // Resolver ocorrência
        $respostaResolvida = $this->postJson("/api/ocorrencias/{$ocorrenciaId}/alterar-situacao", [
            'situacao' => 'RESOLVIDO',
            'motivo' => 'Cano reparado e teste de vazamento concluído com sucesso.',
        ]);
        $respostaResolvida->assertOk();
        $this->assertEquals('RESOLVIDO', Ocorrencia::query()->where('id_publico', $ocorrenciaId)->value('situacao'));
    }
}
