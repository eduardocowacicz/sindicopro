<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RateioFechamento extends ModeloBase
{
    public const UPDATED_AT = null;

    protected $table = 'rateios_fechamento';

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(FechamentoFinanceiro::class, 'fechamento_financeiro_id');
    }

    public function tipoCobranca(): BelongsTo
    {
        return $this->belongsTo(TipoCobranca::class, 'tipo_cobranca_id');
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(RateioUnidadeFechamento::class, 'rateio_fechamento_id');
    }

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'diferenca_arredondamento' => 'decimal:2',
            'quantidade_unidades_participantes' => 'integer',
            'quantidade_unidades_isentas' => 'integer',
            'criado_em' => 'immutable_datetime',
        ];
    }
}
