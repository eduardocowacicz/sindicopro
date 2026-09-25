<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import type { Pessoa } from '@/modules/cadastros/types/pessoa.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<Pessoa>[] = [
  { chave: 'nome_completo', rotulo: 'Nome' },
  { chave: 'email', rotulo: 'E-mail', render: (p) => p.email ?? '—' },
  { chave: 'telefone', rotulo: 'Telefone', render: (p) => p.telefone ?? '—' },
]

const campos: CampoFormulario[] = [
  { chave: 'nome_completo', rotulo: 'Nome completo', tipo: 'texto', obrigatorio: true },
  {
    chave: 'cpf',
    rotulo: 'CPF',
    tipo: 'texto',
    placeholder: 'Somente números',
    somenteCriacao: true,
  },
  { chave: 'email', rotulo: 'E-mail', tipo: 'texto' },
  { chave: 'telefone', rotulo: 'Telefone', tipo: 'texto' },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
]
</script>

<template>
  <EntityListPage
    titulo="Pessoas"
    descricao="Cadastro de moradores, proprietários e demais pessoas vinculadas ao condomínio."
    modulo="Cadastros"
    query-key="pessoas"
    permissao-base="cadastros.pessoas"
    :colunas="colunas"
    :campos="campos"
    :listar="pessoasApi.listarPessoas"
    :criar="pessoasApi.criarPessoa"
    :atualizar="pessoasApi.atualizarPessoa"
    :inativar="pessoasApi.inativarPessoa"
    :reativar="pessoasApi.reativarPessoa"
    :buscar-historico="pessoasApi.historicoPessoa"
  />
</template>
