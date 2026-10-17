<?php

namespace App\Models;

final class ContaFinanceira extends ModeloBase
{
    protected $table = 'contas_financeiras';

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'saldo_fundo_reserva_inicial' => 'decimal:2',
            'data_saldo_inicial' => 'immutable_date',
            'ativo' => 'boolean',
            'inativado_em' => 'immutable_datetime',
        ];
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoFinanceira::class, 'conta_financeira_id');
    }

    public function calcularSaldos(): array
    {
        $entradas = (float) $this->movimentacoes()
            ->where('situacao', '!=', 'CANCELADO')
            ->where('direcao', 'ENTRADA')
            ->sum('valor');

        $saidas = (float) $this->movimentacoes()
            ->where('situacao', '!=', 'CANCELADO')
            ->where('direcao', 'SAIDA')
            ->sum('valor');

        $saldoTotal = (float) $this->saldo_inicial + $entradas - $saidas;

        // Saídas que utilizaram fundo de reserva
        $saidasFundo = (float) $this->movimentacoes()
            ->where('situacao', '!=', 'CANCELADO')
            ->where('direcao', 'SAIDA')
            ->where(function ($q) {
                $q->where('utiliza_fundo_reserva', true)
                    ->orWhere('origem_recurso', 'FUNDO_RESERVA');
            })
            ->sum('valor');

        // Entradas específicas de fundo de reserva
        $entradasFundo = (float) $this->movimentacoes()
            ->where('situacao', '!=', 'CANCELADO')
            ->where('direcao', 'ENTRADA')
            ->where('origem_recurso', 'FUNDO_RESERVA')
            ->sum('valor');

        // Fundo de reserva acumulado dos fechamentos publicados
        // Se a conta for poupança ou a primeira conta corrente ativa, vincula a provisão dos fechamentos
        $ehContaPrincipal = $this->tipo_conta === 'POUPANCA' ||
            ($this->tipo_conta === 'CONTA_CORRENTE' && ! self::query()->where('tipo_conta', 'POUPANCA')->where('ativo', true)->exists());

        $provisaoFechamentos = $ehContaPrincipal
            ? (float) FechamentoFinanceiro::query()->where('situacao', 'PUBLICADO')->sum('valor_fundo_reserva')
            : 0.0;

        $saldoFundoReserva = max(0, round((float) $this->saldo_fundo_reserva_inicial + $entradasFundo + $provisaoFechamentos - $saidasFundo, 2));
        $saldoCapitalGiro = round($saldoTotal - $saldoFundoReserva, 2);

        return [
            'saldo_total' => $saldoTotal,
            'saldo_fundo_reserva' => $saldoFundoReserva,
            'saldo_capital_giro' => $saldoCapitalGiro,
            'total_entradas' => $entradas,
            'total_saidas' => $saidas,
        ];
    }
}
