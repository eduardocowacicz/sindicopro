import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Pessoa } from '@/modules/cadastros/types/pessoa.types'

export async function listarPessoas(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Pessoa>> {
  const resposta = await http.get<RespostaPaginada<Pessoa>>('/api/pessoas', {
    params: { busca: params.busca, page: params.pagina },
  })
  return resposta.data
}

export async function mostrarPessoa(id: string): Promise<Pessoa> {
  const resposta = await http.get<RespostaItem<Pessoa>>(`/api/pessoas/${id}`)
  return resposta.data.dados
}

export async function criarPessoa(dados: Record<string, unknown>): Promise<Pessoa> {
  const resposta = await http.post<RespostaItem<Pessoa>>('/api/pessoas', dados)
  return resposta.data.dados
}

export async function atualizarPessoa(id: string, dados: Record<string, unknown>): Promise<Pessoa> {
  const resposta = await http.put<RespostaItem<Pessoa>>(`/api/pessoas/${id}`, dados)
  return resposta.data.dados
}

export async function inativarPessoa(id: string): Promise<void> {
  await http.post(`/api/pessoas/${id}/inativar`)
}

export async function reativarPessoa(id: string): Promise<void> {
  await http.post(`/api/pessoas/${id}/reativar`)
}

export async function historicoPessoa(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(`/api/pessoas/${id}/historico`)
  return resposta.data.dados
}

export async function listarPessoasParaSelecao(): Promise<Pessoa[]> {
  const resposta = await http.get<RespostaPaginada<Pessoa>>('/api/pessoas', {
    params: { somente_ativos: true, por_pagina: 200 },
  })
  return resposta.data.dados
}
