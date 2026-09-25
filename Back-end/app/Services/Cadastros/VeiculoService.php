<?php

namespace App\Services\Cadastros;

use App\Models\Usuario;
use App\Models\Veiculo;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;

final class VeiculoService
{
    use RegistraAuditoria;

    public function listar(?string $busca, ?int $unidadeId, bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Veiculo::query()
            ->with(['unidade.bloco', 'pessoa'])
            ->when($busca, fn ($query) => $query->whereRaw('LOWER(placa) LIKE ?', ['%'.mb_strtolower($busca).'%']))
            ->when($unidadeId, fn ($query) => $query->where('unidade_id', $unidadeId))
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('placa')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Veiculo
    {
        $dados['placa'] = mb_strtoupper($dados['placa']);
        $veiculo = Veiculo::query()->create([
            ...$dados,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'Veiculo', $veiculo->getKey(), $dados);

        return $veiculo->load(['unidade.bloco', 'pessoa']);
    }

    public function atualizar(Veiculo $veiculo, array $dados, Usuario $usuario): Veiculo
    {
        $dados['placa'] = mb_strtoupper($dados['placa']);
        $antes = $veiculo->only(array_keys($dados));
        $veiculo->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $veiculo->save();

        $this->auditarAtualizacao($usuario, 'Veiculo', $veiculo->getKey(), $antes, $dados);

        return $veiculo->load(['unidade.bloco', 'pessoa']);
    }

    public function inativar(Veiculo $veiculo, Usuario $usuario, ?string $motivo): void
    {
        $veiculo->forceFill([
            'ativo' => false,
            'inativado_em' => now(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'Veiculo', $veiculo->getKey(), $motivo);
    }

    public function reativar(Veiculo $veiculo, Usuario $usuario): void
    {
        $veiculo->forceFill([
            'ativo' => true,
            'inativado_em' => null,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarReativacao($usuario, 'Veiculo', $veiculo->getKey());
    }

    public function historico(Veiculo $veiculo)
    {
        return $this->auditoriaService()->historico('Veiculo', $veiculo->getKey());
    }
}
