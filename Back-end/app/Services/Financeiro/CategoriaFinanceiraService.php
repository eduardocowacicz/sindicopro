<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaFinanceira;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;

final class CategoriaFinanceiraService
{
    use RegistraAuditoria;

    public function listar(?string $direcao, bool $somenteAtivas, int $porPagina): LengthAwarePaginator
    {
        return CategoriaFinanceira::query()
            ->when($direcao, fn ($query) => $query->where('direcao', $direcao))
            ->when($somenteAtivas, fn ($query) => $query->where('ativo', true))
            ->orderBy('codigo')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): CategoriaFinanceira
    {
        $categoria = CategoriaFinanceira::query()->create([
            ...$dados,
            'ativo' => true,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'CategoriaFinanceira', $categoria->getKey(), $dados);

        return $categoria;
    }

    public function atualizar(CategoriaFinanceira $categoria, array $dados, Usuario $usuario): CategoriaFinanceira
    {
        $antes = $categoria->only(array_keys($dados));
        $categoria->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $categoria->save();

        $this->auditarAtualizacao($usuario, 'CategoriaFinanceira', $categoria->getKey(), $antes, $dados);

        return $categoria;
    }

    public function inativar(CategoriaFinanceira $categoria, Usuario $usuario, ?string $motivo): void
    {
        $categoria->forceFill(['ativo' => false, 'inativado_em' => now(), 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'CategoriaFinanceira', $categoria->getKey(), $motivo);
    }

    public function reativar(CategoriaFinanceira $categoria, Usuario $usuario): void
    {
        $categoria->forceFill(['ativo' => true, 'inativado_em' => null, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarReativacao($usuario, 'CategoriaFinanceira', $categoria->getKey());
    }

    public function historico(CategoriaFinanceira $categoria)
    {
        return $this->auditoriaService()->historico('CategoriaFinanceira', $categoria->getKey());
    }
}
