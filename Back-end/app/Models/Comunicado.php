<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Comunicado extends ModeloBase
{
    protected $table = 'comunicados';

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'publicado_por');
    }

    public function blocos(): BelongsToMany
    {
        return $this->belongsToMany(Bloco::class, 'comunicado_blocos', 'comunicado_id', 'bloco_id');
    }

    public function destinatarios(): HasMany
    {
        return $this->hasMany(ComunicadoDestinatario::class, 'comunicado_id');
    }

    protected function casts(): array
    {
        return [
            'publicado_em' => 'immutable_datetime',
        ];
    }
}
