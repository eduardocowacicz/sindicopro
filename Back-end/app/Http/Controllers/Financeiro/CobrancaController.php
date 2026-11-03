<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Cobranca;
use App\Services\Financeiro\CobrancaService;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CobrancaController extends Controller
{
    public function __construct(
        private readonly CobrancaService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filtros = [
            'situacao' => $request->string('situacao')->value() ?: null,
            'mes' => $request->integer('mes') ?: null,
            'ano' => $request->integer('ano') ?: null,
            'busca' => $request->string('busca')->value() ?: null,
        ];

        $resultado = $this->service->listar($filtros, (int) $request->integer('por_pagina', 25));

        return response()->json($resultado);
    }

    public function show(Cobranca $cobranca): JsonResponse
    {
        return response()->json([
            'dados' => $this->service->detalhar($cobranca),
        ]);
    }

    public function registrarPagamento(Request $request, Cobranca $cobranca): JsonResponse
    {
        $request->validate([
            'data_pagamento' => ['nullable', 'date'],
            'valor_pago' => ['nullable', 'numeric', 'min:0.01'],
            'conta_financeira_id' => ['nullable'],
            'forma_pagamento' => ['nullable', 'string', 'in:PIX,BOLETO,TRANSFERENCIA,DINHEIRO,OUTRO'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

        $contaId = $request->string('conta_financeira_id')->value();
        $contaIdResolvido = $contaId ? ResolvedorIdPublico::resolver('contas_financeiras', $contaId) : null;

        $dados = [
            'data_pagamento' => $request->string('data_pagamento')->value() ?: null,
            'valor_pago' => $request->input('valor_pago') ? (float) $request->input('valor_pago') : null,
            'conta_financeira_id' => $contaIdResolvido,
            'forma_pagamento' => $request->string('forma_pagamento')->value() ?: 'BOLETO',
            'observacoes' => $request->string('observacoes')->value() ?: null,
        ];

        $cobrancaAtualizada = $this->service->registrarPagamento($cobranca, $dados, $request->user());

        return response()->json([
            'dados' => $this->service->detalhar($cobrancaAtualizada),
            'mensagem' => 'Pagamento registrado com sucesso.',
        ]);
    }

    public function cancelar(Request $request, Cobranca $cobranca): JsonResponse
    {
        $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->service->cancelar($cobranca, $request->string('motivo')->value(), $request->user());

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Cobrança cancelada com sucesso.',
        ]);
    }
}
