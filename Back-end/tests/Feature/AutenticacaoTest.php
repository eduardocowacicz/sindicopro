<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_usuario_local_entra_e_recebe_acesso_integral(): void
    {
        $this->postJson('/login', [
            'email' => 'sindico@pro.com',
            'senha' => '123',
        ])->assertOk()->assertJsonPath('dados.autenticado', true);

        $resposta = $this->getJson('/api/sessao');

        $resposta
            ->assertOk()
            ->assertJsonPath('dados.usuario.email', 'sindico@pro.com')
            ->assertJsonPath('dados.pessoa.nome_completo', 'Síndico de Desenvolvimento')
            ->assertJsonPath('dados.acesso_integral', true)
            ->assertJsonFragment(['codigo' => 'SINDICO'])
            ->assertJsonPath(
                'dados.permissoes',
                fn (array $permissoes): bool => in_array('fechamentos.mensais.reabrir', $permissoes, true),
            );
    }

    public function test_login_nao_revela_qual_credencial_falhou(): void
    {
        $this->postJson('/login', [
            'email' => 'sindico@pro.com',
            'senha' => 'incorreta',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_sessao_exige_autenticacao(): void
    {
        $this->getJson('/api/sessao')->assertUnauthorized();
    }

    public function test_logout_invalida_a_sessao(): void
    {
        $this->postJson('/login', [
            'email' => 'sindico@pro.com',
            'senha' => '123',
        ])->assertOk();

        $this->postJson('/logout')->assertNoContent();
        $this->getJson('/api/sessao')->assertUnauthorized();
    }

    public function test_seeder_de_desenvolvimento_e_idempotente(): void
    {
        $this->seed();

        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('pessoas', 1);
        $this->assertDatabaseHas('usuarios', [
            'email' => 'sindico@pro.com',
            'ativo' => true,
        ]);
    }

    public function test_perfil_sindico_e_liberado_em_qualquer_policy(): void
    {
        $usuario = Usuario::query()->where('email', 'sindico@pro.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($usuario)->allows('funcao-futura-do-sistema'));
    }
}
