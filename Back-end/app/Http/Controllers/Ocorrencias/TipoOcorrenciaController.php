<?php

namespace App\Http\Controllers\Ocorrencias;

use App\Http\Controllers\Controller;
use App\Models\TipoOcorrencia;
use App\Services\Ocorrencias\TipoOcorrenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoOcorrenciaController extends Controller
{
    public function __construct(
        private readonly TipoOcorrenciaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->boolean('somente_ativos', false),
            (int) $request->integer('por_pagina', 50),
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

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
        ]);

        return response()->json(['dados' => $this->service->criar($dados, $request->user())], 201);
    }

    public function show(TipoOcorrencia $tipo): JsonResponse
    {
        return response()->json(['dados' => $tipo]);
    }

    public function update(Request $request, TipoOcorrencia $tipo): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
        ]);

        return response()->json(['dados' => $this->service->atualizar($tipo, $dados, $request->user())]);
    }

    public function inativar(Request $request, TipoOcorrencia $tipo): JsonResponse
    {
        $this->service->inativar($tipo, $request->user());

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, TipoOcorrencia $tipo): JsonResponse
    {
        $this->service->reativar($tipo, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }
}
