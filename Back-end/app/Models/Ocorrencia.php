<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ocorrencia extends ModeloBase
{
    protected $table = 'ocorrencias';

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoOcorrencia::class, 'tipo_ocorrencia_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(OcorrenciaUnidade::class, 'ocorrencia_id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(ComentarioOcorrencia::class, 'ocorrencia_id')->orderBy('criado_em');
    }

    protected function casts(): array
    {
        return [
            'numero_protocolo' => 'integer',
            'interna' => 'boolean',
            'aberto_em' => 'immutable_datetime',
            'resolvido_em' => 'immutable_datetime',
            'fechado_em' => 'immutable_datetime',
        ];
    }
}
