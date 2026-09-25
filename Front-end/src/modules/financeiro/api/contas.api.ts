import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { ContaFinanceira } from '@/modules/financeiro/types/conta.types'

export async function listarContas(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<ContaFinanceira>> {
  const resposta = await http.get<RespostaPaginada<ContaFinanceira>>('/api/financeiro/contas', {
    params: { page: params.pagina },
  })
  return resposta.data
}

export async function listarContasParaSelecao(): Promise<ContaFinanceira[]> {
  const resposta = await http.get<RespostaPaginada<ContaFinanceira>>('/api/financeiro/contas', {
    params: { somente_ativos: true, por_pagina: 200 },
  })
  return resposta.data.dados
}

export async function criarConta(dados: Record<string, unknown>): Promise<ContaFinanceira> {
  const resposta = await http.post<RespostaItem<ContaFinanceira>>('/api/financeiro/contas', dados)
  return resposta.data.dados
}

export async function atualizarConta(
  id: string,
  dados: Record<string, unknown>,
): Promise<ContaFinanceira> {
  const resposta = await http.put<RespostaItem<ContaFinanceira>>(
    `/api/financeiro/contas/${id}`,
    dados,
  )
  return resposta.data.dados
}

export async function inativarConta(id: string): Promise<void> {
  await http.post(`/api/financeiro/contas/${id}/inativar`)
}

export async function reativarConta(id: string): Promise<void> {
  await http.post(`/api/financeiro/contas/${id}/reativar`)
}

export async function historicoConta(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/financeiro/contas/${id}/historico`,
  )
  return resposta.data.dados
}
