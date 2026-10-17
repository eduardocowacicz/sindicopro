<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BloqueioAmbiente extends ModeloBase
{
    protected $table = 'bloqueios_ambiente';

    public const UPDATED_AT = null;

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_id');
    }

    public function faixaHorario(): BelongsTo
    {
        return $this->belongsTo(FaixaHorarioAmbiente::class, 'faixa_horario_id');
    }

    public function bloqueadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'bloqueado_por');
    }

    protected function casts(): array
    {
        return [
            'inicio_vigencia' => 'immutable_datetime',
            'fim_vigencia' => 'immutable_datetime',
            'cancelado_em' => 'immutable_datetime',
        ];
    }
}
