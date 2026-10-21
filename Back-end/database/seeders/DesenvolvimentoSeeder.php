<?php

namespace Database\Seeders;

use App\Models\Ambiente;
use App\Models\Bloco;
use App\Models\CategoriaFinanceira;
use App\Models\Comunicado;
use App\Models\ContaFinanceira;
use App\Models\FaixaHorarioAmbiente;
use App\Models\MovimentacaoFinanceira;
use App\Models\Ocorrencia;
use App\Models\Perfil;
use App\Models\PeriodoFinanceiro;
use App\Models\Permissao;
use App\Models\Pessoa;
use App\Models\Reserva;
use App\Models\TaxaAmbiente;
use App\Models\TipoCobranca;
use App\Models\TipoOcorrencia;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Models\Veiculo;
use App\Models\VinculoUnidadePessoa;
use App\Services\Cadastros\BlocoService;
use App\Services\Cadastros\PessoaService;
use App\Services\Comunicados\ComunicadoService;
use App\Services\Fechamentos\FechamentoMensalService;
use App\Services\Ocorrencias\OcorrenciaService;
use App\Services\Reservas\ReservaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DesenvolvimentoSeeder extends Seeder
{
    private const PERFIS = [
        'SINDICO' => 'Síndico',
        'SUBSINDICO' => 'Subsíndico',
        'CONSELHO' => 'Conselho',
        'MORADOR' => 'Morador',
        'PROPRIETARIO' => 'Proprietário',
    ];

    private const RECURSOS = [
        'painel' => ['consultar'],
        'cadastros.blocos' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'cadastros.unidades' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'cadastros.pessoas' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'cadastros.veiculos' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'cadastros.vinculos' => ['consultar', 'cadastrar', 'encerrar'],
        'acesso.usuarios' => ['consultar', 'cadastrar', 'editar', 'inativar', 'bloquear', 'desbloquear', 'redefinir_senha'],
        'acesso.perfis' => ['consultar', 'cadastrar', 'editar', 'inativar', 'gerenciar_permissoes'],
        'financeiro.movimentacoes' => ['consultar', 'cadastrar', 'editar', 'contabilizar', 'estornar'],
        'financeiro.contas' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'financeiro.categorias' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'financeiro.tipos_cobranca' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'financeiro.isencoes' => ['consultar', 'cadastrar', 'revogar'],
        'financeiro.modelos_recorrentes' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'financeiro.cobrancas' => ['consultar', 'cancelar'],
        'financeiro.recebimentos' => ['consultar', 'registrar', 'estornar'],
        'financeiro.acordos' => ['consultar', 'cadastrar', 'cancelar'],
        'financeiro.creditos' => ['consultar', 'ajustar', 'devolver'],
        'financeiro.inadimplencia' => ['consultar', 'notificar'],
        'fechamentos.mensais' => ['consultar', 'fechar', 'reabrir', 'exportar'],
        'fechamentos.anuais' => ['consultar', 'fechar', 'exportar'],
        'relatorios' => ['consultar', 'exportar'],
        'reservas.agenda' => ['consultar', 'cadastrar', 'cancelar', 'validar'],
        'reservas.ambientes' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'reservas.bloqueios' => ['consultar', 'cadastrar', 'cancelar'],
        'comunicados' => ['consultar', 'cadastrar', 'editar', 'publicar', 'cancelar'],
        'ocorrencias' => ['consultar', 'cadastrar', 'responder', 'alterar_situacao'],
        'ocorrencias.tipos' => ['consultar', 'cadastrar', 'editar', 'inativar'],
        'arquivos' => ['enviar', 'baixar'],
        'auditoria.acessos' => ['consultar'],
        'auditoria.acoes' => ['consultar'],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::PERFIS as $codigo => $nome) {
                Perfil::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nome' => $nome,
                        'descricao' => "Perfil oficial {$nome}",
                        'sistema' => true,
                        'ativo' => true,
                    ],
                );
            }

            $pessoa = Pessoa::query()->updateOrCreate(
                ['email' => 'sindico@pro.com'],
                [
                    'nome_completo' => 'Síndico de Desenvolvimento',
                    'telefone' => null,
                    'ativo' => true,
                ],
            );

            $usuario = Usuario::query()->updateOrCreate(
                ['email' => 'sindico@pro.com'],
                [
                    'pessoa_id' => $pessoa->getKey(),
                    'senha' => Hash::make('123'),
                    'email_verificado_em' => now(),
                    'deve_alterar_senha' => false,
                    'ativo' => true,
                    'bloqueado_em' => null,
                    'motivo_bloqueio' => null,
                ],
            );

            $permissoes = collect(self::RECURSOS)->flatMap(
                fn (array $acoes, string $recurso): array => array_map(
                    fn (string $acao): array => [$recurso, $acao],
                    $acoes,
                ),
            );

            foreach ($permissoes as [$recurso, $acao]) {
                $codigo = "{$recurso}.{$acao}";
                $modulo = explode('.', $recurso)[0];

                Permissao::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'modulo' => $modulo,
                        'acao' => $acao,
                        'nome' => str($codigo)->replace(['.', '_'], ' ')->title()->toString(),
                        'sensivel' => in_array($acao, ['estornar', 'fechar', 'reabrir', 'baixar', 'gerenciar_permissoes'], true),
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );
            }

            $perfilSindico = Perfil::query()->where('codigo', 'SINDICO')->firstOrFail();
            DB::table('usuario_perfis')->updateOrInsert(
                [
                    'usuario_id' => $usuario->getKey(),
                    'perfil_id' => $perfilSindico->getKey(),
                    'termina_em' => null,
                ],
                [
                    'inicia_em' => now(),
                    'atribuido_por' => $usuario->getKey(),
                    'criado_em' => now(),
                ],
            );

            foreach (Permissao::query()->pluck('id') as $permissaoId) {
                DB::table('perfil_permissoes')->updateOrInsert(
                    [
                        'perfil_id' => $perfilSindico->getKey(),
                        'permissao_id' => $permissaoId,
                    ],
                    [
                        'concedido_por' => $usuario->getKey(),
                        'criado_em' => now(),
                    ],
                );
            }

            Pessoa::query()->updateOrCreate(
                ['email' => 'prestador@servico.com'],
                [
                    'nome_completo' => 'João Silva Pinturas e Reformas ME',
                    'tipo' => 'PRESTADOR_SERVICO',
                    'telefone' => '11987654321',
                    'ativo' => true,
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ],
            );

            Pessoa::query()->updateOrCreate(
                ['email' => 'fornecedor@energia.com'],
                [
                    'nome_completo' => 'Companhia Paulista de Energia Elétrica',
                    'tipo' => 'FORNECEDOR',
                    'telefone' => '1133334444',
                    'ativo' => true,
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ],
            );

            // Contas financeiras padrão
            ContaFinanceira::query()->firstOrCreate(
                ['nome' => 'Conta Corrente Principal'],
                [
                    'tipo_conta' => 'CONTA_CORRENTE',
                    'nome_banco' => 'Banco do Brasil',
                    'codigo_banco' => '001',
                    'saldo_inicial' => 50000.00,
                    'data_saldo_inicial' => '2026-01-01',
                    'ativo' => true,
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ],
            );

            ContaFinanceira::query()->firstOrCreate(
                ['nome' => 'Caixa Físico / Pequeno Caixa'],
                [
                    'tipo_conta' => 'CAIXA',
                    'saldo_inicial' => 1500.00,
                    'data_saldo_inicial' => '2026-01-01',
                    'ativo' => true,
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ],
            );

            // Categorias financeiras padrão de entrada e saída
            $categorias = [
                ['nome' => 'Taxa Condominial Ordinária', 'direcao' => 'ENTRADA'],
                ['nome' => 'Fundo de Reserva', 'direcao' => 'ENTRADA'],
                ['nome' => 'Taxa de Mudança', 'direcao' => 'ENTRADA'],
                ['nome' => 'Rendimento de Aplicações', 'direcao' => 'ENTRADA'],
                ['nome' => 'Multas e Juros', 'direcao' => 'ENTRADA'],
                ['nome' => 'Energia Elétrica', 'direcao' => 'SAIDA'],
                ['nome' => 'Água e Esgoto', 'direcao' => 'SAIDA'],
                ['nome' => 'Manutenção e Conservação Predial', 'direcao' => 'SAIDA'],
                ['nome' => 'Serviços de Limpeza e Portaria', 'direcao' => 'SAIDA'],
                ['nome' => 'Manutenção de Elevadores', 'direcao' => 'SAIDA'],
                ['nome' => 'Despesas Bancárias', 'direcao' => 'SAIDA'],
            ];

            foreach ($categorias as $cat) {
                CategoriaFinanceira::query()->firstOrCreate(
                    ['nome' => $cat['nome']],
                    [
                        'direcao' => $cat['direcao'],
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );
            }

            $tiposCobranca = [
                [
                    'nome' => 'Cota Condominial Ordinária',
                    'papel_pagador' => 'PROPRIETARIO',
                    'dia_vencimento_padrao' => 10,
                    'descricao' => 'Cota mensal regular de rateio condominial',
                ],
                [
                    'nome' => 'Fundo de Reserva',
                    'papel_pagador' => 'PROPRIETARIO',
                    'dia_vencimento_padrao' => 10,
                    'descricao' => 'Contribuição mensal ao fundo de reserva do condomínio',
                ],
                [
                    'nome' => 'Taxa de Mudança',
                    'papel_pagador' => 'MORADOR',
                    'dia_vencimento_padrao' => 15,
                    'descricao' => 'Taxa operacional cobrada em mudanças de moradores',
                ],
            ];

            foreach ($tiposCobranca as $tipo) {
                TipoCobranca::query()->firstOrCreate(
                    ['nome' => $tipo['nome']],
                    [
                        ...$tipo,
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );
            }

            // Gerador determinístico de CPFs válidos para testes
            $gerarCpf = function (int $baseNum): string {
                $base = sprintf('%09d', $baseNum);
                $s1 = 0;
                for ($i = 0; $i < 9; $i++) {
                    $s1 += (int) $base[$i] * (10 - $i);
                }
                $d1 = ($s1 * 10) % 11 % 10;
                $baseComD1 = $base.$d1;
                $s2 = 0;
                for ($i = 0; $i < 10; $i++) {
                    $s2 += (int) $baseComD1[$i] * (11 - $i);
                }
                $d2 = ($s2 * 10) % 11 % 10;

                return $base.$d1.$d2;
            };

            $pessoaService = app(PessoaService::class);
            $pessoasLista = [
                ['nome_completo' => 'Carlos Eduardo Mendes', 'tipo' => 'MORADOR', 'email' => 'carlos.mendes@email.com', 'telefone' => '11981234567', 'cpf' => $gerarCpf(111222333)],
                ['nome_completo' => 'Mariana Souza Ramos', 'tipo' => 'MORADOR', 'email' => 'mariana.ramos@email.com', 'telefone' => '11972345678', 'cpf' => $gerarCpf(222333444)],
                ['nome_completo' => 'Roberto Alves Ferreira', 'tipo' => 'MORADOR', 'email' => 'roberto.ferreira@email.com', 'telefone' => '11963456789', 'cpf' => $gerarCpf(333444555)],
                ['nome_completo' => 'Juliana Costa Lima', 'tipo' => 'MORADOR', 'email' => 'juliana.lima@email.com', 'telefone' => '11954567890', 'cpf' => $gerarCpf(444555666)],
                ['nome_completo' => 'Fernando Henrique Dias', 'tipo' => 'MORADOR', 'email' => 'fernando.dias@email.com', 'telefone' => '11945678901', 'cpf' => $gerarCpf(555666777)],
                ['nome_completo' => 'Camila Barreto Gomes', 'tipo' => 'MORADOR', 'email' => 'camila.gomes@email.com', 'telefone' => '11936789012', 'cpf' => $gerarCpf(666777888)],
                ['nome_completo' => 'Lucas Pires Santos', 'tipo' => 'MORADOR', 'email' => 'lucas.santos@email.com', 'telefone' => '11927890123', 'cpf' => $gerarCpf(777888999)],
                ['nome_completo' => 'Beatriz Rocha Martins', 'tipo' => 'MORADOR', 'email' => 'beatriz.martins@email.com', 'telefone' => '11918901234', 'cpf' => $gerarCpf(888999111)],
                ['nome_completo' => 'Elevadores Atlas Schindler Brasil', 'tipo' => 'PRESTADOR_SERVICO', 'email' => 'contato@schindler.com', 'telefone' => '1140041234'],
                ['nome_completo' => 'SegurMax Portaria e Vigilância', 'tipo' => 'PRESTADOR_SERVICO', 'email' => 'contato@segurmax.com', 'telefone' => '1133338888'],
                ['nome_completo' => 'HidroLimpeza Desentupidora', 'tipo' => 'PRESTADOR_SERVICO', 'email' => 'atendimento@hidrolimpeza.com', 'telefone' => '1131112222'],
                ['nome_completo' => 'Sanepar Saneamento e Abastecimento', 'tipo' => 'FORNECEDOR', 'email' => 'contas@sanepar.com.br', 'telefone' => '1130035000'],
            ];

            $pessoasInstancias = [];
            foreach ($pessoasLista as $dadosPessoa) {
                $p = Pessoa::query()->where('email', $dadosPessoa['email'])->first();
                if (! $p) {
                    $p = $pessoaService->criar($dadosPessoa, $usuario);
                }
                $pessoasInstancias[$dadosPessoa['email']] = $p;
            }

            // Blocos e unidades
            $blocoService = app(BlocoService::class);
            if (Bloco::query()->count() === 0) {
                $blocoService->criar([
                    'nome' => 'Bloco A',
                    'apelido' => 'Torre Solar',
                    'andares' => 4,
                    'apartamentos_por_andar' => 4,
                    'ativo' => true,
                ], $usuario);

                $blocoService->criar([
                    'nome' => 'Bloco B',
                    'apelido' => 'Torre Lunar',
                    'andares' => 4,
                    'apartamentos_por_andar' => 4,
                    'ativo' => true,
                ], $usuario);

                $blocoService->criar([
                    'nome' => 'Bloco C',
                    'apelido' => 'Edifício Jardins',
                    'andares' => 2,
                    'apartamentos_por_andar' => 2,
                    'ativo' => true,
                ], $usuario);
            }

            $blocoA = Bloco::query()->where('nome', 'Bloco A')->first();
            $blocoB = Bloco::query()->where('nome', 'Bloco B')->first();
            $blocoC = Bloco::query()->where('nome', 'Bloco C')->first();

            // Vínculos e Veículos
            if ($blocoA && VinculoUnidadePessoa::query()->count() === 0) {
                $vincular = function (Unidade $unidade, Pessoa $pessoa, string $tipoVinculo, ?string $papelCobranca, bool $respFinanceiro, ?string $obsUnidade = null) use ($usuario): void {
                    VinculoUnidadePessoa::query()->create([
                        'unidade_id' => $unidade->getKey(),
                        'pessoa_id' => $pessoa->getKey(),
                        'tipo_vinculo' => $tipoVinculo,
                        'papel_cobranca' => $papelCobranca,
                        'contato_principal' => true,
                        'responsavel_financeiro' => $respFinanceiro,
                        'inicio_vigencia' => '2026-01-01',
                        'fim_vigencia' => null,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ]);

                    $unidade->forceFill([
                        'situacao_ocupacao' => 'OCUPADO',
                        'observacoes' => $obsUnidade,
                        'atualizado_por' => $usuario->getKey(),
                    ])->save();
                };

                $criarVeiculo = function (Unidade $unidade, ?Pessoa $pessoa, string $placa, string $modelo, string $cor, string $vaga, ?string $obs = null) use ($usuario): void {
                    Veiculo::query()->create([
                        'unidade_id' => $unidade->getKey(),
                        'pessoa_id' => $pessoa?->getKey(),
                        'placa' => $placa,
                        'modelo' => $modelo,
                        'cor' => $cor,
                        'vaga' => $vaga,
                        'observacoes' => $obs,
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ]);
                };

                // Apto 101 Bloco A - Carlos & Síndico
                $aptoA101 = Unidade::query()->where('bloco_id', $blocoA->getKey())->where('codigo', '101')->first();
                if ($aptoA101 && isset($pessoasInstancias['carlos.mendes@email.com'])) {
                    $p = $pessoasInstancias['carlos.mendes@email.com'];
                    $vincular($aptoA101, $p, 'PROPRIETARIO', 'PROPRIETARIO', true, 'Proprietário residente. Possui 1 cachorro de porte pequeno.');
                    $criarVeiculo($aptoA101, $p, 'TOR1A01', 'Toyota Corolla Cross', 'Prata', 'Vaga G1-01', 'Tag de acesso 101');

                    VinculoUnidadePessoa::query()->create([
                        'unidade_id' => $aptoA101->getKey(),
                        'pessoa_id' => $pessoa->getKey(),
                        'tipo_vinculo' => 'MORADOR',
                        'papel_cobranca' => null,
                        'contato_principal' => false,
                        'responsavel_financeiro' => false,
                        'inicio_vigencia' => '2026-01-01',
                        'fim_vigencia' => null,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ]);
                }

                // Apto 102 Bloco A - Mariana
                $aptoA102 = Unidade::query()->where('bloco_id', $blocoA->getKey())->where('codigo', '102')->first();
                if ($aptoA102 && isset($pessoasInstancias['mariana.ramos@email.com'])) {
                    $p = $pessoasInstancias['mariana.ramos@email.com'];
                    $vincular($aptoA102, $p, 'LOCATARIO', 'MORADOR', true, 'Locatária recente. Vaga demarcada.');
                    $criarVeiculo($aptoA102, $p, 'TOR1A02', 'Honda Civic Touring', 'Preto', 'Vaga G1-02', 'Tag de acesso 102');
                }

                // Apto 201 Bloco A - Roberto
                $aptoA201 = Unidade::query()->where('bloco_id', $blocoA->getKey())->where('codigo', '201')->first();
                if ($aptoA201 && isset($pessoasInstancias['roberto.ferreira@email.com'])) {
                    $p = $pessoasInstancias['roberto.ferreira@email.com'];
                    $vincular($aptoA201, $p, 'PROPRIETARIO', 'PROPRIETARIO', true, 'Conselheiro fiscal ativo do condomínio.');
                    $criarVeiculo($aptoA201, $p, 'TOR2A01', 'Jeep Compass Longitude', 'Branco', 'Vaga G1-05');
                }

                // Apto 202 Bloco A - Juliana
                $aptoA202 = Unidade::query()->where('bloco_id', $blocoA->getKey())->where('codigo', '202')->first();
                if ($aptoA202 && isset($pessoasInstancias['juliana.lima@email.com'])) {
                    $p = $pessoasInstancias['juliana.lima@email.com'];
                    $vincular($aptoA202, $p, 'MORADOR', 'MORADOR', false, 'Mora com a família.');
                    $criarVeiculo($aptoA202, $p, 'TOR2A02', 'Hyundai HB20 Platinum', 'Cinza', 'Vaga G1-06');
                }

                // Apto 101 Bloco B - Fernando
                if ($blocoB) {
                    $aptoB101 = Unidade::query()->where('bloco_id', $blocoB->getKey())->where('codigo', '101')->first();
                    if ($aptoB101 && isset($pessoasInstancias['fernando.dias@email.com'])) {
                        $p = $pessoasInstancias['fernando.dias@email.com'];
                        $vincular($aptoB101, $p, 'PROPRIETARIO', 'PROPRIETARIO', true, 'Apartamento reformado recentemente.');
                        $criarVeiculo($aptoB101, $p, 'TOR1B01', 'Volkswagen T-Cross Highline', 'Azul', 'Vaga G2-01');
                    }

                    $aptoB102 = Unidade::query()->where('bloco_id', $blocoB->getKey())->where('codigo', '102')->first();
                    if ($aptoB102 && isset($pessoasInstancias['camila.gomes@email.com'])) {
                        $p = $pessoasInstancias['camila.gomes@email.com'];
                        $vincular($aptoB102, $p, 'LOCATARIO', 'MORADOR', true, 'Solicitou autorização para vaga de visitante.');
                        $criarVeiculo($aptoB102, $p, 'TOR1B02', 'Chevrolet Tracker Premier', 'Vermelho', 'Vaga G2-02');
                    }

                    $aptoB201 = Unidade::query()->where('bloco_id', $blocoB->getKey())->where('codigo', '201')->first();
                    if ($aptoB201 && isset($pessoasInstancias['lucas.santos@email.com'])) {
                        $p = $pessoasInstancias['lucas.santos@email.com'];
                        $vincular($aptoB201, $p, 'PROPRIETARIO', 'PROPRIETARIO', true, 'Subsíndico do bloco B.');
                        $criarVeiculo($aptoB201, $p, 'TOR2B01', 'Fiat Pulse Audace', 'Cinza Strato', 'Vaga G2-05');
                    }
                }

                // Apto 101 Bloco C - Beatriz
                if ($blocoC) {
                    $aptoC101 = Unidade::query()->where('bloco_id', $blocoC->getKey())->where('codigo', '101')->first();
                    if ($aptoC101 && isset($pessoasInstancias['beatriz.martins@email.com'])) {
                        $p = $pessoasInstancias['beatriz.martins@email.com'];
                        $vincular($aptoC101, $p, 'PROPRIETARIO', 'PROPRIETARIO', true, 'Membro da comissão de patrimônio.');
                        $criarVeiculo($aptoC101, $p, 'TOR1C01', 'Nissan Kicks Exclusive', 'Branco Perolizado', 'Vaga C-101');
                    }
                }
            }

            // Usuário Morador para Teste em Desenvolvimento e Homologação
            $perfilMorador = Perfil::query()->where('codigo', 'MORADOR')->first();
            $pessoaMariana = $pessoasInstancias['mariana.ramos@email.com'] ?? null;
            if ($perfilMorador && $pessoaMariana) {
                $usuarioMorador = Usuario::query()->updateOrCreate(
                    ['email' => 'morador@pro.com'],
                    [
                        'pessoa_id' => $pessoaMariana->getKey(),
                        'senha' => Hash::make('123'),
                        'email_verificado_em' => now(),
                        'deve_alterar_senha' => false,
                        'ativo' => true,
                    ],
                );

                DB::table('usuario_perfis')->updateOrInsert(
                    [
                        'usuario_id' => $usuarioMorador->getKey(),
                        'perfil_id' => $perfilMorador->getKey(),
                        'termina_em' => null,
                    ],
                    [
                        'inicia_em' => now(),
                        'atribuido_por' => $usuario->getKey(),
                        'criado_em' => now(),
                    ],
                );
            }

            // Movimentações financeiras e Fechamentos de exemplo
            $contaPrincipal = ContaFinanceira::query()->where('nome', 'Conta Corrente Principal')->firstOrFail();
            $catTaxa = CategoriaFinanceira::query()->where('nome', 'Taxa Condominial Ordinária')->firstOrFail();
            $catFundo = CategoriaFinanceira::query()->where('nome', 'Fundo de Reserva')->firstOrFail();
            $catMudanca = CategoriaFinanceira::query()->where('nome', 'Taxa de Mudança')->firstOrFail();
            $catRend = CategoriaFinanceira::query()->where('nome', 'Rendimento de Aplicações')->firstOrFail();
            $catMulta = CategoriaFinanceira::query()->where('nome', 'Multas e Juros')->firstOrFail();

            $catEnergia = CategoriaFinanceira::query()->where('nome', 'Energia Elétrica')->firstOrFail();
            $catAgua = CategoriaFinanceira::query()->where('nome', 'Água e Esgoto')->firstOrFail();
            $catManut = CategoriaFinanceira::query()->where('nome', 'Manutenção e Conservação Predial')->firstOrFail();
            $catLimpeza = CategoriaFinanceira::query()->where('nome', 'Serviços de Limpeza e Portaria')->firstOrFail();
            $catElevadores = CategoriaFinanceira::query()->where('nome', 'Manutenção de Elevadores')->firstOrFail();
            $catTarifas = CategoriaFinanceira::query()->where('nome', 'Despesas Bancárias')->firstOrFail();

            $tipoCota = TipoCobranca::query()->where('nome', 'Cota Condominial Ordinária')->first();
            $tipoMudanca = TipoCobranca::query()->where('nome', 'Taxa de Mudança')->first();

            $pCpfl = Pessoa::query()->where('email', 'fornecedor@energia.com')->first();
            $pSanepar = $pessoasInstancias['contas@sanepar.com.br'] ?? null;
            $pSchindler = $pessoasInstancias['contato@schindler.com'] ?? null;
            $pSegurmax = $pessoasInstancias['contato@segurmax.com'] ?? null;
            $pHidro = $pessoasInstancias['atendimento@hidrolimpeza.com'] ?? null;
            $pJoao = Pessoa::query()->where('email', 'prestador@servico.com')->first();
            $pMariana = $pessoasInstancias['mariana.ramos@email.com'] ?? null;

            $dadosMeses = [
                '2026-07-01' => [
                    'fechar' => true,
                    'movs' => [
                        ['direcao' => 'ENTRADA', 'cat' => $catTaxa, 'tipo' => $tipoCota, 'pessoa' => null, 'desc' => 'Arrecadação de Cotas Condominiais Ordinárias (Julho)', 'val' => 17850.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catMudanca, 'tipo' => $tipoMudanca, 'pessoa' => $pMariana, 'desc' => 'Taxa de Mudança - Unidade 102 Bloco A', 'val' => 350.00, 'dia' => 12],
                        ['direcao' => 'ENTRADA', 'cat' => $catRend, 'tipo' => null, 'pessoa' => null, 'desc' => 'Rendimento de Aplicações CDB Automático', 'val' => 245.80, 'dia' => 28],
                        ['direcao' => 'SAIDA', 'cat' => $catEnergia, 'tipo' => null, 'pessoa' => $pCpfl, 'desc' => 'Conta de Energia Elétrica - Áreas Comuns e Elevadores', 'val' => 2450.00, 'dia' => 15],
                        ['direcao' => 'SAIDA', 'cat' => $catAgua, 'tipo' => null, 'pessoa' => $pSanepar, 'desc' => 'Consumo de Água e Saneamento do Condomínio', 'val' => 1890.30, 'dia' => 16],
                        ['direcao' => 'SAIDA', 'cat' => $catLimpeza, 'tipo' => null, 'pessoa' => $pSegurmax, 'desc' => 'Contrato Mensal de Portaria, Segurança e Limpeza', 'val' => 7800.00, 'dia' => 20],
                        ['direcao' => 'SAIDA', 'cat' => $catElevadores, 'tipo' => null, 'pessoa' => $pSchindler, 'desc' => 'Manutenção Preventiva e Conservação dos Elevadores', 'val' => 1200.00, 'dia' => 22],
                        ['direcao' => 'SAIDA', 'cat' => $catManut, 'tipo' => null, 'pessoa' => $pJoao, 'desc' => 'Pintura e Restauro de Paredes do Hall dos Blocos', 'val' => 1850.00, 'dia' => 25],
                        ['direcao' => 'SAIDA', 'cat' => $catTarifas, 'tipo' => null, 'pessoa' => null, 'desc' => 'Tarifas Bancárias e Manutenção de Conta', 'val' => 125.00, 'dia' => 30],
                    ],
                ],
                '2026-08-01' => [
                    'fechar' => true,
                    'movs' => [
                        ['direcao' => 'ENTRADA', 'cat' => $catTaxa, 'tipo' => $tipoCota, 'pessoa' => null, 'desc' => 'Arrecadação de Cotas Condominiais Ordinárias (Agosto)', 'val' => 18200.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catFundo, 'tipo' => null, 'pessoa' => null, 'desc' => 'Fundo de Reserva Mensal Ordinário', 'val' => 1800.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catMulta, 'tipo' => null, 'pessoa' => null, 'desc' => 'Multa por Descumprimento do Regimento Interno', 'val' => 300.00, 'dia' => 18],
                        ['direcao' => 'ENTRADA', 'cat' => $catRend, 'tipo' => null, 'pessoa' => null, 'desc' => 'Rendimento de Aplicações CDB Automático', 'val' => 255.40, 'dia' => 29],
                        ['direcao' => 'SAIDA', 'cat' => $catEnergia, 'tipo' => null, 'pessoa' => $pCpfl, 'desc' => 'Conta de Energia Elétrica - Áreas Comuns', 'val' => 2380.00, 'dia' => 15],
                        ['direcao' => 'SAIDA', 'cat' => $catAgua, 'tipo' => null, 'pessoa' => $pSanepar, 'desc' => 'Consumo de Água e Saneamento', 'val' => 1950.00, 'dia' => 16],
                        ['direcao' => 'SAIDA', 'cat' => $catLimpeza, 'tipo' => null, 'pessoa' => $pSegurmax, 'desc' => 'Contrato Mensal de Portaria e Limpeza', 'val' => 7800.00, 'dia' => 20],
                        ['direcao' => 'SAIDA', 'cat' => $catElevadores, 'tipo' => null, 'pessoa' => $pSchindler, 'desc' => 'Manutenção Preventiva de Elevadores', 'val' => 1200.00, 'dia' => 22],
                        ['direcao' => 'SAIDA', 'cat' => $catManut, 'tipo' => null, 'pessoa' => $pHidro, 'desc' => 'Limpeza de Caixas d\'Água e Desentupimento Preventivo', 'val' => 1450.00, 'dia' => 26],
                        ['direcao' => 'SAIDA', 'cat' => $catTarifas, 'tipo' => null, 'pessoa' => null, 'desc' => 'Tarifas Bancárias e Custódia', 'val' => 125.00, 'dia' => 30],
                    ],
                ],
                '2026-09-01' => [
                    'fechar' => true,
                    'movs' => [
                        ['direcao' => 'ENTRADA', 'cat' => $catTaxa, 'tipo' => $tipoCota, 'pessoa' => null, 'desc' => 'Arrecadação de Cotas Condominiais Ordinárias (Setembro)', 'val' => 18400.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catFundo, 'tipo' => null, 'pessoa' => null, 'desc' => 'Fundo de Reserva Mensal', 'val' => 1800.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catRend, 'tipo' => null, 'pessoa' => null, 'desc' => 'Rendimento de Aplicações CDB Automático', 'val' => 268.90, 'dia' => 28],
                        ['direcao' => 'SAIDA', 'cat' => $catEnergia, 'tipo' => null, 'pessoa' => $pCpfl, 'desc' => 'Conta de Energia Elétrica - Áreas Comuns', 'val' => 2510.00, 'dia' => 15],
                        ['direcao' => 'SAIDA', 'cat' => $catAgua, 'tipo' => null, 'pessoa' => $pSanepar, 'desc' => 'Consumo de Água e Saneamento', 'val' => 2020.00, 'dia' => 16],
                        ['direcao' => 'SAIDA', 'cat' => $catLimpeza, 'tipo' => null, 'pessoa' => $pSegurmax, 'desc' => 'Contrato Mensal de Portaria e Limpeza', 'val' => 7800.00, 'dia' => 20],
                        ['direcao' => 'SAIDA', 'cat' => $catElevadores, 'tipo' => null, 'pessoa' => $pSchindler, 'desc' => 'Manutenção Preventiva de Elevadores', 'val' => 1200.00, 'dia' => 22],
                        ['direcao' => 'SAIDA', 'cat' => $catManut, 'tipo' => null, 'pessoa' => null, 'desc' => 'Substituição de Lâmpadas por LED nas Garagens', 'val' => 750.00, 'dia' => 25],
                        ['direcao' => 'SAIDA', 'cat' => $catManut, 'tipo' => null, 'pessoa' => null, 'desc' => 'Serviços de Paisagismo e Poda nos Jardins', 'val' => 980.00, 'dia' => 27],
                        ['direcao' => 'SAIDA', 'cat' => $catTarifas, 'tipo' => null, 'pessoa' => null, 'desc' => 'Tarifas Bancárias', 'val' => 130.00, 'dia' => 30],
                    ],
                ],
                '2026-10-01' => [
                    'fechar' => false, // Fica ABERTO para teste do usuário!
                    'movs' => [
                        ['direcao' => 'ENTRADA', 'cat' => $catTaxa, 'tipo' => $tipoCota, 'pessoa' => null, 'desc' => 'Recebimento Parcial de Cotas Condominiais (Outubro)', 'val' => 12500.00, 'dia' => 10],
                        ['direcao' => 'ENTRADA', 'cat' => $catMudanca, 'tipo' => $tipoMudanca, 'pessoa' => null, 'desc' => 'Taxa de Mudança - Unidade 201 Bloco B', 'val' => 350.00, 'dia' => 11],
                        ['direcao' => 'ENTRADA', 'cat' => $catRend, 'tipo' => null, 'pessoa' => null, 'desc' => 'Rendimento de Aplicação CDB Automático', 'val' => 138.50, 'dia' => 15],
                        ['direcao' => 'SAIDA', 'cat' => $catEnergia, 'tipo' => null, 'pessoa' => $pCpfl, 'desc' => 'Conta de Energia Elétrica - Competência Outubro', 'val' => 2440.00, 'dia' => 15],
                        ['direcao' => 'SAIDA', 'cat' => $catAgua, 'tipo' => null, 'pessoa' => $pSanepar, 'desc' => 'Consumo de Água e Saneamento', 'val' => 1980.00, 'dia' => 16],
                        ['direcao' => 'SAIDA', 'cat' => $catLimpeza, 'tipo' => null, 'pessoa' => $pSegurmax, 'desc' => 'Contrato Mensal de Portaria e Limpeza', 'val' => 7800.00, 'dia' => 18],
                        ['direcao' => 'SAIDA', 'cat' => $catElevadores, 'tipo' => null, 'pessoa' => $pSchindler, 'desc' => 'Manutenção Preventiva Mensal Elevadores', 'val' => 1200.00, 'dia' => 20],
                        ['direcao' => 'SAIDA', 'cat' => $catManut, 'tipo' => null, 'pessoa' => null, 'desc' => 'Aquisição de Materiais de Limpeza e Higiene', 'val' => 640.00, 'dia' => 22],
                    ],
                ],
            ];

            $fechamentoService = app(FechamentoMensalService::class);

            foreach ($dadosMeses as $dataMes => $configMes) {
                $periodo = PeriodoFinanceiro::query()->firstOrCreate(
                    ['periodo' => $dataMes],
                    [
                        'situacao' => 'ABERTO',
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );

                if (MovimentacaoFinanceira::query()->where('periodo_financeiro_id', $periodo->getKey())->count() === 0) {
                    foreach ($configMes['movs'] as $m) {
                        $dataEfetiva = Carbon::parse($dataMes)->addDays($m['dia'] - 1)->toDateString();
                        MovimentacaoFinanceira::query()->create([
                            'conta_financeira_id' => $contaPrincipal->getKey(),
                            'categoria_financeira_id' => $m['cat']->getKey(),
                            'tipo_cobranca_id' => $m['tipo']?->getKey(),
                            'pessoa_id' => $m['pessoa']?->getKey(),
                            'periodo_financeiro_id' => $periodo->getKey(),
                            'direcao' => $m['direcao'],
                            'descricao' => $m['desc'],
                            'valor' => $m['val'],
                            'data_competencia' => $dataMes,
                            'data_vencimento' => $dataEfetiva,
                            'data_efetiva' => $dataEfetiva,
                            'nome_contraparte' => $m['pessoa']?->nome_completo,
                            'situacao' => 'RASCUNHO',
                            'natureza' => 'NORMAL',
                            'criado_por' => $usuario->getKey(),
                            'atualizado_por' => $usuario->getKey(),
                        ]);
                    }
                }

                if ($configMes['fechar'] && $periodo->situacao !== 'FECHADO') {
                    $fechamentoService->fechar($dataMes, $usuario);
                }
            }

            // Ambientes e Faixas de Horário
            $listaAmbientes = [
                [
                    'codigo' => 'SALAO-FESTAS',
                    'nome' => 'Salão de Festas Principal',
                    'descricao' => 'Espaço climatizado para até 80 pessoas com cozinha completa, churrasqueira integrada, mesas e cadeiras.',
                    'horas_antecedencia_minima_morador' => 24,
                    'horas_limite_cancelamento_morador' => 48,
                    'bloquear_unidades_inadimplentes' => true,
                    'aprovacao_automatica' => false,
                    'taxa' => 150.00,
                    'faixas' => [
                        ['nome' => 'Almoço (10h às 16h)', 'inicia_em' => '10:00:00', 'termina_em' => '16:00:00'],
                        ['nome' => 'Noturno (18h às 23h59)', 'inicia_em' => '18:00:00', 'termina_em' => '23:59:00'],
                    ],
                ],
                [
                    'codigo' => 'GOURMET-CHURRASQUEIRA',
                    'nome' => 'Espaço Gourmet com Churrasqueira',
                    'descricao' => 'Área gourmet ao ar livre para até 25 pessoas com churrasqueira a carvão, chopeira e geladeira.',
                    'horas_antecedencia_minima_morador' => 12,
                    'horas_limite_cancelamento_morador' => 24,
                    'bloquear_unidades_inadimplentes' => true,
                    'aprovacao_automatica' => true,
                    'taxa' => 60.00,
                    'faixas' => [
                        ['nome' => 'Diurno (11h às 16h)', 'inicia_em' => '11:00:00', 'termina_em' => '16:00:00'],
                        ['nome' => 'Noite (17h às 22h30)', 'inicia_em' => '17:00:00', 'termina_em' => '22:30:00'],
                    ],
                ],
                [
                    'codigo' => 'QUADRA-POLIESPORTIVA',
                    'nome' => 'Quadra Poliesportiva',
                    'descricao' => 'Quadra com iluminação noturna para futebol society, basquete e vôlei.',
                    'horas_antecedencia_minima_morador' => 2,
                    'horas_limite_cancelamento_morador' => 4,
                    'bloquear_unidades_inadimplentes' => false,
                    'aprovacao_automatica' => true,
                    'taxa' => 0.00,
                    'faixas' => [
                        ['nome' => 'Manhã (08h às 11h)', 'inicia_em' => '08:00:00', 'termina_em' => '11:00:00'],
                        ['nome' => 'Tarde (14h às 17h)', 'inicia_em' => '14:00:00', 'termina_em' => '17:00:00'],
                        ['nome' => 'Noite (18h às 21h)', 'inicia_em' => '18:00:00', 'termina_em' => '21:00:00'],
                    ],
                ],
            ];

            foreach ($listaAmbientes as $dadosAmbiente) {
                $amb = Ambiente::query()->updateOrCreate(
                    ['codigo' => $dadosAmbiente['codigo']],
                    [
                        'nome' => $dadosAmbiente['nome'],
                        'descricao' => $dadosAmbiente['descricao'],
                        'horas_antecedencia_minima_morador' => $dadosAmbiente['horas_antecedencia_minima_morador'],
                        'horas_limite_cancelamento_morador' => $dadosAmbiente['horas_limite_cancelamento_morador'],
                        'bloquear_unidades_inadimplentes' => $dadosAmbiente['bloquear_unidades_inadimplentes'],
                        'permitir_proprietario_nao_residente' => false,
                        'aprovacao_automatica' => $dadosAmbiente['aprovacao_automatica'],
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );

                foreach ($dadosAmbiente['faixas'] as $f) {
                    $faixa = FaixaHorarioAmbiente::query()->firstOrCreate(
                        [
                            'ambiente_id' => $amb->getKey(),
                            'nome' => $f['nome'],
                        ],
                        [
                            'inicia_em' => $f['inicia_em'],
                            'termina_em' => $f['termina_em'],
                            'inicio_validade' => '2026-01-01',
                            'fim_validade' => null,
                            'ativo' => true,
                            'criado_por' => $usuario->getKey(),
                            'atualizado_por' => $usuario->getKey(),
                        ],
                    );

                    if ($dadosAmbiente['taxa'] > 0) {
                        TaxaAmbiente::query()->firstOrCreate(
                            [
                                'ambiente_id' => $amb->getKey(),
                                'faixa_horario_id' => $faixa->getKey(),
                            ],
                            [
                                'valor' => $dadosAmbiente['taxa'],
                                'inicio_validade' => '2026-01-01',
                                'fim_validade' => null,
                                'definido_por' => $usuario->getKey(),
                                'criado_por' => $usuario->getKey(),
                                'atualizado_por' => $usuario->getKey(),
                            ],
                        );
                    }
                }
            }

            // Tipos de Ocorrência
            $tiposOcorrencia = [
                ['codigo' => 'BARULHO', 'nome' => 'Barulho e Perturbação do Sossego', 'descricao' => 'Som alto, festas fora de horário, obras sem aviso prévio.'],
                ['codigo' => 'GARAGEM', 'nome' => 'Vagas de Garagem e Veículos', 'descricao' => 'Veículos estacionados incorretamente, excesso de velocidade ou vazamento de óleo.'],
                ['codigo' => 'MANUTENCAO', 'nome' => 'Manutenção e Conservação Predial', 'descricao' => 'Lâmpadas queimadas, elevador em pane, portão eletrônico ou áreas danificadas.'],
                ['codigo' => 'ANIMAIS', 'nome' => 'Animais de Estimação e Convivência', 'descricao' => 'Uso de guia em áreas comuns, latidos excessivos ou sujeira nos halls.'],
                ['codigo' => 'HIDRAULICA', 'nome' => 'Vazamentos e Instalações Hidráulicas', 'descricao' => 'Infiltrações entre apartamentos, pressão baixa ou vazamentos no teto.'],
            ];

            foreach ($tiposOcorrencia as $tipo) {
                TipoOcorrencia::query()->updateOrCreate(
                    ['codigo' => $tipo['codigo']],
                    [
                        'nome' => $tipo['nome'],
                        'descricao' => $tipo['descricao'],
                        'ativo' => true,
                        'criado_por' => $usuario->getKey(),
                        'atualizado_por' => $usuario->getKey(),
                    ],
                );
            }

            // Comunicados
            if (Comunicado::query()->count() === 0) {
                $comunicadoService = app(ComunicadoService::class);
                $comunicadoService->criar([
                    'titulo' => 'Convocação: Assembleia Geral Ordinária (AGO) de Prestação de Contas',
                    'conteudo' => "Prezados condôminos,\n\nConvocamos todos os proprietários e moradores para nossa Assembleia Geral Ordinária que será realizada no próximo dia 25 às 19:30 no Salão de Festas Principal.\n\nPauta:\n1. Prestação de contas do exercício 2026\n2. Aprovação da previsão orçamentária para 2027\n3. Eleição de Síndico e Conselho Consultivo\n4. Assuntos gerais.\n\nSua presença é fundamental para o nosso condomínio!",
                    'tipo_publico' => 'TODOS',
                    'publicar_agora' => true,
                ], $usuario);

                $comunicadoService->criar([
                    'titulo' => 'Aviso: Manutenção Preventiva dos Elevadores - Torre Solar',
                    'conteudo' => "Informamos que na próxima quinta-feira, entre 09:00 e 12:00, os elevadores da Torre Solar (Bloco A) passarão por manutenção preventiva periódica.\n\nDurante o período, um dos elevadores permanecerá desligado temporariamente para troca de cabos e lubrificação técnica.\n\nAgradecemos a compreensão de todos.",
                    'tipo_publico' => 'BLOCOS',
                    'blocos' => $blocoA ? [$blocoA->getKey()] : [],
                    'publicar_agora' => true,
                ], $usuario);

                $comunicadoService->criar([
                    'titulo' => 'Coleta Seletiva e Descarte Correto de Entulhos e Móveis',
                    'conteudo' => "Lembramos a todos os moradores sobre a obrigatoriedade de separar materiais recicláveis nas lixeiras identificadas no subsolo.\n\nPara descarte de móveis usados, entulhos de reformas ou eletrodomésticos, solicitamos abrir chamado previamente com a administração para agendamento de caçamba.",
                    'tipo_publico' => 'TODOS',
                    'publicar_agora' => true,
                ], $usuario);
            }

            // Reservas de Teste
            $aptoA101 = Unidade::query()->where('codigo', '101')->first();
            if (Reserva::query()->count() === 0 && $aptoA101) {
                $ambienteSalao = Ambiente::query()->where('codigo', 'SALAO-FESTAS')->first();
                $faixaNoite = $ambienteSalao?->faixasHorario()->where('nome', 'LIKE', '%Noturno%')->first();
                if ($ambienteSalao && $faixaNoite) {
                    $dataFutura = Carbon::today()->addDays(5)->toDateString();
                    app(ReservaService::class)->criar([
                        'ambiente_id' => $ambienteSalao->getKey(),
                        'faixa_horario_id' => $faixaNoite->getKey(),
                        'unidade_id' => $aptoA101->getKey(),
                        'data_reserva' => $dataFutura,
                        'observacoes' => 'Comemoração em família (30 pessoas)',
                        'valor_taxa' => 150.00,
                    ], $usuario, false);
                }
            }

            // Ocorrências de Teste
            if (Ocorrencia::query()->count() === 0 && $aptoA101) {
                $tipoBarulho = TipoOcorrencia::query()->where('codigo', 'BARULHO')->first();
                if ($tipoBarulho) {
                    $ocorrenciaService = app(OcorrenciaService::class);
                    $ocorrencia = $ocorrenciaService->criar([
                        'tipo_ocorrencia_id' => $tipoBarulho->getKey(),
                        'unidade_id' => $aptoA101->getKey(),
                        'motivo' => 'Música em volume elevado após as 22h na unidade vizinha',
                        'descricao' => 'No sábado passado ocorreu som excessivo com batidas fortes até as 01:30 da madrugada, perturbando o descanso da minha família.',
                        'interna' => false,
                    ], $usuario);

                    $ocorrenciaService->comentar($ocorrencia, 'Registrado o chamado. Notificamos a portaria para realizar a ronda preventiva no bloco e emitimos advertência educativa ao morador.', $usuario, 'TODOS_PARTICIPANTES');
                    $ocorrenciaService->alterarSituacao($ocorrencia, 'EM_ANDAMENTO', $usuario, 'Em verificação com a portaria');
                }
            }
        });
    }
}
