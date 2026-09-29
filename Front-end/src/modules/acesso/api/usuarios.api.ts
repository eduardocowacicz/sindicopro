import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Usuario } from '@/modules/acesso/types/usuario.types'

export async function listarUsuarios(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Usuario>> {
  const resposta = await http.get<RespostaPaginada<Usuario>>('/api/usuarios', {
    params: { busca: params.busca, page: params.pagina },
  })
  return resposta.data
}

export async function mostrarUsuario(id: string): Promise<Usuario> {
  const resposta = await http.get<RespostaItem<Usuario>>(`/api/usuarios/${id}`)
  return resposta.data.dados
}

export async function criarUsuario(dados: Record<string, unknown>): Promise<Usuario> {
  const resposta = await http.post<RespostaItem<Usuario>>('/api/usuarios', dados)
  return resposta.data.dados
}

export async function atualizarUsuario(
  id: string,
  dados: Record<string, unknown>,
): Promise<Usuario> {
  const resposta = await http.put<RespostaItem<Usuario>>(`/api/usuarios/${id}`, dados)
  return resposta.data.dados
}

export async function inativarUsuario(id: string): Promise<void> {
  await http.post(`/api/usuarios/${id}/inativar`)
}

export async function reativarUsuario(id: string): Promise<void> {
  await http.post(`/api/usuarios/${id}/reativar`)
}

export async function bloquearUsuario(id: string, motivo?: string): Promise<void> {
  await http.post(`/api/usuarios/${id}/bloquear`, { motivo })
}

export async function desbloquearUsuario(id: string): Promise<void> {
  await http.post(`/api/usuarios/${id}/desbloquear`)
}

export async function redefinirSenhaUsuario(id: string): Promise<string> {
  const resposta = await http.post<RespostaItem<{ senha_temporaria: string }>>(
    `/api/usuarios/${id}/redefinir-senha`,
  )
  return resposta.data.dados.senha_temporaria
}

export async function historicoUsuario(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/usuarios/${id}/historico`,
  )
  return resposta.data.dados
}
