<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'

import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as pessoasApi from '@/modules/cadastros/api/pessoas.api'
import * as unidadesApi from '@/modules/cadastros/api/unidades.api'
import * as veiculosApi from '@/modules/cadastros/api/veiculos.api'
import type { Veiculo } from '@/modules/cadastros/types/veiculo.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const { data: unidades } = useQuery({
  queryKey: ['unidades-selecao'],
  queryFn: unidadesApi.listarUnidadesParaSelecao,
})

const { data: pessoas } = useQuery({
  queryKey: ['pessoas-selecao'],
  queryFn: pessoasApi.listarPessoasParaSelecao,
})

const colunas: ColunaTabela<Veiculo>[] = [
  { chave: 'unidade', rotulo: 'Apartamento', render: (v) => v.unidade?.codigo ?? '—' },
  { chave: 'pessoa', rotulo: 'Condutor', render: (v) => v.pessoa?.nome_completo ?? '—' },
  { chave: 'placa', rotulo: 'Placa' },
  { chave: 'modelo', rotulo: 'Modelo' },
  { chave: 'cor', rotulo: 'Cor', render: (v) => v.cor ?? '—' },
  { chave: 'vaga', rotulo: 'Vaga', render: (v) => v.vaga ?? '—' },
]

const campos = computed<CampoFormulario[]>(() => [
  {
    chave: 'unidade_id',
    rotulo: 'Apartamento',
    tipo: 'selecao',
    obrigatorio: true,
    opcoes: (unidades.value ?? []).map((u) => ({ valor: u.id_publico, rotulo: u.codigo })),
  },
  {
    chave: 'pessoa_id',
    rotulo: 'Proprietário/condutor',
    tipo: 'selecao',
    opcoes: (pessoas.value ?? []).map((p) => ({ valor: p.id_publico, rotulo: p.nome_completo })),
  },
  { chave: 'placa', rotulo: 'Placa', tipo: 'texto', obrigatorio: true },
  { chave: 'modelo', rotulo: 'Modelo', tipo: 'texto', obrigatorio: true },
  { chave: 'cor', rotulo: 'Cor', tipo: 'texto' },
  { chave: 'vaga', rotulo: 'Vaga', tipo: 'texto' },
  { chave: 'observacoes', rotulo: 'Observações', tipo: 'texto-longo' },
])
</script>

<template>
  <EntityListPage
    titulo="Veículos"
    descricao="Veículos cadastrados por apartamento, com vaga e condutor associado."
    modulo="Cadastros"
    query-key="veiculos"
    permissao-base="cadastros.veiculos"
    :colunas="colunas"
    :campos="campos"
    :listar="veiculosApi.listarVeiculos"
    :criar="veiculosApi.criarVeiculo"
    :atualizar="veiculosApi.atualizarVeiculo"
    :inativar="veiculosApi.inativarVeiculo"
    :reativar="veiculosApi.reativarVeiculo"
    :buscar-historico="veiculosApi.historicoVeiculo"
  />
</template>
