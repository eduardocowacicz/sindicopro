import { http } from '@/api/http'
import type { RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Ambiente, BloqueioAmbiente, Reserva } from '../types/reserva.types'

export async function listarAmbientes(params?: {
  busca?: string
  somente_ativos?: boolean
  pagina?: number
}): Promise<RespostaPaginada<Ambiente>> {
  const resposta = await http.get<RespostaPaginada<Ambiente>>('/api/reservas/ambientes', {
    params: {
      busca: params?.busca,
      somente_ativos: params?.somente_ativos,
      page: params?.pagina,
    },
  })
  return resposta.data
}

export async function mostrarAmbiente(id: string): Promise<Ambiente> {
  const resposta = await http.get<RespostaItem<Ambiente>>(`/api/reservas/ambientes/${id}`)
  return resposta.data.dados
}

export async function criarAmbiente(dados: Record<string, unknown>): Promise<Ambiente> {
  const resposta = await http.post<RespostaItem<Ambiente>>('/api/reservas/ambientes', dados)
  return resposta.data.dados
}

export async function atualizarAmbiente(
  id: string,
  dados: Record<string, unknown>,
): Promise<Ambiente> {
  const resposta = await http.put<RespostaItem<Ambiente>>(`/api/reservas/ambientes/${id}`, dados)
  return resposta.data.dados
}

export async function inativarAmbiente(id: string): Promise<void> {
  await http.post(`/api/reservas/ambientes/${id}/inativar`)
}

export async function reativarAmbiente(id: string): Promise<void> {
  await http.post(`/api/reservas/ambientes/${id}/reativar`)
}

export async function listarBloqueios(params?: {
  ambiente_id?: string
  pagina?: number
}): Promise<RespostaPaginada<BloqueioAmbiente>> {
  const resposta = await http.get<RespostaPaginada<BloqueioAmbiente>>('/api/reservas/bloqueios', {
    params: { ambiente_id: params?.ambiente_id, page: params?.pagina },
  })
  return resposta.data
}

export async function criarBloqueio(dados: Record<string, unknown>): Promise<BloqueioAmbiente> {
  const resposta = await http.post<RespostaItem<BloqueioAmbiente>>('/api/reservas/bloqueios', dados)
  return resposta.data.dados
}

export async function cancelarBloqueio(id: string): Promise<void> {
  await http.post(`/api/reservas/bloqueios/${id}/cancelar`)
}

export async function listarReservas(params?: {
  busca?: string
  ambiente_id?: string
  data_inicio?: string
  data_fim?: string
  situacao?: string
  pagina?: number
}): Promise<RespostaPaginada<Reserva>> {
  const resposta = await http.get<RespostaPaginada<Reserva>>('/api/reservas/agenda', {
    params: {
      busca: params?.busca,
      ambiente_id: params?.ambiente_id,
      data_inicio: params?.data_inicio,
      data_fim: params?.data_fim,
      situacao: params?.situacao,
      page: params?.pagina,
    },
  })
  return resposta.data
}

export async function mostrarReserva(id: string): Promise<Reserva> {
  const resposta = await http.get<RespostaItem<Reserva>>(`/api/reservas/agenda/${id}`)
  return resposta.data.dados
}

export async function criarReserva(dados: Record<string, unknown>): Promise<Reserva> {
  const resposta = await http.post<RespostaItem<Reserva>>('/api/reservas/agenda', dados)
  return resposta.data.dados
}

export async function validarReserva(id: string): Promise<Reserva> {
  const resposta = await http.post<RespostaItem<Reserva>>(`/api/reservas/agenda/${id}/validar`)
  return resposta.data.dados
}

export async function cancelarReserva(id: string, motivo?: string): Promise<Reserva> {
  const resposta = await http.post<RespostaItem<Reserva>>(`/api/reservas/agenda/${id}/cancelar`, {
    motivo,
  })
  return resposta.data.dados
}
