<?php

namespace App\Services\Comunicados;

use App\Models\Comunicado;
use App\Models\ComunicadoDestinatario;
use App\Models\Usuario;
use App\Models\VinculoUnidadePessoa;
use App\Services\Concerns\RegistraAuditoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ComunicadoService
{
    use RegistraAuditoria;

    public function listar(?string $busca, ?string $situacao, int $porPagina = 20): LengthAwarePaginator
    {
        return Comunicado::query()
            ->with(['publicadoPor.pessoa', 'blocos'])
            ->withCount(['destinatarios', 'destinatarios as lidos_count' => fn ($q) => $q->whereNotNull('lido_em')])
            ->when($situacao && $situacao !== 'TODOS', fn ($q) => $q->where('situacao', $situacao))
            ->when($busca, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(titulo) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                ->orWhereRaw('LOWER(conteudo) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->orderByDesc('criado_em')
            ->paginate($porPagina);
    }

    public function criar(array $dados, Usuario $usuario): Comunicado
    {
        return DB::transaction(function () use ($dados, $usuario): Comunicado {
            $tipoPublico = $dados['tipo_publico'] ?? 'TODOS';

            $comunicado = Comunicado::query()->create([
                'titulo' => $dados['titulo'],
                'conteudo' => $dados['conteudo'],
                'tipo_publico' => $tipoPublico,
                'situacao' => 'RASCUNHO',
                'criado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ]);

            if ($tipoPublico === 'BLOCOS' && ! empty($dados['blocos'])) {
                $blocoIds = $dados['blocos'];
                $comunicado->blocos()->sync($blocoIds);
            }

            if (! empty($dados['publicar_agora'])) {
                $this->publicar($comunicado, $usuario);
            }

            $this->auditarCriacao($usuario, 'Comunicado', $comunicado->getKey(), ['titulo' => $comunicado->titulo]);

            return $comunicado->load(['publicadoPor.pessoa', 'blocos']);
        });
    }

    public function publicar(Comunicado $comunicado, Usuario $usuario): Comunicado
    {
        return DB::transaction(function () use ($comunicado, $usuario): Comunicado {
            $comunicado->forceFill([
                'situacao' => 'PUBLICADO',
                'publicado_em' => now(),
                'publicado_por' => $usuario->getKey(),
                'atualizado_por' => $usuario->getKey(),
            ])->save();

            // Identifica as unidades e pessoas destinatárias
            $vinculosQuery = VinculoUnidadePessoa::query()
                ->with(['pessoa', 'unidade'])
                ->where('inicio_vigencia', '<=', today())
                ->where(function ($q) {
                    $q->whereNull('fim_vigencia')->orWhere('fim_vigencia', '>=', today());
                });

            if ($comunicado->tipo_publico === 'BLOCOS') {
                $blocosIds = $comunicado->blocos()->pluck('blocos.id')->all();
                $vinculosQuery->whereHas('unidade', fn ($u) => $u->whereIn('bloco_id', $blocosIds));
            }

            $vinculos = $vinculosQuery->get();

            // Agrupa por pessoa e unidade para evitar duplicatas
            $processados = [];
            foreach ($vinculos as $vinculo) {
                $rawPessoaId = $vinculo->getRawOriginal('pessoa_id');
                $rawUnidadeId = $vinculo->getRawOriginal('unidade_id');
                $chave = "{$rawPessoaId}-{$rawUnidadeId}";
                if (isset($processados[$chave])) {
                    continue;
                }
                $processados[$chave] = true;

                $usuarioDest = Usuario::query()->where('pessoa_id', $rawPessoaId)->first();

                ComunicadoDestinatario::query()->firstOrCreate(
                    [
                        'comunicado_id' => $comunicado->getKey(),
                        'pessoa_id' => $rawPessoaId,
                        'unidade_id' => $rawUnidadeId,
                    ],
                    [
                        'usuario_id' => $usuarioDest?->getKey(),
                        'email_registrado' => $vinculo->pessoa?->email ?? $usuarioDest?->email ?? 'sem-email@condominio.local',
                        'situacao_entrega' => 'ENTREGUE',
                        'enviado_em' => now(),
                        'entregue_em' => now(),
                    ],
                );
            }

            $this->auditoriaService()->registrar($usuario, 'PUBLICAR_COMUNICADO', 'Comunicado', $comunicado->getKey());

            return $comunicado->load(['publicadoPor.pessoa', 'blocos']);
        });
    }

    public function cancelar(Comunicado $comunicado, Usuario $usuario): Comunicado
    {
        $comunicado->forceFill([
            'situacao' => 'CANCELADO',
            'atualizado_por' => $usuario->getKey(),
        ])->save();

        $this->auditoriaService()->registrar($usuario, 'CANCELAR_COMUNICADO', 'Comunicado', $comunicado->getKey());

        return $comunicado->load(['publicadoPor.pessoa', 'blocos']);
    }

    public function marcarLido(Comunicado $comunicado, Usuario $usuario): void
    {
        ComunicadoDestinatario::query()
            ->where('comunicado_id', $comunicado->getKey())
            ->where(function ($q) use ($usuario) {
                $q->where('usuario_id', $usuario->getKey())
                    ->orWhere('pessoa_id', $usuario->pessoa_id);
            })
            ->whereNull('lido_em')
            ->update(['lido_em' => now()]);
    }
}
