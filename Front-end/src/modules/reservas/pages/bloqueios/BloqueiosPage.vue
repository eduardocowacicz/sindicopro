<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { Ban, CirclePlus, LoaderCircle, ShieldAlert } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'

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
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()
const podeCadastrar = computed(() => autenticacao.temPermissao('reservas.bloqueios.cadastrar'))
const podeCancelar = computed(() => autenticacao.temPermissao('reservas.bloqueios.cancelar'))

const modalAberto = ref(false)
const erroModal = ref('')

const formulario = reactive({
  ambiente_id: '',
  inicio_vigencia: '',
  fim_vigencia: '',
  motivo: '',
})

const { data: ambientesData } = useQuery({
  queryKey: ['ambientes-ativos-select'],
  queryFn: () => reservasApi.listarAmbientes({ somente_ativos: true }),
})
const ambientes = computed(() => ambientesData.value?.dados ?? [])

const { data, isPending } = useQuery({
  queryKey: ['reservas-bloqueios'],
  queryFn: () => reservasApi.listarBloqueios(),
})
const bloqueios = computed(() => data.value?.dados ?? [])

function abrirModal(): void {
  formulario.ambiente_id = ambientes.value[0]?.id_publico ?? ''
  formulario.inicio_vigencia = ''
  formulario.fim_vigencia = ''
  formulario.motivo = ''
  erroModal.value = ''
  modalAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () =>
    reservasApi.criarBloqueio({
      ambiente_id: formulario.ambiente_id,
      inicio_vigencia: formulario.inicio_vigencia,
      fim_vigencia: formulario.fim_vigencia,
      motivo: formulario.motivo,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reservas-bloqueios'] })
    modalAberto.value = false
  },
  onError: (err: unknown) => {
    erroModal.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao registrar bloqueio de ambiente.'
  },
})

const mutacaoCancelar = useMutation({
  mutationFn: (id: string) => reservasApi.cancelarBloqueio(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reservas-bloqueios'] })
  },
})

function formatarData(dataStr?: string): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      titulo="Bloqueios de Ambientes"
      descricao="Interdições temporárias para manutenção, reformas ou eventos institucionais."
      modulo="Reservas"
    >
      <template #acoes>
        <Button v-if="podeCadastrar" class="gap-2" @click="abrirModal">
          <CirclePlus class="h-4 w-4" />
          Novo Bloqueio
        </Button>
      </template>
    </PageHeader>

    <div v-if="isPending" class="flex justify-center py-12">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="bloqueios.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <Ban class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
      <p class="font-medium text-foreground">Nenhum bloqueio cadastrado</p>
      <p class="text-sm">
        Todos os ambientes estão liberados conforme suas regras normais de agendamento.
      </p>
    </div>

    <div v-else class="overflow-hidden rounded-xl border bg-card shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
            <tr>
              <th class="px-6 py-4">Ambiente</th>
              <th class="px-6 py-4">Período de Bloqueio</th>
              <th class="px-6 py-4">Motivo / Justificativa</th>
              <th class="px-6 py-4">Situação</th>
              <th class="px-6 py-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="b in bloqueios"
              :key="b.id_publico"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 font-medium text-foreground">
                {{ b.ambiente?.nome ?? 'Ambiente' }}
              </td>
              <td class="px-6 py-4 text-muted-foreground whitespace-nowrap">
                {{ formatarData(b.inicio_vigencia) }} até {{ formatarData(b.fim_vigencia) }}
              </td>
              <td class="px-6 py-4 max-w-md text-foreground">
                {{ b.motivo }}
              </td>
              <td class="px-6 py-4">
                <Badge v-if="b.cancelado_em" variant="outline" class="text-muted-foreground"
                  >Cancelado</Badge
                >
                <Badge v-else variant="destructive" class="gap-1">
                  <ShieldAlert class="h-3 w-3" />
                  Bloqueado
                </Badge>
              </td>
              <td class="px-6 py-4 text-right">
                <Button
                  v-if="!b.cancelado_em && podeCancelar"
                  variant="outline"
                  size="sm"
                  class="text-destructive hover:bg-destructive/10"
                  :disabled="mutacaoCancelar.isPending.value"
                  @click="mutacaoCancelar.mutate(b.id_publico)"
                >
                  Desbloquear
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Novo Bloqueio -->
    <Dialog :open="modalAberto" @update:open="modalAberto = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Interditar / Bloquear Ambiente</DialogTitle>
          <DialogDescription>
            Defina o período em que o ambiente não aceitará reservas de moradores.
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
            <Label for="ambiente">Ambiente *</Label>
            <select
              id="ambiente"
              v-model="formulario.ambiente_id"
              required
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            >
              <option v-for="amb in ambientes" :key="amb.id_publico" :value="amb.id_publico">
                {{ amb.nome }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-2">
              <Label for="inicio">Início do Bloqueio *</Label>
              <Input
                id="inicio"
                v-model="formulario.inicio_vigencia"
                type="datetime-local"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="fim">Fim do Bloqueio *</Label>
              <Input id="fim" v-model="formulario.fim_vigencia" type="datetime-local" required />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="motivo">Motivo do Bloqueio *</Label>
            <textarea
              id="motivo"
              v-model="formulario.motivo"
              required
              rows="3"
              placeholder="Ex: Pintura das paredes e manutenção do piso."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
          </div>

          <DialogFooter class="pt-2">
            <Button type="button" variant="outline" @click="modalAberto = false">Cancelar</Button>
            <Button type="submit" variant="destructive" :disabled="mutacaoCriar.isPending.value">
              Confirmar Bloqueio
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
