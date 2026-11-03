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

        if (is_numeric($idPublico)) {
            $id = DB::table($tabela)->where('id', (int) $idPublico)->value($campo);
            if ($id !== null) {
                return (int) $id;
            }
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
    public static function resolverMuitos(string $tabela, array $valores): array
    {
        $ids = [];
        $idsPublicos = [];

        foreach ($valores as $valor) {
            if (is_numeric($valor)) {
                $ids[] = (int) $valor;
            } elseif (is_string($valor) && $valor !== '') {
                $idsPublicos[] = $valor;
            }
        }

        if ($idsPublicos !== []) {
            $resolvidos = DB::table($tabela)->whereIn('id_publico', $idsPublicos)->pluck('id')->all();
            $ids = array_merge($ids, $resolvidos);
        }

        return array_values(array_unique($ids));
    }
}
