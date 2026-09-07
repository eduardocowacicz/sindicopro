<?php

namespace App\Models;

final class Perfil extends ModeloBase
{
    protected $table = 'perfis';

    protected function casts(): array
    {
        return [
            'sistema' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
