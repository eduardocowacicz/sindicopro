<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financeiro\MovimentacaoFinanceiraRequest;
use App\Models\MovimentacaoFinanceira;
use App\Services\Financeiro\MovimentacaoFinanceiraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MovimentacaoFinanceiraController extends Controller
{
    public function __construct(
        private readonly MovimentacaoFinanceiraService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->integer('conta_financeira_id') ?: null,
            $request->string('situacao')->value() ?: null,
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(MovimentacaoFinanceiraRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(MovimentacaoFinanceira $movimentacao_financeira): JsonResponse
    {
        return response()->json(['dados' => $movimentacao_financeira->load(['conta', 'categoria', 'tipoCobranca'])]);
    }

    public function update(MovimentacaoFinanceiraRequest $request, MovimentacaoFinanceira $movimentacao_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($movimentacao_financeira, $request->validated(), $request->user())]);
    }

    public function cancelar(Request $request, MovimentacaoFinanceira $movimentacao_financeira): JsonResponse
    {
        $this->service->cancelar($movimentacao_financeira, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['situacao' => 'CANCELADO']]);
    }

    public function historico(MovimentacaoFinanceira $movimentacao_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($movimentacao_financeira)]);
    }
}
