<?php

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Autenticacao\AutenticacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly AutenticacaoService $service,
    ) {}

    public function __invoke(Request $request): Response
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        $this->service->sair($usuario, $request);

        return response()->noContent();
    }
}
