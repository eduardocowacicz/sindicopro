<script setup lang="ts">
import EntityDetailPage from '@/components/app/crud/EntityDetailPage.vue'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import type { Pessoa } from '@/modules/cadastros/types/pessoa.types'

const campos = [
  { rotulo: 'Nome completo', render: (p: Pessoa) => p.nome_completo },
  { rotulo: 'E-mail', render: (p: Pessoa) => p.email ?? '—' },
  { rotulo: 'Telefone', render: (p: Pessoa) => p.telefone ?? '—' },
  { rotulo: 'Observações', render: (p: Pessoa) => p.observacoes ?? '—' },
]
</script>

<template>
  <EntityDetailPage
    titulo="Detalhe da pessoa"
    descricao="Informações completas e histórico de alterações da pessoa cadastrada."
    modulo="Cadastros"
    query-key="pessoa-detalhe"
    :campos="campos"
    :buscar="pessoasApi.mostrarPessoa"
    :buscar-historico="pessoasApi.historicoPessoa"
    status-campo="ativo"
  />
</template>
