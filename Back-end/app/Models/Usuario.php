<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class Usuario extends Authenticatable
{
    use HasUuids, Notifiable;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'usuarios';

    protected $guarded = [];

    protected $hidden = [
        'id',
        'pessoa_id',
        'senha',
        'token_lembrar',
        'criado_por',
        'atualizado_por',
    ];

    protected $rememberTokenName = 'token_lembrar';

    public function uniqueIds(): array
    {
        return ['id_publico'];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'email_verificado_em' => 'immutable_datetime',
            'ultimo_acesso_em' => 'immutable_datetime',
            'deve_alterar_senha' => 'boolean',
            'ativo' => 'boolean',
            'bloqueado_em' => 'immutable_datetime',
        ];
    }
}
