<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  Building2,
  Check,
  CirclePlus,
  Copy,
  Eye,
  EyeOff,
  History,
  Home,
  KeyRound,
  LoaderCircle,
  Lock,
  Pencil,
  Power,
  PowerOff,
  Search,
  Shield,
  ShieldCheck,
  Unlock,
  Users,
} from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'

import PageHeader from '@/components/app/PageHeader.vue'
import HistoricoSheet from '@/components/app/crud/HistoricoSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { errosDeCampos, mensagemDeErro } from '@/lib/http-error'
import * as perfisApi from '@/modules/acesso/api/perfis.api'
import * as usuariosApi from '@/modules/acesso/api/usuarios.api'
import type { Perfil } from '@/modules/acesso/types/perfil.types'
import type { Usuario } from '@/modules/acesso/types/usuario.types'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import type { Pessoa } from '@/modules/cadastros/types/pessoa.types'

const autenticacao = useAutenticacaoStore()
const queryClient = useQueryClient()

const podeCadastrar = computed(() => autenticacao.temPermissao('acesso.usuarios.cadastrar'))
const podeEditar = computed(() => autenticacao.temPermissao('acesso.usuarios.editar'))
const podeInativar = computed(() => autenticacao.temPermissao('acesso.usuarios.inativar'))
const podeBloquear = computed(() => autenticacao.temPermissao('acesso.usuarios.bloquear'))
const podeRedefinirSenha = computed(() =>
  autenticacao.temPermissao('acesso.usuarios.redefinir_senha'),
)

// Filtros
const busca = ref('')
const filtroPerfil = ref('TODOS')
const filtroSituacao = ref('TODOS')
const pagina = ref(1)

// Consultas
const {
  data: respostaUsuarios,
  isPending: carregandoUsuarios,
  isError: erroCarregandoUsuarios,
} = useQuery({
  queryKey: ['usuarios', busca, pagina],
  queryFn: () =>
    usuariosApi.listarUsuarios({ busca: busca.value || undefined, pagina: pagina.value }),
})

const { data: listaPessoas } = useQuery({
  queryKey: ['pessoas-selecao'],
  queryFn: () => pessoasApi.listarPessoasParaSelecao(),
})

const { data: listaPerfis } = useQuery({
  queryKey: ['perfis-selecao'],
  queryFn: () => perfisApi.listarPerfisParaSelecao(),
})

watch(busca, () => {
  pagina.value = 1
})

const todosUsuarios = computed<Usuario[]>(() => respostaUsuarios.value?.dados ?? [])
const meta = computed(() => respostaUsuarios.value?.meta)

// Filtragem local complementar para Perfil e Situação
const usuariosFiltrados = computed(() => {
  return todosUsuarios.value.filter((usuario) => {
    if (filtroPerfil.value !== 'TODOS') {
      const temPerfil = (usuario.perfis_vigentes ?? []).some((p) => p.codigo === filtroPerfil.value)
      if (!temPerfil) return false
    }

    if (filtroSituacao.value === 'ATIVOS') {
      if (!usuario.ativo || usuario.bloqueado_em) return false
    } else if (filtroSituacao.value === 'BLOQUEADOS') {
      if (!usuario.bloqueado_em) return false
    } else if (filtroSituacao.value === 'INATIVOS') {
      if (usuario.ativo) return false
    }

    return true
  })
})

// KPIs
const totalUsuarios = computed(() => meta.value?.total ?? todosUsuarios.value.length)
const totalMoradoresComAcesso = computed(
  () =>
    todosUsuarios.value.filter((u) => (u.perfis_vigentes ?? []).some((p) => p.codigo === 'MORADOR'))
      .length,
)
const totalGestores = computed(
  () =>
    todosUsuarios.value.filter((u) =>
      (u.perfis_vigentes ?? []).some((p) =>
        ['SINDICO', 'SUBSINDICO', 'CONSELHO'].includes(p.codigo),
      ),
    ).length,
)
const totalAtivos = computed(
  () => todosUsuarios.value.filter((u) => u.ativo && !u.bloqueado_em).length,
)

// Modal de Formulário (Criação / Edição)
const modalAberto = ref(false)
const usuarioEmEdicao = ref<Usuario | null>(null)
const pessoaSelecionadaId = ref('')
const emailForm = ref('')
const senhaForm = ref('')
const mostrarSenha = ref(false)
const perfisSelecionadosIds = ref<string[]>([])
const erroFormulario = ref('')
const errosCampos = reactive<Record<string, string>>({})

const pessoaSelecionada = computed<Pessoa | undefined>(() => {
  return (listaPessoas.value ?? []).find((p) => p.id_publico === pessoaSelecionadaId.value)
})

const ehMorador = computed(() => {
  if (usuarioEmEdicao.value) {
    const p = usuarioEmEdicao.value.pessoa
    return p?.tipo === 'MORADOR' || (p?.vinculos && p.vinculos.length > 0)
  }
  return pessoaSelecionada.value?.tipo === 'MORADOR' || pessoaSelecionada.value?.tipo === undefined
})

// Auto preenchimento ao selecionar Pessoa na criação
watch(pessoaSelecionada, (novaPessoa) => {
  if (!usuarioEmEdicao.value && novaPessoa) {
    if (novaPessoa.email && !emailForm.value) {
      emailForm.value = novaPessoa.email
    }
    // Garante que o perfil MORADOR fica marcado para moradores
    const perfilMorador = (listaPerfis.value ?? []).find((p) => p.codigo === 'MORADOR')
    if (perfilMorador && ehMorador.value) {
      if (!perfisSelecionadosIds.value.includes(perfilMorador.id_publico)) {
        perfisSelecionadosIds.value.push(perfilMorador.id_publico)
      }
    }
  }
})

function abrirCriacao(): void {
  usuarioEmEdicao.value = null
  pessoaSelecionadaId.value = ''
  emailForm.value = ''
  senhaForm.value = ''
  mostrarSenha.value = false
  erroFormulario.value = ''
  Object.keys(errosCampos).forEach((k) => delete errosCampos[k])

  // Por padrão, inclui o perfil MORADOR se disponível
  const perfilMorador = (listaPerfis.value ?? []).find((p) => p.codigo === 'MORADOR')
  perfisSelecionadosIds.value = perfilMorador ? [perfilMorador.id_publico] : []

  modalAberto.value = true
}

function abrirEdicao(usuario: Usuario): void {
  usuarioEmEdicao.value = usuario
  pessoaSelecionadaId.value = usuario.pessoa?.id_publico ?? ''
  emailForm.value = usuario.email
  senhaForm.value = ''
  mostrarSenha.value = false
  erroFormulario.value = ''
  Object.keys(errosCampos).forEach((k) => delete errosCampos[k])

  // Carrega IDs dos perfis vigentes do usuário
  const idsVigentes = (usuario.perfis_vigentes ?? []).map((p) => p.id_publico)
  const perfilMorador = (listaPerfis.value ?? []).find((p) => p.codigo === 'MORADOR')

  // Se a pessoa for morador, assegura que o perfil MORADOR está marcado
  if (ehMorador.value && perfilMorador && !idsVigentes.includes(perfilMorador.id_publico)) {
    idsVigentes.push(perfilMorador.id_publico)
  }

  perfisSelecionadosIds.value = idsVigentes
  modalAberto.value = true
}

function alternarPerfil(perfil: Perfil): void {
  // Se for morador, o perfil MORADOR não pode ser desmarcado
  if (perfil.codigo === 'MORADOR' && ehMorador.value) {
    return
  }

  const index = perfisSelecionadosIds.value.indexOf(perfil.id_publico)
  if (index >= 0) {
    perfisSelecionadosIds.value.splice(index, 1)
  } else {
    perfisSelecionadosIds.value.push(perfil.id_publico)
  }
}

const mutacaoSalvar = useMutation({
  mutationFn: async () => {
    erroFormulario.value = ''
    Object.keys(errosCampos).forEach((k) => delete errosCampos[k])

    const payload: Record<string, unknown> = {
      email: emailForm.value,
      perfis: perfisSelecionadosIds.value,
    }

    if (senhaForm.value) {
      payload.senha = senhaForm.value
    }

    if (usuarioEmEdicao.value) {
      return usuariosApi.atualizarUsuario(usuarioEmEdicao.value.id_publico, payload)
    }

    payload.pessoa_id = pessoaSelecionadaId.value
    return usuariosApi.criarUsuario(payload)
  },
  onSuccess: async () => {
    modalAberto.value = false
    await queryClient.invalidateQueries({ queryKey: ['usuarios'] })
  },
  onError: (erro: unknown) => {
    erroFormulario.value = mensagemDeErro(erro)
    Object.assign(errosCampos, errosDeCampos(erro))
  },
})

// Mutações de Situação, Bloqueio e Senha
const mutacaoSituacao = useMutation({
  mutationFn: async (usuario: Usuario) => {
    if (usuario.ativo) {
      return usuariosApi.inativarUsuario(usuario.id_publico)
    }
    return usuariosApi.reativarUsuario(usuario.id_publico)
  },
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['usuarios'] })
  },
})

const mutacaoBloqueio = useMutation({
  mutationFn: async (usuario: Usuario) => {
    if (usuario.bloqueado_em) {
      return usuariosApi.desbloquearUsuario(usuario.id_publico)
    }
    return usuariosApi.bloquearUsuario(usuario.id_publico, 'Bloqueio administrativo manual')
  },
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['usuarios'] })
  },
})

// Modal de Redefinição de Senha
const modalSenhaAberto = ref(false)
const usuarioSenhaRedefinida = ref<Usuario | null>(null)
const novaSenhaTemporaria = ref('')
const senhaCopiada = ref(false)

const mutacaoRedefinirSenha = useMutation({
  mutationFn: async (usuario: Usuario) => {
    usuarioSenhaRedefinida.value = usuario
    const senha = await usuariosApi.redefinirSenhaUsuario(usuario.id_publico)
    novaSenhaTemporaria.value = senha
    senhaCopiada.value = false
    modalSenhaAberto.value = true
  },
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['usuarios'] })
  },
})

async function copiarSenha(): Promise<void> {
  if (!novaSenhaTemporaria.value) return
  await navigator.clipboard.writeText(novaSenhaTemporaria.value)
  senhaCopiada.value = true
  setTimeout(() => {
    senhaCopiada.value = false
  }, 2500)
}

// Histórico
const historicoAberto = ref(false)
const usuarioHistorico = ref<Usuario | null>(null)

function abrirHistorico(usuario: Usuario): void {
  usuarioHistorico.value = usuario
  historicoAberto.value = true
}

// Funções auxiliares de renderização
function iconePerfil(codigo: string) {
  switch (codigo) {
    case 'SINDICO':
      return ShieldCheck
    case 'SUBSINDICO':
      return Shield
    case 'CONSELHO':
      return Users
    case 'MORADOR':
      return Home
    case 'PROPRIETARIO':
      return Building2
    default:
      return Shield
  }
}

function variantePerfil(codigo: string): 'default' | 'secondary' | 'outline' | 'destructive' {
  switch (codigo) {
    case 'SINDICO':
      return 'default'
    case 'SUBSINDICO':
      return 'secondary'
    case 'CONSELHO':
      return 'outline'
    case 'MORADOR':
      return 'outline'
    default:
      return 'secondary'
  }
}

function formatarUnidades(usuario: Usuario): string {
  const vinculos = usuario.pessoa?.vinculos
  if (!vinculos || vinculos.length === 0) return ''
  return vinculos
    .map((v) => `${v.unidade?.bloco?.nome ?? ''} ${v.unidade?.codigo ?? ''}`.trim())
    .filter(Boolean)
    .join(', ')
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader
      title="Usuários"
      description="Contas de acesso ao sistema com suporte a múltiplos perfis e permissões cumulativas."
      eyebrow="Acesso"
    >
      <template v-if="podeCadastrar" #actions>
        <Button class="gap-2" @click="abrirCriacao">
          <CirclePlus class="size-4" aria-hidden="true" />
          Novo usuário
        </Button>
      </template>
    </PageHeader>

    <!-- Indicadores / KPIs -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardDescription>Total de Usuários</CardDescription>
          <Users class="size-4 text-muted-foreground" aria-hidden="true" />
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-bold">{{ totalUsuarios }}</p>
          <p class="text-xs text-muted-foreground">Cadastrados no sistema</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardDescription>Moradores com Acesso</CardDescription>
          <Home class="size-4 text-primary" aria-hidden="true" />
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-bold text-primary">{{ totalMoradoresComAcesso }}</p>
          <p class="text-xs text-muted-foreground">Acesso à Área do Morador</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardDescription>Administração e Síndicos</CardDescription>
          <ShieldCheck class="size-4 text-amber-500" aria-hidden="true" />
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
            {{ totalGestores }}
          </p>
          <p class="text-xs text-muted-foreground">Síndico, Subsíndico ou Conselho</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardDescription>Usuários Ativos</CardDescription>
          <Check class="size-4 text-emerald-500" aria-hidden="true" />
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ totalAtivos }}
          </p>
          <p class="text-xs text-muted-foreground">Sem bloqueio ou restrição</p>
        </CardContent>
      </Card>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full max-w-sm">
        <Search class="absolute left-3 top-2.5 size-4 text-muted-foreground" aria-hidden="true" />
        <Input v-model="busca" placeholder="Buscar por nome ou e-mail..." class="pl-9" />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Filtro por Perfil -->
        <select
          v-model="filtroPerfil"
          class="h-9 rounded-md border border-input bg-transparent px-3 text-xs shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
          <option value="TODOS">Todos os perfis</option>
          <option value="MORADOR">Morador</option>
          <option value="SINDICO">Síndico</option>
          <option value="SUBSINDICO">Subsíndico</option>
          <option value="CONSELHO">Conselho</option>
          <option value="PROPRIETARIO">Proprietário</option>
        </select>

        <!-- Filtro por Situação -->
        <select
          v-model="filtroSituacao"
          class="h-9 rounded-md border border-input bg-transparent px-3 text-xs shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
          <option value="TODOS">Todas as situações</option>
          <option value="ATIVOS">Apenas ativos</option>
          <option value="BLOQUEADOS">Apenas bloqueados</option>
          <option value="INATIVOS">Apenas inativos</option>
        </select>
      </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="overflow-x-auto rounded-md border bg-card">
      <table class="w-full text-sm">
        <thead class="border-b bg-muted/50 text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th class="px-4 py-3 font-semibold">Usuário / Pessoa</th>
            <th class="px-4 py-3 font-semibold">Tipo</th>
            <th class="px-4 py-3 font-semibold">Perfis Ativos (Cumulativos)</th>
            <th class="px-4 py-3 font-semibold">Situação</th>
            <th class="px-4 py-3 font-semibold">Último Acesso</th>
            <th class="px-4 py-3 text-right font-semibold">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-if="carregandoUsuarios">
            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
              <LoaderCircle class="mx-auto size-6 animate-spin text-primary" aria-hidden="true" />
              <p class="mt-2 text-xs">Carregando usuários...</p>
            </td>
          </tr>
          <tr v-else-if="erroCarregandoUsuarios">
            <td colspan="6" class="px-4 py-8 text-center text-destructive">
              Não foi possível carregar a lista de usuários.
            </td>
          </tr>
          <tr v-else-if="usuariosFiltrados.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
              <Users class="mx-auto size-8 opacity-40" aria-hidden="true" />
              <p class="mt-2 font-medium">Nenhum usuário encontrado.</p>
              <p class="text-xs">Tente ajustar seus termos de busca ou filtros.</p>
            </td>
          </tr>
          <tr
            v-for="usuario in usuariosFiltrados"
            :key="usuario.id_publico"
            class="transition-colors hover:bg-muted/40"
          >
            <!-- Usuário / Pessoa -->
            <td class="px-4 py-3">
              <div class="flex flex-col">
                <span class="font-medium text-foreground">
                  {{ usuario.pessoa?.nome_completo ?? '—' }}
                </span>
                <span class="text-xs text-muted-foreground">{{ usuario.email }}</span>
                <div
                  v-if="formatarUnidades(usuario)"
                  class="mt-1 flex items-center gap-1 text-[11px] text-primary"
                >
                  <Home class="size-3" aria-hidden="true" />
                  <span>{{ formatarUnidades(usuario) }}</span>
                </div>
              </div>
            </td>

            <!-- Tipo de Pessoa -->
            <td class="px-4 py-3">
              <Badge
                variant="outline"
                :class="[
                  usuario.pessoa?.tipo === 'MORADOR'
                    ? 'border-blue-500/30 bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                    : usuario.pessoa?.tipo === 'PRESTADOR_SERVICO'
                      ? 'border-purple-500/30 bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300'
                      : 'border-amber-500/30 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
                ]"
              >
                {{
                  usuario.pessoa?.tipo === 'MORADOR'
                    ? 'Morador'
                    : usuario.pessoa?.tipo === 'PRESTADOR_SERVICO'
                      ? 'Prestador'
                      : usuario.pessoa?.tipo === 'FORNECEDOR'
                        ? 'Fornecedor'
                        : '—'
                }}
              </Badge>
            </td>

            <!-- Perfis Ativos (Cumulativos) -->
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-1.5">
                <Badge
                  v-for="perfil in usuario.perfis_vigentes ?? []"
                  :key="perfil.id_publico"
                  :variant="variantePerfil(perfil.codigo)"
                  class="gap-1 px-2 py-0.5 text-xs font-medium"
                  :class="[
                    perfil.codigo === 'SINDICO'
                      ? 'bg-amber-500 hover:bg-amber-600 text-white dark:bg-amber-600'
                      : perfil.codigo === 'MORADOR'
                        ? 'border-primary/40 bg-primary/10 text-primary'
                        : '',
                  ]"
                >
                  <component :is="iconePerfil(perfil.codigo)" class="size-3" aria-hidden="true" />
                  <span>{{ perfil.nome }}</span>
                </Badge>
                <span
                  v-if="!usuario.perfis_vigentes || usuario.perfis_vigentes.length === 0"
                  class="text-xs text-muted-foreground italic"
                >
                  Sem perfil ativo
                </span>
              </div>
            </td>

            <!-- Situação -->
            <td class="px-4 py-3">
              <Badge v-if="usuario.bloqueado_em" variant="destructive" class="gap-1">
                <Lock class="size-3" aria-hidden="true" />
                Bloqueado
              </Badge>
              <Badge
                v-else-if="usuario.ativo"
                variant="default"
                class="gap-1 bg-emerald-600 hover:bg-emerald-700 text-white"
              >
                <Check class="size-3" aria-hidden="true" />
                Ativo
              </Badge>
              <Badge v-else variant="secondary" class="gap-1 text-muted-foreground">
                <PowerOff class="size-3" aria-hidden="true" />
                Inativo
              </Badge>
            </td>

            <!-- Último Acesso -->
            <td class="px-4 py-3 text-xs text-muted-foreground">
              {{
                usuario.ultimo_acesso_em
                  ? new Date(usuario.ultimo_acesso_em).toLocaleString('pt-BR')
                  : 'Nunca acessou'
              }}
            </td>

            <!-- Ações -->
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <Button
                  v-if="podeEditar"
                  variant="ghost"
                  size="icon-sm"
                  title="Editar dados e perfis"
                  aria-label="Editar dados e perfis"
                  @click="abrirEdicao(usuario)"
                >
                  <Pencil class="size-4" aria-hidden="true" />
                </Button>

                <Button
                  v-if="podeRedefinirSenha"
                  variant="ghost"
                  size="icon-sm"
                  title="Redefinir senha de acesso"
                  aria-label="Redefinir senha de acesso"
                  :disabled="mutacaoRedefinirSenha.isPending.value"
                  @click="mutacaoRedefinirSenha.mutate(usuario)"
                >
                  <KeyRound class="size-4 text-amber-500" aria-hidden="true" />
                </Button>

                <Button
                  v-if="podeBloquear"
                  variant="ghost"
                  size="icon-sm"
                  :title="usuario.bloqueado_em ? 'Desbloquear acesso' : 'Bloquear acesso'"
                  :aria-label="usuario.bloqueado_em ? 'Desbloquear acesso' : 'Bloquear acesso'"
                  @click="mutacaoBloqueio.mutate(usuario)"
                >
                  <Unlock
                    v-if="usuario.bloqueado_em"
                    class="size-4 text-emerald-500"
                    aria-hidden="true"
                  />
                  <Lock v-else class="size-4 text-destructive" aria-hidden="true" />
                </Button>

                <Button
                  v-if="podeInativar"
                  variant="ghost"
                  size="icon-sm"
                  :title="usuario.ativo ? 'Inativar usuário' : 'Reativar usuário'"
                  :aria-label="usuario.ativo ? 'Inativar usuário' : 'Reativar usuário'"
                  @click="mutacaoSituacao.mutate(usuario)"
                >
                  <PowerOff
                    v-if="usuario.ativo"
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Power v-else class="size-4 text-emerald-500" aria-hidden="true" />
                </Button>

                <Button
                  variant="ghost"
                  size="icon-sm"
                  title="Histórico de alterações"
                  aria-label="Histórico de alterações"
                  @click="abrirHistorico(usuario)"
                >
                  <History class="size-4" aria-hidden="true" />
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <div v-if="meta && meta.ultima_pagina > 1" class="flex items-center justify-between text-sm">
      <span class="text-xs text-muted-foreground">
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

    <!-- Modal de Cadastro / Edição -->
    <Sheet v-model:open="modalAberto">
      <SheetContent
        side="center"
        class="max-h-[92vh] w-[calc(100vw-2rem)] max-w-[720px] overflow-y-auto rounded-[10px] p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-3">
          <SheetTitle class="text-base font-semibold text-foreground sm:text-lg">
            {{ usuarioEmEdicao ? 'Editar usuário' : 'Novo usuário' }}
          </SheetTitle>
          <p class="text-xs text-muted-foreground">
            Configure as credenciais e selecione os perfis de acesso. As permissões são cumulativas.
          </p>
        </SheetHeader>

        <form class="space-y-4 pt-4" @submit.prevent="mutacaoSalvar.mutate()">
          <!-- Pessoa Vinculada -->
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-foreground" for="pessoa_id">
              Pessoa vinculada <span class="text-destructive">*</span>
            </label>

            <!-- Modo Criação: Select -->
            <select
              v-if="!usuarioEmEdicao"
              id="pessoa_id"
              v-model="pessoaSelecionadaId"
              class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring"
              required
            >
              <option value="" disabled>Selecione a pessoa cadastrada...</option>
              <option v-for="p in listaPessoas ?? []" :key="p.id_publico" :value="p.id_publico">
                {{ p.nome_completo }} ({{
                  p.tipo === 'MORADOR'
                    ? 'Morador'
                    : p.tipo === 'PRESTADOR_SERVICO'
                      ? 'Prestador'
                      : 'Fornecedor'
                }})
              </option>
            </select>

            <!-- Modo Edição: Input fixo -->
            <Input
              v-else
              :model-value="`${usuarioEmEdicao.pessoa?.nome_completo ?? '—'} (${usuarioEmEdicao.pessoa?.tipo ?? 'MORADOR'})`"
              disabled
              class="bg-muted text-muted-foreground"
            />

            <!-- Aviso sobre Morador -->
            <div
              v-if="ehMorador"
              class="flex items-start gap-2 rounded-md border border-blue-500/20 bg-blue-50/50 p-2.5 text-xs text-blue-700 dark:bg-blue-950/20 dark:text-blue-300"
            >
              <Home
                class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-400"
                aria-hidden="true"
              />
              <div>
                <span class="font-semibold">Pessoa identificada como Morador:</span>
                O perfil de <strong>Morador</strong> (Área do Morador) é concedido automaticamente
                para garantir o acesso à sua unidade, boletos e reservas.
              </div>
            </div>
            <p v-if="errosCampos.pessoa_id" class="text-xs text-destructive">
              {{ errosCampos.pessoa_id }}
            </p>
          </div>

          <!-- E-mail e Senha -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- E-mail -->
            <div class="space-y-1.5">
              <label class="block text-xs font-semibold text-foreground" for="email">
                E-mail de acesso <span class="text-destructive">*</span>
              </label>
              <Input
                id="email"
                v-model="emailForm"
                type="email"
                required
                placeholder="exemplo@condominio.com"
                :aria-invalid="Boolean(errosCampos.email)"
              />
              <p v-if="errosCampos.email" class="text-xs text-destructive">
                {{ errosCampos.email }}
              </p>
            </div>

            <!-- Senha -->
            <div class="space-y-1.5">
              <label class="block text-xs font-semibold text-foreground" for="senha">
                Senha de acesso
                <span v-if="!usuarioEmEdicao" class="text-destructive">*</span>
              </label>
              <div class="relative">
                <Input
                  id="senha"
                  v-model="senhaForm"
                  :type="mostrarSenha ? 'text' : 'password'"
                  :required="!usuarioEmEdicao"
                  placeholder="Mínimo 8 caracteres"
                  class="pr-10"
                  :aria-invalid="Boolean(errosCampos.senha)"
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 grid w-9 place-items-center text-muted-foreground hover:text-foreground"
                  :aria-label="mostrarSenha ? 'Ocultar senha' : 'Exibir senha'"
                  @click="mostrarSenha = !mostrarSenha"
                >
                  <EyeOff v-if="mostrarSenha" class="size-4" aria-hidden="true" />
                  <Eye v-else class="size-4" aria-hidden="true" />
                </button>
              </div>
              <p v-if="usuarioEmEdicao" class="text-[11px] text-muted-foreground">
                Deixe em branco para manter a senha atual.
              </p>
              <p v-if="errosCampos.senha" class="text-xs text-destructive">
                {{ errosCampos.senha }}
              </p>
            </div>
          </div>

          <!-- Perfis de Acesso (Permissões Cumulativas) -->
          <div class="space-y-2 pt-2 border-t border-border/60">
            <div class="flex flex-col gap-1">
              <label class="block text-xs font-semibold text-foreground">
                Perfis de acesso (Permissões Cumulativas)
              </label>
              <p class="text-xs text-muted-foreground">
                Selecione os perfis ativos deste usuário. Permissões são cumulativas: um morador
                pode receber permissão de <strong>Síndico</strong> durante seu mandato e,
                posteriormente, ter o perfil revogado voltando a ter apenas as permissões de
                morador.
              </p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 pt-1">
              <div
                v-for="perfil in listaPerfis ?? []"
                :key="perfil.id_publico"
                class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                :class="[
                  perfisSelecionadosIds.includes(perfil.id_publico)
                    ? 'border-primary/50 bg-primary/5 ring-1 ring-primary/20'
                    : 'border-border/60 hover:bg-muted/30',
                  perfil.codigo === 'MORADOR' && ehMorador ? 'cursor-not-allowed opacity-90' : '',
                ]"
                @click="alternarPerfil(perfil)"
              >
                <input
                  type="checkbox"
                  class="mt-1 size-4 rounded border-input text-primary focus:ring-primary"
                  :checked="perfisSelecionadosIds.includes(perfil.id_publico)"
                  :disabled="perfil.codigo === 'MORADOR' && ehMorador"
                  @click.stop="alternarPerfil(perfil)"
                />

                <div class="space-y-0.5 min-w-0 flex-1">
                  <div class="flex items-center gap-1.5">
                    <component
                      :is="iconePerfil(perfil.codigo)"
                      class="size-3.5 text-primary"
                      aria-hidden="true"
                    />
                    <span class="text-xs font-semibold text-foreground">
                      {{ perfil.nome }}
                    </span>
                    <Badge
                      v-if="perfil.codigo === 'MORADOR' && ehMorador"
                      variant="outline"
                      class="ml-auto text-[10px] py-0 px-1 border-blue-400 text-blue-600"
                    >
                      Base morador
                    </Badge>
                  </div>
                  <p class="text-[11px] text-muted-foreground leading-snug">
                    {{
                      perfil.codigo === 'MORADOR'
                        ? 'Acesso completo à Área do Morador (Minha Unidade, Boletos, Reservas, Comunicados).'
                        : perfil.codigo === 'SINDICO'
                          ? 'Acesso Administrativo Total (Condomínio, Blocos, Financeiro, Fechamento e Contas).'
                          : perfil.codigo === 'SUBSINDICO'
                            ? 'Acesso administrativo de apoio à gestão condominial.'
                            : perfil.codigo === 'CONSELHO'
                              ? 'Acesso a relatórios, balancetes e prestação de contas.'
                              : perfil.codigo === 'PROPRIETARIO'
                                ? 'Acesso aos recursos de proprietário de unidades.'
                                : (perfil.descricao ?? 'Perfil de acesso ao sistema.')
                    }}
                  </p>
                </div>
              </div>
            </div>
            <p v-if="errosCampos.perfis" class="text-xs text-destructive">
              {{ errosCampos.perfis }}
            </p>
          </div>

          <!-- Mensagem de Erro Geral -->
          <p
            v-if="erroFormulario"
            class="rounded-md bg-destructive/10 px-3 py-2 text-xs font-medium text-destructive"
            role="alert"
          >
            {{ erroFormulario }}
          </p>

          <!-- Rodapé do Modal -->
          <SheetFooter class="border-t border-border/60 pt-3">
            <Button
              type="button"
              variant="outline"
              :disabled="mutacaoSalvar.isPending.value"
              @click="modalAberto = false"
            >
              Cancelar
            </Button>
            <Button type="submit" :disabled="mutacaoSalvar.isPending.value">
              <LoaderCircle
                v-if="mutacaoSalvar.isPending.value"
                class="size-4 animate-spin mr-1.5"
                aria-hidden="true"
              />
              {{ usuarioEmEdicao ? 'Salvar alterações' : 'Criar usuário' }}
            </Button>
          </SheetFooter>
        </form>
      </SheetContent>
    </Sheet>

    <!-- Modal de Redefinição de Senha Concluída -->
    <Sheet v-model:open="modalSenhaAberto">
      <SheetContent
        side="center"
        class="w-[calc(100vw-2rem)] max-w-[480px] rounded-[10px] p-5 sm:p-6"
      >
        <SheetHeader class="border-b border-border/60 pb-3">
          <SheetTitle class="text-base font-semibold text-foreground">
            Senha temporária gerada
          </SheetTitle>
          <CardDescription>
            A senha de acesso de
            <strong>{{ usuarioSenhaRedefinida?.pessoa?.nome_completo }}</strong> foi redefinida.
          </CardDescription>
        </SheetHeader>

        <div class="space-y-4 pt-4">
          <p class="text-xs text-muted-foreground">
            Forneça a nova senha temporária para o usuário. Ele será orientado a alterá-la no
            próximo login.
          </p>

          <div
            class="flex items-center justify-between rounded-lg border bg-muted/60 p-3 font-mono text-base font-bold text-foreground"
          >
            <span>{{ novaSenhaTemporaria }}</span>
            <Button variant="outline" size="sm" class="gap-1.5 text-xs" @click="copiarSenha">
              <Check v-if="senhaCopiada" class="size-3.5 text-emerald-500" aria-hidden="true" />
              <Copy v-else class="size-3.5" aria-hidden="true" />
              {{ senhaCopiada ? 'Copiada!' : 'Copiar' }}
            </Button>
          </div>
        </div>

        <SheetFooter class="border-t border-border/60 pt-3 mt-4">
          <Button class="w-full" @click="modalSenhaAberto = false"> Concluído </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>

    <!-- Historico -->
    <HistoricoSheet
      v-model:aberto="historicoAberto"
      titulo="Usuário"
      :query-key="`usuario-${usuarioHistorico?.id_publico ?? 'item'}`"
      :buscar="
        usuarioHistorico ? () => usuariosApi.historicoUsuario(usuarioHistorico!.id_publico) : null
      "
    />
  </section>
</template>
