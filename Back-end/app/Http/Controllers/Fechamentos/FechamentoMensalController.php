<?php

namespace App\Http\Controllers\Fechamentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fechamentos\FecharPeriodoRequest;
use App\Http\Requests\Fechamentos\ReabrirFechamentoRequest;
use App\Models\FechamentoFinanceiro;
use App\Services\Fechamentos\FechamentoMensalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FechamentoMensalController extends Controller
{
    public function __construct(
        private readonly FechamentoMensalService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ano = (int) ($request->integer('ano') ?: now()->year);
        $situacao = $request->string('situacao')->value() ?: null;

        return response()->json([
            'dados' => $this->service->listar($ano, $situacao),
        ]);
    }

    public function show(string $identificador): JsonResponse
    {
        return response()->json([
            'dados' => $this->service->detalhar($identificador),
        ]);
    }

    public function store(FecharPeriodoRequest $request): JsonResponse
    {
        $fechamento = $this->service->fechar(
            $request->string('periodo')->value(),
            $request->user(),
            $request->integer('tipo_cobranca_id') ?: null,
            $request->input('despesas'),
            $request->string('data_vencimento')->value() ?: null,
            $request->input('percentual_fundo_reserva') !== null ? (float) $request->input('percentual_fundo_reserva') : 10.0,
        );

        return response()->json([
            'dados' => $this->service->detalhar($fechamento->id_publico),
        ], 201);
    }

    public function reabrir(ReabrirFechamentoRequest $request, FechamentoFinanceiro $fechamento): JsonResponse
    {
        $this->service->reabrir(
            $fechamento,
            $request->string('motivo')->value(),
            $request->user(),
        );

        return response()->json([
            'dados' => [
                'sucesso' => true,
                'mensagem' => 'Fechamento reaberto com sucesso.',
            ],
        ]);
    }
}
