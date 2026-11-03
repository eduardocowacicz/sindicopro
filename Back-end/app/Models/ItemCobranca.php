<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ItemCobranca extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    protected $table = 'itens_cobranca';

    protected $guarded = [];

    public function cobranca(): BelongsTo
    {
        return $this->belongsTo(Cobranca::class, 'cobranca_id');
    }

    public function rateioUnidade(): BelongsTo
    {
        return $this->belongsTo(RateioUnidadeFechamento::class, 'rateio_unidade_fechamento_id');
    }

    protected function casts(): array
    {
        return [
            'valor' => 'float',
            'criado_em' => 'immutable_datetime',
        ];
    }
}
