import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { Veiculo } from '@/modules/cadastros/types/veiculo.types'

export async function listarVeiculos(params: {
  busca?: string
  pagina: number
}): Promise<RespostaPaginada<Veiculo>> {
  const resposta = await http.get<RespostaPaginada<Veiculo>>('/api/veiculos', {
    params: { busca: params.busca, page: params.pagina },
  })
  return resposta.data
}

export async function criarVeiculo(dados: Record<string, unknown>): Promise<Veiculo> {
  const resposta = await http.post<RespostaItem<Veiculo>>('/api/veiculos', dados)
  return resposta.data.dados
}

export async function atualizarVeiculo(
  id: string,
  dados: Record<string, unknown>,
): Promise<Veiculo> {
  const resposta = await http.put<RespostaItem<Veiculo>>(`/api/veiculos/${id}`, dados)
  return resposta.data.dados
}

export async function inativarVeiculo(id: string): Promise<void> {
  await http.post(`/api/veiculos/${id}/inativar`)
}

export async function reativarVeiculo(id: string): Promise<void> {
  await http.post(`/api/veiculos/${id}/reativar`)
}

export async function historicoVeiculo(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/veiculos/${id}/historico`,
  )
  return resposta.data.dados
}
