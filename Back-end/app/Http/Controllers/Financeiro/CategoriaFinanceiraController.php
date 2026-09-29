<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financeiro\CategoriaFinanceiraRequest;
use App\Models\CategoriaFinanceira;
use App\Services\Financeiro\CategoriaFinanceiraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoriaFinanceiraController extends Controller
{
    public function __construct(
        private readonly CategoriaFinanceiraService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->string('direcao')->value() ?: null,
            $request->boolean('somente_ativos'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(CategoriaFinanceiraRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(CategoriaFinanceira $categoria_financeira): JsonResponse
    {
        return response()->json(['dados' => $categoria_financeira]);
    }

    public function update(CategoriaFinanceiraRequest $request, CategoriaFinanceira $categoria_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($categoria_financeira, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, CategoriaFinanceira $categoria_financeira): JsonResponse
    {
        $this->service->inativar($categoria_financeira, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, CategoriaFinanceira $categoria_financeira): JsonResponse
    {
        $this->service->reativar($categoria_financeira, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(CategoriaFinanceira $categoria_financeira): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($categoria_financeira)]);
    }
}
