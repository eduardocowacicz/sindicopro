<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as blocosApi from '@/modules/cadastros/api/blocos.api'
import type { Bloco } from '@/modules/cadastros/types/bloco.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<Bloco>[] = [
  { chave: 'codigo', rotulo: 'Código' },
  { chave: 'nome', rotulo: 'Nome' },
  {
    chave: 'quantidade_andares',
    rotulo: 'Andares',
    render: (b) => b.quantidade_andares?.toString() ?? '—',
  },
]

const campos: CampoFormulario[] = [
  {
    chave: 'codigo',
    rotulo: 'Código',
    tipo: 'texto',
    somenteEdicao: true,
    desabilitado: true,
    ajuda: 'Gerado automaticamente pelo sistema.',
  },
  { chave: 'nome', rotulo: 'Nome', tipo: 'texto', obrigatorio: true },
  { chave: 'quantidade_andares', rotulo: 'Quantidade de andares', tipo: 'numero' },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
]
</script>

<template>
  <EntityListPage
    titulo="Blocos"
    descricao="Cadastro dos blocos e prédios do condomínio."
    modulo="Cadastros"
    query-key="blocos"
    permissao-base="cadastros.blocos"
    :colunas="colunas"
    :campos="campos"
    :listar="blocosApi.listarBlocos"
    :criar="blocosApi.criarBloco"
    :atualizar="blocosApi.atualizarBloco"
    :inativar="blocosApi.inativarBloco"
    :reativar="blocosApi.reativarBloco"
    :buscar-historico="blocosApi.historicoBloco"
  />
</template>
