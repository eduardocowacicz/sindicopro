<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as categoriasApi from '@/modules/financeiro/api/categorias.api'
import type { CategoriaFinanceira } from '@/modules/financeiro/types/categoria.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<CategoriaFinanceira>[] = [
  { chave: 'codigo', rotulo: 'Código' },
  { chave: 'nome', rotulo: 'Nome' },
  {
    chave: 'direcao',
    rotulo: 'Direção',
    render: (c) => (c.direcao === 'ENTRADA' ? 'Entrada' : 'Saída'),
  },
]

const campos: CampoFormulario[] = [
  { chave: 'codigo', rotulo: 'Código', tipo: 'texto', obrigatorio: true },
  { chave: 'nome', rotulo: 'Nome', tipo: 'texto', obrigatorio: true },
  {
    chave: 'direcao',
    rotulo: 'Direção',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'ENTRADA', rotulo: 'Entrada' },
      { valor: 'SAIDA', rotulo: 'Saída' },
    ],
  },
]
</script>

<template>
  <EntityListPage
    titulo="Plano de contas / Categorias"
    descricao="Categorias que organizam as movimentações financeiras de entrada e saída."
    modulo="Financeiro"
    query-key="financeiro-categorias"
    permissao-base="financeiro.categorias"
    :com-busca="false"
    :colunas="colunas"
    :campos="campos"
    :listar="categoriasApi.listarCategorias"
    :criar="categoriasApi.criarCategoria"
    :atualizar="categoriasApi.atualizarCategoria"
    :inativar="categoriasApi.inativarCategoria"
    :reativar="categoriasApi.reativarCategoria"
    :buscar-historico="categoriasApi.historicoCategoria"
  />
</template>
