<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  Building2,
  Car,
  Check,
  FileText,
  History,
  Info,
  LoaderCircle,
  Mail,
  Pencil,
  Phone,
  Plus,
  Power,
  PowerOff,
  Save,
  Search,
  Users,
  X,
} from '@lucide/vue'
import { computed, nextTick, reactive, ref, watch } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import HistoricoSheet from '@/components/app/crud/HistoricoSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { errosDeCampos, mensagemDeErro } from '@/lib/http-error'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import * as blocosApi from '@/modules/cadastros/api/blocos.api'
import * as unidadesApi from '@/modules/cadastros/api/unidades.api'
import type { Bloco } from '@/modules/cadastros/types/bloco.types'
import type { Unidade } from '@/modules/cadastros/types/unidade.types'

const autenticacao = useAutenticacaoStore()
const filaCliente = useQueryClient()

const podeCadastrar = computed(() => autenticacao.temPermissao('cadastros.blocos.cadastrar'))
const podeEditar = computed(() => autenticacao.temPermissao('cadastros.blocos.editar'))
const podeInativar = computed(() => autenticacao.temPermissao('cadastros.blocos.inativar'))

// Filtros de busca e status
const busca = ref('')
const filtroStatus = ref<'TODOS' | 'ATIVOS' | 'INATIVOS'>('TODOS')

// Query de blocos
const { data, isPending } = useQuery({
  queryKey: ['blocos', busca],
  queryFn: async () => {
    const res = await blocosApi.listarBlocos({
      busca: busca.value || undefined,
      pagina: 1,
    })
    return res.dados
  },
})

// Blocos filtrados
const blocos = computed(() => {
  const lista = data.value ?? []
  if (filtroStatus.value === 'ATIVOS') {
    return lista.filter((b) => b.ativo)
  }
  if (filtroStatus.value === 'INATIVOS') {
    return lista.filter((b) => !b.ativo)
  }
  return lista
})

// Bloco selecionado
const blocoSelecionadoId = ref<string | null>(null)

// Quando os dados carregarem pela primeira vez, seleciona o primeiro bloco
watch(
  blocos,
  (novosBlocos) => {
    if (novosBlocos.length > 0 && !blocoSelecionadoId.value) {
      blocoSelecionadoId.value = novosBlocos[0]?.id_publico ?? null
    } else if (
      novosBlocos.length > 0 &&
      !novosBlocos.some((b) => b.id_publico === blocoSelecionadoId.value)
    ) {
      blocoSelecionadoId.value = novosBlocos[0]?.id_publico ?? null
    }
  },
  { immediate: true },
)

// Query detalhada do bloco selecionado com apartamentos, moradores e veículos
const { data: blocoDetalhes, isPending: carregandoDetalhes } = useQuery({
  queryKey: ['bloco-detalhes', blocoSelecionadoId],
  queryFn: async () => {
    if (!blocoSelecionadoId.value) return null
    return await blocosApi.mostrarBloco(blocoSelecionadoId.value)
  },
  enabled: computed(() => Boolean(blocoSelecionadoId.value)),
})

const blocoAtivo = computed(() => {
  return blocoDetalhes.value ?? blocos.value.find((b) => b.id_publico === blocoSelecionadoId.value)
})

// Apartamentos agrupados por andar (de 1 até N)
const andaresAgrupados = computed(() => {
  const unidades = blocoDetalhes.value?.unidades ?? []
  const grupos: Record<number, Unidade[]> = {}

  // Determina quantidade de andares do bloco
  const totalAndares =
    blocoAtivo.value?.andares ??
    blocoAtivo.value?.quantidade_andares ??
    Math.max(1, ...unidades.map((u) => u.andar ?? u.numero_andar ?? 1))

  // Inicializa todos os andares de 1 até totalAndares
  for (let andar = 1; andar <= totalAndares; andar++) {
    grupos[andar] = []
  }

  // Distribui os apartamentos nos andares
  unidades.forEach((unidade) => {
    const andar = unidade.andar ?? unidade.numero_andar ?? 1
    if (!grupos[andar]) {
      grupos[andar] = []
    }
    grupos[andar]!.push(unidade)
  })

  // Ordena os apartamentos dentro de cada andar por código
  Object.keys(grupos).forEach((andarStr) => {
    const andarNum = Number(andarStr)
    const lista = grupos[andarNum]
    if (lista) {
      lista.sort((a, b) => a.codigo.localeCompare(b.codigo, undefined, { numeric: true }))
    }
  })

  // Converte para lista ordenada por número do andar (1, 2, 3...)
  // No template, usamos flex-col-reverse para renderizar de baixo para cima (1 na base, N no topo)!
  return Object.keys(grupos)
    .map(Number)
    .sort((a, b) => a - b)
    .map((andar) => ({
      andar,
      rotulo: `${andar}º Andar`,
      apartamentos: grupos[andar] ?? [],
    }))
})

// Apartamento selecionado para exibir card de detalhes
const apartamentoSelecionado = ref<Unidade | null>(null)
const painelDetalhesRef = ref<HTMLElement | null>(null)
const observacoesForm = ref('')
const salvandoObservacoes = ref(false)
const observacaoSalvaFeedback = ref(false)

async function selecionarApartamento(apto: Unidade): Promise<void> {
  if (apartamentoSelecionado.value?.id_publico === apto.id_publico) {
    apartamentoSelecionado.value = null
  } else {
    apartamentoSelecionado.value = apto
    observacoesForm.value = apto.observacoes ?? ''
    observacaoSalvaFeedback.value = false

    await nextTick()
    if (painelDetalhesRef.value) {
      painelDetalhesRef.value.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  }
}

async function salvarObservacoes(): Promise<void> {
  if (!apartamentoSelecionado.value) return
  salvandoObservacoes.value = true
  observacaoSalvaFeedback.value = false

  try {
    const unidadeAtualizada = await unidadesApi.atualizarObservacoesUnidade(
      apartamentoSelecionado.value.id_publico,
      observacoesForm.value || null,
    )

    // Atualiza o estado local reativamente
    apartamentoSelecionado.value.observacoes = unidadeAtualizada.observacoes
    if (blocoDetalhes.value?.unidades) {
      const idx = blocoDetalhes.value.unidades.findIndex(
        (u) => u.id_publico === unidadeAtualizada.id_publico,
      )
      if (idx !== -1 && blocoDetalhes.value.unidades[idx]) {
        blocoDetalhes.value.unidades[idx]!.observacoes = unidadeAtualizada.observacoes
      }
    }

    observacaoSalvaFeedback.value = true
    setTimeout(() => {
      observacaoSalvaFeedback.value = false
    }, 3000)
  } catch (err) {
    alert(mensagemDeErro(err))
  } finally {
    salvandoObservacoes.value = false
  }
}

// Modal de Criação / Edição de Bloco
const sheetFormAberto = ref(false)
const blocoEmEdicao = ref<Bloco | null>(null)
const salvandoBloco = ref(false)
const erroFormulario = ref('')
const errosCampos = reactive<Record<string, string>>({})

const form = reactive({
  nome: '',
  apelido: '',
  andares: 4,
  apartamentos_por_andar: 4,
  ativo: true,
  observacoes: '',
})

function limparErrosForm(): void {
  erroFormulario.value = ''
  Object.keys(errosCampos).forEach((k) => delete errosCampos[k])
}

function abrirModalCriacao(): void {
  blocoEmEdicao.value = null
  form.nome = ''
  form.apelido = ''
  form.andares = 4
  form.apartamentos_por_andar = 4
  form.ativo = true
  form.observacoes = ''
  limparErrosForm()
  sheetFormAberto.value = true
}

function abrirModalEdicao(bloco: Bloco, event?: Event): void {
  if (event) event.stopPropagation()
  blocoEmEdicao.value = bloco
  form.nome = bloco.nome
  form.apelido = bloco.apelido ?? ''
  form.andares = bloco.andares ?? bloco.quantidade_andares ?? 1
  form.apartamentos_por_andar = bloco.apartamentos_por_andar ?? 1
  form.ativo = bloco.ativo
  form.observacoes = bloco.observacoes ?? ''
  limparErrosForm()
  sheetFormAberto.value = true
}

async function submeterFormBloco(): Promise<void> {
  limparErrosForm()
  salvandoBloco.value = true

  const payload: Record<string, unknown> = {
    nome: form.nome,
    apelido: form.apelido.trim() ? form.apelido.trim() : null,
    andares: Number(form.andares),
    quantidade_andares: Number(form.andares),
    apartamentos_por_andar: Number(form.apartamentos_por_andar),
    observacoes: form.observacoes.trim() ? form.observacoes.trim() : null,
  }

  try {
    if (blocoEmEdicao.value) {
      await blocosApi.atualizarBloco(blocoEmEdicao.value.id_publico, payload)
    } else {
      const novo = await blocosApi.criarBloco(payload)
      blocoSelecionadoId.value = novo.id_publico
    }

    await filaCliente.invalidateQueries({ queryKey: ['blocos'] })
    await filaCliente.invalidateQueries({ queryKey: ['bloco-detalhes'] })
    sheetFormAberto.value = false
  } catch (erro) {
    erroFormulario.value = mensagemDeErro(erro)
    Object.assign(errosCampos, errosDeCampos(erro))
  } finally {
    salvandoBloco.value = false
  }
}

// Inativação / Reativação
const mutacaoInativar = useMutation({
  mutationFn: (id: string) => blocosApi.inativarBloco(id),
  onSuccess: async () => {
    await filaCliente.invalidateQueries({ queryKey: ['blocos'] })
    await filaCliente.invalidateQueries({ queryKey: ['bloco-detalhes'] })
  },
  onError: (erro) => alert(mensagemDeErro(erro)),
})

const mutacaoReativar = useMutation({
  mutationFn: (id: string) => blocosApi.reativarBloco(id),
  onSuccess: async () => {
    await filaCliente.invalidateQueries({ queryKey: ['blocos'] })
    await filaCliente.invalidateQueries({ queryKey: ['bloco-detalhes'] })
  },
  onError: (erro) => alert(mensagemDeErro(erro)),
})

function alternarStatusBloco(bloco: Bloco, event: Event): void {
  event.stopPropagation()
  if (bloco.ativo) {
    if (confirm(`Deseja realmente inativar o bloco "${bloco.nome}"?`)) {
      mutacaoInativar.mutate(bloco.id_publico)
    }
  } else {
    mutacaoReativar.mutate(bloco.id_publico)
  }
}

// Histórico
const historicoAberto = ref(false)
const blocoHistorico = ref<Bloco | null>(null)

function abrirHistorico(bloco: Bloco, event: Event): void {
  event.stopPropagation()
  blocoHistorico.value = bloco
  historicoAberto.value = true
}

const buscarHistoricoFn = computed(() => {
  if (!blocoHistorico.value) return null
  return () => blocosApi.historicoBloco(blocoHistorico.value!.id_publico)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Cabeçalho Principal -->
    <PageHeader
      title="Blocos"
      description="Gerenciamento visual e interativo dos edifícios, andares e apartamentos do condomínio."
      eyebrow="Cadastros"
    >
      <template #actions>
        <Button v-if="podeCadastrar" class="gap-2" @click="abrirModalCriacao">
          <Plus class="size-4" />
          Novo bloco
        </Button>
      </template>
    </PageHeader>

    <!-- Barra de Filtros e Busca -->
    <div
      class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-card p-3 rounded-lg border shadow-xs"
    >
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
        <Input
          v-model="busca"
          placeholder="Buscar por nome ou apelido do bloco..."
          class="pl-9 h-9"
        />
      </div>

      <div class="flex items-center gap-2">
        <span class="text-xs text-muted-foreground font-medium hidden sm:inline">Exibir:</span>
        <div class="flex rounded-md border bg-muted/40 p-0.5 text-xs">
          <button
            type="button"
            class="px-3 py-1 font-medium rounded-sm transition-all"
            :class="
              filtroStatus === 'TODOS'
                ? 'bg-background text-foreground shadow-xs'
                : 'text-muted-foreground hover:text-foreground'
            "
            @click="filtroStatus = 'TODOS'"
          >
            Todos ({{ (data ?? []).length }})
          </button>
          <button
            type="button"
            class="px-3 py-1 font-medium rounded-sm transition-all"
            :class="
              filtroStatus === 'ATIVOS'
                ? 'bg-background text-foreground shadow-xs'
                : 'text-muted-foreground hover:text-foreground'
            "
            @click="filtroStatus = 'ATIVOS'"
          >
            Ativos ({{ (data ?? []).filter((b) => b.ativo).length }})
          </button>
          <button
            type="button"
            class="px-3 py-1 font-medium rounded-sm transition-all"
            :class="
              filtroStatus === 'INATIVOS'
                ? 'bg-background text-foreground shadow-xs'
                : 'text-muted-foreground hover:text-foreground'
            "
            @click="filtroStatus = 'INATIVOS'"
          >
            Inativos ({{ (data ?? []).filter((b) => !b.ativo).length }})
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State de Blocos -->
    <div
      v-if="isPending"
      class="flex flex-col items-center justify-center p-12 bg-card rounded-lg border text-muted-foreground gap-3"
    >
      <LoaderCircle class="size-8 animate-spin text-primary" />
      <p class="text-sm">Carregando edifícios do condomínio...</p>
    </div>

    <!-- Empty State Geral -->
    <div
      v-else-if="blocos.length === 0"
      class="flex flex-col items-center justify-center p-12 bg-card rounded-lg border border-dashed text-center gap-3"
    >
      <Building2 class="size-12 text-muted-foreground/60" />
      <h3 class="text-base font-semibold">Nenhum bloco encontrado</h3>
      <p class="text-sm text-muted-foreground max-w-sm">
        {{
          busca
            ? `Nenhum resultado corresponde à busca "${busca}".`
            : 'Nenhum bloco foi cadastrado ainda no condomínio.'
        }}
      </p>
      <Button v-if="podeCadastrar && !busca" class="mt-2 gap-2" @click="abrirModalCriacao">
        <Plus class="size-4" />
        Cadastrar primeiro bloco
      </Button>
    </div>

    <!-- Grid Visual de Prédios -->
    <div v-else class="space-y-6">
      <div
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
      >
        <div
          v-for="bloco in blocos"
          :key="bloco.id_publico"
          class="group relative flex flex-col justify-between rounded-xl border bg-card text-card-foreground transition-all duration-200 cursor-pointer overflow-hidden"
          :class="[
            blocoSelecionadoId === bloco.id_publico
              ? 'ring-2 ring-primary border-primary shadow-md bg-primary/5 dark:bg-primary/10'
              : 'hover:border-primary/50 hover:shadow-sm',
            !bloco.ativo && 'opacity-70 bg-muted/30',
          ]"
          @click="blocoSelecionadoId = bloco.id_publico"
        >
          <!-- Topo do Prédio (Crown / Telhado estilizado com apelido) -->
          <div
            class="px-3.5 pt-3 pb-2 border-b transition-colors"
            :class="
              blocoSelecionadoId === bloco.id_publico
                ? 'bg-primary/10 border-primary/20'
                : 'bg-muted/40 border-border group-hover:bg-muted/60'
            "
          >
            <div class="flex items-center justify-between gap-1.5">
              <span
                class="text-xs font-semibold truncate tracking-tight"
                :class="
                  bloco.apelido
                    ? 'text-foreground font-bold'
                    : 'text-muted-foreground/80 italic font-normal'
                "
                :title="bloco.apelido || 'Sem apelido'"
              >
                {{ bloco.apelido || '—' }}
              </span>

              <Badge
                :variant="bloco.ativo ? 'default' : 'secondary'"
                class="text-[10px] px-1.5 py-0 h-4 uppercase font-semibold shrink-0"
              >
                {{ bloco.ativo ? 'Ativo' : 'Inativo' }}
              </Badge>
            </div>
          </div>

          <!-- Centro do Prédio: Ilustração Arquitetônica SVG + Resumo -->
          <div class="px-4 py-4 flex flex-col items-center text-center gap-3">
            <!-- Ilustração SVG Moderna de Edifício com Janelas -->
            <div
              class="relative flex items-center justify-center size-20 rounded-xl transition-transform duration-200 group-hover:scale-105"
              :class="
                blocoSelecionadoId === bloco.id_publico
                  ? 'bg-primary/15 text-primary'
                  : 'bg-muted text-muted-foreground group-hover:text-foreground'
              "
            >
              <svg
                viewBox="0 0 64 64"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="size-14 drop-shadow-xs"
              >
                <!-- Antena do topo -->
                <line
                  x1="32"
                  y1="4"
                  x2="32"
                  y2="12"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <circle cx="32" cy="4" r="2" fill="currentColor" />

                <!-- Silhueta da Torre Principal -->
                <rect
                  x="14"
                  y="12"
                  width="36"
                  height="48"
                  rx="3"
                  stroke="currentColor"
                  stroke-width="2.2"
                  fill="currentColor"
                  fill-opacity="0.1"
                />

                <!-- Janelas iluminadas arquitetônicas -->
                <rect x="19" y="18" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="29" y="18" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="39" y="18" width="6" height="5" rx="1" fill="currentColor" />

                <rect x="19" y="27" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="29" y="27" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="39" y="27" width="6" height="5" rx="1" fill="currentColor" />

                <rect x="19" y="36" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="29" y="36" width="6" height="5" rx="1" fill="currentColor" />
                <rect x="39" y="36" width="6" height="5" rx="1" fill="currentColor" />

                <!-- Porta da entrada principal -->
                <path
                  d="M27 60V47H37V60"
                  stroke="currentColor"
                  stroke-width="2.2"
                  stroke-linecap="round"
                />
              </svg>

              <!-- Indicador de Seleção Ativa -->
              <span
                v-if="blocoSelecionadoId === bloco.id_publico"
                class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-xs text-[10px]"
              >
                <Check class="size-3" />
              </span>
            </div>

            <!-- Resumo de Andares e Apartamentos -->
            <div class="space-y-0.5">
              <p class="text-xs font-medium text-muted-foreground">
                {{ bloco.andares ?? bloco.quantidade_andares ?? 1 }} andares •
                {{ bloco.apartamentos_por_andar ?? 1 }} aptos/andar
              </p>
            </div>
          </div>

          <!-- Base do Prédio: Nome Visível + Ações Rápidas -->
          <div class="px-3.5 py-2.5 border-t bg-muted/20 flex items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
              <span class="font-bold text-sm text-foreground block truncate" :title="bloco.nome">
                {{ bloco.nome }}
              </span>
            </div>

            <!-- Botões de Ação sem poluir o card -->
            <div class="flex items-center gap-1 shrink-0">
              <Button
                v-if="podeEditar"
                variant="ghost"
                size="icon"
                class="size-7 text-muted-foreground hover:text-foreground"
                title="Editar bloco"
                @click="(e: MouseEvent) => abrirModalEdicao(bloco, e)"
              >
                <Pencil class="size-3.5" />
              </Button>

              <Button
                v-if="podeInativar"
                variant="ghost"
                size="icon"
                class="size-7 text-muted-foreground hover:text-destructive"
                :title="bloco.ativo ? 'Inativar bloco' : 'Reativar bloco'"
                @click="(e: MouseEvent) => alternarStatusBloco(bloco, e)"
              >
                <PowerOff v-if="bloco.ativo" class="size-3.5 text-muted-foreground" />
                <Power v-else class="size-3.5 text-emerald-600" />
              </Button>

              <Button
                variant="ghost"
                size="icon"
                class="size-7 text-muted-foreground hover:text-foreground"
                title="Histórico de alterações"
                @click="(e: MouseEvent) => abrirHistorico(bloco, e)"
              >
                <History class="size-3.5" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- PAINEL DA ESTRUTURA DO EDIFÍCIO (ANDARES DE BAIXO PARA CIMA) -->
      <Card v-if="blocoAtivo" class="overflow-hidden border shadow-sm">
        <CardHeader class="pb-3 border-b bg-muted/30">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <div
                class="flex items-center justify-center size-8 rounded-lg bg-primary/10 text-primary"
              >
                <Building2 class="size-5" />
              </div>
              <div>
                <CardTitle class="text-base font-bold flex items-center gap-2">
                  Estrutura do Edifício — {{ blocoAtivo.nome }}
                  <Badge v-if="blocoAtivo.apelido" variant="outline" class="font-normal text-xs">
                    {{ blocoAtivo.apelido }}
                  </Badge>
                </CardTitle>
                <p class="text-xs text-muted-foreground">
                  Visão arquitetônica dos andares e apartamentos dispostos do 1º ao último andar (de
                  cima para baixo).
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 text-xs text-muted-foreground">
              <span class="flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-emerald-500 inline-block"></span>
                Ocupado
              </span>
              <span class="flex items-center gap-1.5">
                <span
                  class="size-2 rounded-full bg-slate-300 dark:bg-slate-700 inline-block"
                ></span>
                Vago
              </span>
              <Badge variant="secondary" class="font-normal text-xs">
                Total: {{ blocoDetalhes?.unidades?.length ?? 0 }} apartamentos
              </Badge>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-5 space-y-6">
          <div
            v-if="carregandoDetalhes"
            class="flex items-center justify-center py-12 gap-3 text-sm text-muted-foreground"
          >
            <LoaderCircle class="size-6 animate-spin text-primary" />
            Carregando apartamentos e moradores...
          </div>

          <div
            v-else-if="andaresAgrupados.length === 0"
            class="text-center py-8 text-sm text-muted-foreground"
          >
            Nenhum apartamento cadastrado neste edifício.
          </div>

          <!-- Disposição vertical de cima para baixo: 1º andar no topo até o último andar -->
          <div v-else class="flex flex-col gap-3.5">
            <div
              v-for="andar in andaresAgrupados"
              :key="andar.andar"
              class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 rounded-lg border bg-card/60 hover:bg-muted/30 transition-colors"
            >
              <!-- Identificação do Andar -->
              <div class="w-24 shrink-0 flex items-center gap-2">
                <Badge
                  variant="outline"
                  class="font-semibold text-xs tracking-wide bg-background py-1 px-2 border-border/80"
                >
                  {{ andar.rotulo }}
                </Badge>
              </div>

              <!-- Linha Horizontal de Apartamentos -->
              <div class="flex flex-wrap items-center gap-2.5 flex-1">
                <button
                  v-for="apto in andar.apartamentos"
                  :key="apto.id_publico"
                  type="button"
                  class="relative group/apto flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs font-medium transition-all shadow-2xs select-none"
                  :class="[
                    apartamentoSelecionado?.id_publico === apto.id_publico
                      ? 'border-primary ring-2 ring-primary/40 bg-primary/10 text-primary font-bold shadow-xs'
                      : 'border-border bg-background hover:border-primary/50 hover:bg-accent/40 text-foreground',
                  ]"
                  @click="selecionarApartamento(apto)"
                >
                  <!-- Indicador de Ocupação (Bolinha de status) -->
                  <span
                    class="size-2 rounded-full shrink-0"
                    :class="
                      apto.situacao_ocupacao === 'OCUPADO'
                        ? 'bg-emerald-500'
                        : 'bg-muted-foreground/30'
                    "
                  />

                  <!-- Número do Apartamento -->
                  <span>Apto {{ apto.codigo }}</span>

                  <!-- Badges Informativos: Moradores e Veículos -->
                  <div
                    class="flex items-center gap-1.5 text-[11px] text-muted-foreground pl-1 border-l border-border/60"
                  >
                    <span
                      class="flex items-center gap-0.5"
                      :class="
                        (apto.moradores?.length ?? 0) > 0
                          ? 'text-primary font-semibold'
                          : 'opacity-60'
                      "
                      title="Moradores vinculados"
                    >
                      <Users class="size-3" />
                      {{ apto.moradores?.length ?? 0 }}
                    </span>

                    <span
                      class="flex items-center gap-0.5"
                      :class="
                        (apto.veiculos?.length ?? 0) > 0
                          ? 'text-blue-600 dark:text-blue-400 font-semibold'
                          : 'opacity-60'
                      "
                      title="Veículos cadastrados"
                    >
                      <Car class="size-3" />
                      {{ apto.veiculos?.length ?? 0 }}
                    </span>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Base do Edifício (Térreo / Entrada Gráfica Sutil) -->
          <div class="pt-2 flex items-center justify-center">
            <div class="w-full max-w-xs h-1.5 bg-muted rounded-full mx-auto" />
          </div>
        </CardContent>
      </Card>

      <!-- PAINEL EXPANSÍVEL: DETALHES DO APARTAMENTO SELECIONADO -->
      <div v-if="apartamentoSelecionado" ref="painelDetalhesRef" class="scroll-mt-6">
        <Card
          class="border-primary/40 bg-gradient-to-b from-card to-primary/5 shadow-md transition-all animate-in fade-in-50 duration-300"
        >
          <CardHeader class="pb-3 border-b flex flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div
                class="flex items-center justify-center size-9 rounded-lg bg-primary text-primary-foreground font-bold text-sm"
              >
                {{ apartamentoSelecionado.codigo }}
              </div>
              <div>
                <CardTitle class="text-base font-bold flex items-center gap-2">
                  Apartamento {{ apartamentoSelecionado.codigo }}
                  <Badge
                    :variant="
                      apartamentoSelecionado.situacao_ocupacao === 'OCUPADO'
                        ? 'default'
                        : 'secondary'
                    "
                    class="text-xs uppercase"
                  >
                    {{ apartamentoSelecionado.situacao_ocupacao }}
                  </Badge>
                </CardTitle>
                <p class="text-xs text-muted-foreground">
                  {{ apartamentoSelecionado.andar }}º Andar • Bloco {{ blocoAtivo?.nome }}
                </p>
              </div>
            </div>

            <Button
              variant="ghost"
              size="icon"
              class="size-8 text-muted-foreground hover:text-foreground"
              title="Fechar detalhes"
              @click="apartamentoSelecionado = null"
            >
              <X class="size-4" />
            </Button>
          </CardHeader>

          <CardContent class="pt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- SEÇÃO 1: MORADORES -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <h4
                  class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
                >
                  <Users class="size-3.5 text-primary" />
                  Moradores ({{ apartamentoSelecionado.moradores?.length ?? 0 }})
                </h4>
              </div>

              <div
                v-if="
                  !apartamentoSelecionado.moradores || apartamentoSelecionado.moradores.length === 0
                "
                class="flex flex-col items-center justify-center p-6 rounded-lg border border-dashed bg-muted/20 text-center gap-2"
              >
                <Users class="size-7 text-muted-foreground/40" />
                <p class="text-xs text-muted-foreground font-medium">Nenhum morador vinculado</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="morador in apartamentoSelecionado.moradores"
                  :key="morador.id"
                  class="p-2.5 rounded-lg border bg-background text-xs space-y-1 shadow-2xs"
                >
                  <div class="flex items-center justify-between gap-1">
                    <span class="font-semibold text-foreground truncate">
                      {{ morador.nome || morador.nome_completo }}
                    </span>
                    <Badge
                      v-if="morador.tipo_vinculo"
                      variant="outline"
                      class="text-[10px] px-1 py-0 uppercase"
                    >
                      {{ morador.tipo_vinculo }}
                    </Badge>
                  </div>

                  <div
                    v-if="morador.email"
                    class="flex items-center gap-1.5 text-muted-foreground truncate"
                  >
                    <Mail class="size-3 shrink-0" />
                    <span class="truncate">{{ morador.email }}</span>
                  </div>

                  <div
                    v-if="morador.telefone"
                    class="flex items-center gap-1.5 text-muted-foreground"
                  >
                    <Phone class="size-3 shrink-0" />
                    <span>{{ morador.telefone }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- SEÇÃO 2: VEÍCULOS -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <h4
                  class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
                >
                  <Car class="size-3.5 text-blue-600 dark:text-blue-400" />
                  Veículos ({{ apartamentoSelecionado.veiculos?.length ?? 0 }})
                </h4>
              </div>

              <div
                v-if="
                  !apartamentoSelecionado.veiculos || apartamentoSelecionado.veiculos.length === 0
                "
                class="flex flex-col items-center justify-center p-6 rounded-lg border border-dashed bg-muted/20 text-center gap-2"
              >
                <Car class="size-7 text-muted-foreground/40" />
                <p class="text-xs text-muted-foreground font-medium">Nenhum veículo cadastrado</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="veiculo in apartamentoSelecionado.veiculos"
                  :key="veiculo.id_publico"
                  class="p-2.5 rounded-lg border bg-background text-xs space-y-1 shadow-2xs"
                >
                  <div class="flex items-center justify-between gap-1">
                    <!-- Placa Estilizada Mercosul -->
                    <span
                      class="font-mono font-bold px-1.5 py-0.5 rounded border border-blue-600/30 bg-blue-50 dark:bg-blue-950/60 text-blue-900 dark:text-blue-200 tracking-wider text-[11px]"
                    >
                      {{ veiculo.placa }}
                    </span>
                    <span v-if="veiculo.cor" class="text-muted-foreground text-[11px]">
                      {{ veiculo.cor }}
                    </span>
                  </div>

                  <p class="font-medium text-foreground truncate">
                    {{ veiculo.modelo }}
                  </p>

                  <p v-if="veiculo.vaga" class="text-[11px] text-muted-foreground">
                    Vaga: {{ veiculo.vaga }}
                  </p>
                </div>
              </div>
            </div>

            <!-- SEÇÃO 3: OBSERVAÇÕES RÁPIDAS -->
            <div class="space-y-3 flex flex-col justify-between">
              <div class="space-y-2">
                <h4
                  class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
                >
                  <FileText class="size-3.5 text-amber-500" />
                  Observações do Apartamento
                </h4>

                <textarea
                  v-model="observacoesForm"
                  rows="4"
                  placeholder="Ex: Vazamento no banheiro em reparo, autorização de entrega na portaria..."
                  class="w-full rounded-md border border-input bg-background p-2.5 text-xs outline-none focus-visible:ring-2 focus-visible:ring-primary shadow-xs resize-none"
                />
              </div>

              <div class="flex items-center justify-between gap-2 pt-1">
                <span
                  v-if="observacaoSalvaFeedback"
                  class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1 animate-in fade-in-50"
                >
                  <Check class="size-3.5" />
                  Observação salva!
                </span>
                <span v-else class="text-[11px] text-muted-foreground">
                  Atualização em tempo real
                </span>

                <Button
                  size="sm"
                  class="h-8 gap-1.5 text-xs font-medium"
                  :disabled="salvandoObservacoes"
                  @click="salvarObservacoes"
                >
                  <LoaderCircle v-if="salvandoObservacoes" class="size-3.5 animate-spin" />
                  <Save v-else class="size-3.5" />
                  Salvar observação
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- MODAL: NOVO / EDITAR BLOCO -->
    <Sheet v-model:open="sheetFormAberto">
      <SheetContent
        side="center"
        class="max-h-[90vh] w-[calc(100vw-2rem)] max-w-lg overflow-y-auto rounded-lg p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-3">
          <SheetTitle class="text-base font-semibold text-foreground sm:text-lg">
            {{ blocoEmEdicao ? `Editar Bloco — ${blocoEmEdicao.nome}` : 'Novo Bloco' }}
          </SheetTitle>
        </SheetHeader>

        <form class="space-y-4 px-4 py-4" @submit.prevent="submeterFormBloco">
          <div
            v-if="erroFormulario"
            class="rounded-md bg-destructive/10 p-3 text-sm text-destructive"
            role="alert"
          >
            {{ erroFormulario }}
          </div>

          <!-- Nome * (Obrigatório) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold" for="bloco-nome">
              Nome <span class="text-destructive">*</span>
            </label>
            <Input
              id="bloco-nome"
              v-model="form.nome"
              placeholder="ex: Bloco A, Torre 1"
              required
              :aria-invalid="Boolean(errosCampos.nome)"
            />
            <p v-if="errosCampos.nome" class="text-xs text-destructive">
              {{ errosCampos.nome }}
            </p>
          </div>

          <!-- Apelido (Opcional) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold" for="bloco-apelido">
              Apelido
              <span class="text-xs text-muted-foreground font-normal">(Opcional)</span>
            </label>
            <Input
              id="bloco-apelido"
              v-model="form.apelido"
              placeholder="ex: Torre Sul, Edifício Ipê"
              :aria-invalid="Boolean(errosCampos.apelido)"
            />
            <p class="text-xs text-muted-foreground">
              Nome de fantasia ou identificação amigável do edifício.
            </p>
            <p v-if="errosCampos.apelido" class="text-xs text-destructive">
              {{ errosCampos.apelido }}
            </p>
          </div>

          <!-- Andares * e Apartamentos por andar * (LADO A LADO EM 2 COLUNAS) -->
          <div class="grid grid-cols-2 gap-3.5">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold" for="bloco-andares">
                Andares <span class="text-destructive">*</span>
              </label>
              <Input
                id="bloco-andares"
                v-model="form.andares"
                type="number"
                min="1"
                max="100"
                required
                :aria-invalid="Boolean(errosCampos.andares)"
              />
              <p v-if="errosCampos.andares" class="text-xs text-destructive">
                {{ errosCampos.andares }}
              </p>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold" for="bloco-aptos-andar">
                Apartamentos por andar <span class="text-destructive">*</span>
              </label>
              <Input
                id="bloco-aptos-andar"
                v-model="form.apartamentos_por_andar"
                type="number"
                min="1"
                max="50"
                required
                :aria-invalid="Boolean(errosCampos.apartamentos_por_andar)"
              />
              <p v-if="errosCampos.apartamentos_por_andar" class="text-xs text-destructive">
                {{ errosCampos.apartamentos_por_andar }}
              </p>
            </div>
          </div>

          <!-- Aviso de Geração Automática -->
          <div
            v-if="!blocoEmEdicao"
            class="flex items-start gap-2.5 p-3 rounded-lg bg-primary/10 border border-primary/20 text-xs text-foreground"
          >
            <Info class="size-4 text-primary shrink-0 mt-0.5" />
            <p>
              Ao cadastrar, o sistema gerará automaticamente
              <strong>{{ Number(form.andares) * Number(form.apartamentos_por_andar) }}</strong>
              apartamentos numerados sequencialmente (ex: 101, 102... 201, 202...).
            </p>
          </div>

          <!-- Observações -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold" for="bloco-obs">Observações</label>
            <textarea
              id="bloco-obs"
              v-model="form.observacoes"
              rows="3"
              placeholder="Informações adicionais sobre o bloco..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs outline-none focus-visible:ring-2 focus-visible:ring-primary shadow-xs"
            />
          </div>

          <SheetFooter class="pt-4 border-t flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              :disabled="salvandoBloco"
              @click="sheetFormAberto = false"
            >
              Cancelar
            </Button>
            <Button type="submit" :disabled="salvandoBloco">
              <LoaderCircle v-if="salvandoBloco" class="mr-2 size-4 animate-spin" />
              Salvar
            </Button>
          </SheetFooter>
        </form>
      </SheetContent>
    </Sheet>

    <!-- SHEET DE HISTÓRICO -->
    <HistoricoSheet
      :aberto="historicoAberto"
      :titulo="blocoHistorico?.nome ?? ''"
      query-key="bloco"
      :buscar="buscarHistoricoFn"
      @update:aberto="(v) => (historicoAberto = v)"
    />
  </div>
</template>
