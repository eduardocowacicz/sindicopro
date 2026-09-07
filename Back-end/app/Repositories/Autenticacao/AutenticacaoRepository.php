<?php

namespace App\Repositories\Autenticacao;

use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AutenticacaoRepository
{
    public function buscarPorEmail(string $email): ?Usuario
    {
        return Usuario::query()
            ->with('pessoa')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    public function perfisVigentes(Usuario $usuario): Collection
    {
        return DB::table('perfis')
            ->join('usuario_perfis', 'usuario_perfis.perfil_id', '=', 'perfis.id')
            ->where('usuario_perfis.usuario_id', $usuario->getKey())
            ->where('perfis.ativo', true)
            ->where('usuario_perfis.inicia_em', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('usuario_perfis.termina_em')
                    ->orWhere('usuario_perfis.termina_em', '>', now());
            })
            ->orderBy('perfis.nome')
            ->get(['perfis.id', 'perfis.codigo', 'perfis.nome']);
    }

    public function permissoesEfetivas(Usuario $usuario, Collection $perfis): Collection
    {
        if ($perfis->contains('codigo', 'SINDICO')) {
            return DB::table('permissoes')->orderBy('codigo')->pluck('codigo');
        }

        $codigos = DB::table('permissoes')
            ->join('perfil_permissoes', 'perfil_permissoes.permissao_id', '=', 'permissoes.id')
            ->whereIn('perfil_permissoes.perfil_id', $perfis->pluck('id'))
            ->pluck('permissoes.codigo')
            ->keyBy(fn (string $codigo): string => $codigo);

        $regras = DB::table('usuario_permissoes')
            ->join('permissoes', 'permissoes.id', '=', 'usuario_permissoes.permissao_id')
            ->where('usuario_permissoes.usuario_id', $usuario->getKey())
            ->where('usuario_permissoes.inicia_em', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('usuario_permissoes.termina_em')
                    ->orWhere('usuario_permissoes.termina_em', '>', now());
            })
            ->get(['permissoes.codigo', 'usuario_permissoes.efeito']);

        foreach ($regras->where('efeito', 'PERMITIR') as $regra) {
            $codigos->put($regra->codigo, $regra->codigo);
        }
        foreach ($regras->where('efeito', 'NEGAR') as $regra) {
            $codigos->forget($regra->codigo);
        }

        return $codigos->values()->sort()->values();
    }

    public function unidadesVigentes(Usuario $usuario): Collection
    {
        return DB::table('vinculos_unidade_pessoa')
            ->join('unidades', 'unidades.id', '=', 'vinculos_unidade_pessoa.unidade_id')
            ->join('blocos', 'blocos.id', '=', 'unidades.bloco_id')
            ->where('vinculos_unidade_pessoa.pessoa_id', $usuario->pessoa_id)
            ->where('vinculos_unidade_pessoa.inicio_vigencia', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('vinculos_unidade_pessoa.fim_vigencia')
                    ->orWhere('vinculos_unidade_pessoa.fim_vigencia', '>=', today());
            })
            ->where('unidades.ativo', true)
            ->orderBy('blocos.nome')
            ->orderBy('unidades.codigo')
            ->get([
                'unidades.id_publico',
                'unidades.codigo',
                'blocos.nome as bloco',
                'vinculos_unidade_pessoa.tipo_vinculo',
            ]);
    }

    public function registrarAcesso(
        ?Usuario $usuario,
        string $email,
        string $tipoEvento,
        ?string $enderecoIp,
        ?string $agenteUsuario,
        ?string $sessaoId = null,
    ): void {
        DB::table('logs_acesso')->insert([
            'usuario_id' => $usuario?->getKey(),
            'hash_email_tentado' => hash_hmac('sha256', $email, (string) config('app.key')),
            'tipo_evento' => $tipoEvento,
            'endereco_ip' => $enderecoIp,
            'agente_usuario' => mb_substr((string) $agenteUsuario, 0, 500),
            'sessao_id' => $sessaoId,
            'ocorrido_em' => now(),
        ]);
    }

    public function registrarUltimoAcesso(Usuario $usuario): void
    {
        $usuario->forceFill(['ultimo_acesso_em' => now()])->save();
    }
}
