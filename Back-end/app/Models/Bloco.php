<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Bloco extends ModeloBase
{
    protected $table = 'blocos';

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidade::class, 'bloco_id');
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
