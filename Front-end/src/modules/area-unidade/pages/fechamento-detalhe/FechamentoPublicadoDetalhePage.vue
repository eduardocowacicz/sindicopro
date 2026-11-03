<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { ArrowLeft, LoaderCircle, Shield, TrendingDown, TrendingUp, Wallet } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Button } from '@/components/ui/button'
import * as fechamentosApi from '@/modules/fechamentos/api/fechamentos.api'

const route = useRoute()
const router = useRouter()
const id = computed(() => String(route.params.id))

const { data: fechamento, isPending } = useQuery({
  queryKey: ['morador-fechamento-detalhe', id],
  queryFn: () => fechamentosApi.obterFechamento(id.value),
})

function formatarMesAno(dataStr?: string): string {
  if (!dataStr) return '—'
  const partes = dataStr.split('-')
  const data = new Date(Number(partes[0]), Number(partes[1]) - 1, 1)
  return data.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
}

function formatarMoeda(val: number | string): string {
  return Number(val || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-fechamentos' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar aos fechamentos
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="fechamento" class="space-y-6">
      <PageHeader
        :titulo="`Demonstrativo Financeiro: ${formatarMesAno(fechamento.data_competencia || fechamento.periodo)}`"
        descricao="Relatório detalhado de prestação de contas mensal para acompanhamento dos condôminos."
        modulo="Meu Espaço"
      />

      <!-- Cards de Resumo -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border bg-card p-5 shadow-sm space-y-1">
          <span
            class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-1"
          >
            <TrendingUp class="h-3.5 w-3.5 text-emerald-600" />
            Receitas do Mês
          </span>
          <span class="text-2xl font-bold text-emerald-600 block">
            {{ formatarMoeda(fechamento.total_entradas) }}
          </span>
        </div>

        <div class="rounded-xl border bg-card p-5 shadow-sm space-y-1">
          <span
            class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-1"
          >
            <TrendingDown class="h-3.5 w-3.5 text-rose-600" />
            Despesas do Mês
          </span>
          <span class="text-2xl font-bold text-rose-600 block">
            {{ formatarMoeda(fechamento.total_saidas) }}
          </span>
        </div>

        <div class="rounded-xl border bg-card p-5 shadow-sm space-y-1">
          <span
            class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-1"
          >
            <Shield class="h-3.5 w-3.5 text-primary" />
            Fundo de Reserva
          </span>
          <span class="text-2xl font-bold text-foreground block">
            {{ formatarMoeda(fechamento.fundo_reserva_arrecadado ?? 0) }}
          </span>
        </div>

        <div class="rounded-xl border bg-card p-5 shadow-sm space-y-1">
          <span
            class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-1"
          >
            <Wallet class="h-3.5 w-3.5 text-blue-600" />
            Saldo Final em Contas
          </span>
          <span class="text-2xl font-bold text-foreground block">
            {{ formatarMoeda(fechamento.saldo_final) }}
          </span>
        </div>
      </div>

      <!-- Discriminação de Despesas Pagas -->
      <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
        <div class="border-b bg-muted/30 p-4">
          <h3 class="font-semibold text-base">Despesas e Pagamentos Realizados no Mês</h3>
        </div>

        <div
          v-if="!fechamento.despesas_rateadas || fechamento.despesas_rateadas.length === 0"
          class="p-8 text-center text-sm text-muted-foreground"
        >
          Nenhuma despesa individual discriminada neste demonstrativo.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead
              class="border-b bg-muted/20 text-xs font-semibold text-muted-foreground uppercase"
            >
              <tr>
                <th class="px-6 py-3">Descrição da Despesa</th>
                <th class="px-6 py-3">Categoria</th>
                <th class="px-6 py-3">Fornecedor / Favorecido</th>
                <th class="px-6 py-3 text-right">Valor Rateado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr
                v-for="(d, idx) in fechamento.despesas_rateadas"
                :key="idx"
                class="hover:bg-muted/10"
              >
                <td class="px-6 py-3.5 font-medium text-foreground">
                  {{ d.movimentacao?.descricao ?? d.descricao ?? 'Despesa' }}
                </td>
                <td class="px-6 py-3.5 text-xs text-muted-foreground">
                  {{ d.movimentacao?.categoria?.nome ?? 'Geral' }}
                </td>
                <td class="px-6 py-3.5 text-xs">
                  {{
                    d.movimentacao?.pessoa?.nome_completo ?? d.movimentacao?.nome_contraparte ?? '—'
                  }}
                </td>
                <td class="px-6 py-3.5 text-right font-semibold text-rose-600">
                  {{ formatarMoeda(d.valor_cobrado ?? d.valor ?? 0) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
