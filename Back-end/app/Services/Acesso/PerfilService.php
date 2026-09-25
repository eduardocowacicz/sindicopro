<?php

namespace App\Services\Acesso;

use App\Models\Perfil;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PerfilService
{
    use RegistraAuditoria;

    public function listar(bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Perfil::query()
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Perfil
    {
        $perfil = Perfil::query()->create([
            'codigo' => $dados['codigo'],
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'sistema' => false,
            'ativo' => true,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'Perfil', $perfil->getKey(), $dados);

        return $perfil;
    }

    public function atualizar(Perfil $perfil, array $dados, Usuario $usuario): Perfil
    {
        $antes = $perfil->only(['codigo', 'nome', 'descricao']);
        $perfil->fill([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'atualizado_por' => $usuario->getKey(),
        ]);
        $perfil->save();

        $this->auditarAtualizacao($usuario, 'Perfil', $perfil->getKey(), $antes, $dados);

        return $perfil;
    }

    public function inativar(Perfil $perfil, Usuario $usuario, ?string $motivo): void
    {
        if ($perfil->sistema) {
            throw ValidationException::withMessages([
                'perfil' => ['Perfis do sistema não podem ser inativados.'],
            ]);
        }

        $perfil->forceFill(['ativo' => false, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'Perfil', $perfil->getKey(), $motivo);
    }

    public function reativar(Perfil $perfil, Usuario $usuario): void
    {
        $perfil->forceFill(['ativo' => true, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarReativacao($usuario, 'Perfil', $perfil->getKey());
    }

    public function permissoes(Perfil $perfil): Collection
    {
        return DB::table('permissoes')
            ->leftJoin('perfil_permissoes', function ($join) use ($perfil): void {
                $join->on('perfil_permissoes.permissao_id', '=', 'permissoes.id')
                    ->where('perfil_permissoes.perfil_id', '=', $perfil->getKey());
            })
            ->orderBy('permissoes.modulo')->orderBy('permissoes.acao')
            ->get([
                'permissoes.id_publico', 'permissoes.codigo', 'permissoes.modulo', 'permissoes.acao',
                'permissoes.nome', 'permissoes.sensivel',
                DB::raw('(perfil_permissoes.id IS NOT NULL) as concedida'),
            ]);
    }

    public function definirPermissoes(Perfil $perfil, array $permissaoIds, Usuario $usuario): void
    {
        DB::transaction(function () use ($perfil, $permissaoIds, $usuario): void {
            DB::table('perfil_permissoes')->where('perfil_id', $perfil->getKey())->delete();

            $linhas = array_map(fn (int $permissaoId): array => [
                'perfil_id' => $perfil->getKey(),
                'permissao_id' => $permissaoId,
                'concedido_por' => $usuario->getKey(),
                'criado_em' => now(),
            ], $permissaoIds);

            if ($linhas !== []) {
                DB::table('perfil_permissoes')->insert($linhas);
            }
        });

        $this->auditoriaService()->registrar($usuario, 'GERENCIAR_PERMISSOES', 'Perfil', $perfil->getKey(), null, ['permissoes' => $permissaoIds]);
    }

    public function historico(Perfil $perfil)
    {
        return $this->auditoriaService()->historico('Perfil', $perfil->getKey());
    }
}
