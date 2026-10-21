<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as ocorrenciasApi from '@/modules/ocorrencias/api/ocorrencias.api'
import type { TipoOcorrencia } from '@/modules/ocorrencias/types/ocorrencia.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<TipoOcorrencia>[] = [
  { chave: 'nome', rotulo: 'Categoria' },
  { chave: 'codigo', rotulo: 'Código' },
  { chave: 'descricao', rotulo: 'Descrição', render: (t) => t.descricao ?? '—' },
]

const campos: CampoFormulario[] = [
  { chave: 'nome', rotulo: 'Nome da categoria', tipo: 'texto', obrigatorio: true },
  { chave: 'descricao', rotulo: 'Descrição / Orientações', tipo: 'texto-longo' },
]
</script>

<template>
  <EntityListPage
    titulo="Tipos de Ocorrência"
    descricao="Categorias de chamados para triagem e atendimento das demandas condominiais."
    modulo="Ocorrências"
    query-key="ocorrencias-tipos"
    permissao-base="ocorrencias.tipos"
    :colunas="colunas"
    :campos="campos"
    :listar="ocorrenciasApi.listarTiposOcorrencia"
    :criar="ocorrenciasApi.criarTipoOcorrencia"
    :atualizar="ocorrenciasApi.atualizarTipoOcorrencia"
    :inativar="ocorrenciasApi.inativarTipoOcorrencia"
    :reativar="ocorrenciasApi.reativarTipoOcorrencia"
  />
</template>
