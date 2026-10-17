<?php

namespace App\Services\Cadastros;

use App\Models\Bloco;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BlocoService
{
    use RegistraAuditoria;

    public function listar(?string $busca, bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Bloco::query()
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(codigo) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(apelido) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Bloco
    {
        return DB::transaction(function () use ($dados, $usuario): Bloco {
            $andares = isset($dados['andares'])
                ? (int) $dados['andares']
                : (isset($dados['quantidade_andares']) ? (int) $dados['quantidade_andares'] : null);
            $apartamentosPorAndar = isset($dados['apartamentos_por_andar'])
                ? (int) $dados['apartamentos_por_andar']
                : 1;

            $bloco = Bloco::query()->create([
                ...$dados,
                'andares' => $andares,
                'quantidade_andares' => $andares,
                'apartamentos_por_andar' => $apartamentosPorAndar,
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            // O código vem do DEFAULT da coluna (sequência do banco); recarrega para devolvê-lo.
            $bloco->refresh();

            // Gera automaticamente os registros de Apartamentos quando apartamentos_por_andar for informado e andares > 0
            if ($andares !== null && $andares > 0 && isset($dados['apartamentos_por_andar'])) {
                for ($andar = 1; $andar <= $andares; $andar++) {
                    for ($apto = 1; $apto <= $apartamentosPorAndar; $apto++) {
                        $codigo = sprintf('%d%02d', $andar, $apto);

                        Unidade::query()->create([
                            'bloco_id' => $bloco->getKey(),
                            'codigo' => $codigo,
                            'numero_andar' => $andar,
                            'andar' => $andar,
                            'situacao_ocupacao' => 'VAGO',
                            'ativo' => true,
                            'criado_por' => $usuario->getKey(),
                            'atualizado_por' => $usuario->getKey(),
                        ]);
                    }
                }
            }

            $this->auditarCriacao($usuario, 'Bloco', $bloco->getKey(), $dados);

            return $bloco;
        });
    }

    public function carregarComApartamentos(Bloco $bloco): Bloco
    {
        return $bloco->load([
            'unidades' => function ($query) {
                $query->where('ativo', true)
                    ->orderBy('numero_andar')
                    ->orderBy('codigo')
                    ->with([
                        'vinculos' => function ($vQuery) {
                            $vQuery->whereNull('fim_vigencia')
                                ->with(['pessoa']);
                        },
                        'veiculos' => function ($veiQuery) {
                            $veiQuery->where('ativo', true);
                        },
                    ]);
            },
        ]);
    }

    public function obterApartamentosDoBloco(Bloco $bloco)
    {
        return $bloco->unidades()
            ->where('ativo', true)
            ->orderBy('numero_andar')
            ->orderBy('codigo')
            ->with([
                'vinculos' => function ($vQuery) {
                    $vQuery->whereNull('fim_vigencia')
                        ->with(['pessoa']);
                },
                'veiculos' => function ($veiQuery) {
                    $veiQuery->where('ativo', true);
                },
            ])
            ->get();
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
        if ($bloco->unidades()->whereHas('vinculos', fn ($q) => $q->whereNull('fim_vigencia'))->exists()) {
            throw ValidationException::withMessages([
                'bloco' => ['Não é possível inativar um bloco com unidades que possuem moradores vinculados. Encerre os vínculos primeiro.'],
            ]);
        }

        DB::transaction(function () use ($bloco, $usuario, $motivo): void {
            $bloco->unidades()->where('ativo', true)->update([
                'ativo' => false,
                'inativado_em' => now(),
                'inativado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            $bloco->forceFill([
                'ativo' => false,
                'inativado_em' => now(),
                'inativado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $this->auditarInativacao($usuario, 'Bloco', $bloco->getKey(), $motivo);
        });
    }

    public function reativar(Bloco $bloco, Usuario $usuario): void
    {
        DB::transaction(function () use ($bloco, $usuario): void {
            $bloco->unidades()->where('ativo', false)->update([
                'ativo' => true,
                'inativado_em' => null,
                'inativado_por' => null,
                'atualizado_por' => $usuario->getKey(),
            ]);

            $bloco->forceFill([
                'ativo' => true,
                'inativado_em' => null,
                'inativado_por' => null,
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $this->auditarReativacao($usuario, 'Bloco', $bloco->getKey());
        });
    }

    public function historico(Bloco $bloco)
    {
        return $this->auditoriaService()->historico('Bloco', $bloco->getKey());
    }
}
