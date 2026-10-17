<?php

namespace App\Services\Ocorrencias;

use App\Models\ComentarioOcorrencia;
use App\Models\Ocorrencia;
use App\Models\OcorrenciaUnidade;
use App\Models\TipoOcorrencia;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OcorrenciaService
{
    use RegistraAuditoria;

    public function listar(
        ?string $busca,
        ?string $situacao,
        ?int $tipoId,
        int $porPagina = 20,
    ): LengthAwarePaginator {
        return Ocorrencia::query()
            ->with([
                'tipo',
                'registradoPor.pessoa',
                'unidades.unidade.bloco',
                'comentarios.autor.pessoa',
            ])
            ->when($tipoId, fn ($q) => $q->where('tipo_ocorrencia_id', $tipoId))
            ->when($situacao && $situacao !== 'TODOS', fn ($q) => $q->where('situacao', $situacao))
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->where('numero_protocolo', 'LIKE', "%{$busca}%")
                ->orWhereRaw('LOWER(motivo) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(descricao) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereHas('registradoPor.pessoa', fn ($p) => $p->whereRaw('LOWER(nome_completo) LIKE ?', ['%'.mb_strtolower($busca).'%']))))
            ->orderByDesc('aberto_em')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Ocorrencia
    {
        return DB::transaction(function () use ($dados, $usuario): Ocorrencia {
            $proximoProtocolo = (int) (Ocorrencia::query()->max('numero_protocolo') ?? 2026000) + 1;
            $tipo = TipoOcorrencia::query()->findOrFail($dados['tipo_ocorrencia_id']);

            $ocorrencia = Ocorrencia::query()->create([
                'numero_protocolo' => $proximoProtocolo,
                'tipo_ocorrencia_id' => $tipo->getKey(),
                'registrado_por' => $usuario->getKey(),
                'motivo' => $dados['motivo'],
                'descricao' => $dados['descricao'],
                'interna' => (bool) ($dados['interna'] ?? false),
                'situacao' => 'ABERTO',
                'aberto_em' => now(),
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            // Vincula a unidade solicitante se fornecida
            if (! empty($dados['unidade_id'])) {
                $unidade = Unidade::query()->findOrFail($dados['unidade_id']);
                OcorrenciaUnidade::query()->create([
                    'ocorrencia_id' => $ocorrencia->getKey(),
                    'unidade_id' => $unidade->getKey(),
                    'tipo_relacao' => 'SOLICITANTE',
                    'visivel_para_unidade' => true,
                    'vinculado_por' => $usuario->getKey(),
                    'vinculado_em' => now(),
                ]);
            }

            DB::table('historico_situacao_ocorrencia')->insert([
                'ocorrencia_id' => $ocorrencia->getKey(),
                'situacao_anterior' => null,
                'nova_situacao' => 'ABERTO',
                'alterado_por' => $usuario->getKey(),
                'alterado_em' => now(),
            ]);

            $this->auditarCriacao($usuario, 'Ocorrencia', $ocorrencia->getKey(), [
                'protocolo' => $ocorrencia->numero_protocolo,
                'motivo' => $ocorrencia->motivo,
            ]);

            return $ocorrencia->load(['tipo', 'registradoPor.pessoa', 'unidades.unidade.bloco', 'comentarios.autor.pessoa']);
        });
    }

    public function comentar(
        Ocorrencia $ocorrencia,
        string $conteudo,
        Usuario $usuario,
        string $visibilidade = 'TODOS_PARTICIPANTES',
    ): ComentarioOcorrencia {
        return DB::transaction(function () use ($ocorrencia, $conteudo, $usuario, $visibilidade): ComentarioOcorrencia {
            $comentario = ComentarioOcorrencia::query()->create([
                'ocorrencia_id' => $ocorrencia->getKey(),
                'autor_id' => $usuario->getKey(),
                'conteudo' => $conteudo,
                'visibilidade' => $visibilidade,
                'criado_em' => now(),
            ]);

            // Se a ocorrência ainda estava em ABERTO e foi a administração que respondeu, passa para EM_ANDAMENTO
            if ($ocorrencia->situacao === 'ABERTO') {
                $this->alterarSituacao($ocorrencia, 'EM_ANDAMENTO', $usuario, 'Resposta da administração.');
            }

            return $comentario->load('autor.pessoa');
        });
    }

    public function alterarSituacao(
        Ocorrencia $ocorrencia,
        string $novaSituacao,
        Usuario $usuario,
        ?string $motivo = null,
    ): Ocorrencia {
        return DB::transaction(function () use ($ocorrencia, $novaSituacao, $usuario, $motivo): Ocorrencia {
            $situacaoAnterior = $ocorrencia->situacao;
            $updates = [
                'situacao' => $novaSituacao,
                'atualizado_por' => $usuario->getKey(),
            ];

            if ($novaSituacao === 'RESOLVIDO') {
                $updates['resolvido_em'] = now();
            } elseif ($novaSituacao === 'FECHADO') {
                $updates['fechado_em'] = now();
            }

            $ocorrencia->forceFill($updates)->save();

            $comentarioId = null;
            if ($motivo) {
                $comentario = ComentarioOcorrencia::query()->create([
                    'ocorrencia_id' => $ocorrencia->getKey(),
                    'autor_id' => $usuario->getKey(),
                    'conteudo' => "[Situação alterada para {$novaSituacao}]: {$motivo}",
                    'visibilidade' => 'TODOS_PARTICIPANTES',
                    'criado_em' => now(),
                ]);
                $comentarioId = $comentario->getKey();
            }

            DB::table('historico_situacao_ocorrencia')->insert([
                'ocorrencia_id' => $ocorrencia->getKey(),
                'situacao_anterior' => $situacaoAnterior,
                'nova_situacao' => $novaSituacao,
                'comentario_ocorrencia_id' => $comentarioId,
                'alterado_por' => $usuario->getKey(),
                'alterado_em' => now(),
            ]);

            $this->auditoriaService()->registrar($usuario, 'ALTERAR_SITUACAO_OCORRENCIA', 'Ocorrencia', $ocorrencia->getKey(), [
                'de' => $situacaoAnterior,
                'para' => $novaSituacao,
            ]);

            return $ocorrencia->load(['tipo', 'registradoPor.pessoa', 'unidades.unidade.bloco', 'comentarios.autor.pessoa']);
        });
    }

    public function historico(Ocorrencia $ocorrencia)
    {
        return DB::table('historico_situacao_ocorrencia')
            ->join('usuarios', 'usuarios.id', '=', 'historico_situacao_ocorrencia.alterado_por')
            ->leftJoin('pessoas', 'pessoas.id', '=', 'usuarios.pessoa_id')
            ->where('ocorrencia_id', $ocorrencia->getKey())
            ->orderByDesc('alterado_em')
            ->select('historico_situacao_ocorrencia.*', 'pessoas.nome_completo as autor_nome', 'usuarios.email as autor_email')
            ->get();
    }
}
