<?php

namespace App\Services\Financeiro;

use App\Models\MovimentacaoFinanceira;
use App\Models\PeriodoFinanceiro;
use App\Models\Pessoa;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class MovimentacaoFinanceiraService
{
    use RegistraAuditoria;

    public function listar(?int $contaId, ?string $situacao, int $porPagina): LengthAwarePaginator
    {
        return MovimentacaoFinanceira::query()
            ->with(['conta', 'categoria', 'tipoCobranca', 'pessoa'])
            ->when($contaId, fn ($query) => $query->where('conta_financeira_id', $contaId))
            ->when($situacao, fn ($query) => $query->where('situacao', $situacao))
            ->orderByDesc('data_efetiva')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): MovimentacaoFinanceira
    {
        $periodo = $this->obterOuCriarPeriodo(Carbon::parse($dados['data_competencia']), $usuario);

        if ($periodo->situacao === 'FECHADO') {
            throw ValidationException::withMessages([
                'data_competencia' => ['O período financeiro desta competência já está fechado.'],
            ]);
        }

        if (! empty($dados['pessoa_id']) && empty($dados['nome_contraparte'])) {
            $pessoa = Pessoa::query()->find($dados['pessoa_id']);
            if ($pessoa) {
                $dados['nome_contraparte'] = $pessoa->nome_completo;
            }
        }

        $utilizaFundo = ! empty($dados['utiliza_fundo_reserva']) || ($dados['origem_recurso'] ?? null) === 'FUNDO_RESERVA';
        $dados['utiliza_fundo_reserva'] = $utilizaFundo;
        $dados['origem_recurso'] = $utilizaFundo ? 'FUNDO_RESERVA' : 'CAPITAL_GIRO';

        $movimentacao = MovimentacaoFinanceira::query()->create([
            ...$dados,
            'periodo_financeiro_id' => $periodo->getKey(),
            'situacao' => 'RASCUNHO',
            'natureza' => 'NORMAL',
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'MovimentacaoFinanceira', $movimentacao->getKey(), [
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'direcao' => $dados['direcao'],
        ]);

        return $movimentacao->load(['conta', 'categoria', 'tipoCobranca', 'pessoa']);
    }

    public function atualizar(MovimentacaoFinanceira $movimentacao, array $dados, Usuario $usuario): MovimentacaoFinanceira
    {
        if ($movimentacao->situacao !== 'RASCUNHO') {
            throw ValidationException::withMessages([
                'movimentacao' => ['Somente lançamentos em rascunho podem ser editados.'],
            ]);
        }

        if (! empty($dados['pessoa_id']) && empty($dados['nome_contraparte'])) {
            $pessoa = Pessoa::query()->find($dados['pessoa_id']);
            if ($pessoa) {
                $dados['nome_contraparte'] = $pessoa->nome_completo;
            }
        }

        if (array_key_exists('utiliza_fundo_reserva', $dados) || array_key_exists('origem_recurso', $dados)) {
            $utilizaFundo = ! empty($dados['utiliza_fundo_reserva']) || ($dados['origem_recurso'] ?? null) === 'FUNDO_RESERVA';
            $dados['utiliza_fundo_reserva'] = $utilizaFundo;
            $dados['origem_recurso'] = $utilizaFundo ? 'FUNDO_RESERVA' : 'CAPITAL_GIRO';
        }

        $antes = $movimentacao->only(['descricao', 'valor']);
        $movimentacao->fill([...$dados, 'atualizado_por' => $usuario->getKey()]);
        $movimentacao->save();

        $this->auditarAtualizacao($usuario, 'MovimentacaoFinanceira', $movimentacao->getKey(), $antes, [
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
        ]);

        return $movimentacao->load(['conta', 'categoria', 'tipoCobranca', 'pessoa']);
    }

    public function cancelar(MovimentacaoFinanceira $movimentacao, Usuario $usuario, ?string $motivo): void
    {
        if ($movimentacao->situacao !== 'RASCUNHO') {
            throw ValidationException::withMessages([
                'movimentacao' => ['Somente lançamentos em rascunho podem ser cancelados.'],
            ]);
        }

        $movimentacao->forceFill(['situacao' => 'CANCELADO', 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'MovimentacaoFinanceira', $movimentacao->getKey(), $motivo);
    }

    public function historico(MovimentacaoFinanceira $movimentacao)
    {
        return $this->auditoriaService()->historico('MovimentacaoFinanceira', $movimentacao->getKey());
    }

    private function obterOuCriarPeriodo(Carbon $competencia, Usuario $usuario): PeriodoFinanceiro
    {
        $inicioMes = $competencia->copy()->startOfMonth()->toDateString();

        return PeriodoFinanceiro::query()->firstOrCreate(
            ['periodo' => $inicioMes],
            [
                'situacao' => 'ABERTO',
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ],
        );
    }
}
