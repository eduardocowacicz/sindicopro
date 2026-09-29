<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\PessoaRequest;
use App\Models\Pessoa;
use App\Services\Cadastros\PessoaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PessoaController extends Controller
{
    public function __construct(
        private readonly PessoaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $request->boolean('somente_ativos'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(PessoaRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(Pessoa $pessoa): JsonResponse
    {
        return response()->json(['dados' => $pessoa]);
    }

    public function update(PessoaRequest $request, Pessoa $pessoa): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($pessoa, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, Pessoa $pessoa): JsonResponse
    {
        $this->service->inativar($pessoa, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Pessoa $pessoa): JsonResponse
    {
        $this->service->reativar($pessoa, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Pessoa $pessoa): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($pessoa)]);
    }
}
