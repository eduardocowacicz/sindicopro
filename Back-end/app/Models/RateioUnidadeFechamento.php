<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RateioUnidadeFechamento extends ModeloBase
{
    public const UPDATED_AT = null;

    protected $table = 'rateios_unidades_fechamento';

    public function rateio(): BelongsTo
    {
        return $this->belongsTo(RateioFechamento::class, 'rateio_fechamento_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(VinculoUnidadePessoa::class, 'vinculo_unidade_pessoa_id');
    }

    protected function casts(): array
    {
        return [
            'participante' => 'boolean',
            'isento' => 'boolean',
            'valor_rateado' => 'decimal:2',
            'ajuste_arredondamento' => 'decimal:2',
            'criado_em' => 'immutable_datetime',
        ];
    }
}
