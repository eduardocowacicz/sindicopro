<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as contasApi from '@/modules/financeiro/api/contas.api'
import type { ContaFinanceira } from '@/modules/financeiro/types/conta.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<ContaFinanceira>[] = [
  { chave: 'nome', rotulo: 'Nome' },
  {
    chave: 'tipo_conta',
    rotulo: 'Tipo',
    render: (c) =>
      ({ CONTA_CORRENTE: 'Conta corrente', POUPANCA: 'Poupança', CAIXA: 'Caixa' })[c.tipo_conta],
  },
  { chave: 'nome_banco', rotulo: 'Banco', render: (c) => c.nome_banco ?? '—' },
  {
    chave: 'ultimos4_conta',
    rotulo: 'Conta',
    render: (c) => (c.ultimos4_conta ? `••••${c.ultimos4_conta}` : '—'),
  },
  {
    chave: 'saldo_inicial',
    rotulo: 'Saldo inicial',
    render: (c) =>
      Number(c.saldo_inicial).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
  },
]

const campos: CampoFormulario[] = [
  { chave: 'nome', rotulo: 'Nome da conta', tipo: 'texto', obrigatorio: true },
  {
    chave: 'tipo_conta',
    rotulo: 'Tipo de conta',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'CONTA_CORRENTE', rotulo: 'Conta corrente' },
      { valor: 'POUPANCA', rotulo: 'Poupança' },
      { valor: 'CAIXA', rotulo: 'Caixa' },
    ],
  },
  { chave: 'nome_banco', rotulo: 'Banco', tipo: 'texto' },
  { chave: 'codigo_banco', rotulo: 'Código do banco', tipo: 'texto' },
  { chave: 'agencia', rotulo: 'Agência', tipo: 'texto', somenteCriacao: true },
  { chave: 'numero_conta', rotulo: 'Número da conta', tipo: 'texto', somenteCriacao: true },
  { chave: 'saldo_inicial', rotulo: 'Saldo inicial', tipo: 'numero', obrigatorio: true },
  { chave: 'data_saldo_inicial', rotulo: 'Data do saldo inicial', tipo: 'data', obrigatorio: true },
]
</script>

<template>
  <EntityListPage
    titulo="Contas bancárias"
    descricao="Contas correntes, poupança e caixa utilizadas na movimentação financeira do condomínio."
    modulo="Financeiro"
    query-key="financeiro-contas"
    permissao-base="financeiro.contas"
    :com-busca="false"
    :colunas="colunas"
    :campos="campos"
    :listar="contasApi.listarContas"
    :criar="contasApi.criarConta"
    :atualizar="contasApi.atualizarConta"
    :inativar="contasApi.inativarConta"
    :reativar="contasApi.reativarConta"
    :buscar-historico="contasApi.historicoConta"
  />
</template>
