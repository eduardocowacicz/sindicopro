<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Unidade extends ModeloBase
{
    protected $table = 'unidades';

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
            get: fn (mixed $value) => $this->relationLoaded('bloco') ? $this->bloco?->id_publico : $value,
        );
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
