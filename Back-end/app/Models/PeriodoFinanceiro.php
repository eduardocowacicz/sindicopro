<?php

namespace App\Models;

final class PeriodoFinanceiro extends ModeloBase
{
    protected $table = 'periodos_financeiros';

    protected function casts(): array
    {
        return [
            'periodo' => 'immutable_date',
            'fechado_em' => 'immutable_datetime',
        ];
    }
}
