import { http } from '@/api/http'
import type { RegistroAuditoria, RespostaItem, RespostaPaginada } from '@/lib/api-types'
import type { MovimentacaoFinanceira } from '@/modules/financeiro/types/movimentacao.types'

export async function listarMovimentacoes(params: {
  pagina: number
}): Promise<RespostaPaginada<MovimentacaoFinanceira>> {
  const resposta = await http.get<RespostaPaginada<MovimentacaoFinanceira>>(
    '/api/financeiro/movimentacoes',
    {
      params: { page: params.pagina },
    },
  )
  return resposta.data
}

export async function criarMovimentacao(
  dados: Record<string, unknown>,
): Promise<MovimentacaoFinanceira> {
  const resposta = await http.post<RespostaItem<MovimentacaoFinanceira>>(
    '/api/financeiro/movimentacoes',
    dados,
  )
  return resposta.data.dados
}

export async function cancelarMovimentacao(id: string): Promise<void> {
  await http.post(`/api/financeiro/movimentacoes/${id}/cancelar`)
}

export async function historicoMovimentacao(id: string): Promise<RegistroAuditoria[]> {
  const resposta = await http.get<RespostaItem<RegistroAuditoria[]>>(
    `/api/financeiro/movimentacoes/${id}/historico`,
  )
  return resposta.data.dados
}
