<?php

namespace App\Services\Fechamentos;

use App\Models\Cobranca;
use App\Models\ContaFinanceira;
use App\Models\FechamentoFinanceiro;
use App\Models\ItemCobranca;
use App\Models\MovimentacaoFechamento;
use App\Models\MovimentacaoFinanceira;
use App\Models\PeriodoFinanceiro;
use App\Models\Pessoa;
use App\Models\RateioFechamento;
use App\Models\RateioUnidadeFechamento;
use App\Models\ReaberturaFechamento;
use App\Models\TipoCobranca;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FechamentoMensalService
{
    use RegistraAuditoria;

    private const MESES_NOMES = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    public function listar(int $ano, ?string $filtroSituacao = null): array
    {
        $periodosDoAno = PeriodoFinanceiro::query()
            ->with(['fechamentoPublicado.publicadoPor', 'fechadoPor'])
            ->whereYear('periodo', $ano)
            ->get()
            ->keyBy(fn (PeriodoFinanceiro $p) => Carbon::parse($p->periodo)->month);

        $resultado = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $dataMes = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
            $periodoStr = $dataMes->toDateString();
            $periodo = $periodosDoAno->get($mes);
            $fechamento = $periodo?->fechamentoPublicado;

            if ($fechamento) {
                $qtdBoletos = Cobranca::query()
                    ->where('fechamento_financeiro_id', $fechamento->getKey())
                    ->count();

                $item = [
                    'mes' => $mes,
                    'ano' => $ano,
                    'nome_mes' => self::MESES_NOMES[$mes],
                    'periodo' => $periodoStr,
                    'id_publico' => $fechamento->id_publico,
                    'periodo_id_publico' => $periodo->id_publico,
                    'situacao' => 'PUBLICADO',
                    'versao' => $fechamento->versao,
                    'total_entradas' => (float) $fechamento->total_entradas,
                    'total_saidas' => (float) $fechamento->total_saidas,
                    'saldo_inicial' => (float) $fechamento->saldo_inicial,
                    'saldo_final' => (float) $fechamento->saldo_final,
                    'resultado' => (float) ($fechamento->total_entradas - $fechamento->total_saidas),
                    'total_rateado' => (float) $fechamento->total_rateado,
                    'quantidade_movimentacoes' => $fechamento->movimentacoes()->count(),
                    'quantidade_boletos' => $qtdBoletos,
                    'fechado_em' => $fechamento->publicado_em?->toIso8601String(),
                    'fechado_por' => $fechamento->publicadoPor?->nome_exibicao ?? $fechamento->publicadoPor?->email,
                ];
            } else {
                // Período aberto ou sem movimento
                $queryMovs = MovimentacaoFinanceira::query()
                    ->whereDate('data_competencia', '>=', $dataMes->copy()->startOfMonth())
                    ->whereDate('data_competencia', '<=', $dataMes->copy()->endOfMonth())
                    ->where('situacao', '!=', 'CANCELADO');

                $qtdMovs = (clone $queryMovs)->count();
                $entradas = (float) (clone $queryMovs)->where('direcao', 'ENTRADA')->sum('valor');
                $saidas = (float) (clone $queryMovs)->where('direcao', 'SAIDA')->sum('valor');

                $situacao = $periodo ? $periodo->situacao : ($qtdMovs > 0 ? 'ABERTO' : 'SEM_MOVIMENTO');

                $item = [
                    'mes' => $mes,
                    'ano' => $ano,
                    'nome_mes' => self::MESES_NOMES[$mes],
                    'periodo' => $periodoStr,
                    'id_publico' => $periodo?->id_publico ?? $periodoStr,
                    'periodo_id_publico' => $periodo?->id_publico,
                    'situacao' => $situacao,
                    'versao' => null,
                    'total_entradas' => $entradas,
                    'total_saidas' => $saidas,
                    'saldo_inicial' => 0.0,
                    'saldo_final' => (float) ($entradas - $saidas),
                    'resultado' => (float) ($entradas - $saidas),
                    'total_rateado' => $saidas > 0 ? $saidas : $entradas,
                    'quantidade_movimentacoes' => $qtdMovs,
                    'quantidade_boletos' => 0,
                    'fechado_em' => null,
                    'fechado_por' => null,
                ];
            }

            if ($filtroSituacao === null || $filtroSituacao === 'TODOS' || $item['situacao'] === $filtroSituacao) {
                $resultado[] = $item;
            }
        }

        return $resultado;
    }

    public function detalhar(string $identificador): array
    {
        $ehUuid = Str::isUuid($identificador);
        $ehData = (bool) preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $identificador);
        $dataStr = null;
        if ($ehData) {
            try {
                $dataStr = Carbon::parse($identificador)->startOfMonth()->toDateString();
            } catch (\Throwable) {
                $ehData = false;
            }
        }

        $fechamento = null;

        // 1. Tenta buscar FechamentoFinanceiro se for UUID
        if ($ehUuid) {
            $fechamento = FechamentoFinanceiro::query()
                ->with([
                    'periodoFinanceiro',
                    'publicadoPor',
                    'movimentacoes.movimentacaoFinanceira',
                    'rateios.unidades',
                    'cobrancas.unidade.bloco',
                    'cobrancas.pessoa',
                ])
                ->where('id_publico', $identificador)
                ->first();
        }

        // 2. Se não achou, tenta localizar o período financeiro
        if (! $fechamento) {
            $periodoQuery = PeriodoFinanceiro::query();
            if ($ehUuid) {
                $periodoQuery->where('id_publico', $identificador);
            } elseif ($ehData && $dataStr) {
                $periodoQuery->where('periodo', $dataStr);
            } else {
                throw ValidationException::withMessages([
                    'fechamento' => ['Identificador de fechamento inválido.'],
                ]);
            }

            $periodo = $periodoQuery->first();

            if ($periodo) {
                $fechamento = $periodo->fechamentoPublicado()
                    ->with([
                        'periodoFinanceiro',
                        'publicadoPor',
                        'movimentacoes.movimentacaoFinanceira',
                        'rateios.unidades',
                        'cobrancas.unidade.bloco',
                        'cobrancas.pessoa',
                    ])
                    ->first();
            }
        }

        if ($fechamento) {
            return $this->formatarDetalheFechamentoPublicado($fechamento);
        }

        // 3. Se não tem fechamento publicado, busca ou inicializa a prévia do período aberto
        $periodoQuery = PeriodoFinanceiro::query();
        if ($ehUuid) {
            $periodoQuery->where('id_publico', $identificador);
        } elseif ($ehData && $dataStr) {
            $periodoQuery->where('periodo', $dataStr);
        }
        $periodo = $periodoQuery->first();

        if (! $periodo && $ehData && $dataStr) {
            $periodo = new PeriodoFinanceiro([
                'periodo' => $dataStr,
                'situacao' => 'ABERTO',
            ]);
            $periodo->id_publico = $identificador;
        }

        if (! $periodo) {
            throw ValidationException::withMessages([
                'fechamento' => ['Fechamento ou período financeiro não foi localizado.'],
            ]);
        }

        return $this->montarPreviaPeriodoAberto($periodo);
    }

    public function fechar(
        string $periodoStr,
        Usuario $usuario,
        ?int $tipoCobrancaId = null,
        ?array $despesasSelecionadas = null,
        ?string $dataVencimentoBoletos = null,
        ?float $percentualFundoReserva = 10.0,
    ): FechamentoFinanceiro {
        $dataPeriodo = Carbon::parse($periodoStr)->startOfMonth();
        $dataInicio = $dataPeriodo->toDateString();
        $mes = $dataPeriodo->month;
        $ano = $dataPeriodo->year;

        return DB::transaction(function () use ($dataInicio, $dataPeriodo, $mes, $ano, $usuario, $tipoCobrancaId, $despesasSelecionadas, $dataVencimentoBoletos, $percentualFundoReserva): FechamentoFinanceiro {
            $periodo = PeriodoFinanceiro::query()->firstOrCreate(
                ['periodo' => $dataInicio],
                [
                    'situacao' => 'ABERTO',
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ],
            );

            if ($periodo->situacao === 'FECHADO' && $periodo->fechamentoPublicado()->exists()) {
                throw ValidationException::withMessages([
                    'periodo' => ['Este período já possui um fechamento publicado. Reabra o período se desejar gerar novo fechamento.'],
                ]);
            }

            // Busca os pagamentos/despesas elegíveis que ainda não foram totalmente cobrados dos moradores
            $todasDespesas = MovimentacaoFinanceira::query()
                ->with(['categoria', 'conta', 'tipoCobranca', 'pessoa'])
                ->where('direcao', 'SAIDA')
                ->where('situacao', '!=', 'CANCELADO')
                ->whereDate('data_efetiva', '<=', $dataPeriodo->copy()->endOfMonth())
                ->get();

            $itensParaRateio = [];
            $despesasFundoDiscriminadas = [];
            $totalRateado = 0.0;

            if ($despesasSelecionadas !== null && count($despesasSelecionadas) > 0) {
                // Usuário selecionou quais despesas e valores parciais cobrar
                $mapaSelecao = collect($despesasSelecionadas)->keyBy('movimentacao_id');

                foreach ($todasDespesas as $mov) {
                    $pagoComFundo = (bool) ($mov->utiliza_fundo_reserva || $mov->origem_recurso === 'FUNDO_RESERVA');
                    if ($pagoComFundo) {
                        $despesasFundoDiscriminadas[] = $mov;

                        continue;
                    }

                    if (! $mapaSelecao->has($mov->getKey())) {
                        continue;
                    }

                    $itemSel = $mapaSelecao->get($mov->getKey());
                    $valorJaCobrado = (float) MovimentacaoFechamento::query()
                        ->where('movimentacao_financeira_id', $mov->getKey())
                        ->whereHas('fechamento', fn ($q) => $q->where('situacao', 'PUBLICADO'))
                        ->sum('valor');

                    $saldoPendente = max(0, round((float) $mov->valor - $valorJaCobrado, 2));
                    $valorCobrar = min($saldoPendente, round((float) $itemSel['valor_cobrar'], 2));

                    if ($valorCobrar > 0) {
                        $itensParaRateio[] = [
                            'movimentacao' => $mov,
                            'valor_cobrar' => $valorCobrar,
                            'valor_ja_cobrado' => $valorJaCobrado,
                            'saldo_remanescente' => max(0, round($saldoPendente - $valorCobrar, 2)),
                        ];
                        $totalRateado += $valorCobrar;
                    }
                }
            } else {
                // Modo automático padrão: cobra 100% do saldo pendente de todas as despesas ordinárias não cobradas
                foreach ($todasDespesas as $mov) {
                    $pagoComFundo = (bool) ($mov->utiliza_fundo_reserva || $mov->origem_recurso === 'FUNDO_RESERVA');
                    if ($pagoComFundo) {
                        $despesasFundoDiscriminadas[] = $mov;

                        continue;
                    }

                    $valorJaCobrado = (float) MovimentacaoFechamento::query()
                        ->where('movimentacao_financeira_id', $mov->getKey())
                        ->whereHas('fechamento', fn ($q) => $q->where('situacao', 'PUBLICADO'))
                        ->sum('valor');

                    $saldoPendente = max(0, round((float) $mov->valor - $valorJaCobrado, 2));

                    if ($saldoPendente > 0) {
                        $itensParaRateio[] = [
                            'movimentacao' => $mov,
                            'valor_cobrar' => $saldoPendente,
                            'valor_ja_cobrado' => $valorJaCobrado,
                            'saldo_remanescente' => 0.0,
                        ];
                        $totalRateado += $saldoPendente;
                    }
                }
            }

            // Movimentações do próprio mês para demonstrativo de entradas e saídas
            $movimentacoesMes = MovimentacaoFinanceira::query()
                ->with(['categoria', 'conta', 'tipoCobranca', 'pessoa'])
                ->where(function ($q) use ($periodo, $dataPeriodo) {
                    $q->where('periodo_financeiro_id', $periodo->getKey())
                        ->orWhere(function ($q2) use ($dataPeriodo) {
                            $q2->whereDate('data_competencia', '>=', $dataPeriodo->copy()->startOfMonth())
                                ->whereDate('data_competencia', '<=', $dataPeriodo->copy()->endOfMonth());
                        });
                })
                ->where('situacao', '!=', 'CANCELADO')
                ->get();

            $totalEntradas = (float) $movimentacoesMes->where('direcao', 'ENTRADA')->sum('valor');
            $totalSaidas = (float) $movimentacoesMes->where('direcao', 'SAIDA')->sum('valor');

            // Se o total rateado for 0 (ex: sem despesas configuradas), usa as saídas do mês
            if ($totalRateado <= 0) {
                $totalRateado = $totalSaidas > 0 ? $totalSaidas : $totalEntradas;
            }

            // Cálculo do Fundo de Reserva (porcentagem configurável, padrão 10%)
            $percentualFundo = ($percentualFundoReserva !== null && $percentualFundoReserva >= 0) ? (float) $percentualFundoReserva : 10.0;
            $valorDespesasOrdinarias = $totalRateado;
            $valorFundoReserva = round($valorDespesasOrdinarias * ($percentualFundo / 100), 2);
            $totalRateadoGeral = $valorDespesasOrdinarias + $valorFundoReserva;

            // Saldo inicial: mês anterior ou soma das contas ativas
            $periodoAnterior = PeriodoFinanceiro::query()
                ->where('periodo', $dataPeriodo->copy()->subMonth()->startOfMonth()->toDateString())
                ->first();

            $fechamentoAnterior = $periodoAnterior?->fechamentoPublicado;
            $saldoInicial = $fechamentoAnterior
                ? (float) $fechamentoAnterior->saldo_final
                : (float) ContaFinanceira::query()->where('ativo', true)->sum('saldo_inicial');

            $saldoFinal = $saldoInicial + $totalEntradas - $totalSaidas;

            // Resumo de contas e categorias
            $resumoContas = [];
            foreach (ContaFinanceira::query()->where('ativo', true)->get() as $conta) {
                $entradasConta = (float) $movimentacoesMes->where('conta_financeira_id', $conta->getKey())->where('direcao', 'ENTRADA')->sum('valor');
                $saidasConta = (float) $movimentacoesMes->where('conta_financeira_id', $conta->getKey())->where('direcao', 'SAIDA')->sum('valor');
                $saldosConta = $conta->calcularSaldos();
                $resumoContas[] = [
                    'conta_id' => $conta->id_publico,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo_conta,
                    'entradas' => $entradasConta,
                    'saidas' => $saidasConta,
                    'saldo' => (float) $conta->saldo_inicial + $entradasConta - $saidasConta,
                    'saldo_capital_giro' => $saldosConta['saldo_capital_giro'],
                    'saldo_fundo_reserva' => $saldosConta['saldo_fundo_reserva'],
                ];
            }

            $resumoCategorias = [];
            foreach ($movimentacoesMes->groupBy('categoria_financeira_id') as $catId => $itensCat) {
                $primeiro = $itensCat->first();
                $totalCat = (float) $itensCat->sum('valor');
                $resumoCategorias[] = [
                    'categoria_id' => $primeiro->categoria?->id_publico,
                    'nome' => $primeiro->categoria?->nome ?? 'Outros',
                    'direcao' => $primeiro->direcao,
                    'total' => $totalCat,
                    'quantidade' => $itensCat->count(),
                ];
            }

            $dadosCalculo = [
                'contas' => $resumoContas,
                'categorias' => $resumoCategorias,
                'resultado_mes' => $totalEntradas - $totalSaidas,
                'unidades_totais' => Unidade::query()->where('ativo', true)->count(),
                'fundo_reserva' => [
                    'percentual' => $percentualFundo,
                    'valor' => $valorFundoReserva,
                    'despesas_ordinarias' => $valorDespesasOrdinarias,
                ],
            ];

            $versao = ((int) $periodo->fechamentos()->max('versao')) + 1;

            $fechamento = FechamentoFinanceiro::query()->create([
                'periodo_financeiro_id' => $periodo->getKey(),
                'versao' => $versao,
                'situacao' => 'PUBLICADO',
                'total_entradas' => $totalEntradas,
                'total_saidas' => $totalSaidas,
                'saldo_inicial' => $saldoInicial,
                'saldo_final' => $saldoFinal,
                'total_rateado' => $totalRateadoGeral,
                'percentual_fundo_reserva' => $percentualFundo,
                'valor_fundo_reserva' => $valorFundoReserva,
                'valor_despesas_ordinarias' => $valorDespesasOrdinarias,
                'dados_calculo' => $dadosCalculo,
                'publicado_em' => now(),
                'publicado_por' => $usuario->getKey(),
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            // Snapshots das movimentações incluídas no rateio ordinário
            foreach ($itensParaRateio as $itemRateio) {
                $mov = $itemRateio['movimentacao'];
                $valorCobrado = $itemRateio['valor_cobrar'];

                MovimentacaoFechamento::query()->create([
                    'fechamento_financeiro_id' => $fechamento->getKey(),
                    'movimentacao_financeira_id' => $mov->getKey(),
                    'direcao' => $mov->direcao,
                    'nome_categoria' => $mov->categoria?->nome ?? 'Geral',
                    'nome_tipo_cobranca' => $mov->tipoCobranca?->nome,
                    'descricao' => $mov->descricao,
                    'data_efetiva' => $mov->data_efetiva,
                    'valor' => $valorCobrado,
                ]);

                // Se a despesa foi totalmente cobrada (ou quase), marca como contabilizado
                if (($itemRateio['valor_ja_cobrado'] + $valorCobrado) >= ((float) $mov->valor - 0.01)) {
                    $mov->forceFill([
                        'situacao' => 'CONTABILIZADO',
                        'contabilizado_em' => now(),
                        'contabilizado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ])->save();
                }
            }

            // Snapshots das despesas pagas com Fundo de Reserva (discriminadas para prestação de contas, sem cobrança aos moradores)
            foreach ($despesasFundoDiscriminadas as $movFundo) {
                MovimentacaoFechamento::query()->create([
                    'fechamento_financeiro_id' => $fechamento->getKey(),
                    'movimentacao_financeira_id' => $movFundo->getKey(),
                    'direcao' => $movFundo->direcao,
                    'nome_categoria' => $movFundo->categoria?->nome ?? 'Fundo de Reserva',
                    'nome_tipo_cobranca' => $movFundo->tipoCobranca?->nome,
                    'descricao' => $movFundo->descricao.' (Pago com Fundo de Reserva)',
                    'data_efetiva' => $movFundo->data_efetiva,
                    'valor' => (float) $movFundo->valor,
                ]);

                $movFundo->forceFill([
                    'situacao' => 'CONTABILIZADO',
                    'contabilizado_em' => now(),
                    'contabilizado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ])->save();
            }

            // Tipo de cobrança padrão
            $tipoCobranca = $tipoCobrancaId
                ? TipoCobranca::query()->find($tipoCobrancaId)
                : TipoCobranca::query()->where('ativo', true)->first();

            if (! $tipoCobranca) {
                $tipoCobranca = TipoCobranca::query()->firstOrCreate(
                    ['nome' => 'Cota Condominial Ordinária'],
                    [
                        'papel_pagador' => 'PROPRIETARIO',
                        'dia_vencimento_padrao' => 10,
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );
            }

            // Unidades ativas para rateio
            $unidades = Unidade::query()
                ->where('ativo', true)
                ->with(['bloco', 'vinculos.pessoa'])
                ->get();

            $qtdUnidades = $unidades->count();

            // Data de vencimento padrão dos boletos gerados (dia 10 do mês seguinte à competência)
            $vencimentoBoletos = $dataVencimentoBoletos
                ? Carbon::parse($dataVencimentoBoletos)->toDateString()
                : $dataPeriodo->copy()->addMonth()->setDay($tipoCobranca->dia_vencimento_padrao ?? 10)->toDateString();

            if ($qtdUnidades > 0) {
                $cotaOrdinariaUnidade = round($valorDespesasOrdinarias / $qtdUnidades, 2);
                $cotaFundoUnidade = round($valorFundoReserva / $qtdUnidades, 2);
                $diferenca = round($totalRateadoGeral - (($cotaOrdinariaUnidade + $cotaFundoUnidade) * $qtdUnidades), 2);

                $rateio = RateioFechamento::query()->create([
                    'fechamento_financeiro_id' => $fechamento->getKey(),
                    'tipo_cobranca_id' => $tipoCobranca->getKey(),
                    'nome_tipo_cobranca' => $tipoCobranca->nome,
                    'papel_pagador' => $tipoCobranca->papel_pagador ?? 'PROPRIETARIO',
                    'valor_total' => $totalRateadoGeral,
                    'quantidade_unidades_participantes' => $qtdUnidades,
                    'quantidade_unidades_isentas' => 0,
                    'diferenca_arredondamento' => $diferenca,
                ]);

                foreach ($unidades as $indice => $unidade) {
                    $vinculo = $unidade->vinculos->first(fn ($v) => $v->fim_vigencia === null);
                    $ajuste = ($indice === 0) ? $diferenca : 0.0;
                    $cotaFinalOrdinaria = $cotaOrdinariaUnidade + $ajuste;
                    $valorFinalUnidade = $cotaFinalOrdinaria + $cotaFundoUnidade;

                    $pessoaId = $vinculo ? $vinculo->getRawOriginal('pessoa_id') : null;
                    if (! $pessoaId) {
                        // Se a unidade não tem morador cadastrado, atribui à primeira pessoa ativa ou síndico
                        $pessoaId = $usuario->pessoa_id ?? Pessoa::query()->first()?->getKey();
                    }

                    $rateioUnidade = RateioUnidadeFechamento::query()->create([
                        'rateio_fechamento_id' => $rateio->getKey(),
                        'unidade_id' => $unidade->getKey(),
                        'codigo_unidade' => $unidade->codigo,
                        'nome_bloco' => $unidade->bloco?->nome ?? 'Principal',
                        'vinculo_unidade_pessoa_id' => $vinculo?->getKey(),
                        'pessoa_id' => $pessoaId,
                        'nome_pessoa' => $vinculo?->pessoa?->nome_completo ?? 'Proprietário Unidade '.$unidade->codigo,
                        'participante' => true,
                        'isento' => false,
                        'valor_rateado' => $valorFinalUnidade,
                        'ajuste_arredondamento' => $ajuste,
                    ]);

                    // GERAÇÃO DO BOLETO / COBRANÇA PARA O APARTAMENTO
                    if ($pessoaId && $valorFinalUnidade > 0) {
                        $blocoSlug = Str::slug($unidade->bloco?->nome ?? 'B'.$unidade->bloco_id);
                        $codigoRef = sprintf(
                            'BOL-%04d%02d-%s-%s-%d',
                            $ano,
                            $mes,
                            substr($blocoSlug, 0, 8),
                            $unidade->codigo,
                            $unidade->getKey()
                        );

                        $cobranca = Cobranca::query()->create([
                            'tipo_origem' => 'EM_FECHAMENTO',
                            'fechamento_financeiro_id' => $fechamento->getKey(),
                            'unidade_id' => $unidade->getKey(),
                            'pessoa_id' => $pessoaId,
                            'vinculo_unidade_pessoa_id' => $vinculo?->getKey(),
                            'papel_pagador' => $tipoCobranca->papel_pagador ?? 'PROPRIETARIO',
                            'codigo_referencia' => $codigoRef,
                            'descricao' => sprintf('Cota Condominial %s/%d - Apto %s (%s)', self::MESES_NOMES[$mes], $ano, $unidade->codigo, $unidade->bloco?->nome ?? 'Bloco'),
                            'data_emissao' => now()->toDateString(),
                            'data_vencimento' => $vencimentoBoletos,
                            'valor_original' => $valorFinalUnidade,
                            'valor_desconto' => 0.0,
                            'valor_juros' => 0.0,
                            'valor_multa' => 0.0,
                            'situacao' => 'ABERTO',
                            'criado_por' => $usuario->getKey(),
                            'atualizado_por' => $usuario->getKey(),
                        ]);

                        ItemCobranca::query()->create([
                            'cobranca_id' => $cobranca->getKey(),
                            'rateio_unidade_fechamento_id' => $rateioUnidade->getKey(),
                            'tipo_item' => 'RATEIO',
                            'descricao' => sprintf('Cota Condominial Ordinária %s/%d', self::MESES_NOMES[$mes], $ano),
                            'valor' => $cotaFinalOrdinaria,
                        ]);

                        if ($cotaFundoUnidade > 0) {
                            ItemCobranca::query()->create([
                                'cobranca_id' => $cobranca->getKey(),
                                'rateio_unidade_fechamento_id' => $rateioUnidade->getKey(),
                                'tipo_item' => 'TAXA_RESERVA',
                                'descricao' => sprintf('Fundo de Reserva (%.1f%%) %s/%d', $percentualFundo, self::MESES_NOMES[$mes], $ano),
                                'valor' => $cotaFundoUnidade,
                            ]);
                        }
                    }
                }
            }

            // Atualiza status do período financeiro
            $periodo->forceFill([
                'situacao' => 'FECHADO',
                'fechado_em' => now(),
                'fechado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $this->auditarCriacao($usuario, 'FechamentoFinanceiro', $fechamento->getKey(), [
                'periodo' => $dataInicio,
                'versao' => $versao,
                'total_entradas' => $totalEntradas,
                'total_saidas' => $totalSaidas,
                'total_rateado' => $totalRateado,
                'saldo_final' => $saldoFinal,
            ]);

            return $fechamento;
        });
    }

    public function reabrir(FechamentoFinanceiro $fechamento, string $motivo, Usuario $usuario): void
    {
        if ($fechamento->situacao !== 'PUBLICADO') {
            throw ValidationException::withMessages([
                'fechamento' => ['Somente fechamentos publicados podem ser reabertos.'],
            ]);
        }

        DB::transaction(function () use ($fechamento, $motivo, $usuario): void {
            $periodo = $fechamento->periodoFinanceiro;

            // Cancela os boletos gerados por este fechamento que ainda estejam em aberto
            Cobranca::query()
                ->where('fechamento_financeiro_id', $fechamento->getKey())
                ->where('situacao', 'ABERTO')
                ->update([
                    'situacao' => 'CANCELADO',
                    'cancelado_em' => now(),
                    'cancelado_por' => $usuario->getKey(),
                    'motivo_cancelamento' => 'Fechamento reaberto: '.$motivo,
                    'atualizado_em' => now(),
                    'atualizado_por' => $usuario->getKey(),
                ]);

            ReaberturaFechamento::query()->create([
                'periodo_financeiro_id' => $periodo->getKey(),
                'fechamento_arquivado_id' => $fechamento->getKey(),
                'motivo' => $motivo,
                'reaberto_em' => now(),
                'reaberto_por' => $usuario->getKey(),
            ]);

            $fechamento->forceFill([
                'situacao' => 'ARQUIVADO',
                'arquivado_em' => now(),
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $periodo->forceFill([
                'situacao' => 'ABERTO',
                'fechado_em' => null,
                'fechado_por' => null,
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            $this->auditarAtualizacao($usuario, 'FechamentoFinanceiro', $fechamento->getKey(), ['situacao' => 'PUBLICADO'], [
                'situacao' => 'ARQUIVADO',
                'motivo' => $motivo,
            ]);
        });
    }

    private function formatarDetalheFechamentoPublicado(FechamentoFinanceiro $fechamento): array
    {
        $periodo = $fechamento->periodoFinanceiro;
        $dataPeriodo = Carbon::parse($periodo->periodo);
        $mes = $dataPeriodo->month;
        $ano = $dataPeriodo->year;

        $dadosCalculo = $fechamento->dados_calculo ?? [];

        // Monta lista de rateios de unidades
        $rateioPrincipal = $fechamento->rateios->first();
        $unidadesRateadas = [];
        if ($rateioPrincipal) {
            foreach ($rateioPrincipal->unidades as $u) {
                $unidadesRateadas[] = [
                    'id_publico' => $u->id_publico,
                    'codigo' => $u->codigo_unidade,
                    'bloco' => $u->nome_bloco,
                    'responsavel' => $u->nome_pessoa ?? 'Sem responsável cadastrado',
                    'participante' => $u->participante,
                    'isento' => $u->isento,
                    'motivo_isencao' => $u->motivo_isencao,
                    'valor' => (float) $u->valor_rateado,
                ];
            }
        }

        // Boletos / Cobranças geradas pelo fechamento
        $boletos = Cobranca::query()
            ->with(['unidade.bloco', 'pessoa', 'itens'])
            ->where('fechamento_financeiro_id', $fechamento->getKey())
            ->orderBy('codigo_referencia')
            ->get()
            ->map(function (Cobranca $c) {
                $itemOrdinario = $c->itens->firstWhere('tipo_item', 'RATEIO');
                $itemFundo = $c->itens->firstWhere('tipo_item', 'TAXA_RESERVA');

                return [
                    'id_publico' => $c->id_publico,
                    'codigo_referencia' => $c->codigo_referencia,
                    'unidade_codigo' => $c->unidade?->codigo,
                    'bloco_nome' => $c->unidade?->bloco?->nome ?? 'Bloco',
                    'morador_nome' => $c->pessoa?->nome_completo ?? 'Morador',
                    'papel_pagador' => $c->papel_pagador,
                    'descricao' => $c->descricao,
                    'data_emissao' => $c->data_emissao->toDateString(),
                    'data_vencimento' => $c->data_vencimento->toDateString(),
                    'valor' => (float) $c->valor_original,
                    'valor_cota_ordinaria' => $itemOrdinario ? (float) $itemOrdinario->valor : (float) $c->valor_original,
                    'valor_fundo_reserva' => $itemFundo ? (float) $itemFundo->valor : 0.0,
                    'situacao' => $c->situacao,
                ];
            })
            ->all();

        // Movimentações incluídas no rateio com informações de saldo remanescente
        $movimentacoesIncluidas = $fechamento->movimentacoes->map(function ($m) {
            $movOrig = $m->movimentacaoFinanceira;
            $valorOrig = $movOrig ? (float) $movOrig->valor : (float) $m->valor;
            $valorCobrado = (float) $m->valor;
            $saldoPendente = max(0, round($valorOrig - $valorCobrado, 2));

            return [
                'id' => $m->id,
                'movimentacao_id' => $m->movimentacao_financeira_id,
                'direcao' => $m->direcao,
                'categoria' => $m->nome_categoria,
                'tipo_cobranca' => $m->nome_tipo_cobranca,
                'descricao' => $m->descricao,
                'data_efetiva' => $m->data_efetiva->toDateString(),
                'valor_cobrado' => $valorCobrado,
                'valor_original' => $valorOrig,
                'saldo_pendente' => $saldoPendente,
                'cobrado_parcial' => $saldoPendente > 0.01,
            ];
        })->all();

        // Histórico de reaberturas do período
        $reaberturas = ReaberturaFechamento::query()
            ->with('reabertoPor')
            ->where('periodo_financeiro_id', $periodo->getKey())
            ->orderByDesc('reaberto_em')
            ->get()
            ->map(fn ($r) => [
                'id_publico' => $r->id_publico,
                'motivo' => $r->motivo,
                'reaberto_em' => $r->reaberto_em?->toIso8601String(),
                'reaberto_por' => $r->reabertoPor?->nome_exibicao ?? $r->reabertoPor?->email,
            ])
            ->all();

        return [
            'id_publico' => $fechamento->id_publico,
            'periodo_id_publico' => $periodo->id_publico,
            'periodo' => $periodo->periodo->toDateString(),
            'mes' => $mes,
            'ano' => $ano,
            'competencia' => sprintf('%02d/%d', $mes, $ano),
            'nome_mes' => self::MESES_NOMES[$mes],
            'situacao' => $fechamento->situacao,
            'versao' => $fechamento->versao,
            'saldo_inicial' => (float) $fechamento->saldo_inicial,
            'total_entradas' => (float) $fechamento->total_entradas,
            'total_saidas' => (float) $fechamento->total_saidas,
            'saldo_final' => (float) $fechamento->saldo_final,
            'resultado' => (float) ($fechamento->total_entradas - $fechamento->total_saidas),
            'total_rateado' => (float) $fechamento->total_rateado,
            'percentual_fundo_reserva' => (float) ($fechamento->percentual_fundo_reserva ?? 10.0),
            'valor_fundo_reserva' => (float) ($fechamento->valor_fundo_reserva ?? 0),
            'valor_despesas_ordinarias' => (float) ($fechamento->valor_despesas_ordinarias ?? $fechamento->total_rateado),
            'publicado_em' => $fechamento->publicado_em?->toIso8601String(),
            'publicado_por' => $fechamento->publicadoPor?->nome_exibicao ?? $fechamento->publicadoPor?->email,
            'contas' => $dadosCalculo['contas'] ?? [],
            'categorias' => $dadosCalculo['categorias'] ?? [],
            'movimentacoes' => $movimentacoesIncluidas,
            'despesas_disponiveis' => [],
            'boletos' => $boletos,
            'rateio' => [
                'tipo_cobranca' => $rateioPrincipal?->nome_tipo_cobranca ?? 'Cota Ordinária',
                'papel_pagador' => $rateioPrincipal?->papel_pagador ?? 'PROPRIETARIO',
                'valor_total' => (float) ($rateioPrincipal?->valor_total ?? 0),
                'unidades_participantes' => $rateioPrincipal?->quantidade_unidades_participantes ?? count($unidadesRateadas),
                'unidades' => $unidadesRateadas,
            ],
            'reaberturas' => $reaberturas,
        ];
    }

    private function montarPreviaPeriodoAberto(PeriodoFinanceiro $periodo): array
    {
        $dataPeriodo = Carbon::parse($periodo->periodo);
        $mes = $dataPeriodo->month;
        $ano = $dataPeriodo->year;

        // Todas as despesas efetuadas pelo condomínio que ainda possuem saldo não cobrado aos moradores
        $despesasCondominio = MovimentacaoFinanceira::query()
            ->with(['categoria', 'conta', 'tipoCobranca', 'pessoa'])
            ->where('direcao', 'SAIDA')
            ->where('situacao', '!=', 'CANCELADO')
            ->whereDate('data_efetiva', '<=', $dataPeriodo->copy()->endOfMonth())
            ->orderBy('data_efetiva')
            ->get();

        $despesasDisponiveis = [];
        $totalPendenteCobrar = 0.0;

        foreach ($despesasCondominio as $mov) {
            $pagoComFundo = (bool) ($mov->utiliza_fundo_reserva || $mov->origem_recurso === 'FUNDO_RESERVA');
            $valorJaCobrado = (float) MovimentacaoFechamento::query()
                ->where('movimentacao_financeira_id', $mov->getKey())
                ->whereHas('fechamento', fn ($q) => $q->where('situacao', 'PUBLICADO'))
                ->sum('valor');

            $saldoPendente = $pagoComFundo ? 0.0 : max(0, round((float) $mov->valor - $valorJaCobrado, 2));

            if ($saldoPendente > 0 || $pagoComFundo) {
                $despesasDisponiveis[] = [
                    'id' => $mov->getKey(),
                    'id_publico' => $mov->id_publico,
                    'descricao' => $mov->descricao,
                    'categoria' => $mov->categoria?->nome ?? 'Geral',
                    'fornecedor' => $mov->nome_contraparte ?? $mov->pessoa?->nome_completo ?? 'Fornecedor / Prestador',
                    'data_efetiva' => $mov->data_efetiva->toDateString(),
                    'valor_original' => (float) $mov->valor,
                    'valor_ja_cobrado' => $valorJaCobrado,
                    'valor_pendente' => $saldoPendente,
                    'valor_a_cobrar' => $saldoPendente,
                    'cobrado_parcial' => $valorJaCobrado > 0,
                    'pago_com_fundo_reserva' => $pagoComFundo,
                    'selecionado' => ! $pagoComFundo && $saldoPendente > 0,
                ];

                if (! $pagoComFundo) {
                    $totalPendenteCobrar += $saldoPendente;
                }
            }
        }

        // Movimentações do próprio mês para demonstrativo de receitas e despesas
        $movimentacoesMes = MovimentacaoFinanceira::query()
            ->with(['categoria', 'conta', 'tipoCobranca', 'pessoa'])
            ->where(function ($q) use ($periodo, $dataPeriodo) {
                if ($periodo->exists) {
                    $q->where('periodo_financeiro_id', $periodo->getKey());
                } else {
                    $q->whereDate('data_competencia', '>=', $dataPeriodo->copy()->startOfMonth())
                        ->whereDate('data_competencia', '<=', $dataPeriodo->copy()->endOfMonth());
                }
            })
            ->where('situacao', '!=', 'CANCELADO')
            ->get();

        $totalEntradas = (float) $movimentacoesMes->where('direcao', 'ENTRADA')->sum('valor');
        $totalSaidas = (float) $movimentacoesMes->where('direcao', 'SAIDA')->sum('valor');

        $periodoAnterior = PeriodoFinanceiro::query()
            ->where('periodo', $dataPeriodo->copy()->subMonth()->startOfMonth()->toDateString())
            ->first();

        $fechamentoAnterior = $periodoAnterior?->fechamentoPublicado;
        $saldoInicial = $fechamentoAnterior
            ? (float) $fechamentoAnterior->saldo_final
            : (float) ContaFinanceira::query()->where('ativo', true)->sum('saldo_inicial');

        $saldoFinal = $saldoInicial + $totalEntradas - $totalSaidas;

        $percentualFundoReserva = 10.0;
        $valorDespesasOrdinarias = $totalPendenteCobrar > 0 ? $totalPendenteCobrar : $totalSaidas;
        $valorFundoReserva = round($valorDespesasOrdinarias * ($percentualFundoReserva / 100), 2);
        $totalRateadoEstimado = $valorDespesasOrdinarias + $valorFundoReserva;

        // Contas bancárias
        $contas = [];
        foreach (ContaFinanceira::query()->where('ativo', true)->get() as $conta) {
            $e = (float) $movimentacoesMes->where('conta_financeira_id', $conta->getKey())->where('direcao', 'ENTRADA')->sum('valor');
            $s = (float) $movimentacoesMes->where('conta_financeira_id', $conta->getKey())->where('direcao', 'SAIDA')->sum('valor');
            $saldosConta = $conta->calcularSaldos();
            $contas[] = [
                'conta_id' => $conta->id_publico,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo_conta,
                'entradas' => $e,
                'saidas' => $s,
                'saldo' => (float) $conta->saldo_inicial + $e - $s,
                'saldo_capital_giro' => $saldosConta['saldo_capital_giro'],
                'saldo_fundo_reserva' => $saldosConta['saldo_fundo_reserva'],
            ];
        }

        // Categorias
        $categorias = [];
        foreach ($movimentacoesMes->groupBy('categoria_financeira_id') as $catId => $itensCat) {
            $primeiro = $itensCat->first();
            $categorias[] = [
                'categoria_id' => $primeiro->categoria?->id_publico,
                'nome' => $primeiro->categoria?->nome ?? 'Outros',
                'direcao' => $primeiro->direcao,
                'total' => (float) $itensCat->sum('valor'),
                'quantidade' => $itensCat->count(),
            ];
        }

        // Unidades e estimativa de boletos
        $unidades = Unidade::query()->where('ativo', true)->with(['bloco', 'vinculos.pessoa'])->get();
        $qtd = $unidades->count();
        $valorPorUnidade = $qtd > 0 ? round($totalRateadoEstimado / $qtd, 2) : 0.0;

        $unidadesRateadas = $unidades->map(function ($u) use ($valorPorUnidade) {
            $vinculo = $u->vinculos->first(fn ($v) => $v->fim_vigencia === null);

            return [
                'id_publico' => $u->id_publico,
                'codigo' => $u->codigo,
                'bloco' => $u->bloco?->nome ?? 'Principal',
                'responsavel' => $vinculo?->pessoa?->nome_completo ?? 'Sem morador vinculado',
                'participante' => true,
                'isento' => false,
                'valor' => $valorPorUnidade,
            ];
        })->all();

        // Simulação prévia dos boletos
        $boletosEstimados = $unidades->map(function ($u) use ($ano, $mes, $valorPorUnidade) {
            $vinculo = $u->vinculos->first(fn ($v) => $v->fim_vigencia === null);

            return [
                'id_publico' => $u->id_publico,
                'codigo_referencia' => sprintf('BOL-%04d%02d-%s (Prévia)', $ano, $mes, $u->codigo),
                'unidade_codigo' => $u->codigo,
                'bloco_nome' => $u->bloco?->nome ?? 'Bloco',
                'morador_nome' => $vinculo?->pessoa?->nome_completo ?? 'Proprietário Unidade '.$u->codigo,
                'papel_pagador' => $vinculo?->papel_cobranca ?? 'PROPRIETARIO',
                'descricao' => sprintf('Boleto Cota Condominial %s/%d - Apto %s', self::MESES_NOMES[$mes], $ano, $u->codigo),
                'data_emissao' => now()->toDateString(),
                'data_vencimento' => Carbon::createFromDate($ano, $mes, 1)->addMonth()->setDay(10)->toDateString(),
                'valor' => $valorPorUnidade,
                'situacao' => 'PREVIA',
            ];
        })->all();

        return [
            'id_publico' => $periodo->id_publico ?? $periodo->periodo->toDateString(),
            'periodo_id_publico' => $periodo->id_publico,
            'periodo' => $periodo->periodo->toDateString(),
            'mes' => $mes,
            'ano' => $ano,
            'competencia' => sprintf('%02d/%d', $mes, $ano),
            'nome_mes' => self::MESES_NOMES[$mes],
            'situacao' => 'ABERTO',
            'versao' => null,
            'saldo_inicial' => $saldoInicial,
            'total_entradas' => $totalEntradas,
            'total_saidas' => $totalSaidas,
            'saldo_final' => $saldoFinal,
            'resultado' => $totalEntradas - $totalSaidas,
            'total_rateado' => round($totalRateadoEstimado * 1.10, 2),
            'percentual_fundo_reserva' => 10.0,
            'valor_fundo_reserva' => round($totalRateadoEstimado * 0.10, 2),
            'valor_despesas_ordinarias' => $totalRateadoEstimado,
            'publicado_em' => null,
            'publicado_por' => null,
            'contas' => $contas,
            'categorias' => $categorias,
            'movimentacoes' => $movimentacoesMes->map(fn ($m) => [
                'id' => $m->id,
                'movimentacao_id' => $m->id,
                'direcao' => $m->direcao,
                'categoria' => $m->categoria?->nome ?? 'Geral',
                'tipo_cobranca' => $m->tipoCobranca?->nome,
                'descricao' => $m->descricao,
                'data_efetiva' => $m->data_efetiva->toDateString(),
                'valor_cobrado' => (float) $m->valor,
                'valor_original' => (float) $m->valor,
                'saldo_pendente' => (float) $m->valor,
                'cobrado_parcial' => false,
            ])->all(),
            'despesas_disponiveis' => $despesasDisponiveis,
            'boletos' => $boletosEstimados,
            'rateio' => [
                'tipo_cobranca' => 'Cota Condominial Ordinária (Estimada)',
                'papel_pagador' => 'PROPRIETARIO',
                'valor_total' => $totalRateadoEstimado,
                'unidades_participantes' => $qtd,
                'unidades' => $unidadesRateadas,
            ],
            'reaberturas' => [],
        ];
    }
}
