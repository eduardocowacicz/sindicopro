<?php

namespace App\Http\Controllers\Ocorrencias;

use App\Http\Controllers\Controller;
use App\Models\Ocorrencia;
use App\Services\Ocorrencias\OcorrenciaService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OcorrenciaController extends Controller
{
    public function __construct(
        private readonly OcorrenciaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tipoId = null;
        if ($request->filled('tipo_id')) {
            $tipoId = ResolvedorIdPublico::resolver('tipos_ocorrencia', $request->string('tipo_id')->value());
        }

        $paginador = $this->service->listar(
            $request->string('busca')->value() ?: null,
            $request->string('situacao')->value() ?: null,
            $tipoId,
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
            'tipo_id' => ['required'],
            'motivo' => ['required', 'string', 'max:180'],
            'descricao' => ['required', 'string'],
            'unidade_id' => ['nullable'],
            'interna' => ['nullable', 'boolean'],
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
            'interna' => (bool) $request->input('interna', false),
        ];

        return response()->json(['dados' => $this->service->criar($dados, $request->user())], 201);
    }

    public function show(Ocorrencia $ocorrencia): JsonResponse
    {
        return response()->json([
            'dados' => $ocorrencia->load([
                'tipo',
                'registradoPor.pessoa',
                'unidades.unidade.bloco',
                'comentarios.autor.pessoa',
            ]),
        ]);
    }

    public function comentar(Request $request, Ocorrencia $ocorrencia): JsonResponse
    {
        $request->validate([
            'conteudo' => ['required', 'string'],
            'visibilidade' => ['nullable', 'in:SOMENTE_ADMINISTRACAO,UNIDADE_SOLICITANTE,UNIDADES_RELACIONADAS,TODOS_PARTICIPANTES'],
        ]);

        $visibilidade = $request->string('visibilidade')->value() ?: 'TODOS_PARTICIPANTES';
        $comentario = $this->service->comentar($ocorrencia, $request->string('conteudo')->value(), $request->user(), $visibilidade);

        return response()->json(['dados' => $comentario], 201);
    }

    public function alterarSituacao(Request $request, Ocorrencia $ocorrencia): JsonResponse
    {
        $request->validate([
            'situacao' => ['required', 'in:ABERTO,EM_ANDAMENTO,RESOLVIDO,FECHADO,CANCELADO'],
            'motivo' => ['nullable', 'string'],
        ]);

        $ocorrenciaAtualizada = $this->service->alterarSituacao(
            $ocorrencia,
            $request->string('situacao')->value(),
            $request->user(),
            $request->string('motivo')->value() ?: null,
        );

        return response()->json(['dados' => $ocorrenciaAtualizada]);
    }

    public function historico(Ocorrencia $ocorrencia): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($ocorrencia)]);
    }
}
