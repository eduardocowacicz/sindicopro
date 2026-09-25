import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Unidade } from '@/modules/cadastros/types/unidade.types'

export async function listarUnidades(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Unidade>> {
  const resposta = await http.get<RespostaPaginada<Unidade>>('/api/unidades', {
    params: { busca: params.busca, page: params.pagina },
  })
  return resposta.data
}

export async function mostrarUnidade(id: string): Promise<Unidade> {
  const resposta = await http.get<RespostaItem<Unidade>>(`/api/unidades/${id}`)
  return resposta.data.dados
}

export async function criarUnidade(dados: Record<string, unknown>): Promise<Unidade> {
  const resposta = await http.post<RespostaItem<Unidade>>('/api/unidades', dados)
  return resposta.data.dados
}

export async function atualizarUnidade(
  id: string,
  dados: Record<string, unknown>,
): Promise<Unidade> {
  const resposta = await http.put<RespostaItem<Unidade>>(`/api/unidades/${id}`, dados)
  return resposta.data.dados
}

export async function inativarUnidade(id: string): Promise<void> {
  await http.post(`/api/unidades/${id}/inativar`)
}

export async function reativarUnidade(id: string): Promise<void> {
  await http.post(`/api/unidades/${id}/reativar`)
}

export async function historicoUnidade(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/unidades/${id}/historico`,
  )
  return resposta.data.dados
}

export async function listarUnidadesParaSelecao(): Promise<Unidade[]> {
  const resposta = await http.get<RespostaPaginada<Unidade>>('/api/unidades', {
    params: { somente_ativos: true, por_pagina: 500 },
  })
  return resposta.data.dados
}
