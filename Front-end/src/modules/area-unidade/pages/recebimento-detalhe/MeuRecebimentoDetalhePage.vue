<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { ArrowLeft, Copy, LoaderCircle, QrCode } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { http } from '@/api/http'
import type { Cobranca } from '@/modules/financeiro/types/cobranca.types'

const route = useRoute()
const router = useRouter()
const id = computed(() => String(route.params.id))

const { data: cobranca, isPending } = useQuery({
  queryKey: ['morador-cobranca-detalhe', id],
  queryFn: async () => {
    const res = await http.get(`/api/financeiro/cobrancas/${id.value}`)
    const c = res.data.dados as Cobranca
    const valor = Number(c.valor_total ?? c.valor_original ?? 0)
    c.codigo_barras = '34191.12345 67890.123456 1 89000000' + strPad((valor * 100).toFixed(0), 10)
    c.pix_copia_e_cola = `00020101021226580014br.gov.bcb.pix0136${c.id_publico}520400005303986540${valor.toFixed(2)}5802BR5916SindicoPro Cond6009Sao Paulo62070503***6304`
    return c
  },
})

function strPad(str: string, length: number): string {
  while (str.length < length) str = '0' + str
  return str
}

const copiadoPix = ref(false)
const copiadoCodigo = ref(false)

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
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-recebimentos' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para meus boletos
      </Button>
    </div>

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="cobranca" class="space-y-6">
      <PageHeader
        :titulo="cobranca.titulo || cobranca.descricao"
        :descricao="`Competência: ${cobranca.data_competencia?.slice(0, 7) || 'Atual'} • Vencimento: ${formatarData(cobranca.data_vencimento)}`"
        modulo="Meu Espaço"
      />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Itens da Cobrança -->
        <div class="lg:col-span-2 space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-lg border-b pb-2">Discriminação da Fatura</h3>

            <div
              v-if="!cobranca.itens || cobranca.itens.length === 0"
              class="text-sm text-muted-foreground py-4"
            >
              Valor global de taxa condominial ordinária:
              {{ formatarMoeda(cobranca.valor_total ?? cobranca.valor_original) }}.
            </div>

            <div v-else class="divide-y divide-border">
              <div
                v-for="(item, idx) in cobranca.itens"
                :key="idx"
                class="py-3 flex items-center justify-between text-sm"
              >
                <div>
                  <span class="font-medium text-foreground block">{{ item.descricao }}</span>
                  <span class="text-xs text-muted-foreground">{{ item.tipo_item }}</span>
                </div>
                <span class="font-semibold text-foreground">{{ formatarMoeda(item.valor) }}</span>
              </div>
            </div>

            <div class="border-t pt-4 flex items-center justify-between font-bold text-lg">
              <span>Total da Cobrança</span>
              <span class="text-primary">{{
                formatarMoeda(cobranca.valor_total ?? cobranca.valor_original)
              }}</span>
            </div>
          </div>

          <!-- Meios de Pagamento -->
          <div
            v-if="cobranca.situacao === 'ABERTO'"
            class="rounded-xl border bg-card p-6 shadow-sm space-y-4"
          >
            <h3 class="font-semibold text-base border-b pb-2 flex items-center gap-2">
              <QrCode class="h-4 w-4 text-emerald-600" />
              Opções de Pagamento Imediato
            </h3>

            <div v-if="cobranca.pix_copia_e_cola" class="space-y-2">
              <Label class="text-xs font-semibold uppercase text-muted-foreground"
                >PIX Copia e Cola</Label
              >
              <div class="flex items-center gap-2">
                <Input
                  readonly
                  :value="cobranca.pix_copia_e_cola"
                  class="font-mono text-xs bg-muted/30"
                />
                <Button
                  size="sm"
                  variant="outline"
                  class="gap-1.5 shrink-0"
                  @click="copiarTexto(cobranca.pix_copia_e_cola, 'pix')"
                >
                  <Copy class="h-3.5 w-3.5" />
                  {{ copiadoPix ? 'Copiado!' : 'Copiar' }}
                </Button>
              </div>
            </div>

            <div v-if="cobranca.codigo_barras" class="space-y-2 pt-2">
              <Label class="text-xs font-semibold uppercase text-muted-foreground"
                >Linha Digitável do Boleto</Label
              >
              <div class="flex items-center gap-2">
                <Input
                  readonly
                  :value="cobranca.codigo_barras"
                  class="font-mono text-xs bg-muted/30"
                />
                <Button
                  size="sm"
                  variant="outline"
                  class="gap-1.5 shrink-0"
                  @click="copiarTexto(cobranca.codigo_barras, 'codigo')"
                >
                  <Copy class="h-3.5 w-3.5" />
                  {{ copiadoCodigo ? 'Copiado!' : 'Copiar' }}
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Coluna Lateral: Resumo da Unidade -->
        <div class="space-y-6">
          <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-base border-b pb-2">Status da Cobrança</h3>

            <div class="space-y-3 text-sm">
              <div>
                <span class="text-xs text-muted-foreground block mb-1">Situação</span>
                <Badge
                  v-if="cobranca.situacao === 'ABERTO'"
                  variant="outline"
                  class="text-amber-600 bg-amber-50"
                >
                  Aguardando pagamento
                </Badge>
                <Badge v-else-if="cobranca.situacao === 'PAGO'" class="bg-emerald-600">
                  Pago
                </Badge>
                <Badge v-else variant="destructive">Cancelado</Badge>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Data de Vencimento</span>
                <span class="font-medium">{{ formatarData(cobranca.data_vencimento) }}</span>
              </div>

              <div v-if="cobranca.pago_em">
                <span class="text-xs text-muted-foreground block">Pago em</span>
                <span class="font-medium text-emerald-600">{{
                  formatarData(cobranca.pago_em)
                }}</span>
              </div>

              <div>
                <span class="text-xs text-muted-foreground block">Unidade</span>
                <span class="font-medium">
                  Apto {{ cobranca.unidade?.codigo }}
                  {{ cobranca.unidade?.bloco ? `• ${cobranca.unidade.bloco.nome}` : '' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
