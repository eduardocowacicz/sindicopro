<?php

namespace App\Services\Acesso;

use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UsuarioService
{
    use RegistraAuditoria;

    public function listar(?string $busca, bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Usuario::query()
            ->with('pessoa')
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereHas('pessoa', fn ($p) => $p->whereRaw('LOWER(nome_completo) LIKE ?', ['%'.mb_strtolower($busca).'%']))))
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('email')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuarioLogado): Usuario
    {
        $usuario = Usuario::query()->create([
            'pessoa_id' => $dados['pessoa_id'],
            'email' => $dados['email'],
            'senha' => Hash::make($dados['senha'] ?? Str::random(16)),
            'deve_alterar_senha' => true,
            'ativo' => true,
            'criado_por' => $usuarioLogado->getKey(),
            'atualizado_por' => $usuarioLogado->getKey(),
        ]);

        $this->sincronizarPerfis($usuario, $dados['perfis'] ?? [], $usuarioLogado);
        $this->auditarCriacao($usuarioLogado, 'Usuario', $usuario->getKey(), ['email' => $dados['email']]);

        return $usuario->load('pessoa');
    }

    public function atualizar(Usuario $usuario, array $dados, Usuario $usuarioLogado): Usuario
    {
        $antes = $usuario->only(['email']);
        $usuario->fill([
            'email' => $dados['email'],
            'atualizado_por' => $usuarioLogado->getKey(),
        ]);
        if (filled($dados['senha'] ?? null)) {
            $usuario->senha = Hash::make($dados['senha']);
            $usuario->deve_alterar_senha = true;
        }
        $usuario->save();

        if (array_key_exists('perfis', $dados)) {
            $this->sincronizarPerfis($usuario, $dados['perfis'], $usuarioLogado);
        }

        $this->auditarAtualizacao($usuarioLogado, 'Usuario', $usuario->getKey(), $antes, ['email' => $dados['email']]);

        return $usuario->load('pessoa');
    }

    public function inativar(Usuario $usuario, Usuario $usuarioLogado, ?string $motivo): void
    {
        $usuario->forceFill([
            'ativo' => false,
            'atualizado_por' => $usuarioLogado->getKey(),
        ])->save();

        $this->auditarInativacao($usuarioLogado, 'Usuario', $usuario->getKey(), $motivo);
    }

    public function reativar(Usuario $usuario, Usuario $usuarioLogado): void
    {
        $usuario->forceFill([
            'ativo' => true,
            'atualizado_por' => $usuarioLogado->getKey(),
        ])->save();

        $this->auditarReativacao($usuarioLogado, 'Usuario', $usuario->getKey());
    }

    public function bloquear(Usuario $usuario, Usuario $usuarioLogado, ?string $motivo): void
    {
        $usuario->forceFill([
            'bloqueado_em' => now(),
            'motivo_bloqueio' => $motivo,
            'atualizado_por' => $usuarioLogado->getKey(),
        ])->save();

        $this->auditoriaService()->registrar($usuarioLogado, 'BLOQUEIO', 'Usuario', $usuario->getKey(), null, null, $motivo);
    }

    public function desbloquear(Usuario $usuario, Usuario $usuarioLogado): void
    {
        $usuario->forceFill([
            'bloqueado_em' => null,
            'motivo_bloqueio' => null,
            'atualizado_por' => $usuarioLogado->getKey(),
        ])->save();

        $this->auditoriaService()->registrar($usuarioLogado, 'DESBLOQUEIO', 'Usuario', $usuario->getKey());
    }

    public function redefinirSenha(Usuario $usuario, Usuario $usuarioLogado): string
    {
        $senhaTemporaria = Str::password(12, symbols: false);
        $usuario->forceFill([
            'senha' => Hash::make($senhaTemporaria),
            'deve_alterar_senha' => true,
            'atualizado_por' => $usuarioLogado->getKey(),
        ])->save();

        $this->auditoriaService()->registrar($usuarioLogado, 'REDEFINICAO_SENHA', 'Usuario', $usuario->getKey());

        return $senhaTemporaria;
    }

    public function historico(Usuario $usuario)
    {
        return $this->auditoriaService()->historico('Usuario', $usuario->getKey());
    }

    private function sincronizarPerfis(Usuario $usuario, array $perfilIds, Usuario $usuarioLogado): void
    {
        DB::table('usuario_perfis')
            ->where('usuario_id', $usuario->getKey())
            ->whereNull('termina_em')
            ->whereNotIn('perfil_id', $perfilIds)
            ->update(['termina_em' => now()]);

        $existentes = DB::table('usuario_perfis')
            ->where('usuario_id', $usuario->getKey())
            ->whereNull('termina_em')
            ->pluck('perfil_id')
            ->all();

        foreach (array_diff($perfilIds, $existentes) as $perfilId) {
            DB::table('usuario_perfis')->insert([
                'usuario_id' => $usuario->getKey(),
                'perfil_id' => $perfilId,
                'inicia_em' => now(),
                'atribuido_por' => $usuarioLogado->getKey(),
                'criado_em' => now(),
            ]);
        }
    }
}
