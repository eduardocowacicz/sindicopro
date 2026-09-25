<script setup lang="ts" generic="T extends Record<string, any>">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { CirclePlus, History, LoaderCircle, Pencil, PowerOff, Power } from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import EntityForm from '@/components/app/crud/EntityForm.vue'
import HistoricoSheet from '@/components/app/crud/HistoricoSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import type { RegistroAuditoria, RespostaPaginada } from '@/lib/api-types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'
import { mensagemDeErro } from '@/lib/http-error'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const props = defineProps<{
  titulo: string
  descricao: string
  modulo: string
  queryKey: string
  colunas: ColunaTabela<T>[]
  campos: CampoFormulario[]
  idCampo?: string
  statusCampo?: string
  permissaoBase: string
  comBusca?: boolean
  listar: (params: { busca?: string; pagina: number }) => Promise<RespostaPaginada<T>>
  criar?: (dados: Record<string, unknown>) => Promise<T>
  atualizar?: (id: string, dados: Record<string, unknown>) => Promise<T>
  inativar?: (id: string, motivo?: string) => Promise<void>
  reativar?: (id: string) => Promise<void>
  buscarHistorico?: (id: string) => Promise<RegistroAuditoria[]>
}>()

const autenticacao = useAutenticacaoStore()
const filaCliente = useQueryClient()

const idCampo = props.idCampo ?? 'id_publico'
const statusCampo = props.statusCampo ?? 'ativo'

const busca = ref('')
const pagina = ref(1)

const { data, isPending, isError } = useQuery({
  queryKey: [props.queryKey, busca, pagina],
  queryFn: () => props.listar({ busca: busca.value || undefined, pagina: pagina.value }),
})

watch(busca, () => {
  pagina.value = 1
})

const itens = computed(() => data.value?.dados ?? [])
const meta = computed(() => data.value?.meta)

const podeCadastrar = computed(() => autenticacao.temPermissao(`${props.permissaoBase}.cadastrar`))
const podeEditar = computed(() => autenticacao.temPermissao(`${props.permissaoBase}.editar`))
const podeInativar = computed(() => autenticacao.temPermissao(`${props.permissaoBase}.inativar`))

const sheetAberto = ref(false)
const registroEmEdicao = ref<T | null>(null)
const valoresFormulario = reactive<Record<string, unknown>>({})
const erroFormulario = ref('')

const historicoAberto = ref(false)
const registroHistorico = ref<T | null>(null)

function abrirCriacao(): void {
  registroEmEdicao.value = null
  Object.keys(valoresFormulario).forEach((chave) => delete valoresFormulario[chave])
  props.campos.forEach((campo) => {
    valoresFormulario[campo.chave] = campo.tipo === 'checkbox' ? false : ''
  })
  erroFormulario.value = ''
  sheetAberto.value = true
}

function abrirEdicao(item: T): void {
  registroEmEdicao.value = item
  Object.keys(valoresFormulario).forEach((chave) => delete valoresFormulario[chave])
  props.campos.forEach((campo) => {
    valoresFormulario[campo.chave] = item[campo.chave] ?? (campo.tipo === 'checkbox' ? false : '')
  })
  erroFormulario.value = ''
  sheetAberto.value = true
}

function abrirHistorico(item: T): void {
  registroHistorico.value = item
  historicoAberto.value = true
}

const mutacaoSalvar = useMutation({
  mutationFn: async () => {
    if (registroEmEdicao.value) {
      const id = registroEmEdicao.value[idCampo] as string
      return props.atualizar?.(id, { ...valoresFormulario })
    }

    return props.criar?.({ ...valoresFormulario })
  },
  onSuccess: async () => {
    sheetAberto.value = false
    await filaCliente.invalidateQueries({ queryKey: [props.queryKey] })
  },
  onError: (erro: unknown) => {
    erroFormulario.value = mensagemDeErro(erro)
  },
})

const mutacaoSituacao = useMutation({
  mutationFn: async (item: T) => {
    const id = item[idCampo] as string

    if (item[statusCampo]) {
      return props.inativar?.(id)
    }

    return props.reativar?.(id)
  },
  onSuccess: async () => {
    await filaCliente.invalidateQueries({ queryKey: [props.queryKey] })
  },
})
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader :title="titulo" :description="descricao" :eyebrow="modulo">
      <template v-if="podeCadastrar && criar" #actions>
        <Button @click="abrirCriacao">
          <CirclePlus class="size-4" aria-hidden="true" />
          Novo
        </Button>
      </template>
    </PageHeader>

    <Input v-if="comBusca !== false" v-model="busca" placeholder="Pesquisar..." class="max-w-sm" />

    <div class="overflow-x-auto rounded-md border">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th v-for="coluna in colunas" :key="coluna.chave" class="px-4 py-2 font-medium">
              {{ coluna.rotulo }}
            </th>
            <th class="px-4 py-2 font-medium">Situação</th>
            <th class="px-4 py-2 font-medium text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isPending">
            <td :colspan="colunas.length + 2" class="px-4 py-8 text-center text-muted-foreground">
              <LoaderCircle class="mx-auto size-5 animate-spin" aria-hidden="true" />
            </td>
          </tr>
          <tr v-else-if="isError">
            <td :colspan="colunas.length + 2" class="px-4 py-8 text-center text-destructive">
              Não foi possível carregar os dados.
            </td>
          </tr>
          <tr v-else-if="itens.length === 0">
            <td :colspan="colunas.length + 2" class="px-4 py-8 text-center text-muted-foreground">
              Nenhum registro encontrado.
            </td>
          </tr>
          <tr v-for="item in itens" :key="item[idCampo]" class="border-t">
            <td v-for="coluna in colunas" :key="coluna.chave" :class="['px-4 py-2', coluna.classe]">
              {{ coluna.render ? coluna.render(item) : (item[coluna.chave] ?? '—') }}
            </td>
            <td class="px-4 py-2">
              <Badge
                v-if="statusCampo in item"
                :variant="item[statusCampo] ? 'default' : 'secondary'"
              >
                {{ item[statusCampo] ? 'Ativo' : 'Inativo' }}
              </Badge>
              <span v-else class="text-muted-foreground">—</span>
            </td>
            <td class="px-4 py-2">
              <div class="flex justify-end gap-1">
                <Button
                  v-if="buscarHistorico"
                  variant="ghost"
                  size="icon-sm"
                  title="Histórico"
                  @click="abrirHistorico(item)"
                >
                  <History class="size-4" aria-hidden="true" />
                </Button>
                <Button
                  v-if="podeEditar && atualizar"
                  variant="ghost"
                  size="icon-sm"
                  title="Editar"
                  @click="abrirEdicao(item)"
                >
                  <Pencil class="size-4" aria-hidden="true" />
                </Button>
                <Button
                  v-if="podeInativar && (inativar || reativar) && statusCampo in item"
                  variant="ghost"
                  size="icon-sm"
                  :title="item[statusCampo] ? 'Inativar' : 'Reativar'"
                  @click="mutacaoSituacao.mutate(item)"
                >
                  <PowerOff v-if="item[statusCampo]" class="size-4" aria-hidden="true" />
                  <Power v-else class="size-4" aria-hidden="true" />
                </Button>
                <slot name="acoes-extra" :item="item" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta && meta.ultima_pagina > 1" class="flex items-center justify-between text-sm">
      <span class="text-muted-foreground">
        Página {{ meta.pagina_atual }} de {{ meta.ultima_pagina }} — {{ meta.total }} registros
      </span>
      <div class="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="pagina <= 1"
          @click="pagina = Math.max(1, pagina - 1)"
        >
          Anterior
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="meta.pagina_atual >= meta.ultima_pagina"
          @click="pagina = pagina + 1"
        >
          Próxima
        </Button>
      </div>
    </div>

    <Sheet v-model:open="sheetAberto">
      <SheetContent class="w-full overflow-y-auto sm:max-w-md">
        <SheetHeader>
          <SheetTitle>{{ registroEmEdicao ? 'Editar' : 'Novo' }} — {{ titulo }}</SheetTitle>
        </SheetHeader>

        <div class="space-y-4 px-4 pb-4">
          <EntityForm :campos="campos" :valores="valoresFormulario" :criando="!registroEmEdicao" />
          <p
            v-if="erroFormulario"
            class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
          >
            {{ erroFormulario }}
          </p>
        </div>

        <SheetFooter>
          <Button :disabled="mutacaoSalvar.isPending.value" @click="mutacaoSalvar.mutate()">
            <LoaderCircle
              v-if="mutacaoSalvar.isPending.value"
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
      :titulo="titulo"
      :query-key="`${queryKey}-${registroHistorico ? registroHistorico[idCampo] : 'novo'}`"
      :buscar="
        buscarHistorico && registroHistorico
          ? () => buscarHistorico!(registroHistorico![idCampo] as string)
          : null
      "
    />
  </section>
</template>
