<script setup lang="ts">
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, CheckCircle2, LoaderCircle } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import * as comunicadosApi from '@/modules/comunicados/api/comunicados.api'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const id = computed(() => String(route.params.id))

const { data: comunicado, isPending } = useQuery({
  queryKey: ['morador-comunicado-detalhe', id],
  queryFn: async () => {
    const com = await comunicadosApi.mostrarComunicado(id.value)
    // Marca como lido em segundo plano
    moradorApi.marcarComunicadoLido(id.value).then(() => {
      queryClient.invalidateQueries({ queryKey: ['morador-meus-comunicados'] })
      queryClient.invalidateQueries({ queryKey: ['morador-dashboard'] })
    })
    return com
  },
})

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleString('pt-BR')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-comunicados' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar aos comunicados
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="comunicado" class="max-w-4xl space-y-6">
      <PageHeader
        :titulo="comunicado.titulo"
        :descricao="`Publicado em ${formatarData(comunicado.publicado_em)} pela administração`"
        modulo="Meu Espaço"
      />

      <div class="rounded-2xl border bg-card p-6 md:p-8 shadow-sm space-y-6">
        <div
          class="flex flex-wrap items-center justify-between gap-2 border-b pb-4 text-xs text-muted-foreground"
        >
          <Badge variant="outline" class="gap-1 text-emerald-600 bg-emerald-50 border-emerald-200">
            <CheckCircle2 class="h-3 w-3" />
            Leitura confirmada
          </Badge>
          <span
            >Emitido para:
            {{
              comunicado.tipo_publico === 'TODOS' ? 'Todos os Condôminos' : 'Blocos Selecionados'
            }}</span
          >
        </div>

        <div class="prose max-w-none text-foreground whitespace-pre-wrap leading-relaxed text-base">
          {{ comunicado.conteudo }}
        </div>
      </div>
    </div>
  </div>
</template>
