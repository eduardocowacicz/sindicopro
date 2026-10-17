<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FaixaHorarioAmbiente extends ModeloBase
{
    protected $table = 'faixas_horario_ambiente';

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_id');
    }

    public function taxas(): HasMany
    {
        return $this->hasMany(TaxaAmbiente::class, 'faixa_horario_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'faixa_horario_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_validade' => 'immutable_date',
            'fim_validade' => 'immutable_date',
            'ativo' => 'boolean',
        ];
    }
}
