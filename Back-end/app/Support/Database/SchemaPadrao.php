<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

final class SchemaPadrao
{
    public static function identificacao(Blueprint $table): void
    {
        $table->id();
        $table->uuid('id_publico')->default(DB::raw('gen_random_uuid()'));
    }

    public static function datas(Blueprint $table): void
    {
        $table->timestampTz('criado_em')->useCurrent();
        $table->timestampTz('atualizado_em')->useCurrent();
    }

    public static function entidade(Blueprint $table): void
    {
        self::identificacao($table);
        self::datas($table);
        $table->foreignId('criado_por')->nullable();
        $table->foreignId('atualizado_por')->nullable();
    }

    public static function unicoIdPublico(Blueprint $table, string $nomeTabela): void
    {
        $table->unique('id_publico', "uq_{$nomeTabela}_id_publico");
    }
}
