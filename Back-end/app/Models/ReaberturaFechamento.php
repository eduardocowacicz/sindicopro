<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReaberturaFechamento extends ModeloBase
{
    public const UPDATED_AT = null;

    protected $table = 'reaberturas_fechamento';

    public function periodoFinanceiro(): BelongsTo
    {
        return $this->belongsTo(PeriodoFinanceiro::class, 'periodo_financeiro_id');
    }

    public function fechamentoArquivado(): BelongsTo
    {
        return $this->belongsTo(FechamentoFinanceiro::class, 'fechamento_arquivado_id');
    }

    public function reabertoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'reaberto_por');
    }

    protected function casts(): array
    {
        return [
            'reaberto_em' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
        ];
    }
}
