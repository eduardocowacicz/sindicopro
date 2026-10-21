<script setup lang="ts">
import { ArrowDownLeft, ArrowUpRight, RefreshCw, WalletCards } from '@lucide/vue'
import { computed } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useDashboardFinanceiroQuery } from '@/modules/inicio/queries/dashboard.queries'
import { useStatusQuery } from '@/modules/inicio/queries/status.queries'
import EvolucaoFinanceiraChart from './components/EvolucaoFinanceiraChart.vue'

const statusQuery = useStatusQuery()
const dashboardQuery = useDashboardFinanceiroQuery()
const meses = computed(() => dashboardQuery.data.value?.meses ?? [])
const totalEntradas = computed(() => meses.value.reduce((total, mes) => total + mes.entradas, 0))
const totalSaidas = computed(() => meses.value.reduce((total, mes) => total + mes.saidas, 0))
const resultado = computed(() => totalEntradas.value - totalSaidas.value)
const temMovimentacoes = computed(() => totalEntradas.value > 0 || totalSaidas.value > 0)
const formatarMoeda = (valor: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor)

const statusText = computed(() => {
  if (statusQuery.isPending.value) return 'Verificando API'
  if (statusQuery.isError.value) return 'API indisponível'
  return `${statusQuery.data.value?.application ?? 'SindicoPro'} conectado`
})

const indicators = computed(() => [
  {
    label: 'Receitas',
    value: formatarMoeda(totalEntradas.value),
    description: 'Entradas contabilizadas nos últimos 12 meses',
    icon: ArrowDownLeft,
  },
  {
    label: 'Despesas',
    value: formatarMoeda(totalSaidas.value),
    description: 'Saídas contabilizadas nos últimos 12 meses',
    icon: ArrowUpRight,
  },
  {
    label: 'Resultado',
    value: formatarMoeda(resultado.value),
    description: 'Receitas menos despesas no período',
    icon: WalletCards,
  },
])

async function atualizarDados(): Promise<void> {
  await Promise.all([statusQuery.refetch(), dashboardQuery.refetch()])
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-sm font-medium text-primary">Visão financeira</p>
        <h1 class="text-2xl font-semibold tracking-tight">Painel do condomínio</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Evolução mensal de receitas e despesas contabilizadas nos últimos 12 meses.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <Badge :variant="statusQuery.isError.value ? 'destructive' : 'outline'">
          {{ statusText }}
        </Badge>
        <Button
          variant="outline"
          :disabled="statusQuery.isFetching.value || dashboardQuery.isFetching.value"
          @click="atualizarDados"
        >
          <RefreshCw class="mr-2 size-4" aria-hidden="true" />
          Atualizar dados
        </Button>
      </div>
    </div>

    <div
      v-if="dashboardQuery.isPending.value"
      class="grid gap-4 md:grid-cols-3"
      aria-label="Carregando indicadores financeiros"
      aria-busy="true"
    >
      <Card v-for="item in 3" :key="item">
        <CardContent class="space-y-3 pt-6">
          <div class="h-4 w-24 animate-pulse rounded bg-muted" />
          <div class="h-8 w-36 animate-pulse rounded bg-muted" />
          <div class="h-3 w-48 animate-pulse rounded bg-muted" />
        </CardContent>
      </Card>
    </div>

    <Card v-else-if="dashboardQuery.isError.value" role="alert">
      <CardHeader>
        <CardTitle>Não foi possível carregar o painel financeiro</CardTitle>
        <CardDescription>
          Confira a conexão com a API e sua permissão para consultar movimentações financeiras.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button :disabled="dashboardQuery.isFetching.value" @click="dashboardQuery.refetch()">
          Tentar novamente
        </Button>
      </CardContent>
    </Card>

    <template v-else-if="dashboardQuery.data.value">
      <div class="grid gap-4 md:grid-cols-3">
        <Card v-for="indicator in indicators" :key="indicator.label">
          <CardHeader class="flex-row items-center justify-between pb-2">
            <CardDescription>{{ indicator.label }}</CardDescription>
            <component
              :is="indicator.icon"
              class="size-4 text-muted-foreground"
              aria-hidden="true"
            />
          </CardHeader>
          <CardContent>
            <p class="text-2xl font-semibold">{{ indicator.value }}</p>
            <p class="mt-1 text-xs text-muted-foreground">{{ indicator.description }}</p>
          </CardContent>
        </Card>
      </div>

      <div v-if="temMovimentacoes" class="grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Evolução de receitas e despesas</CardTitle>
            <CardDescription>Comparação mês a mês no período selecionado.</CardDescription>
          </CardHeader>
          <CardContent>
            <EvolucaoFinanceiraChart :meses="meses" tipo="linha" titulo="Gráfico de linhas" />
            <div class="mt-4 flex flex-wrap gap-4 text-sm">
              <span class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-primary" aria-hidden="true" />
                Receitas
              </span>
              <span class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-destructive" aria-hidden="true" />
                Despesas
              </span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Comparativo mensal</CardTitle>
            <CardDescription>Valores de receitas e despesas em cada mês.</CardDescription>
          </CardHeader>
          <CardContent>
            <EvolucaoFinanceiraChart :meses="meses" tipo="barras" titulo="Gráfico de barras" />
            <div class="mt-4 flex flex-wrap gap-4 text-sm">
              <span class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-primary" aria-hidden="true" />
                Receitas
              </span>
              <span class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-destructive" aria-hidden="true" />
                Despesas
              </span>
            </div>
          </CardContent>
        </Card>
      </div>
      <Card v-else role="status">
        <CardHeader>
          <CardTitle>Sem movimentações no período</CardTitle>
          <CardDescription>
            Os gráficos serão exibidos assim que houver lançamentos financeiros contabilizados.
          </CardDescription>
        </CardHeader>
      </Card>
    </template>
  </section>
</template>
