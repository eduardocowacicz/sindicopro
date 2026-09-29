<?php

namespace App\Services\Cadastros;

use App\Models\Pessoa;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

final class PessoaService
{
    use RegistraAuditoria;

    public function listar(?string $busca, bool $somenteAtivas, int $porPagina): LengthAwarePaginator
    {
        return Pessoa::query()
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(nome_completo) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->when($somenteAtivas, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome_completo')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Pessoa
    {
        $pessoa = Pessoa::query()->create([
            ...$this->prepararDados($dados),
            'criado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ]);

        $this->auditarCriacao($usuario, 'Pessoa', $pessoa->getKey(), ['nome_completo' => $dados['nome_completo']]);

        return $pessoa;
    }

    public function atualizar(Pessoa $pessoa, array $dados, Usuario $usuario): Pessoa
    {
        $antes = $pessoa->only(['nome_completo', 'email', 'telefone']);
        $pessoa->fill([...$this->prepararDados($dados), 'atualizado_por' => $usuario->getKey()]);
        $pessoa->save();

        $this->auditarAtualizacao($usuario, 'Pessoa', $pessoa->getKey(), $antes, [
            'nome_completo' => $dados['nome_completo'],
            'email' => $dados['email'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
        ]);

        return $pessoa;
    }

    public function inativar(Pessoa $pessoa, Usuario $usuario, ?string $motivo): void
    {
        if ($pessoa->vinculos()->whereNull('fim_vigencia')->exists()) {
            throw ValidationException::withMessages([
                'pessoa' => ['Não é possível inativar uma pessoa com vínculos vigentes com unidades.'],
            ]);
        }

        $pessoa->forceFill([
            'ativo' => false,
            'inativado_em' => now(),
            'inativado_por' => $usuario->getKey(),
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarInativacao($usuario, 'Pessoa', $pessoa->getKey(), $motivo);
    }

    public function reativar(Pessoa $pessoa, Usuario $usuario): void
    {
        $pessoa->forceFill([
            'ativo' => true,
            'inativado_em' => null,
            'inativado_por' => null,
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditarReativacao($usuario, 'Pessoa', $pessoa->getKey());
    }

    public function historico(Pessoa $pessoa)
    {
        return $this->auditoriaService()->historico('Pessoa', $pessoa->getKey());
    }

    private function prepararDados(array $dados): array
    {
        $preparados = [
            'nome_completo' => $dados['nome_completo'],
            'email' => $dados['email'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
        ];

        if (array_key_exists('cpf', $dados) && filled($dados['cpf'])) {
            $cpf = preg_replace('/\D/', '', (string) $dados['cpf']);
            $preparados['cpf_criptografado'] = Crypt::encryptString($cpf);
            $preparados['hash_cpf'] = hash('sha256', $cpf);
        }

        return $preparados;
    }
}
