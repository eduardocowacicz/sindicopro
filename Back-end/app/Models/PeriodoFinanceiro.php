<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class PeriodoFinanceiro extends ModeloBase
{
    protected $table = 'periodos_financeiros';

    public function fechamentos(): HasMany
    {
        return $this->hasMany(FechamentoFinanceiro::class, 'periodo_financeiro_id');
    }

    public function fechamentoPublicado(): HasOne
    {
        return $this->hasOne(FechamentoFinanceiro::class, 'periodo_financeiro_id')
            ->where('situacao', 'PUBLICADO');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoFinanceira::class, 'periodo_financeiro_id');
    }

    public function reaberturas(): HasMany
    {
        return $this->hasMany(ReaberturaFechamento::class, 'periodo_financeiro_id');
    }

    public function fechadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'fechado_por');
    }

    protected function casts(): array
    {
        return [
            'periodo' => 'immutable_date',
            'fechado_em' => 'immutable_datetime',
        ];
    }
}
