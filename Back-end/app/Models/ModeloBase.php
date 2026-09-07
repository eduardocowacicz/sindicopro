<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class ModeloBase extends Model
{
    use HasUuids;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $guarded = [];

    protected $hidden = [
        'id',
        'criado_por',
        'atualizado_por',
    ];

    public function uniqueIds(): array
    {
        return ['id_publico'];
    }

    public function getRouteKeyName(): string
    {
        return 'id_publico';
    }
}
