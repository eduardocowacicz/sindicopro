<?php

namespace App\Services\Financeiro;

use App\Models\TipoCobranca;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;

final class TipoCobrancaService
{
    use RegistraAuditoria;

    public function listar(bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return TipoCobranca::query()
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): TipoCobranca
    {
        $tipo = TipoCobranca::query()->create([
            ...$dados,
            'ativo' => true,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'TipoCobranca', $tipo->getKey(), $dados);

        return $tipo;
    }

    public function atualizar(TipoCobranca $tipo, array $dados, Usuario $usuario): TipoCobranca
    {
        $antes = $tipo->only(array_keys($dados));
        $tipo->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $tipo->save();

        $this->auditarAtualizacao($usuario, 'TipoCobranca', $tipo->getKey(), $antes, $dados);

        return $tipo;
    }

    public function inativar(TipoCobranca $tipo, Usuario $usuario, ?string $motivo): void
    {
        $tipo->forceFill(['ativo' => false, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'TipoCobranca', $tipo->getKey(), $motivo);
    }

    public function reativar(TipoCobranca $tipo, Usuario $usuario): void
    {
        $tipo->forceFill(['ativo' => true, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarReativacao($usuario, 'TipoCobranca', $tipo->getKey());
    }

    public function historico(TipoCobranca $tipo)
    {
        return $this->auditoriaService()->historico('TipoCobranca', $tipo->getKey());
    }
}
