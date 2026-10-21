<?php

namespace App\Services\Reservas;

use App\Models\Ambiente;
use App\Models\FaixaHorarioAmbiente;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class AmbienteService
{
    use RegistraAuditoria;

    public function listar(?string $busca, bool $somenteAtivos, int $porPagina): LengthAwarePaginator
    {
        return Ambiente::query()
            ->with(['faixasHorario' => fn ($q) => $q->where('ativo', true)->orderBy('inicia_em')])
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(codigo) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->when($somenteAtivos, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Ambiente
    {
        return DB::transaction(function () use ($dados, $usuario): Ambiente {
            $codigo = $dados['codigo'] ?? null;
            if (! $codigo) {
                $proximo = (int) (Ambiente::query()->max('id') ?? 0) + 1;
                $codigo = 'AMB-'.str_pad((string) $proximo, 3, '0', STR_PAD_LEFT);
            }

            $ambiente = Ambiente::query()->create([
                'codigo' => $codigo,
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'] ?? null,
                'horas_antecedencia_minima_morador' => (int) ($dados['horas_antecedencia_minima_morador'] ?? 24),
                'horas_limite_cancelamento_morador' => (int) ($dados['horas_limite_cancelamento_morador'] ?? 12),
                'bloquear_unidades_inadimplentes' => (bool) ($dados['bloquear_unidades_inadimplentes'] ?? true),
                'permitir_proprietario_nao_residente' => (bool) ($dados['permitir_proprietario_nao_residente'] ?? false),
                'aprovacao_automatica' => (bool) ($dados['aprovacao_automatica'] ?? true),
                'ativo' => true,
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            // Se faixas foram fornecidas
            $faixas = $dados['faixas'] ?? [
                ['nome' => 'Diurno', 'inicia_em' => '09:00:00', 'termina_em' => '22:00:00'],
            ];

            foreach ($faixas as $faixa) {
                FaixaHorarioAmbiente::query()->create([
                    'ambiente_id' => $ambiente->getKey(),
                    'nome' => $faixa['nome'],
                    'inicia_em' => $faixa['inicia_em'],
                    'termina_em' => $faixa['termina_em'],
                    'inicio_validade' => today(),
                    'ativo' => true,
                    'criado_por' => $usuario->getKey(),
                    'atualizado_por' => $usuario->getKey(),
                ]);
            }

            $this->auditarCriacao($usuario, 'Ambiente', $ambiente->getKey(), ['nome' => $ambiente->nome]);

            return $ambiente->load('faixasHorario');
        });
    }

    public function atualizar(Ambiente $ambiente, array $dados, Usuario $usuario): Ambiente
    {
        $antes = $ambiente->only(['nome', 'descricao', 'ativo']);

        $ambiente->fill([
            'nome' => $dados['nome'] ?? $ambiente->nome,
            'descricao' => array_key_exists('descricao', $dados) ? $dados['descricao'] : $ambiente->descricao,
            'horas_antecedencia_minima_morador' => (int) ($dados['horas_antecedencia_minima_morador'] ?? $ambiente->horas_antecedencia_minima_morador),
            'horas_limite_cancelamento_morador' => (int) ($dados['horas_limite_cancelamento_morador'] ?? $ambiente->horas_limite_cancelamento_morador),
            'bloquear_unidades_inadimplentes' => (bool) ($dados['bloquear_unidades_inadimplentes'] ?? $ambiente->bloquear_unidades_inadimplentes),
            'aprovacao_automatica' => (bool) ($dados['aprovacao_automatica'] ?? $ambiente->aprovacao_automatica),
            'atualizado_por' => $usuario->getKey(),
        ]);
        $ambiente->save();

        $this->auditarAtualizacao($usuario, 'Ambiente', $ambiente->getKey(), $antes, $ambiente->only(['nome', 'descricao']));

        return $ambiente->load('faixasHorario');
    }

    public function inativar(Ambiente $ambiente, Usuario $usuario): void
    {
        $ambiente->forceFill(['ativo' => false, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarInativacao($usuario, 'Ambiente', $ambiente->getKey());
    }

    public function reativar(Ambiente $ambiente, Usuario $usuario): void
    {
        $ambiente->forceFill(['ativo' => true, 'atualizado_por' => $usuario->getKey()])->save();
        $this->auditarReativacao($usuario, 'Ambiente', $ambiente->getKey());
    }

    public function historico(Ambiente $ambiente)
    {
        return $this->auditoriaService()->historico('Ambiente', $ambiente->getKey());
    }
}
