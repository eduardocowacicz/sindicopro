<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable();
            $table->string('acao', 50);
            $table->string('tipo_entidade', 100);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->uuid('requisicao_id')->nullable();
            $table->ipAddress('endereco_ip')->nullable();
            $table->text('agente_usuario')->nullable();
            $table->text('motivo')->nullable();
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_posteriores')->nullable();
            $table->timestampTz('ocorrido_em')->useCurrent();

            $table->index(['tipo_entidade', 'entidade_id', 'ocorrido_em'], 'idx_logs_auditoria_entidade_data');
            $table->index(['usuario_id', 'ocorrido_em'], 'idx_logs_auditoria_usuario_data');
            $table->foreign('usuario_id', 'fk_logs_auditoria_usuario')->references('id')->on('usuarios')->restrictOnDelete();
        });

        Schema::create('tarefas_fila', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('fila')->index('idx_tarefas_fila_fila');
            $table->longText('conteudo');
            $table->unsignedTinyInteger('tentativas');
            $table->unsignedInteger('reservado_em')->nullable();
            $table->unsignedInteger('disponivel_em');
            $table->unsignedInteger('criado_em');
        });

        Schema::create('lotes_tarefas', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->integer('total_tarefas');
            $table->integer('tarefas_pendentes');
            $table->integer('quantidade_tarefas_com_falha');
            $table->longText('ids_tarefas_com_falha');
            $table->mediumText('configuracoes')->nullable();
            $table->integer('cancelado_em')->nullable();
            $table->integer('criado_em');
            $table->integer('finalizado_em')->nullable();
        });

        Schema::create('tarefas_com_falha', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique('uq_tarefas_com_falha_uuid');
            $table->text('conexao');
            $table->text('fila');
            $table->longText('conteudo');
            $table->longText('excecao');
            $table->timestampTz('falhou_em')->useCurrent();
        });

        DB::statement('CREATE VIEW jobs AS SELECT id, fila AS queue, conteudo AS payload, tentativas AS attempts, reservado_em AS reserved_at, disponivel_em AS available_at, criado_em AS created_at FROM tarefas_fila');
        DB::statement('CREATE VIEW job_batches AS SELECT id, nome AS name, total_tarefas AS total_jobs, tarefas_pendentes AS pending_jobs, quantidade_tarefas_com_falha AS failed_jobs, ids_tarefas_com_falha AS failed_job_ids, configuracoes AS options, cancelado_em AS cancelled_at, criado_em AS created_at, finalizado_em AS finished_at FROM lotes_tarefas');
        DB::statement('CREATE VIEW failed_jobs AS SELECT id, uuid, conexao AS connection, fila AS queue, conteudo AS payload, excecao AS exception, falhou_em AS failed_at FROM tarefas_com_falha');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS failed_jobs');
        DB::statement('DROP VIEW IF EXISTS job_batches');
        DB::statement('DROP VIEW IF EXISTS jobs');
        Schema::dropIfExists('tarefas_com_falha');
        Schema::dropIfExists('lotes_tarefas');
        Schema::dropIfExists('tarefas_fila');
        Schema::dropIfExists('logs_auditoria');
    }
};
