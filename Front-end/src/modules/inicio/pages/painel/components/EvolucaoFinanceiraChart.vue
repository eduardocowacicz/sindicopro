<script setup lang="ts">
import { computed } from 'vue'

import type { MesFinanceiro } from '@/modules/inicio/api/dashboard.api'

const props = defineProps<{
  meses: MesFinanceiro[]
  tipo: 'linha' | 'barras'
  titulo: string
}>()

const area = { esquerda: 76, direita: 710, topo: 24, base: 236 }
const altura = area.base - area.topo
const largura = area.direita - area.esquerda

const maximo = computed(() =>
  Math.max(0, ...props.meses.flatMap((mes) => [mes.entradas, mes.saidas])),
)

const escala = computed(() => maximo.value || 1)
const marcadores = computed(() =>
  Array.from({ length: 5 }, (_, indice) => (escala.value * (4 - indice)) / 4),
)

function posicaoX(indice: number): number {
  if (props.meses.length <= 1) return area.esquerda + largura / 2
  return area.esquerda + (largura * indice) / (props.meses.length - 1)
}

function posicaoY(valor: number): number {
  return area.base - (valor / escala.value) * altura
}

function linha(campo: 'entradas' | 'saidas'): string {
  return props.meses.map((mes, indice) => `${posicaoX(indice)},${posicaoY(mes[campo])}`).join(' ')
}

function formatarValor(valor: number): string {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(valor)
}

function formatarMes(competencia: string): string {
  const [ano, mes] = competencia.split('-')
  if (!ano || !mes) return competencia

  return new Intl.DateTimeFormat('pt-BR', { month: 'short' })
    .format(new Date(Number(ano), Number(mes) - 1, 1, 12))
    .replace('.', '')
}

function alturaBarra(valor: number): number {
  return (valor / escala.value) * altura
}
</script>

<template>
  <svg
    class="h-auto w-full overflow-visible text-muted-foreground"
    viewBox="0 0 740 280"
    role="img"
    :aria-label="titulo"
  >
    <g v-for="marcador in marcadores" :key="marcador">
      <line
        :x1="area.esquerda"
        :x2="area.direita"
        :y1="posicaoY(marcador)"
        :y2="posicaoY(marcador)"
        class="stroke-border"
      />
      <text
        :x="area.esquerda - 10"
        :y="posicaoY(marcador) + 4"
        text-anchor="end"
        class="fill-current text-[10px]"
      >
        {{ formatarValor(marcador) }}
      </text>
    </g>

    <template v-if="tipo === 'linha'">
      <polyline
        :points="linha('entradas')"
        fill="none"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="text-primary"
      />
      <polyline
        :points="linha('saidas')"
        fill="none"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="text-destructive"
      />
      <g v-for="(mes, indice) in meses" :key="mes.competencia">
        <circle :cx="posicaoX(indice)" :cy="posicaoY(mes.entradas)" r="3" class="fill-primary" />
        <circle :cx="posicaoX(indice)" :cy="posicaoY(mes.saidas)" r="3" class="fill-destructive" />
      </g>
    </template>

    <template v-else>
      <g v-for="(mes, indice) in meses" :key="mes.competencia">
        <rect
          :x="posicaoX(indice) - 12"
          :y="area.base - alturaBarra(mes.entradas)"
          width="10"
          :height="alturaBarra(mes.entradas)"
          rx="2"
          class="fill-primary"
        />
        <rect
          :x="posicaoX(indice) + 2"
          :y="area.base - alturaBarra(mes.saidas)"
          width="10"
          :height="alturaBarra(mes.saidas)"
          rx="2"
          class="fill-destructive"
        />
      </g>
    </template>

    <g v-for="(mes, indice) in meses" :key="`${mes.competencia}-rotulo`">
      <text :x="posicaoX(indice)" y="260" text-anchor="middle" class="fill-current text-[10px]">
        {{ formatarMes(mes.competencia) }}
      </text>
    </g>
  </svg>
</template>
