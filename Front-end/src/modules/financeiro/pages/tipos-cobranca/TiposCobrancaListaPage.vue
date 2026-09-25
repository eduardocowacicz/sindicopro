<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as tiposCobrancaApi from '@/modules/financeiro/api/tipos-cobranca.api'
import type { TipoCobranca } from '@/modules/financeiro/types/tipo-cobranca.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<TipoCobranca>[] = [
  { chave: 'codigo', rotulo: 'Código' },
  { chave: 'nome', rotulo: 'Nome' },
  {
    chave: 'papel_pagador',
    rotulo: 'Pagador',
    render: (t) => (t.papel_pagador === 'MORADOR' ? 'Morador' : 'Proprietário'),
  },
  { chave: 'dia_vencimento_padrao', rotulo: 'Dia de vencimento' },
]

const campos: CampoFormulario[] = [
  { chave: 'codigo', rotulo: 'Código', tipo: 'texto', obrigatorio: true },
  { chave: 'nome', rotulo: 'Nome', tipo: 'texto', obrigatorio: true },
  {
    chave: 'papel_pagador',
    rotulo: 'Quem paga',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'MORADOR', rotulo: 'Morador' },
      { valor: 'PROPRIETARIO', rotulo: 'Proprietário' },
    ],
  },
  {
    chave: 'dia_vencimento_padrao',
    rotulo: 'Dia de vencimento padrão',
    tipo: 'numero',
    obrigatorio: true,
  },
  { chave: 'descricao', rotulo: 'Descrição', tipo: 'texto-longo' },
]
</script>

<template>
  <EntityListPage
    titulo="Tipos de cobrança"
    descricao="Tipos utilizados para gerar cobranças recorrentes, com dia de vencimento padrão."
    modulo="Financeiro"
    query-key="financeiro-tipos-cobranca"
    permissao-base="financeiro.tipos_cobranca"
    :com-busca="false"
    :colunas="colunas"
    :campos="campos"
    :listar="tiposCobrancaApi.listarTiposCobranca"
    :criar="tiposCobrancaApi.criarTipoCobranca"
    :atualizar="tiposCobrancaApi.atualizarTipoCobranca"
    :inativar="tiposCobrancaApi.inativarTipoCobranca"
    :reativar="tiposCobrancaApi.reativarTipoCobranca"
    :buscar-historico="tiposCobrancaApi.historicoTipoCobranca"
  />
</template>
