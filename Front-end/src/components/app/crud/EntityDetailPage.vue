<script setup lang="ts" generic="T extends Record<string, any>">
import { useQuery } from '@tanstack/vue-query'
import { LoaderCircle } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import type { RegistroAuditoria } from '@/lib/api-types'

const props = defineProps<{
  titulo: string
  descricao: string
  modulo: string
  queryKey: string
  campos: { rotulo: string; render: (item: T) => string }[]
  buscar: (id: string) => Promise<T>
  buscarHistorico?: (id: string) => Promise<RegistroAuditoria[]>
  statusCampo?: string
}>()

const rota = useRoute()
const id = computed(() => rota.params.id as string)

const { data: item, isPending } = useQuery({
  queryKey: [props.queryKey, id],
  queryFn: () => props.buscar(id.value),
})

const { data: historico } = useQuery({
  queryKey: [props.queryKey, id, 'historico'],
  queryFn: () => props.buscarHistorico!(id.value),
  enabled: computed(() => Boolean(props.buscarHistorico)),
})

const acaoRotulo: Record<string, string> = {
  CRIACAO: 'Criação',
  ATUALIZACAO: 'Atualização',
  INATIVACAO: 'Inativação',
  REATIVACAO: 'Reativação',
}

function formatarData(data: string): string {
  return new Date(data).toLocaleString('pt-BR')
}
</script>

<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <PageHeader :title="titulo" :description="descricao" :eyebrow="modulo" />

    <p v-if="isPending" class="text-sm text-muted-foreground">
      <LoaderCircle class="inline size-4 animate-spin" aria-hidden="true" /> Carregando...
    </p>

    <div v-else-if="item" class="space-y-6">
      <div class="rounded-md border p-4">
        <dl class="grid gap-4 sm:grid-cols-2">
          <div v-for="campo in campos" :key="campo.rotulo">
            <dt class="text-xs uppercase text-muted-foreground">{{ campo.rotulo }}</dt>
            <dd class="text-sm">{{ campo.render(item) }}</dd>
          </div>
          <div v-if="statusCampo && statusCampo in item">
            <dt class="text-xs uppercase text-muted-foreground">Situação</dt>
            <dd>
              <Badge :variant="item[statusCampo] ? 'default' : 'secondary'">
                {{ item[statusCampo] ? 'Ativo' : 'Inativo' }}
              </Badge>
            </dd>
          </div>
        </dl>
      </div>

      <div v-if="buscarHistorico">
        <h2 class="mb-3 text-sm font-semibold uppercase text-muted-foreground">Histórico</h2>
        <div class="space-y-2">
          <p v-if="!historico || historico.length === 0" class="text-sm text-muted-foreground">
            Nenhum registro de histórico ainda.
          </p>
          <div
            v-for="(registro, indice) in historico"
            :key="indice"
            class="rounded-md border p-3 text-sm"
          >
            <div class="flex items-center justify-between">
              <span class="font-medium">{{ acaoRotulo[registro.acao] ?? registro.acao }}</span>
              <span class="text-xs text-muted-foreground">{{
                formatarData(registro.ocorrido_em)
              }}</span>
            </div>
            <p v-if="registro.usuario_nome" class="mt-1 text-xs text-muted-foreground">
              Por {{ registro.usuario_nome }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
