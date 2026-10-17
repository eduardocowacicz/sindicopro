<?php

namespace App\Services\Reservas;

use App\Models\Ambiente;
use App\Models\BloqueioAmbiente;
use App\Models\FaixaHorarioAmbiente;
use App\Models\Reserva;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReservaService
{
    use RegistraAuditoria;

    public function listar(
        ?string $busca,
        ?int $ambienteId,
        ?string $dataInicio,
        ?string $dataFim,
        ?string $situacao,
        int $porPagina = 20,
    ): LengthAwarePaginator {
        return Reserva::query()
            ->with(['ambiente', 'faixaHorario', 'unidade.bloco', 'solicitadoPor.pessoa'])
            ->when($ambienteId, fn ($q) => $q->where('ambiente_id', $ambienteId))
            ->when($situacao && $situacao !== 'TODOS', fn ($q) => $q->where('situacao', $situacao))
            ->when($dataInicio, fn ($q) => $q->where('data_reserva', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_reserva', '<=', $dataFim))
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereHas('ambiente', fn ($a) => $a->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%']))
                ->orWhereHas('unidade', fn ($u) => $u->whereRaw('LOWER(codigo) LIKE ?', ['%'.mb_strtolower($busca).'%']))
                ->orWhereHas('solicitadoPor.pessoa', fn ($p) => $p->whereRaw('LOWER(nome_completo) LIKE ?', ['%'.mb_strtolower($busca).'%']))))
            ->orderByDesc('data_reserva')
            ->orderBy('inicia_em')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario, bool $pelaAdministracao = true): Reserva
    {
        return DB::transaction(function () use ($dados, $usuario, $pelaAdministracao): Reserva {
            $ambiente = Ambiente::query()->where('ativo', true)->findOrFail($dados['ambiente_id']);
            $faixa = FaixaHorarioAmbiente::query()->where('ambiente_id', $ambiente->getKey())->where('ativo', true)->findOrFail($dados['faixa_horario_id']);
            $unidade = Unidade::query()->findOrFail($dados['unidade_id']);
            $dataReserva = Carbon::parse($dados['data_reserva'])->toDateString();

            // 1. Verifica se ambiente está bloqueado
            $bloqueado = BloqueioAmbiente::query()
                ->where('ambiente_id', $ambiente->getKey())
                ->whereNull('cancelado_em')
                ->where('inicio_vigencia', '<=', "{$dataReserva} 23:59:59")
                ->where('fim_vigencia', '>=', "{$dataReserva} 00:00:00")
                ->exists();

            if ($bloqueado) {
                throw ValidationException::withMessages([
                    'ambiente_id' => ['O ambiente selecionado possui um bloqueio administrativo para esta data.'],
                ]);
            }

            // 2. Verifica duplicidade / conflito de reserva ativa
            $existeReserva = Reserva::query()
                ->where('ambiente_id', $ambiente->getKey())
                ->where('data_reserva', $dataReserva)
                ->where('faixa_horario_id', $faixa->getKey())
                ->where('situacao', '!=', 'CANCELADO')
                ->exists();

            if ($existeReserva) {
                throw ValidationException::withMessages([
                    'faixa_horario_id' => ['Este ambiente já possui uma reserva confirmada para o horário e data selecionados.'],
                ]);
            }

            $reserva = Reserva::query()->create([
                'ambiente_id' => $ambiente->getKey(),
                'faixa_horario_id' => $faixa->getKey(),
                'unidade_id' => $unidade->getKey(),
                'solicitado_por' => $dados['solicitado_por'] ?? $usuario->getKey(),
                'data_reserva' => $dataReserva,
                'inicia_em' => $faixa->inicia_em,
                'termina_em' => $faixa->termina_em,
                'valor_taxa' => $dados['valor_taxa'] ?? 0.00,
                'situacao' => 'CONFIRMADO',
                'criado_pela_administracao' => $pelaAdministracao,
                'observacoes' => $dados['observacoes'] ?? null,
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            DB::table('historico_situacao_reserva')->insert([
                'reserva_id' => $reserva->getKey(),
                'situacao_anterior' => null,
                'nova_situacao' => 'CONFIRMADO',
                'tipo_evento' => 'CONFIRMADA_AUTOMATICAMENTE',
                'alterado_por' => $usuario->getKey(),
                'alterado_em' => now(),
            ]);

            $this->auditarCriacao($usuario, 'Reserva', $reserva->getKey(), [
                'ambiente' => $ambiente->nome,
                'data' => $dataReserva,
                'unidade' => $unidade->codigo,
            ]);

            return $reserva->load(['ambiente', 'faixaHorario', 'unidade.bloco', 'solicitadoPor.pessoa']);
        });
    }

    public function validar(Reserva $reserva, Usuario $usuario): Reserva
    {
        $reserva->forceFill([
            'validado_em' => now(),
            'validado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        DB::table('historico_situacao_reserva')->insert([
            'reserva_id' => $reserva->getKey(),
            'situacao_anterior' => $reserva->situacao,
            'nova_situacao' => $reserva->situacao,
            'tipo_evento' => 'VALIDADO',
            'alterado_por' => $usuario->getKey(),
            'alterado_em' => now(),
        ]);

        return $reserva->load(['ambiente', 'faixaHorario', 'unidade.bloco', 'solicitadoPor.pessoa']);
    }

    public function cancelar(Reserva $reserva, Usuario $usuario, ?string $motivo = null): Reserva
    {
        if ($reserva->situacao === 'CANCELADO') {
            return $reserva;
        }

        $situacaoAnterior = $reserva->situacao;
        $reserva->forceFill([
            'situacao' => 'CANCELADO',
            'cancelado_em' => now(),
            'cancelado_por' => $usuario->getKey(),
            'observacoes' => $motivo ? ($reserva->observacoes ? "{$reserva->observacoes} | Cancelamento: {$motivo}" : "Cancelamento: {$motivo}") : $reserva->observacoes,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        DB::table('historico_situacao_reserva')->insert([
            'reserva_id' => $reserva->getKey(),
            'situacao_anterior' => $situacaoAnterior,
            'nova_situacao' => 'CANCELADO',
            'tipo_evento' => 'CANCELADO',
            'alterado_por' => $usuario->getKey(),
            'alterado_em' => now(),
        ]);

        $this->auditoriaService()->registrar($usuario, 'CANCELAR_RESERVA', 'Reserva', $reserva->getKey(), null, ['motivo' => $motivo]);

        return $reserva->load(['ambiente', 'faixaHorario', 'unidade.bloco', 'solicitadoPor.pessoa']);
    }

    public function historico(Reserva $reserva)
    {
        return DB::table('historico_situacao_reserva')
            ->join('usuarios', 'usuarios.id', '=', 'historico_situacao_reserva.alterado_por')
            ->where('reserva_id', $reserva->getKey())
            ->orderByDesc('alterado_em')
            ->select('historico_situacao_reserva.*', 'usuarios.email as autor_email')
            ->get();
    }
}
