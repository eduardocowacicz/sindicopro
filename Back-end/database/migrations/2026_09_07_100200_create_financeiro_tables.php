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
        Schema::create('contas_financeiras', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('nome', 100);
            $table->string('tipo_conta', 20);
            $table->string('codigo_banco', 10)->nullable();
            $table->string('nome_banco', 100)->nullable();
            $table->text('agencia_criptografada')->nullable();
            $table->text('numero_conta_criptografado')->nullable();
            $table->string('ultimos4_conta', 4)->nullable();
            $table->decimal('saldo_inicial', 15, 2);
            $table->date('data_saldo_inicial');
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'contas_financeiras');
        });
        DB::statement("ALTER TABLE contas_financeiras ADD CONSTRAINT ck_contas_financeiras_tipo CHECK (tipo_conta IN ('CONTA_CORRENTE', 'POUPANCA', 'CAIXA'))");

        Schema::create('categorias_financeiras', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 30);
            $table->string('nome', 100);
            $table->string('direcao', 10);
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'categorias_financeiras');
            $table->unique('codigo', 'uq_categorias_financeiras_codigo');
        });
        DB::statement("ALTER TABLE categorias_financeiras ADD CONSTRAINT ck_categorias_financeiras_direcao CHECK (direcao IN ('ENTRADA', 'SAIDA'))");

        Schema::create('tipos_cobranca', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 30);
            $table->string('nome', 100);
            $table->string('papel_pagador', 20);
            $table->smallInteger('dia_vencimento_padrao');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);

            SchemaPadrao::unicoIdPublico($table, 'tipos_cobranca');
            $table->unique('codigo', 'uq_tipos_cobranca_codigo');
        });
        DB::statement("ALTER TABLE tipos_cobranca ADD CONSTRAINT ck_tipos_cobranca_papel CHECK (papel_pagador IN ('MORADOR', 'PROPRIETARIO'))");
        DB::statement('ALTER TABLE tipos_cobranca ADD CONSTRAINT ck_tipos_cobranca_vencimento CHECK (dia_vencimento_padrao BETWEEN 1 AND 28)');

        Schema::create('isencoes_cobranca_unidade', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('unidade_id');
            $table->foreignId('tipo_cobranca_id');
            $table->date('inicio_vigencia');
            $table->date('fim_vigencia')->nullable();
            $table->text('motivo');
            $table->foreignId('aprovado_por');
            $table->timestampTz('revogado_em')->nullable();
            $table->foreignId('revogado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'isencoes_cobranca_unidade');
            $table->index(['unidade_id', 'tipo_cobranca_id', 'inicio_vigencia', 'fim_vigencia'], 'idx_isencoes_unidade_tipo_vigencia');
            $table->foreign('unidade_id', 'fk_isencoes_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('tipo_cobranca_id', 'fk_isencoes_tipo_cobranca')->references('id')->on('tipos_cobranca')->restrictOnDelete();
            $table->foreign('aprovado_por', 'fk_isencoes_aprovado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('revogado_por', 'fk_isencoes_revogado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE isencoes_cobranca_unidade ADD CONSTRAINT ck_isencoes_vigencia CHECK (fim_vigencia IS NULL OR fim_vigencia >= inicio_vigencia)');
        DB::statement('CREATE UNIQUE INDEX uq_isencoes_vigente ON isencoes_cobranca_unidade (unidade_id, tipo_cobranca_id) WHERE fim_vigencia IS NULL AND revogado_em IS NULL');

        Schema::create('modelos_movimentacao_recorrente', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('conta_financeira_id');
            $table->foreignId('categoria_financeira_id');
            $table->foreignId('tipo_cobranca_id')->nullable();
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->smallInteger('dia_mes');
            $table->date('inicio_vigencia');
            $table->date('fim_vigencia')->nullable();
            $table->boolean('ativo')->default(true);

            SchemaPadrao::unicoIdPublico($table, 'modelos_movimentacao_recorrente');
            $table->foreign('conta_financeira_id', 'fk_modelos_recorrentes_conta')->references('id')->on('contas_financeiras')->restrictOnDelete();
            $table->foreign('categoria_financeira_id', 'fk_modelos_recorrentes_categoria')->references('id')->on('categorias_financeiras')->restrictOnDelete();
            $table->foreign('tipo_cobranca_id', 'fk_modelos_recorrentes_tipo_cobranca')->references('id')->on('tipos_cobranca')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE modelos_movimentacao_recorrente ADD CONSTRAINT ck_modelos_recorrentes_valor CHECK (valor > 0)');
        DB::statement('ALTER TABLE modelos_movimentacao_recorrente ADD CONSTRAINT ck_modelos_recorrentes_dia CHECK (dia_mes BETWEEN 1 AND 28)');
        DB::statement('ALTER TABLE modelos_movimentacao_recorrente ADD CONSTRAINT ck_modelos_recorrentes_vigencia CHECK (fim_vigencia IS NULL OR fim_vigencia >= inicio_vigencia)');

        Schema::create('periodos_financeiros', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->date('periodo');
            $table->string('situacao', 15)->default('ABERTO');
            $table->timestampTz('fechado_em')->nullable();
            $table->foreignId('fechado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'periodos_financeiros');
            $table->unique('periodo', 'uq_periodos_financeiros_periodo');
            $table->foreign('fechado_por', 'fk_periodos_financeiros_fechado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE periodos_financeiros ADD CONSTRAINT ck_periodos_financeiros_situacao CHECK (situacao IN ('ABERTO', 'EM_FECHAMENTO', 'FECHADO'))");
        DB::statement('ALTER TABLE periodos_financeiros ADD CONSTRAINT ck_periodos_financeiros_mes CHECK (EXTRACT(DAY FROM periodo) = 1)');

        Schema::create('movimentacoes_financeiras', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('periodo_financeiro_id');
            $table->foreignId('conta_financeira_id');
            $table->foreignId('categoria_financeira_id');
            $table->foreignId('tipo_cobranca_id')->nullable();
            $table->foreignId('modelo_recorrente_id')->nullable();
            $table->string('direcao', 10);
            $table->string('natureza', 25)->default('NORMAL');
            $table->string('situacao', 15)->default('RASCUNHO');
            $table->string('descricao', 255);
            $table->string('numero_documento', 80)->nullable();
            $table->string('nome_contraparte', 200)->nullable();
            $table->date('data_competencia');
            $table->date('data_efetiva');
            $table->date('data_vencimento')->nullable();
            $table->decimal('valor', 15, 2);
            $table->foreignId('estorno_de_id')->nullable();
            $table->timestampTz('contabilizado_em')->nullable();
            $table->foreignId('contabilizado_por')->nullable();
            $table->text('observacoes')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'movimentacoes_financeiras');
            $table->index(['periodo_financeiro_id', 'situacao'], 'idx_movimentacoes_periodo_situacao');
            $table->index(['conta_financeira_id', 'data_efetiva', 'situacao'], 'idx_movimentacoes_conta_data_situacao');
            $table->index(['categoria_financeira_id', 'data_competencia'], 'idx_movimentacoes_categoria_competencia');
            $table->foreign('periodo_financeiro_id', 'fk_movimentacoes_periodo')->references('id')->on('periodos_financeiros')->restrictOnDelete();
            $table->foreign('conta_financeira_id', 'fk_movimentacoes_conta')->references('id')->on('contas_financeiras')->restrictOnDelete();
            $table->foreign('categoria_financeira_id', 'fk_movimentacoes_categoria')->references('id')->on('categorias_financeiras')->restrictOnDelete();
            $table->foreign('tipo_cobranca_id', 'fk_movimentacoes_tipo_cobranca')->references('id')->on('tipos_cobranca')->restrictOnDelete();
            $table->foreign('modelo_recorrente_id', 'fk_movimentacoes_modelo_recorrente')->references('id')->on('modelos_movimentacao_recorrente')->restrictOnDelete();
            $table->foreign('estorno_de_id', 'fk_movimentacoes_estorno_de')->references('id')->on('movimentacoes_financeiras')->restrictOnDelete();
            $table->foreign('contabilizado_por', 'fk_movimentacoes_contabilizado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE movimentacoes_financeiras ADD CONSTRAINT ck_movimentacoes_direcao CHECK (direcao IN ('ENTRADA', 'SAIDA'))");
        DB::statement("ALTER TABLE movimentacoes_financeiras ADD CONSTRAINT ck_movimentacoes_natureza CHECK (natureza IN ('NORMAL', 'RECEBIMENTO', 'DEVOLUCAO', 'TAXA_RESERVA', 'ESTORNO'))");
        DB::statement("ALTER TABLE movimentacoes_financeiras ADD CONSTRAINT ck_movimentacoes_situacao CHECK (situacao IN ('RASCUNHO', 'CONTABILIZADO', 'ESTORNADO', 'CANCELADO'))");
        DB::statement('ALTER TABLE movimentacoes_financeiras ADD CONSTRAINT ck_movimentacoes_valor CHECK (valor > 0)');
        DB::statement('CREATE UNIQUE INDEX uq_movimentacoes_estorno_de ON movimentacoes_financeiras (estorno_de_id) WHERE estorno_de_id IS NOT NULL');

        $this->adicionarAuditoria(['contas_financeiras', 'categorias_financeiras', 'tipos_cobranca', 'isencoes_cobranca_unidade', 'modelos_movimentacao_recorrente', 'periodos_financeiros', 'movimentacoes_financeiras']);
    }

    private function adicionarAuditoria(array $tabelas): void
    {
        foreach ($tabelas as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['movimentacoes_financeiras', 'periodos_financeiros', 'modelos_movimentacao_recorrente', 'isencoes_cobranca_unidade', 'tipos_cobranca', 'categorias_financeiras', 'contas_financeiras'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
