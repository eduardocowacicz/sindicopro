<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\BlocoRequest;
use App\Models\Bloco;
use App\Services\Cadastros\BlocoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlocoController extends Controller
{
    public function __construct(
        private readonly BlocoService $service,
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
            'meta' => [
                'pagina_atual' => $paginador->currentPage(),
                'por_pagina' => $paginador->perPage(),
                'total' => $paginador->total(),
                'ultima_pagina' => $paginador->lastPage(),
            ],
        ]);
    }

    public function store(BlocoRequest $request): JsonResponse
    {
        $bloco = $this->service->criar($request->validated(), $request->user());

        return response()->json(['dados' => $bloco], 201);
    }

    public function show(Bloco $bloco): JsonResponse
    {
        return response()->json(['dados' => $bloco]);
    }

    public function update(BlocoRequest $request, Bloco $bloco): JsonResponse
    {
        $bloco = $this->service->atualizar($bloco, $request->validated(), $request->user());

        return response()->json(['dados' => $bloco]);
    }

    public function inativar(Request $request, Bloco $bloco): JsonResponse
    {
        $this->service->inativar($bloco, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Bloco $bloco): JsonResponse
    {
        $this->service->reativar($bloco, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Bloco $bloco): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($bloco)]);
    }
}
