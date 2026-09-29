<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'

import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as usuariosApi from '@/modules/acesso/api/usuarios.api'
import type { Usuario } from '@/modules/acesso/types/usuario.types'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const { data: pessoas } = useQuery({
  queryKey: ['pessoas-selecao'],
  queryFn: pessoasApi.listarPessoasParaSelecao,
})

const colunas: ColunaTabela<Usuario>[] = [
  { chave: 'pessoa', rotulo: 'Nome', render: (u) => u.pessoa?.nome_completo ?? '—' },
  { chave: 'email', rotulo: 'E-mail' },
  {
    chave: 'bloqueado_em',
    rotulo: 'Bloqueio',
    render: (u) => (u.bloqueado_em ? 'Bloqueado' : '—'),
  },
]

const campos = computed<CampoFormulario[]>(() => [
  {
    chave: 'pessoa_id',
    rotulo: 'Pessoa',
    tipo: 'selecao',
    obrigatorio: true,
    somenteCriacao: true,
    opcoes: (pessoas.value ?? []).map((p) => ({ valor: p.id_publico, rotulo: p.nome_completo })),
  },
  { chave: 'email', rotulo: 'E-mail', tipo: 'texto', obrigatorio: true },
  {
    chave: 'senha',
    rotulo: 'Senha',
    tipo: 'senha',
    obrigatorio: true,
    ajuda: 'Deixe em branco ao editar para manter a senha atual.',
  },
])
</script>

<template>
  <EntityListPage
    titulo="Usuários"
    descricao="Contas de acesso ao sistema, vinculadas a uma pessoa cadastrada."
    modulo="Acesso"
    query-key="usuarios"
    permissao-base="acesso.usuarios"
    :colunas="colunas"
    :campos="campos"
    :listar="usuariosApi.listarUsuarios"
    :criar="usuariosApi.criarUsuario"
    :atualizar="usuariosApi.atualizarUsuario"
    :inativar="usuariosApi.inativarUsuario"
    :reativar="usuariosApi.reativarUsuario"
    :buscar-historico="usuariosApi.historicoUsuario"
  />
</template>
