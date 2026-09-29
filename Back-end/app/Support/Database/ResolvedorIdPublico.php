<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResolvedorIdPublico
{
    public static function resolver(string $tabela, ?string $idPublico, string $campo = 'id'): ?int
    {
        if ($idPublico === null || $idPublico === '') {
            return null;
        }

        $id = DB::table($tabela)->where('id_publico', $idPublico)->value($campo);

        if ($id === null) {
            throw ValidationException::withMessages([
                $tabela => ["Registro informado não foi encontrado em {$tabela}."],
            ]);
        }

        return $id;
    }

    /**
     * @return array<int>
     */
    public static function resolverMuitos(string $tabela, array $idsPublicos): array
    {
        return DB::table($tabela)->whereIn('id_publico', $idsPublicos)->pluck('id')->all();
    }
}
