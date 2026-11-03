<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { CheckCircle2, ChevronRight, LoaderCircle, Megaphone, Sparkles } from '@lucide/vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'

const router = useRouter()

const { data: comunicadosData, isPending } = useQuery({
  queryKey: ['morador-meus-comunicados'],
  queryFn: () => moradorApi.obterMeusComunicados(),
})
const comunicados = computed(() => comunicadosData.value ?? [])

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleDateString('pt-BR', {
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
      titulo="Mural de Avisos e Comunicados"
      descricao="Fique por dentro das novidades, manutenções, convocações e avisos da administração do condomínio."
      modulo="Meu Espaço"
    />

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="comunicados.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <Megaphone class="mx-auto h-12 w-12 text-muted-foreground/40 mb-3" />
      <p class="font-medium text-foreground">Nenhum aviso no momento</p>
      <p class="text-sm">Você está em dia com todos os comunicados do condomínio.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="c in comunicados"
        :key="c.id_publico"
        class="group flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border bg-card p-6 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all"
        @click="router.push({ name: 'app-comunicado-detalhe', params: { id: c.id_publico } })"
      >
        <div class="space-y-2 flex-1 pr-4">
          <div class="flex items-center gap-2">
            <Badge v-if="!c.lido_em" class="bg-primary gap-1">
              <Sparkles class="h-3 w-3" />
              Novo Comunicado
            </Badge>
            <Badge v-else variant="outline" class="text-muted-foreground gap-1">
              <CheckCircle2 class="h-3 w-3 text-emerald-600" />
              Lido
            </Badge>
            <span class="text-xs text-muted-foreground">{{ formatarData(c.publicado_em) }}</span>
          </div>

          <h3 class="font-bold text-lg text-foreground group-hover:text-primary transition-colors">
            {{ c.titulo }}
          </h3>

          <p class="text-sm text-muted-foreground line-clamp-2 max-w-3xl">
            {{ c.conteudo }}
          </p>
        </div>

        <div
          class="flex items-center gap-2 text-primary font-medium text-sm self-end sm:self-auto shrink-0"
        >
          <span>Ler aviso</span>
          <ChevronRight class="h-4 w-4" />
        </div>
      </div>
    </div>
  </div>
</template>
