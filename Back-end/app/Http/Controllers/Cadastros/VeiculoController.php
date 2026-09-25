<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\VeiculoRequest;
use App\Models\Veiculo;
use App\Services\Cadastros\VeiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VeiculoController extends Controller
{
    public function __construct(
        private readonly VeiculoService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $request->integer('unidade_id') ?: null,
            $request->boolean('somente_ativos'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(VeiculoRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(Veiculo $veiculo): JsonResponse
    {
        return response()->json(['dados' => $veiculo->load(['unidade.bloco', 'pessoa'])]);
    }

    public function update(VeiculoRequest $request, Veiculo $veiculo): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($veiculo, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, Veiculo $veiculo): JsonResponse
    {
        $this->service->inativar($veiculo, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Veiculo $veiculo): JsonResponse
    {
        $this->service->reativar($veiculo, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Veiculo $veiculo): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($veiculo)]);
    }
}
