<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocos', function (Blueprint $table): void {
            if (! Schema::hasColumn('blocos', 'apelido')) {
                $table->string('apelido', 100)->nullable();
            }
            if (! Schema::hasColumn('blocos', 'andares')) {
                $table->unsignedInteger('andares')->nullable();
            }
            if (! Schema::hasColumn('blocos', 'apartamentos_por_andar')) {
                $table->unsignedInteger('apartamentos_por_andar')->default(1);
            }
        });

        // Sincroniza quantidade_andares para andares
        DB::statement('UPDATE blocos SET andares = quantidade_andares WHERE andares IS NULL AND quantidade_andares IS NOT NULL');

        Schema::table('unidades', function (Blueprint $table): void {
            if (! Schema::hasColumn('unidades', 'andar')) {
                $table->integer('andar')->nullable();
            }
            if (! Schema::hasColumn('unidades', 'observacoes')) {
                $table->text('observacoes')->nullable();
            }
        });

        // Sincroniza numero_andar para andar
        DB::statement('UPDATE unidades SET andar = numero_andar WHERE andar IS NULL AND numero_andar IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table): void {
            if (Schema::hasColumn('unidades', 'andar')) {
                $table->dropColumn('andar');
            }
        });

        Schema::table('blocos', function (Blueprint $table): void {
            $colunas = [];
            if (Schema::hasColumn('blocos', 'apelido')) {
                $colunas[] = 'apelido';
            }
            if (Schema::hasColumn('blocos', 'andares')) {
                $colunas[] = 'andares';
            }
            if (Schema::hasColumn('blocos', 'apartamentos_por_andar')) {
                $colunas[] = 'apartamentos_por_andar';
            }
            if (! empty($colunas)) {
                $table->dropColumn($colunas);
            }
        });
    }
};
