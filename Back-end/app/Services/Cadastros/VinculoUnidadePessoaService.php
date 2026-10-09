<?php

namespace App\Services\Cadastros;

use App\Models\Usuario;
use App\Models\VinculoUnidadePessoa;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class VinculoUnidadePessoaService
{
    use RegistraAuditoria;

    public function listar(?int $unidadeId, ?int $pessoaId, bool $somenteVigentes, int $porPagina): LengthAwarePaginator
    {
        return VinculoUnidadePessoa::query()
            ->with(['unidade.bloco', 'pessoa'])
            ->when($unidadeId, fn ($query) => $query->where('unidade_id', $unidadeId))
            ->when($pessoaId, fn ($query) => $query->where('pessoa_id', $pessoaId))
            ->when($somenteVigentes, fn ($query) => $query->whereNull('fim_vigencia'))
            ->orderByDesc('inicio_vigencia')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): VinculoUnidadePessoa
    {
        $vinculo = VinculoUnidadePessoa::query()->create([
            ...$dados,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'VinculoUnidadePessoa', $vinculo->getKey(), $dados);

        return $vinculo->load(['unidade.bloco', 'pessoa']);
    }

    public function encerrar(VinculoUnidadePessoa $vinculo, Usuario $usuario, ?string $motivo): void
    {
        if ($vinculo->fim_vigencia !== null) {
            throw ValidationException::withMessages([
                'vinculo' => ['Este vínculo já está encerrado.'],
            ]);
        }

        $vinculo->forceFill([
            'fim_vigencia' => now()->toDateString(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'VinculoUnidadePessoa', $vinculo->getKey(), $motivo);
    }

    public function historico(VinculoUnidadePessoa $vinculo)
    {
        return $this->auditoriaService()->historico('VinculoUnidadePessoa', $vinculo->getKey());
    }
}
