<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Pessoa extends ModeloBase
{
    protected $table = 'pessoas';

    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoUnidadePessoa::class, 'pessoa_id');
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
