<?php

namespace App\Services\Cadastros;

use App\Models\Bloco;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class BlocoService
{
    use RegistraAuditoria;

    public function listar(?string $busca, bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Bloco::query()
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(codigo) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Bloco
    {
        $bloco = Bloco::query()->create([
            ...$dados,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        // O código vem do DEFAULT da coluna (sequência do banco); recarrega para devolvê-lo.
        $bloco->refresh();

        $this->auditarCriacao($usuario, 'Bloco', $bloco->getKey(), $dados);

        return $bloco;
    }

    public function atualizar(Bloco $bloco, array $dados, Usuario $usuario): Bloco
    {
        $antes = $bloco->only(array_keys($dados));
        $bloco->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $bloco->save();

        $this->auditarAtualizacao($usuario, 'Bloco', $bloco->getKey(), $antes, $dados);

        return $bloco;
    }

    public function inativar(Bloco $bloco, Usuario $usuario, ?string $motivo): void
    {
        if ($bloco->unidades()->where('ativo', true)->exists()) {
            throw ValidationException::withMessages([
                'bloco' => ['Não é possível inativar um bloco com unidades ativas.'],
            ]);
        }

        $bloco->forceFill([
            'ativo' => false,
            'inativado_em' => now(),
            'inativado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'Bloco', $bloco->getKey(), $motivo);
    }

    public function reativar(Bloco $bloco, Usuario $usuario): void
    {
        $bloco->forceFill([
            'ativo' => true,
            'inativado_em' => null,
            'inativado_por' => null,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarReativacao($usuario, 'Bloco', $bloco->getKey());
    }

    public function historico(Bloco $bloco)
    {
        return $this->auditoriaService()->historico('Bloco', $bloco->getKey());
    }
}
