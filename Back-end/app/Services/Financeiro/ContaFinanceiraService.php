<?php

namespace App\Services\Financeiro;

use App\Models\ContaFinanceira;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

final class ContaFinanceiraService
{
    use RegistraAuditoria;

    public function listar(bool $somenteAtivas, int $porPagina): LengthAwarePaginator
    {
        return ContaFinanceira::query()
            ->when($somenteAtivas, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): ContaFinanceira
    {
        $conta = ContaFinanceira::query()->create([
            ...$this->prepararDados($dados),
            'ativo' => true,
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'ContaFinanceira', $conta->getKey(), ['nome' => $dados['nome']]);

        return $conta;
    }

    public function atualizar(ContaFinanceira $conta, array $dados, Usuario $usuario): ContaFinanceira
    {
        $antes = $conta->only(['nome', 'tipo_conta']);
        $conta->fill([...$this->prepararDados($dados), 'atualizado_por' => $usuario->getKey()]);
        $conta->save();

        $this->auditarAtualizacao($usuario, 'ContaFinanceira', $conta->getKey(), $antes, ['nome' => $dados['nome'], 'tipo_conta' => $dados['tipo_conta']]);

        return $conta;
    }

    public function inativar(ContaFinanceira $conta, Usuario $usuario, ?string $motivo): void
    {
        $conta->forceFill(['ativo' => false, 'inativado_em' => now(), 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'ContaFinanceira', $conta->getKey(), $motivo);
    }

    public function reativar(ContaFinanceira $conta, Usuario $usuario): void
    {
        $conta->forceFill(['ativo' => true, 'inativado_em' => null, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarReativacao($usuario, 'ContaFinanceira', $conta->getKey());
    }

    public function historico(ContaFinanceira $conta)
    {
        return $this->auditoriaService()->historico('ContaFinanceira', $conta->getKey());
    }

    private function prepararDados(array $dados): array
    {
        $preparados = [
            'nome' => $dados['nome'],
            'tipo_conta' => $dados['tipo_conta'],
            'codigo_banco' => $dados['codigo_banco'] ?? null,
            'nome_banco' => $dados['nome_banco'] ?? null,
            'saldo_inicial' => $dados['saldo_inicial'],
            'data_saldo_inicial' => $dados['data_saldo_inicial'],
        ];

        if (filled($dados['agencia'] ?? null)) {
            $preparados['agencia_criptografada'] = Crypt::encryptString($dados['agencia']);
        }
        if (filled($dados['numero_conta'] ?? null)) {
            $numero = (string) $dados['numero_conta'];
            $preparados['numero_conta_criptografado'] = Crypt::encryptString($numero);
            $preparados['ultimos4_conta'] = mb_substr($numero, -4);
        }

        return $preparados;
    }
}
