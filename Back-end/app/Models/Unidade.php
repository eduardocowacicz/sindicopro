<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Unidade extends ModeloBase
{
    protected $table = 'unidades';

    protected $appends = [
        'andar',
        'numero',
        'moradores',
    ];

    public function bloco(): BelongsTo
    {
        return $this->belongsTo(Bloco::class, 'bloco_id');
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoUnidadePessoa::class, 'unidade_id');
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class, 'unidade_id');
    }

    /**
     * Expõe o id público do bloco (em vez do id interno) quando o relacionamento está carregado,
     * para que o frontend nunca precise conhecer ids internos sequenciais.
     */
    protected function blocoId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => ($this->relationLoaded('bloco') && $this->getRelation('bloco')) ? $this->getRelation('bloco')->id_publico : $value,
        );
    }

    protected function andar(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['andar'] ?? $attributes['numero_andar'] ?? null,
            set: fn ($value) => [
                'andar' => $value,
                'numero_andar' => $value,
            ],
        );
    }

    protected function numero(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['codigo'] ?? null,
        );
    }

    protected function moradores(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->relationLoaded('vinculos')) {
                    return [];
                }

                return $this->vinculos
                    ->filter(fn ($v) => $v->fim_vigencia === null && $v->pessoa !== null)
                    ->map(fn ($v) => [
                        'id' => $v->pessoa->id_publico,
                        'id_publico' => $v->pessoa->id_publico,
                        'nome' => $v->pessoa->nome_completo,
                        'nome_completo' => $v->pessoa->nome_completo,
                        'email' => $v->pessoa->email,
                        'telefone' => $v->pessoa->telefone,
                        'tipo_vinculo' => $v->tipo_vinculo,
                    ])
                    ->values();
            },
        );
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'andar' => 'integer',
            'numero_andar' => 'integer',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
