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
        Schema::create('tipos_ocorrencia', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 30);
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'tipos_ocorrencia');
            $table->unique('codigo', 'uq_tipos_ocorrencia_codigo');
        });

        Schema::create('ocorrencias', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->unsignedBigInteger('numero_protocolo');
            $table->foreignId('tipo_ocorrencia_id');
            $table->foreignId('registrado_por');
            $table->string('motivo', 180);
            $table->text('descricao');
            $table->boolean('interna')->default(false);
            $table->string('situacao', 20)->default('ABERTO');
            $table->timestampTz('aberto_em')->useCurrent();
            $table->timestampTz('resolvido_em')->nullable();
            $table->timestampTz('fechado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'ocorrencias');
            $table->unique('numero_protocolo', 'uq_ocorrencias_numero_protocolo');
            $table->index(['situacao', 'aberto_em'], 'idx_ocorrencias_situacao_abertura');
            $table->index(['registrado_por', 'aberto_em'], 'idx_ocorrencias_usuario_abertura');
            $table->foreign('tipo_ocorrencia_id', 'fk_ocorrencias_tipo')->references('id')->on('tipos_ocorrencia')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_ocorrencias_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE ocorrencias ADD CONSTRAINT ck_ocorrencias_situacao CHECK (situacao IN ('ABERTO', 'EM_ANDAMENTO', 'RESOLVIDO', 'FECHADO', 'CANCELADO'))");

        Schema::create('ocorrencia_unidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ocorrencia_id');
            $table->foreignId('unidade_id');
            $table->string('tipo_relacao', 20);
            $table->boolean('visivel_para_unidade')->default(true);
            $table->foreignId('vinculado_por');
            $table->timestampTz('vinculado_em')->useCurrent();

            $table->unique(['ocorrencia_id', 'unidade_id', 'tipo_relacao'], 'uq_ocorrencia_unidades');
            $table->foreign('ocorrencia_id', 'fk_ocorrencia_unidades_ocorrencia')->references('id')->on('ocorrencias')->restrictOnDelete();
            $table->foreign('unidade_id', 'fk_ocorrencia_unidades_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('vinculado_por', 'fk_ocorrencia_unidades_vinculado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE ocorrencia_unidades ADD CONSTRAINT ck_ocorrencia_unidades_tipo CHECK (tipo_relacao IN ('SOLICITANTE', 'RELACIONADA', 'AFETADA'))");

        Schema::create('comentarios_ocorrencia', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('ocorrencia_id');
            $table->foreignId('autor_id');
            $table->text('conteudo');
            $table->string('visibilidade', 30);
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'comentarios_ocorrencia');
            $table->foreign('ocorrencia_id', 'fk_comentarios_ocorrencia')->references('id')->on('ocorrencias')->restrictOnDelete();
            $table->foreign('autor_id', 'fk_comentarios_ocorrencia_autor')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE comentarios_ocorrencia ADD CONSTRAINT ck_comentarios_ocorrencia_visibilidade CHECK (visibilidade IN ('SOMENTE_ADMINISTRACAO', 'UNIDADE_SOLICITANTE', 'UNIDADES_RELACIONADAS', 'TODOS_PARTICIPANTES'))");

        Schema::create('historico_situacao_ocorrencia', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ocorrencia_id');
            $table->string('situacao_anterior', 20)->nullable();
            $table->string('nova_situacao', 20);
            $table->foreignId('comentario_ocorrencia_id')->nullable();
            $table->foreignId('alterado_por');
            $table->timestampTz('alterado_em')->useCurrent();

            $table->foreign('ocorrencia_id', 'fk_historico_ocorrencia')->references('id')->on('ocorrencias')->restrictOnDelete();
            $table->foreign('comentario_ocorrencia_id', 'fk_historico_ocorrencia_comentario')->references('id')->on('comentarios_ocorrencia')->restrictOnDelete();
            $table->foreign('alterado_por', 'fk_historico_ocorrencia_alterado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        foreach (['tipos_ocorrencia', 'ocorrencias'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['historico_situacao_ocorrencia', 'comentarios_ocorrencia', 'ocorrencia_unidades', 'ocorrencias', 'tipos_ocorrencia'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
