<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaxaAmbiente extends ModeloBase
{
    protected $table = 'taxas_ambiente';

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_id');
    }

    public function faixaHorario(): BelongsTo
    {
        return $this->belongsTo(FaixaHorarioAmbiente::class, 'faixa_horario_id');
    }

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'inicio_validade' => 'immutable_date',
            'fim_validade' => 'immutable_date',
        ];
    }
}
