<?php

namespace Tests\Feature\Morador;

use App\Models\Ambiente;
use App\Models\Comunicado;
use App\Models\TipoOcorrencia;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AreaMoradorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private Usuario $morador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->morador = Usuario::query()->where('email', 'morador@pro.com')->firstOrFail();
        $this->actingAs($this->morador);
    }

    public function test_morador_acessa_dashboard_e_resumo_de_sua_unidade(): void
    {
        $respostaDashboard = $this->getJson('/api/morador/dashboard');
        $respostaDashboard->assertOk();
        $this->assertArrayHasKey('unidades', $respostaDashboard->json('dados'));

        $respostaUnidade = $this->getJson('/api/morador/unidade');
        $respostaUnidade->assertOk();
        $this->assertNotNull($respostaUnidade->json('dados.unidade.codigo'));
    }

    public function test_morador_consulta_cobrancas_e_fechamentos_publicados(): void
    {
        $respostaCobrancas = $this->getJson('/api/morador/cobrancas');
        $respostaCobrancas->assertOk();

        $respostaFechamentos = $this->getJson('/api/morador/fechamentos');
        $respostaFechamentos->assertOk();
        $this->assertNotEmpty($respostaFechamentos->json('dados'));
    }

    public function test_morador_consulta_ambientes_e_cria_reserva(): void
    {
        $respostaAmbientes = $this->getJson('/api/morador/ambientes');
        $respostaAmbientes->assertOk();
        $ambientes = $respostaAmbientes->json('dados');
        $this->assertNotEmpty($ambientes);

        $ambiente = Ambiente::query()->firstOrFail();
        $faixa = $ambiente->faixasHorario()->firstOrFail();
        $dataReserva = Carbon::today()->addDays(15)->toDateString();

        $respostaReserva = $this->postJson('/api/morador/reservas', [
            'ambiente_id' => $ambiente->id_publico,
            'faixa_horario_id' => $faixa->id_publico,
            'data_reserva' => $dataReserva,
            'observacoes' => 'Reserva solicitada pelo aplicativo do morador',
        ]);

        $respostaReserva->assertCreated();
        $this->assertNotNull($respostaReserva->json('dados.id_publico'));

        // Listar minhas reservas
        $minhasReservas = $this->getJson('/api/morador/reservas');
        $minhasReservas->assertOk();
        $this->assertNotEmpty($minhasReservas->json('dados'));
    }

    public function test_morador_visualiza_e_marca_comunicado_como_lido(): void
    {
        $respostaComunicados = $this->getJson('/api/morador/comunicados');
        $respostaComunicados->assertOk();
        $dados = $respostaComunicados->json('dados');
        $this->assertNotEmpty($dados);

        $comunicado = Comunicado::query()->where('situacao', 'PUBLICADO')->firstOrFail();
        $respostaLido = $this->postJson("/api/morador/comunicados/{$comunicado->id_publico}/lido");
        $respostaLido->assertOk();
        $this->assertTrue($respostaLido->json('dados.lido'));
    }

    public function test_morador_abre_ocorrencia_e_adiciona_comentario(): void
    {
        $tipo = TipoOcorrencia::query()->firstOrFail();

        $respostaCriacao = $this->postJson('/api/morador/ocorrencias', [
            'tipo_id' => $tipo->id_publico,
            'motivo' => 'Lâmpada piscando no corredor do andar',
            'descricao' => 'A lâmpada em frente ao apartamento está oscilando constantemente.',
        ]);

        $respostaCriacao->assertCreated();
        $ocorrenciaId = $respostaCriacao->json('dados.id_publico');

        // Morador comenta
        $respostaComentario = $this->postJson("/api/morador/ocorrencias/{$ocorrenciaId}/comentar", [
            'conteudo' => 'Agradeço se puderem verificar ainda hoje.',
        ]);
        $respostaComentario->assertCreated();

        // Morador lista ocorrências
        $respostaLista = $this->getJson('/api/morador/ocorrencias');
        $respostaLista->assertOk();
        $this->assertNotEmpty($respostaLista->json('dados'));
    }
}
