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
        Schema::create('comunicados', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('titulo', 180);
            $table->text('conteudo');
            $table->string('tipo_publico', 20);
            $table->string('situacao', 15)->default('RASCUNHO');
            $table->timestampTz('publicado_em')->nullable();
            $table->foreignId('publicado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'comunicados');
            $table->index(['situacao', 'publicado_em'], 'idx_comunicados_situacao_publicacao');
            $table->foreign('publicado_por', 'fk_comunicados_publicado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE comunicados ADD CONSTRAINT ck_comunicados_publico CHECK (tipo_publico IN ('TODOS', 'BLOCOS'))");
        DB::statement("ALTER TABLE comunicados ADD CONSTRAINT ck_comunicados_situacao CHECK (situacao IN ('RASCUNHO', 'PUBLICADO', 'CANCELADO'))");

        Schema::create('comunicado_blocos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comunicado_id');
            $table->foreignId('bloco_id');
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['comunicado_id', 'bloco_id'], 'uq_comunicado_blocos');
            $table->foreign('comunicado_id', 'fk_comunicado_blocos_comunicado')->references('id')->on('comunicados')->restrictOnDelete();
            $table->foreign('bloco_id', 'fk_comunicado_blocos_bloco')->references('id')->on('blocos')->restrictOnDelete();
        });

        Schema::create('comunicado_destinatarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comunicado_id');
            $table->foreignId('pessoa_id');
            $table->foreignId('usuario_id')->nullable();
            $table->foreignId('unidade_id');
            $table->string('email_registrado', 254);
            $table->string('situacao_entrega', 15)->default('ENFILEIRADO');
            $table->string('id_mensagem_provedor', 150)->nullable();
            $table->timestampTz('enviado_em')->nullable();
            $table->timestampTz('entregue_em')->nullable();
            $table->timestampTz('lido_em')->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['comunicado_id', 'pessoa_id', 'unidade_id'], 'uq_comunicado_destinatarios');
            $table->index(['usuario_id', 'lido_em', 'comunicado_id'], 'idx_comunicado_destinatarios_leitura');
            $table->foreign('comunicado_id', 'fk_comunicado_destinatarios_comunicado')->references('id')->on('comunicados')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_comunicado_destinatarios_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
            $table->foreign('usuario_id', 'fk_comunicado_destinatarios_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('unidade_id', 'fk_comunicado_destinatarios_unidade')->references('id')->on('unidades')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE comunicado_destinatarios ADD CONSTRAINT ck_comunicado_destinatarios_situacao CHECK (situacao_entrega IN ('ENFILEIRADO', 'ENVIADO', 'ENTREGUE', 'DEVOLVIDO', 'FALHOU'))");

        Schema::table('comunicados', function (Blueprint $table): void {
            $table->foreign('criado_por', 'fk_comunicados_criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('atualizado_por', 'fk_comunicados_atualizado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicado_destinatarios');
        Schema::dropIfExists('comunicado_blocos');
        Schema::dropIfExists('comunicados');
    }
};
