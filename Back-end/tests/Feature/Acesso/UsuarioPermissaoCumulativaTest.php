<?php

namespace Tests\Feature\Acesso;

use App\Models\Perfil;
use App\Models\Pessoa;
use App\Models\Usuario;
use App\Services\Autorizacao\PermissaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UsuarioPermissaoCumulativaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_morador_recebe_perfil_morador_automaticamente_ao_ser_criado(): void
    {
        $sindico = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $pessoaMorador = Pessoa::query()->where('tipo', 'MORADOR')->where('email', '!=', 'sindico@pro.com')->firstOrFail();

        $resposta = $this->actingAs($sindico)->postJson('/api/usuarios', [
            'pessoa_id' => $pessoaMorador->id_publico,
            'email' => 'morador.teste@condominio.com',
            'senha' => 'senhaSegura123',
        ]);

        $resposta->assertCreated();
        $usuarioCriado = Usuario::query()->where('email', 'morador.teste@condominio.com')->firstOrFail();

        $this->assertTrue($usuarioCriado->perfisVigentes->contains('codigo', 'MORADOR'));
        $this->assertFalse($usuarioCriado->perfisVigentes->contains('codigo', 'SINDICO'));

        $permissaoService = app(PermissaoService::class);
        $this->assertFalse($permissaoService->possuiAcessoIntegral($usuarioCriado));
    }

    public function test_morador_acumula_perfil_de_sindico_e_recebe_acesso_integral(): void
    {
        $sindico = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $pessoaMorador = Pessoa::query()->where('tipo', 'MORADOR')->where('email', '!=', 'sindico@pro.com')->firstOrFail();
        $perfilSindico = Perfil::query()->where('codigo', 'SINDICO')->firstOrFail();

        $respostaCriacao = $this->actingAs($sindico)->postJson('/api/usuarios', [
            'pessoa_id' => $pessoaMorador->id_publico,
            'email' => 'mariana.sindica@condominio.com',
            'senha' => 'senhaSegura123',
            'perfis' => [$perfilSindico->id_publico],
        ]);

        $respostaCriacao->assertCreated();
        $usuario = Usuario::query()->where('email', 'mariana.sindica@condominio.com')->firstOrFail();

        // O usuário possui AMBOS os perfis cumulativamente: MORADOR e SINDICO
        $perfisCodigos = $usuario->perfisVigentes->pluck('codigo')->all();
        $this->assertContains('MORADOR', $perfisCodigos);
        $this->assertContains('SINDICO', $perfisCodigos);

        $permissaoService = app(PermissaoService::class);
        $this->assertTrue($permissaoService->possuiAcessoIntegral($usuario));
    }

    public function test_revogacao_de_sindico_mantem_perfil_morador_e_remove_acesso_integral(): void
    {
        $sindico = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();
        $pessoaMorador = Pessoa::query()->where('tipo', 'MORADOR')->where('email', '!=', 'sindico@pro.com')->firstOrFail();
        $perfilSindico = Perfil::query()->where('codigo', 'SINDICO')->firstOrFail();
        $perfilMorador = Perfil::query()->where('codigo', 'MORADOR')->firstOrFail();

        // Cria usuário já com Síndico e Morador
        $this->actingAs($sindico)->postJson('/api/usuarios', [
            'pessoa_id' => $pessoaMorador->id_publico,
            'email' => 'carlos.mandato@condominio.com',
            'senha' => 'senhaSegura123',
            'perfis' => [$perfilSindico->id_publico, $perfilMorador->id_publico],
        ])->assertCreated();

        $usuario = Usuario::query()->where('email', 'carlos.mandato@condominio.com')->firstOrFail();
        $this->assertTrue(app(PermissaoService::class)->possuiAcessoIntegral($usuario));

        // Revoga Síndico: atualiza passando apenas perfil Morador (ou lista sem Síndico)
        $respostaUpdate = $this->actingAs($sindico)->putJson("/api/usuarios/{$usuario->id_publico}", [
            'email' => 'carlos.mandato@condominio.com',
            'perfis' => [$perfilMorador->id_publico],
        ]);

        $respostaUpdate->assertOk();
        $usuario->refresh();

        // Deve manter MORADOR e ter removido SINDICO
        $perfisVigentes = $usuario->perfisVigentes->pluck('codigo')->all();
        $this->assertContains('MORADOR', $perfisVigentes);
        $this->assertNotContains('SINDICO', $perfisVigentes);

        // Não possui mais acesso integral
        $this->assertFalse(app(PermissaoService::class)->possuiAcessoIntegral($usuario));
    }
}
