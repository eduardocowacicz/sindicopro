<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { CirclePlus, LoaderCircle } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import EntityForm from '@/components/app/crud/EntityForm.vue'
import HistoricoSheet from '@/components/app/crud/HistoricoSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import type { CampoFormulario } from '@/lib/crud/types'
import { mensagemDeErro } from '@/lib/http-error'
import * as categoriasApi from '@/modules/financeiro/api/categorias.api'
import * as contasApi from '@/modules/financeiro/api/contas.api'
import * as movimentacoesApi from '@/modules/financeiro/api/movimentacoes.api'
import * as tiposCobrancaApi from '@/modules/financeiro/api/tipos-cobranca.api'
import type { MovimentacaoFinanceira } from '@/modules/financeiro/types/movimentacao.types'

const filaCliente = useQueryClient()
const pagina = ref(1)

const { data, isPending } = useQuery({
  queryKey: ['financeiro-movimentacoes', pagina],
  queryFn: () => movimentacoesApi.listarMovimentacoes({ pagina: pagina.value }),
})

const { data: contas } = useQuery({
  queryKey: ['financeiro-contas-selecao'],
  queryFn: contasApi.listarContasParaSelecao,
})
const { data: categorias } = useQuery({
  queryKey: ['financeiro-categorias-selecao'],
  queryFn: categoriasApi.listarCategoriasParaSelecao,
})
const { data: tiposCobranca } = useQuery({
  queryKey: ['financeiro-tipos-cobranca-selecao'],
  queryFn: tiposCobrancaApi.listarTiposCobrancaParaSelecao,
})

const situacaoRotulo: Record<string, string> = {
  RASCUNHO: 'Rascunho',
  CONTABILIZADO: 'Contabilizado',
  CANCELADO: 'Cancelado',
  ESTORNADO: 'Estornado',
}

const campos = computed<CampoFormulario[]>(() => [
  {
    chave: 'conta_financeira_id',
    rotulo: 'Conta',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (contas.value ?? []).map((c) => ({ valor: c.id_publico, rotulo: c.nome })),
  },
  {
    chave: 'categoria_financeira_id',
    rotulo: 'Categoria',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (categorias.value ?? []).map((c) => ({
      valor: c.id_publico,
      rotulo: `${c.codigo} — ${c.nome}`,
    })),
  },
  {
    chave: 'tipo_cobranca_id',
    rotulo: 'Tipo de cobrança',
    tipo: 'selecao',
    opcoes: (tiposCobranca.value ?? []).map((t) => ({ valor: t.id_publico, rotulo: t.nome })),
  },
  {
    chave: 'direcao',
    rotulo: 'Direção',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'ENTRADA', rotulo: 'Entrada' },
      { valor: 'SAIDA', rotulo: 'Saída' },
    ],
  },
  { chave: 'descricao', rotulo: 'Descrição', tipo: 'texto', obrigatorio: true },
  { chave: 'nome_contraparte', rotulo: 'Contraparte', tipo: 'texto' },
  { chave: 'numero_documento', rotulo: 'Documento', tipo: 'texto' },
  { chave: 'data_competencia', rotulo: 'Competência', tipo: 'data', obrigatorio: true },
  { chave: 'data_efetiva', rotulo: 'Data efetiva', tipo: 'data', obrigatorio: true },
  { chave: 'data_vencimento', rotulo: 'Vencimento', tipo: 'data' },
  { chave: 'valor', rotulo: 'Valor', tipo: 'numero', obrigatorio: true },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
])

const sheetAberto = ref(false)
const valoresFormulario = reactive<Record<string, unknown>>({})
const erroFormulario = ref('')

function abrirCriacao(): void {
  Object.keys(valoresFormulario).forEach((chave) => delete valoresFormulario[chave])
  campos.value.forEach((campo) => {
    valoresFormulario[campo.chave] = ''
  })
  erroFormulario.value = ''
  sheetAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () => movimentacoesApi.criarMovimentacao({ ...valoresFormulario }),
  onSuccess: async () => {
    sheetAberto.value = false
    await filaCliente.invalidateQueries({ queryKey: ['financeiro-movimentacoes'] })
  },
  onError: (erro: unknown) => {
    erroFormulario.value = mensagemDeErro(erro)
  },
})

const mutacaoCancelar = useMutation({
  mutationFn: (idPublico: string) => movimentacoesApi.cancelarMovimentacao(idPublico),
  onSuccess: async () => {
    await filaCliente.invalidateQueries({ queryKey: ['financeiro-movimentacoes'] })
  },
})

const historicoAberto = ref(false)
const movimentacaoHistorico = ref<MovimentacaoFinanceira | null>(null)

function abrirHistorico(movimentacao: MovimentacaoFinanceira): void {
  movimentacaoHistorico.value = movimentacao
  historicoAberto.value = true
}

function formatarValor(valor: string, direcao: string): string {
  const numero = Number(valor)
  const formatado = numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
  return direcao === 'SAIDA' ? `- ${formatado}` : formatado
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader
      title="Lançamentos financeiros"
      description="Lançamentos básicos de entrada e saída, vinculados a uma conta e categoria."
      eyebrow="Financeiro"
    >
      <template #actions>
        <Button @click="abrirCriacao">
          <CirclePlus class="size-4" aria-hidden="true" />
          Novo lançamento
        </Button>
      </template>
    </PageHeader>

    <div class="overflow-x-auto rounded-md border">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th class="px-4 py-2 font-medium">Descrição</th>
            <th class="px-4 py-2 font-medium">Conta</th>
            <th class="px-4 py-2 font-medium">Categoria</th>
            <th class="px-4 py-2 font-medium">Competência</th>
            <th class="px-4 py-2 font-medium">Valor</th>
            <th class="px-4 py-2 font-medium">Situação</th>
            <th class="px-4 py-2 font-medium text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isPending">
            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
              <LoaderCircle class="mx-auto size-5 animate-spin" aria-hidden="true" />
            </td>
          </tr>
          <tr v-else-if="!data || data.dados.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
              Nenhum lançamento cadastrado.
            </td>
          </tr>
          <tr
            v-for="movimentacao in data?.dados ?? []"
            :key="movimentacao.id_publico"
            class="border-t"
          >
            <td class="px-4 py-2">{{ movimentacao.descricao }}</td>
            <td class="px-4 py-2">{{ movimentacao.conta?.nome ?? '—' }}</td>
            <td class="px-4 py-2">{{ movimentacao.categoria?.nome ?? '—' }}</td>
            <td class="px-4 py-2 text-xs text-muted-foreground">
              {{ movimentacao.data_competencia }}
            </td>
            <td class="px-4 py-2">{{ formatarValor(movimentacao.valor, movimentacao.direcao) }}</td>
            <td class="px-4 py-2">
              <Badge :variant="movimentacao.situacao === 'RASCUNHO' ? 'secondary' : 'default'">
                {{ situacaoRotulo[movimentacao.situacao] }}
              </Badge>
            </td>
            <td class="px-4 py-2">
              <div class="flex justify-end gap-1">
                <Button variant="ghost" size="sm" @click="abrirHistorico(movimentacao)"
                  >Histórico</Button
                >
                <Button
                  v-if="movimentacao.situacao === 'RASCUNHO'"
                  variant="ghost"
                  size="sm"
                  @click="mutacaoCancelar.mutate(movimentacao.id_publico)"
                >
                  Cancelar
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Sheet v-model:open="sheetAberto">
      <SheetContent class="w-full overflow-y-auto sm:max-w-md">
        <SheetHeader>
          <SheetTitle>Novo lançamento</SheetTitle>
        </SheetHeader>
        <div class="space-y-4 px-4 pb-4">
          <EntityForm :campos="campos" :valores="valoresFormulario" :criando="true" />
          <p
            v-if="erroFormulario"
            class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
          >
            {{ erroFormulario }}
          </p>
        </div>
        <SheetFooter>
          <Button :disabled="mutacaoCriar.isPending.value" @click="mutacaoCriar.mutate()">
            <LoaderCircle
              v-if="mutacaoCriar.isPending.value"
              class="size-4 animate-spin"
              aria-hidden="true"
            />
            Salvar
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>

    <HistoricoSheet
      v-model:aberto="historicoAberto"
      titulo="Lançamento"
      :query-key="`movimentacao-${movimentacaoHistorico?.id_publico ?? 'novo'}`"
      :buscar="
        movimentacaoHistorico
          ? () => movimentacoesApi.historicoMovimentacao(movimentacaoHistorico!.id_publico)
          : null
      "
    />
  </section>
</template>
