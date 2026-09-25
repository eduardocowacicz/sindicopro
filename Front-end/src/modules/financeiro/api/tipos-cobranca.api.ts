import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { TipoCobranca } from '@/modules/financeiro/types/tipo-cobranca.types'

export async function listarTiposCobranca(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<TipoCobranca>> {
  const resposta = await http.get<RespostaPaginada<TipoCobranca>>(
    '/api/financeiro/tipos-cobranca',
    {
      params: { page: params.pagina },
    },
  )
  return resposta.data
}

export async function listarTiposCobrancaParaSelecao(): Promise<TipoCobranca[]> {
  const resposta = await http.get<RespostaPaginada<TipoCobranca>>(
    '/api/financeiro/tipos-cobranca',
    {
      params: { somente_ativos: true, por_pagina: 200 },
    },
  )
  return resposta.data.dados
}

export async function criarTipoCobranca(dados: Record<string, unknown>): Promise<TipoCobranca> {
  const resposta = await http.post<RespostaItem<TipoCobranca>>(
    '/api/financeiro/tipos-cobranca',
    dados,
  )
  return resposta.data.dados
}

export async function atualizarTipoCobranca(
  id: string,
  dados: Record<string, unknown>,
): Promise<TipoCobranca> {
  const resposta = await http.put<RespostaItem<TipoCobranca>>(
    `/api/financeiro/tipos-cobranca/${id}`,
    dados,
  )
  return resposta.data.dados
}

export async function inativarTipoCobranca(id: string): Promise<void> {
  await http.post(`/api/financeiro/tipos-cobranca/${id}/inativar`)
}

export async function reativarTipoCobranca(id: string): Promise<void> {
  await http.post(`/api/financeiro/tipos-cobranca/${id}/reativar`)
}

export async function historicoTipoCobranca(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/financeiro/tipos-cobranca/${id}/historico`,
  )
  return resposta.data.dados
}
