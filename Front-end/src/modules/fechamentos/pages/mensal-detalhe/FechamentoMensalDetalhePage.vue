<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  AlertTriangle,
  ArrowDownLeft,
  ArrowLeft,
  ArrowUpRight,
  Building,
  Check,
  CheckCircle2,
  Copy,
  FileText,
  History,
  Layers,
  ListOrdered,
  LoaderCircle,
  Lock,
  Receipt,
  RotateCcw,
  Scale,
  Search,
  Sparkles,
  Users,
  Wallet,
} from '@lucide/vue'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { mensagemDeErro } from '@/lib/http-error'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import * as fechamentosApi from '@/modules/fechamentos/api/fechamentos.api'
import type { DespesaDisponivelItem } from '@/modules/fechamentos/types/fechamento.types'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const filaCliente = useQueryClient()

const identificador = computed(() => route.params.id as string)

const podeFechar = computed(() => autenticacao.temPermissao('fechamentos.mensais.fechar'))
const podeReabrir = computed(() => autenticacao.temPermissao('fechamentos.mensais.reabrir'))

// Abas de navegação
const abaAtiva = ref<'boletos' | 'despesas' | 'rateio' | 'reconciliacao' | 'historico'>('boletos')

// Filtros de busca
const buscaBoleto = ref('')
const buscaUnidade = ref('')
const buscaDespesa = ref('')

// Estado local de seleção de despesas para fechamento (quando o período está ABERTO)
interface DespesaEdicao extends DespesaDisponivelItem {
  selecionado: boolean
  valor_a_cobrar: number
}
const despesasParaFechar = ref<DespesaEdicao[]>([])
const dataVencimentoBoletos = ref('')

// Query dos detalhes do fechamento
const {
  data: fechamento,
  isPending,
  error,
} = useQuery({
  queryKey: ['fechamento-mensal-detalhe', identificador],
  queryFn: () => fechamentosApi.obterFechamento(identificador.value),
  enabled: computed(() => Boolean(identificador.value)),
})

const percentualFundoReserva = ref(10.0)

// Sincroniza despesas disponíveis e data de vencimento quando os dados do período chegam
watch(
  () => fechamento.value,
  (novoFechamento) => {
    if (!novoFechamento) return

    if (
      novoFechamento.percentual_fundo_reserva !== undefined &&
      novoFechamento.percentual_fundo_reserva !== null
    ) {
      percentualFundoReserva.value = Number(novoFechamento.percentual_fundo_reserva)
    }

    // Se estiver em aberto, aba padrão é a seleção de despesas / rateio
    if (novoFechamento.situacao === 'ABERTO') {
      abaAtiva.value = 'despesas'
    } else {
      abaAtiva.value = 'boletos'
    }

    if (novoFechamento.despesas_disponiveis) {
      despesasParaFechar.value = novoFechamento.despesas_disponiveis.map((d) => ({
        ...d,
        selecionado: !d.pago_com_fundo_reserva,
        valor_a_cobrar: d.pago_com_fundo_reserva ? 0 : Number(d.valor_pendente),
      }))
    }

    // Calcula dia 10 do mês subsequente como vencimento sugerido
    const anoNum = novoFechamento.ano
    const mesNum = novoFechamento.mes
    let proxMes = mesNum + 1
    let proxAno = anoNum
    if (proxMes > 12) {
      proxMes = 1
      proxAno++
    }
    dataVencimentoBoletos.value = `${proxAno}-${String(proxMes).padStart(2, '0')}-10`
  },
  { immediate: true },
)

function formatarMoeda(valor?: number | null): string {
  if (valor === undefined || valor === null) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor)
}

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '-'
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(dataStr))
}

function formatarDataSimples(dataStr?: string | null): string {
  if (!dataStr) return '-'
  return new Intl.DateTimeFormat('pt-BR').format(new Date(dataStr + 'T12:00:00'))
}

function voltar() {
  router.push({ name: 'admin-fechamentos-mensais' })
}

// Despesas filtradas para seleção
const despesasFiltradas = computed(() => {
  const termo = buscaDespesa.value.trim().toLowerCase()
  if (!termo) return despesasParaFechar.value
  return despesasParaFechar.value.filter(
    (d) =>
      d.descricao.toLowerCase().includes(termo) ||
      d.categoria.toLowerCase().includes(termo) ||
      d.fornecedor.toLowerCase().includes(termo),
  )
})

// Totais calculados dinamicamente das despesas selecionadas
const totalDespesasSelecionadas = computed(() => {
  return despesasParaFechar.value
    .filter((d) => d.selecionado)
    .reduce((soma, d) => soma + (Number(d.valor_a_cobrar) || 0), 0)
})

const totalDespesasOrdinarias = computed(() => {
  return despesasParaFechar.value
    .filter((d) => d.selecionado && !d.pago_com_fundo_reserva)
    .reduce((soma, d) => soma + (Number(d.valor_a_cobrar) || 0), 0)
})

const valorFundoReservaCalculado = computed(() => {
  const pct = Number(percentualFundoReserva.value) || 0
  return Math.round(totalDespesasOrdinarias.value * pct) / 100
})

const totalGeralParaBoletos = computed(() => {
  return totalDespesasOrdinarias.value + valorFundoReservaCalculado.value
})

const qtdDespesasSelecionadas = computed(() => {
  return despesasParaFechar.value.filter((d) => d.selecionado).length
})

const valorCotaEstimada = computed(() => {
  const participantes = fechamento.value?.rateio?.unidades_participantes || 36
  if (participantes <= 0) return 0
  return totalGeralParaBoletos.value / participantes
})

const valorCotaOrdinariaUnitaria = computed(() => {
  const participantes = fechamento.value?.rateio?.unidades_participantes || 36
  if (participantes <= 0) return 0
  return totalDespesasOrdinarias.value / participantes
})

const valorFundoReservaUnitario = computed(() => {
  const participantes = fechamento.value?.rateio?.unidades_participantes || 36
  if (participantes <= 0) return 0
  return valorFundoReservaCalculado.value / participantes
})

// Selecionar / Desmarcar todas as despesas
const todasSelecionadas = computed({
  get: () =>
    despesasParaFechar.value.length > 0 && despesasParaFechar.value.every((d) => d.selecionado),
  set: (val: boolean) => {
    for (const d of despesasParaFechar.value) {
      d.selecionado = val
    }
  },
})

// Boletos filtrados pela busca
const boletosFiltrados = computed(() => {
  if (!fechamento.value?.boletos) return []
  const termo = buscaBoleto.value.trim().toLowerCase()
  if (!termo) return fechamento.value.boletos

  return fechamento.value.boletos.filter((b) => {
    return (
      b.codigo_referencia.toLowerCase().includes(termo) ||
      b.unidade_codigo.toLowerCase().includes(termo) ||
      b.bloco_nome.toLowerCase().includes(termo) ||
      b.morador_nome.toLowerCase().includes(termo)
    )
  })
})

// Unidades filtradas pela busca
const unidadesFiltradas = computed(() => {
  if (!fechamento.value?.rateio?.unidades) return []
  const termo = buscaUnidade.value.trim().toLowerCase()
  if (!termo) return fechamento.value.rateio.unidades

  return fechamento.value.rateio.unidades.filter((u) => {
    return (
      u.codigo.toLowerCase().includes(termo) ||
      u.bloco.toLowerCase().includes(termo) ||
      u.responsavel.toLowerCase().includes(termo)
    )
  })
})

// Copiar código de referência do boleto
const copiadoId = ref<string | null>(null)
async function copiarCodigo(codigo: string) {
  try {
    await navigator.clipboard.writeText(codigo)
    copiadoId.value = codigo
    setTimeout(() => {
      copiadoId.value = null
    }, 2000)
  } catch {
    // fallback
  }
}

// Modal de Fechamento
const modalFecharAberto = ref(false)
const erroFechar = ref<string | null>(null)

const mutacaoFechar = useMutation({
  mutationFn: async () => {
    if (!fechamento.value) return

    // Prepara payload com despesas selecionadas e seus respectivos valores
    const despesasPayload = despesasParaFechar.value
      .filter((d) => d.selecionado && Number(d.valor_a_cobrar) > 0)
      .map((d) => ({
        movimentacao_id: d.id,
        valor_cobrar: Number(d.valor_a_cobrar),
      }))

    return await fechamentosApi.fecharPeriodo({
      periodo: fechamento.value.periodo,
      despesas: despesasPayload,
      data_vencimento: dataVencimentoBoletos.value || undefined,
      percentual_fundo_reserva: Number(percentualFundoReserva.value),
    })
  },
  onSuccess: (novosDados) => {
    filaCliente.invalidateQueries({ queryKey: ['fechamentos-mensais'] })
    filaCliente.invalidateQueries({ queryKey: ['fechamento-mensal-detalhe', identificador] })
    modalFecharAberto.value = false
    if (novosDados?.id_publico && novosDados.id_publico !== identificador.value) {
      router.replace({
        name: 'admin-fechamento-mensal-detalhe',
        params: { id: novosDados.id_publico },
      })
    }
  },
  onError: (err) => {
    erroFechar.value = mensagemDeErro(err)
  },
})

// Modal de Reabertura
const modalReabrirAberto = ref(false)
const motivoReabertura = ref('')
const erroReabrir = ref<string | null>(null)

const mutacaoReabrir = useMutation({
  mutationFn: async () => {
    if (!fechamento.value) return
    await fechamentosApi.reabrirFechamento(
      fechamento.value.id_publico,
      motivoReabertura.value.trim(),
    )
  },
  onSuccess: () => {
    filaCliente.invalidateQueries({ queryKey: ['fechamentos-mensais'] })
    filaCliente.invalidateQueries({ queryKey: ['fechamento-mensal-detalhe', identificador] })
    modalReabrirAberto.value = false
    motivoReabertura.value = ''
  },
  onError: (err) => {
    erroReabrir.value = mensagemDeErro(err)
  },
})
</script>

<template>
  <div class="space-y-6">
    <!-- Barra Superior de Navegação e Ações -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <Button variant="outline" size="sm" class="gap-1.5 text-xs" @click="voltar">
          <ArrowLeft class="size-3.5" />
          Voltar
        </Button>

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
              {{
                fechamento
                  ? `${fechamento.nome_mes} de ${fechamento.ano}`
                  : 'Carregando fechamento...'
              }}
            </h1>

            <template v-if="fechamento">
              <Badge
                v-if="fechamento.situacao === 'PUBLICADO'"
                variant="outline"
                class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 font-semibold"
              >
                <CheckCircle2 class="mr-1 size-3.5" />
                Fechado (v{{ fechamento.versao }})
              </Badge>
              <Badge
                v-else
                variant="outline"
                class="border-amber-500/30 bg-amber-500/10 text-amber-700 font-semibold"
              >
                <Sparkles class="mr-1 size-3.5" />
                Competência Aberta (Em Apuração)
              </Badge>
            </template>
          </div>

          <p v-if="fechamento" class="text-xs text-muted-foreground mt-0.5">
            Competência {{ fechamento.competencia }} •
            <span v-if="fechamento.publicado_em">
              Publicado em {{ formatarData(fechamento.publicado_em) }} por
              {{ fechamento.publicado_por }}
            </span>
            <span v-else>
              Apure as despesas do condomínio, selecione o que cobrar e gere os boletos dos
              moradores
            </span>
          </p>
        </div>
      </div>

      <!-- Ações do Fechamento -->
      <div v-if="fechamento" class="flex items-center gap-2">
        <Button
          v-if="fechamento.situacao === 'ABERTO' && podeFechar"
          variant="default"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs gap-1.5 shadow-sm"
          @click="modalFecharAberto = true"
        >
          <Lock class="size-3.5" />
          Fechar Mês & Gerar Boletos
        </Button>

        <Button
          v-if="fechamento.situacao === 'PUBLICADO' && podeReabrir"
          variant="outline"
          class="border-amber-500/40 text-amber-700 hover:bg-amber-500/10 font-medium text-xs gap-1.5"
          @click="modalReabrirAberto = true"
        >
          <RotateCcw class="size-3.5" />
          Reabrir Competência
        </Button>
      </div>
    </div>

    <!-- Indicador de Carregamento -->
    <div
      v-if="isPending"
      class="flex flex-col items-center justify-center py-20 text-muted-foreground"
    >
      <LoaderCircle class="size-8 animate-spin text-primary" />
      <span class="mt-3 text-sm">Carregando detalhes da competência...</span>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-destructive/20 bg-destructive/10 p-6 text-center text-sm text-destructive"
    >
      <AlertTriangle class="mx-auto size-6 mb-2" />
      Erro ao carregar detalhes: {{ mensagemDeErro(error) }}
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div v-else-if="fechamento" class="space-y-6">
      <!-- Cards KPI de Resumo do Mês -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <Card class="border-border/80 shadow-xs">
          <CardHeader class="pb-1.5 pt-3 px-4">
            <CardTitle
              class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
            >
              Saldo Inicial
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4 pb-3">
            <div class="text-lg font-bold tracking-tight text-foreground">
              {{ formatarMoeda(fechamento.saldo_inicial) }}
            </div>
            <p class="text-[10px] text-muted-foreground mt-0.5">Herdado do mês anterior</p>
          </CardContent>
        </Card>

        <Card class="border-border/80 shadow-xs">
          <CardHeader class="pb-1.5 pt-3 px-4">
            <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">
              (+) Entradas / Receitas
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4 pb-3">
            <div class="text-lg font-bold tracking-tight text-emerald-600">
              {{ formatarMoeda(fechamento.total_entradas) }}
            </div>
            <p class="text-[10px] text-muted-foreground mt-0.5">Arrecadações recebidas</p>
          </CardContent>
        </Card>

        <Card class="border-border/80 shadow-xs">
          <CardHeader class="pb-1.5 pt-3 px-4">
            <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">
              (-) Saídas / Despesas
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4 pb-3">
            <div class="text-lg font-bold tracking-tight text-rose-600">
              {{ formatarMoeda(fechamento.total_saidas) }}
            </div>
            <p class="text-[10px] text-muted-foreground mt-0.5">Despesas pagas no mês</p>
          </CardContent>
        </Card>

        <Card class="border-border/80 shadow-xs">
          <CardHeader class="pb-1.5 pt-3 px-4">
            <CardTitle
              class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
            >
              (=) Saldo Final
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4 pb-3">
            <div class="text-lg font-bold tracking-tight text-blue-600">
              {{ formatarMoeda(fechamento.saldo_final) }}
            </div>
            <p class="text-[10px] text-muted-foreground mt-0.5">Saldo bancário consolidado</p>
          </CardContent>
        </Card>

        <Card class="col-span-2 sm:col-span-1 border-primary/30 bg-primary/[0.02] shadow-xs">
          <CardHeader class="pb-1.5 pt-3 px-4">
            <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-primary">
              {{ fechamento.situacao === 'PUBLICADO' ? 'Total Cobrado' : 'Total a Ratear' }}
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4 pb-3">
            <div class="text-lg font-bold tracking-tight text-foreground">
              {{
                fechamento.situacao === 'PUBLICADO'
                  ? formatarMoeda(fechamento.total_rateado)
                  : formatarMoeda(totalGeralParaBoletos)
              }}
            </div>
            <p
              v-if="fechamento.situacao === 'PUBLICADO'"
              class="text-[10px] text-muted-foreground mt-0.5"
            >
              Ord:
              {{ formatarMoeda(fechamento.valor_despesas_ordinarias ?? fechamento.total_rateado) }}
              <span class="text-amber-700 dark:text-amber-400 font-medium">
                • Fundo ({{ fechamento.percentual_fundo_reserva ?? 10 }}%):
                {{ formatarMoeda(fechamento.valor_fundo_reserva ?? 0) }}
              </span>
            </p>
            <div v-else class="text-[10px] text-primary font-medium mt-0.5 space-y-0.5">
              <div>{{ formatarMoeda(valorCotaEstimada) }} / apto</div>
              <div class="text-[9px] text-muted-foreground">
                Ord: {{ formatarMoeda(valorCotaOrdinariaUnitaria) }} + Fundo ({{
                  percentualFundoReserva
                }}%): {{ formatarMoeda(valorFundoReservaUnitario) }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Abas de Navegação -->
      <div class="flex items-center gap-2 border-b overflow-x-auto">
        <!-- Aba 1: Boletos / Cobranças (Em destaque quando fechado) -->
        <button
          v-if="fechamento.situacao === 'PUBLICADO'"
          type="button"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-semibold transition-colors whitespace-nowrap"
          :class="
            abaAtiva === 'boletos'
              ? 'border-emerald-600 text-emerald-700'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
          @click="abaAtiva = 'boletos'"
        >
          <Receipt class="size-4" />
          Boletos dos Apartamentos
          <Badge
            variant="outline"
            class="ml-1 text-[10px] px-1.5 py-0 border-emerald-500/40 text-emerald-700 font-mono"
          >
            {{ fechamento.boletos?.length ?? 0 }}
          </Badge>
        </button>

        <!-- Aba 2: Seleção de Despesas para Cobrança (Quando ABERTO) ou Despesas Rateadas (Quando PUBLICADO) -->
        <button
          type="button"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-semibold transition-colors whitespace-nowrap"
          :class="
            abaAtiva === 'despesas'
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
          @click="abaAtiva = 'despesas'"
        >
          <ListOrdered class="size-4" />
          {{
            fechamento.situacao === 'ABERTO'
              ? 'Despesas para Rateio (Seleção & Parciais)'
              : 'Despesas Rateadas'
          }}
          <Badge variant="secondary" class="ml-1 text-[10px] px-1.5 py-0">
            {{
              fechamento.situacao === 'ABERTO'
                ? despesasParaFechar.length
                : (fechamento.movimentacoes?.length ?? 0)
            }}
          </Badge>
        </button>

        <!-- Aba 3: Memória de Rateio das Unidades -->
        <button
          type="button"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-semibold transition-colors whitespace-nowrap"
          :class="
            abaAtiva === 'rateio'
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
          @click="abaAtiva = 'rateio'"
        >
          <Building class="size-4" />
          Rateio por Unidade
          <Badge variant="secondary" class="ml-1 text-[10px] px-1.5 py-0">
            {{ fechamento.rateio?.unidades?.length ?? 0 }}
          </Badge>
        </button>

        <!-- Aba 4: Contas & Categorias -->
        <button
          type="button"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-semibold transition-colors whitespace-nowrap"
          :class="
            abaAtiva === 'reconciliacao'
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
          @click="abaAtiva = 'reconciliacao'"
        >
          <Scale class="size-4" />
          Contas & Categorias
        </button>

        <!-- Aba 5: Reaberturas (se houver) -->
        <button
          v-if="fechamento.reaberturas && fechamento.reaberturas.length > 0"
          type="button"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-semibold transition-colors whitespace-nowrap"
          :class="
            abaAtiva === 'historico'
              ? 'border-amber-500 text-amber-700'
              : 'border-transparent text-muted-foreground hover:text-foreground'
          "
          @click="abaAtiva = 'historico'"
        >
          <History class="size-4" />
          Reaberturas
          <Badge
            variant="outline"
            class="ml-1 text-[10px] px-1.5 py-0 border-amber-500/40 text-amber-700"
          >
            {{ fechamento.reaberturas.length }}
          </Badge>
        </button>
      </div>

      <!-- ABA: BOLETOS DOS APARTAMENTOS (QUANDO PUBLICADO) -->
      <div v-if="abaAtiva === 'boletos' && fechamento.situacao === 'PUBLICADO'" class="space-y-4">
        <!-- Barra de controle e busca de boletos -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative w-full max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              v-model="buscaBoleto"
              placeholder="Buscar por apto, bloco, morador ou código..."
              class="pl-9 h-9 text-xs"
            />
          </div>

          <div class="flex items-center gap-3 text-xs text-muted-foreground">
            <span
              >Total de boletos: <strong>{{ fechamento.boletos?.length ?? 0 }}</strong></span
            >
            <span>•</span>
            <span
              >Valor total emitido:
              <strong class="text-foreground">{{
                formatarMoeda(fechamento.total_rateado)
              }}</strong></span
            >
          </div>
        </div>

        <!-- Tabela de Boletos dos Moradores -->
        <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
          <table class="w-full text-xs text-left">
            <thead
              class="bg-muted/40 font-semibold text-muted-foreground border-b uppercase tracking-wider text-[11px]"
            >
              <tr>
                <th class="py-3 px-4">Código Referência</th>
                <th class="py-3 px-4">Apartamento</th>
                <th class="py-3 px-4">Morador / Sacado</th>
                <th class="py-3 px-4">Vencimento</th>
                <th class="py-3 px-4 text-center">Situação</th>
                <th class="py-3 px-4 text-right">Composição e Valor Total</th>
                <th class="py-3 px-4 text-center">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="boleto in boletosFiltrados"
                :key="boleto.id_publico"
                class="hover:bg-muted/30 transition-colors"
              >
                <!-- Código de Referência com Botão Copiar -->
                <td class="py-3 px-4 font-mono font-medium text-foreground whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span>{{ boleto.codigo_referencia }}</span>
                    <button
                      type="button"
                      class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                      title="Copiar código de referência"
                      @click="copiarCodigo(boleto.codigo_referencia)"
                    >
                      <Check
                        v-if="copiadoId === boleto.codigo_referencia"
                        class="size-3.5 text-emerald-600"
                      />
                      <Copy v-else class="size-3.5" />
                    </button>
                  </div>
                </td>

                <!-- Bloco e Unidade -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-1.5">
                    <Badge variant="secondary" class="font-mono text-xs font-semibold">
                      {{ boleto.unidade_codigo }}
                    </Badge>
                    <span class="text-muted-foreground font-medium">{{ boleto.bloco_nome }}</span>
                  </div>
                </td>

                <!-- Morador / Sacado -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-1.5">
                    <Users class="size-3.5 text-muted-foreground/80 shrink-0" />
                    <span class="font-medium text-foreground">{{ boleto.morador_nome }}</span>
                  </div>
                  <span class="text-[10px] text-muted-foreground block pl-5">{{
                    boleto.papel_pagador
                  }}</span>
                </td>

                <!-- Vencimento -->
                <td class="py-3 px-4 text-muted-foreground whitespace-nowrap">
                  {{ formatarDataSimples(boleto.data_vencimento) }}
                </td>

                <!-- Situação -->
                <td class="py-3 px-4 text-center">
                  <Badge
                    variant="outline"
                    class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 text-[10px] font-semibold"
                  >
                    {{ boleto.situacao }}
                  </Badge>
                </td>

                <!-- Valor -->
                <td class="py-3 px-4 text-right">
                  <div class="font-bold text-foreground text-sm">
                    {{ formatarMoeda(boleto.valor) }}
                  </div>
                  <div
                    v-if="boleto.valor_fundo_reserva"
                    class="text-[10px] text-muted-foreground whitespace-nowrap"
                  >
                    Ord:
                    {{
                      formatarMoeda(
                        boleto.valor_cota_ordinaria ?? boleto.valor - boleto.valor_fundo_reserva,
                      )
                    }}
                    <span class="text-amber-700 dark:text-amber-400 font-medium">
                      + Res: {{ formatarMoeda(boleto.valor_fundo_reserva) }}
                    </span>
                  </div>
                </td>

                <!-- Ações -->
                <td class="py-3 px-4 text-center">
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs px-2 gap-1"
                    title="Visualizar cobrança gerada"
                    @click="copiarCodigo(boleto.codigo_referencia)"
                  >
                    <FileText class="size-3.5 text-primary" />
                    <span>Boleto</span>
                  </Button>
                </td>
              </tr>

              <tr v-if="boletosFiltrados.length === 0">
                <td colspan="7" class="py-8 text-center text-muted-foreground text-xs">
                  Nenhum boleto encontrado com os filtros aplicados.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-muted/30 font-semibold border-t">
              <tr>
                <td
                  colspan="5"
                  class="py-3 px-4 text-right uppercase text-[11px] text-muted-foreground"
                >
                  Total de Cobranças Emitidas:
                </td>
                <td class="py-3 px-4 text-right font-bold text-sm text-foreground">
                  {{ formatarMoeda(fechamento.total_rateado) }}
                </td>
                <td class="py-3 px-4"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ABA: DESPESAS PARA RATEIO (SELEÇÃO & PARCIAIS QUANDO ABERTO) -->
      <div v-if="abaAtiva === 'despesas' && fechamento.situacao === 'ABERTO'" class="space-y-4">
        <!-- Banner Explicativo -->
        <div
          class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
          <div class="space-y-1">
            <div class="flex items-center gap-2 font-semibold text-blue-900 text-sm">
              <Sparkles class="size-4 text-blue-600" />
              <span>Controle de Rateio e Cobrança Parcial</span>
            </div>
            <p class="text-xs text-muted-foreground leading-relaxed max-w-2xl">
              Abaixo estão listadas todas as despesas pagas pelo condomínio que ainda possuem saldo
              pendente de cobrança aos moradores. Marque as despesas desejadas e ajuste o
              <strong>valor a cobrar</strong> caso queira transferir parte do saldo para o próximo
              mês.
            </p>
          </div>

          <!-- Caixa de Resumo Rápido -->
          <div
            class="rounded-lg border bg-background p-3 shadow-xs min-w-[240px] space-y-1.5 text-xs"
          >
            <div class="flex justify-between">
              <span class="text-muted-foreground">Despesas selecionadas:</span>
              <strong class="text-foreground"
                >{{ qtdDespesasSelecionadas }} de {{ despesasParaFechar.length }}</strong
              >
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Total a Cobrar:</span>
              <strong class="text-foreground text-sm">{{
                formatarMoeda(totalDespesasSelecionadas)
              }}</strong>
            </div>
            <div class="flex justify-between border-t pt-1">
              <span class="text-muted-foreground">Cota por Apto (36 un):</span>
              <strong class="text-primary font-bold">{{ formatarMoeda(valorCotaEstimada) }}</strong>
            </div>
          </div>
        </div>

        <!-- Filtro e Ações em Lote -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative w-full max-w-xs">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              v-model="buscaDespesa"
              placeholder="Filtrar por descrição, fornecedor ou categoria..."
              class="pl-9 h-9 text-xs"
            />
          </div>

          <!-- Data de Vencimento dos Boletos a Gerar -->
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-foreground whitespace-nowrap">
              Vencimento dos Boletos:
            </label>
            <Input
              v-model="dataVencimentoBoletos"
              type="date"
              class="h-9 w-40 text-xs font-medium"
            />
          </div>
        </div>

        <!-- Tabela de Despesas Elegíveis com Checkbox e Valor a Cobrar Editável -->
        <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
          <table class="w-full text-xs text-left">
            <thead
              class="bg-muted/40 font-semibold text-muted-foreground border-b uppercase tracking-wider text-[11px]"
            >
              <tr>
                <th class="py-3 px-4 w-10 text-center">
                  <input
                    v-model="todasSelecionadas"
                    type="checkbox"
                    class="rounded border-input text-primary focus:ring-primary size-4"
                  />
                </th>
                <th class="py-3 px-4">Data Efetiva</th>
                <th class="py-3 px-4">Descrição da Despesa</th>
                <th class="py-3 px-4">Fornecedor / Prestador</th>
                <th class="py-3 px-4">Categoria</th>
                <th class="py-3 px-4 text-right">Valor Original</th>
                <th class="py-3 px-4 text-right">Já Cobrado</th>
                <th class="py-3 px-4 text-right">Saldo Pendente</th>
                <th class="py-3 px-4 text-right w-44">Valor a Cobrar (R$)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="item in despesasFiltradas"
                :key="item.id"
                class="hover:bg-muted/30 transition-colors"
                :class="{ 'bg-muted/15': !item.selecionado }"
              >
                <!-- Checkbox -->
                <td class="py-3 px-4 text-center">
                  <span v-if="item.pago_com_fundo_reserva" class="text-xs text-muted-foreground"
                    >—</span
                  >
                  <input
                    v-else
                    v-model="item.selecionado"
                    type="checkbox"
                    class="rounded border-input text-primary focus:ring-primary size-4"
                  />
                </td>

                <!-- Data Efetiva -->
                <td class="py-3 px-4 text-muted-foreground whitespace-nowrap">
                  {{ formatarDataSimples(item.data_efetiva) }}
                </td>

                <!-- Descrição -->
                <td class="py-3 px-4 font-medium text-foreground">
                  {{ item.descricao }}
                  <Badge
                    v-if="item.pago_com_fundo_reserva"
                    variant="outline"
                    class="ml-1.5 border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px]"
                  >
                    Fundo de Reserva (Sem Cobrança)
                  </Badge>
                  <span
                    v-else-if="item.cobrado_parcial"
                    class="ml-1.5 inline-flex items-center text-[10px] text-amber-700 bg-amber-500/10 px-1.5 py-0.2 rounded font-medium"
                  >
                    Cobrança Parcial Anterior
                  </span>
                </td>

                <!-- Fornecedor -->
                <td class="py-3 px-4 text-muted-foreground">
                  {{ item.fornecedor }}
                </td>

                <!-- Categoria -->
                <td class="py-3 px-4">
                  <Badge variant="secondary" class="text-[10px]">
                    {{ item.categoria }}
                  </Badge>
                </td>

                <!-- Valor Original -->
                <td class="py-3 px-4 text-right text-muted-foreground whitespace-nowrap">
                  {{ formatarMoeda(item.valor_original) }}
                </td>

                <!-- Já Cobrado -->
                <td class="py-3 px-4 text-right text-muted-foreground whitespace-nowrap">
                  {{ formatarMoeda(item.valor_ja_cobrado) }}
                </td>

                <!-- Saldo Pendente -->
                <td class="py-3 px-4 text-right font-semibold text-foreground whitespace-nowrap">
                  {{ formatarMoeda(item.valor_pendente) }}
                </td>

                <!-- Campo Editável: Valor a Cobrar Neste Fechamento -->
                <td class="py-2 px-4 text-right">
                  <div
                    v-if="item.pago_com_fundo_reserva"
                    class="text-xs text-muted-foreground italic text-right"
                  >
                    R$ 0,00 (Saldo utilizado)
                  </div>
                  <div v-else class="flex items-center justify-end gap-1.5">
                    <Input
                      v-model.number="item.valor_a_cobrar"
                      type="number"
                      step="0.01"
                      :min="0"
                      :max="item.valor_pendente"
                      :disabled="!item.selecionado"
                      class="h-8 w-28 text-right font-bold text-xs"
                      :class="{
                        'border-amber-500 bg-amber-500/5':
                          item.valor_a_cobrar < item.valor_pendente && item.selecionado,
                      }"
                    />
                  </div>
                  <span
                    v-if="
                      !item.pago_com_fundo_reserva &&
                      item.valor_a_cobrar < item.valor_pendente &&
                      item.selecionado
                    "
                    class="text-[10px] text-amber-600 block text-right mt-0.5"
                  >
                    Resta {{ formatarMoeda(item.valor_pendente - item.valor_a_cobrar) }} p/ próximo
                    mês
                  </span>
                </td>
              </tr>

              <tr v-if="despesasFiltradas.length === 0">
                <td colspan="9" class="py-8 text-center text-muted-foreground text-xs">
                  Nenhuma despesa pendente de cobrança foi encontrada.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-muted/30 font-semibold border-t">
              <tr>
                <td
                  colspan="8"
                  class="py-2.5 px-4 text-right uppercase text-[11px] text-muted-foreground"
                >
                  Total de Despesas Ordinárias Selecionadas:
                </td>
                <td class="py-2.5 px-4 text-right font-bold text-xs text-foreground">
                  {{ formatarMoeda(totalDespesasOrdinarias) }}
                </td>
              </tr>
              <tr class="bg-amber-500/5 text-amber-800 dark:text-amber-400">
                <td colspan="8" class="py-2 px-4 text-right uppercase text-[11px]">
                  + Provisão Fundo de Reserva ({{ percentualFundoReserva }}% da Cota Ordinária):
                </td>
                <td class="py-2 px-4 text-right font-bold text-xs">
                  + {{ formatarMoeda(valorFundoReservaCalculado) }}
                </td>
              </tr>
              <tr class="bg-muted/60 font-bold border-t">
                <td colspan="8" class="py-3 px-4 text-right uppercase text-xs text-foreground">
                  Total Geral a Emitir nos Boletos:
                </td>
                <td
                  class="py-3 px-4 text-right font-extrabold text-sm text-emerald-600 dark:text-emerald-400"
                >
                  {{ formatarMoeda(totalGeralParaBoletos) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Botão de Ação Inferior -->
        <div class="flex items-center justify-end pt-2">
          <Button
            v-if="podeFechar"
            variant="default"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs gap-1.5"
            :disabled="totalDespesasOrdinarias <= 0"
            @click="modalFecharAberto = true"
          >
            <Lock class="size-3.5" />
            Confirmar Fechamento e Emitir Boletos ({{ formatarMoeda(totalGeralParaBoletos) }})
          </Button>
        </div>
      </div>

      <!-- ABA: DESPESAS RATEADAS (QUANDO JÁ PUBLICADO) -->
      <div v-if="abaAtiva === 'despesas' && fechamento.situacao === 'PUBLICADO'" class="space-y-4">
        <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
          <table class="w-full text-xs text-left">
            <thead
              class="bg-muted/40 font-semibold text-muted-foreground border-b uppercase tracking-wider text-[11px]"
            >
              <tr>
                <th class="py-3 px-4">Data Efetiva</th>
                <th class="py-3 px-4 text-center">Tipo</th>
                <th class="py-3 px-4">Categoria</th>
                <th class="py-3 px-4">Descrição da Movimentação</th>
                <th class="py-3 px-4 text-right">Valor Original</th>
                <th class="py-3 px-4 text-right">Cobrado Neste Fechamento</th>
                <th class="py-3 px-4 text-right">Saldo Remanescente</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="mov in fechamento.movimentacoes"
                :key="mov.id"
                class="hover:bg-muted/30 transition-colors"
              >
                <td class="py-3 px-4 text-muted-foreground whitespace-nowrap">
                  {{ formatarDataSimples(mov.data_efetiva) }}
                </td>
                <td class="py-3 px-4 text-center">
                  <Badge
                    v-if="mov.direcao === 'ENTRADA'"
                    variant="outline"
                    class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 text-[10px]"
                  >
                    <ArrowDownLeft class="mr-1 size-3" />
                    Entrada
                  </Badge>
                  <Badge
                    v-else
                    variant="outline"
                    class="border-rose-500/30 bg-rose-500/10 text-rose-700 text-[10px]"
                  >
                    <ArrowUpRight class="mr-1 size-3" />
                    Saída
                  </Badge>
                </td>
                <td class="py-3 px-4 font-medium text-foreground">
                  {{ mov.categoria }}
                </td>
                <td class="py-3 px-4 text-foreground/90">
                  {{ mov.descricao }}
                  <span
                    v-if="mov.cobrado_parcial"
                    class="ml-1.5 inline-flex items-center text-[10px] text-amber-700 bg-amber-500/10 px-1.5 py-0.2 rounded font-medium"
                  >
                    Cobrança Parcial
                  </span>
                </td>
                <td class="py-3 px-4 text-right text-muted-foreground whitespace-nowrap">
                  {{ formatarMoeda(mov.valor_original) }}
                </td>
                <td
                  class="py-3 px-4 text-right font-bold whitespace-nowrap"
                  :class="mov.direcao === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600'"
                >
                  {{ formatarMoeda(mov.valor_cobrado) }}
                </td>
                <td
                  class="py-3 px-4 text-right font-medium text-muted-foreground whitespace-nowrap"
                >
                  <span v-if="mov.saldo_pendente > 0.01" class="text-amber-700 font-semibold">
                    {{ formatarMoeda(mov.saldo_pendente) }}
                  </span>
                  <span v-else class="text-emerald-600">Quitado (R$ 0,00)</span>
                </td>
              </tr>

              <tr v-if="fechamento.movimentacoes.length === 0">
                <td colspan="7" class="py-8 text-center text-muted-foreground text-xs">
                  Nenhuma movimentação incluída neste fechamento.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-muted/30 font-semibold border-t">
              <tr>
                <td
                  colspan="5"
                  class="py-3 px-4 text-right uppercase text-[11px] text-muted-foreground"
                >
                  Total Rateado aos Moradores:
                </td>
                <td class="py-3 px-4 text-right font-bold text-sm text-foreground">
                  {{ formatarMoeda(fechamento.total_rateado) }}
                </td>
                <td class="py-3 px-4"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ABA: MEMÓRIA DE RATEIO POR UNIDADE -->
      <div v-if="abaAtiva === 'rateio'" class="space-y-4">
        <!-- Busca de unidade -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative w-full max-w-xs">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              v-model="buscaUnidade"
              placeholder="Buscar unidade, bloco ou morador..."
              class="pl-9 h-9 text-xs"
            />
          </div>

          <div class="text-xs text-muted-foreground">
            Exibindo <strong>{{ unidadesFiltradas.length }}</strong> de
            <strong>{{ fechamento.rateio?.unidades?.length ?? 0 }}</strong> unidades
          </div>
        </div>

        <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
          <table class="w-full text-xs text-left">
            <thead
              class="bg-muted/40 font-semibold text-muted-foreground border-b uppercase tracking-wider text-[11px]"
            >
              <tr>
                <th class="py-3 px-4">Bloco</th>
                <th class="py-3 px-4">Apartamento</th>
                <th class="py-3 px-4">Responsável / Morador</th>
                <th class="py-3 px-4 text-center">Participação</th>
                <th class="py-3 px-4 text-right">Valor Rateado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="unidade in unidadesFiltradas"
                :key="unidade.id_publico"
                class="hover:bg-muted/30 transition-colors"
              >
                <td class="py-3 px-4 font-medium text-foreground">
                  {{ unidade.bloco }}
                </td>
                <td class="py-3 px-4">
                  <Badge variant="secondary" class="font-mono text-xs font-semibold">
                    {{ unidade.codigo }}
                  </Badge>
                </td>
                <td class="py-3 px-4 text-muted-foreground">
                  <div class="flex items-center gap-1.5">
                    <Users class="size-3.5 text-muted-foreground/80 shrink-0" />
                    <span class="font-medium text-foreground">{{ unidade.responsavel }}</span>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <Badge
                    v-if="!unidade.isento"
                    variant="outline"
                    class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 text-[10px]"
                  >
                    Participante
                  </Badge>
                  <Badge
                    v-else
                    variant="outline"
                    class="border-amber-500/30 bg-amber-500/10 text-amber-700 text-[10px]"
                  >
                    Isento ({{ unidade.motivo_isencao || 'Convenção' }})
                  </Badge>
                </td>
                <td class="py-3 px-4 text-right font-bold text-foreground">
                  {{ formatarMoeda(unidade.valor) }}
                </td>
              </tr>

              <tr v-if="unidadesFiltradas.length === 0">
                <td colspan="5" class="py-8 text-center text-muted-foreground text-xs">
                  Nenhuma unidade encontrada com o termo pesquisado.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-muted/30 font-semibold border-t">
              <tr>
                <td
                  colspan="4"
                  class="py-3 px-4 text-right uppercase text-[11px] text-muted-foreground"
                >
                  Total Rateado Distribuído:
                </td>
                <td class="py-3 px-4 text-right font-bold text-sm text-foreground">
                  {{
                    formatarMoeda(
                      fechamento.situacao === 'PUBLICADO'
                        ? fechamento.rateio?.valor_total
                        : totalDespesasSelecionadas,
                    )
                  }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ABA: RECONCILIAÇÃO DE CONTAS E CATEGORIAS -->
      <div v-if="abaAtiva === 'reconciliacao'" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Contas Financeiras / Bancárias -->
        <div class="rounded-xl border bg-card p-4 shadow-xs space-y-3">
          <div class="flex items-center gap-2 border-b pb-2">
            <Wallet class="size-4 text-primary" />
            <h3 class="font-bold text-sm text-foreground">Reconciliação por Conta Bancária</h3>
          </div>

          <div class="space-y-3">
            <div
              v-for="conta in fechamento.contas"
              :key="conta.conta_id"
              class="rounded-lg border bg-muted/20 p-3 text-xs space-y-1.5"
            >
              <div class="flex items-center justify-between font-semibold text-foreground">
                <span>{{ conta.nome }}</span>
                <Badge variant="secondary" class="text-[10px]">{{ conta.tipo }}</Badge>
              </div>
              <div class="grid grid-cols-3 gap-2 text-muted-foreground pt-1">
                <div>
                  <span class="block text-[10px]">Entradas:</span>
                  <span class="font-semibold text-emerald-600"
                    >+ {{ formatarMoeda(conta.entradas) }}</span
                  >
                </div>
                <div>
                  <span class="block text-[10px]">Saídas:</span>
                  <span class="font-semibold text-rose-600"
                    >- {{ formatarMoeda(conta.saidas) }}</span
                  >
                </div>
                <div>
                  <span class="block text-[10px]">Saldo Total:</span>
                  <span class="font-bold text-foreground">{{ formatarMoeda(conta.saldo) }}</span>
                </div>
              </div>
              <div
                v-if="
                  conta.saldo_capital_giro !== undefined || conta.saldo_fundo_reserva !== undefined
                "
                class="grid grid-cols-2 gap-2 border-t border-border/40 pt-1.5 text-[11px]"
              >
                <div>
                  <span class="text-muted-foreground">Capital de Giro:</span>
                  <span class="ml-1 font-semibold text-foreground">
                    {{ formatarMoeda(conta.saldo_capital_giro) }}
                  </span>
                </div>
                <div>
                  <span class="text-muted-foreground">Fundo de Reserva:</span>
                  <span class="ml-1 font-semibold text-amber-700 dark:text-amber-400">
                    {{ formatarMoeda(conta.saldo_fundo_reserva) }}
                  </span>
                </div>
              </div>
            </div>

            <div
              v-if="fechamento.contas.length === 0"
              class="text-xs text-muted-foreground py-4 text-center"
            >
              Nenhuma conta bancária movimentada no período.
            </div>
          </div>
        </div>

        <!-- Categorias Financeiras -->
        <div class="rounded-xl border bg-card p-4 shadow-xs space-y-3">
          <div class="flex items-center gap-2 border-b pb-2">
            <Layers class="size-4 text-primary" />
            <h3 class="font-bold text-sm text-foreground">Distribuição por Categoria</h3>
          </div>

          <div class="space-y-2">
            <div
              v-for="cat in fechamento.categorias"
              :key="cat.nome"
              class="flex items-center justify-between rounded-lg border bg-muted/20 p-2.5 text-xs"
            >
              <div class="flex items-center gap-2">
                <Badge
                  variant="outline"
                  class="text-[9px] px-1 py-0"
                  :class="
                    cat.direcao === 'ENTRADA'
                      ? 'border-emerald-500/40 text-emerald-700'
                      : 'border-rose-500/40 text-rose-700'
                  "
                >
                  {{ cat.direcao === 'ENTRADA' ? 'REC' : 'DESP' }}
                </Badge>
                <div>
                  <span class="font-medium text-foreground block">{{ cat.nome }}</span>
                  <span class="text-[10px] text-muted-foreground"
                    >{{ cat.quantidade }} lançamento(s)</span
                  >
                </div>
              </div>
              <span
                class="font-bold"
                :class="cat.direcao === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600'"
              >
                {{ formatarMoeda(cat.total) }}
              </span>
            </div>

            <div
              v-if="fechamento.categorias.length === 0"
              class="text-xs text-muted-foreground py-4 text-center"
            >
              Nenhuma categoria movimentada no período.
            </div>
          </div>
        </div>
      </div>

      <!-- ABA: HISTÓRICO DE REABERTURAS -->
      <div v-if="abaAtiva === 'historico'" class="space-y-4">
        <div class="rounded-xl border bg-card p-4 shadow-xs space-y-3">
          <div class="flex items-center gap-2 border-b pb-2 text-amber-700">
            <History class="size-4" />
            <h3 class="font-bold text-sm">Histórico de Reaberturas da Competência</h3>
          </div>

          <div class="divide-y divide-border">
            <div
              v-for="reabertura in fechamento.reaberturas"
              :key="reabertura.id_publico"
              class="py-3 text-xs space-y-1.5"
            >
              <div class="flex items-center justify-between text-muted-foreground">
                <span class="font-semibold text-foreground"
                  >Reaberto por: {{ reabertura.reaberto_por }}</span
                >
                <span>{{ formatarData(reabertura.reaberto_em) }}</span>
              </div>
              <div class="rounded-md bg-muted/40 p-2.5 text-foreground italic">
                "{{ reabertura.motivo }}"
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL FECHAR COMPETÊNCIA & EMITIR BOLETOS -->
    <Sheet v-model:open="modalFecharAberto">
      <SheetContent side="center" class="sm:max-w-lg">
        <SheetHeader>
          <div class="flex items-center gap-2 text-emerald-600 mb-1">
            <div class="rounded-full bg-emerald-500/10 p-2">
              <Lock class="size-5" />
            </div>
            <SheetTitle class="text-xl">Fechar Competência & Emitir Boletos</SheetTitle>
          </div>
          <p class="text-xs text-muted-foreground">
            Competência: <strong>{{ fechamento?.nome_mes }} / {{ fechamento?.ano }}</strong>
          </p>
        </SheetHeader>

        <div v-if="fechamento" class="space-y-4 py-2 text-sm">
          <!-- Configuração do Fundo de Reserva -->
          <div
            class="rounded-lg border border-amber-300/60 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20 p-3 space-y-1.5"
          >
            <div class="flex items-center justify-between">
              <div>
                <label for="campo-pct-fundo" class="block text-xs font-semibold text-foreground">
                  Fundo de Reserva (%)
                </label>
                <p class="text-[11px] text-muted-foreground">
                  Provisão cobrada todo mês junto ao fechamento (padrão: 10%)
                </p>
              </div>
              <div class="w-24">
                <Input
                  id="campo-pct-fundo"
                  v-model.number="percentualFundoReserva"
                  type="number"
                  step="0.5"
                  min="0"
                  max="100"
                  class="h-8 text-right font-bold text-xs"
                />
              </div>
            </div>
          </div>

          <div class="rounded-lg border bg-muted/30 p-3.5 space-y-2">
            <div class="flex justify-between text-xs">
              <span class="text-muted-foreground">Despesas ordinárias apuradas:</span>
              <span class="font-semibold text-foreground">
                {{ qtdDespesasSelecionadas }} despesa(s) ({{
                  formatarMoeda(totalDespesasOrdinarias)
                }})
              </span>
            </div>
            <div
              class="flex justify-between text-xs text-amber-800 dark:text-amber-400 font-medium"
            >
              <span>+ Provisão Fundo de Reserva ({{ percentualFundoReserva }}%):</span>
              <span>+ {{ formatarMoeda(valorFundoReservaCalculado) }}</span>
            </div>
            <div class="flex justify-between text-xs border-t pt-1.5 font-bold">
              <span class="text-foreground">Total geral para cobrança nos boletos:</span>
              <span class="text-emerald-700 dark:text-emerald-400 text-sm">
                {{ formatarMoeda(totalGeralParaBoletos) }}
              </span>
            </div>
            <div class="flex justify-between text-xs border-t pt-1.5">
              <span class="text-muted-foreground">Boletos a serem gerados:</span>
              <span class="font-semibold text-foreground">
                {{ fechamento.rateio.unidades_participantes }} apartamentos
              </span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-muted-foreground">Valor individual por boleto:</span>
              <div class="text-right">
                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-sm">
                  {{ formatarMoeda(valorCotaEstimada) }}
                </span>
                <span class="block text-[10px] text-muted-foreground">
                  (Ord: {{ formatarMoeda(valorCotaOrdinariaUnitaria) }} + Fundo:
                  {{ formatarMoeda(valorFundoReservaUnitario) }})
                </span>
              </div>
            </div>
            <div class="flex justify-between text-xs border-t pt-1.5">
              <span class="text-muted-foreground">Data de Vencimento dos Boletos:</span>
              <span class="font-medium text-foreground">
                {{ formatarDataSimples(dataVencimentoBoletos) }}
              </span>
            </div>
          </div>

          <div
            class="rounded-lg border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-800 leading-relaxed space-y-1"
          >
            <div class="flex items-center gap-1.5 font-semibold text-amber-900 dark:text-amber-300">
              <AlertTriangle class="size-4 shrink-0 text-amber-600" />
              <span>Geração Automática de Cobranças:</span>
            </div>
            <p>
              Ao confirmar o fechamento, o sistema registrará as movimentações financeiras como
              contabilizadas e gerará os boletos individuais de rateio com a cota ordinária e a taxa
              de reserva para cada apartamento.
            </p>
          </div>

          <div
            v-if="erroFechar"
            class="rounded-md bg-destructive/10 p-2.5 text-xs text-destructive"
          >
            {{ erroFechar }}
          </div>
        </div>

        <SheetFooter class="flex flex-row justify-end gap-2 border-t pt-3">
          <Button
            variant="outline"
            :disabled="mutacaoFechar.isPending.value"
            @click="modalFecharAberto = false"
          >
            Cancelar
          </Button>
          <Button
            variant="default"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium"
            :disabled="mutacaoFechar.isPending.value || totalDespesasOrdinarias <= 0"
            @click="mutacaoFechar.mutate()"
          >
            <LoaderCircle v-if="mutacaoFechar.isPending.value" class="mr-2 size-4 animate-spin" />
            <Lock v-else class="mr-1.5 size-4" />
            Confirmar Fechamento e Emitir
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>

    <!-- MODAL REABRIR COMPETÊNCIA -->
    <Sheet v-model:open="modalReabrirAberto">
      <SheetContent side="center" class="sm:max-w-md">
        <SheetHeader>
          <div class="flex items-center gap-2 text-amber-600 mb-1">
            <div class="rounded-full bg-amber-500/10 p-2">
              <RotateCcw class="size-5" />
            </div>
            <SheetTitle class="text-xl">Reabrir Competência</SheetTitle>
          </div>
          <p class="text-xs text-muted-foreground">
            Competência: <strong>{{ fechamento?.nome_mes }} / {{ fechamento?.ano }}</strong>
          </p>
        </SheetHeader>

        <div class="space-y-4 py-2 text-sm">
          <div
            class="rounded-lg border border-rose-500/20 bg-rose-500/10 p-3 text-xs text-rose-800 leading-relaxed space-y-1"
          >
            <div class="flex items-center gap-1.5 font-semibold text-rose-900">
              <AlertTriangle class="size-4 shrink-0 text-rose-600" />
              <span>Atenção: Cancelamento de Boletos em Aberto</span>
            </div>
            <p>
              Ao reabrir a competência, os boletos ainda não quitados desta competência serão
              <strong>cancelados</strong> e o período voltará para o status "Em Aberto", permitindo
              ajustar os valores e despesas.
            </p>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">
              Justificativa da Reabertura <span class="text-rose-600">*</span>
            </label>
            <textarea
              v-model="motivoReabertura"
              rows="3"
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs outline-none focus:border-ring focus:ring-1 focus:ring-ring"
              placeholder="Ex: Necessário ajustar lançamento de fornecedor com valor incorreto..."
            />
            <p class="text-[11px] text-muted-foreground">Mínimo de 5 caracteres.</p>
          </div>

          <div
            v-if="erroReabrir"
            class="rounded-md bg-destructive/10 p-2.5 text-xs text-destructive"
          >
            {{ erroReabrir }}
          </div>
        </div>

        <SheetFooter class="flex flex-row justify-end gap-2 border-t pt-3">
          <Button
            variant="outline"
            :disabled="mutacaoReabrir.isPending.value"
            @click="modalReabrirAberto = false"
          >
            Cancelar
          </Button>
          <Button
            variant="default"
            class="bg-amber-600 hover:bg-amber-700 text-white font-medium"
            :disabled="mutacaoReabrir.isPending.value || motivoReabertura.trim().length < 5"
            @click="mutacaoReabrir.mutate()"
          >
            <LoaderCircle v-if="mutacaoReabrir.isPending.value" class="mr-2 size-4 animate-spin" />
            <RotateCcw v-else class="mr-1.5 size-4" />
            Confirmar Reabertura
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </div>
</template>
