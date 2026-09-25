import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { CategoriaFinanceira } from '@/modules/financeiro/types/categoria.types'

export async function listarCategorias(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<CategoriaFinanceira>> {
  const resposta = await http.get<RespostaPaginada<CategoriaFinanceira>>(
    '/api/financeiro/categorias',
    {
      params: { page: params.pagina },
    },
  )
  return resposta.data
}

export async function listarCategoriasParaSelecao(): Promise<CategoriaFinanceira[]> {
  const resposta = await http.get<RespostaPaginada<CategoriaFinanceira>>(
    '/api/financeiro/categorias',
    {
      params: { somente_ativos: true, por_pagina: 200 },
    },
  )
  return resposta.data.dados
}

export async function criarCategoria(dados: Record<string, unknown>): Promise<CategoriaFinanceira> {
  const resposta = await http.post<RespostaItem<CategoriaFinanceira>>(
    '/api/financeiro/categorias',
    dados,
  )
  return resposta.data.dados
}

export async function atualizarCategoria(
  id: string,
  dados: Record<string, unknown>,
): Promise<CategoriaFinanceira> {
  const resposta = await http.put<RespostaItem<CategoriaFinanceira>>(
    `/api/financeiro/categorias/${id}`,
    dados,
  )
  return resposta.data.dados
}

export async function inativarCategoria(id: string): Promise<void> {
  await http.post(`/api/financeiro/categorias/${id}/inativar`)
}

export async function reativarCategoria(id: string): Promise<void> {
  await http.post(`/api/financeiro/categorias/${id}/reativar`)
}

export async function historicoCategoria(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/financeiro/categorias/${id}/historico`,
  )
  return resposta.data.dados
}
