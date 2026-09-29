<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue'

import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import { Button } from '@/components/ui/button'
import * as perfisApi from '@/modules/acesso/api/perfis.api'
import type { Perfil } from '@/modules/acesso/types/perfil.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<Perfil>[] = [
  { chave: 'codigo', rotulo: 'Código' },
  { chave: 'nome', rotulo: 'Nome' },
  { chave: 'descricao', rotulo: 'Descrição', render: (p) => p.descricao ?? '—' },
]

const campos: CampoFormulario[] = [
  { chave: 'codigo', rotulo: 'Código', tipo: 'texto', obrigatorio: true, somenteCriacao: true },
  { chave: 'nome', rotulo: 'Nome', tipo: 'texto', obrigatorio: true },
  { chave: 'descricao', rotulo: 'Descrição', tipo: 'texto-longo' },
]
</script>

<template>
  <EntityListPage
    titulo="Perfis"
    descricao="Perfis de acesso que agrupam permissões atribuídas aos usuários."
    modulo="Acesso"
    query-key="perfis"
    permissao-base="acesso.perfis"
    :com-busca="false"
    :colunas="colunas"
    :campos="campos"
    :listar="perfisApi.listarPerfis"
    :criar="perfisApi.criarPerfil"
    :atualizar="perfisApi.atualizarPerfil"
    :inativar="perfisApi.inativarPerfil"
    :reativar="perfisApi.reativarPerfil"
    :buscar-historico="perfisApi.historicoPerfil"
  >
    <template #acoes-extra="{ item }">
      <Button variant="ghost" size="icon-sm" title="Permissões" as-child>
        <RouterLink :to="{ name: 'admin-perfil-permissoes', params: { id: item.id_publico } }">
          <ShieldCheck class="size-4" aria-hidden="true" />
        </RouterLink>
      </Button>
    </template>
  </EntityListPage>
</template>
