<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  Calendar,
  CalendarDays,
  Check,
  CirclePlus,
  Clock,
  LoaderCircle,
  Search,
  User,
} from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'
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
import * as reservasApi from '@/modules/reservas/api/reservas.api'
import { http } from '@/api/http'
import type { Reserva } from '@/modules/reservas/types/reserva.types'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const podeCadastrar = computed(() => autenticacao.temPermissao('reservas.agenda.cadastrar'))
const podeValidar = computed(() => autenticacao.temPermissao('reservas.agenda.validar'))
const podeCancelar = computed(() => autenticacao.temPermissao('reservas.agenda.cancelar'))

const busca = ref('')
const ambienteFiltro = ref('')
const situacaoFiltro = ref('TODOS')
const pagina = ref(1)

const modalNovaReservaAberto = ref(false)
const modalCancelarAberto = ref(false)
const reservaSelecionadaParaCancelar = ref<Reserva | null>(null)
const motivoCancelamento = ref('')
const erroModal = ref('')

const formularioReserva = reactive({
  ambiente_id: '',
  faixa_horario_id: '',
  unidade_id: '',
  data_reserva: '',
  observacoes: '',
  valor_taxa: 0,
})

const { data: ambientesData } = useQuery({
  queryKey: ['ambientes-ativos-agenda'],
  queryFn: () => reservasApi.listarAmbientes({ somente_ativos: true }),
})
const ambientes = computed(() => ambientesData.value?.dados ?? [])

const ambienteSelecionadoNoForm = computed(() =>
  ambientes.value.find((a) => a.id_publico === formularioReserva.ambiente_id),
)
const faixasDoAmbiente = computed(() => ambienteSelecionadoNoForm.value?.faixas_horario ?? [])

// Carrega unidades para seleção
const { data: unidadesData } = useQuery({
  queryKey: ['unidades-select-reserva'],
  queryFn: async () => {
    const res = await http.get('/api/unidades', { params: { por_pagina: 200 } })
    return res.data?.dados ?? []
  },
})
const unidades = computed(() => unidadesData.value ?? [])

const { data, isPending } = useQuery({
  queryKey: ['reservas-agenda', busca, ambienteFiltro, situacaoFiltro, pagina],
  queryFn: () =>
    reservasApi.listarReservas({
      busca: busca.value || undefined,
      ambiente_id: ambienteFiltro.value || undefined,
      situacao: situacaoFiltro.value || undefined,
      pagina: pagina.value,
    }),
})
const reservas = computed(() => data.value?.dados ?? [])
const meta = computed(() => data.value?.meta)

watch([busca, ambienteFiltro, situacaoFiltro], () => {
  pagina.value = 1
})

function abrirModalNovaReserva(): void {
  formularioReserva.ambiente_id = ambientes.value[0]?.id_publico ?? ''
  formularioReserva.faixa_horario_id = ''
  formularioReserva.unidade_id = unidades.value[0]?.id_publico ?? ''
  formularioReserva.data_reserva = new Date().toISOString().split('T')[0] ?? ''
  formularioReserva.observacoes = ''
  formularioReserva.valor_taxa = 0
  erroModal.value = ''
  modalNovaReservaAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () =>
    reservasApi.criarReserva({
      ambiente_id: formularioReserva.ambiente_id,
      faixa_horario_id: formularioReserva.faixa_horario_id,
      unidade_id: formularioReserva.unidade_id,
      data_reserva: formularioReserva.data_reserva,
      observacoes: formularioReserva.observacoes,
      valor_taxa: formularioReserva.valor_taxa,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reservas-agenda'] })
    modalNovaReservaAberto.value = false
  },
  onError: (err: unknown) => {
    erroModal.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao registrar agendamento de reserva.'
  },
})

const mutacaoValidar = useMutation({
  mutationFn: (id: string) => reservasApi.validarReserva(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reservas-agenda'] })
  },
})

function confirmarCancelamento(r: Reserva): void {
  reservaSelecionadaParaCancelar.value = r
  motivoCancelamento.value = ''
  modalCancelarAberto.value = true
}

const mutacaoCancelar = useMutation({
  mutationFn: async () => {
    if (!reservaSelecionadaParaCancelar.value) return
    await reservasApi.cancelarReserva(
      reservaSelecionadaParaCancelar.value.id_publico,
      motivoCancelamento.value || undefined,
    )
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reservas-agenda'] })
    modalCancelarAberto.value = false
  },
})

function formatarData(dataStr?: string): string {
  if (!dataStr) return '—'
  const partes = dataStr.split('-')
  if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`
  return new Date(dataStr).toLocaleDateString('pt-BR')
}

function formatarMoeda(val: number | string): string {
  return Number(val || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      titulo="Agenda Geral de Reservas"
      descricao="Controle de ocupação das áreas comuns, aprovação de solicitações e validações financeiras."
      modulo="Reservas"
    >
      <template #acoes>
        <Button v-if="podeCadastrar" class="gap-2" @click="abrirModalNovaReserva">
          <CirclePlus class="h-4 w-4" />
          Nova Reserva
        </Button>
      </template>
    </PageHeader>

    <!-- Filtros -->
    <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-4 shadow-sm">
      <div class="relative min-w-[240px] flex-1">
        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input v-model="busca" placeholder="Buscar por morador ou unidade..." class="pl-9" />
      </div>

      <div class="w-52">
        <select
          v-model="ambienteFiltro"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option value="">Todos os ambientes</option>
          <option v-for="amb in ambientes" :key="amb.id_publico" :value="amb.id_publico">
            {{ amb.nome }}
          </option>
        </select>
      </div>

      <div class="w-44">
        <select
          v-model="situacaoFiltro"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option value="TODOS">Todas situações</option>
          <option value="CONFIRMADO">Confirmadas</option>
          <option value="CANCELADO">Canceladas</option>
          <option value="CONCLUIDO">Concluídas</option>
        </select>
      </div>
    </div>

    <!-- Tabela de Reservas -->
    <div v-if="isPending" class="flex justify-center py-12">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="reservas.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <Calendar class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
      <p class="font-medium text-foreground">Nenhuma reserva localizada</p>
      <p class="text-sm">Altere os filtros de busca ou cadastre um novo agendamento.</p>
    </div>

    <div v-else class="overflow-hidden rounded-xl border bg-card shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
            <tr>
              <th class="px-6 py-4">Data & Horário</th>
              <th class="px-6 py-4">Ambiente</th>
              <th class="px-6 py-4">Unidade / Morador</th>
              <th class="px-6 py-4">Taxa</th>
              <th class="px-6 py-4">Situação</th>
              <th class="px-6 py-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="r in reservas"
              :key="r.id_publico"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-semibold text-foreground flex items-center gap-1.5">
                  <CalendarDays class="h-4 w-4 text-primary" />
                  {{ formatarData(r.data_reserva) }}
                </div>
                <div class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                  <Clock class="h-3 w-3" />
                  {{
                    r.faixa_horario?.nome ??
                    `${r.inicia_em.slice(0, 5)} às ${r.termina_em.slice(0, 5)}`
                  }}
                </div>
              </td>
              <td class="px-6 py-4 font-medium text-foreground">
                {{ r.ambiente?.nome }}
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-foreground">
                  Apto {{ r.unidade?.codigo }}
                  {{ r.unidade?.bloco ? `• ${r.unidade.bloco.nome}` : '' }}
                </div>
                <div class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                  <User class="h-3 w-3" />
                  {{
                    r.solicitado_por?.pessoa?.nome_completo ?? r.solicitado_por?.email ?? 'Morador'
                  }}
                </div>
              </td>
              <td class="px-6 py-4 font-medium">
                {{ formatarMoeda(r.valor_taxa) }}
              </td>
              <td class="px-6 py-4">
                <Badge v-if="r.situacao === 'CONFIRMADO'" class="bg-emerald-600">Confirmada</Badge>
                <Badge v-else-if="r.situacao === 'CANCELADO'" variant="destructive"
                  >Cancelada</Badge
                >
                <Badge v-else variant="outline">Concluída</Badge>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <Button
                    v-if="r.situacao === 'CONFIRMADO' && !r.validado_em && podeValidar"
                    variant="outline"
                    size="sm"
                    class="gap-1 border-emerald-600 text-emerald-600 hover:bg-emerald-50"
                    :disabled="mutacaoValidar.isPending.value"
                    @click="mutacaoValidar.mutate(r.id_publico)"
                  >
                    <Check class="h-3.5 w-3.5" />
                    Validar
                  </Button>
                  <Button
                    v-if="r.situacao === 'CONFIRMADO' && podeCancelar"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:bg-destructive/10"
                    @click="confirmarCancelamento(r)"
                  >
                    Cancelar
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="
                      router.push({ name: 'admin-reserva-detalhe', params: { id: r.id_publico } })
                    "
                  >
                    Detalhes
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div
        v-if="meta && meta.ultima_pagina > 1"
        class="flex items-center justify-between border-t px-6 py-3"
      >
        <p class="text-xs text-muted-foreground">Total de {{ meta.total }} reservas encontradas</p>
        <div class="flex gap-1">
          <Button variant="outline" size="sm" :disabled="pagina <= 1" @click="pagina--">
            Anterior
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="pagina >= meta.ultima_pagina"
            @click="pagina++"
          >
            Próxima
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal Nova Reserva Administrativa -->
    <Dialog :open="modalNovaReservaAberto" @update:open="modalNovaReservaAberto = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Agendar Nova Reserva</DialogTitle>
          <DialogDescription>
            Registro direto de reserva de área comum pela administração do condomínio.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-4 py-2" @submit.prevent="mutacaoCriar.mutate()">
          <div
            v-if="erroModal"
            class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive font-medium"
          >
            {{ erroModal }}
          </div>

          <div class="space-y-2">
            <Label for="res-ambiente">Ambiente *</Label>
            <select
              id="res-ambiente"
              v-model="formularioReserva.ambiente_id"
              required
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            >
              <option v-for="amb in ambientes" :key="amb.id_publico" :value="amb.id_publico">
                {{ amb.nome }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <Label for="res-faixa">Faixa de Horário *</Label>
            <select
              id="res-faixa"
              v-model="formularioReserva.faixa_horario_id"
              required
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            >
              <option value="" disabled>Selecione um horário...</option>
              <option v-for="f in faixasDoAmbiente" :key="f.id_publico" :value="f.id_publico">
                {{ f.nome }} ({{ f.inicia_em.slice(0, 5) }} às {{ f.termina_em.slice(0, 5) }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-2">
              <Label for="res-unidade">Unidade / Apto *</Label>
              <select
                id="res-unidade"
                v-model="formularioReserva.unidade_id"
                required
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option v-for="u in unidades" :key="u.id_publico" :value="u.id_publico">
                  Apto {{ u.codigo }} {{ u.bloco ? `• ${u.bloco.nome}` : '' }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="res-data">Data do Agendamento *</Label>
              <Input id="res-data" v-model="formularioReserva.data_reserva" type="date" required />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="res-taxa">Taxa de Utilização (R$)</Label>
            <Input
              id="res-taxa"
              v-model.number="formularioReserva.valor_taxa"
              type="number"
              step="0.01"
              min="0"
            />
          </div>

          <div class="space-y-2">
            <Label for="res-obs">Observações</Label>
            <textarea
              id="res-obs"
              v-model="formularioReserva.observacoes"
              rows="2"
              placeholder="Finalidade do evento, quantidade prevista de convidados..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
          </div>

          <DialogFooter class="pt-2">
            <Button type="button" variant="outline" @click="modalNovaReservaAberto = false"
              >Cancelar</Button
            >
            <Button type="submit" :disabled="mutacaoCriar.isPending.value"
              >Salvar Agendamento</Button
            >
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Modal Cancelar Reserva -->
    <Dialog :open="modalCancelarAberto" @update:open="modalCancelarAberto = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Cancelar Agendamento de Reserva</DialogTitle>
          <DialogDescription>
            Confirme o cancelamento desta reserva. O horário voltará a ficar disponível para outros
            moradores.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3 py-2">
          <Label for="canc-motivo">Motivo do cancelamento (opcional)</Label>
          <Input
            id="canc-motivo"
            v-model="motivoCancelamento"
            placeholder="Ex: Desistência informada pelo condômino."
          />
        </div>

        <DialogFooter>
          <Button variant="outline" @click="modalCancelarAberto = false">Voltar</Button>
          <Button
            variant="destructive"
            :disabled="mutacaoCancelar.isPending.value"
            @click="mutacaoCancelar.mutate()"
          >
            Confirmar Cancelamento
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
