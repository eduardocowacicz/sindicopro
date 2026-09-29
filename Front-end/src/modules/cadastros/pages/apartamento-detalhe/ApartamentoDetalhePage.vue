<script setup lang="ts">
import EntityDetailPage from '@/components/app/crud/EntityDetailPage.vue'
import * as unidadesApi from '@/modules/cadastros/api/unidades.api'
import type { Unidade } from '@/modules/cadastros/types/unidade.types'

const campos = [
  { rotulo: 'Bloco', render: (u: Unidade) => u.bloco?.nome ?? '—' },
  { rotulo: 'Código', render: (u: Unidade) => u.codigo },
  { rotulo: 'Andar', render: (u: Unidade) => u.numero_andar?.toString() ?? '—' },
  {
    rotulo: 'Ocupação',
    render: (u: Unidade) => (u.situacao_ocupacao === 'OCUPADO' ? 'Ocupado' : 'Vago'),
  },
  { rotulo: 'Observações', render: (u: Unidade) => u.observacoes ?? '—' },
]
</script>

<template>
  <EntityDetailPage
    titulo="Detalhe do apartamento"
    descricao="Informações completas e histórico de alterações da unidade."
    modulo="Cadastros"
    query-key="unidade-detalhe"
    :campos="campos"
    :buscar="unidadesApi.mostrarUnidade"
    :buscar-historico="unidadesApi.historicoUnidade"
    status-campo="ativo"
  />
</template>
