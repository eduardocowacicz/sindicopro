<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovimentacaoFinanceira extends ModeloBase
{
    protected $table = 'movimentacoes_financeiras';

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_financeira_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_financeira_id');
    }

    public function tipoCobranca(): BelongsTo
    {
        return $this->belongsTo(TipoCobranca::class, 'tipo_cobranca_id');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoFinanceiro::class, 'periodo_financeiro_id');
    }

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_competencia' => 'immutable_date',
            'data_efetiva' => 'immutable_date',
            'data_vencimento' => 'immutable_date',
            'contabilizado_em' => 'immutable_datetime',
        ];
    }
}
