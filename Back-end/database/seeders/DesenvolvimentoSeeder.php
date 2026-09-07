<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Pessoa;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
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
        });
    }
}
