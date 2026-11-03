<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowDownLeft, ArrowUpRight, CirclePlus, LoaderCircle } from '@lucide/vue'
import { reactive, ref } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import HistoricoSheet from '@/components/app/crud/HistoricoSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { errosDeCampos, mensagemDeErro } from '@/lib/http-error'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
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
const { data: pessoas } = useQuery({
  queryKey: ['pessoas-prestadores-fornecedores'],
  queryFn: async () => {
    const dados = await pessoasApi.listarPessoasParaSelecao({
      tipos: ['PRESTADOR_SERVICO', 'FORNECEDOR'],
    })
    return (dados ?? []).filter((p) => p.tipo === 'PRESTADOR_SERVICO' || p.tipo === 'FORNECEDOR')
  },
})

const situacaoRotulo: Record<string, string> = {
  RASCUNHO: 'Rascunho',
  CONTABILIZADO: 'Contabilizado',
  CANCELADO: 'Cancelado',
  ESTORNADO: 'Estornado',
}

const tipoRotuloPessoa: Record<string, string> = {
  MORADOR: 'Morador',
  PRESTADOR_SERVICO: 'Prestador de serviço',
  FORNECEDOR: 'Fornecedor',
}

const classeSelect =
  'border-input h-9 w-full min-w-0 rounded-md border bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20'

interface FormularioLancamento {
  direcao: 'ENTRADA' | 'SAIDA'
  descricao: string
  valor: number | string
  conta_financeira_id: string
  categoria_financeira_id: string
  data_competencia: string
  data_vencimento: string
  data_efetiva: string
  pessoa_id: string
  nome_contraparte: string
  tipo_cobranca_id: string
  numero_documento: string
  observacoes: string
  utiliza_fundo_reserva: boolean
}

const sheetAberto = ref(false)
const valoresFormulario = reactive<FormularioLancamento>({
  direcao: 'SAIDA',
  descricao: '',
  valor: '',
  conta_financeira_id: '',
  categoria_financeira_id: '',
  data_competencia: '',
  data_vencimento: '',
  data_efetiva: '',
  pessoa_id: '',
  nome_contraparte: '',
  tipo_cobranca_id: '',
  numero_documento: '',
  observacoes: '',
  utiliza_fundo_reserva: false,
})
const erroFormulario = ref('')
const errosCampos = reactive<Record<string, string>>({})

function limparErros(): void {
  erroFormulario.value = ''
  Object.keys(errosCampos).forEach((chave) => delete errosCampos[chave])
}

function abrirCriacao(): void {
  valoresFormulario.direcao = 'SAIDA'
  valoresFormulario.descricao = ''
  valoresFormulario.valor = ''
  valoresFormulario.conta_financeira_id = ''
  valoresFormulario.categoria_financeira_id = ''
  valoresFormulario.data_competencia = ''
  valoresFormulario.data_vencimento = ''
  valoresFormulario.data_efetiva = ''
  valoresFormulario.pessoa_id = ''
  valoresFormulario.nome_contraparte = ''
  valoresFormulario.tipo_cobranca_id = ''
  valoresFormulario.numero_documento = ''
  valoresFormulario.observacoes = ''
  valoresFormulario.utiliza_fundo_reserva = false
  limparErros()
  sheetAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () => {
    const payload: Record<string, unknown> = { ...valoresFormulario }
    if (!payload.pessoa_id) delete payload.pessoa_id
    if (!payload.tipo_cobranca_id) delete payload.tipo_cobranca_id
    if (!payload.data_vencimento) delete payload.data_vencimento
    if (valoresFormulario.direcao !== 'SAIDA') {
      payload.utiliza_fundo_reserva = false
      payload.origem_recurso = 'CAPITAL_GIRO'
    } else {
      payload.origem_recurso = valoresFormulario.utiliza_fundo_reserva
        ? 'FUNDO_RESERVA'
        : 'CAPITAL_GIRO'
    }
    return movimentacoesApi.criarMovimentacao(payload)
  },
  onMutate: () => {
    limparErros()
  },
  onSuccess: async () => {
    sheetAberto.value = false
    await filaCliente.invalidateQueries({ queryKey: ['financeiro-movimentacoes'] })
  },
  onError: (erro: unknown) => {
    erroFormulario.value = mensagemDeErro(erro)
    Object.assign(errosCampos, errosDeCampos(erro))
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
            <td class="px-4 py-2">
              <div class="font-medium flex items-center gap-2">
                <span>{{ movimentacao.descricao }}</span>
                <Badge
                  v-if="movimentacao.utiliza_fundo_reserva"
                  variant="outline"
                  class="border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-300 text-[10px] px-1.5 py-0"
                >
                  Fundo de Reserva
                </Badge>
              </div>
              <div
                v-if="movimentacao.pessoa?.nome_completo || movimentacao.nome_contraparte"
                class="text-xs text-muted-foreground"
              >
                <span v-if="movimentacao.pessoa?.nome_completo">
                  {{ movimentacao.pessoa.nome_completo }}
                  <span v-if="movimentacao.pessoa.tipo" class="text-[11px] opacity-75">
                    ({{ tipoRotuloPessoa[movimentacao.pessoa.tipo] ?? movimentacao.pessoa.tipo }})
                  </span>
                </span>
                <span v-else>{{ movimentacao.nome_contraparte }}</span>
              </div>
            </td>
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
      <SheetContent
        side="center"
        class="max-h-[90vh] w-[calc(100vw-2rem)] max-w-[700px] overflow-y-auto rounded-[10px] p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-2">
          <SheetTitle class="text-base font-semibold text-foreground sm:text-lg">
            Novo lançamento
          </SheetTitle>
        </SheetHeader>

        <form
          class="grid grid-cols-12 gap-x-4 gap-y-3 pt-2"
          @submit.prevent="mutacaoCriar.mutate()"
        >
          <!-- Linha 1 - Direção (12 colunas) -->
          <div class="col-span-12 space-y-1">
            <label class="block text-xs font-semibold text-foreground">
              Direção <span class="text-destructive">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2 rounded-lg bg-muted p-1">
              <button
                type="button"
                :class="[
                  'flex cursor-pointer items-center justify-center gap-1.5 rounded-md py-1.5 text-xs font-semibold transition-all',
                  valoresFormulario.direcao === 'ENTRADA'
                    ? 'bg-background font-bold text-emerald-600 shadow-xs dark:text-emerald-400'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="valoresFormulario.direcao = 'ENTRADA'"
              >
                <ArrowDownLeft class="size-4 text-emerald-500" aria-hidden="true" />
                Entrada
              </button>
              <button
                type="button"
                :class="[
                  'flex cursor-pointer items-center justify-center gap-1.5 rounded-md py-1.5 text-xs font-semibold transition-all',
                  valoresFormulario.direcao === 'SAIDA'
                    ? 'bg-background font-bold text-rose-600 shadow-xs dark:text-rose-400'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="valoresFormulario.direcao = 'SAIDA'"
              >
                <ArrowUpRight class="size-4 text-rose-500" aria-hidden="true" />
                Saída
              </button>
            </div>
            <p v-if="errosCampos.direcao" class="text-xs text-destructive">
              {{ errosCampos.direcao }}
            </p>
          </div>

          <!-- Linha 2 - Dados Principais -->
          <div class="col-span-12 space-y-1 sm:col-span-8">
            <label for="campo-descricao" class="block text-xs font-semibold text-foreground">
              Descrição <span class="text-destructive">*</span>
            </label>
            <Input
              id="campo-descricao"
              v-model="valoresFormulario.descricao"
              placeholder="Descrição do lançamento"
              :aria-invalid="Boolean(errosCampos.descricao)"
            />
            <p v-if="errosCampos.descricao" class="text-xs text-destructive">
              {{ errosCampos.descricao }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-4">
            <label for="campo-valor" class="block text-xs font-semibold text-foreground">
              Valor <span class="text-destructive">*</span>
            </label>
            <Input
              id="campo-valor"
              v-model="valoresFormulario.valor"
              type="number"
              step="0.01"
              placeholder="0,00"
              :aria-invalid="Boolean(errosCampos.valor)"
            />
            <p v-if="errosCampos.valor" class="text-xs text-destructive">
              {{ errosCampos.valor }}
            </p>
          </div>

          <!-- Linha 3 - Classificação Financeira -->
          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-conta" class="block text-xs font-semibold text-foreground">
              Conta <span class="text-destructive">*</span>
            </label>
            <select
              id="campo-conta"
              v-model="valoresFormulario.conta_financeira_id"
              :class="[
                classeSelect,
                errosCampos.conta_financeira_id && 'border-destructive ring-destructive/20',
              ]"
              :aria-invalid="Boolean(errosCampos.conta_financeira_id)"
            >
              <option value="" disabled>Selecione...</option>
              <option v-for="c in contas ?? []" :key="c.id_publico" :value="c.id_publico">
                {{ c.nome }}
              </option>
            </select>
            <p v-if="errosCampos.conta_financeira_id" class="text-xs text-destructive">
              {{ errosCampos.conta_financeira_id }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-categoria" class="block text-xs font-semibold text-foreground">
              Categoria <span class="text-destructive">*</span>
            </label>
            <select
              id="campo-categoria"
              v-model="valoresFormulario.categoria_financeira_id"
              :class="[
                classeSelect,
                errosCampos.categoria_financeira_id && 'border-destructive ring-destructive/20',
              ]"
              :aria-invalid="Boolean(errosCampos.categoria_financeira_id)"
            >
              <option value="" disabled>Selecione...</option>
              <option v-for="cat in categorias ?? []" :key="cat.id_publico" :value="cat.id_publico">
                {{ cat.codigo }} — {{ cat.nome }}
              </option>
            </select>
            <p v-if="errosCampos.categoria_financeira_id" class="text-xs text-destructive">
              {{ errosCampos.categoria_financeira_id }}
            </p>
          </div>

          <!-- Linha 4 - Datas do Lançamento -->
          <div class="col-span-12 space-y-1 sm:col-span-4">
            <label for="campo-competencia" class="block text-xs font-semibold text-foreground">
              Competência <span class="text-destructive">*</span>
            </label>
            <Input
              id="campo-competencia"
              v-model="valoresFormulario.data_competencia"
              type="date"
              :aria-invalid="Boolean(errosCampos.data_competencia)"
            />
            <p v-if="errosCampos.data_competencia" class="text-xs text-destructive">
              {{ errosCampos.data_competencia }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-4">
            <label for="campo-vencimento" class="block text-xs font-semibold text-foreground">
              Vencimento
            </label>
            <Input
              id="campo-vencimento"
              v-model="valoresFormulario.data_vencimento"
              type="date"
              :aria-invalid="Boolean(errosCampos.data_vencimento)"
            />
            <p v-if="errosCampos.data_vencimento" class="text-xs text-destructive">
              {{ errosCampos.data_vencimento }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-4">
            <label for="campo-data-efetiva" class="block text-xs font-semibold text-foreground">
              Data efetiva <span class="text-destructive">*</span>
            </label>
            <Input
              id="campo-data-efetiva"
              v-model="valoresFormulario.data_efetiva"
              type="date"
              :aria-invalid="Boolean(errosCampos.data_efetiva)"
            />
            <p v-if="errosCampos.data_efetiva" class="text-xs text-destructive">
              {{ errosCampos.data_efetiva }}
            </p>
          </div>

          <!-- Divisor Visual / Seção de Opcionais -->
          <div class="col-span-12 border-t border-border/60 pt-2">
            <span class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
              Detalhes e Vínculos (Opcional)
            </span>
          </div>

          <!-- Linha 5 - Envolvidos / Contraparte -->
          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-pessoa" class="block text-xs font-semibold text-foreground">
              Prestador de serviço / Fornecedor
            </label>
            <select
              id="campo-pessoa"
              v-model="valoresFormulario.pessoa_id"
              :class="[
                classeSelect,
                errosCampos.pessoa_id && 'border-destructive ring-destructive/20',
              ]"
              :aria-invalid="Boolean(errosCampos.pessoa_id)"
            >
              <option value="">Selecione...</option>
              <option v-for="p in pessoas ?? []" :key="p.id_publico" :value="p.id_publico">
                {{ p.nome_completo }} ({{ tipoRotuloPessoa[p.tipo] ?? p.tipo }})
              </option>
            </select>
            <p class="text-[11px] text-muted-foreground">
              Opcional: selecione o prestador de serviço ou fornecedor vinculado.
            </p>
            <p v-if="errosCampos.pessoa_id" class="text-xs text-destructive">
              {{ errosCampos.pessoa_id }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-contraparte" class="block text-xs font-semibold text-foreground">
              Contraparte (texto livre)
            </label>
            <Input
              id="campo-contraparte"
              v-model="valoresFormulario.nome_contraparte"
              placeholder="Nome da contraparte ou favorecido"
              :aria-invalid="Boolean(errosCampos.nome_contraparte)"
            />
            <p v-if="errosCampos.nome_contraparte" class="text-xs text-destructive">
              {{ errosCampos.nome_contraparte }}
            </p>
          </div>

          <!-- Linha 6 - Cobrança e Documento -->
          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-tipo-cobranca" class="block text-xs font-semibold text-foreground">
              Tipo de cobrança
            </label>
            <select
              id="campo-tipo-cobranca"
              v-model="valoresFormulario.tipo_cobranca_id"
              :class="[
                classeSelect,
                errosCampos.tipo_cobranca_id && 'border-destructive ring-destructive/20',
              ]"
              :aria-invalid="Boolean(errosCampos.tipo_cobranca_id)"
            >
              <option value="">Selecione...</option>
              <option v-for="t in tiposCobranca ?? []" :key="t.id_publico" :value="t.id_publico">
                {{ t.nome }}
              </option>
            </select>
            <p v-if="errosCampos.tipo_cobranca_id" class="text-xs text-destructive">
              {{ errosCampos.tipo_cobranca_id }}
            </p>
          </div>

          <div class="col-span-12 space-y-1 sm:col-span-6">
            <label for="campo-documento" class="block text-xs font-semibold text-foreground">
              Documento
            </label>
            <Input
              id="campo-documento"
              v-model="valoresFormulario.numero_documento"
              placeholder="Nº da NF, recibo ou boleto"
              :aria-invalid="Boolean(errosCampos.numero_documento)"
            />
            <p v-if="errosCampos.numero_documento" class="text-xs text-destructive">
              {{ errosCampos.numero_documento }}
            </p>
          </div>

          <!-- Fundo de Reserva (somente para Saída) -->
          <div
            v-if="valoresFormulario.direcao === 'SAIDA'"
            class="col-span-12 rounded-lg border border-amber-300/60 bg-amber-500/10 p-3"
          >
            <label class="flex cursor-pointer items-start gap-2.5">
              <input
                v-model="valoresFormulario.utiliza_fundo_reserva"
                type="checkbox"
                class="mt-0.5 size-4 rounded border-input text-amber-600 focus:ring-amber-500"
              />
              <div class="space-y-0.5">
                <span class="block text-xs font-semibold text-foreground">
                  Pago com recursos do Fundo de Reserva
                </span>
                <p class="text-[11px] text-muted-foreground">
                  Esta despesa utilizará o saldo de Fundo de Reserva da conta bancária. Ela
                  aparecerá discriminada na prestação de contas do fechamento, mas
                  <strong>não será cobrada novamente</strong> dos moradores.
                </p>
              </div>
            </label>
          </div>

          <!-- Linha 7 - Observações -->
          <div class="col-span-12 space-y-1">
            <label for="campo-observacoes" class="block text-xs font-semibold text-foreground">
              Observações
            </label>
            <textarea
              id="campo-observacoes"
              v-model="valoresFormulario.observacoes"
              rows="2"
              :class="[
                'border-input w-full min-w-0 resize-y rounded-md border bg-background px-3 py-1.5 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20',
                errosCampos.observacoes && 'border-destructive ring-destructive/20',
              ]"
              placeholder="Observações complementares..."
              :aria-invalid="Boolean(errosCampos.observacoes)"
            />
            <p v-if="errosCampos.observacoes" class="text-xs text-destructive">
              {{ errosCampos.observacoes }}
            </p>
          </div>

          <!-- Alerta de erro geral -->
          <div v-if="erroFormulario" class="col-span-12">
            <p class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {{ erroFormulario }}
            </p>
          </div>

          <!-- Rodapé de ações -->
          <div
            class="col-span-12 mt-1 flex flex-row items-center justify-end gap-2 border-t border-border/60 pt-3"
          >
            <Button variant="outline" type="button" @click="sheetAberto = false"> Cancelar </Button>
            <Button type="submit" :disabled="mutacaoCriar.isPending.value">
              <LoaderCircle
                v-if="mutacaoCriar.isPending.value"
                class="size-4 animate-spin"
                aria-hidden="true"
              />
              Salvar
            </Button>
          </div>
        </form>
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
