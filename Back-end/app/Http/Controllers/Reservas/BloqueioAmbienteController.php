<?php

namespace App\Http\Controllers\Reservas;

use App\Http\Controllers\Controller;
use App\Models\BloqueioAmbiente;
use App\Services\Reservas\BloqueioAmbienteService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BloqueioAmbienteController extends Controller
{
    public function __construct(
        private readonly BloqueioAmbienteService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ambienteId = null;
        if ($request->filled('ambiente_id')) {
            $ambienteId = ResolvedorIdPublico::resolver('ambientes', $request->string('ambiente_id')->value());
        }

        $paginador = $this->service->listar($ambienteId, (int) $request->integer('por_pagina', 20));

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
        $ambienteId = ResolvedorIdPublico::resolver('ambientes', $request->string('ambiente_id')->value());
        $faixaId = null;
        if ($request->filled('faixa_horario_id')) {
            $faixaId = ResolvedorIdPublico::resolver('faixas_horario_ambiente', $request->string('faixa_horario_id')->value());
        }

        $dados = [
            'ambiente_id' => $ambienteId,
            'faixa_horario_id' => $faixaId,
            'inicio_vigencia' => $request->input('inicio_vigencia'),
            'fim_vigencia' => $request->input('fim_vigencia'),
            'motivo' => $request->input('motivo'),
        ];

        return response()->json(['dados' => $this->service->criar($dados, $request->user())], 201);
    }

    public function cancelar(Request $request, BloqueioAmbiente $bloqueio): JsonResponse
    {
        $this->service->cancelar($bloqueio, $request->user());

        return response()->json(['dados' => ['cancelado' => true]]);
    }
}
