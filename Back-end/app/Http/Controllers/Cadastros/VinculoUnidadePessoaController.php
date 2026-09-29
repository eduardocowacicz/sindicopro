<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\VinculoUnidadePessoaRequest;
use App\Models\VinculoUnidadePessoa;
use App\Services\Cadastros\VinculoUnidadePessoaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VinculoUnidadePessoaController extends Controller
{
    public function __construct(
        private readonly VinculoUnidadePessoaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->integer('unidade_id') ?: null,
            $request->integer('pessoa_id') ?: null,
            $request->boolean('somente_vigentes'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(VinculoUnidadePessoaRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(VinculoUnidadePessoa $vinculo): JsonResponse
    {
        return response()->json(['dados' => $vinculo->load(['unidade.bloco', 'pessoa'])]);
    }

    public function encerrar(Request $request, VinculoUnidadePessoa $vinculo): JsonResponse
    {
        $this->service->encerrar($vinculo, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['encerrado' => true]]);
    }

    public function historico(VinculoUnidadePessoa $vinculo): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($vinculo)]);
    }
}
