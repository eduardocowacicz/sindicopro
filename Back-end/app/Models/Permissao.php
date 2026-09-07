<?php

namespace App\Models;

final class Permissao extends ModeloBase
{
    protected $table = 'permissoes';

    protected function casts(): array
    {
        return [
            'sensivel' => 'boolean',
        ];
    }
}
