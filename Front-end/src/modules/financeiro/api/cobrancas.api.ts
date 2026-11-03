import { http } from '@/api/http'
import type { RespostaItem } from '@/lib/api-types'
import type {
  CobrancaItem,
  FiltrosCobranca,
  RegistrarPagamentoPayload,
  RespostaCobrancas,
} from '@/modules/financeiro/types/cobranca.types'

export async function listarCobrancas(filtros: FiltrosCobranca = {}): Promise<RespostaCobrancas> {
  const params: Record<string, unknown> = {}
  if (filtros.situacao && filtros.situacao !== 'TODOS') params.situacao = filtros.situacao
  if (filtros.mes) params.mes = filtros.mes
  if (filtros.ano) params.ano = filtros.ano
  if (filtros.busca) params.busca = filtros.busca
  if (filtros.pagina) params.page = filtros.pagina
  if (filtros.por_pagina) params.por_pagina = filtros.por_pagina

  const resposta = await http.get<RespostaCobrancas>('/api/financeiro/cobrancas', { params })
  return resposta.data
}

export async function detalharCobranca(idPublico: string): Promise<CobrancaItem> {
  const resposta = await http.get<RespostaItem<CobrancaItem>>(
    `/api/financeiro/cobrancas/${idPublico}`,
  )
  return resposta.data.dados
}

export async function registrarPagamento(
  idPublico: string,
  payload: RegistrarPagamentoPayload,
): Promise<{ dados: CobrancaItem; mensagem: string }> {
  const resposta = await http.post<{ dados: CobrancaItem; mensagem: string }>(
    `/api/financeiro/cobrancas/${idPublico}/registrar-pagamento`,
    payload,
  )
  return resposta.data
}

export async function cancelarCobranca(idPublico: string, motivo?: string): Promise<void> {
  await http.post(`/api/financeiro/cobrancas/${idPublico}/cancelar`, { motivo })
}
