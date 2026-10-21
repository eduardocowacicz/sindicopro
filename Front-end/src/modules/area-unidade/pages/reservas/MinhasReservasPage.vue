<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  Calendar,
  CalendarCheck,
  CalendarDays,
  Clock,
  Info,
  LoaderCircle,
  Plus,
  Sparkles,
} from '@lucide/vue'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import type { Ambiente } from '@/modules/reservas/types/reserva.types'

const router = useRouter()
const queryClient = useQueryClient()

const modalReservaAberto = ref(false)
const ambienteSelecionadoParaReserva = ref<Ambiente | null>(null)
const erroModal = ref('')

const formulario = reactive({
  ambiente_id: '',
  faixa_horario_id: '',
  data_reserva: '',
  observacoes: '',
})

const { data: ambientesData, isPending: carregandoAmbientes } = useQuery({
  queryKey: ['morador-ambientes-disponiveis'],
  queryFn: () => moradorApi.obterAmbientesDisponiveis(),
})
const ambientes = computed<Ambiente[]>(() => ambientesData.value ?? [])

const { data: reservasData, isPending: carregandoReservas } = useQuery({
  queryKey: ['morador-minhas-reservas'],
  queryFn: () => moradorApi.obterMinhasReservas(),
})
const reservas = computed(() => reservasData.value ?? [])

const faixasDoAmbienteSelecionado = computed(() => {
  if (!formulario.ambiente_id) return []
  const amb = ambientes.value.find((a) => a.id_publico === formulario.ambiente_id)
  return amb?.faixas_horario ?? []
})

function abrirModalAgendamento(amb?: Ambiente): void {
  const ambienteAlvo = amb ?? ambientes.value[0]
  if (!ambienteAlvo) return

  ambienteSelecionadoParaReserva.value = ambienteAlvo
  formulario.ambiente_id = ambienteAlvo.id_publico
  formulario.faixa_horario_id = ambienteAlvo.faixas_horario?.[0]?.id_publico ?? ''

  // Define data mínima considerando antecedência mínima
  const amanha = new Date()
  amanha.setDate(amanha.getDate() + 2)
  formulario.data_reserva = amanha.toISOString().split('T')[0] ?? ''
  formulario.observacoes = ''
  erroModal.value = ''
  modalReservaAberto.value = true
}

const mutacaoSolicitar = useMutation({
  mutationFn: () =>
    moradorApi.solicitarReserva({
      ambiente_id: formulario.ambiente_id,
      faixa_horario_id: formulario.faixa_horario_id,
      data_reserva: formulario.data_reserva,
      observacoes: formulario.observacoes || undefined,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['morador-minhas-reservas'] })
    queryClient.invalidateQueries({ queryKey: ['morador-dashboard'] })
    modalReservaAberto.value = false
  },
  onError: (err: unknown) => {
    erroModal.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao solicitar agendamento da área comum.'
  },
})

const mutacaoCancelar = useMutation({
  mutationFn: (id: string) => moradorApi.cancelarMinhaReserva(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['morador-minhas-reservas'] })
    queryClient.invalidateQueries({ queryKey: ['morador-dashboard'] })
  },
})

function formatarData(dataStr?: string): string {
  if (!dataStr) return '—'
  const partes = dataStr.split('-')
  if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`
  return new Date(dataStr).toLocaleDateString('pt-BR')
}
</script>

<template>
  <div class="space-y-8">
    <PageHeader
      titulo="Reservas de Áreas Comuns"
      descricao="Consulte os espaços de lazer do condomínio, veja as regras de utilização e agende datas para seu evento."
      modulo="Meu Espaço"
    >
      <template #acoes>
        <Button class="gap-2" @click="abrirModalAgendamento()">
          <Plus class="h-4 w-4" />
          Solicitar Agendamento
        </Button>
      </template>
    </PageHeader>

    <!-- Catálogo de Ambientes Disponíveis -->
    <div class="space-y-4">
      <h2 class="text-lg font-bold text-foreground flex items-center gap-2">
        <Sparkles class="h-5 w-5 text-primary" />
        Espaços e Ambientes Disponíveis
      </h2>

      <div v-if="carregandoAmbientes" class="flex justify-center py-8">
        <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="amb in ambientes"
          :key="amb.id_publico"
          class="rounded-2xl border bg-card p-6 shadow-sm hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between space-y-4"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-lg text-foreground">{{ amb.nome }}</h3>
              <Badge variant="secondary" class="text-xs">
                {{ amb.aprovacao_automatica ? 'Aprovação Imediata' : 'Validação Síndico' }}
              </Badge>
            </div>

            <p class="text-sm text-muted-foreground line-clamp-3">
              {{
                amb.descricao ||
                'Área de convivência do condomínio disponível para agendamento dos moradores.'
              }}
            </p>

            <div class="pt-2 text-xs text-muted-foreground space-y-1">
              <div class="flex items-center gap-1.5">
                <Clock class="h-3.5 w-3.5 text-primary" />
                <span
                  >Antecedência mínima:
                  <strong>{{ amb.horas_antecedencia_minima_morador }}h</strong></span
                >
              </div>
              <div class="flex items-center gap-1.5">
                <CalendarDays class="h-3.5 w-3.5 text-primary" />
                <span
                  >Cancelamento até:
                  <strong>{{ amb.horas_limite_cancelamento_morador }}h antes</strong></span
                >
              </div>
            </div>
          </div>

          <Button class="w-full gap-1.5 mt-2" @click="abrirModalAgendamento(amb)">
            <CalendarCheck class="h-4 w-4" />
            Agendar Este Espaço
          </Button>
        </div>
      </div>
    </div>

    <!-- Lista de Minhas Reservas -->
    <div class="space-y-4 pt-4 border-t">
      <h2 class="text-lg font-bold text-foreground flex items-center gap-2">
        <CalendarDays class="h-5 w-5 text-primary" />
        Minhas Reservas Solicitadas
      </h2>

      <div v-if="carregandoReservas" class="flex justify-center py-8">
        <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
      </div>

      <div
        v-else-if="reservas.length === 0"
        class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
      >
        <Calendar class="mx-auto h-12 w-12 text-muted-foreground/40 mb-3" />
        <p class="font-medium text-foreground">Você ainda não possui agendamentos</p>
        <p class="text-sm">Selecione um dos ambientes acima para agendar sua data.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-4">
        <div
          v-for="r in reservas"
          :key="r.id_publico"
          class="rounded-xl border bg-card p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-bold text-base text-foreground">{{ r.ambiente?.nome }}</span>
              <Badge v-if="r.situacao === 'CONFIRMADO'" class="bg-emerald-600">Confirmada</Badge>
              <Badge v-else-if="r.situacao === 'CANCELADO'" variant="destructive">Cancelada</Badge>
              <Badge v-else variant="outline">Concluída</Badge>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
              <span class="flex items-center gap-1 font-medium text-foreground">
                <CalendarDays class="h-3.5 w-3.5 text-primary" />
                {{ formatarData(r.data_reserva) }}
              </span>
              <span class="flex items-center gap-1">
                <Clock class="h-3.5 w-3.5" />
                {{
                  r.faixa_horario?.nome ??
                  `${r.inicia_em.slice(0, 5)} às ${r.termina_em.slice(0, 5)}`
                }}
              </span>
            </div>

            <p v-if="r.observacoes" class="text-xs text-muted-foreground italic pt-1">
              "{{ r.observacoes }}"
            </p>
          </div>

          <div class="flex items-center gap-2 self-end sm:self-auto">
            <Button
              v-if="r.situacao === 'CONFIRMADO'"
              variant="outline"
              size="sm"
              class="text-destructive hover:bg-destructive/10 border-destructive/30"
              :disabled="mutacaoCancelar.isPending.value"
              @click="mutacaoCancelar.mutate(r.id_publico)"
            >
              Cancelar Reserva
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="router.push({ name: 'app-reserva-detalhe', params: { id: r.id_publico } })"
            >
              Detalhes
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Solicitar Reserva -->
    <Dialog :open="modalReservaAberto" @update:open="modalReservaAberto = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Agendar Espaço Comum</DialogTitle>
          <DialogDescription>
            Escolha o ambiente e a faixa de horário desejada para o seu evento.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-4 py-2" @submit.prevent="mutacaoSolicitar.mutate()">
          <div
            v-if="erroModal"
            class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive font-medium"
          >
            {{ erroModal }}
          </div>

          <div class="space-y-2">
            <Label for="ag-amb">Ambiente *</Label>
            <select
              id="ag-amb"
              v-model="formulario.ambiente_id"
              required
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            >
              <option v-for="amb in ambientes" :key="amb.id_publico" :value="amb.id_publico">
                {{ amb.nome }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <Label for="ag-faixa">Horário / Turno *</Label>
            <select
              id="ag-faixa"
              v-model="formulario.faixa_horario_id"
              required
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            >
              <option value="" disabled>Selecione um horário disponível...</option>
              <option
                v-for="f in faixasDoAmbienteSelecionado"
                :key="f.id_publico"
                :value="f.id_publico"
              >
                {{ f.nome }} ({{ f.inicia_em.slice(0, 5) }} às {{ f.termina_em.slice(0, 5) }})
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <Label for="ag-data">Data Desejada *</Label>
            <Input id="ag-data" v-model="formulario.data_reserva" type="date" required />
          </div>

          <div class="space-y-2">
            <Label for="ag-obs">Finalidade / Observações</Label>
            <textarea
              id="ag-obs"
              v-model="formulario.observacoes"
              rows="2"
              placeholder="Ex: Aniversário em família (cerca de 20 pessoas)..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
          </div>

          <div
            class="rounded-lg bg-muted/40 p-3 border text-xs text-muted-foreground flex items-start gap-2"
          >
            <Info class="h-4 w-4 text-primary shrink-0 mt-0.5" />
            <span>
              Ao confirmar, você se compromete com o regulamento interno do condomínio sobre
              conservação, limpeza e horários de silêncio.
            </span>
          </div>

          <DialogFooter class="pt-2">
            <Button type="button" variant="outline" @click="modalReservaAberto = false"
              >Cancelar</Button
            >
            <Button type="submit" :disabled="mutacaoSolicitar.isPending.value"
              >Confirmar Reserva</Button
            >
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
