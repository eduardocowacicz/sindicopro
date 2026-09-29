<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Models\Permissao;
use Illuminate\Http\JsonResponse;

final class PermissaoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'dados' => Permissao::query()->orderBy('modulo')->orderBy('acao')->get(),
        ]);
    }
}
