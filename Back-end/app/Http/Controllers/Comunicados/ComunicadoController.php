<?php

namespace App\Http\Controllers\Comunicados;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use App\Services\Comunicados\ComunicadoService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComunicadoController extends Controller
{
    public function __construct(
        private readonly ComunicadoService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $request->string('situacao')->value() ?: null,
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

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'conteudo' => ['required', 'string'],
            'tipo_publico' => ['required', 'in:TODOS,BLOCOS'],
            'blocos' => ['nullable', 'array'],
            'publicar_agora' => ['nullable', 'boolean'],
        ]);

        $blocosIds = [];
        if ($request->filled('blocos')) {
            $blocosIds = ResolvedorIdPublico::resolverMuitos('blocos', (array) $request->input('blocos'));
        }

        $dados = [
            'titulo' => $request->input('titulo'),
            'conteudo' => $request->input('conteudo'),
            'tipo_publico' => $request->input('tipo_publico'),
            'blocos' => $blocosIds,
            'publicar_agora' => (bool) $request->input('publicar_agora', false),
        ];

        return response()->json(['dados' => $this->service->criar($dados, $request->user())], 201);
    }

    public function show(Comunicado $comunicado): JsonResponse
    {
        return response()->json([
            'dados' => $comunicado->load(['publicadoPor.pessoa', 'blocos', 'destinatarios.pessoa', 'destinatarios.unidade.bloco'])
                ->loadCount(['destinatarios', 'destinatarios as lidos_count' => fn ($q) => $q->whereNotNull('lido_em')]),
        ]);
    }

    public function publicar(Request $request, Comunicado $comunicado): JsonResponse
    {
        return response()->json(['dados' => $this->service->publicar($comunicado, $request->user())]);
    }

    public function cancelar(Request $request, Comunicado $comunicado): JsonResponse
    {
        return response()->json(['dados' => $this->service->cancelar($comunicado, $request->user())]);
    }
}
