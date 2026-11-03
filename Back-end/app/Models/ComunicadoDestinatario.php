<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ComunicadoDestinatario extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    protected $table = 'comunicado_destinatarios';

    protected $guarded = [];

    public function comunicado(): BelongsTo
    {
        return $this->belongsTo(Comunicado::class, 'comunicado_id');
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    protected function casts(): array
    {
        return [
            'enviado_em' => 'immutable_datetime',
            'entregue_em' => 'immutable_datetime',
            'lido_em' => 'immutable_datetime',
        ];
    }
}
