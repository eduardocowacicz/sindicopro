import { http } from '@/api/http'
import type { RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Comunicado } from '../types/comunicado.types'

export async function listarComunicados(params?: {
  busca?: string
  situacao?: string
  pagina?: number
}): Promise<RespostaPaginada<Comunicado>> {
  const resposta = await http.get<RespostaPaginada<Comunicado>>('/api/comunicados', {
    params: {
      busca: params?.busca,
      situacao: params?.situacao,
      page: params?.pagina,
    },
  })
  return resposta.data
}

export async function mostrarComunicado(id: string): Promise<Comunicado> {
  const resposta = await http.get<RespostaItem<Comunicado>>(`/api/comunicados/${id}`)
  return resposta.data.dados
}

export async function criarComunicado(dados: Record<string, unknown>): Promise<Comunicado> {
  const resposta = await http.post<RespostaItem<Comunicado>>('/api/comunicados', dados)
  return resposta.data.dados
}

export async function publicarComunicado(id: string): Promise<Comunicado> {
  const resposta = await http.post<RespostaItem<Comunicado>>(`/api/comunicados/${id}/publicar`)
  return resposta.data.dados
}

export async function cancelarComunicado(id: string): Promise<Comunicado> {
  const resposta = await http.post<RespostaItem<Comunicado>>(`/api/comunicados/${id}/cancelar`)
  return resposta.data.dados
}
