<?php

namespace App\Services\Auditoria;

use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AuditoriaService
{
    public function registrar(
        ?Usuario $usuario,
        string $acao,
        string $tipoEntidade,
        ?int $entidadeId,
        ?array $dadosAnteriores = null,
        ?array $dadosPosteriores = null,
        ?string $motivo = null,
    ): void {
        DB::table('logs_auditoria')->insert([
            'usuario_id' => $usuario?->getKey(),
            'acao' => $acao,
            'tipo_entidade' => $tipoEntidade,
            'entidade_id' => $entidadeId,
            'requisicao_id' => (string) Str::uuid(),
            'endereco_ip' => request()->ip(),
            'agente_usuario' => mb_substr((string) request()->userAgent(), 0, 500),
            'motivo' => $motivo,
            'dados_anteriores' => $dadosAnteriores !== null ? json_encode($dadosAnteriores) : null,
            'dados_posteriores' => $dadosPosteriores !== null ? json_encode($dadosPosteriores) : null,
            'ocorrido_em' => now(),
        ]);
    }

    public function historico(string $tipoEntidade, int $entidadeId): Collection
    {
        return DB::table('logs_auditoria')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'logs_auditoria.usuario_id')
            ->leftJoin('pessoas', 'pessoas.id', '=', 'usuarios.pessoa_id')
            ->where('logs_auditoria.tipo_entidade', $tipoEntidade)
            ->where('logs_auditoria.entidade_id', $entidadeId)
            ->orderByDesc('logs_auditoria.ocorrido_em')
            ->get([
                'logs_auditoria.acao',
                'logs_auditoria.motivo',
                'logs_auditoria.dados_anteriores',
                'logs_auditoria.dados_posteriores',
                'logs_auditoria.ocorrido_em',
                'pessoas.nome_completo as usuario_nome',
            ]);
    }
}
