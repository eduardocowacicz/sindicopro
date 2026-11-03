<?php

namespace App\Http\Controllers\Inicio;

use App\Http\Controllers\Controller;
use App\Services\Inicio\DashboardFinanceiroService;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardFinanceiroService $service,
    ) {}

    public function financeiro(): JsonResponse
    {
        return response()->json(['dados' => $this->service->evolucaoFinanceira()]);
    }
}
