<?php

namespace App\Services\Reservas;

use App\Models\Ambiente;
use App\Models\BloqueioAmbiente;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;

final class BloqueioAmbienteService
{
    use RegistraAuditoria;

    public function listar(?int $ambienteId, int $porPagina = 20): LengthAwarePaginator
    {
        return BloqueioAmbiente::query()
            ->with(['ambiente', 'faixaHorario', 'bloqueadoPor'])
            ->when($ambienteId, fn ($q) => $q->where('ambiente_id', $ambienteId))
            ->orderByDesc('inicio_vigencia')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): BloqueioAmbiente
    {
        $ambiente = Ambiente::query()->findOrFail($dados['ambiente_id']);

        $bloqueio = BloqueioAmbiente::query()->create([
            'ambiente_id' => $ambiente->getKey(),
            'faixa_horario_id' => $dados['faixa_horario_id'] ?? null,
            'inicio_vigencia' => $dados['inicio_vigencia'],
            'fim_vigencia' => $dados['fim_vigencia'],
            'motivo' => $dados['motivo'],
            'bloqueado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'BloqueioAmbiente', $bloqueio->getKey(), ['motivo' => $bloqueio->motivo]);

        return $bloqueio->load(['ambiente', 'faixaHorario']);
    }

    public function cancelar(BloqueioAmbiente $bloqueio, Usuario $usuario): void
    {
        $bloqueio->forceFill([
            'cancelado_em' => now(),
            'cancelado_por' => $usuario->getKey(),
        ])->save();

        $this->auditoriaService()->registrar($usuario, 'CANCELAR_BLOQUEIO', 'BloqueioAmbiente', $bloqueio->getKey());
    }
}
