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

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(ContaFinanceiraRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(ContaFinanceira $conta_financeira): JsonResponse
    {
        return response()->json(['dados' => $conta_financeira]);
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
