<?php

namespace App\Http\Controllers\Reservas;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\Reservas\ReservaService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ambienteId = null;
        if ($request->filled('ambiente_id')) {
            $ambienteId = ResolvedorIdPublico::resolver('ambientes', $request->string('ambiente_id')->value());
        }

        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $ambienteId,
            $request->string('data_inicio')->value() ?: null,
            $request->string('data_fim')->value() ?: null,
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
        $ambienteId = ResolvedorIdPublico::resolver('ambientes', $request->string('ambiente_id')->value());
        $faixaId = ResolvedorIdPublico::resolver('faixas_horario_ambiente', $request->string('faixa_horario_id')->value());
        $unidadeId = ResolvedorIdPublico::resolver('unidades', $request->string('unidade_id')->value());

        $dados = [
            'ambiente_id' => $ambienteId,
            'faixa_horario_id' => $faixaId,
            'unidade_id' => $unidadeId,
            'data_reserva' => $request->input('data_reserva'),
            'observacoes' => $request->input('observacoes'),
            'valor_taxa' => (float) $request->input('valor_taxa', 0.0),
        ];

        return response()->json(['dados' => $this->service->criar($dados, $request->user(), true)], 201);
    }

    public function show(Reserva $reserva): JsonResponse
    {
        return response()->json(['dados' => $reserva->load(['ambiente', 'faixaHorario', 'unidade.bloco', 'solicitadoPor.pessoa'])]);
    }

    public function validar(Request $request, Reserva $reserva): JsonResponse
    {
        return response()->json(['dados' => $this->service->validar($reserva, $request->user())]);
    }

    public function cancelar(Request $request, Reserva $reserva): JsonResponse
    {
        $motivo = $request->string('motivo')->value() ?: null;

        return response()->json(['dados' => $this->service->cancelar($reserva, $request->user(), $motivo)]);
    }

    public function historico(Reserva $reserva): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($reserva)]);
    }
}
