<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import {
  CalendarDays,
  ChevronRight,
  FileText,
  LoaderCircle,
  PieChart,
  Shield,
  TrendingDown,
  TrendingUp,
} from '@lucide/vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'

const router = useRouter()

const { data: fechamentosData, isPending } = useQuery({
  queryKey: ['morador-fechamentos-publicados'],
  queryFn: () => moradorApi.obterFechamentosPublicados(),
})
const fechamentos = computed(() => fechamentosData.value ?? [])

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
    <PageHeader
      titulo="Fechamentos e Prestação de Contas"
      descricao="Transparência financeira: consulte os fechamentos mensais publicados pelo condomínio com receitas, despesas e fundo de reserva."
      modulo="Meu Espaço"
    />

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="fechamentos.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <PieChart class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
      <p class="font-medium text-foreground">Nenhum fechamento publicado</p>
      <p class="text-sm">
        A administração ainda não publicou os fechamentos das competências anteriores.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="f in fechamentos"
        :key="f.id_publico"
        class="rounded-2xl border bg-card p-6 shadow-sm hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between space-y-4"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between border-b pb-3">
            <div class="flex items-center gap-2">
              <CalendarDays class="h-5 w-5 text-primary" />
              <h3 class="font-bold text-lg capitalize text-foreground">
                {{ formatarMesAno(f.data_competencia || f.periodo) }}
              </h3>
            </div>
            <Badge class="bg-emerald-600">Fechado</Badge>
          </div>

          <div class="grid grid-cols-2 gap-3 text-sm pt-1">
            <div class="bg-muted/30 p-3 rounded-xl border">
              <span class="text-xs text-muted-foreground flex items-center gap-1">
                <TrendingUp class="h-3 w-3 text-emerald-600" />
                Receitas Arrecadadas
              </span>
              <span class="font-bold text-base text-emerald-600 block mt-1">
                {{ formatarMoeda(f.total_entradas) }}
              </span>
            </div>

            <div class="bg-muted/30 p-3 rounded-xl border">
              <span class="text-xs text-muted-foreground flex items-center gap-1">
                <TrendingDown class="h-3 w-3 text-rose-600" />
                Despesas Pagas
              </span>
              <span class="font-bold text-base text-rose-600 block mt-1">
                {{ formatarMoeda(f.total_saidas) }}
              </span>
            </div>
          </div>

          <div class="flex items-center justify-between text-xs text-muted-foreground pt-1">
            <span class="flex items-center gap-1">
              <Shield class="h-3.5 w-3.5 text-primary" />
              Fundo de Reserva:
              <strong class="text-foreground">{{
                formatarMoeda(f.fundo_reserva_arrecadado ?? 0)
              }}</strong>
            </span>
            <span
              >Saldo Final:
              <strong class="text-foreground">{{ formatarMoeda(f.saldo_final) }}</strong></span
            >
          </div>
        </div>

        <Button
          variant="outline"
          class="w-full gap-1.5 mt-2"
          @click="router.push({ name: 'app-fechamento-detalhe', params: { id: f.id_publico } })"
        >
          <FileText class="h-4 w-4" />
          Ver Demonstrativo Completo
          <ChevronRight class="h-4 w-4 ml-auto" />
        </Button>
      </div>
    </div>
  </div>
</template>
