<?php

namespace App\Http\Controllers\Reservas;

use App\Http\Controllers\Controller;
use App\Models\Ambiente;
use App\Services\Reservas\AmbienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AmbienteController extends Controller
{
    public function __construct(
        private readonly AmbienteService $service,
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

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'horas_antecedencia_minima_morador' => ['nullable', 'integer', 'min:0'],
            'horas_limite_cancelamento_morador' => ['nullable', 'integer', 'min:0'],
            'bloquear_unidades_inadimplentes' => ['nullable', 'boolean'],
            'aprovacao_automatica' => ['nullable', 'boolean'],
            'faixas' => ['nullable', 'array'],
        ]);

        return response()->json(['dados' => $this->service->criar($dados, $request->user())], 201);
    }

    public function show(Ambiente $ambiente): JsonResponse
    {
        return response()->json(['dados' => $ambiente->load('faixasHorario')]);
    }

    public function update(Request $request, Ambiente $ambiente): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'horas_antecedencia_minima_morador' => ['nullable', 'integer', 'min:0'],
            'horas_limite_cancelamento_morador' => ['nullable', 'integer', 'min:0'],
            'bloquear_unidades_inadimplentes' => ['nullable', 'boolean'],
            'aprovacao_automatica' => ['nullable', 'boolean'],
        ]);

        return response()->json(['dados' => $this->service->atualizar($ambiente, $dados, $request->user())]);
    }

    public function inativar(Request $request, Ambiente $ambiente): JsonResponse
    {
        $this->service->inativar($ambiente, $request->user());

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Ambiente $ambiente): JsonResponse
    {
        $this->service->reativar($ambiente, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Ambiente $ambiente): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($ambiente)]);
    }
}
