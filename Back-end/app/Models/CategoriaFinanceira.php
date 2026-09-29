<?php

namespace App\Models;

final class CategoriaFinanceira extends ModeloBase
{
    protected $table = 'categorias_financeiras';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
