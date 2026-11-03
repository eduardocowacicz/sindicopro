<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { CheckCircle2, Copy, LoaderCircle, QrCode } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import type { Cobranca } from '@/modules/financeiro/types/cobranca.types'

const router = useRouter()

const { data: cobrancasData, isPending } = useQuery({
  queryKey: ['morador-cobrancas'],
  queryFn: () => moradorApi.obterMinhasCobrancas(),
})
const cobrancas = computed(() => cobrancasData.value ?? [])

const modalPixAberto = ref(false)
const cobrancaSelecionada = ref<Cobranca | null>(null)
const copiadoPix = ref(false)
const copiadoCodigo = ref(false)

function abrirModalPix(c: Cobranca): void {
  cobrancaSelecionada.value = c
  copiadoPix.value = false
  copiadoCodigo.value = false
  modalPixAberto.value = true
}

async function copiarTexto(texto: string, tipo: 'pix' | 'codigo'): Promise<void> {
  await navigator.clipboard.writeText(texto)
  if (tipo === 'pix') {
    copiadoPix.value = true
    setTimeout(() => (copiadoPix.value = false), 2500)
  } else {
    copiadoCodigo.value = true
    setTimeout(() => (copiadoCodigo.value = false), 2500)
  }
}

function formatarData(dataStr?: string): string {
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
    <PageHeader
      titulo="Meus Boletos e Cobranças"
      descricao="Acompanhe as cotas condominiais da sua unidade, emita 2ª via e pague via PIX com compensação instantânea."
      modulo="Meu Espaço"
    />

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div
      v-else-if="cobrancas.length === 0"
      class="rounded-xl border bg-card p-12 text-center text-muted-foreground shadow-sm"
    >
      <CheckCircle2 class="mx-auto h-12 w-12 text-emerald-500 mb-3" />
      <p class="font-medium text-foreground">Tudo em dia!</p>
      <p class="text-sm">Não há nenhum boleto ou taxa pendente de pagamento para a sua unidade.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="c in cobrancas"
        :key="c.id_publico"
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border bg-card p-5 shadow-sm hover:border-primary/40 transition-all"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="font-bold text-base text-foreground">{{ c.titulo || c.descricao }}</span>
            <Badge
              v-if="c.situacao === 'ABERTO'"
              variant="outline"
              class="text-amber-600 bg-amber-50 border-amber-300"
            >
              Aguardando pagamento
            </Badge>
            <Badge v-else-if="c.situacao === 'PAGO'" class="bg-emerald-600"> Pago </Badge>
            <Badge v-else variant="destructive">Cancelado</Badge>
          </div>

          <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
            <span
              >Competência: <strong>{{ c.data_competencia?.slice(0, 7) || 'Atual' }}</strong></span
            >
            <span
              >Vencimento:
              <strong class="text-foreground">{{ formatarData(c.data_vencimento) }}</strong></span
            >
            <span v-if="c.pago_em" class="text-emerald-600 font-medium"
              >Pago em {{ formatarData(c.pago_em) }}</span
            >
          </div>
        </div>

        <div
          class="flex items-center justify-between sm:justify-end gap-3 pt-3 sm:pt-0 border-t sm:border-t-0"
        >
          <div class="text-left sm:text-right">
            <span class="text-xs text-muted-foreground block">Valor Total</span>
            <span class="text-lg font-bold text-foreground">{{
              formatarMoeda(c.valor_total ?? c.valor_original)
            }}</span>
          </div>

          <div class="flex items-center gap-2">
            <Button
              v-if="c.situacao === 'ABERTO'"
              size="sm"
              class="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
              @click="abrirModalPix(c)"
            >
              <QrCode class="h-4 w-4" />
              Pagar via PIX
            </Button>
            <Button
              size="sm"
              variant="outline"
              @click="
                router.push({ name: 'app-recebimento-detalhe', params: { id: c.id_publico } })
              "
            >
              Detalhes
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal PIX e Linha Digitável -->
    <Dialog :open="modalPixAberto" @update:open="modalPixAberto = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <QrCode class="h-5 w-5 text-emerald-600" />
            Pagamento via PIX e Boleto
          </DialogTitle>
          <DialogDescription>
            Pague de forma instantânea usando o PIX Copia-e-Cola ou utilize a linha digitável do
            boleto.
          </DialogDescription>
        </DialogHeader>

        <div v-if="cobrancaSelecionada" class="space-y-4 py-2">
          <div class="rounded-xl bg-muted/40 p-4 border text-center space-y-1">
            <span class="text-xs text-muted-foreground block">{{
              cobrancaSelecionada.titulo || cobrancaSelecionada.descricao
            }}</span>
            <span class="text-2xl font-bold text-foreground block">
              {{
                formatarMoeda(cobrancaSelecionada.valor_total ?? cobrancaSelecionada.valor_original)
              }}
            </span>
            <span class="text-xs text-muted-foreground block">
              Vencimento: {{ formatarData(cobrancaSelecionada.data_vencimento) }}
            </span>
          </div>

          <!-- PIX Copia e Cola -->
          <div v-if="cobrancaSelecionada.pix_copia_e_cola" class="space-y-2">
            <Label class="text-xs font-semibold uppercase text-muted-foreground"
              >PIX Copia e Cola</Label
            >
            <div class="flex items-center gap-2">
              <Input
                readonly
                :value="cobrancaSelecionada.pix_copia_e_cola"
                class="font-mono text-xs bg-muted/30"
              />
              <Button
                size="sm"
                variant="outline"
                class="shrink-0 gap-1.5"
                @click="copiarTexto(cobrancaSelecionada.pix_copia_e_cola, 'pix')"
              >
                <Copy class="h-3.5 w-3.5" />
                {{ copiadoPix ? 'Copiado!' : 'Copiar' }}
              </Button>
            </div>
          </div>

          <!-- Linha digitável -->
          <div v-if="cobrancaSelecionada.codigo_barras" class="space-y-2">
            <Label class="text-xs font-semibold uppercase text-muted-foreground"
              >Código de Barras / Linha Digitável</Label
            >
            <div class="flex items-center gap-2">
              <Input
                readonly
                :value="cobrancaSelecionada.codigo_barras"
                class="font-mono text-xs bg-muted/30"
              />
              <Button
                size="sm"
                variant="outline"
                class="shrink-0 gap-1.5"
                @click="copiarTexto(cobrancaSelecionada.codigo_barras, 'codigo')"
              >
                <Copy class="h-3.5 w-3.5" />
                {{ copiadoCodigo ? 'Copiado!' : 'Copiar' }}
              </Button>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="modalPixAberto = false">Fechar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
