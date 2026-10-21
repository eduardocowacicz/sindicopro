<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  AlertCircle,
  CheckCircle2,
  Clock,
  DollarSign,
  Eye,
  FileText,
  LoaderCircle,
  Percent,
  Search,
} from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { mensagemDeErro } from '@/lib/http-error'
import * as cobrancasApi from '@/modules/financeiro/api/cobrancas.api'
import * as contasApi from '@/modules/financeiro/api/contas.api'
import type { CobrancaItem } from '@/modules/financeiro/types/cobranca.types'

const filaCliente = useQueryClient()

// Filtros
const situacaoFiltro = ref<'TODOS' | 'ABERTO' | 'PAGO' | 'VENCIDO'>('TODOS')
const buscaFiltro = ref('')
const buscaAtiva = ref('')
const mesFiltro = ref<number | ''>('')
const anoFiltro = ref<number | ''>('')
const pagina = ref(1)

const anosDisponiveis = computed(() => {
  const anoAtual = new Date().getFullYear()
  return [anoAtual - 1, anoAtual, anoAtual + 1]
})

const meses = [
  { valor: 1, nome: 'Janeiro' },
  { valor: 2, nome: 'Fevereiro' },
  { valor: 3, nome: 'Março' },
  { valor: 4, nome: 'Abril' },
  { valor: 5, nome: 'Maio' },
  { valor: 6, nome: 'Junho' },
  { valor: 7, nome: 'Julho' },
  { valor: 8, nome: 'Agosto' },
  { valor: 9, nome: 'Setembro' },
  { valor: 10, nome: 'Outubro' },
  { valor: 11, nome: 'Novembro' },
  { valor: 12, nome: 'Dezembro' },
]

function aplicarBusca(): void {
  buscaAtiva.value = buscaFiltro.value
  pagina.value = 1
}

watch([situacaoFiltro, mesFiltro, anoFiltro], () => {
  pagina.value = 1
})

// Query de Cobranças
const { data, isPending } = useQuery({
  queryKey: ['financeiro-cobrancas', situacaoFiltro, buscaAtiva, mesFiltro, anoFiltro, pagina],
  queryFn: () =>
    cobrancasApi.listarCobrancas({
      situacao: situacaoFiltro.value,
      busca: buscaAtiva.value || undefined,
      mes: mesFiltro.value ? Number(mesFiltro.value) : undefined,
      ano: anoFiltro.value ? Number(anoFiltro.value) : undefined,
      pagina: pagina.value,
      por_pagina: 25,
    }),
})

// Query de Contas Financeiras para Baixa
const { data: contas } = useQuery({
  queryKey: ['financeiro-contas-selecao'],
  queryFn: contasApi.listarContasParaSelecao,
})

// Modais
const modalPagamentoAberto = ref(false)
const modalDetalhesAberto = ref(false)
const cobrancaSelecionada = ref<CobrancaItem | null>(null)
const erroPagamento = ref('')

const formPagamento = reactive({
  data_pagamento: '',
  valor_pago: 0,
  conta_financeira_id: '',
  forma_pagamento: 'BOLETO' as 'PIX' | 'BOLETO' | 'TRANSFERENCIA' | 'DINHEIRO' | 'OUTRO',
  observacoes: '',
})

function abrirRegistrarPagamento(cobranca: CobrancaItem): void {
  cobrancaSelecionada.value = cobranca
  formPagamento.data_pagamento = new Date().toISOString().slice(0, 10)
  formPagamento.valor_pago = cobranca.valor_original
  formPagamento.conta_financeira_id = contas.value?.[0]?.id_publico ?? ''
  formPagamento.forma_pagamento = 'BOLETO'
  formPagamento.observacoes = ''
  erroPagamento.value = ''
  modalPagamentoAberto.value = true
}

function abrirDetalhes(cobranca: CobrancaItem): void {
  cobrancaSelecionada.value = cobranca
  modalDetalhesAberto.value = true
}

const mutacaoRegistrarPagamento = useMutation({
  mutationFn: () => {
    if (!cobrancaSelecionada.value) throw new Error('Cobrança não selecionada')
    return cobrancasApi.registrarPagamento(cobrancaSelecionada.value.id_publico, {
      data_pagamento: formPagamento.data_pagamento,
      valor_pago: formPagamento.valor_pago,
      conta_financeira_id: formPagamento.conta_financeira_id || undefined,
      forma_pagamento: formPagamento.forma_pagamento,
      observacoes: formPagamento.observacoes || undefined,
    })
  },
  onSuccess: async () => {
    modalPagamentoAberto.value = false
    await Promise.all([
      filaCliente.invalidateQueries({ queryKey: ['financeiro-cobrancas'] }),
      filaCliente.invalidateQueries({ queryKey: ['financeiro-contas'] }),
    ])
  },
  onError: (erro: unknown) => {
    erroPagamento.value = mensagemDeErro(erro)
  },
})

function formatarMoeda(valor?: number | null): string {
  return Number(valor ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '—'
  const partes = dataStr.slice(0, 10).split('-')
  if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`
  return dataStr
}

const situacaoBadge: Record<
  string,
  { rotulo: string; variant: 'default' | 'secondary' | 'destructive' | 'outline'; classe: string }
> = {
  ABERTO: {
    rotulo: 'Em Aberto',
    variant: 'outline',
    classe: 'border-amber-500/50 bg-amber-500/10 text-amber-700 dark:text-amber-400 font-semibold',
  },
  PAGO: {
    rotulo: 'Pago',
    variant: 'outline',
    classe:
      'border-emerald-500/50 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-semibold',
  },
  VENCIDO: {
    rotulo: 'Vencido',
    variant: 'outline',
    classe: 'border-rose-500/50 bg-rose-500/10 text-rose-700 dark:text-rose-400 font-semibold',
  },
  CANCELADO: {
    rotulo: 'Cancelado',
    variant: 'secondary',
    classe: 'text-muted-foreground',
  },
}

const classeSelect =
  'border-input h-9 w-full min-w-0 rounded-md border bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 disabled:pointer-events-none disabled:opacity-50'
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader
      title="Boletos e Cobranças"
      description="Controle e acompanhamento dos boletos emitidos aos moradores, adimplência e baixa de pagamentos."
      eyebrow="Financeiro"
    />

    <!-- KPIs de Cobrança -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total em Aberto -->
      <Card class="border-amber-200/60 bg-card dark:border-amber-900/30">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            A Receber (Em Aberto)
          </CardTitle>
          <div class="rounded-full bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
            <Clock class="size-4" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-foreground">
            {{ formatarMoeda(data?.kpis?.total_aberto ?? 0) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ data?.kpis?.qtd_aberto ?? 0 }} boletos aguardando pagamento
          </p>
        </CardContent>
      </Card>

      <!-- Total Recebido -->
      <Card class="border-emerald-200/60 bg-card dark:border-emerald-900/30">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            Total Recebido (Pago)
          </CardTitle>
          <div class="rounded-full bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="size-4" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
            {{ formatarMoeda(data?.kpis?.total_pago ?? 0) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ data?.kpis?.qtd_pago ?? 0 }} boletos quitados
          </p>
        </CardContent>
      </Card>

      <!-- Total Vencido -->
      <Card class="border-rose-200/60 bg-card dark:border-rose-900/30">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            Total em Atraso (Vencido)
          </CardTitle>
          <div class="rounded-full bg-rose-500/10 p-2 text-rose-600 dark:text-rose-400">
            <AlertCircle class="size-4" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
            {{ formatarMoeda(data?.kpis?.total_vencido ?? 0) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ data?.kpis?.qtd_vencido ?? 0 }} boletos vencidos
          </p>
        </CardContent>
      </Card>

      <!-- Taxa de Adimplência -->
      <Card class="border-sky-200/60 bg-card dark:border-sky-900/30">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            Taxa de Adimplência
          </CardTitle>
          <div class="rounded-full bg-sky-500/10 p-2 text-sky-600 dark:text-sky-400">
            <Percent class="size-4" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl font-bold tracking-tight text-foreground">
              {{ data?.kpis?.taxa_adimplencia ?? 100 }}%
            </span>
            <span class="text-xs text-muted-foreground">
              ({{ data?.kpis?.qtd_pago ?? 0 }}/{{ data?.kpis?.qtd_total ?? 0 }})
            </span>
          </div>
          <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="
                (data?.kpis?.taxa_adimplencia ?? 100) >= 80
                  ? 'bg-emerald-500'
                  : (data?.kpis?.taxa_adimplencia ?? 100) >= 50
                    ? 'bg-amber-500'
                    : 'bg-rose-500'
              "
              :style="{ width: `${data?.kpis?.taxa_adimplencia ?? 100}%` }"
            />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div
      class="flex flex-col gap-4 rounded-xl border border-border/60 bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <!-- Abas de Situação -->
      <div class="flex flex-wrap gap-1 rounded-lg bg-muted p-1">
        <button
          type="button"
          :class="[
            'cursor-pointer rounded-md px-3 py-1.5 text-xs font-semibold transition-all',
            situacaoFiltro === 'TODOS'
              ? 'bg-background text-foreground shadow-xs'
              : 'text-muted-foreground hover:text-foreground',
          ]"
          @click="situacaoFiltro = 'TODOS'"
        >
          Todos ({{ data?.kpis?.qtd_total ?? 0 }})
        </button>
        <button
          type="button"
          :class="[
            'cursor-pointer rounded-md px-3 py-1.5 text-xs font-semibold transition-all',
            situacaoFiltro === 'ABERTO'
              ? 'bg-background text-amber-700 shadow-xs dark:text-amber-400'
              : 'text-muted-foreground hover:text-foreground',
          ]"
          @click="situacaoFiltro = 'ABERTO'"
        >
          Em Aberto ({{ data?.kpis?.qtd_aberto ?? 0 }})
        </button>
        <button
          type="button"
          :class="[
            'cursor-pointer rounded-md px-3 py-1.5 text-xs font-semibold transition-all',
            situacaoFiltro === 'PAGO'
              ? 'bg-background text-emerald-700 shadow-xs dark:text-emerald-400'
              : 'text-muted-foreground hover:text-foreground',
          ]"
          @click="situacaoFiltro = 'PAGO'"
        >
          Pagos ({{ data?.kpis?.qtd_pago ?? 0 }})
        </button>
        <button
          type="button"
          :class="[
            'cursor-pointer rounded-md px-3 py-1.5 text-xs font-semibold transition-all',
            situacaoFiltro === 'VENCIDO'
              ? 'bg-background text-rose-700 shadow-xs dark:text-rose-400'
              : 'text-muted-foreground hover:text-foreground',
          ]"
          @click="situacaoFiltro = 'VENCIDO'"
        >
          Vencidos ({{ data?.kpis?.qtd_vencido ?? 0 }})
        </button>
      </div>

      <!-- Filtros de Mês/Ano e Busca Livre -->
      <div class="flex flex-wrap items-center gap-2">
        <select v-model="mesFiltro" :class="[classeSelect, 'w-32']">
          <option value="">Todos meses</option>
          <option v-for="m in meses" :key="m.valor" :value="m.valor">
            {{ m.nome }}
          </option>
        </select>

        <select v-model="anoFiltro" :class="[classeSelect, 'w-24']">
          <option value="">Ano...</option>
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">
            {{ ano }}
          </option>
        </select>

        <div class="relative w-full sm:w-56">
          <Search
            class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
            aria-hidden="true"
          />
          <Input
            v-model="buscaFiltro"
            placeholder="Buscar morador, apto..."
            class="pl-8"
            @keyup.enter="aplicarBusca"
          />
        </div>
        <Button variant="secondary" size="sm" @click="aplicarBusca"> Filtrar </Button>
      </div>
    </div>

    <!-- Tabela de Boletos / Cobranças -->
    <div class="overflow-x-auto rounded-md border bg-card">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th class="px-4 py-3 font-medium">Boleto / Cód.</th>
            <th class="px-4 py-3 font-medium">Unidade / Morador</th>
            <th class="px-4 py-3 font-medium">Vencimento</th>
            <th class="px-4 py-3 font-medium">Composição (Ordinária + Fundo)</th>
            <th class="px-4 py-3 font-medium">Valor Total</th>
            <th class="px-4 py-3 font-medium">Situação</th>
            <th class="px-4 py-3 font-medium text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isPending">
            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
              <LoaderCircle class="mx-auto size-6 animate-spin text-primary" aria-hidden="true" />
              <p class="mt-2 text-xs">Carregando boletos...</p>
            </td>
          </tr>
          <tr v-else-if="!data || data.dados.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
              <FileText class="mx-auto size-8 opacity-40" aria-hidden="true" />
              <p class="mt-2 font-medium">Nenhum boleto encontrado.</p>
              <p class="text-xs text-muted-foreground">
                Os boletos são gerados automaticamente quando um fechamento mensal é publicado.
              </p>
            </td>
          </tr>
          <tr
            v-for="cobranca in data?.dados ?? []"
            :key="cobranca.id_publico"
            class="border-t transition-colors hover:bg-muted/30"
          >
            <!-- Código do boleto -->
            <td class="px-4 py-3 font-mono text-xs font-semibold">
              <span class="rounded bg-muted px-1.5 py-0.5 text-foreground">
                {{ cobranca.codigo_referencia }}
              </span>
            </td>

            <!-- Apartamento e Morador -->
            <td class="px-4 py-3">
              <div class="font-medium text-foreground">
                Apto {{ cobranca.unidade_codigo }} • {{ cobranca.bloco_nome }}
              </div>
              <div class="text-xs text-muted-foreground">
                {{ cobranca.morador_nome }}
                <span v-if="cobranca.morador_telefone" class="opacity-75">
                  • {{ cobranca.morador_telefone }}
                </span>
              </div>
            </td>

            <!-- Vencimento -->
            <td class="px-4 py-3">
              <div class="text-xs font-medium text-foreground">
                {{ formatarData(cobranca.data_vencimento) }}
              </div>
              <div
                v-if="cobranca.situacao === 'VENCIDO'"
                class="text-[11px] font-bold text-rose-600"
              >
                Em atraso
              </div>
              <div v-else class="text-[11px] text-muted-foreground">
                Emissão: {{ formatarData(cobranca.data_emissao) }}
              </div>
            </td>

            <!-- Composição: Rateio + Fundo de Reserva -->
            <td class="px-4 py-3 text-xs">
              <div class="flex flex-col gap-0.5">
                <span class="text-muted-foreground">
                  Cota:
                  <strong class="text-foreground">{{
                    formatarMoeda(cobranca.valor_cota_ordinaria)
                  }}</strong>
                </span>
                <span
                  v-if="(cobranca.valor_fundo_reserva ?? 0) > 0"
                  class="text-amber-700 dark:text-amber-400 font-medium"
                >
                  + Fundo Res.: {{ formatarMoeda(cobranca.valor_fundo_reserva) }}
                </span>
              </div>
            </td>

            <!-- Valor Total -->
            <td class="px-4 py-3 font-semibold text-foreground">
              {{ formatarMoeda(cobranca.valor_original) }}
            </td>

            <!-- Situação -->
            <td class="px-4 py-3">
              <Badge
                :variant="situacaoBadge[cobranca.situacao]?.variant ?? 'outline'"
                :class="situacaoBadge[cobranca.situacao]?.classe"
              >
                {{ situacaoBadge[cobranca.situacao]?.rotulo ?? cobranca.situacao }}
              </Badge>
            </td>

            <!-- Ações -->
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Button
                  variant="ghost"
                  size="sm"
                  title="Ver Detalhes"
                  @click="abrirDetalhes(cobranca)"
                >
                  <Eye class="size-4" aria-hidden="true" />
                  <span class="sr-only">Detalhes</span>
                </Button>

                <Button
                  v-if="cobranca.situacao === 'ABERTO' || cobranca.situacao === 'VENCIDO'"
                  variant="default"
                  size="sm"
                  class="gap-1 bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-700"
                  @click="abrirRegistrarPagamento(cobranca)"
                >
                  <DollarSign class="size-3.5" aria-hidden="true" />
                  Dar Baixa
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <div
      v-if="data && data.meta.ultima_pagina > 1"
      class="flex items-center justify-between border-t border-border/60 pt-4 text-xs text-muted-foreground"
    >
      <span>
        Mostrando {{ (data.meta.pagina_atual - 1) * data.meta.por_pagina + 1 }} a
        {{ Math.min(data.meta.pagina_atual * data.meta.por_pagina, data.meta.total) }} de
        {{ data.meta.total }} boletos
      </span>
      <div class="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="data.meta.pagina_atual <= 1"
          @click="pagina--"
        >
          Anterior
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="data.meta.pagina_atual >= data.meta.ultima_pagina"
          @click="pagina++"
        >
          Próxima
        </Button>
      </div>
    </div>

    <!-- Modal: Registrar Pagamento / Dar Baixa -->
    <Sheet v-model:open="modalPagamentoAberto">
      <SheetContent
        side="center"
        class="max-h-[90vh] w-[calc(100vw-2rem)] max-w-[560px] overflow-y-auto rounded-[10px] p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-3">
          <SheetTitle class="flex items-center gap-2 text-base font-semibold text-foreground">
            <DollarSign class="size-5 text-emerald-600" aria-hidden="true" />
            Registrar Pagamento do Boleto
          </SheetTitle>
        </SheetHeader>

        <form
          v-if="cobrancaSelecionada"
          class="space-y-4 pt-3"
          @submit.prevent="mutacaoRegistrarPagamento.mutate()"
        >
          <!-- Resumo da Cobrança -->
          <div class="rounded-lg border border-border/60 bg-muted/40 p-3 text-xs space-y-1">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Boleto:</span>
              <strong class="font-mono text-foreground">{{
                cobrancaSelecionada.codigo_referencia
              }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Unidade:</span>
              <strong class="text-foreground">
                Apto {{ cobrancaSelecionada.unidade_codigo }} • {{ cobrancaSelecionada.bloco_nome }}
              </strong>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Morador:</span>
              <strong class="text-foreground">{{ cobrancaSelecionada.morador_nome }}</strong>
            </div>
            <div class="flex justify-between border-t border-border/40 pt-1 font-medium">
              <span class="text-muted-foreground">Valor do Boleto:</span>
              <span class="text-sm font-bold text-foreground">
                {{ formatarMoeda(cobrancaSelecionada.valor_original) }}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <!-- Data de Pagamento -->
            <div class="space-y-1">
              <label for="campo-data-pag" class="block text-xs font-semibold text-foreground">
                Data do Pagamento <span class="text-destructive">*</span>
              </label>
              <Input
                id="campo-data-pag"
                v-model="formPagamento.data_pagamento"
                type="date"
                required
              />
            </div>

            <!-- Valor Pago -->
            <div class="space-y-1">
              <label for="campo-valor-pago" class="block text-xs font-semibold text-foreground">
                Valor Pago (R$) <span class="text-destructive">*</span>
              </label>
              <Input
                id="campo-valor-pago"
                v-model.number="formPagamento.valor_pago"
                type="number"
                step="0.01"
                min="0.01"
                required
              />
            </div>
          </div>

          <!-- Conta Bancária de Destino -->
          <div class="space-y-1">
            <label for="campo-conta-dest" class="block text-xs font-semibold text-foreground">
              Conta Bancária de Destino <span class="text-destructive">*</span>
            </label>
            <select
              id="campo-conta-dest"
              v-model="formPagamento.conta_financeira_id"
              :class="classeSelect"
              required
            >
              <option value="" disabled>Selecione a conta bancária...</option>
              <option v-for="c in contas ?? []" :key="c.id_publico" :value="c.id_publico">
                {{ c.nome }} ({{ c.nome_banco ?? 'Caixa Condomínio' }})
              </option>
            </select>
            <p class="text-[11px] text-muted-foreground">
              O valor recebido será creditado nesta conta financeira como receita de cobrança.
            </p>
          </div>

          <!-- Forma de Pagamento -->
          <div class="space-y-1">
            <label for="campo-forma-pag" class="block text-xs font-semibold text-foreground">
              Forma de Pagamento
            </label>
            <select
              id="campo-forma-pag"
              v-model="formPagamento.forma_pagamento"
              :class="classeSelect"
            >
              <option value="BOLETO">Boleto Bancário</option>
              <option value="PIX">PIX</option>
              <option value="TRANSFERENCIA">Transferência Bancária (TED/DOC)</option>
              <option value="DINHEIRO">Dinheiro</option>
              <option value="OUTRO">Outro</option>
            </select>
          </div>

          <!-- Observações -->
          <div class="space-y-1">
            <label for="campo-obs-pag" class="block text-xs font-semibold text-foreground">
              Observações da baixa
            </label>
            <textarea
              id="campo-obs-pag"
              v-model="formPagamento.observacoes"
              rows="2"
              class="border-input w-full min-w-0 resize-y rounded-md border bg-background px-3 py-1.5 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
              placeholder="Ex: Comprovante enviado via WhatsApp pelo morador..."
            />
          </div>

          <!-- Erro geral -->
          <div
            v-if="erroPagamento"
            class="rounded-md bg-destructive/10 p-2.5 text-xs text-destructive"
          >
            {{ erroPagamento }}
          </div>

          <!-- Rodapé -->
          <div class="flex items-center justify-end gap-2 border-t border-border/60 pt-3">
            <Button type="button" variant="outline" @click="modalPagamentoAberto = false">
              Cancelar
            </Button>
            <Button
              type="submit"
              class="bg-emerald-600 hover:bg-emerald-700 text-white"
              :disabled="mutacaoRegistrarPagamento.isPending.value"
            >
              <LoaderCircle
                v-if="mutacaoRegistrarPagamento.isPending.value"
                class="size-4 animate-spin"
                aria-hidden="true"
              />
              Confirmar Baixa
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>

    <!-- Modal: Detalhes e Composição da Cobrança -->
    <Sheet v-model:open="modalDetalhesAberto">
      <SheetContent
        side="center"
        class="max-h-[90vh] w-[calc(100vw-2rem)] max-w-[560px] overflow-y-auto rounded-[10px] p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-3">
          <SheetTitle class="flex items-center gap-2 text-base font-semibold text-foreground">
            <FileText class="size-5 text-primary" aria-hidden="true" />
            Detalhes do Boleto
          </SheetTitle>
        </SheetHeader>

        <div v-if="cobrancaSelecionada" class="space-y-4 pt-3 text-sm">
          <!-- Identificação -->
          <div
            class="grid grid-cols-2 gap-3 rounded-lg border border-border/60 bg-muted/30 p-3 text-xs"
          >
            <div>
              <span class="text-muted-foreground">Código de Referência:</span>
              <p class="font-mono font-bold text-foreground">
                {{ cobrancaSelecionada.codigo_referencia }}
              </p>
            </div>
            <div>
              <span class="text-muted-foreground">Situação:</span>
              <p>
                <Badge
                  :variant="situacaoBadge[cobrancaSelecionada.situacao]?.variant ?? 'outline'"
                  :class="situacaoBadge[cobrancaSelecionada.situacao]?.classe"
                >
                  {{
                    situacaoBadge[cobrancaSelecionada.situacao]?.rotulo ??
                    cobrancaSelecionada.situacao
                  }}
                </Badge>
              </p>
            </div>
            <div>
              <span class="text-muted-foreground">Unidade / Bloco:</span>
              <p class="font-medium text-foreground">
                Apto {{ cobrancaSelecionada.unidade_codigo }} • {{ cobrancaSelecionada.bloco_nome }}
              </p>
            </div>
            <div>
              <span class="text-muted-foreground">Sacado / Morador:</span>
              <p class="font-medium text-foreground">{{ cobrancaSelecionada.morador_nome }}</p>
              <p v-if="cobrancaSelecionada.morador_email" class="text-[11px] text-muted-foreground">
                {{ cobrancaSelecionada.morador_email }}
              </p>
            </div>
            <div>
              <span class="text-muted-foreground">Data de Emissão:</span>
              <p class="font-medium text-foreground">
                {{ formatarData(cobrancaSelecionada.data_emissao) }}
              </p>
            </div>
            <div>
              <span class="text-muted-foreground">Data de Vencimento:</span>
              <p class="font-medium text-foreground">
                {{ formatarData(cobrancaSelecionada.data_vencimento) }}
              </p>
            </div>
          </div>

          <!-- Composição / Itens do Boleto -->
          <div class="space-y-2">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              Composição de Itens da Cobrança
            </h4>
            <div class="divide-y divide-border/60 rounded-lg border border-border/60 bg-card">
              <div
                v-for="(item, idx) in cobrancaSelecionada.itens ?? []"
                :key="idx"
                class="flex items-center justify-between p-2.5 text-xs"
              >
                <div>
                  <span class="font-medium text-foreground">{{ item.descricao }}</span>
                  <p class="text-[11px] text-muted-foreground">Tipo: {{ item.tipo_item }}</p>
                </div>
                <span class="font-semibold text-foreground">{{ formatarMoeda(item.valor) }}</span>
              </div>
              <div class="flex items-center justify-between bg-muted/40 p-2.5 text-xs font-bold">
                <span>Total do Boleto:</span>
                <span class="text-sm text-foreground">
                  {{ formatarMoeda(cobrancaSelecionada.valor_original) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Rodapé do Modal -->
          <div class="flex justify-end border-t border-border/60 pt-3">
            <Button variant="outline" @click="modalDetalhesAberto = false"> Fechar </Button>
          </div>
        </div>
      </SheetContent>
    </Sheet>
  </section>
</template>
