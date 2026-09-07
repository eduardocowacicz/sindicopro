<?php

namespace App\Services\Autorizacao;

use App\Models\Usuario;
use App\Repositories\Autenticacao\AutenticacaoRepository;

final class PermissaoService
{
    public function __construct(
        private readonly AutenticacaoRepository $repository,
    ) {}

    public function possuiAcessoIntegral(Usuario $usuario): bool
    {
        return $this->repository
            ->perfisVigentes($usuario)
            ->contains('codigo', 'SINDICO');
    }

    public function permite(Usuario $usuario, string $codigo): bool
    {
        $perfis = $this->repository->perfisVigentes($usuario);

        return $perfis->contains('codigo', 'SINDICO')
            || $this->repository->permissoesEfetivas($usuario, $perfis)->contains($codigo);
    }
}
