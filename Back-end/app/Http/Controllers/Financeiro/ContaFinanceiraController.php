<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financeiro\ContaFinanceiraRequest;
use App\Models\ContaFinanceira;
use App\Services\Financeiro\ContaFinanceiraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContaFinanceiraController extends Controller
{
    public function __construct(
        private readonly ContaFinanceiraService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar($request->boolean('somente_ativos'), (int) $request->integer('por_pagina', 20));

        $itens = collect($paginador->items())->map(function (ContaFinanceira $conta) {
            $saldos = $conta->calcularSaldos();
            $contaArray = $conta->toArray();
            $contaArray['saldo_atual'] = $saldos['saldo_total'];
            $contaArray['saldo_capital_giro'] = $saldos['saldo_capital_giro'];
            $contaArray['saldo_fundo_reserva'] = $saldos['saldo_fundo_reserva'];

            return $contaArray;
        });

        return response()->json([
            'dados' => $itens,
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(ContaFinanceiraRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(ContaFinanceira $conta_financeira): JsonResponse
    {
        $saldos = $conta_financeira->calcularSaldos();
        $dados = $conta_financeira->toArray();
        $dados['saldo_atual'] = $saldos['saldo_total'];
        $dados['saldo_capital_giro'] = $saldos['saldo_capital_giro'];
        $dados['saldo_fundo_reserva'] = $saldos['saldo_fundo_reserva'];

        return response()->json(['dados' => $dados]);
    }

    public function update(ContaFinanceiraRequest $request, ContaFinanceira $conta_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($conta_financeira, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, ContaFinanceira $conta_financeira): JsonResponse
    {
        $this->service->inativar($conta_financeira, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, ContaFinanceira $conta_financeira): JsonResponse
    {
        $this->service->reativar($conta_financeira, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(ContaFinanceira $conta_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($conta_financeira)]);
    }
}
