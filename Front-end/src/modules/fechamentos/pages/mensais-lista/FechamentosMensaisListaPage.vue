<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import {
  AlertTriangle,
  ArrowDownLeft,
  ArrowRight,
  ArrowUpRight,
  Calendar,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  FileCheck,
  FileText,
  LoaderCircle,
  Scale,
  Sparkles,
} from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { mensagemDeErro } from '@/lib/http-error'
import * as fechamentosApi from '@/modules/fechamentos/api/fechamentos.api'
import type { FechamentoMensalItem } from '@/modules/fechamentos/types/fechamento.types'

const router = useRouter()

const anoAtual = new Date().getFullYear()
const anoSelecionado = ref(anoAtual)
const filtroSituacao = ref<'TODOS' | 'PUBLICADO' | 'ABERTO' | 'SEM_MOVIMENTO'>('TODOS')

// Query dos fechamentos anuais
const {
  data: meses,
  isPending,
  error,
} = useQuery({
  queryKey: ['fechamentos-mensais', anoSelecionado, filtroSituacao],
  queryFn: () =>
    fechamentosApi.listarFechamentos({
      ano: anoSelecionado.value,
      situacao: filtroSituacao.value,
    }),
})

// Estatísticas anuais
const estatisticasAno = computed(() => {
  if (!meses.value) {
    return { totalEntradas: 0, totalSaidas: 0, resultado: 0, fechados: 0, abertos: 0 }
  }
  let totalEntradas = 0
  let totalSaidas = 0
  let fechados = 0
  let abertos = 0

  for (const m of meses.value) {
    totalEntradas += m.total_entradas
    totalSaidas += m.total_saidas
    if (m.situacao === 'PUBLICADO') fechados++
    if (m.situacao === 'ABERTO') abertos++
  }

  return {
    totalEntradas,
    totalSaidas,
    resultado: totalEntradas - totalSaidas,
    fechados,
    abertos,
  }
})

function formatarMoeda(valor: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor)
}

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '-'
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(dataStr))
}

function alterarAno(delta: number) {
  anoSelecionado.value += delta
}

// Navegação direta para a tela de detalhe daquele mês
function abrirDetalhes(item: FechamentoMensalItem) {
  router.push({
    name: 'admin-fechamento-mensal-detalhe',
    params: { id: item.id_publico },
  })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Fechamentos Mensais"
      description="Lista de competências financeiras do condomínio. Clique no mês desejado para visualizar lançamentos, conferir rateio e gerar as cobranças."
    />

    <!-- Barra de Controle: Navegação de Ano e Filtros -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Seletor de Ano -->
      <div class="flex items-center gap-2">
        <Button variant="outline" size="icon" class="h-9 w-9" @click="alterarAno(-1)">
          <ChevronLeft class="size-4" />
        </Button>
        <div class="flex items-center gap-2 rounded-md border bg-card px-4 py-1.5 shadow-xs">
          <Calendar class="size-4 text-muted-foreground" />
          <span class="font-semibold text-base tracking-tight">{{ anoSelecionado }}</span>
        </div>
        <Button variant="outline" size="icon" class="h-9 w-9" @click="alterarAno(1)">
          <ChevronRight class="size-4" />
        </Button>
        <Button
          v-if="anoSelecionado !== anoAtual"
          variant="ghost"
          size="sm"
          class="text-xs text-muted-foreground hover:text-foreground"
          @click="anoSelecionado = anoAtual"
        >
          Ano atual
        </Button>
      </div>

      <!-- Filtros de Situação -->
      <div class="flex flex-wrap items-center gap-1.5 rounded-lg border bg-muted/40 p-1 text-sm">
        <button
          type="button"
          class="rounded-md px-3 py-1 font-medium transition-colors"
          :class="
            filtroSituacao === 'TODOS'
              ? 'bg-background text-foreground shadow-xs'
              : 'text-muted-foreground hover:text-foreground'
          "
          @click="filtroSituacao = 'TODOS'"
        >
          Todos
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-1 font-medium transition-colors"
          :class="
            filtroSituacao === 'PUBLICADO'
              ? 'bg-background text-foreground shadow-xs'
              : 'text-muted-foreground hover:text-foreground'
          "
          @click="filtroSituacao = 'PUBLICADO'"
        >
          Fechados
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-1 font-medium transition-colors"
          :class="
            filtroSituacao === 'ABERTO'
              ? 'bg-background text-foreground shadow-xs'
              : 'text-muted-foreground hover:text-foreground'
          "
          @click="filtroSituacao = 'ABERTO'"
        >
          Abertos
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-1 font-medium transition-colors"
          :class="
            filtroSituacao === 'SEM_MOVIMENTO'
              ? 'bg-background text-foreground shadow-xs'
              : 'text-muted-foreground hover:text-foreground'
          "
          @click="filtroSituacao = 'SEM_MOVIMENTO'"
        >
          Sem movimento
        </button>
      </div>
    </div>

    <!-- Cards KPI de Resumo do Ano -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <Card class="border-border/80 shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Total Entradas (Ano)
          </CardTitle>
          <div class="rounded-full bg-emerald-500/10 p-2 text-emerald-600">
            <ArrowDownLeft class="size-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-emerald-600">
            {{ formatarMoeda(estatisticasAno.totalEntradas) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">Arrecadações e receitas computadas</p>
        </CardContent>
      </Card>

      <Card class="border-border/80 shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Total Saídas (Ano)
          </CardTitle>
          <div class="rounded-full bg-rose-500/10 p-2 text-rose-600">
            <ArrowUpRight class="size-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-rose-600">
            {{ formatarMoeda(estatisticasAno.totalSaidas) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">Despesas pagas pelo condomínio</p>
        </CardContent>
      </Card>

      <Card class="border-border/80 shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Resultado Consolidado
          </CardTitle>
          <div
            class="rounded-full p-2"
            :class="
              estatisticasAno.resultado >= 0
                ? 'bg-blue-500/10 text-blue-600'
                : 'bg-amber-500/10 text-amber-600'
            "
          >
            <Scale class="size-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div
            class="text-2xl font-bold tracking-tight"
            :class="estatisticasAno.resultado >= 0 ? 'text-blue-600' : 'text-amber-600'"
          >
            {{ formatarMoeda(estatisticasAno.resultado) }}
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{
              estatisticasAno.resultado >= 0
                ? 'Superávit financeiro anual'
                : 'Déficit acumulado no ano'
            }}
          </p>
        </CardContent>
      </Card>

      <Card class="border-border/80 shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Competências Fechadas
          </CardTitle>
          <div class="rounded-full bg-primary/10 p-2 text-primary">
            <FileCheck class="size-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight">{{ estatisticasAno.fechados }} / 12</div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ estatisticasAno.abertos }} competência(s) em aberto
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Feedback de carregamento ou erro -->
    <div
      v-if="isPending"
      class="flex flex-col items-center justify-center py-16 text-muted-foreground"
    >
      <LoaderCircle class="size-8 animate-spin text-primary" />
      <span class="mt-3 text-sm">Carregando lista de fechamentos...</span>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-destructive/20 bg-destructive/10 p-6 text-center text-sm text-destructive"
    >
      <AlertTriangle class="mx-auto size-6 mb-2" />
      Ocorreu um erro ao carregar os dados de fechamentos: {{ mensagemDeErro(error) }}
    </div>

    <!-- LISTA DIRETA DE FECHAMENTOS (Tabela Limpa) -->
    <div v-else class="rounded-xl border bg-card shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead
            class="bg-muted/40 font-semibold text-muted-foreground border-b uppercase tracking-wider text-[11px]"
          >
            <tr>
              <th class="py-3.5 px-4">Mês / Competência</th>
              <th class="py-3.5 px-4 text-center">Situação</th>
              <th class="py-3.5 px-4 text-right">Entradas (+)</th>
              <th class="py-3.5 px-4 text-right">Saídas (-)</th>
              <th class="py-3.5 px-4 text-right">Total a Ratear</th>
              <th class="py-3.5 px-4 text-center">Boletos / Cobranças</th>
              <th class="py-3.5 px-4">Fechamento</th>
              <th class="py-3.5 px-4 text-center">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="mesItem in meses"
              :key="mesItem.mes"
              class="hover:bg-muted/40 transition-colors cursor-pointer group"
              @click="abrirDetalhes(mesItem)"
            >
              <!-- Mês e Competência -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2.5">
                  <div
                    class="rounded-md p-1.5 transition-colors"
                    :class="
                      mesItem.situacao === 'PUBLICADO'
                        ? 'bg-emerald-500/10 text-emerald-600'
                        : mesItem.situacao === 'ABERTO'
                          ? 'bg-amber-500/10 text-amber-600'
                          : 'bg-muted text-muted-foreground'
                    "
                  >
                    <Calendar class="size-4" />
                  </div>
                  <div>
                    <span
                      class="font-bold text-sm text-foreground group-hover:text-primary transition-colors block"
                    >
                      {{ mesItem.nome_mes }}
                    </span>
                    <span class="text-[11px] text-muted-foreground">
                      Competência {{ String(mesItem.mes).padStart(2, '0') }}/{{ mesItem.ano }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Situação -->
              <td class="py-3.5 px-4 text-center">
                <Badge
                  v-if="mesItem.situacao === 'PUBLICADO'"
                  variant="outline"
                  class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 font-semibold"
                >
                  <CheckCircle2 class="mr-1 size-3.5" />
                  Fechado
                </Badge>
                <Badge
                  v-else-if="mesItem.situacao === 'ABERTO'"
                  variant="outline"
                  class="border-amber-500/30 bg-amber-500/10 text-amber-700 font-semibold"
                >
                  <Sparkles class="mr-1 size-3.5" />
                  Em Aberto
                </Badge>
                <Badge v-else variant="outline" class="text-muted-foreground border-dashed">
                  Sem movimento
                </Badge>
              </td>

              <!-- Entradas -->
              <td class="py-3.5 px-4 text-right font-medium text-emerald-600 whitespace-nowrap">
                {{ formatarMoeda(mesItem.total_entradas) }}
              </td>

              <!-- Saídas -->
              <td class="py-3.5 px-4 text-right font-medium text-rose-600 whitespace-nowrap">
                {{ formatarMoeda(mesItem.total_saidas) }}
              </td>

              <!-- Total Rateado -->
              <td class="py-3.5 px-4 text-right font-bold text-foreground whitespace-nowrap">
                {{ formatarMoeda(mesItem.total_rateado) }}
              </td>

              <!-- Boletos / Cobranças -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <Badge
                  v-if="mesItem.situacao === 'PUBLICADO'"
                  variant="secondary"
                  class="font-mono text-xs bg-emerald-500/10 text-emerald-700 border border-emerald-500/20"
                >
                  <FileText class="mr-1 size-3" />
                  {{ mesItem.quantidade_boletos }} boletos gerados
                </Badge>
                <span
                  v-else-if="mesItem.situacao === 'ABERTO'"
                  class="text-[11px] text-amber-700 font-medium"
                >
                  Pendente de geração
                </span>
                <span v-else class="text-muted-foreground text-[11px]">—</span>
              </td>

              <!-- Informações de Fechamento -->
              <td class="py-3.5 px-4 text-muted-foreground text-[11px]">
                <div v-if="mesItem.fechado_em">
                  <span class="block text-foreground font-medium">
                    {{ formatarData(mesItem.fechado_em) }}
                  </span>
                  <span v-if="mesItem.fechado_por" class="text-muted-foreground truncate block">
                    por {{ mesItem.fechado_por }}
                  </span>
                </div>
                <div v-else-if="mesItem.situacao === 'ABERTO'" class="text-amber-700">
                  <span>{{ mesItem.quantidade_movimentacoes }} lançamento(s) prontos</span>
                </div>
                <div v-else class="text-muted-foreground">—</div>
              </td>

              <!-- Ação direta para abrir detalhes -->
              <td class="py-3.5 px-4 text-center" @click.stop>
                <Button
                  variant="outline"
                  size="sm"
                  class="text-xs font-medium gap-1 group-hover:bg-primary group-hover:text-primary-foreground transition-all"
                  @click="abrirDetalhes(mesItem)"
                >
                  <span>Abrir Detalhes</span>
                  <ArrowRight class="size-3.5" />
                </Button>
              </td>
            </tr>

            <tr v-if="!meses || meses.length === 0">
              <td colspan="8" class="py-12 text-center text-muted-foreground text-sm">
                Nenhuma competência financeira encontrada para o ano de {{ anoSelecionado }}.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
