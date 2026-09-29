<script setup lang="ts">
import EntityDetailPage from '@/components/app/crud/EntityDetailPage.vue'
import * as blocosApi from '@/modules/cadastros/api/blocos.api'
import type { Bloco } from '@/modules/cadastros/types/bloco.types'

const campos = [
  { rotulo: 'Código', render: (b: Bloco) => b.codigo },
  { rotulo: 'Nome', render: (b: Bloco) => b.nome },
  { rotulo: 'Andares', render: (b: Bloco) => b.quantidade_andares?.toString() ?? '—' },
  { rotulo: 'Observações', render: (b: Bloco) => b.observacoes ?? '—' },
]
</script>

<template>
  <EntityDetailPage
    titulo="Detalhe do bloco"
    descricao="Informações completas e histórico de alterações do bloco."
    modulo="Cadastros"
    query-key="bloco-detalhe"
    :campos="campos"
    :buscar="blocosApi.mostrarBloco"
    :buscar-historico="blocosApi.historicoBloco"
    status-campo="ativo"
  />
</template>
