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
        Schema::create('fechamentos_financeiros', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('periodo_financeiro_id');
            $table->smallInteger('versao');
            $table->string('situacao', 15)->default('PROCESSANDO');
            $table->decimal('total_entradas', 15, 2)->default(0);
            $table->decimal('total_saidas', 15, 2)->default(0);
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_final', 15, 2)->default(0);
            $table->decimal('total_rateado', 15, 2)->default(0);
            $table->json('dados_calculo');
            $table->timestampTz('publicado_em')->nullable();
            $table->foreignId('publicado_por')->nullable();
            $table->timestampTz('arquivado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'fechamentos_financeiros');
            $table->unique(['periodo_financeiro_id', 'versao'], 'uq_fechamentos_periodo_versao');
            $table->index(['periodo_financeiro_id', 'situacao', 'versao'], 'idx_fechamentos_periodo_situacao_versao');
            $table->foreign('periodo_financeiro_id', 'fk_fechamentos_periodo')->references('id')->on('periodos_financeiros')->restrictOnDelete();
            $table->foreign('publicado_por', 'fk_fechamentos_publicado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fechamentos_financeiros ADD CONSTRAINT ck_fechamentos_situacao CHECK (situacao IN ('PROCESSANDO', 'PUBLICADO', 'ARQUIVADO', 'FALHOU'))");
        DB::statement('ALTER TABLE fechamentos_financeiros ADD CONSTRAINT ck_fechamentos_versao CHECK (versao > 0)');
        DB::statement("CREATE UNIQUE INDEX uq_fechamentos_publicado ON fechamentos_financeiros (periodo_financeiro_id) WHERE situacao = 'PUBLICADO'");

        Schema::create('movimentacoes_fechamento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fechamento_financeiro_id');
            $table->foreignId('movimentacao_financeira_id');
            $table->string('direcao', 10);
            $table->string('nome_categoria', 100);
            $table->string('nome_tipo_cobranca', 100)->nullable();
            $table->string('descricao', 255);
            $table->date('data_efetiva');
            $table->decimal('valor', 15, 2);
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['fechamento_financeiro_id', 'movimentacao_financeira_id'], 'uq_movimentacoes_fechamento');
            $table->foreign('fechamento_financeiro_id', 'fk_movimentacoes_fechamento_fechamento')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('movimentacao_financeira_id', 'fk_movimentacoes_fechamento_movimentacao')->references('id')->on('movimentacoes_financeiras')->restrictOnDelete();
        });

        Schema::create('rateios_fechamento', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('fechamento_financeiro_id');
            $table->foreignId('tipo_cobranca_id');
            $table->string('nome_tipo_cobranca', 100);
            $table->string('papel_pagador', 20);
            $table->decimal('valor_total', 15, 2);
            $table->integer('quantidade_unidades_participantes');
            $table->integer('quantidade_unidades_isentas');
            $table->decimal('diferenca_arredondamento', 15, 2)->default(0);
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'rateios_fechamento');
            $table->unique(['fechamento_financeiro_id', 'tipo_cobranca_id'], 'uq_rateios_fechamento_tipo');
            $table->foreign('fechamento_financeiro_id', 'fk_rateios_fechamento_fechamento')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('tipo_cobranca_id', 'fk_rateios_fechamento_tipo')->references('id')->on('tipos_cobranca')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE rateios_fechamento ADD CONSTRAINT ck_rateios_fechamento_papel CHECK (papel_pagador IN ('MORADOR', 'PROPRIETARIO'))");
        DB::statement('ALTER TABLE rateios_fechamento ADD CONSTRAINT ck_rateios_fechamento_quantidades CHECK (quantidade_unidades_participantes > 0 AND quantidade_unidades_isentas >= 0)');

        Schema::create('rateios_unidades_fechamento', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('rateio_fechamento_id');
            $table->foreignId('unidade_id');
            $table->string('codigo_unidade', 30);
            $table->string('nome_bloco', 100);
            $table->foreignId('vinculo_unidade_pessoa_id')->nullable();
            $table->foreignId('pessoa_id')->nullable();
            $table->string('nome_pessoa', 200)->nullable();
            $table->boolean('participante');
            $table->boolean('isento');
            $table->text('motivo_isencao')->nullable();
            $table->decimal('valor_rateado', 15, 2)->default(0);
            $table->decimal('ajuste_arredondamento', 15, 2)->default(0);
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'rateios_unidades_fechamento');
            $table->unique(['rateio_fechamento_id', 'unidade_id'], 'uq_rateios_unidades_fechamento');
            $table->foreign('rateio_fechamento_id', 'fk_rateios_unidades_rateio')->references('id')->on('rateios_fechamento')->restrictOnDelete();
            $table->foreign('unidade_id', 'fk_rateios_unidades_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('vinculo_unidade_pessoa_id', 'fk_rateios_unidades_vinculo')->references('id')->on('vinculos_unidade_pessoa')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_rateios_unidades_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE rateios_unidades_fechamento ADD CONSTRAINT ck_rateios_unidades_isencao CHECK (NOT isento OR valor_rateado = 0)');

        Schema::create('reaberturas_fechamento', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('periodo_financeiro_id');
            $table->foreignId('fechamento_arquivado_id');
            $table->foreignId('proximo_fechamento_id')->nullable();
            $table->text('motivo');
            $table->timestampTz('reaberto_em');
            $table->foreignId('reaberto_por');
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'reaberturas_fechamento');
            $table->foreign('periodo_financeiro_id', 'fk_reaberturas_periodo')->references('id')->on('periodos_financeiros')->restrictOnDelete();
            $table->foreign('fechamento_arquivado_id', 'fk_reaberturas_fechamento_arquivado')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('proximo_fechamento_id', 'fk_reaberturas_proximo_fechamento')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('reaberto_por', 'fk_reaberturas_reaberto_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        Schema::create('fechamentos_anuais', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->smallInteger('ano');
            $table->smallInteger('versao');
            $table->string('situacao', 15)->default('PROCESSANDO');
            $table->decimal('total_entradas', 15, 2)->default(0);
            $table->decimal('total_saidas', 15, 2)->default(0);
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_final', 15, 2)->default(0);
            $table->timestampTz('publicado_em')->nullable();
            $table->foreignId('publicado_por')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'fechamentos_anuais');
            $table->unique(['ano', 'versao'], 'uq_fechamentos_anuais_ano_versao');
            $table->foreign('publicado_por', 'fk_fechamentos_anuais_publicado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fechamentos_anuais ADD CONSTRAINT ck_fechamentos_anuais_situacao CHECK (situacao IN ('PROCESSANDO', 'PUBLICADO', 'ARQUIVADO', 'FALHOU'))");
        DB::statement('ALTER TABLE fechamentos_anuais ADD CONSTRAINT ck_fechamentos_anuais_ano CHECK (ano BETWEEN 2000 AND 9999)');
        DB::statement('ALTER TABLE fechamentos_anuais ADD CONSTRAINT ck_fechamentos_anuais_versao CHECK (versao > 0)');

        Schema::create('meses_fechamento_anual', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fechamento_anual_id');
            $table->smallInteger('mes');
            $table->foreignId('fechamento_financeiro_id');
            $table->decimal('total_entradas', 15, 2);
            $table->decimal('total_saidas', 15, 2);
            $table->decimal('saldo_inicial', 15, 2);
            $table->decimal('saldo_final', 15, 2);
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['fechamento_anual_id', 'mes'], 'uq_meses_fechamento_anual');
            $table->foreign('fechamento_anual_id', 'fk_meses_fechamento_anual')->references('id')->on('fechamentos_anuais')->restrictOnDelete();
            $table->foreign('fechamento_financeiro_id', 'fk_meses_fechamento_mensal')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE meses_fechamento_anual ADD CONSTRAINT ck_meses_fechamento_anual_mes CHECK (mes BETWEEN 1 AND 12)');

        Schema::create('exportacoes_relatorio', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->string('tipo_relatorio', 40);
            $table->foreignId('fechamento_financeiro_id')->nullable();
            $table->foreignId('fechamento_anual_id')->nullable();
            $table->json('parametros');
            $table->string('formato', 10);
            $table->string('situacao', 15)->default('ENFILEIRADO');
            $table->foreignId('solicitado_por');
            $table->timestampTz('solicitado_em')->useCurrent();
            $table->timestampTz('iniciado_em')->nullable();
            $table->timestampTz('gerado_em')->nullable();
            $table->text('mensagem_erro')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'exportacoes_relatorio');
            $table->foreign('fechamento_financeiro_id', 'fk_exportacoes_fechamento_mensal')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('fechamento_anual_id', 'fk_exportacoes_fechamento_anual')->references('id')->on('fechamentos_anuais')->restrictOnDelete();
            $table->foreign('solicitado_por', 'fk_exportacoes_solicitado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE exportacoes_relatorio ADD CONSTRAINT ck_exportacoes_tipo CHECK (tipo_relatorio IN ('EXTRATO_CONTA', 'RECEITAS_DESPESAS', 'COBRANCAS', 'INADIMPLENCIA', 'FECHAMENTO_MENSAL', 'FECHAMENTO_ANUAL', 'MEMORIA_RATEIO', 'HISTORICO_REABERTURAS'))");
        DB::statement("ALTER TABLE exportacoes_relatorio ADD CONSTRAINT ck_exportacoes_formato CHECK (formato IN ('PDF', 'XLSX'))");
        DB::statement("ALTER TABLE exportacoes_relatorio ADD CONSTRAINT ck_exportacoes_situacao CHECK (situacao IN ('ENFILEIRADO', 'PROCESSANDO', 'PRONTO', 'FALHOU'))");

        foreach (['fechamentos_financeiros'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['exportacoes_relatorio', 'meses_fechamento_anual', 'fechamentos_anuais', 'reaberturas_fechamento', 'rateios_unidades_fechamento', 'rateios_fechamento', 'movimentacoes_fechamento', 'fechamentos_financeiros'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
