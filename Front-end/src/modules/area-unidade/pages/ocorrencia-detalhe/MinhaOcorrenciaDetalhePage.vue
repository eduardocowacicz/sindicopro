<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, LoaderCircle, MessageSquare, Send, User } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import * as ocorrenciasApi from '@/modules/ocorrencias/api/ocorrencias.api'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const id = computed(() => String(route.params.id))

const novoComentario = ref('')

const { data: ocorrencia, isPending } = useQuery({
  queryKey: ['morador-ocorrencia-detalhe', id],
  queryFn: () => ocorrenciasApi.mostrarOcorrencia(id.value),
})

const mutacaoComentar = useMutation({
  mutationFn: () => moradorApi.comentarMinhaOcorrencia(id.value, novoComentario.value),
  onSuccess: () => {
    novoComentario.value = ''
    queryClient.invalidateQueries({ queryKey: ['morador-ocorrencia-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['morador-minhas-ocorrencias'] })
  },
})

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '—'
  return new Date(dataStr).toLocaleString('pt-BR')
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
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-ocorrencias' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar aos chamados
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="ocorrencia" class="space-y-6">
      <PageHeader
        :titulo="`Chamado #${ocorrencia.numero_protocolo}: ${ocorrencia.motivo}`"
        :descricao="`Aberto em ${formatarData(ocorrencia.aberto_em)}`"
        modulo="Meu Espaço"
      />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna Principal -->
        <div class="lg:col-span-2 space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center gap-2 border-b pb-3">
              <Badge
                :variant="getSituacaoBadge(ocorrencia.situacao).variant"
                :class="getSituacaoBadge(ocorrencia.situacao).class"
              >
                {{ getSituacaoBadge(ocorrencia.situacao).label }}
              </Badge>
              <Badge variant="secondary">{{ ocorrencia.tipo?.nome }}</Badge>
              <span class="text-xs text-muted-foreground ml-auto">
                Protocolo: #{{ ocorrencia.numero_protocolo }}
              </span>
            </div>

            <div>
              <h3 class="font-semibold text-base mb-1">{{ ocorrencia.motivo }}</h3>
              <p
                class="text-sm text-foreground whitespace-pre-wrap leading-relaxed bg-muted/20 p-4 rounded-xl border"
              >
                {{ ocorrencia.descricao }}
              </p>
            </div>
          </div>

          <!-- Mensagens e Respostas com a Administração -->
          <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="border-b bg-muted/30 p-4">
              <h3 class="font-semibold text-base flex items-center gap-2">
                <MessageSquare class="h-4 w-4 text-primary" />
                Interações e Atendimento do Síndico
              </h3>
            </div>

            <div class="p-6 space-y-4">
              <div
                v-if="!ocorrencia.comentarios || ocorrencia.comentarios.length === 0"
                class="text-center py-6 text-sm text-muted-foreground"
              >
                Ainda não há mensagens da administração. Seu chamado está na fila de triagem.
              </div>

              <div
                v-for="c in ocorrencia.comentarios"
                :key="c.id_publico"
                class="rounded-xl border p-4 space-y-2 bg-muted/10 hover:bg-muted/20 transition-colors"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <div
                      class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                      <User class="h-3.5 w-3.5" />
                    </div>
                    <span class="font-semibold text-sm">
                      {{ c.autor?.pessoa?.nome_completo ?? c.autor?.email ?? 'Administração' }}
                    </span>
                  </div>
                  <span class="text-xs text-muted-foreground">{{ formatarData(c.criado_em) }}</span>
                </div>

                <p class="text-sm text-foreground pl-9 whitespace-pre-wrap">{{ c.conteudo }}</p>
              </div>

              <!-- Enviar nova mensagem -->
              <form
                v-if="ocorrencia.situacao !== 'FECHADO' && ocorrencia.situacao !== 'CANCELADO'"
                class="pt-4 border-t space-y-3"
                @submit.prevent="mutacaoComentar.mutate()"
              >
                <textarea
                  v-model="novoComentario"
                  rows="3"
                  required
                  placeholder="Escreva uma nova mensagem ou esclarecimento para a administração..."
                  class="w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                ></textarea>

                <div class="flex justify-end">
                  <Button
                    type="submit"
                    class="gap-1.5"
                    :disabled="!novoComentario.trim() || mutacaoComentar.isPending.value"
                  >
                    <Send class="h-4 w-4" />
                    Enviar Mensagem
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral -->
        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Status do Atendimento</h3>

            <div class="space-y-3 text-sm">
              <div>
                <span class="text-xs text-muted-foreground block mb-1">Situação</span>
                <Badge
                  :variant="getSituacaoBadge(ocorrencia.situacao).variant"
                  :class="getSituacaoBadge(ocorrencia.situacao).class"
                >
                  {{ getSituacaoBadge(ocorrencia.situacao).label }}
                </Badge>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Data de Abertura</span>
                <span class="font-medium">{{ formatarData(ocorrencia.aberto_em) }}</span>
              </div>

              <div v-if="ocorrencia.resolvido_em">
                <span class="text-xs text-muted-foreground block">Resolvido em</span>
                <span class="font-medium text-emerald-600">{{
                  formatarData(ocorrencia.resolvido_em)
                }}</span>
              </div>

              <div v-if="ocorrencia.fechado_em">
                <span class="text-xs text-muted-foreground block">Fechado em</span>
                <span class="font-medium">{{ formatarData(ocorrencia.fechado_em) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
