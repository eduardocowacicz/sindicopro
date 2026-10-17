import { http } from '@/api/http'
import type { RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { ComentarioOcorrencia, Ocorrencia, TipoOcorrencia } from '../types/ocorrencia.types'

export async function listarTiposOcorrencia(params?: {
  somente_ativos?: boolean
  pagina?: number
}): Promise<RespostaPaginada<TipoOcorrencia>> {
  const resposta = await http.get<RespostaPaginada<TipoOcorrencia>>('/api/ocorrencias/tipos', {
    params: { somente_ativos: params?.somente_ativos, page: params?.pagina },
  })
  return resposta.data
}

export async function criarTipoOcorrencia(dados: Record<string, unknown>): Promise<TipoOcorrencia> {
  const resposta = await http.post<RespostaItem<TipoOcorrencia>>('/api/ocorrencias/tipos', dados)
  return resposta.data.dados
}

export async function atualizarTipoOcorrencia(
  id: string,
  dados: Record<string, unknown>,
): Promise<TipoOcorrencia> {
  const resposta = await http.put<RespostaItem<TipoOcorrencia>>(
    `/api/ocorrencias/tipos/${id}`,
    dados,
  )
  return resposta.data.dados
}

export async function inativarTipoOcorrencia(id: string): Promise<void> {
  await http.post(`/api/ocorrencias/tipos/${id}/inativar`)
}

export async function reativarTipoOcorrencia(id: string): Promise<void> {
  await http.post(`/api/ocorrencias/tipos/${id}/reativar`)
}

export async function listarOcorrencias(params?: {
  busca?: string
  situacao?: string
  tipo_id?: string
  pagina?: number
}): Promise<RespostaPaginada<Ocorrencia>> {
  const resposta = await http.get<RespostaPaginada<Ocorrencia>>('/api/ocorrencias', {
    params: {
      busca: params?.busca,
      situacao: params?.situacao,
      tipo_id: params?.tipo_id,
      page: params?.pagina,
    },
  })
  return resposta.data
}

export async function mostrarOcorrencia(id: string): Promise<Ocorrencia> {
  const resposta = await http.get<RespostaItem<Ocorrencia>>(`/api/ocorrencias/${id}`)
  return resposta.data.dados
}

export async function criarOcorrencia(dados: Record<string, unknown>): Promise<Ocorrencia> {
  const resposta = await http.post<RespostaItem<Ocorrencia>>('/api/ocorrencias', dados)
  return resposta.data.dados
}

export async function comentarOcorrencia(
  id: string,
  dados: { conteudo: string; visibilidade?: string },
): Promise<ComentarioOcorrencia> {
  const resposta = await http.post<RespostaItem<ComentarioOcorrencia>>(
    `/api/ocorrencias/${id}/comentar`,
    dados,
  )
  return resposta.data.dados
}

export async function alterarSituacaoOcorrencia(
  id: string,
  dados: { situacao: string; motivo?: string },
): Promise<Ocorrencia> {
  const resposta = await http.post<RespostaItem<Ocorrencia>>(
    `/api/ocorrencias/${id}/alterar-situacao`,
    dados,
  )
  return resposta.data.dados
}
