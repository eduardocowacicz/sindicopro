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
        Schema::create('acordos', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('unidade_id');
            $table->foreignId('pessoa_id');
            $table->string('codigo_referencia', 50);
            $table->date('data_acordo');
            $table->decimal('valor_original', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_juros', 15, 2)->default(0);
            $table->decimal('valor_acordado', 15, 2);
            $table->smallInteger('quantidade_parcelas');
            $table->string('situacao', 20)->default('ATIVO');
            $table->text('observacoes');
            $table->foreignId('aprovado_por');

            SchemaPadrao::unicoIdPublico($table, 'acordos');
            $table->unique('codigo_referencia', 'uq_acordos_codigo_referencia');
            $table->foreign('unidade_id', 'fk_acordos_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_acordos_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
            $table->foreign('aprovado_por', 'fk_acordos_aprovado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE acordos ADD CONSTRAINT ck_acordos_valores CHECK (valor_original > 0 AND valor_desconto >= 0 AND valor_juros >= 0 AND valor_acordado > 0)');
        DB::statement('ALTER TABLE acordos ADD CONSTRAINT ck_acordos_parcelas CHECK (quantidade_parcelas > 0)');
        DB::statement("ALTER TABLE acordos ADD CONSTRAINT ck_acordos_situacao CHECK (situacao IN ('ATIVO', 'QUITADO', 'QUEBRADO', 'CANCELADO'))");

        Schema::create('parcelas_acordo', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('acordo_id');
            $table->smallInteger('numero_parcela');
            $table->date('data_vencimento');
            $table->decimal('valor', 15, 2);
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'parcelas_acordo');
            $table->unique(['acordo_id', 'numero_parcela'], 'uq_parcelas_acordo_numero');
            $table->foreign('acordo_id', 'fk_parcelas_acordo')->references('id')->on('acordos')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE parcelas_acordo ADD CONSTRAINT ck_parcelas_acordo_valor CHECK (valor > 0)');
        DB::statement('ALTER TABLE parcelas_acordo ADD CONSTRAINT ck_parcelas_acordo_numero CHECK (numero_parcela > 0)');

        Schema::create('cobrancas', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('tipo_origem', 30);
            $table->foreignId('fechamento_financeiro_id')->nullable();
            $table->foreignId('reserva_id')->nullable();
            $table->foreignId('parcela_acordo_id')->nullable();
            $table->foreignId('unidade_id');
            $table->foreignId('pessoa_id');
            $table->foreignId('vinculo_unidade_pessoa_id')->nullable();
            $table->string('papel_pagador', 20);
            $table->string('codigo_referencia', 50);
            $table->string('descricao', 255);
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->decimal('valor_original', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_juros', 15, 2)->default(0);
            $table->decimal('valor_multa', 15, 2)->default(0);
            $table->string('situacao', 20)->default('ABERTO');
            $table->timestampTz('cancelado_em')->nullable();
            $table->foreignId('cancelado_por')->nullable();
            $table->text('motivo_cancelamento')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'cobrancas');
            $table->unique('codigo_referencia', 'uq_cobrancas_codigo_referencia');
            $table->index(['situacao', 'data_vencimento'], 'idx_cobrancas_situacao_vencimento');
            $table->index(['pessoa_id', 'situacao', 'data_vencimento'], 'idx_cobrancas_pessoa_situacao_vencimento');
            $table->index(['unidade_id', 'situacao', 'data_vencimento'], 'idx_cobrancas_unidade_situacao_vencimento');
            $table->foreign('fechamento_financeiro_id', 'fk_cobrancas_fechamento')->references('id')->on('fechamentos_financeiros')->restrictOnDelete();
            $table->foreign('reserva_id', 'fk_cobrancas_reserva')->references('id')->on('reservas')->restrictOnDelete();
            $table->foreign('parcela_acordo_id', 'fk_cobrancas_parcela_acordo')->references('id')->on('parcelas_acordo')->restrictOnDelete();
            $table->foreign('unidade_id', 'fk_cobrancas_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_cobrancas_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
            $table->foreign('vinculo_unidade_pessoa_id', 'fk_cobrancas_vinculo')->references('id')->on('vinculos_unidade_pessoa')->restrictOnDelete();
            $table->foreign('cancelado_por', 'fk_cobrancas_cancelado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE cobrancas ADD CONSTRAINT ck_cobrancas_tipo_origem CHECK (tipo_origem IN ('EM_FECHAMENTO', 'RESERVA', 'PARCELA_ACORDO'))");
        DB::statement("ALTER TABLE cobrancas ADD CONSTRAINT ck_cobrancas_origem CHECK ((tipo_origem = 'EM_FECHAMENTO' AND fechamento_financeiro_id IS NOT NULL AND reserva_id IS NULL AND parcela_acordo_id IS NULL) OR (tipo_origem = 'RESERVA' AND fechamento_financeiro_id IS NULL AND reserva_id IS NOT NULL AND parcela_acordo_id IS NULL) OR (tipo_origem = 'PARCELA_ACORDO' AND fechamento_financeiro_id IS NULL AND reserva_id IS NULL AND parcela_acordo_id IS NOT NULL))");
        DB::statement("ALTER TABLE cobrancas ADD CONSTRAINT ck_cobrancas_papel CHECK (papel_pagador IN ('PROPRIETARIO', 'MORADOR'))");
        DB::statement("ALTER TABLE cobrancas ADD CONSTRAINT ck_cobrancas_situacao CHECK (situacao IN ('ABERTO', 'PARCIALMENTE_PAGO', 'PAGO', 'VENCIDO', 'CANCELADO', 'RENEGOCIADO'))");
        DB::statement('ALTER TABLE cobrancas ADD CONSTRAINT ck_cobrancas_valores CHECK (valor_original > 0 AND valor_desconto >= 0 AND valor_juros >= 0 AND valor_multa >= 0)');

        Schema::create('itens_cobranca', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cobranca_id');
            $table->foreignId('rateio_unidade_fechamento_id')->nullable();
            $table->string('tipo_item', 20);
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->timestampTz('criado_em')->useCurrent();

            $table->foreign('cobranca_id', 'fk_itens_cobranca')->references('id')->on('cobrancas')->restrictOnDelete();
            $table->foreign('rateio_unidade_fechamento_id', 'fk_itens_cobranca_rateio_unidade')->references('id')->on('rateios_unidades_fechamento')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE itens_cobranca ADD CONSTRAINT ck_itens_cobranca_tipo CHECK (tipo_item IN ('RATEIO', 'TAXA_RESERVA', 'ACORDO', 'JUROS', 'MULTA', 'DESCONTO', 'AJUSTE'))");

        Schema::create('acordo_cobrancas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('acordo_id');
            $table->foreignId('cobranca_id');
            $table->decimal('valor_incluido', 15, 2);
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['acordo_id', 'cobranca_id'], 'uq_acordo_cobrancas');
            $table->foreign('acordo_id', 'fk_acordo_cobrancas_acordo')->references('id')->on('acordos')->restrictOnDelete();
            $table->foreign('cobranca_id', 'fk_acordo_cobrancas_cobranca')->references('id')->on('cobrancas')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE acordo_cobrancas ADD CONSTRAINT ck_acordo_cobrancas_valor CHECK (valor_incluido > 0)');

        Schema::create('recebimentos', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('conta_financeira_id');
            $table->foreignId('pessoa_id');
            $table->foreignId('acordo_id')->nullable();
            $table->foreignId('movimentacao_financeira_id');
            $table->string('codigo_referencia', 50);
            $table->string('forma_pagamento', 20);
            $table->timestampTz('pago_em');
            $table->decimal('valor', 15, 2);
            $table->string('referencia_externa', 100)->nullable();
            $table->string('situacao', 15)->default('CONFIRMADO');
            $table->foreignId('registrado_por');
            $table->timestampTz('estornado_em')->nullable();
            $table->foreignId('estornado_por')->nullable();
            $table->text('motivo_estorno')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'recebimentos');
            $table->unique('codigo_referencia', 'uq_recebimentos_codigo_referencia');
            $table->unique('movimentacao_financeira_id', 'uq_recebimentos_movimentacao');
            $table->index(['pessoa_id', 'pago_em'], 'idx_recebimentos_pessoa_data');
            $table->index(['conta_financeira_id', 'pago_em'], 'idx_recebimentos_conta_data');
            $table->foreign('conta_financeira_id', 'fk_recebimentos_conta')->references('id')->on('contas_financeiras')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_recebimentos_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
            $table->foreign('acordo_id', 'fk_recebimentos_acordo')->references('id')->on('acordos')->restrictOnDelete();
            $table->foreign('movimentacao_financeira_id', 'fk_recebimentos_movimentacao')->references('id')->on('movimentacoes_financeiras')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_recebimentos_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('estornado_por', 'fk_recebimentos_estornado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX uq_recebimentos_referencia_externa ON recebimentos (conta_financeira_id, referencia_externa) WHERE referencia_externa IS NOT NULL');
        DB::statement("ALTER TABLE recebimentos ADD CONSTRAINT ck_recebimentos_forma CHECK (forma_pagamento IN ('PIX', 'TRANSFERENCIA', 'DINHEIRO', 'OUTRO'))");
        DB::statement("ALTER TABLE recebimentos ADD CONSTRAINT ck_recebimentos_situacao CHECK (situacao IN ('CONFIRMADO', 'ESTORNADO'))");
        DB::statement('ALTER TABLE recebimentos ADD CONSTRAINT ck_recebimentos_valor CHECK (valor > 0)');

        Schema::create('aplicacoes_recebimento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recebimento_id');
            $table->foreignId('cobranca_id');
            $table->decimal('valor', 15, 2);
            $table->timestampTz('estornado_em')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['recebimento_id', 'cobranca_id'], 'uq_aplicacoes_recebimento');
            $table->foreign('recebimento_id', 'fk_aplicacoes_recebimento')->references('id')->on('recebimentos')->restrictOnDelete();
            $table->foreign('cobranca_id', 'fk_aplicacoes_cobranca')->references('id')->on('cobrancas')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE aplicacoes_recebimento ADD CONSTRAINT ck_aplicacoes_recebimento_valor CHECK (valor > 0)');

        Schema::create('movimentacoes_credito', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('unidade_id');
            $table->foreignId('pessoa_id');
            $table->foreignId('recebimento_id')->nullable();
            $table->foreignId('cobranca_id')->nullable();
            $table->foreignId('movimentacao_financeira_id')->nullable();
            $table->string('tipo_lancamento', 20);
            $table->decimal('valor', 15, 2);
            $table->string('descricao', 255);
            $table->timestampTz('ocorrido_em');
            $table->foreignId('registrado_por');
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'movimentacoes_credito');
            $table->foreign('unidade_id', 'fk_movimentacoes_credito_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_movimentacoes_credito_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
            $table->foreign('recebimento_id', 'fk_movimentacoes_credito_recebimento')->references('id')->on('recebimentos')->restrictOnDelete();
            $table->foreign('cobranca_id', 'fk_movimentacoes_credito_cobranca')->references('id')->on('cobrancas')->restrictOnDelete();
            $table->foreign('movimentacao_financeira_id', 'fk_movimentacoes_credito_movimentacao')->references('id')->on('movimentacoes_financeiras')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_movimentacoes_credito_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE movimentacoes_credito ADD CONSTRAINT ck_movimentacoes_credito_tipo CHECK (tipo_lancamento IN ('CONCEDIDO', 'APLICADO', 'DEVOLVIDO', 'AJUSTE'))");
        DB::statement("ALTER TABLE movimentacoes_credito ADD CONSTRAINT ck_movimentacoes_credito_valor CHECK ((tipo_lancamento = 'CONCEDIDO' AND valor > 0) OR (tipo_lancamento IN ('APLICADO', 'DEVOLVIDO') AND valor < 0) OR (tipo_lancamento = 'AJUSTE' AND valor <> 0))");

        Schema::create('avisos_inadimplencia', function (Blueprint $table): void {
            SchemaPadrao::identificacao($table);
            $table->foreignId('cobranca_id');
            $table->foreignId('pessoa_id');
            $table->string('email_registrado', 254);
            $table->string('tipo_aviso', 30);
            $table->string('situacao', 15)->default('ENFILEIRADO');
            $table->timestampTz('enviado_em')->nullable();
            $table->string('id_mensagem_provedor', 150)->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            SchemaPadrao::unicoIdPublico($table, 'avisos_inadimplencia');
            $table->foreign('cobranca_id', 'fk_avisos_inadimplencia_cobranca')->references('id')->on('cobrancas')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_avisos_inadimplencia_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE avisos_inadimplencia ADD CONSTRAINT ck_avisos_inadimplencia_situacao CHECK (situacao IN ('ENFILEIRADO', 'ENVIADO', 'ENTREGUE', 'DEVOLVIDO', 'FALHOU'))");

        foreach (['acordos', 'cobrancas', 'recebimentos'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['avisos_inadimplencia', 'movimentacoes_credito', 'aplicacoes_recebimento', 'recebimentos', 'acordo_cobrancas', 'itens_cobranca', 'cobrancas', 'parcelas_acordo', 'acordos'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
