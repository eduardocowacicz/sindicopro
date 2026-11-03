<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import type { Pessoa } from '@/modules/cadastros/types/pessoa.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const tipoRotulo: Record<string, string> = {
  MORADOR: 'Morador / Geral',
  PRESTADOR_SERVICO: 'Prestador de serviço',
  FORNECEDOR: 'Fornecedor',
}

const colunas: ColunaTabela<Pessoa>[] = [
  { chave: 'nome_completo', rotulo: 'Nome' },
  {
    chave: 'tipo',
    rotulo: 'Tipo',
    render: (p) => tipoRotulo[p.tipo] ?? p.tipo ?? 'Morador / Geral',
  },
  { chave: 'email', rotulo: 'E-mail', render: (p) => p.email ?? '—' },
  { chave: 'telefone', rotulo: 'Telefone', render: (p) => p.telefone ?? '—' },
]

const campos: CampoFormulario[] = [
  { chave: 'nome_completo', rotulo: 'Nome completo', tipo: 'texto', obrigatorio: true },
  {
    chave: 'tipo',
    rotulo: 'Tipo de cadastro',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'MORADOR', rotulo: 'Morador / Geral' },
      { valor: 'PRESTADOR_SERVICO', rotulo: 'Prestador de serviço' },
      { valor: 'FORNECEDOR', rotulo: 'Fornecedor' },
    ],
  },
  {
    chave: 'cpf',
    rotulo: 'CPF',
    tipo: 'cpf',
    placeholder: '000.000.000-00',
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
