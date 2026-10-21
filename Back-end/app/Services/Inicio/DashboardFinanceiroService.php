<?php

namespace App\Services\Inicio;

use App\Models\MovimentacaoFinanceira;
use Illuminate\Support\Carbon;

final class DashboardFinanceiroService
{
    public function evolucaoFinanceira(): array
    {
        $inicio = now()->startOfMonth()->subMonths(11);
        $fim = now()->endOfMonth();
        $meses = [];

        for ($mes = $inicio->copy(); $mes->lte($fim); $mes->addMonth()) {
            $competencia = $mes->format('Y-m');
            $meses[$competencia] = [
                'competencia' => $competencia,
                'entradas_centavos' => 0,
                'saidas_centavos' => 0,
            ];
        }

        MovimentacaoFinanceira::query()
            ->where('situacao', 'CONTABILIZADO')
            ->whereBetween('data_efetiva', [$inicio->toDateString(), $fim->toDateString()])
            ->get(['data_efetiva', 'direcao', 'valor'])
            ->each(function (MovimentacaoFinanceira $movimentacao) use (&$meses): void {
                $competencia = Carbon::parse($movimentacao->data_efetiva)->format('Y-m');
                $campo = $movimentacao->direcao === 'ENTRADA' ? 'entradas_centavos' : 'saidas_centavos';
                $meses[$competencia][$campo] += (int) round((float) $movimentacao->valor * 100);
            });

        return [
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fim' => $fim->toDateString(),
            'meses' => array_values(array_map(
                static fn (array $mes): array => [
                    'competencia' => $mes['competencia'],
                    'entradas' => $mes['entradas_centavos'] / 100,
                    'saidas' => $mes['saidas_centavos'] / 100,
                ],
                $meses,
            )),
        ];
    }
}
