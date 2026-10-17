<?php

namespace Tests\Feature\Reservas;

use App\Models\Ambiente;
use App\Models\BloqueioAmbiente;
use App\Models\Reserva;
use App\Models\Unidade;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReservasTest extends TestCase
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

    public function test_listar_ambientes_de_reserva(): void
    {
        $resposta = $this->getJson('/api/reservas/ambientes');
        $resposta->assertOk();
        $this->assertNotEmpty($resposta->json('dados'));
    }

    public function test_cadastrar_atualizar_e_inativar_ambiente(): void
    {
        $respostaCriacao = $this->postJson('/api/reservas/ambientes', [
            'nome' => 'Espaço Gourmet Teste',
            'descricao' => 'Espaço com churrasqueira e forno de pizza',
            'horas_antecedencia_minima_morador' => 24,
            'horas_limite_cancelamento_morador' => 12,
            'bloquear_unidades_inadimplentes' => true,
            'aprovacao_automatica' => false,
            'faixas' => [
                [
                    'nome' => 'Almoço',
                    'inicia_em' => '11:00:00',
                    'termina_em' => '15:00:00',
                    'valor' => 120.00,
                ],
            ],
        ]);

        $respostaCriacao->assertCreated();
        $idPublico = $respostaCriacao->json('dados.id_publico');

        // Atualizar
        $respostaUpdate = $this->putJson("/api/reservas/ambientes/{$idPublico}", [
            'nome' => 'Espaço Gourmet Renovado',
            'descricao' => 'Descrição atualizada',
        ]);
        $respostaUpdate->assertOk();
        $this->assertEquals('Espaço Gourmet Renovado', $respostaUpdate->json('dados.nome'));

        // Inativar
        $this->postJson("/api/reservas/ambientes/{$idPublico}/inativar")->assertOk();
        $this->assertFalse(Ambiente::query()->where('id_publico', $idPublico)->value('ativo'));

        // Reativar
        $this->postJson("/api/reservas/ambientes/{$idPublico}/reativar")->assertOk();
        $this->assertTrue(Ambiente::query()->where('id_publico', $idPublico)->value('ativo'));
    }

    public function test_criar_e_cancelar_bloqueio_de_ambiente(): void
    {
        $ambiente = Ambiente::query()->firstOrFail();

        $dataInicio = Carbon::today()->addDays(10)->toDateString();
        $dataFim = Carbon::today()->addDays(12)->toDateString();

        $respostaBloqueio = $this->postJson('/api/reservas/bloqueios', [
            'ambiente_id' => $ambiente->id_publico,
            'inicio_vigencia' => "{$dataInicio} 08:00:00",
            'fim_vigencia' => "{$dataFim} 18:00:00",
            'motivo' => 'Reforma preventiva do piso',
        ]);

        $respostaBloqueio->assertCreated();
        $bloqueioId = $respostaBloqueio->json('dados.id_publico');

        // Cancelar bloqueio
        $this->postJson("/api/reservas/bloqueios/{$bloqueioId}/cancelar")->assertOk();
        $this->assertNotNull(BloqueioAmbiente::query()->where('id_publico', $bloqueioId)->value('cancelado_em'));
    }

    public function test_fluxo_de_criacao_validacao_e_cancelamento_de_reserva(): void
    {
        $ambiente = Ambiente::query()->firstOrFail();
        $faixa = $ambiente->faixasHorario()->firstOrFail();
        $unidade = Unidade::query()->firstOrFail();

        $dataReserva = Carbon::today()->addDays(20)->toDateString();

        $respostaCriacao = $this->postJson('/api/reservas/agenda', [
            'ambiente_id' => $ambiente->id_publico,
            'faixa_horario_id' => $faixa->id_publico,
            'unidade_id' => $unidade->id_publico,
            'data_reserva' => $dataReserva,
            'observacoes' => 'Aniversário de teste',
            'valor_taxa' => 100.00,
        ]);

        $respostaCriacao->assertCreated();
        $reservaId = $respostaCriacao->json('dados.id_publico');

        // Validar / Aprovar
        $respostaValidar = $this->postJson("/api/reservas/agenda/{$reservaId}/validar");
        $respostaValidar->assertOk();
        $this->assertEquals('CONFIRMADO', Reserva::query()->where('id_publico', $reservaId)->value('situacao'));

        // Cancelar
        $respostaCancelar = $this->postJson("/api/reservas/agenda/{$reservaId}/cancelar", [
            'motivo' => 'Cancelado a pedido do condômino',
        ]);
        $respostaCancelar->assertOk();
        $this->assertEquals('CANCELADO', Reserva::query()->where('id_publico', $reservaId)->value('situacao'));
    }
}
