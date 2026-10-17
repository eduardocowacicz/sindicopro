<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { AlertCircle, ChevronRight, LoaderCircle, MessageSquare, Plus } from '@lucide/vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'

const router = useRouter()

const { data: ocorrenciasData, isPending } = useQuery({
  queryKey: ['morador-minhas-ocorrencias'],
  queryFn: () => moradorApi.obterMinhasOcorrencias(),
})
const ocorrencias = computed(() => ocorrenciasData.value ?? [])

function formatarData(dataStr?: string): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getSituacaoBadge(situacao: string) {
  switch (situacao) {
    case 'ABERTO':
      return {
        variant: 'outline' as const,
        label: 'Aberto',
        class: 'text-amber-600 border-amber-300 bg-amber-50',
      }
    case 'EM_ANDAMENTO':
      return { variant: 'default' as const, label: 'Em Andamento', class: 'bg-blue-600' }
    case 'RESOLVIDO':
      return { variant: 'default' as const, label: 'Resolvido', class: 'bg-emerald-600' }
    case 'FECHADO':
      return { variant: 'secondary' as const, label: 'Fechado', class: '' }
    default:
      return { variant: 'destructive' as const, label: 'Cancelado', class: '' }
  }
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      titulo="Minhas Ocorrências e Chamados"
      descricao="Abra chamados para manutenções prediais, barulho, garagens ou dúvidas e interaja diretamente com o síndico."
      modulo="Meu Espaço"
    >
      <template #acoes>
        <Button class="gap-2" @click="router.push({ name: 'app-ocorrencia-nova' })">
          <Plus class="h-4 w-4" />
          Abrir Ocorrência
        </Button>
      </template>
    </PageHeader>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="ocorrencias.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <AlertCircle class="mx-auto h-12 w-12 text-muted-foreground/40 mb-3" />
      <p class="font-medium text-foreground">Você não possui ocorrências abertas</p>
      <p class="text-sm">
        Caso precise de suporte ou note algum problema no condomínio, abra um chamado.
      </p>
      <Button class="mt-4 gap-2" @click="router.push({ name: 'app-ocorrencia-nova' })">
        <Plus class="h-4 w-4" />
        Abrir Primeiro Chamado
      </Button>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="o in ocorrencias"
        :key="o.id_publico"
        class="group flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border bg-card p-6 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all"
        @click="router.push({ name: 'app-ocorrencia-detalhe', params: { id: o.id_publico } })"
      >
        <div class="space-y-2 flex-1 pr-4">
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="font-mono font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20"
            >
              #{{ o.numero_protocolo }}
            </span>
            <Badge
              :variant="getSituacaoBadge(o.situacao).variant"
              :class="getSituacaoBadge(o.situacao).class"
            >
              {{ getSituacaoBadge(o.situacao).label }}
            </Badge>
            <Badge variant="secondary">{{ o.tipo?.nome }}</Badge>
            <span class="text-xs text-muted-foreground">{{ formatarData(o.aberto_em) }}</span>
          </div>

          <h3
            class="font-bold text-base text-foreground group-hover:text-primary transition-colors"
          >
            {{ o.motivo }}
          </h3>

          <p class="text-sm text-muted-foreground line-clamp-2">
            {{ o.descricao }}
          </p>

          <div
            v-if="o.comentarios && o.comentarios.length > 0"
            class="pt-1 text-xs text-primary font-medium flex items-center gap-1"
          >
            <MessageSquare class="h-3.5 w-3.5" />
            {{ o.comentarios.length }} mensagem(ns) no atendimento
          </div>
        </div>

        <div
          class="flex items-center gap-2 text-primary font-medium text-sm self-end sm:self-auto shrink-0"
        >
          <span>Ver atendimento</span>
          <ChevronRight class="h-4 w-4" />
        </div>
      </div>
    </div>
  </div>
</template>
