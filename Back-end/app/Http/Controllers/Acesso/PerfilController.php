<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acesso\PerfilRequest;
use App\Models\Perfil;
use App\Services\Acesso\PerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PerfilController extends Controller
{
    public function __construct(
        private readonly PerfilService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginador = $this->service->listar(
            $request->boolean('somente_ativos'),
            (int) $request->integer('por_pagina', 20),
        );

        return response()->json([
            'dados' => $paginador->items(),
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(PerfilRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(Perfil $perfil): JsonResponse
    {
        return response()->json(['dados' => $perfil]);
    }

    public function update(PerfilRequest $request, Perfil $perfil): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($perfil, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, Perfil $perfil): JsonResponse
    {
        $this->service->inativar($perfil, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Perfil $perfil): JsonResponse
    {
        $this->service->reativar($perfil, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function historico(Perfil $perfil): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($perfil)]);
    }
}
