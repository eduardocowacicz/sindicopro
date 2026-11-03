<script setup lang="ts">
import EntityListPage from '@/components/app/crud/EntityListPage.vue'
import * as reservasApi from '@/modules/reservas/api/reservas.api'
import type { Ambiente } from '@/modules/reservas/types/reserva.types'
import type { CampoFormulario, ColunaTabela } from '@/lib/crud/types'

const colunas: ColunaTabela<Ambiente>[] = [
  { chave: 'nome', rotulo: 'Ambiente' },
  { chave: 'codigo', rotulo: 'Código' },
  {
    chave: 'horas_antecedencia_minima_morador',
    rotulo: 'Antecedência Mín.',
    render: (a) => `${a.horas_antecedencia_minima_morador ?? 0}h`,
  },
  {
    chave: 'horas_limite_cancelamento_morador',
    rotulo: 'Cancelamento até',
    render: (a) => `${a.horas_limite_cancelamento_morador ?? 0}h antes`,
  },
  {
    chave: 'aprovacao_automatica',
    rotulo: 'Aprovação Auto.',
    render: (a) => (a.aprovacao_automatica ? 'Sim' : 'Manual'),
  },
  {
    chave: 'bloquear_unidades_inadimplentes',
    rotulo: 'Bloqueia Inadimplente',
    render: (a) => (a.bloquear_unidades_inadimplentes ? 'Sim' : 'Não'),
  },
]

const campos: CampoFormulario[] = [
  { chave: 'nome', rotulo: 'Nome do ambiente', tipo: 'texto', obrigatorio: true },
  { chave: 'descricao', rotulo: 'Descrição / Regras de uso', tipo: 'texto-longo' },
  {
    chave: 'horas_antecedencia_minima_morador',
    rotulo: 'Antecedência mínima (horas)',
    tipo: 'numero',
    obrigatorio: true,
    ajuda: 'Horas mínimas de antecedência para solicitação por moradores (ex: 24).',
  },
  {
    chave: 'horas_limite_cancelamento_morador',
    rotulo: 'Prazo limite para cancelamento (horas)',
    tipo: 'numero',
    obrigatorio: true,
    ajuda: 'Horas mínimas antes do horário da reserva para o morador cancelar (ex: 48).',
  },
  {
    chave: 'aprovacao_automatica',
    rotulo: 'Aprovação automática ao solicitar',
    tipo: 'checkbox',
  },
  {
    chave: 'bloquear_unidades_inadimplentes',
    rotulo: 'Bloquear unidades inadimplentes automaticamente',
    tipo: 'checkbox',
  },
]
</script>

<template>
  <EntityListPage
    titulo="Ambientes e Espaços"
    descricao="Gestão de áreas comuns, regras de reserva, antecedências e bloqueios automáticos."
    modulo="Reservas"
    query-key="reservas-ambientes"
    permissao-base="reservas.ambientes"
    :colunas="colunas"
    :campos="campos"
    :listar="reservasApi.listarAmbientes"
    :criar="reservasApi.criarAmbiente"
    :atualizar="reservasApi.atualizarAmbiente"
    :inativar="reservasApi.inativarAmbiente"
    :reativar="reservasApi.reativarAmbiente"
  />
</template>
