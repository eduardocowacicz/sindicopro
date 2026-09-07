<?php

namespace App\Services\Autenticacao;

use App\Models\Usuario;
use App\Repositories\Autenticacao\AutenticacaoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AutenticacaoService
{
    public function __construct(
        private readonly AutenticacaoRepository $repository,
    ) {}

    public function entrar(string $email, string $senha, Request $request): void
    {
        $usuario = $this->repository->buscarPorEmail($email);
        $senhaValida = Hash::check(
            $senha,
            $usuario?->senha ?? Hash::make('credencial-inexistente'),
        );
        $credencialValida = $usuario instanceof Usuario
            && $usuario->ativo
            && $usuario->bloqueado_em === null
            && $senhaValida
            && $this->repository->perfisVigentes($usuario)->isNotEmpty();

        if (! $credencialValida) {
            $this->repository->registrarAcesso(
                $usuario,
                $email,
                $usuario?->bloqueado_em !== null ? 'CONTA_BLOQUEADA' : 'LOGIN_FALHOU',
                $request->ip(),
                $request->userAgent(),
            );

            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        Auth::guard('web')->login($usuario);
        $request->session()->regenerate();

        $this->repository->registrarUltimoAcesso($usuario);
        $this->repository->registrarAcesso(
            $usuario,
            $email,
            'LOGIN_SUCESSO',
            $request->ip(),
            $request->userAgent(),
            $request->session()->getId(),
        );
    }

    public function sessao(Usuario $usuario): array
    {
        $usuario->loadMissing('pessoa');
        $perfis = $this->repository->perfisVigentes($usuario);

        return [
            'usuario' => [
                'id_publico' => $usuario->id_publico,
                'email' => $usuario->email,
                'deve_alterar_senha' => $usuario->deve_alterar_senha,
            ],
            'pessoa' => [
                'id_publico' => $usuario->pessoa->id_publico,
                'nome_completo' => $usuario->pessoa->nome_completo,
            ],
            'perfis' => $perfis->map(fn (object $perfil): array => [
                'codigo' => $perfil->codigo,
                'nome' => $perfil->nome,
            ])->values()->all(),
            'permissoes' => $this->repository->permissoesEfetivas($usuario, $perfis)->all(),
            'acesso_integral' => $perfis->contains('codigo', 'SINDICO'),
            'unidades' => $this->repository->unidadesVigentes($usuario)->map(fn (object $unidade): array => [
                'id_publico' => $unidade->id_publico,
                'codigo' => $unidade->codigo,
                'bloco' => $unidade->bloco,
                'tipo_vinculo' => $unidade->tipo_vinculo,
            ])->all(),
        ];
    }

    public function sair(Usuario $usuario, Request $request): void
    {
        $this->repository->registrarAcesso(
            $usuario,
            $usuario->email,
            'SAIDA_SISTEMA',
            $request->ip(),
            $request->userAgent(),
            $request->session()->getId(),
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
