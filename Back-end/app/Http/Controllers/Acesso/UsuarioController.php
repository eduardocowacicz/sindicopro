<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acesso\UsuarioRequest;
use App\Models\Usuario;
use App\Services\Acesso\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $service,
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
            'meta' => ['pagina_atual' => $paginador->currentPage(), 'por_pagina' => $paginador->perPage(), 'total' => $paginador->total(), 'ultima_pagina' => $paginador->lastPage()],
        ]);
    }

    public function store(UsuarioRequest $request): JsonResponse
    {
        return response()->json(['dados' => $this->service->criar($request->validated(), $request->user())], 201);
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json(['dados' => $usuario->load('pessoa')]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario): JsonResponse
    {
        return response()->json(['dados' => $this->service->atualizar($usuario, $request->validated(), $request->user())]);
    }

    public function inativar(Request $request, Usuario $usuario): JsonResponse
    {
        $this->service->inativar($usuario, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['inativado' => true]]);
    }

    public function reativar(Request $request, Usuario $usuario): JsonResponse
    {
        $this->service->reativar($usuario, $request->user());

        return response()->json(['dados' => ['ativo' => true]]);
    }

    public function bloquear(Request $request, Usuario $usuario): JsonResponse
    {
        $this->service->bloquear($usuario, $request->user(), $request->string('motivo')->value() ?: null);

        return response()->json(['dados' => ['bloqueado' => true]]);
    }

    public function desbloquear(Request $request, Usuario $usuario): JsonResponse
    {
        $this->service->desbloquear($usuario, $request->user());

        return response()->json(['dados' => ['bloqueado' => false]]);
    }

    public function redefinirSenha(Request $request, Usuario $usuario): JsonResponse
    {
        $senhaTemporaria = $this->service->redefinirSenha($usuario, $request->user());

        return response()->json(['dados' => ['senha_temporaria' => $senhaTemporaria]]);
    }

    public function historico(Usuario $usuario): JsonResponse
    {
        return response()->json(['dados' => $this->service->historico($usuario)]);
    }
}
