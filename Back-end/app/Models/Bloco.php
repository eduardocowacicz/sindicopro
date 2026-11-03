<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Bloco extends ModeloBase
{
    protected $table = 'blocos';

    protected $appends = [
        'andares',
        'situacao',
    ];

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidade::class, 'bloco_id');
    }

    protected function andares(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['andares'] ?? $attributes['quantidade_andares'] ?? null,
            set: fn ($value) => [
                'andares' => $value,
                'quantidade_andares' => $value,
            ],
        );
    }

    protected function situacao(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['ativo'] ?? true) ? 'ATIVO' : 'INATIVO',
        );
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'andares' => 'integer',
            'quantidade_andares' => 'integer',
            'apartamentos_por_andar' => 'integer',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
