<?php

use App\Support\Database\SchemaPadrao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arquivos', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('enviado_por');
            $table->string('disco', 50);
            $table->string('caminho', 500);
            $table->string('nome_original', 255);
            $table->string('extensao', 10);
            $table->string('tipo_mime', 100);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->char('sha256', 64);
            $table->string('visibilidade', 20)->default('PRIVADO');
            $table->string('situacao_validacao', 20)->default('PENDENTE');
            $table->timestampTz('validado_em')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'arquivos');
            $table->unique(['disco', 'caminho'], 'uq_arquivos_disco_caminho');
            $table->index('sha256', 'idx_arquivos_sha256');
            $table->index(['situacao_validacao', 'criado_em'], 'idx_arquivos_validacao_data');
            $table->foreign('enviado_por', 'fk_arquivos_enviado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE arquivos ADD CONSTRAINT ck_arquivos_tamanho CHECK (tamanho_bytes BETWEEN 1 AND 5242880)');
        DB::statement("ALTER TABLE arquivos ADD CONSTRAINT ck_arquivos_visibilidade CHECK (visibilidade IN ('PRIVADO', 'UNIDADE', 'PUBLICADO'))");
        DB::statement("ALTER TABLE arquivos ADD CONSTRAINT ck_arquivos_validacao CHECK (situacao_validacao IN ('PENDENTE', 'VALIDO', 'REJEITADO', 'FALHOU'))");

        Schema::table('pessoas', function (Blueprint $table): void {
            $table->foreign('foto_arquivo_id', 'fk_pessoas_foto_arquivo')->references('id')->on('arquivos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pessoas', function (Blueprint $table): void {
            $table->dropForeign('fk_pessoas_foto_arquivo');
        });
        Schema::dropIfExists('arquivos');
    }
};
