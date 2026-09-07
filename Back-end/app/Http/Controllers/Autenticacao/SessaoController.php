<?php

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Autenticacao\AutenticacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SessaoController extends Controller
{
    public function __construct(
        private readonly AutenticacaoService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        return response()->json([
            'dados' => $this->service->sessao($usuario),
        ]);
    }
}
