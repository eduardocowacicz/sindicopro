<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acesso\PerfilPermissoesRequest;
use App\Models\Perfil;
use App\Services\Acesso\PerfilService;
use Illuminate\Http\JsonResponse;

final class PerfilPermissoesController extends Controller
{
    public function __construct(
        private readonly PerfilService $service,
    ) {}

    public function index(Perfil $perfil): JsonResponse
    {
        return response()->json(['dados' => $this->service->permissoes($perfil)]);
    }

    public function update(PerfilPermissoesRequest $request, Perfil $perfil): JsonResponse
    {
        $this->service->definirPermissoes($perfil, $request->validated()['permissoes'], $request->user());

        return response()->json(['dados' => $this->service->permissoes($perfil)]);
    }
}
