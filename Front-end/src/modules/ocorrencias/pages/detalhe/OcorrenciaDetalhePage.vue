<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, LoaderCircle, MessageSquare, Send, User } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as ocorrenciasApi from '@/modules/ocorrencias/api/ocorrencias.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const id = computed(() => String(route.params.id))
const podeResponder = computed(() => autenticacao.temPermissao('ocorrencias.responder'))
const podeAlterarSituacao = computed(() =>
  autenticacao.temPermissao('ocorrencias.alterar_situacao'),
)

const novoComentario = ref('')

const { data: ocorrencia, isPending } = useQuery({
  queryKey: ['ocorrencia-detalhe', id],
  queryFn: () => ocorrenciasApi.mostrarOcorrencia(id.value),
})

const mutacaoComentar = useMutation({
  mutationFn: () =>
    ocorrenciasApi.comentarOcorrencia(id.value, {
      conteudo: novoComentario.value,
      visibilidade: 'TODOS_PARTICIPANTES',
    }),
  onSuccess: () => {
    novoComentario.value = ''
    queryClient.invalidateQueries({ queryKey: ['ocorrencia-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['ocorrencias-lista'] })
  },
})

const mutacaoSituacao = useMutation({
  mutationFn: (situacao: string) =>
    ocorrenciasApi.alterarSituacaoOcorrencia(id.value, {
      situacao,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['ocorrencia-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['ocorrencias-lista'] })
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
        @click="router.push({ name: 'admin-ocorrencias' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para a lista
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="ocorrencia" class="space-y-6">
      <PageHeader
        :titulo="`Ocorrência #${ocorrencia.numero_protocolo}: ${ocorrencia.motivo}`"
        :descricao="`Registrado em ${formatarData(ocorrencia.aberto_em)}`"
        modulo="Ocorrências"
      >
        <template #acoes>
          <div v-if="podeAlterarSituacao" class="flex items-center gap-2">
            <Button
              v-if="ocorrencia.situacao === 'ABERTO'"
              variant="outline"
              size="sm"
              class="border-blue-600 text-blue-600 hover:bg-blue-50"
              :disabled="mutacaoSituacao.isPending.value"
              @click="mutacaoSituacao.mutate('EM_ANDAMENTO')"
            >
              Iniciar Atendimento
            </Button>
            <Button
              v-if="['ABERTO', 'EM_ANDAMENTO'].includes(ocorrencia.situacao)"
              variant="outline"
              size="sm"
              class="border-emerald-600 text-emerald-600 hover:bg-emerald-50"
              :disabled="mutacaoSituacao.isPending.value"
              @click="mutacaoSituacao.mutate('RESOLVIDO')"
            >
              Marcar como Resolvido
            </Button>
            <Button
              v-if="ocorrencia.situacao === 'RESOLVIDO'"
              variant="secondary"
              size="sm"
              :disabled="mutacaoSituacao.isPending.value"
              @click="mutacaoSituacao.mutate('FECHADO')"
            >
              Fechar Chamado
            </Button>
            <Button
              v-if="ocorrencia.situacao !== 'CANCELADO' && ocorrencia.situacao !== 'FECHADO'"
              variant="destructive"
              size="sm"
              :disabled="mutacaoSituacao.isPending.value"
              @click="mutacaoSituacao.mutate('CANCELADO')"
            >
              Cancelar
            </Button>
          </div>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna Principal: Detalhes e Linha do Tempo de Respostas -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Detalhes do Chamado -->
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

          <!-- Timeline de Interações e Comentários -->
          <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="border-b bg-muted/30 p-4">
              <h3 class="font-semibold text-base flex items-center gap-2">
                <MessageSquare class="h-4 w-4 text-primary" />
                Histórico de Mensagens e Atendimento
              </h3>
            </div>

            <div class="p-6 space-y-4">
              <div
                v-if="!ocorrencia.comentarios || ocorrencia.comentarios.length === 0"
                class="text-center py-6 text-sm text-muted-foreground"
              >
                Nenhum comentário ou interação registrada até o momento.
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
                      {{ c.autor?.pessoa?.nome_completo ?? c.autor?.email ?? 'Usuário' }}
                    </span>
                  </div>
                  <span class="text-xs text-muted-foreground">{{ formatarData(c.criado_em) }}</span>
                </div>

                <p class="text-sm text-foreground pl-9 whitespace-pre-wrap">{{ c.conteudo }}</p>
              </div>

              <!-- Enviar Resposta -->
              <form
                v-if="podeResponder"
                class="pt-4 border-t space-y-3"
                @submit.prevent="mutacaoComentar.mutate()"
              >
                <textarea
                  v-model="novoComentario"
                  rows="3"
                  required
                  placeholder="Escreva uma resposta oficial ou atualização do chamado para o morador..."
                  class="w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                ></textarea>

                <div class="flex justify-end">
                  <Button
                    type="submit"
                    class="gap-1.5"
                    :disabled="!novoComentario.trim() || mutacaoComentar.isPending.value"
                  >
                    <Send class="h-4 w-4" />
                    Enviar Resposta
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral -->
        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Informações da Solicitação</h3>

            <div class="space-y-3 text-sm">
              <div>
                <span class="text-xs text-muted-foreground block">Solicitante</span>
                <span class="font-medium">
                  {{
                    ocorrencia.registrado_por?.pessoa?.nome_completo ??
                    ocorrencia.registrado_por?.email ??
                    'Morador'
                  }}
                </span>
                <span class="text-xs text-muted-foreground block">{{
                  ocorrencia.registrado_por?.email
                }}</span>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Unidade Relacionada</span>
                <span class="font-medium">
                  {{
                    ocorrencia.unidades?.[0]?.unidade
                      ? `Apto ${ocorrencia.unidades[0].unidade.codigo} ${ocorrencia.unidades[0].unidade.bloco ? '• ' + ocorrencia.unidades[0].unidade.bloco.nome : ''}`
                      : 'Área Comum / Não vinculado'
                  }}
                </span>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Data de Abertura</span>
                <span class="font-medium">{{ formatarData(ocorrencia.aberto_em) }}</span>
              </div>

              <div v-if="ocorrencia.resolvido_em">
                <span class="text-xs text-muted-foreground block">Data de Resolução</span>
                <span class="font-medium text-emerald-600">{{
                  formatarData(ocorrencia.resolvido_em)
                }}</span>
              </div>

              <div v-if="ocorrencia.fechado_em">
                <span class="text-xs text-muted-foreground block">Data de Fechamento</span>
                <span class="font-medium">{{ formatarData(ocorrencia.fechado_em) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
