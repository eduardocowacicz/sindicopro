<?php

namespace App\Services\Cadastros;

use App\Models\Unidade;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class UnidadeService
{
    use RegistraAuditoria;

    public function listar(?string $busca, ?int $blocoId, bool $somenteAtivas, int $porPagina): LengthAwarePaginator
    {
        return Unidade::query()
            ->with('bloco')
            ->when($busca, fn ($query) => $query->whereRaw('LOWER(codigo) LIKE ?', ['%'.mb_strtolower($busca).'%']))
            ->when($blocoId, fn ($query) => $query->where('bloco_id', $blocoId))
            ->when($somenteAtivas, fn ($query) => $query->where('ativo', true))
            ->orderBy('bloco_id')->orderBy('codigo')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Unidade
    {
        $unidade = Unidade::query()->create([
            ...$dados,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'Unidade', $unidade->getKey(), $dados);

        return $unidade->load('bloco');
    }

    public function atualizar(Unidade $unidade, array $dados, Usuario $usuario): Unidade
    {
        $antes = $unidade->only(array_keys($dados));
        $unidade->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $unidade->save();

        $this->auditarAtualizacao($usuario, 'Unidade', $unidade->getKey(), $antes, $dados);

        return $unidade->load('bloco');
    }

    public function inativar(Unidade $unidade, Usuario $usuario, ?string $motivo): void
    {
        if ($unidade->vinculos()->whereNull('fim_vigencia')->exists()) {
            throw ValidationException::withMessages([
                'unidade' => ['Não é possível inativar uma unidade com vínculos vigentes. Encerre os vínculos primeiro.'],
            ]);
        }

        $unidade->forceFill([
            'ativo' => false,
            'inativado_em' => now(),
            'inativado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'Unidade', $unidade->getKey(), $motivo);
    }

    public function reativar(Unidade $unidade, Usuario $usuario): void
    {
        $unidade->forceFill([
            'ativo' => true,
            'inativado_em' => null,
            'inativado_por' => null,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarReativacao($usuario, 'Unidade', $unidade->getKey());
    }

    public function historico(Unidade $unidade)
    {
        return $this->auditoriaService()->historico('Unidade', $unidade->getKey());
    }
}
