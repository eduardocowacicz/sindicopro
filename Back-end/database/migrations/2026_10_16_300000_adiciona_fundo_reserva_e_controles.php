<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Saldo de fundo de reserva inicial nas contas bancárias
        Schema::table('contas_financeiras', function (Blueprint $table): void {
            $table->decimal('saldo_fundo_reserva_inicial', 15, 2)->default(0)->after('saldo_inicial');
        });

        // 2. Controle de origem de recurso nas movimentações financeiras
        Schema::table('movimentacoes_financeiras', function (Blueprint $table): void {
            $table->boolean('utiliza_fundo_reserva')->default(false)->after('natureza');
            $table->string('origem_recurso', 25)->default('CAPITAL_GIRO')->after('utiliza_fundo_reserva');
        });
        DB::statement("ALTER TABLE movimentacoes_financeiras ADD CONSTRAINT ck_movimentacoes_origem_recurso CHECK (origem_recurso IN ('CAPITAL_GIRO', 'FUNDO_RESERVA'))");

        // 3. Fundo de reserva configurável no fechamento financeiro
        Schema::table('fechamentos_financeiros', function (Blueprint $table): void {
            $table->decimal('percentual_fundo_reserva', 5, 2)->default(10.00)->after('total_rateado');
            $table->decimal('valor_fundo_reserva', 15, 2)->default(0)->after('percentual_fundo_reserva');
            $table->decimal('valor_despesas_ordinarias', 15, 2)->default(0)->after('valor_fundo_reserva');
        });
    }

    public function down(): void
    {
        Schema::table('fechamentos_financeiros', function (Blueprint $table): void {
            $table->dropColumn(['percentual_fundo_reserva', 'valor_fundo_reserva', 'valor_despesas_ordinarias']);
        });

        DB::statement('ALTER TABLE movimentacoes_financeiras DROP CONSTRAINT IF EXISTS ck_movimentacoes_origem_recurso');
        Schema::table('movimentacoes_financeiras', function (Blueprint $table): void {
            $table->dropColumn(['utiliza_fundo_reserva', 'origem_recurso']);
        });

        Schema::table('contas_financeiras', function (Blueprint $table): void {
            $table->dropColumn('saldo_fundo_reserva_inicial');
        });
    }
};
