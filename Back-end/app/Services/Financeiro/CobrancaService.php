<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaFinanceira;
use App\Models\Cobranca;
use App\Models\ContaFinanceira;
use App\Models\MovimentacaoFinanceira;
use App\Models\PeriodoFinanceiro;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CobrancaService
{
    use RegistraAuditoria;

    public function listar(array $filtros, int $porPagina = 25): array
    {
        $hoje = now()->toDateString();

        // 1. Atualiza cobranças em aberto vencidas para VENCIDO caso estejam vencidas
        Cobranca::query()
            ->where('situacao', 'ABERTO')
            ->whereDate('data_vencimento', '<', $hoje)
            ->update(['situacao' => 'VENCIDO']);

        // 2. Query com filtros
        $query = Cobranca::query()
            ->with(['unidade.bloco', 'pessoa', 'fechamento.periodoFinanceiro', 'itens'])
            ->orderByDesc('data_vencimento')
            ->orderBy('codigo_referencia');

        if (! empty($filtros['situacao']) && $filtros['situacao'] !== 'TODOS') {
            $query->where('situacao', $filtros['situacao']);
        }

        if (! empty($filtros['mes'])) {
            $query->whereMonth('data_vencimento', (int) $filtros['mes']);
        }

        if (! empty($filtros['ano'])) {
            $query->whereYear('data_vencimento', (int) $filtros['ano']);
        }

        if (! empty($filtros['busca'])) {
            $termo = '%'.mb_strtolower(trim($filtros['busca'])).'%';
            $query->where(function ($q) use ($termo) {
                $q->whereRaw('LOWER(codigo_referencia) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(descricao) LIKE ?', [$termo])
                    ->orWhereHas('unidade', function ($qu) use ($termo) {
                        $qu->whereRaw('LOWER(codigo) LIKE ?', [$termo])
                            ->orWhereHas('bloco', fn ($qb) => $qb->whereRaw('LOWER(nome) LIKE ?', [$termo]));
                    })
                    ->orWhereHas('pessoa', fn ($qp) => $qp->whereRaw('LOWER(nome_completo) LIKE ?', [$termo]));
            });
        }

        $paginador = $query->paginate($porPagina);

        // 3. Totais e KPIs Consolidados
        $baseTotalQuery = Cobranca::query();
        if (! empty($filtros['mes'])) {
            $baseTotalQuery->whereMonth('data_vencimento', (int) $filtros['mes']);
        }
        if (! empty($filtros['ano'])) {
            $baseTotalQuery->whereYear('data_vencimento', (int) $filtros['ano']);
        }

        $totalGeral = (float) (clone $baseTotalQuery)->where('situacao', '!=', 'CANCELADO')->sum('valor_original');
        $totalAberto = (float) (clone $baseTotalQuery)->where('situacao', 'ABERTO')->sum('valor_original');
        $totalPago = (float) (clone $baseTotalQuery)->where('situacao', 'PAGO')->sum('valor_original');
        $totalVencido = (float) (clone $baseTotalQuery)->where('situacao', 'VENCIDO')->sum('valor_original');

        $qtdTotal = (int) (clone $baseTotalQuery)->where('situacao', '!=', 'CANCELADO')->count();
        $qtdAberto = (int) (clone $baseTotalQuery)->where('situacao', 'ABERTO')->count();
        $qtdPago = (int) (clone $baseTotalQuery)->where('situacao', 'PAGO')->count();
        $qtdVencido = (int) (clone $baseTotalQuery)->where('situacao', 'VENCIDO')->count();

        $taxaAdimplencia = $qtdTotal > 0 ? round(($qtdPago / $qtdTotal) * 100, 1) : 100.0;

        $itensFormatados = collect($paginador->items())->map(function (Cobranca $c) {
            $itemOrdinario = $c->itens->firstWhere('tipo_item', 'RATEIO');
            $itemFundo = $c->itens->firstWhere('tipo_item', 'TAXA_RESERVA');

            return [
                'id_publico' => $c->id_publico,
                'codigo_referencia' => $c->codigo_referencia,
                'unidade_codigo' => $c->unidade?->codigo ?? '-',
                'bloco_nome' => $c->unidade?->bloco?->nome ?? '-',
                'morador_nome' => $c->pessoa?->nome_completo ?? 'Morador',
                'morador_email' => $c->pessoa?->email,
                'morador_telefone' => $c->pessoa?->telefone,
                'papel_pagador' => $c->papel_pagador,
                'descricao' => $c->descricao,
                'data_emissao' => $c->data_emissao->toDateString(),
                'data_vencimento' => $c->data_vencimento->toDateString(),
                'valor_original' => (float) $c->valor_original,
                'valor_cota_ordinaria' => $itemOrdinario ? (float) $itemOrdinario->valor : (float) $c->valor_original,
                'valor_fundo_reserva' => $itemFundo ? (float) $itemFundo->valor : 0.0,
                'situacao' => $c->situacao,
                'itens' => $c->itens->map(fn ($it) => [
                    'tipo_item' => $it->tipo_item,
                    'descricao' => $it->descricao,
                    'valor' => (float) $it->valor,
                ]),
                'fechamento' => $c->fechamento ? [
                    'id_publico' => $c->fechamento->id_publico,
                    'periodo' => $c->fechamento->periodoFinanceiro?->periodo?->toDateString(),
                ] : null,
            ];
        });

        return [
            'dados' => $itensFormatados,
            'kpis' => [
                'total_geral' => $totalGeral,
                'total_aberto' => $totalAberto,
                'total_pago' => $totalPago,
                'total_vencido' => $totalVencido,
                'qtd_total' => $qtdTotal,
                'qtd_aberto' => $qtdAberto,
                'qtd_pago' => $qtdPago,
                'qtd_vencido' => $qtdVencido,
                'taxa_adimplencia' => $taxaAdimplencia,
            ],
            'meta' => [
                'pagina_atual' => $paginador->currentPage(),
                'por_pagina' => $paginador->perPage(),
                'total' => $paginador->total(),
                'ultima_pagina' => $paginador->lastPage(),
            ],
        ];
    }

    public function detalhar(Cobranca $cobranca): array
    {
        $cobranca->load(['unidade.bloco', 'pessoa', 'fechamento.periodoFinanceiro', 'itens']);
        $itemOrdinario = $cobranca->itens->firstWhere('tipo_item', 'RATEIO');
        $itemFundo = $cobranca->itens->firstWhere('tipo_item', 'TAXA_RESERVA');

        return [
            'id_publico' => $cobranca->id_publico,
            'codigo_referencia' => $cobranca->codigo_referencia,
            'unidade_codigo' => $cobranca->unidade?->codigo,
            'bloco_nome' => $cobranca->unidade?->bloco?->nome,
            'morador_nome' => $cobranca->pessoa?->nome_completo,
            'morador_email' => $cobranca->pessoa?->email,
            'morador_telefone' => $cobranca->pessoa?->telefone,
            'papel_pagador' => $cobranca->papel_pagador,
            'descricao' => $cobranca->descricao,
            'data_emissao' => $cobranca->data_emissao->toDateString(),
            'data_vencimento' => $cobranca->data_vencimento->toDateString(),
            'valor_original' => (float) $cobranca->valor_original,
            'valor_cota_ordinaria' => $itemOrdinario ? (float) $itemOrdinario->valor : (float) $cobranca->valor_original,
            'valor_fundo_reserva' => $itemFundo ? (float) $itemFundo->valor : 0.0,
            'situacao' => $cobranca->situacao,
            'itens' => $cobranca->itens->map(fn ($it) => [
                'tipo_item' => $it->tipo_item,
                'descricao' => $it->descricao,
                'valor' => (float) $it->valor,
            ]),
        ];
    }

    public function registrarPagamento(Cobranca $cobranca, array $dados, Usuario $usuario): Cobranca
    {
        if (in_array($cobranca->situacao, ['PAGO', 'CANCELADO'], true)) {
            throw ValidationException::withMessages([
                'cobranca' => ["A cobrança já está {$cobranca->situacao} e não pode receber pagamento."],
            ]);
        }

        return DB::transaction(function () use ($cobranca, $dados, $usuario): Cobranca {
            $dataPagamento = filled($dados['data_pagamento'] ?? null)
                ? Carbon::parse($dados['data_pagamento'])->toDateString()
                : now()->toDateString();

            $valorPago = isset($dados['valor_pago']) && $dados['valor_pago'] > 0
                ? (float) $dados['valor_pago']
                : (float) $cobranca->valor_original;

            $contaId = $dados['conta_financeira_id'] ?? null;
            $conta = $contaId
                ? ContaFinanceira::query()->find($contaId)
                : ContaFinanceira::query()->where('ativo', true)->first();

            if (! $conta) {
                throw ValidationException::withMessages([
                    'conta_financeira_id' => ['Conta bancária ativa para crédito não foi localizada.'],
                ]);
            }

            // Período financeiro correspondente à data do pagamento
            $periodo = PeriodoFinanceiro::query()->firstOrCreate(
                ['periodo' => Carbon::parse($dataPagamento)->startOfMonth()->toDateString()],
                [
                    'situacao' => 'ABERTO',
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ]
            );

            // Categoria de recebimento de cota condominial
            $categoria = CategoriaFinanceira::query()
                ->where('direcao', 'ENTRADA')
                ->where('ativo', true)
                ->first();

            // Movimentação financeira de entrada (crédito na conta)
            $movimentacao = MovimentacaoFinanceira::query()->create([
                'periodo_financeiro_id' => $periodo->getKey(),
                'conta_financeira_id' => $conta->getKey(),
                'categoria_financeira_id' => $categoria?->getKey() ?? 1,
                'pessoa_id' => $cobranca->pessoa_id,
                'direcao' => 'ENTRADA',
                'natureza' => 'NORMAL',
                'situacao' => 'CONTABILIZADO',
                'descricao' => sprintf('Recebimento Boleto %s - Apto %s', $cobranca->codigo_referencia, $cobranca->unidade?->codigo ?? ''),
                'nome_contraparte' => $cobranca->pessoa?->nome_completo,
                'data_competencia' => $periodo->periodo,
                'data_efetiva' => $dataPagamento,
                'data_vencimento' => $cobranca->data_vencimento,
                'valor' => $valorPago,
                'contabilizado_em' => now(),
                'contabilizado_por' => $usuario->getKey(),
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            // Atualiza status da cobrança
            $cobranca->forceFill([
                'situacao' => 'PAGO',
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $this->auditarAtualizacao($usuario, 'Cobranca', $cobranca->getKey(), ['situacao' => 'ABERTO'], [
                'situacao' => 'PAGO',
                'valor_pago' => $valorPago,
                'pago_em' => $dataPagamento,
                'movimentacao_financeira_id' => $movimentacao->getKey(),
            ]);

            return $cobranca->fresh(['unidade.bloco', 'pessoa', 'itens']);
        });
    }

    public function cancelar(Cobranca $cobranca, string $motivo, Usuario $usuario): void
    {
        if ($cobranca->situacao === 'PAGO') {
            throw ValidationException::withMessages([
                'cobranca' => ['Cobrança já paga não pode ser cancelada.'],
            ]);
        }

        $cobranca->forceFill([
            'situacao' => 'CANCELADO',
            'cancelado_em' => now(),
            'cancelado_por' => $usuario->getKey(),
            'motivo_cancelamento' => $motivo,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarAtualizacao($usuario, 'Cobranca', $cobranca->getKey(), ['situacao' => $cobranca->situacao], [
            'situacao' => 'CANCELADO',
            'motivo' => $motivo,
        ]);
    }
}
