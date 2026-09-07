<?php

namespace App\Models;

final class Pessoa extends ModeloBase
{
    protected $table = 'pessoas';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
