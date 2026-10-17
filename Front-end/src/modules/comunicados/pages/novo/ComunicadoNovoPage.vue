<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, Building2, Send, Users } from '@lucide/vue'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import * as comunicadosApi from '@/modules/comunicados/api/comunicados.api'
import { listarBlocosParaSelecao } from '@/modules/cadastros/api/blocos.api'

const router = useRouter()
const queryClient = useQueryClient()

const formulario = reactive({
  titulo: '',
  conteudo: '',
  tipo_publico: 'TODOS',
  blocos: [] as string[],
  publicar_agora: true,
})
const erroFormulario = ref('')

const { data: blocos = [] } = useQuery({
  queryKey: ['blocos-para-comunicado'],
  queryFn: () => listarBlocosParaSelecao(),
})

function alternarBloco(id: string): void {
  const idx = formulario.blocos.indexOf(id)
  if (idx >= 0) {
    formulario.blocos.splice(idx, 1)
  } else {
    formulario.blocos.push(id)
  }
}

const mutacaoSalvar = useMutation({
  mutationFn: () =>
    comunicadosApi.criarComunicado({
      titulo: formulario.titulo,
      conteudo: formulario.conteudo,
      tipo_publico: formulario.tipo_publico,
      blocos: formulario.tipo_publico === 'BLOCOS' ? formulario.blocos : [],
      publicar_agora: formulario.publicar_agora,
    }),
  onSuccess: (comunicado) => {
    queryClient.invalidateQueries({ queryKey: ['comunicados-lista'] })
    router.push({ name: 'admin-comunicado-detalhe', params: { id: comunicado.id_publico } })
  },
  onError: (err: unknown) => {
    erroFormulario.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao salvar comunicado.'
  },
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'admin-comunicados' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para a lista
      </Button>
    </div>

    <PageHeader
      titulo="Novo Comunicado Oficial"
      descricao="Crie e dissemine comunicados importantes com rastreabilidade de leitura para os moradores."
      modulo="Comunicados"
    />

    <form class="max-w-3xl space-y-6" @submit.prevent="mutacaoSalvar.mutate()">
      <div
        v-if="erroFormulario"
        class="rounded-lg bg-destructive/10 p-4 text-sm text-destructive font-medium"
      >
        {{ erroFormulario }}
      </div>

      <div class="rounded-xl border bg-card p-6 shadow-sm space-y-5">
        <div class="space-y-2">
          <Label for="titulo" class="text-sm font-semibold">Título do Comunicado *</Label>
          <Input
            id="titulo"
            v-model="formulario.titulo"
            required
            placeholder="Ex: Convocação para Assembleia Geral Extraordinária"
            class="text-base font-medium"
          />
        </div>

        <div class="space-y-2">
          <Label for="conteudo" class="text-sm font-semibold">Conteúdo da Mensagem *</Label>
          <textarea
            id="conteudo"
            v-model="formulario.conteudo"
            required
            rows="8"
            placeholder="Escreva aqui todas as informações, horários, instruções e regras pertinentes..."
            class="w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
          ></textarea>
        </div>

        <!-- Público Alvo -->
        <div class="space-y-3 pt-2 border-t">
          <Label class="text-sm font-semibold">Destinatários / Público-alvo *</Label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label
              :class="[
                'flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all',
                formulario.tipo_publico === 'TODOS'
                  ? 'border-primary bg-primary/5 text-primary shadow-sm ring-1 ring-primary'
                  : 'border-border hover:bg-muted/40',
              ]"
            >
              <input
                v-model="formulario.tipo_publico"
                type="radio"
                name="tipo_publico"
                value="TODOS"
                class="sr-only"
              />
              <Users class="h-5 w-5" />
              <div>
                <span class="font-semibold block text-sm">Todos os Condôminos</span>
                <span class="text-xs text-muted-foreground"
                  >Distribui para proprietários e moradores de todos os blocos</span
                >
              </div>
            </label>

            <label
              :class="[
                'flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all',
                formulario.tipo_publico === 'BLOCOS'
                  ? 'border-primary bg-primary/5 text-primary shadow-sm ring-1 ring-primary'
                  : 'border-border hover:bg-muted/40',
              ]"
            >
              <input
                v-model="formulario.tipo_publico"
                type="radio"
                name="tipo_publico"
                value="BLOCOS"
                class="sr-only"
              />
              <Building2 class="h-5 w-5" />
              <div>
                <span class="font-semibold block text-sm">Blocos Específicos</span>
                <span class="text-xs text-muted-foreground"
                  >Direciona o comunicado apenas para as torres selecionadas</span
                >
              </div>
            </label>
          </div>

          <!-- Seleção de Blocos quando BLOCOS está marcado -->
          <div
            v-if="formulario.tipo_publico === 'BLOCOS'"
            class="mt-4 p-4 rounded-xl border bg-muted/20 space-y-2"
          >
            <span class="text-xs font-semibold uppercase text-muted-foreground block"
              >Selecione os blocos:</span
            >
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <label
                v-for="b in blocos"
                :key="b.id_publico"
                class="flex items-center gap-2 p-2 rounded-lg border bg-card hover:bg-muted/50 cursor-pointer text-sm"
              >
                <input
                  type="checkbox"
                  :checked="formulario.blocos.includes(b.id_publico)"
                  class="rounded border-input text-primary focus:ring-primary"
                  @change="alternarBloco(b.id_publico)"
                />
                <span class="font-medium">{{ b.nome }}</span>
              </label>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t">
          <label class="flex items-center gap-3 cursor-pointer">
            <input
              v-model="formulario.publicar_agora"
              type="checkbox"
              class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
            />
            <div>
              <span class="text-sm font-semibold">Publicar e enviar imediatamente</span>
              <span class="text-xs text-muted-foreground block">
                Disponibiliza no app dos moradores e registra as leituras. Se desmarcado, ficará
                salvo como rascunho.
              </span>
            </div>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <Button type="button" variant="outline" @click="router.push({ name: 'admin-comunicados' })">
          Cancelar
        </Button>
        <Button type="submit" :disabled="mutacaoSalvar.isPending.value" class="gap-2">
          <Send class="h-4 w-4" />
          {{ formulario.publicar_agora ? 'Publicar Comunicado' : 'Salvar como Rascunho' }}
        </Button>
      </div>
    </form>
  </div>
</template>
