<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cobranca extends ModeloBase
{
    protected $table = 'cobrancas';

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(FechamentoFinanceiro::class, 'fechamento_financeiro_id');
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

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemCobranca::class, 'cobranca_id');
    }

    protected function casts(): array
    {
        return [
            'data_emissao' => 'immutable_date',
            'data_vencimento' => 'immutable_date',
            'valor_original' => 'float',
            'valor_desconto' => 'float',
            'valor_juros' => 'float',
            'valor_multa' => 'float',
            'cancelado_em' => 'immutable_datetime',
        ];
    }
}
