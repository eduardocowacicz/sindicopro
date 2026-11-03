<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class TipoOcorrencia extends ModeloBase
{
    protected $table = 'tipos_ocorrencia';

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class, 'tipo_ocorrencia_id');
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }
}
