<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, Building2, CheckCircle2, Eye, LoaderCircle, Send, Users } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as comunicadosApi from '@/modules/comunicados/api/comunicados.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const id = computed(() => String(route.params.id))
const podePublicar = computed(() => autenticacao.temPermissao('comunicados.publicar'))
const podeCancelar = computed(() => autenticacao.temPermissao('comunicados.cancelar'))

const { data: comunicado, isPending } = useQuery({
  queryKey: ['comunicado-detalhe', id],
  queryFn: () => comunicadosApi.mostrarComunicado(id.value),
})

const mutacaoPublicar = useMutation({
  mutationFn: () => comunicadosApi.publicarComunicado(id.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['comunicado-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['comunicados-lista'] })
  },
})

const mutacaoCancelar = useMutation({
  mutationFn: () => comunicadosApi.cancelarComunicado(id.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['comunicado-detalhe', id] })
    queryClient.invalidateQueries({ queryKey: ['comunicados-lista'] })
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
        @click="router.push({ name: 'admin-comunicados' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para a lista
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="comunicado" class="space-y-6">
      <PageHeader
        :titulo="comunicado.titulo"
        descricao="Detalhes do comunicado e rastreamento de leitura por unidade."
        modulo="Comunicados"
      >
        <template #acoes>
          <div class="flex items-center gap-2">
            <Button
              v-if="comunicado.situacao === 'RASCUNHO' && podePublicar"
              class="gap-1.5"
              :disabled="mutacaoPublicar.isPending.value"
              @click="mutacaoPublicar.mutate()"
            >
              <Send class="h-4 w-4" />
              Publicar Agora
            </Button>
            <Button
              v-if="comunicado.situacao !== 'CANCELADO' && podeCancelar"
              variant="destructive"
              :disabled="mutacaoCancelar.isPending.value"
              @click="mutacaoCancelar.mutate()"
            >
              Cancelar Comunicado
            </Button>
          </div>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conteúdo do Comunicado -->
        <div class="lg:col-span-2 space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center gap-2 border-b pb-4">
              <Badge v-if="comunicado.situacao === 'PUBLICADO'" class="bg-emerald-600 gap-1">
                <CheckCircle2 class="h-3 w-3" />
                Publicado
              </Badge>
              <Badge
                v-else-if="comunicado.situacao === 'RASCUNHO'"
                variant="outline"
                class="text-amber-600 bg-amber-50"
              >
                Rascunho
              </Badge>
              <Badge v-else variant="destructive">Cancelado</Badge>

              <Badge variant="secondary" class="gap-1">
                <Users v-if="comunicado.tipo_publico === 'TODOS'" class="h-3 w-3" />
                <Building2 v-else class="h-3 w-3" />
                {{
                  comunicado.tipo_publico === 'TODOS' ? 'Todos os Condôminos' : 'Blocos Específicos'
                }}
              </Badge>

              <span v-if="comunicado.publicado_em" class="text-xs text-muted-foreground">
                Publicado em {{ formatarData(comunicado.publicado_em) }}
              </span>
            </div>

            <div class="prose max-w-none text-foreground whitespace-pre-wrap leading-relaxed">
              {{ comunicado.conteudo }}
            </div>
          </div>

          <!-- Rastreamento de Destinatários e Leitura -->
          <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="border-b bg-muted/40 p-4">
              <h3 class="font-semibold text-base flex items-center justify-between">
                <span>Rastreamento de Leituras</span>
                <span class="text-xs text-muted-foreground font-normal">
                  {{ comunicado.lidos_count ?? 0 }} de
                  {{ comunicado.destinatarios_count ?? 0 }} confirmações
                </span>
              </h3>
            </div>

            <div
              v-if="!comunicado.destinatarios || comunicado.destinatarios.length === 0"
              class="p-8 text-center text-sm text-muted-foreground"
            >
              Ainda não há registros de destinatários (comunicado em rascunho ou sem envios).
            </div>

            <div v-else class="overflow-x-auto max-h-96">
              <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/20 text-xs font-semibold text-muted-foreground">
                  <tr>
                    <th class="px-4 py-2.5">Unidade</th>
                    <th class="px-4 py-2.5">Condômino</th>
                    <th class="px-4 py-2.5">E-mail</th>
                    <th class="px-4 py-2.5 text-right">Confirmação de Leitura</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <tr v-for="d in comunicado.destinatarios" :key="d.id" class="hover:bg-muted/30">
                    <td class="px-4 py-3 font-medium">
                      Apto {{ d.unidade?.codigo }}
                      {{ d.unidade?.bloco ? `• ${d.unidade.bloco.nome}` : '' }}
                    </td>
                    <td class="px-4 py-3">
                      {{ d.pessoa?.nome_completo ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground text-xs">
                      {{ d.email_registrado }}
                    </td>
                    <td class="px-4 py-3 text-right">
                      <Badge v-if="d.lido_em" class="bg-emerald-600 gap-1 text-xs">
                        <Eye class="h-3 w-3" />
                        Lido em {{ formatarData(d.lido_em) }}
                      </Badge>
                      <Badge v-else variant="outline" class="text-muted-foreground text-xs">
                        Pendente
                      </Badge>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral -->
        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Informações da Publicação</h3>

            <div class="space-y-3 text-sm">
              <div>
                <span class="text-xs text-muted-foreground block">Autor / Criado por</span>
                <span class="font-medium">
                  {{
                    comunicado.publicado_por?.pessoa?.nome_completo ??
                    comunicado.publicado_por?.email ??
                    'Administração'
                  }}
                </span>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Alcance</span>
                <span class="font-medium">
                  {{ comunicado.destinatarios_count ?? 0 }} unidades notificadas
                </span>
              </div>

              <div v-if="comunicado.blocos && comunicado.blocos.length > 0">
                <span class="text-xs text-muted-foreground block mb-1">Blocos Direcionados</span>
                <div class="flex flex-wrap gap-1">
                  <Badge v-for="b in comunicado.blocos" :key="b.id_publico" variant="secondary">
                    {{ b.nome }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
