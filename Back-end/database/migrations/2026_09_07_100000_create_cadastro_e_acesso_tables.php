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
        Schema::create('blocos', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 30);
            $table->string('nome', 100);
            $table->smallInteger('quantidade_andares')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();
            $table->foreignId('inativado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'blocos');
            $table->unique('codigo', 'uq_blocos_codigo');
        });
        DB::statement('ALTER TABLE blocos ADD CONSTRAINT ck_blocos_quantidade_andares CHECK (quantidade_andares IS NULL OR quantidade_andares > 0)');

        Schema::create('unidades', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('bloco_id');
            $table->string('codigo', 30);
            $table->smallInteger('numero_andar')->nullable();
            $table->string('situacao_ocupacao', 20)->default('VAGO');
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();
            $table->foreignId('inativado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'unidades');
            $table->unique(['bloco_id', 'codigo'], 'uq_unidades_bloco_codigo');
            $table->index(['bloco_id', 'ativo', 'situacao_ocupacao'], 'idx_unidades_bloco_ativo_ocupacao');
            $table->foreign('bloco_id', 'fk_unidades_bloco')->references('id')->on('blocos')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE unidades ADD CONSTRAINT ck_unidades_situacao_ocupacao CHECK (situacao_ocupacao IN ('OCUPADO', 'VAGO'))");

        Schema::create('pessoas', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('nome_completo', 200);
            $table->text('cpf_criptografado')->nullable();
            $table->char('hash_cpf', 64)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->foreignId('foto_arquivo_id')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();
            $table->foreignId('inativado_por')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'pessoas');
        });
        DB::statement('CREATE UNIQUE INDEX uq_pessoas_hash_cpf ON pessoas (hash_cpf) WHERE hash_cpf IS NOT NULL');
        DB::statement('CREATE INDEX idx_pessoas_nome_normalizado ON pessoas (LOWER(nome_completo))');
        DB::statement('CREATE INDEX idx_pessoas_email_normalizado ON pessoas (LOWER(email)) WHERE email IS NOT NULL');

        Schema::create('usuarios', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('pessoa_id');
            $table->string('email', 254);
            $table->string('senha', 255);
            $table->timestampTz('email_verificado_em')->nullable();
            $table->string('token_lembrar', 100)->nullable();
            $table->timestampTz('ultimo_acesso_em')->nullable();
            $table->boolean('deve_alterar_senha')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestampTz('bloqueado_em')->nullable();
            $table->string('motivo_bloqueio', 255)->nullable();

            SchemaPadrao::unicoIdPublico($table, 'usuarios');
            $table->unique('pessoa_id', 'uq_usuarios_pessoa');
            $table->index(['ativo', 'bloqueado_em'], 'idx_usuarios_ativo_bloqueio');
            $table->foreign('pessoa_id', 'fk_usuarios_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX uq_usuarios_email_normalizado ON usuarios (LOWER(email))');

        foreach (['blocos', 'unidades', 'pessoas'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->foreign('criado_por', "fk_{$nomeTabela}_criado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('atualizado_por', "fk_{$nomeTabela}_atualizado_por")->references('id')->on('usuarios')->restrictOnDelete();
                $table->foreign('inativado_por', "fk_{$nomeTabela}_inativado_por")->references('id')->on('usuarios')->restrictOnDelete();
            });
        }
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->foreign('criado_por', 'fk_usuarios_criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('atualizado_por', 'fk_usuarios_atualizado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        Schema::create('vinculos_unidade_pessoa', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('unidade_id');
            $table->foreignId('pessoa_id');
            $table->string('tipo_vinculo', 20);
            $table->string('papel_cobranca', 20)->nullable();
            $table->boolean('contato_principal')->default(false);
            $table->boolean('responsavel_financeiro')->default(false);
            $table->date('inicio_vigencia');
            $table->date('fim_vigencia')->nullable();
            $table->text('observacoes')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'vinculos_unidade_pessoa');
            $table->index(['unidade_id', 'inicio_vigencia', 'fim_vigencia'], 'idx_vinculos_unidade_vigencia');
            $table->index(['pessoa_id', 'inicio_vigencia', 'fim_vigencia'], 'idx_vinculos_pessoa_vigencia');
            $table->foreign('unidade_id', 'fk_vinculos_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_vinculos_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE vinculos_unidade_pessoa ADD CONSTRAINT ck_vinculos_tipo CHECK (tipo_vinculo IN ('PROPRIETARIO', 'LOCATARIO', 'MORADOR', 'DEPENDENTE'))");
        DB::statement("ALTER TABLE vinculos_unidade_pessoa ADD CONSTRAINT ck_vinculos_papel CHECK (papel_cobranca IS NULL OR papel_cobranca IN ('PROPRIETARIO', 'MORADOR'))");
        DB::statement('ALTER TABLE vinculos_unidade_pessoa ADD CONSTRAINT ck_vinculos_vigencia CHECK (fim_vigencia IS NULL OR fim_vigencia >= inicio_vigencia)');
        DB::statement('ALTER TABLE vinculos_unidade_pessoa ADD CONSTRAINT ck_vinculos_responsavel_papel CHECK (NOT responsavel_financeiro OR papel_cobranca IS NOT NULL)');
        DB::statement('CREATE UNIQUE INDEX uq_vinculos_responsavel_vigente ON vinculos_unidade_pessoa (unidade_id, papel_cobranca) WHERE responsavel_financeiro AND fim_vigencia IS NULL');

        Schema::create('veiculos', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->foreignId('unidade_id');
            $table->foreignId('pessoa_id')->nullable();
            $table->string('placa', 10);
            $table->string('modelo', 100);
            $table->string('cor', 40)->nullable();
            $table->string('vaga', 30)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('inativado_em')->nullable();

            SchemaPadrao::unicoIdPublico($table, 'veiculos');
            $table->foreign('unidade_id', 'fk_veiculos_unidade')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('pessoa_id', 'fk_veiculos_pessoa')->references('id')->on('pessoas')->restrictOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX uq_veiculos_placa_ativa ON veiculos (UPPER(placa)) WHERE ativo');

        Schema::create('configuracoes_sistema', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('chave', 100);
            $table->json('valor');
            $table->string('descricao', 255)->nullable();

            SchemaPadrao::unicoIdPublico($table, 'configuracoes_sistema');
            $table->unique('chave', 'uq_configuracoes_sistema_chave');
        });

        Schema::create('perfis', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 50);
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->boolean('sistema')->default(false);
            $table->boolean('ativo')->default(true);

            SchemaPadrao::unicoIdPublico($table, 'perfis');
            $table->unique('codigo', 'uq_perfis_codigo');
        });

        Schema::create('permissoes', function (Blueprint $table): void {
            SchemaPadrao::entidade($table);
            $table->string('codigo', 100);
            $table->string('modulo', 50);
            $table->string('acao', 30);
            $table->string('nome', 120);
            $table->text('descricao')->nullable();
            $table->boolean('sensivel')->default(false);

            SchemaPadrao::unicoIdPublico($table, 'permissoes');
            $table->unique('codigo', 'uq_permissoes_codigo');
            $table->index(['modulo', 'acao'], 'idx_permissoes_modulo_acao');
        });

        Schema::create('usuario_perfis', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id');
            $table->foreignId('perfil_id');
            $table->timestampTz('inicia_em')->useCurrent();
            $table->timestampTz('termina_em')->nullable();
            $table->foreignId('atribuido_por')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            $table->foreign('usuario_id', 'fk_usuario_perfis_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('perfil_id', 'fk_usuario_perfis_perfil')->references('id')->on('perfis')->restrictOnDelete();
            $table->foreign('atribuido_por', 'fk_usuario_perfis_atribuido_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['usuario_id', 'inicia_em', 'termina_em'], 'idx_usuario_perfis_vigencia');
        });
        DB::statement('ALTER TABLE usuario_perfis ADD CONSTRAINT ck_usuario_perfis_vigencia CHECK (termina_em IS NULL OR termina_em >= inicia_em)');
        DB::statement('CREATE UNIQUE INDEX uq_usuario_perfis_vigente ON usuario_perfis (usuario_id, perfil_id) WHERE termina_em IS NULL');

        Schema::create('perfil_permissoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('perfil_id');
            $table->foreignId('permissao_id');
            $table->foreignId('concedido_por')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            $table->unique(['perfil_id', 'permissao_id'], 'uq_perfil_permissoes');
            $table->foreign('perfil_id', 'fk_perfil_permissoes_perfil')->references('id')->on('perfis')->restrictOnDelete();
            $table->foreign('permissao_id', 'fk_perfil_permissoes_permissao')->references('id')->on('permissoes')->restrictOnDelete();
            $table->foreign('concedido_por', 'fk_perfil_permissoes_concedido_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        Schema::create('usuario_permissoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id');
            $table->foreignId('permissao_id');
            $table->string('efeito', 10);
            $table->text('motivo');
            $table->timestampTz('inicia_em')->useCurrent();
            $table->timestampTz('termina_em')->nullable();
            $table->foreignId('definido_por')->nullable();
            $table->timestampTz('criado_em')->useCurrent();

            $table->foreign('usuario_id', 'fk_usuario_permissoes_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('permissao_id', 'fk_usuario_permissoes_permissao')->references('id')->on('permissoes')->restrictOnDelete();
            $table->foreign('definido_por', 'fk_usuario_permissoes_definido_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['usuario_id', 'inicia_em', 'termina_em'], 'idx_usuario_permissoes_vigencia');
        });
        DB::statement("ALTER TABLE usuario_permissoes ADD CONSTRAINT ck_usuario_permissoes_efeito CHECK (efeito IN ('PERMITIR', 'NEGAR'))");
        DB::statement('ALTER TABLE usuario_permissoes ADD CONSTRAINT ck_usuario_permissoes_vigencia CHECK (termina_em IS NULL OR termina_em >= inicia_em)');
        DB::statement('CREATE UNIQUE INDEX uq_usuario_permissoes_vigente ON usuario_permissoes (usuario_id, permissao_id) WHERE termina_em IS NULL');

        Schema::create('tokens_redefinicao_senha', function (Blueprint $table): void {
            $table->string('email', 254)->primary();
            $table->string('token', 255);
            $table->timestampTz('criado_em')->nullable();
        });

        Schema::create('sessoes', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('usuario_id')->nullable();
            $table->ipAddress('endereco_ip')->nullable();
            $table->text('agente_usuario')->nullable();
            $table->text('conteudo_sessao');
            $table->integer('ultima_atividade');

            $table->foreign('usuario_id', 'fk_sessoes_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index('usuario_id', 'idx_sessoes_usuario');
            $table->index('ultima_atividade', 'idx_sessoes_ultima_atividade');
        });

        Schema::create('logs_acesso', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable();
            $table->char('hash_email_tentado', 64)->nullable();
            $table->string('tipo_evento', 30);
            $table->ipAddress('endereco_ip')->nullable();
            $table->text('agente_usuario')->nullable();
            $table->string('sessao_id')->nullable();
            $table->json('metadados')->nullable();
            $table->timestampTz('ocorrido_em')->useCurrent();

            $table->foreign('usuario_id', 'fk_logs_acesso_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['usuario_id', 'ocorrido_em'], 'idx_logs_acesso_usuario_data');
            $table->index(['tipo_evento', 'ocorrido_em'], 'idx_logs_acesso_tipo_data');
        });
        DB::statement("ALTER TABLE logs_acesso ADD CONSTRAINT ck_logs_acesso_tipo CHECK (tipo_evento IN ('LOGIN_SUCESSO', 'LOGIN_FALHOU', 'SAIDA_SISTEMA', 'SENHA_REDEFINIDA', 'SESSAO_EXPIRADA', 'CONTA_BLOQUEADA'))");

        $this->adicionarAuditoria(['vinculos_unidade_pessoa', 'veiculos', 'configuracoes_sistema', 'perfis', 'permissoes']);
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
        foreach (['logs_acesso', 'sessoes', 'tokens_redefinicao_senha', 'usuario_permissoes', 'perfil_permissoes', 'usuario_perfis', 'permissoes', 'perfis', 'configuracoes_sistema', 'veiculos', 'vinculos_unidade_pessoa'] as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropForeign('fk_usuarios_criado_por');
            $table->dropForeign('fk_usuarios_atualizado_por');
        });
        foreach (['pessoas', 'unidades', 'blocos'] as $nomeTabela) {
            Schema::table($nomeTabela, function (Blueprint $table) use ($nomeTabela): void {
                $table->dropForeign("fk_{$nomeTabela}_criado_por");
                $table->dropForeign("fk_{$nomeTabela}_atualizado_por");
                $table->dropForeign("fk_{$nomeTabela}_inativado_por");
            });
        }

        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('pessoas');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('blocos');
    }
};
