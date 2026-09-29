<script setup lang="ts">
import EntityDetailPage from '@/components/app/crud/EntityDetailPage.vue'
import * as usuariosApi from '@/modules/acesso/api/usuarios.api'
import type { Usuario } from '@/modules/acesso/types/usuario.types'

const campos = [
  { rotulo: 'Pessoa', render: (u: Usuario) => u.pessoa?.nome_completo ?? '—' },
  { rotulo: 'E-mail', render: (u: Usuario) => u.email },
  {
    rotulo: 'Bloqueio',
    render: (u: Usuario) => (u.bloqueado_em ? `Bloqueado em ${u.bloqueado_em}` : 'Não bloqueado'),
  },
  {
    rotulo: 'Último acesso',
    render: (u: Usuario) =>
      u.ultimo_acesso_em ? new Date(u.ultimo_acesso_em).toLocaleString('pt-BR') : '—',
  },
]
</script>

<template>
  <EntityDetailPage
    titulo="Detalhe do usuário"
    descricao="Informações completas e histórico de alterações do usuário."
    modulo="Acesso"
    query-key="usuario-detalhe"
    :campos="campos"
    :buscar="usuariosApi.mostrarUsuario"
    :buscar-historico="usuariosApi.historicoUsuario"
    status-campo="ativo"
  />
</template>
