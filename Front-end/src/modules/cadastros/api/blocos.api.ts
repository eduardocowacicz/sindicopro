import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Bloco } from '@/modules/cadastros/types/bloco.types'

export async function listarBlocos(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Bloco>> {
  const resposta = await http.get<RespostaPaginada<Bloco>>('/api/blocos', {
    params: { busca: params.busca, page: params.pagina },
  })
  return resposta.data
}

export async function mostrarBloco(id: string): Promise<Bloco> {
  const resposta = await http.get<RespostaItem<Bloco>>(`/api/blocos/${id}`)
  return resposta.data.dados
}

export async function criarBloco(dados: Record<string, unknown>): Promise<Bloco> {
  const resposta = await http.post<RespostaItem<Bloco>>('/api/blocos', dados)
  return resposta.data.dados
}

export async function atualizarBloco(id: string, dados: Record<string, unknown>): Promise<Bloco> {
  const resposta = await http.put<RespostaItem<Bloco>>(`/api/blocos/${id}`, dados)
  return resposta.data.dados
}

export async function inativarBloco(id: string): Promise<void> {
  await http.post(`/api/blocos/${id}/inativar`)
}

export async function reativarBloco(id: string): Promise<void> {
  await http.post(`/api/blocos/${id}/reativar`)
}

export async function historicoBloco(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(`/api/blocos/${id}/historico`)
  return resposta.data.dados
}

export async function listarBlocosParaSelecao(): Promise<Bloco[]> {
  const resposta = await http.get<RespostaPaginada<Bloco>>('/api/blocos', {
    params: { somente_ativos: true, por_pagina: 200 },
  })
  return resposta.data.dados
}
