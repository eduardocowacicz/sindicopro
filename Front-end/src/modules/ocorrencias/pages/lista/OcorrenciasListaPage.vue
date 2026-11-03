<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { AlertCircle, CirclePlus, LoaderCircle, MessageSquare, Search } from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

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
import * as ocorrenciasApi from '@/modules/ocorrencias/api/ocorrencias.api'
import { http } from '@/api/http'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const podeCadastrar = computed(() => autenticacao.temPermissao('ocorrencias.cadastrar'))

const busca = ref('')
const situacaoFiltro = ref('TODOS')
const tipoFiltro = ref('')
const pagina = ref(1)

const modalNovaAberto = ref(false)
const erroModal = ref('')

const formulario = reactive({
  tipo_id: '',
  unidade_id: '',
  motivo: '',
  descricao: '',
  interna: false,
})

const { data: tiposData } = useQuery({
  queryKey: ['tipos-ocorrencia-select'],
  queryFn: () => ocorrenciasApi.listarTiposOcorrencia({ somente_ativos: true }),
})
const tipos = computed(() => tiposData.value?.dados ?? [])

const { data: unidadesData } = useQuery({
  queryKey: ['unidades-select-ocorrencia'],
  queryFn: async () => {
    const res = await http.get('/api/unidades', { params: { por_pagina: 200 } })
    return res.data?.dados ?? []
  },
})
const unidades = computed(() => unidadesData.value ?? [])

const { data, isPending } = useQuery({
  queryKey: ['ocorrencias-lista', busca, situacaoFiltro, tipoFiltro, pagina],
  queryFn: () =>
    ocorrenciasApi.listarOcorrencias({
      busca: busca.value || undefined,
      situacao: situacaoFiltro.value || undefined,
      tipo_id: tipoFiltro.value || undefined,
      pagina: pagina.value,
    }),
})
const ocorrencias = computed(() => data.value?.dados ?? [])
const meta = computed(() => data.value?.meta)

watch([busca, situacaoFiltro, tipoFiltro], () => {
  pagina.value = 1
})

function abrirModalNova(): void {
  formulario.tipo_id = tipos.value[0]?.id_publico ?? ''
  formulario.unidade_id = ''
  formulario.motivo = ''
  formulario.descricao = ''
  formulario.interna = false
  erroModal.value = ''
  modalNovaAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () =>
    ocorrenciasApi.criarOcorrencia({
      tipo_id: formulario.tipo_id,
      unidade_id: formulario.unidade_id || undefined,
      motivo: formulario.motivo,
      descricao: formulario.descricao,
      interna: formulario.interna,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['ocorrencias-lista'] })
    modalNovaAberto.value = false
  },
  onError: (err: unknown) => {
    erroModal.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao registrar ocorrência.'
  },
})

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
      titulo="Livro de Ocorrências e Chamados"
      descricao="Central de registro, acompanhamento e resolução de chamados de moradores e manutenção predial."
      modulo="Ocorrências"
    >
      <template #acoes>
        <Button v-if="podeCadastrar" class="gap-2" @click="abrirModalNova">
          <CirclePlus class="h-4 w-4" />
          Nova Ocorrência
        </Button>
      </template>
    </PageHeader>

    <!-- Filtros -->
    <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-4 shadow-sm">
      <div class="relative min-w-[260px] flex-1">
        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="busca"
          placeholder="Buscar por protocolo, motivo ou morador..."
          class="pl-9"
        />
      </div>

      <div class="w-52">
        <select
          v-model="tipoFiltro"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option value="">Todas as categorias</option>
          <option v-for="t in tipos" :key="t.id_publico" :value="t.id_publico">
            {{ t.nome }}
          </option>
        </select>
      </div>

      <div class="w-44">
        <select
          v-model="situacaoFiltro"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option value="TODOS">Todas situações</option>
          <option value="ABERTO">Abertas</option>
          <option value="EM_ANDAMENTO">Em Andamento</option>
          <option value="RESOLVIDO">Resolvidas</option>
          <option value="FECHADO">Fechadas</option>
          <option value="CANCELADO">Canceladas</option>
        </select>
      </div>
    </div>

    <!-- Tabela de Ocorrências -->
    <div v-if="isPending" class="flex justify-center py-12">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="ocorrencias.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <AlertCircle class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
      <p class="font-medium text-foreground">Nenhuma ocorrência encontrada</p>
      <p class="text-sm">Nenhum chamado aberto com os filtros selecionados.</p>
    </div>

    <div v-else class="overflow-hidden rounded-xl border bg-card shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
            <tr>
              <th class="px-6 py-4">Protocolo</th>
              <th class="px-6 py-4">Abertura</th>
              <th class="px-6 py-4">Categoria</th>
              <th class="px-6 py-4">Motivo / Descrição</th>
              <th class="px-6 py-4">Unidade / Solicitante</th>
              <th class="px-6 py-4">Situação</th>
              <th class="px-6 py-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="o in ocorrencias"
              :key="o.id_publico"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 font-mono font-semibold text-primary whitespace-nowrap">
                #{{ o.numero_protocolo }}
              </td>
              <td class="px-6 py-4 text-xs text-muted-foreground whitespace-nowrap">
                {{ formatarData(o.aberto_em) }}
              </td>
              <td class="px-6 py-4">
                <Badge variant="secondary">{{ o.tipo?.nome }}</Badge>
              </td>
              <td class="px-6 py-4 max-w-sm">
                <div class="font-semibold text-foreground line-clamp-1">{{ o.motivo }}</div>
                <div class="text-xs text-muted-foreground line-clamp-1">{{ o.descricao }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-foreground">
                  {{
                    o.unidades?.[0]?.unidade
                      ? `Apto ${o.unidades[0].unidade.codigo}`
                      : 'Condomínio / Geral'
                  }}
                </div>
                <div class="text-xs text-muted-foreground">
                  {{
                    o.registrado_por?.pessoa?.nome_completo ??
                    o.registrado_por?.email ??
                    'Administração'
                  }}
                </div>
              </td>
              <td class="px-6 py-4">
                <Badge
                  :variant="getSituacaoBadge(o.situacao).variant"
                  :class="getSituacaoBadge(o.situacao).class"
                >
                  {{ getSituacaoBadge(o.situacao).label }}
                </Badge>
              </td>
              <td class="px-6 py-4 text-right">
                <Button
                  variant="outline"
                  size="sm"
                  class="gap-1.5"
                  @click="
                    router.push({ name: 'admin-ocorrencia-detalhe', params: { id: o.id_publico } })
                  "
                >
                  <MessageSquare class="h-3.5 w-3.5" />
                  Atender
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div
        v-if="meta && meta.ultima_pagina > 1"
        class="flex items-center justify-between border-t px-6 py-3"
      >
        <p class="text-xs text-muted-foreground">Total de {{ meta.total }} ocorrências</p>
        <div class="flex gap-1">
          <Button variant="outline" size="sm" :disabled="pagina <= 1" @click="pagina--"
            >Anterior</Button
          >
          <Button
            variant="outline"
            size="sm"
            :disabled="pagina >= meta.ultima_pagina"
            @click="pagina++"
            >Próxima</Button
          >
        </div>
      </div>
    </div>

    <!-- Modal Nova Ocorrência Administrativa -->
    <Dialog :open="modalNovaAberto" @update:open="modalNovaAberto = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Registrar Nova Ocorrência</DialogTitle>
          <DialogDescription>
            Abertura de chamado de manutenção, perturbação ou vistoria pela administração.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-4 py-2" @submit.prevent="mutacaoCriar.mutate()">
          <div
            v-if="erroModal"
            class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive font-medium"
          >
            {{ erroModal }}
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-2">
              <Label for="oco-tipo">Categoria / Tipo *</Label>
              <select
                id="oco-tipo"
                v-model="formulario.tipo_id"
                required
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option v-for="t in tipos" :key="t.id_publico" :value="t.id_publico">
                  {{ t.nome }}
                </option>
              </select>
            </div>

            <div class="space-y-2">
              <Label for="oco-unidade">Unidade Relacionada</Label>
              <select
                id="oco-unidade"
                v-model="formulario.unidade_id"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option value="">Área comum / Nenhuma específica</option>
                <option v-for="u in unidades" :key="u.id_publico" :value="u.id_publico">
                  Apto {{ u.codigo }} {{ u.bloco ? `• ${u.bloco.nome}` : '' }}
                </option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="oco-motivo">Título / Motivo do Chamado *</Label>
            <Input
              id="oco-motivo"
              v-model="formulario.motivo"
              required
              placeholder="Ex: Lâmpada do hall do 2º andar queimada"
            />
          </div>

          <div class="space-y-2">
            <Label for="oco-desc">Descrição Detalhada *</Label>
            <textarea
              id="oco-desc"
              v-model="formulario.descricao"
              required
              rows="4"
              placeholder="Descreva detalhadamente o ocorrido, localização exata e medidas solicitadas..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
          </div>

          <DialogFooter class="pt-2">
            <Button type="button" variant="outline" @click="modalNovaAberto = false"
              >Cancelar</Button
            >
            <Button type="submit" :disabled="mutacaoCriar.isPending.value"
              >Salvar Ocorrência</Button
            >
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
