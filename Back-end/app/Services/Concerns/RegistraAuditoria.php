<?php

namespace App\Services\Concerns;

use App\Models\Usuario;
use App\Services\Auditoria\AuditoriaService;

trait RegistraAuditoria
{
    private function auditoriaService(): AuditoriaService
    {
        return app(AuditoriaService::class);
    }

    private function auditarCriacao(?Usuario $usuario, string $tipoEntidade, int $entidadeId, array $dados): void
    {
        $this->auditoriaService()->registrar($usuario, 'CRIACAO', $tipoEntidade, $entidadeId, null, $dados);
    }

    private function auditarAtualizacao(?Usuario $usuario, string $tipoEntidade, int $entidadeId, array $antes, array $depois): void
    {
        $this->auditoriaService()->registrar($usuario, 'ATUALIZACAO', $tipoEntidade, $entidadeId, $antes, $depois);
    }

    private function auditarInativacao(?Usuario $usuario, string $tipoEntidade, int $entidadeId, ?string $motivo = null): void
    {
        $this->auditoriaService()->registrar($usuario, 'INATIVACAO', $tipoEntidade, $entidadeId, null, null, $motivo);
    }

    private function auditarReativacao(?Usuario $usuario, string $tipoEntidade, int $entidadeId): void
    {
        $this->auditoriaService()->registrar($usuario, 'REATIVACAO', $tipoEntidade, $entidadeId);
    }
}
