<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovimentacaoFechamento extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    protected $table = 'movimentacoes_fechamento';

    protected $guarded = [];

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(FechamentoFinanceiro::class, 'fechamento_financeiro_id');
    }

    public function fechamentoFinanceiro(): BelongsTo
    {
        return $this->fechamento();
    }

    public function movimentacaoFinanceira(): BelongsTo
    {
        return $this->belongsTo(MovimentacaoFinanceira::class, 'movimentacao_financeira_id');
    }

    protected function casts(): array
    {
        return [
            'data_efetiva' => 'immutable_date',
            'valor' => 'decimal:2',
            'criado_em' => 'immutable_datetime',
        ];
    }
}
