<?php

namespace Tests\Feature\Comunicados;

use App\Models\Bloco;
use App\Models\Comunicado;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ComunicadosTest extends TestCase
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

    public function test_listar_comunicados(): void
    {
        $resposta = $this->getJson('/api/comunicados');
        $resposta->assertOk();
        $this->assertNotEmpty($resposta->json('dados'));
    }

    public function test_criar_publicar_e_cancelar_comunicado(): void
    {
        // Criar como rascunho
        $respostaCriacao = $this->postJson('/api/comunicados', [
            'titulo' => 'Aviso Teste de Dedetização',
            'conteudo' => 'A dedetização semestral das áreas comuns ocorrerá na próxima semana.',
            'tipo_publico' => 'TODOS',
            'publicar_agora' => false,
        ]);

        $respostaCriacao->assertCreated();
        $comunicadoId = $respostaCriacao->json('dados.id_publico');
        $this->assertEquals('RASCUNHO', Comunicado::query()->where('id_publico', $comunicadoId)->value('situacao'));

        // Publicar
        $respostaPublicar = $this->postJson("/api/comunicados/{$comunicadoId}/publicar");
        $respostaPublicar->assertOk();
        $this->assertEquals('PUBLICADO', Comunicado::query()->where('id_publico', $comunicadoId)->value('situacao'));

        // Cancelar
        $respostaCancelar = $this->postJson("/api/comunicados/{$comunicadoId}/cancelar");
        $respostaCancelar->assertOk();
        $this->assertEquals('CANCELADO', Comunicado::query()->where('id_publico', $comunicadoId)->value('situacao'));
    }

    public function test_criar_comunicado_segmentado_por_bloco(): void
    {
        $bloco = Bloco::query()->firstOrFail();

        $respostaCriacao = $this->postJson('/api/comunicados', [
            'titulo' => 'Manutenção Específica do Bloco',
            'conteudo' => 'Comunicado direcionado exclusivamente para este bloco.',
            'tipo_publico' => 'BLOCOS',
            'blocos' => [$bloco->id_publico],
            'publicar_agora' => true,
        ]);

        $respostaCriacao->assertCreated();
        $comunicadoId = $respostaCriacao->json('dados.id_publico');
        $this->assertEquals('PUBLICADO', Comunicado::query()->where('id_publico', $comunicadoId)->value('situacao'));
    }
}
