<?php

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\LoginRequest;
use App\Services\Autenticacao\AutenticacaoService;
use Illuminate\Http\JsonResponse;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AutenticacaoService $service,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $this->service->entrar($dados['email'], $dados['senha'], $request);

        return response()->json([
            'dados' => ['autenticado' => true],
        ]);
    }
}
