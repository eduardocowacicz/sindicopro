<?php

namespace App\Services\Morador;

use App\Models\Ambiente;
use App\Models\Cobranca;
use App\Models\Comunicado;
use App\Models\ComunicadoDestinatario;
use App\Models\FaixaHorarioAmbiente;
use App\Models\FechamentoFinanceiro;
use App\Models\Ocorrencia;
use App\Models\Reserva;
use App\Models\Unidade;
use App\Models\Usuario;
use App\Models\VinculoUnidadePessoa;
use App\Services\Comunicados\ComunicadoService;
use App\Services\Ocorrencias\OcorrenciaService;
use App\Services\Reservas\ReservaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class MoradorAreaService
{
    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly ComunicadoService $comunicadoService,
        private readonly OcorrenciaService $ocorrenciaService,
    ) {}

    public function unidadesDoUsuario(Usuario $usuario): Collection
    {
        return VinculoUnidadePessoa::query()
            ->with(['unidade.bloco'])
            ->where('pessoa_id', $usuario->pessoa_id)
            ->where('inicio_vigencia', '<=', today())
            ->where(function ($q) {
                $q->whereNull('fim_vigencia')->orWhere('fim_vigencia', '>=', today());
            })
            ->get()
            ->pluck('unidade')
            ->filter();
    }

    public function dashboard(Usuario $usuario): array
    {
        $unidades = $this->unidadesDoUsuario($usuario);
        $unidadesIds = $unidades->pluck('id')->all();

        // 1. Cobranças pendentes
        $cobrancasPendentes = Cobranca::query()
            ->whereIn('unidade_id', $unidadesIds)
            ->where('situacao', 'ABERTA')
            ->orderBy('data_vencimento')
            ->get();

        // 2. Próximas reservas ativas
        $reservasFuturas = Reserva::query()
            ->with(['ambiente', 'faixaHorario', 'unidade.bloco'])
            ->where(function ($q) use ($usuario, $unidadesIds) {
                $q->where('solicitado_por', $usuario->getKey())
                    ->orWhereIn('unidade_id', $unidadesIds);
            })
            ->where('data_reserva', '>=', today())
            ->where('situacao', 'CONFIRMADO')
            ->orderBy('data_reserva')
            ->get();

        // 3. Comunicados recentes não lidos
        $comunicadosNaoLidos = ComunicadoDestinatario::query()
            ->with(['comunicado'])
            ->where(fn ($q) => $this->filtrarDestinatario($q, $usuario))
            ->whereNull('lido_em')
            ->whereHas('comunicado', fn ($c) => $c->where('situacao', 'PUBLICADO'))
            ->orderByDesc('criado_em')
            ->get()
            ->unique('comunicado_id')
            ->take(5)
            ->map(fn ($dest) => $dest->comunicado);

        // 4. Ocorrências em andamento
        $ocorrenciasAbertas = Ocorrencia::query()
            ->with(['tipo'])
            ->where('registrado_por', $usuario->getKey())
            ->whereIn('situacao', ['ABERTO', 'EM_ANDAMENTO'])
            ->orderByDesc('aberto_em')
            ->get();

        return [
            'unidades' => $unidades->values(),
            'total_cobrancas_pendentes' => $cobrancasPendentes->count(),
            'valor_total_pendente' => (float) $cobrancasPendentes->sum('valor_total'),
            'proximas_reservas' => $reservasFuturas->values(),
            'comunicados_novos' => $comunicadosNaoLidos->values(),
            'ocorrencias_em_aberto' => $ocorrenciasAbertas->values(),
        ];
    }

    public function minhaUnidade(Usuario $usuario, ?int $unidadeId = null): array
    {
        $unidades = $this->unidadesDoUsuario($usuario);
        if ($unidades->isEmpty()) {
            return ['unidade' => null, 'moradores' => [], 'veiculos' => []];
        }

        $unidade = $unidadeId
            ? $unidades->firstWhere('id', $unidadeId) ?? $unidades->first()
            : $unidades->first();

        $unidadeCarregada = Unidade::query()
            ->with([
                'bloco',
                'vinculos.pessoa',
                'veiculos',
            ])
            ->findOrFail($unidade->id);

        return [
            'unidade' => $unidadeCarregada,
            'bloco' => $unidadeCarregada->bloco,
            'moradores' => $unidadeCarregada->vinculos->map(fn ($v) => [
                'id_publico' => $v->pessoa->id_publico,
                'nome' => $v->pessoa->nome_completo,
                'tipo_vinculo' => $v->tipo_vinculo,
                'responsavel_financeiro' => $v->responsavel_financeiro,
                'telefone' => $v->pessoa->telefone,
                'email' => $v->pessoa->email,
            ]),
            'veiculos' => $unidadeCarregada->veiculos,
        ];
    }

    public function minhasCobrancas(Usuario $usuario): Collection
    {
        $unidadesIds = $this->unidadesDoUsuario($usuario)->pluck('id')->all();

        return Cobranca::query()
            ->with(['itens', 'unidade.bloco'])
            ->whereIn('unidade_id', $unidadesIds)
            ->orderByDesc('data_vencimento')
            ->get()
            ->map(function (Cobranca $c) {
                $c->codigo_barras = $this->linhaDigitavelSimulada($c);
                $c->pix_copia_e_cola = $this->pixCopiaEColaSimulado($c);

                return $c;
            });
    }

    public function fechamentosPublicados(): Collection
    {
        return FechamentoFinanceiro::query()
            ->with(['periodoFinanceiro', 'publicadoPor.pessoa'])
            ->where('situacao', 'PUBLICADO')
            ->orderByDesc('criado_em')
            ->get();
    }

    public function ambientesDisponiveis(): Collection
    {
        return Ambiente::query()
            ->with(['faixasHorario' => fn ($q) => $q->where('ativo', true)->orderBy('inicia_em')])
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }

    public function minhasReservas(Usuario $usuario): Collection
    {
        $unidadesIds = $this->unidadesDoUsuario($usuario)->pluck('id')->all();

        return Reserva::query()
            ->with(['ambiente', 'faixaHorario', 'unidade.bloco'])
            ->where(function ($q) use ($usuario, $unidadesIds) {
                $q->where('solicitado_por', $usuario->getKey())
                    ->orWhereIn('unidade_id', $unidadesIds);
            })
            ->orderByDesc('data_reserva')
            ->get();
    }

    public function criarReserva(array $dados, Usuario $usuario): Reserva
    {
        $unidades = $this->unidadesDoUsuario($usuario);
        $unidadeId = $dados['unidade_id'] ?? $unidades->first()?->id;

        if (! $unidadeId || ! $unidades->contains('id', $unidadeId)) {
            throw ValidationException::withMessages([
                'unidade_id' => ['Você precisa selecionar uma unidade vinculada ao seu cadastro.'],
            ]);
        }

        $ambiente = Ambiente::query()->where('ativo', true)->findOrFail($dados['ambiente_id']);
        $faixa = FaixaHorarioAmbiente::query()
            ->where('ambiente_id', $ambiente->getKey())
            ->where('ativo', true)
            ->findOrFail($dados['faixa_horario_id']);

        // A antecedência é contada a partir do horário em que a faixa começa,
        // e não da meia-noite do dia reservado.
        $horasAntecedencia = (int) $ambiente->horas_antecedencia_minima_morador;
        $dataHoraReserva = $this->inicioDaReserva($dados['data_reserva'], $faixa->inicia_em);
        if ($dataHoraReserva->lt(now())) {
            throw ValidationException::withMessages([
                'data_reserva' => ['Não é possível reservar uma data ou horário que já passou.'],
            ]);
        }
        if ($dataHoraReserva->lt(now()->addHours($horasAntecedencia))) {
            throw ValidationException::withMessages([
                'data_reserva' => ["Este ambiente exige antecedência mínima de {$horasAntecedencia} horas para reserva."],
            ]);
        }

        return $this->reservaService->criar([
            'ambiente_id' => $ambiente->getKey(),
            'faixa_horario_id' => $faixa->getKey(),
            'unidade_id' => $unidadeId,
            'solicitado_por' => $usuario->getKey(),
            'data_reserva' => $dados['data_reserva'],
            'observacoes' => $dados['observacoes'] ?? null,
            'valor_taxa' => 0.00,
        ], $usuario, false);
    }

    public function cancelarReserva(Reserva $reserva, Usuario $usuario): Reserva
    {
        $unidadesIds = $this->unidadesDoUsuario($usuario)->pluck('id')->all();
        $pertenceAoMorador = $reserva->solicitado_por === $usuario->getKey() || in_array($reserva->unidade_id, $unidadesIds, true);

        if (! $pertenceAoMorador) {
            throw ValidationException::withMessages([
                'reserva' => ['Esta reserva não pertence à sua unidade.'],
            ]);
        }

        $limiteHoras = (int) $reserva->ambiente->horas_limite_cancelamento_morador;
        $dataHoraReserva = $this->inicioDaReserva($reserva->data_reserva, $reserva->inicia_em);
        if ($dataHoraReserva->lt(now()->addHours($limiteHoras))) {
            throw ValidationException::withMessages([
                'reserva' => ["O prazo limite para cancelamento pelo morador ({$limiteHoras}h de antecedência) expirou."],
            ]);
        }

        return $this->reservaService->cancelar($reserva, $usuario, 'Cancelado pelo morador.');
    }

    public function meusComunicados(Usuario $usuario): Collection
    {
        return ComunicadoDestinatario::query()
            ->with(['comunicado.publicadoPor.pessoa'])
            ->where(fn ($q) => $this->filtrarDestinatario($q, $usuario))
            ->whereHas('comunicado', fn ($c) => $c->where('situacao', 'PUBLICADO'))
            ->orderByDesc('criado_em')
            ->get()
            ->unique('comunicado_id')
            ->values()
            ->map(function ($dest) {
                $com = $dest->comunicado;
                $com->lido_em = $dest->lido_em;

                return $com;
            });
    }

    public function marcarComunicadoLido(Comunicado $comunicado, Usuario $usuario): void
    {
        $this->comunicadoService->marcarLido($comunicado, $usuario);
    }

    public function minhasOcorrencias(Usuario $usuario): Collection
    {
        return Ocorrencia::query()
            ->with([
                'tipo',
                'unidades.unidade.bloco',
                // Notas internas da administração não podem aparecer para o morador.
                'comentarios' => fn ($q) => $q->where('visibilidade', '!=', 'SOMENTE_ADMINISTRACAO'),
                'comentarios.autor.pessoa',
            ])
            ->where('registrado_por', $usuario->getKey())
            ->orderByDesc('aberto_em')
            ->get();
    }

    public function criarOcorrencia(array $dados, Usuario $usuario): Ocorrencia
    {
        $unidades = $this->unidadesDoUsuario($usuario);
        $unidadeId = $dados['unidade_id'] ?? $unidades->first()?->id;

        if ($unidadeId && ! $unidades->contains('id', $unidadeId)) {
            throw ValidationException::withMessages([
                'unidade_id' => ['A unidade informada não está vinculada ao seu cadastro.'],
            ]);
        }

        return $this->ocorrenciaService->criar([
            'tipo_ocorrencia_id' => $dados['tipo_ocorrencia_id'],
            'unidade_id' => $unidadeId,
            'motivo' => $dados['motivo'],
            'descricao' => $dados['descricao'],
            'interna' => false,
        ], $usuario);
    }

    public function comentarOcorrencia(Ocorrencia $ocorrencia, string $conteudo, Usuario $usuario): void
    {
        if ($ocorrencia->registrado_por !== $usuario->getKey()) {
            throw ValidationException::withMessages([
                'ocorrencia' => ['Você só pode interagir em ocorrências abertas por você.'],
            ]);
        }

        $this->ocorrenciaService->comentar($ocorrencia, $conteudo, $usuario, 'TODOS_PARTICIPANTES', false);
    }

    private function filtrarDestinatario($query, Usuario $usuario): void
    {
        $query->where('usuario_id', $usuario->getKey());

        // Sem esta verificação, um usuário sem pessoa vinculada (pessoa_id nulo)
        // acabava recebendo comunicados de outras pessoas.
        if ($usuario->pessoa_id !== null) {
            $query->orWhere('pessoa_id', $usuario->pessoa_id);
        }
    }

    private function inicioDaReserva(mixed $data, mixed $horario): Carbon
    {
        $dia = Carbon::parse($data)->toDateString();
        $hora = $horario ? Carbon::parse((string) $horario)->format('H:i:s') : '00:00:00';

        return Carbon::parse("{$dia} {$hora}");
    }

    private function linhaDigitavelSimulada(Cobranca $cobranca): string
    {
        // Gerada a partir do id da cobrança para que o número não mude a cada consulta.
        $base = str_pad((string) $cobranca->getKey(), 16, '0', STR_PAD_LEFT);
        $valor = str_pad((string) (int) round((float) $cobranca->valor_total * 100), 10, '0', STR_PAD_LEFT);

        return '34191.'.substr($base, 0, 5).' '.substr($base, 5, 5).'.'.substr($base, 10, 6).' 1 '.$cobranca->data_vencimento?->format('ymd').$valor;
    }

    private function pixCopiaEColaSimulado(Cobranca $cobranca): string
    {
        // Chave fixa por cobrança, no mesmo formato de 36 caracteres de uma chave aleatória.
        $hash = hash('sha256', 'cobranca-'.$cobranca->getKey());
        $chave = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12);
        $valor = number_format((float) $cobranca->valor_total, 2, '.', '');

        return '00020101021226580014br.gov.bcb.pix0136'.$chave.'5204000053039865'.str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT).$valor.'5802BR5916SindicoPro Cond6009Sao Paulo62070503***6304';
    }
}
