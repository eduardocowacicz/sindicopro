<?php

namespace App\Services\Ocorrencias;

use App\Models\TipoOcorrencia;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;

final class TipoOcorrenciaService
{
    use RegistraAuditoria;

    public function listar(bool $somenteAtivos = true, int $porPagina = 50): LengthAwarePaginator
    {
        return TipoOcorrencia::query()
            ->when($somenteAtivos, fn ($q) => $q->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): TipoOcorrencia
    {
        $codigo = $dados['codigo'] ?? null;
        if (! $codigo) {
            $proximo = (int) (TipoOcorrencia::query()->max('id') ?? 0) + 1;
            $codigo = 'TIPO-'.str_pad((string) $proximo, 3, '0', STR_PAD_LEFT);
        }

        $tipo = TipoOcorrencia::query()->create([
            'codigo' => $codigo,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'ativo' => true,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'TipoOcorrencia', $tipo->getKey(), ['nome' => $tipo->nome]);

        return $tipo;
    }

    public function atualizar(TipoOcorrencia $tipo, array $dados, Usuario $usuario): TipoOcorrencia
    {
        $antes = $tipo->only(['nome', 'descricao']);
        $tipo->fill([
            'nome' => $dados['nome'] ?? $tipo->nome,
            'descricao' => array_key_exists('descricao', $dados) ? $dados['descricao'] : $tipo->descricao,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarAtualizacao($usuario, 'TipoOcorrencia', $tipo->getKey(), $antes, $tipo->only(['nome', 'descricao']));

        return $tipo;
    }

    public function inativar(TipoOcorrencia $tipo, Usuario $usuario): void
    {
        $tipo->forceFill([
            'ativo' => false,
            'inativado_em' => now(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'TipoOcorrencia', $tipo->getKey());
    }

    public function reativar(TipoOcorrencia $tipo, Usuario $usuario): void
    {
        $tipo->forceFill([
            'ativo' => true,
            'inativado_em' => null,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarReativacao($usuario, 'TipoOcorrencia', $tipo->getKey());
    }
}
