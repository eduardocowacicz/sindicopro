<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FechamentoFinanceiro extends ModeloBase
{
    protected $table = 'fechamentos_financeiros';

    public function periodoFinanceiro(): BelongsTo
    {
        return $this->belongsTo(PeriodoFinanceiro::class, 'periodo_financeiro_id');
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'publicado_por');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoFechamento::class, 'fechamento_financeiro_id');
    }

    public function rateios(): HasMany
    {
        return $this->hasMany(RateioFechamento::class, 'fechamento_financeiro_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'fechamento_financeiro_id');
    }

    protected function casts(): array
    {
        return [
            'versao' => 'integer',
            'total_entradas' => 'decimal:2',
            'total_saidas' => 'decimal:2',
            'saldo_inicial' => 'decimal:2',
            'saldo_final' => 'decimal:2',
            'total_rateado' => 'decimal:2',
            'dados_calculo' => 'array',
            'publicado_em' => 'immutable_datetime',
            'arquivado_em' => 'immutable_datetime',
        ];
    }
}
