<?php

namespace App\Models;

final class ContaFinanceira extends ModeloBase
{
    protected $table = 'contas_financeiras';

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'data_saldo_inicial' => 'immutable_date',
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
