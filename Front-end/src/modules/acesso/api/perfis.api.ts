import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Perfil, PermissaoPerfil } from '@/modules/acesso/types/perfil.types'

export async function listarPerfis(params: { pagina: number }): Promise<RespostaPaginada<Perfil>> {
  const resposta = await http.get<RespostaPaginada<Perfil>>('/api/perfis', {
    params: { page: params.pagina },
  })
  return resposta.data
}

export async function listarPerfisParaSelecao(): Promise<Perfil[]> {
  const resposta = await http.get<RespostaPaginada<Perfil>>('/api/perfis', {
    params: { somente_ativos: true, por_pagina: 200 },
  })
  return resposta.data.dados
}

export async function mostrarPerfil(id: string): Promise<Perfil> {
  const resposta = await http.get<RespostaItem<Perfil>>(`/api/perfis/${id}`)
  return resposta.data.dados
}

export async function criarPerfil(dados: Record<string, unknown>): Promise<Perfil> {
  const resposta = await http.post<RespostaItem<Perfil>>('/api/perfis', dados)
  return resposta.data.dados
}

export async function atualizarPerfil(id: string, dados: Record<string, unknown>): Promise<Perfil> {
  const resposta = await http.put<RespostaItem<Perfil>>(`/api/perfis/${id}`, dados)
  return resposta.data.dados
}

export async function inativarPerfil(id: string): Promise<void> {
  await http.post(`/api/perfis/${id}/inativar`)
}

export async function reativarPerfil(id: string): Promise<void> {
  await http.post(`/api/perfis/${id}/reativar`)
}

export async function historicoPerfil(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(`/api/perfis/${id}/historico`)
  return resposta.data.dados
}

export async function listarPermissoesDoPerfil(id: string): Promise<PermissaoPerfil[]> {
  const resposta = await http.get<RespostaItem<PermissaoPerfil[]>>(`/api/perfis/${id}/permissoes`)
  return resposta.data.dados
}

export async function definirPermissoesDoPerfil(
  id: string,
  permissoes: string[],
): Promise<PermissaoPerfil[]> {
  const resposta = await http.put<RespostaItem<PermissaoPerfil[]>>(`/api/perfis/${id}/permissoes`, {
    permissoes,
  })
  return resposta.data.dados
}
