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
        Schema::create('ambientes', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 30);
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->smallInteger('horas_antecedencia_minima_morador')->default(48);
            $table->smallInteger('horas_limite_cancelamento_morador')->default(24);
            $table->boolean('bloquear_unidades_inadimplentes')->default(true);
            $table->boolean('permitir_proprietario_nao_residente')->default(false);
            $table->boolean('aprovacao_automatica')->default(true);
            $table->boolean('ativo')->default(true);

            SchemaPadrao::unicoIdPublico($table, 'ambientes');
            $table->unique('codigo', 'uq_ambientes_codigo');
        });

        Schema::create('faixas_horario_ambiente', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('ambiente_id');
            $table->string('nome', 100);
            $table->time('inicia_em');
            $table->time('termina_em');
            $table->date('inicio_validade');
            $table->date('fim_validade')->nullable();
            $table->boolean('ativo')->default(true);

            SchemaPadrao::unicoIdPublico($table, 'faixas_horario_ambiente');
            $table->foreign('ambiente_id', 'fk_faixas_horario_ambiente')->references('id')->on('ambientes')->restrictOnDelete();
            $table->index(['ambiente_id', 'inicio_validade', 'fim_validade'], 'idx_faixas_ambiente_vigencia');
        });
        DB::statement('ALTER TABLE faixas_horario_ambiente ADD CONSTRAINT ck_faixas_horario CHECK (termina_em > inicia_em)');
        DB::statement('ALTER TABLE faixas_horario_ambiente ADD CONSTRAINT ck_faixas_vigencia CHECK (fim_validade IS NULL OR fim_validade >= inicio_validade)');

        Schema::create('taxas_ambiente', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('ambiente_id');
            $table->foreignId('faixa_horario_id');
            $table->decimal('valor', 15, 2);
            $table->date('inicio_validade');
            $table->date('fim_validade')->nullable();
            $table->foreignId('definido_por');

            SchemaPadrao::unicoIdPublico($table, 'taxas_ambiente');
            $table->foreign('ambiente_id', 'fk_taxas_ambiente')->references('id')->on('ambientes')->restrictOnDelete();
            $table->foreign('faixa_horario_id', 'fk_taxas_faixa')->references('id')->on('faixas_horario_ambiente')->restrictOnDelete();
            $table->foreign('definido_por', 'fk_taxas_definido_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['faixa_horario_id', 'inicio_validade', 'fim_validade'], 'idx_taxas_faixa_vigencia');
        });
        DB::statement('ALTER TABLE taxas_ambiente ADD CONSTRAINT ck_taxas_valor CHECK (valor >= 0)');
        DB::statement('ALTER TABLE taxas_ambiente ADD CONSTRAINT ck_taxas_vigencia CHECK (fim_validade IS NULL OR fim_validade >= inicio_validade)');

        Schema::create('bloqueios_ambiente', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('ambiente_id');
            $table->foreignId('faixa_horario_id')->nullable();
            $table->timestampTz('inicio_vigencia');
            $table->timestampTz('fim_vigencia');
            $table->text('motivo');
            $table->foreignId('bloqueado_por');
            $table->timestampTz('cancelado_em')->nullable();
            $table->foreignId('cancelado_por')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'bloqueios_ambiente');
            $table->foreign('ambiente_id', 'fk_bloqueios_ambiente')->references('id')->on('ambientes')->restrictOnDelete();
            $table->foreign('faixa_horario_id', 'fk_bloqueios_faixa')->references('id')->on('faixas_horario_ambiente')->restrictOnDelete();
            $table->foreign('bloqueado_por', 'fk_bloqueios_bloqueado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('cancelado_por', 'fk_bloqueios_cancelado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE bloqueios_ambiente ADD CONSTRAINT ck_bloqueios_vigencia CHECK (fim_vigencia > inicio_vigencia)');

        Schema::create('politicas_reserva_usuario', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('usuario_id');
            $table->boolean('pode_reservar');
            $table->boolean('ignorar_bloqueio_inadimplencia')->default(false);
            $table->smallInteger('horas_antecedencia_minima')->nullable();
            $table->boolean('pode_cancelar_apos_prazo')->default(false);
            $table->timestampTz('inicio_validade');
            $table->timestampTz('fim_validade')->nullable();
            $table->string('motivo', 255);
            $table->foreignId('definido_por');
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'politicas_reserva_usuario');
            $table->foreign('usuario_id', 'fk_politicas_reserva_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('definido_por', 'fk_politicas_reserva_definido_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE politicas_reserva_usuario ADD CONSTRAINT ck_politicas_reserva_vigencia CHECK (fim_validade IS NULL OR fim_validade >= inicio_validade)');
        DB::statement('CREATE UNIQUE INDEX uq_politicas_reserva_vigente ON politicas_reserva_usuario (usuario_id) WHERE fim_validade IS NULL');

        Schema::create('reservas', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('ambiente_id');
            $table->foreignId('faixa_horario_id');
            $table->foreignId('unidade_id');
            $table->foreignId('solicitado_por');
            $table->date('data_reserva');
            $table->time('inicia_em');
            $table->time('termina_em');
            $table->decimal('valor_taxa', 15, 2)->default(0);
            $table->string('situacao', 20)->default('CONFIRMADO');
            $table->boolean('criado_pela_administracao')->default(false);
            $table->timestampTz('validado_em')->nullable();
            $table->foreignId('validado_por')->nullable();
            $table->timestampTz('cancelado_em')->nullable();
            $table->foreignId('cancelado_por')->nullable();
            $table->text('observacoes')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'reservas');
            $table->index(['unidade_id', 'data_reserva'], 'idx_reservas_unidade_data');
            $table->foreign('ambiente_id', 'fk_reservas_ambiente')->references('id')->on('ambientes')->restrictOnDelete();
            $table->foreign('faixa_horario_id', 'fk_reservas_faixa')->references('id')->on('faixas_horario_ambiente')->restrictOnDelete();
            $table->foreign('unidade_id', 'fk_reservas_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('solicitado_por', 'fk_reservas_solicitado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('validado_por', 'fk_reservas_validado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('cancelado_por', 'fk_reservas_cancelado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE reservas ADD CONSTRAINT ck_reservas_situacao CHECK (situacao IN ('CONFIRMADO', 'CANCELADO', 'CONCLUIDO'))");
        DB::statement('ALTER TABLE reservas ADD CONSTRAINT ck_reservas_horario CHECK (termina_em > inicia_em)');
        DB::statement('ALTER TABLE reservas ADD CONSTRAINT ck_reservas_valor_taxa CHECK (valor_taxa >= 0)');
        DB::statement("CREATE UNIQUE INDEX uq_reservas_faixa_ativa ON reservas (ambiente_id, data_reserva, faixa_horario_id) WHERE situacao <> 'CANCELADO'");

        Schema::create('historico_situacao_reserva', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id');
            $table->string('situacao_anterior', 20)->nullable();
            $table->string('nova_situacao', 20);
            $table->string('tipo_evento', 40);
            $table->foreignId('alterado_por');
            $table->timestampTz('alterado_em')->useCurrent();

            $table->foreign('reserva_id', 'fk_historico_reserva')->references('id')->on('reservas')->restrictOnDelete();
            $table->foreign('alterado_por', 'fk_historico_reserva_alterado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE historico_situacao_reserva ADD CONSTRAINT ck_historico_reserva_evento CHECK (tipo_evento IN ('CONFIRMADA_AUTOMATICAMENTE', 'VALIDADO', 'CANCELADO', 'CONCLUIDO'))");

        foreach (['ambientes', 'faixas_horario_ambiente', 'taxas_ambiente', 'reservas'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['historico_situacao_reserva', 'reservas', 'politicas_reserva_usuario', 'bloqueios_ambiente', 'taxas_ambiente', 'faixas_horario_ambiente', 'ambientes'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
