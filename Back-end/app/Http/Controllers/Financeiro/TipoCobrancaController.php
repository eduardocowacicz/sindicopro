<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financeiro\TipoCobrancaRequest;
use App\Models\TipoCobranca;
use App\Services\Financeiro\TipoCobrancaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoCobrancaController extends Controller
{
    public function __construct(
        private readonly TipoCobrancaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar($request->boolean('somente_ativos'), (int) $request->integer('por_pagina', 20));

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(TipoCobrancaRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(TipoCobranca $tipo_cobranca): JsonResponse
    {
        return response()->json(['dados' => $tipo_cobranca]);
    }

    public function update(TipoCobrancaRequest $request, TipoCobranca $tipo_cobranca): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($tipo_cobranca, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, TipoCobranca $tipo_cobranca): JsonResponse
    {
        $this->service->inativar($tipo_cobranca, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, TipoCobranca $tipo_cobranca): JsonResponse
    {
        $this->service->reativar($tipo_cobranca, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(TipoCobranca $tipo_cobranca): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($tipo_cobranca)]);
    }
}
