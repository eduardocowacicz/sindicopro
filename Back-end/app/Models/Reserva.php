<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Reserva extends ModeloBase
{
    protected $table = 'reservas';

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_id');
    }

    public function faixaHorario(): BelongsTo
    {
        return $this->belongsTo(FaixaHorarioAmbiente::class, 'faixa_horario_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitado_por');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'validado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por');
    }

    protected function casts(): array
    {
        return [
            'data_reserva' => 'immutable_date',
            'valor_taxa' => 'decimal:2',
            'criado_pela_administracao' => 'boolean',
            'validado_em' => 'immutable_datetime',
            'cancelado_em' => 'immutable_datetime',
        ];
    }
}
