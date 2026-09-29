<?php

namespace App\Http\Middleware;

use App\Services\Autorizacao\PermissaoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerificaPermissao
{
    public function __construct(
        private readonly PermissaoService $permissaoService,
    ) {}

    public function handle(Request $request, Closure $next, string $codigo): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario !== null && $this->permissaoService->permite($usuario, $codigo),
            403,
            'Você não tem permissão para executar esta ação.',
        );

        return $next($request);
    }
}
