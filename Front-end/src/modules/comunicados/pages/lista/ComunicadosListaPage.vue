<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  Building2,
  CheckCircle,
  Eye,
  LoaderCircle,
  Megaphone,
  Plus,
  Search,
  Send,
  Users,
} from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import * as comunicadosApi from '@/modules/comunicados/api/comunicados.api'
import { listarBlocosParaSelecao } from '@/modules/cadastros/api/blocos.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const router = useRouter()
const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const podeCadastrar = computed(() => autenticacao.temPermissao('comunicados.cadastrar'))
const podePublicar = computed(() => autenticacao.temPermissao('comunicados.publicar'))

const busca = ref('')
const situacaoFiltro = ref('TODOS')
const pagina = ref(1)

const modalNovoAberto = ref(false)
const erroNovo = ref('')
const formularioNovo = reactive({
  titulo: '',
  conteudo: '',
  tipo_publico: 'TODOS',
  blocos: [] as string[],
  publicar_agora: true,
})

const { data, isPending } = useQuery({
  queryKey: ['comunicados-lista', busca, situacaoFiltro, pagina],
  queryFn: () =>
    comunicadosApi.listarComunicados({
      busca: busca.value || undefined,
      situacao: situacaoFiltro.value || undefined,
      pagina: pagina.value,
    }),
})
const comunicados = computed(() => data.value?.dados ?? [])

const { data: blocosDisponiveis = [] } = useQuery({
  queryKey: ['blocos-para-comunicado'],
  queryFn: () => listarBlocosParaSelecao(),
})

watch([busca, situacaoFiltro], () => {
  pagina.value = 1
})

function alternarBloco(id: string): void {
  const idx = formularioNovo.blocos.indexOf(id)
  if (idx >= 0) {
    formularioNovo.blocos.splice(idx, 1)
  } else {
    formularioNovo.blocos.push(id)
  }
}

const mutacaoPublicar = useMutation({
  mutationFn: (id: string) => comunicadosApi.publicarComunicado(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['comunicados-lista'] })
  },
})

const mutacaoCriar = useMutation({
  mutationFn: () =>
    comunicadosApi.criarComunicado({
      titulo: formularioNovo.titulo,
      conteudo: formularioNovo.conteudo,
      tipo_publico: formularioNovo.tipo_publico,
      blocos: formularioNovo.tipo_publico === 'BLOCOS' ? formularioNovo.blocos : [],
      publicar_agora: formularioNovo.publicar_agora,
    }),
  onSuccess: () => {
    modalNovoAberto.value = false
    formularioNovo.titulo = ''
    formularioNovo.conteudo = ''
    formularioNovo.tipo_publico = 'TODOS'
    formularioNovo.blocos = []
    formularioNovo.publicar_agora = true
    erroNovo.value = ''
    queryClient.invalidateQueries({ queryKey: ['comunicados-lista'] })
  },
  onError: (err: unknown) => {
    const responseData = (err as { response?: { data?: { message?: string; mensagem?: string } } })
      ?.response?.data
    erroNovo.value = responseData?.mensagem || responseData?.message || 'Erro ao salvar comunicado.'
  },
})

function formatarData(dataStr?: string | null): string {
  if (!dataStr) return 'Não publicado'
  return new Date(dataStr).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      titulo="Comunicados e Avisos"
      descricao="Publicação de avisos para todos os condôminos ou blocos específicos com confirmação de leitura."
      modulo="Comunicados"
    >
      <template #acoes>
        <Button v-if="podeCadastrar" class="gap-2" @click="modalNovoAberto = true">
          <Plus class="h-4 w-4" />
          Novo Comunicado
        </Button>
      </template>
    </PageHeader>

    <!-- Filtros -->
    <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-4 shadow-sm">
      <div class="relative min-w-[260px] flex-1">
        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input v-model="busca" placeholder="Buscar por título ou conteúdo..." class="pl-9" />
      </div>

      <div class="w-48">
        <select
          v-model="situacaoFiltro"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option value="TODOS">Todas situações</option>
          <option value="PUBLICADO">Publicados</option>
          <option value="RASCUNHO">Rascunhos</option>
          <option value="CANCELADO">Cancelados</option>
        </select>
      </div>
    </div>

    <!-- Lista de Comunicados -->
    <div v-if="isPending" class="flex justify-center p-12">
      <LoaderCircle class="h-8 w-8 animate-spin text-primary" />
    </div>

    <div
      v-else-if="comunicados.length === 0"
      class="flex flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center"
    >
      <Megaphone class="h-12 w-12 text-muted-foreground/50 mb-3" />
      <h3 class="font-semibold text-lg">Nenhum comunicado encontrado</h3>
      <p class="text-sm text-muted-foreground max-w-sm mt-1">
        Nenhum aviso corresponde aos filtros informados.
      </p>
      <Button v-if="podeCadastrar" class="mt-4 gap-2" @click="modalNovoAberto = true">
        <Plus class="h-4 w-4" />
        Cadastrar Primeiro Comunicado
      </Button>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div
        v-for="c in comunicados"
        :key="c.id_publico"
        class="group flex flex-col justify-between rounded-xl border bg-card p-6 shadow-sm transition-all hover:border-primary/40 hover:shadow-md md:flex-row md:items-center"
      >
        <div class="space-y-2 flex-1 pr-4">
          <div class="flex flex-wrap items-center gap-2">
            <Badge v-if="c.situacao === 'PUBLICADO'" class="bg-emerald-600 gap-1">
              <CheckCircle class="h-3 w-3" />
              Publicado
            </Badge>
            <Badge
              v-else-if="c.situacao === 'RASCUNHO'"
              variant="outline"
              class="text-amber-600 border-amber-300 bg-amber-50"
            >
              Rascunho
            </Badge>
            <Badge v-else variant="destructive">Cancelado</Badge>

            <Badge variant="secondary" class="gap-1">
              <Users v-if="c.tipo_publico === 'TODOS'" class="h-3 w-3" />
              <Building2 v-else class="h-3 w-3" />
              {{ c.tipo_publico === 'TODOS' ? 'Todos os condôminos' : 'Blocos selecionados' }}
            </Badge>

            <span class="text-xs text-muted-foreground">
              {{ formatarData(c.publicado_em) }}
            </span>
          </div>

          <h3
            class="font-semibold text-lg text-foreground group-hover:text-primary transition-colors"
          >
            {{ c.titulo }}
          </h3>

          <p class="text-sm text-muted-foreground line-clamp-2 max-w-3xl">
            {{ c.conteudo }}
          </p>

          <div
            v-if="c.situacao === 'PUBLICADO'"
            class="flex items-center gap-4 pt-1 text-xs text-muted-foreground"
          >
            <span class="flex items-center gap-1 font-medium text-foreground">
              <Eye class="h-3.5 w-3.5 text-primary" />
              {{ c.lidos_count ?? 0 }} de {{ c.destinatarios_count ?? 0 }} leituras confirmadas
            </span>
          </div>
        </div>

        <div class="mt-4 flex items-center gap-2 md:mt-0">
          <Button
            v-if="c.situacao === 'RASCUNHO' && podePublicar"
            variant="outline"
            size="sm"
            class="gap-1 border-primary text-primary hover:bg-primary/5"
            :disabled="mutacaoPublicar.isPending.value"
            @click="mutacaoPublicar.mutate(c.id_publico)"
          >
            <Send class="h-3.5 w-3.5" />
            Publicar
          </Button>

          <Button
            variant="outline"
            size="sm"
            @click="router.push({ name: 'admin-comunicado-detalhe', params: { id: c.id_publico } })"
          >
            Visualizar
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal de Novo Comunicado -->
    <Dialog v-model:open="modalNovoAberto">
      <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Novo Comunicado Oficial</DialogTitle>
          <DialogDescription>
            Crie e envie avisos com confirmação de leitura para os condôminos.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-4 pt-2" @submit.prevent="mutacaoCriar.mutate()">
          <div
            v-if="erroNovo"
            class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive font-medium"
          >
            {{ erroNovo }}
          </div>

          <div class="space-y-1.5">
            <Label for="modal-titulo" class="text-sm font-semibold">Título do Comunicado *</Label>
            <Input
              id="modal-titulo"
              v-model="formularioNovo.titulo"
              required
              placeholder="Ex: Assembleia Geral Extraordinária / Manutenção nos elevadores"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="modal-conteudo" class="text-sm font-semibold">Conteúdo da Mensagem *</Label>
            <textarea
              id="modal-conteudo"
              v-model="formularioNovo.conteudo"
              required
              rows="6"
              placeholder="Escreva aqui todas as informações, horários, instruções e regras pertinentes..."
              class="w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            ></textarea>
          </div>

          <!-- Destinatários -->
          <div class="space-y-2 pt-2 border-t">
            <Label class="text-sm font-semibold">Destinatários / Público-alvo *</Label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label
                :class="[
                  'flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all',
                  formularioNovo.tipo_publico === 'TODOS'
                    ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary'
                    : 'border-border hover:bg-muted/40',
                ]"
              >
                <input
                  v-model="formularioNovo.tipo_publico"
                  type="radio"
                  name="modal_tipo_publico"
                  value="TODOS"
                  class="sr-only"
                />
                <Users class="h-4 w-4" />
                <div>
                  <span class="font-semibold block text-sm">Todos os Condôminos</span>
                  <span class="text-xs text-muted-foreground">Todos os blocos e moradores</span>
                </div>
              </label>

              <label
                :class="[
                  'flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition-all',
                  formularioNovo.tipo_publico === 'BLOCOS'
                    ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary'
                    : 'border-border hover:bg-muted/40',
                ]"
              >
                <input
                  v-model="formularioNovo.tipo_publico"
                  type="radio"
                  name="modal_tipo_publico"
                  value="BLOCOS"
                  class="sr-only"
                />
                <Building2 class="h-4 w-4" />
                <div>
                  <span class="font-semibold block text-sm">Blocos Específicos</span>
                  <span class="text-xs text-muted-foreground">Somente torres selecionadas</span>
                </div>
              </label>
            </div>

            <!-- Seleção de Blocos quando BLOCOS -->
            <div
              v-if="formularioNovo.tipo_publico === 'BLOCOS'"
              class="mt-2 p-3 rounded-xl border bg-muted/20 space-y-2"
            >
              <span class="text-xs font-semibold uppercase text-muted-foreground block">
                Selecione os blocos:
              </span>
              <div class="grid grid-cols-2 gap-2">
                <label
                  v-for="b in blocosDisponiveis"
                  :key="b.id_publico"
                  class="flex items-center gap-2 p-2 rounded-lg border bg-card hover:bg-muted/50 cursor-pointer text-sm"
                >
                  <input
                    type="checkbox"
                    :checked="formularioNovo.blocos.includes(b.id_publico)"
                    class="rounded border-input text-primary focus:ring-primary"
                    @change="alternarBloco(b.id_publico)"
                  />
                  <span class="font-medium">{{ b.nome }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="formularioNovo.publicar_agora"
                type="checkbox"
                class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
              />
              <div>
                <span class="text-sm font-semibold">Publicar e notificar imediatamente</span>
                <span class="text-xs text-muted-foreground block">
                  Se desmarcado, ficará salvo como rascunho para envio posterior.
                </span>
              </div>
            </label>
          </div>

          <DialogFooter class="pt-3">
            <Button type="button" variant="outline" @click="modalNovoAberto = false">
              Cancelar
            </Button>
            <Button type="submit" :disabled="mutacaoCriar.isPending.value" class="gap-2">
              <LoaderCircle v-if="mutacaoCriar.isPending.value" class="h-4 w-4 animate-spin" />
              <Send v-else class="h-4 w-4" />
              {{ formularioNovo.publicar_agora ? 'Publicar Comunicado' : 'Salvar como Rascunho' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
