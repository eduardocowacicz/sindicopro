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
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import * as unidadesApi from '@/modules/cadastros/api/unidades.api'
import * as vinculosApi from '@/modules/cadastros/api/vinculos.api'
import type { Vinculo } from '@/modules/cadastros/types/vinculo.types'

const filaCliente = useQueryClient()
const pagina = ref(1)

const { data, isPending } = useQuery({
  queryKey: ['vinculos', pagina],
  queryFn: () => vinculosApi.listarVinculos({ pagina: pagina.value }),
})

const { data: unidades } = useQuery({
  queryKey: ['unidades-selecao'],
  queryFn: unidadesApi.listarUnidadesParaSelecao,
})

const { data: pessoas } = useQuery({
  queryKey: ['pessoas-selecao'],
  queryFn: pessoasApi.listarPessoasParaSelecao,
})

const rotuloTipoVinculo: Record<string, string> = {
  PROPRIETARIO: 'Proprietário',
  LOCATARIO: 'Locatário',
  MORADOR: 'Morador',
  DEPENDENTE: 'Dependente',
}

const campos = computed<CampoFormulario[]>(() => [
  {
    chave: 'unidade_id',
    rotulo: 'Apartamento',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (unidades.value ?? []).map((u) => ({ valor: u.id_publico, rotulo: u.codigo })),
  },
  {
    chave: 'pessoa_id',
    rotulo: 'Pessoa',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (pessoas.value ?? []).map((p) => ({ valor: p.id_publico, rotulo: p.nome_completo })),
  },
  {
    chave: 'tipo_vinculo',
    rotulo: 'Tipo de vínculo',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'PROPRIETARIO', rotulo: 'Proprietário' },
      { valor: 'LOCATARIO', rotulo: 'Locatário' },
      { valor: 'MORADOR', rotulo: 'Morador' },
      { valor: 'DEPENDENTE', rotulo: 'Dependente' },
    ],
  },
  {
    chave: 'papel_cobranca',
    rotulo: 'Papel de cobrança',
    tipo: 'selecao',
    opcoes: [
      { valor: 'PROPRIETARIO', rotulo: 'Proprietário' },
      { valor: 'MORADOR', rotulo: 'Morador' },
    ],
    ajuda: 'Necessário se for o responsável financeiro.',
  },
  { chave: 'contato_principal', rotulo: 'Contato principal', tipo: 'checkbox' },
  { chave: 'responsavel_financeiro', rotulo: 'Responsável financeiro', tipo: 'checkbox' },
  { chave: 'inicio_vigencia', rotulo: 'Início de vigência', tipo: 'data', obrigatorio: true },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
])

const sheetAberto = ref(false)
const valoresFormulario = reactive<Record<string, unknown>>({})
const erroFormulario = ref('')

function abrirCriacao(): void {
  Object.keys(valoresFormulario).forEach((chave) => delete valoresFormulario[chave])
  campos.value.forEach((campo) => {
    valoresFormulario[campo.chave] = campo.tipo === 'checkbox' ? false : ''
  })
  erroFormulario.value = ''
  sheetAberto.value = true
}

const mutacaoCriar = useMutation({
  mutationFn: () => vinculosApi.criarVinculo({ ...valoresFormulario }),
  onSuccess: async () => {
    sheetAberto.value = false
    await filaCliente.invalidateQueries({ queryKey: ['vinculos'] })
  },
  onError: (erro: unknown) => {
    erroFormulario.value = mensagemDeErro(erro)
  },
})

const mutacaoEncerrar = useMutation({
  mutationFn: (idPublico: string) => vinculosApi.encerrarVinculo(idPublico),
  onSuccess: async () => {
    await filaCliente.invalidateQueries({ queryKey: ['vinculos'] })
  },
})

const historicoAberto = ref(false)
const vinculoHistorico = ref<Vinculo | null>(null)

function abrirHistorico(vinculo: Vinculo): void {
  vinculoHistorico.value = vinculo
  historicoAberto.value = true
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader
      title="Vínculos com apartamentos"
      description="Relação entre pessoas e apartamentos: moradores, proprietários e ocupantes."
      eyebrow="Cadastros"
    >
      <template #actions>
        <Button @click="abrirCriacao">
          <CirclePlus class="size-4" aria-hidden="true" />
          Novo vínculo
        </Button>
      </template>
    </PageHeader>

    <div class="overflow-x-auto rounded-md border">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th class="px-4 py-2 font-medium">Apartamento</th>
            <th class="px-4 py-2 font-medium">Pessoa</th>
            <th class="px-4 py-2 font-medium">Tipo</th>
            <th class="px-4 py-2 font-medium">Vigência</th>
            <th class="px-4 py-2 font-medium">Situação</th>
            <th class="px-4 py-2 font-medium text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isPending">
            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
              <LoaderCircle class="mx-auto size-5 animate-spin" aria-hidden="true" />
            </td>
          </tr>
          <tr v-else-if="!data || data.dados.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
              Nenhum vínculo cadastrado.
            </td>
          </tr>
          <tr v-for="vinculo in data?.dados ?? []" :key="vinculo.id_publico" class="border-t">
            <td class="px-4 py-2">{{ vinculo.unidade?.codigo ?? '—' }}</td>
            <td class="px-4 py-2">{{ vinculo.pessoa?.nome_completo ?? '—' }}</td>
            <td class="px-4 py-2">{{ rotuloTipoVinculo[vinculo.tipo_vinculo] }}</td>
            <td class="px-4 py-2 text-xs text-muted-foreground">
              {{ vinculo.inicio_vigencia }} — {{ vinculo.fim_vigencia ?? 'atual' }}
            </td>
            <td class="px-4 py-2">
              <Badge :variant="vinculo.fim_vigencia ? 'secondary' : 'default'">
                {{ vinculo.fim_vigencia ? 'Encerrado' : 'Vigente' }}
              </Badge>
            </td>
            <td class="px-4 py-2">
              <div class="flex justify-end gap-1">
                <Button variant="ghost" size="sm" @click="abrirHistorico(vinculo)"
                  >Histórico</Button
                >
                <Button
                  v-if="!vinculo.fim_vigencia"
                  variant="ghost"
                  size="sm"
                  @click="mutacaoEncerrar.mutate(vinculo.id_publico)"
                >
                  Encerrar
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Sheet v-model:open="sheetAberto">
      <SheetContent side="center" class="overflow-y-auto">
        <SheetHeader>
          <SheetTitle>Novo vínculo</SheetTitle>
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
      titulo="Vínculo"
      :query-key="`vinculo-${vinculoHistorico?.id_publico ?? 'novo'}`"
      :buscar="
        vinculoHistorico ? () => vinculosApi.historicoVinculo(vinculoHistorico!.id_publico) : null
      "
    />
  </section>
</template>
