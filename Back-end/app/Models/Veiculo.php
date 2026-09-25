<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Veiculo extends ModeloBase
{
    protected $table = 'veiculos';

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    protected function unidadeId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => $this->relationLoaded('unidade') ? $this->unidade?->id_publico : $value,
        );
    }

    protected function pessoaId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => $this->relationLoaded('pessoa') ? $this->pessoa?->id_publico : $value,
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
