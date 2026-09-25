<?php

namespace App\Models;

final class TipoCobranca extends ModeloBase
{
    protected $table = 'tipos_cobranca';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
