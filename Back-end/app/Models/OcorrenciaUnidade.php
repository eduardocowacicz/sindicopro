<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OcorrenciaUnidade extends Model
{
    public const CREATED_AT = 'vinculado_em';

    public const UPDATED_AT = null;

    protected $table = 'ocorrencia_unidades';

    protected $guarded = [];

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(Ocorrencia::class, 'ocorrencia_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    public function vinculadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'vinculado_por');
    }

    protected function casts(): array
    {
        return [
            'visivel_para_unidade' => 'boolean',
            'vinculado_em' => 'immutable_datetime',
        ];
    }
}
