<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, Check, LoaderCircle } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as reservasApi from '@/modules/reservas/api/reservas.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const id = computed(() => String(route.params.id))
const podeValidar = computed(() => autenticacao.temPermissao('reservas.agenda.validar'))
const podeCancelar = computed(() => autenticacao.temPermissao('reservas.agenda.cancelar'))

const { data: reserva, isPending } = useQuery({
  queryKey: ['reserva-detalhe', id],
  queryFn: () => reservasApi.mostrarReserva(id.value),
})

const mutacaoValidar = useMutation({
  mutationFn: () => reservasApi.validarReserva(id.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reserva-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['reservas-agenda'] })
  },
})

const mutacaoCancelar = useMutation({
  mutationFn: () => reservasApi.cancelarReserva(id.value, 'Cancelado pela administração'),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['reserva-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['reservas-agenda'] })
  },
})

function formatarData(dataStr?: string): string {
  if (!dataStr) return '—'
  const partes = dataStr.split('-')
  if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`
  return new Date(dataStr).toLocaleDateString('pt-BR')
}

function formatarDataHora(dataStr?: string): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleString('pt-BR')
}

function formatarMoeda(val: number | string): string {
  return Number(val || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'admin-reservas' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar à agenda
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="reserva" class="space-y-6">
      <PageHeader
        :titulo="`Reserva: ${reserva.ambiente?.nome ?? 'Ambiente'}`"
        :descricao="`Agendamento para ${formatarData(reserva.data_reserva)}`"
        modulo="Reservas"
      >
        <template #acoes>
          <div class="flex items-center gap-2">
            <Button
              v-if="reserva.situacao === 'CONFIRMADO' && !reserva.validado_em && podeValidar"
              variant="outline"
              class="gap-1 border-emerald-600 text-emerald-600 hover:bg-emerald-50"
              :disabled="mutacaoValidar.isPending.value"
              @click="mutacaoValidar.mutate()"
            >
              <Check class="h-4 w-4" />
              Validar Reserva
            </Button>
            <Button
              v-if="reserva.situacao === 'CONFIRMADO' && podeCancelar"
              variant="destructive"
              :disabled="mutacaoCancelar.isPending.value"
              @click="mutacaoCancelar.mutate()"
            >
              Cancelar Reserva
            </Button>
          </div>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Coluna Principal -->
        <div class="md:col-span-2 space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-lg border-b pb-2">Dados do Agendamento</h3>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-xs text-muted-foreground block">Data da Reserva</span>
                <span class="font-semibold text-base">{{
                  formatarData(reserva.data_reserva)
                }}</span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Horário</span>
                <span class="font-semibold text-base">
                  {{
                    reserva.faixa_horario?.nome ??
                    `${reserva.inicia_em.slice(0, 5)} às ${reserva.termina_em.slice(0, 5)}`
                  }}
                </span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Ambiente</span>
                <span class="font-medium">{{ reserva.ambiente?.nome }}</span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Taxa de Utilização</span>
                <span class="font-semibold text-emerald-600">{{
                  formatarMoeda(reserva.valor_taxa)
                }}</span>
              </div>
            </div>

            <div v-if="reserva.observacoes" class="pt-2 border-t">
              <span class="text-xs text-muted-foreground block mb-1"
                >Observações do Solicitante</span
              >
              <p class="text-sm bg-muted/40 p-3 rounded-lg">{{ reserva.observacoes }}</p>
            </div>
          </div>

          <!-- Unidade e Solicitante -->
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-lg border-b pb-2">Unidade e Morador</h3>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-xs text-muted-foreground block">Unidade</span>
                <span class="font-semibold">
                  Apto {{ reserva.unidade?.codigo }}
                  {{ reserva.unidade?.bloco ? `• ${reserva.unidade.bloco.nome}` : '' }}
                </span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Solicitado por</span>
                <span class="font-semibold">
                  {{
                    reserva.solicitado_por?.pessoa?.nome_completo ??
                    reserva.solicitado_por?.email ??
                    'Morador'
                  }}
                </span>
                <span class="text-xs text-muted-foreground block">{{
                  reserva.solicitado_por?.email
                }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral: Status & Timeline -->
        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Status da Reserva</h3>

            <div class="space-y-3">
              <div>
                <span class="text-xs text-muted-foreground block mb-1">Situação Atual</span>
                <Badge v-if="reserva.situacao === 'CONFIRMADO'" class="bg-emerald-600"
                  >Confirmada</Badge
                >
                <Badge v-else-if="reserva.situacao === 'CANCELADO'" variant="destructive"
                  >Cancelada</Badge
                >
                <Badge v-else variant="outline">Concluída</Badge>
              </div>

              <div v-if="reserva.validado_em">
                <span class="text-xs text-muted-foreground block">Validação Financeira</span>
                <span class="text-xs font-medium text-emerald-600"
                  >Validado em {{ formatarDataHora(reserva.validado_em) }}</span
                >
              </div>

              <div v-if="reserva.cancelado_em">
                <span class="text-xs text-muted-foreground block">Cancelado em</span>
                <span class="text-xs font-medium text-destructive">{{
                  formatarDataHora(reserva.cancelado_em)
                }}</span>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Origem do Cadastro</span>
                <span class="text-xs font-medium">
                  {{
                    reserva.criado_pela_administracao
                      ? 'Administração do Condomínio'
                      : 'Aplicativo do Morador'
                  }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
