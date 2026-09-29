<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\UnidadeRequest;
use App\Models\Unidade;
use App\Services\Cadastros\UnidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UnidadeController extends Controller
{
    public function __construct(
        private readonly UnidadeService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $request->integer('bloco_id') ?: null,
            $request->boolean('somente_ativos'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(UnidadeRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(Unidade $unidade): JsonResponse
    {
        return response()->json(['dados' => $unidade->load('bloco')]);
    }

    public function update(UnidadeRequest $request, Unidade $unidade): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($unidade, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, Unidade $unidade): JsonResponse
    {
        $this->service->inativar($unidade, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Unidade $unidade): JsonResponse
    {
        $this->service->reativar($unidade, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Unidade $unidade): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($unidade)]);
    }
}
