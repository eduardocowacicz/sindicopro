<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'

import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as blocosApi from '@/modules/cadastros/api/blocos.api'
import * as unidadesApi from '@/modules/cadastros/api/unidades.api'
import type { Unidade } from '@/modules/cadastros/types/unidade.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const { data: blocos } = useQuery({
  queryKey: ['blocos-selecao'],
  queryFn: blocosApi.listarBlocosParaSelecao,
})

const colunas: ColunaTabela<Unidade>[] = [
  { chave: 'bloco', rotulo: 'Bloco', render: (u) => u.bloco?.nome ?? '—' },
  { chave: 'codigo', rotulo: 'Apartamento' },
  {
    chave: 'situacao_ocupacao',
    rotulo: 'Ocupação',
    render: (u) => (u.situacao_ocupacao === 'OCUPADO' ? 'Ocupado' : 'Vago'),
  },
]

const campos = computed<CampoFormulario[]>(() => [
  {
    chave: 'bloco_id',
    rotulo: 'Bloco',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (blocos.value ?? []).map((b) => ({ valor: b.id_publico, rotulo: b.nome })),
  },
  { chave: 'codigo', rotulo: 'Código do apartamento', tipo: 'texto', obrigatorio: true },
  { chave: 'numero_andar', rotulo: 'Andar', tipo: 'numero' },
  {
    chave: 'situacao_ocupacao',
    rotulo: 'Situação de ocupação',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: [
      { valor: 'VAGO', rotulo: 'Vago' },
      { valor: 'OCUPADO', rotulo: 'Ocupado' },
    ],
  },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
])
</script>

<template>
  <EntityListPage
    titulo="Apartamentos"
    descricao="Unidades organizadas por bloco, ocupação e vínculos atuais."
    modulo="Cadastros"
    query-key="unidades"
    permissao-base="cadastros.unidades"
    :colunas="colunas"
    :campos="campos"
    :listar="unidadesApi.listarUnidades"
    :criar="unidadesApi.criarUnidade"
    :atualizar="unidadesApi.atualizarUnidade"
    :inativar="unidadesApi.inativarUnidade"
    :reativar="unidadesApi.reativarUnidade"
    :buscar-historico="unidadesApi.historicoUnidade"
  />
</template>
