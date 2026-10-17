import { http } from '@/api/http'
import type { RespostaItem } from '@/lib/api-types'
import type { DashboardMorador, UnidadeMorador } from '../types/morador.types'
import type { Cobranca } from '@/modules/financeiro/types/cobranca.types'
import type { FechamentoMensal } from '@/modules/fechamentos/types/fechamento.types'
import type { Ambiente, Reserva } from '@/modules/reservas/types/reserva.types'
import type { Comunicado } from '@/modules/comunicados/types/comunicado.types'
import type { Ocorrencia } from '@/modules/ocorrencias/types/ocorrencia.types'

export async function obterDashboardMorador(): Promise<DashboardMorador> {
  const resposta = await http.get<RespostaItem<DashboardMorador>>('/api/morador/dashboard')
  return resposta.data.dados
}

export async function obterMinhaUnidade(unidadeId?: string): Promise<UnidadeMorador> {
  const resposta = await http.get<RespostaItem<UnidadeMorador>>('/api/morador/unidade', {
    params: { unidade_id: unidadeId },
  })
  return resposta.data.dados
}

export async function obterMinhasCobrancas(): Promise<Cobranca[]> {
  const resposta = await http.get<RespostaItem<Cobranca[]>>('/api/morador/cobrancas')
  return resposta.data.dados
}

export async function obterFechamentosPublicados(): Promise<FechamentoMensal[]> {
  const resposta = await http.get<RespostaItem<FechamentoMensal[]>>('/api/morador/fechamentos')
  return resposta.data.dados
}

export async function obterAmbientesDisponiveis(): Promise<Ambiente[]> {
  const resposta = await http.get<RespostaItem<Ambiente[]>>('/api/morador/ambientes')
  return resposta.data.dados
}

export async function obterMinhasReservas(): Promise<Reserva[]> {
  const resposta = await http.get<RespostaItem<Reserva[]>>('/api/morador/reservas')
  return resposta.data.dados
}

export async function solicitarReserva(dados: {
  ambiente_id: string
  faixa_horario_id: string
  data_reserva: string
  unidade_id?: string
  observacoes?: string
}): Promise<Reserva> {
  const resposta = await http.post<RespostaItem<Reserva>>('/api/morador/reservas', dados)
  return resposta.data.dados
}

export async function cancelarMinhaReserva(id: string): Promise<Reserva> {
  const resposta = await http.post<RespostaItem<Reserva>>(`/api/morador/reservas/${id}/cancelar`)
  return resposta.data.dados
}

export async function obterMeusComunicados(): Promise<Comunicado[]> {
  const resposta = await http.get<RespostaItem<Comunicado[]>>('/api/morador/comunicados')
  return resposta.data.dados
}

export async function marcarComunicadoLido(id: string): Promise<void> {
  await http.post(`/api/morador/comunicados/${id}/lido`)
}

export async function obterMinhasOcorrencias(): Promise<Ocorrencia[]> {
  const resposta = await http.get<RespostaItem<Ocorrencia[]>>('/api/morador/ocorrencias')
  return resposta.data.dados
}

export async function abrirMinhaOcorrencia(dados: {
  tipo_id: string
  motivo: string
  descricao: string
  unidade_id?: string
}): Promise<Ocorrencia> {
  const resposta = await http.post<RespostaItem<Ocorrencia>>('/api/morador/ocorrencias', dados)
  return resposta.data.dados
}

export async function comentarMinhaOcorrencia(id: string, conteudo: string): Promise<void> {
  await http.post(`/api/morador/ocorrencias/${id}/comentar`, { conteudo })
}
