<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import {
  AlertCircle,
  Building,
  Calendar,
  CalendarDays,
  CheckCircle2,
  Clock,
  CreditCard,
  LoaderCircle,
  Megaphone,
  Receipt,
} from '@lucide/vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const router = useRouter()
const autenticacao = useAutenticacaoStore()

const { data: dashboard, isPending } = useQuery({
  queryKey: ['morador-dashboard'],
  queryFn: () => moradorApi.obterDashboardMorador(),
})

const unidades = computed(() => dashboard.value?.unidades ?? [])
const cobrancasPendentes = computed(() => dashboard.value?.cobrancas_pendentes ?? [])
const reservasFuturas = computed(() => dashboard.value?.reservas_futuras ?? [])
const comunicadosRecentes = computed(() => dashboard.value?.comunicados_recentes ?? [])
const ocorrencias = computed(() => dashboard.value?.ocorrencias_em_andamento ?? [])

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return '—'
  const partes = dataStr.split('-')
  if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`
  return new Date(dataStr).toLocaleDateString('pt-BR')
}

function formatarMoeda(val: number | string): string {
  return Number(val || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<template>
  <div class="space-y-6">
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-6 border shadow-sm"
    >
      <div class="space-y-1">
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          Olá, {{ autenticacao.sessao?.pessoa?.nome_completo ?? 'Morador' }}!
        </h1>
        <p class="text-sm text-muted-foreground flex items-center gap-2">
          <span>Bem-vindo à Área do Condômino.</span>
          <Badge
            v-for="u in unidades"
            :key="u.id_publico"
            variant="secondary"
            class="gap-1 font-medium"
          >
            <Building class="h-3 w-3" />
            Apto {{ u.codigo }} {{ u.bloco ? `• ${u.bloco.nome}` : '' }}
          </Badge>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <Button size="sm" class="gap-1.5 shadow-sm" @click="router.push({ name: 'app-reservas' })">
          <CalendarDays class="h-4 w-4" />
          Reservar Área
        </Button>
        <Button
          size="sm"
          variant="outline"
          class="gap-1.5"
          @click="router.push({ name: 'app-ocorrencia-nova' })"
        >
          <AlertCircle class="h-4 w-4" />
          Abrir Ocorrência
        </Button>
      </div>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else class="space-y-6">
      <!-- 4 Indicadores Rápidos -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Boletos -->
        <div
          class="rounded-xl border bg-card p-5 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all space-y-3"
          @click="router.push({ name: 'app-recebimentos' })"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wide"
              >Boletos em Aberto</span
            >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600"
            >
              <Receipt class="h-5 w-5" />
            </div>
          </div>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-bold">{{ cobrancasPendentes.length }}</span>
            <span v-if="cobrancasPendentes.length > 0" class="text-xs text-amber-600 font-medium">
              Vencimentos próximos
            </span>
            <span v-else class="text-xs text-emerald-600 font-medium">Tudo em dia!</span>
          </div>
        </div>

        <!-- Reservas -->
        <div
          class="rounded-xl border bg-card p-5 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all space-y-3"
          @click="router.push({ name: 'app-reservas' })"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wide"
              >Minhas Reservas</span
            >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
            >
              <CalendarDays class="h-5 w-5" />
            </div>
          </div>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-bold">{{ reservasFuturas.length }}</span>
            <span class="text-xs text-muted-foreground">Agendamentos futuros</span>
          </div>
        </div>

        <!-- Comunicados -->
        <div
          class="rounded-xl border bg-card p-5 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all space-y-3"
          @click="router.push({ name: 'app-comunicados' })"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wide"
              >Comunicados</span
            >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600"
            >
              <Megaphone class="h-5 w-5" />
            </div>
          </div>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-bold">{{ comunicadosRecentes.length }}</span>
            <span class="text-xs text-muted-foreground">Publicações ativas</span>
          </div>
        </div>

        <!-- Ocorrências -->
        <div
          class="rounded-xl border bg-card p-5 shadow-sm hover:border-primary/40 hover:shadow-md cursor-pointer transition-all space-y-3"
          @click="router.push({ name: 'app-ocorrencias' })"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wide"
              >Meus Chamados</span
            >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600"
            >
              <AlertCircle class="h-5 w-5" />
            </div>
          </div>
          <div class="flex items-baseline justify-between">
            <span class="text-2xl font-bold">{{ ocorrencias.length }}</span>
            <span class="text-xs text-muted-foreground">Em andamento</span>
          </div>
        </div>
      </div>

      <!-- Seção Principal: Próximos Boletos e Próximas Reservas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Boletos Pendentes -->
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div class="border-b bg-muted/30 p-4 flex items-center justify-between">
            <h3 class="font-semibold text-base flex items-center gap-2">
              <CreditCard class="h-4 w-4 text-primary" />
              Boletos e Cotas Condominiais
            </h3>
            <Button variant="ghost" size="sm" @click="router.push({ name: 'app-recebimentos' })">
              Ver todos
            </Button>
          </div>

          <div
            v-if="cobrancasPendentes.length === 0"
            class="p-8 text-center text-muted-foreground space-y-1"
          >
            <CheckCircle2 class="h-8 w-8 text-emerald-500 mx-auto mb-2" />
            <p class="font-medium text-foreground">Parabéns! Não há cobranças pendentes.</p>
            <p class="text-xs">Sua unidade está em dia com todas as cotas condominiais.</p>
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="cob in cobrancasPendentes.slice(0, 3)"
              :key="cob.id_publico"
              class="p-4 flex items-center justify-between hover:bg-muted/20 transition-colors"
            >
              <div>
                <span class="font-semibold text-sm block">{{ cob.titulo }}</span>
                <span class="text-xs text-muted-foreground">
                  Vencimento:
                  <strong class="text-foreground">{{ formatarData(cob.data_vencimento) }}</strong>
                </span>
              </div>
              <div class="text-right">
                <span class="font-bold text-base text-foreground block">{{
                  formatarMoeda(cob.valor_total ?? cob.valor_original ?? 0)
                }}</span>
                <Button
                  size="sm"
                  variant="outline"
                  class="mt-1 h-7 text-xs"
                  @click="
                    router.push({ name: 'app-recebimento-detalhe', params: { id: cob.id_publico } })
                  "
                >
                  Pagar / PIX
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Próximas Reservas -->
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div class="border-b bg-muted/30 p-4 flex items-center justify-between">
            <h3 class="font-semibold text-base flex items-center gap-2">
              <CalendarDays class="h-4 w-4 text-primary" />
              Próximos Agendamentos de Espaços
            </h3>
            <Button variant="ghost" size="sm" @click="router.push({ name: 'app-reservas' })">
              Ver agenda
            </Button>
          </div>

          <div
            v-if="reservasFuturas.length === 0"
            class="p-8 text-center text-muted-foreground space-y-1"
          >
            <Calendar class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
            <p class="font-medium text-foreground">Você não possui reservas agendadas.</p>
            <p class="text-xs">
              Aproveite para reservar o salão de festas, churrasqueira ou quadra.
            </p>
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="r in reservasFuturas.slice(0, 3)"
              :key="r.id_publico"
              class="p-4 flex items-center justify-between hover:bg-muted/20 transition-colors"
            >
              <div>
                <span class="font-semibold text-sm block">{{ r.ambiente?.nome }}</span>
                <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                  <Clock class="h-3 w-3" />
                  {{ formatarData(r.data_reserva) }} •
                  {{ r.faixa_horario?.nome ?? r.inicia_em.slice(0, 5) }}
                </span>
              </div>
              <div>
                <Badge class="bg-emerald-600">Confirmada</Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Feed de Comunicados Recentes -->
      <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
        <div class="border-b bg-muted/30 p-4 flex items-center justify-between">
          <h3 class="font-semibold text-base flex items-center gap-2">
            <Megaphone class="h-4 w-4 text-primary" />
            Últimos Comunicados do Condomínio
          </h3>
          <Button variant="ghost" size="sm" @click="router.push({ name: 'app-comunicados' })">
            Ver todos
          </Button>
        </div>

        <div
          v-if="comunicadosRecentes.length === 0"
          class="p-8 text-center text-sm text-muted-foreground"
        >
          Nenhum comunicado recente publicado.
        </div>

        <div v-else class="divide-y divide-border">
          <div
            v-for="c in comunicadosRecentes.slice(0, 3)"
            :key="c.id_publico"
            class="p-4 hover:bg-muted/20 cursor-pointer transition-colors space-y-1"
            @click="router.push({ name: 'app-comunicado-detalhe', params: { id: c.id_publico } })"
          >
            <div class="flex items-center justify-between">
              <span
                class="font-semibold text-foreground text-sm hover:text-primary transition-colors"
              >
                {{ c.titulo }}
              </span>
              <span class="text-xs text-muted-foreground">{{ formatarData(c.publicado_em) }}</span>
            </div>
            <p class="text-xs text-muted-foreground line-clamp-2">{{ c.conteudo }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
