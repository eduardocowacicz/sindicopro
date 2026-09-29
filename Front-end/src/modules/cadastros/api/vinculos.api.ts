import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Vinculo } from '@/modules/cadastros/types/vinculo.types'

export async function listarVinculos(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Vinculo>> {
  const resposta = await http.get<RespostaPaginada<Vinculo>>('/api/vinculos', {
    params: { page: params.pagina },
  })
  return resposta.data
}

export async function criarVinculo(dados: Record<string, unknown>): Promise<Vinculo> {
  const resposta = await http.post<RespostaItem<Vinculo>>('/api/vinculos', dados)
  return resposta.data.dados
}

export async function encerrarVinculo(id: string): Promise<void> {
  await http.post(`/api/vinculos/${id}/encerrar`)
}

export async function historicoVinculo(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/vinculos/${id}/historico`,
  )
  return resposta.data.dados
}
