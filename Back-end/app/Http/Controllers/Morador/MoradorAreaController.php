<?php

namespace App\Http\Controllers\Morador;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use App\Models\Ocorrencia;
use App\Models\Reserva;
use App\Services\Morador\MoradorAreaService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MoradorAreaController extends Controller
{
    public function __construct(
        private readonly MoradorAreaService $service,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->dashboard($request->user())]);
    }

    public function unidade(Request $request): JsonResponse
    {
        $unidadeId = null;
        if ($request->filled('unidade_id')) {
            $unidadeId = ResolvedorIdPublico::resolver('unidades', $request->string('unidade_id')->value());
        }

        return response()->json(['dados' => $this->service->minhaUnidade($request->user(), $unidadeId)]);
    }

    public function cobrancas(Request $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->minhasCobrancas($request->user())]);
    }

    public function fechamentos(): JsonResponse
    {
        return response()->json(['dados' => $this->service->fechamentosPublicados()]);
    }

    public function ambientes(): JsonResponse
    {
        return response()->json(['dados' => $this->service->ambientesDisponiveis()]);
    }

    public function reservas(Request $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->minhasReservas($request->user())]);
    }

    public function storeReserva(Request $request): JsonResponse
    {
        $request->validate([
            'ambiente_id' => ['required'],
            'faixa_horario_id' => ['required'],
            'data_reserva' => ['required', 'date'],
            'unidade_id' => ['nullable'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $ambienteId = ResolvedorIdPublico::resolver('ambientes', $request->string('ambiente_id')->value());
        $faixaId = ResolvedorIdPublico::resolver('faixas_horario_ambiente', $request->string('faixa_horario_id')->value());
        $unidadeId = null;
        if ($request->filled('unidade_id')) {
            $unidadeId = ResolvedorIdPublico::resolver('unidades', $request->string('unidade_id')->value());
        }

        $dados = [
            'ambiente_id' => $ambienteId,
            'faixa_horario_id' => $faixaId,
            'unidade_id' => $unidadeId,
            'data_reserva' => $request->input('data_reserva'),
            'observacoes' => $request->input('observacoes'),
        ];

        return response()->json(['dados' => $this->service->criarReserva($dados, $request->user())], 201);
    }

    public function cancelarReserva(Request $request, Reserva $reserva): JsonResponse
    {
        return response()->json(['dados' => $this->service->cancelarReserva($reserva, $request->user())]);
    }

    public function comunicados(Request $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->meusComunicados($request->user())]);
    }

    public function marcarComunicadoLido(Request $request, Comunicado $comunicado): JsonResponse
    {
        $this->service->marcarComunicadoLido($comunicado, $request->user());

        return response()->json(['dados' => ['lido' => true]]);
    }

    public function ocorrencias(Request $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->minhasOcorrencias($request->user())]);
    }

    public function storeOcorrencia(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_id' => ['required'],
            'motivo' => ['required', 'string', 'max:180'],
            'descricao' => ['required', 'string'],
            'unidade_id' => ['nullable'],
        ]);

        $tipoId = ResolvedorIdPublico::resolver('tipos_ocorrencia', $request->string('tipo_id')->value());
        $unidadeId = null;
        if ($request->filled('unidade_id')) {
            $unidadeId = ResolvedorIdPublico::resolver('unidades', $request->string('unidade_id')->value());
        }

        $dados = [
            'tipo_ocorrencia_id' => $tipoId,
            'unidade_id' => $unidadeId,
            'motivo' => $request->input('motivo'),
            'descricao' => $request->input('descricao'),
        ];

        return response()->json(['dados' => $this->service->criarOcorrencia($dados, $request->user())], 201);
    }

    public function comentarOcorrencia(Request $request, Ocorrencia $ocorrencia): JsonResponse
    {
        $request->validate([
            'conteudo' => ['required', 'string'],
        ]);

        $this->service->comentarOcorrencia($ocorrencia, $request->string('conteudo')->value(), $request->user());

        return response()->json(['dados' => ['sucesso' => true]], 201);
    }
}
