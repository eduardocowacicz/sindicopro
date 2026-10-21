<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, LoaderCircle } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import * as reservasApi from '@/modules/reservas/api/reservas.api'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const id = computed(() => String(route.params.id))

const { data: reserva, isPending } = useQuery({
  queryKey: ['morador-reserva-detalhe', id],
  queryFn: () => reservasApi.mostrarReserva(id.value),
})

const mutacaoCancelar = useMutation({
  mutationFn: () => moradorApi.cancelarMinhaReserva(id.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['morador-reserva-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['morador-minhas-reservas'] })
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
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-reservas' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para minhas reservas
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="reserva" class="space-y-6">
      <PageHeader
        :titulo="`Reserva: ${reserva.ambiente?.nome}`"
        :descricao="`Agendado para ${formatarData(reserva.data_reserva)}`"
        modulo="Meu Espaço"
      >
        <template #acoes>
          <Button
            v-if="reserva.situacao === 'CONFIRMADO'"
            variant="destructive"
            :disabled="mutacaoCancelar.isPending.value"
            @click="mutacaoCancelar.mutate()"
          >
            Cancelar Reserva
          </Button>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-lg border-b pb-2">Informações do Agendamento</h3>

            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-xs text-muted-foreground block">Data</span>
                <span class="font-bold text-base text-foreground">{{
                  formatarData(reserva.data_reserva)
                }}</span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Horário / Turno</span>
                <span class="font-bold text-base text-foreground">
                  {{
                    reserva.faixa_horario?.nome ??
                    `${reserva.inicia_em.slice(0, 5)} às ${reserva.termina_em.slice(0, 5)}`
                  }}
                </span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Espaço</span>
                <span class="font-medium">{{ reserva.ambiente?.nome }}</span>
              </div>
              <div>
                <span class="text-xs text-muted-foreground block">Minha Unidade</span>
                <span class="font-medium">
                  Apto {{ reserva.unidade?.codigo }}
                  {{ reserva.unidade?.bloco ? `• ${reserva.unidade.bloco.nome}` : '' }}
                </span>
              </div>
            </div>

            <div v-if="reserva.observacoes" class="pt-3 border-t">
              <span class="text-xs text-muted-foreground block mb-1">Minhas Observações</span>
              <p class="text-sm bg-muted/30 p-3 rounded-lg">{{ reserva.observacoes }}</p>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Status da Reserva</h3>
            <div class="space-y-3">
              <div>
                <span class="text-xs text-muted-foreground block mb-1">Situação</span>
                <Badge v-if="reserva.situacao === 'CONFIRMADO'" class="bg-emerald-600"
                  >Confirmada</Badge
                >
                <Badge v-else-if="reserva.situacao === 'CANCELADO'" variant="destructive"
                  >Cancelada</Badge
                >
                <Badge v-else variant="outline">Concluída</Badge>
              </div>

              <div class="text-xs text-muted-foreground space-y-1">
                <p>
                  • Cancelamento permitido com até
                  {{ reserva.ambiente?.horas_limite_cancelamento_morador ?? 24 }}h de antecedência.
                </p>
                <p>• Mantenha o ambiente limpo e respeite as normas do condomínio.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
