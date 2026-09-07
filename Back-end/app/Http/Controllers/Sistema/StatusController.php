<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'application' => config('app.name'),
                'status' => 'ok',
            ],
        ]);
    }
}
