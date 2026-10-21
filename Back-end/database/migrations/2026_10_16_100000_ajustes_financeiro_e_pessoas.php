<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Auto-incremento sequencial para categorias_financeiras.codigo
        DB::statement('CREATE SEQUENCE IF NOT EXISTS categorias_financeiras_codigo_seq');
        DB::statement(
            "SELECT setval('categorias_financeiras_codigo_seq', COALESCE((SELECT MAX(codigo::bigint) FROM categorias_financeiras WHERE codigo ~ '^[0-9]{1,18}$'), 0) + 1, false)"
        );
        DB::statement('ALTER SEQUENCE categorias_financeiras_codigo_seq OWNED BY categorias_financeiras.codigo');
        DB::statement("ALTER TABLE categorias_financeiras ALTER COLUMN codigo SET DEFAULT nextval('categorias_financeiras_codigo_seq')::text");

        // 2. Auto-incremento sequencial para tipos_cobranca.codigo
        DB::statement('CREATE SEQUENCE IF NOT EXISTS tipos_cobranca_codigo_seq');
        DB::statement(
            "SELECT setval('tipos_cobranca_codigo_seq', COALESCE((SELECT MAX(codigo::bigint) FROM tipos_cobranca WHERE codigo ~ '^[0-9]{1,18}$'), 0) + 1, false)"
        );
        DB::statement('ALTER SEQUENCE tipos_cobranca_codigo_seq OWNED BY tipos_cobranca.codigo');
        DB::statement("ALTER TABLE tipos_cobranca ALTER COLUMN codigo SET DEFAULT nextval('tipos_cobranca_codigo_seq')::text");

        // 3. Opção de Prestador de serviço e Fornecedor no cadastro de Pessoa
        Schema::table('pessoas', function (Blueprint $table): void {
            $table->string('tipo', 30)->default('MORADOR');
        });
        DB::statement("ALTER TABLE pessoas ADD CONSTRAINT ck_pessoas_tipo CHECK (tipo IN ('MORADOR', 'PRESTADOR_SERVICO', 'FORNECEDOR'))");

        // 4. Associação de Pessoa (Prestador/Fornecedor/Morador) em Lançamentos Financeiros
        Schema::table('movimentacoes_financeiras', function (Blueprint $table): void {
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_financeiras', function (Blueprint $table): void {
            $table->dropForeign(['pessoa_id']);
            $table->dropColumn('pessoa_id');
        });

        DB::statement('ALTER TABLE pessoas DROP CONSTRAINT IF EXISTS ck_pessoas_tipo');
        Schema::table('pessoas', function (Blueprint $table): void {
            $table->dropColumn('tipo');
        });

        DB::statement('ALTER TABLE tipos_cobranca ALTER COLUMN codigo DROP DEFAULT');
        DB::statement('DROP SEQUENCE IF EXISTS tipos_cobranca_codigo_seq');

        DB::statement('ALTER TABLE categorias_financeiras ALTER COLUMN codigo DROP DEFAULT');
        DB::statement('DROP SEQUENCE IF EXISTS categorias_financeiras_codigo_seq');
    }
};
