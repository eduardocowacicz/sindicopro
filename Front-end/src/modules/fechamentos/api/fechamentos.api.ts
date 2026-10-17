import { http } from '@/api/http'
import type { RespostaItem } from '@/lib/api-types'
import type { FechamentoMensalDetalhe, FechamentoMensalItem } from '../types/fechamento.types'

export async function listarFechamentos(params: {
  ano: number
  situacao?: string
}): Promise<FechamentoMensalItem[]> {
  const resposta = await http.get<RespostaItem<FechamentoMensalItem[]>>(
    '/api/fechamentos/mensais',
    {
      params: {
        ano: params.ano,
        situacao: params.situacao && params.situacao !== 'TODOS' ? params.situacao : undefined,
      },
    },
  )
  return resposta.data.dados
}

export async function obterFechamento(identificador: string): Promise<FechamentoMensalDetalhe> {
  const resposta = await http.get<RespostaItem<FechamentoMensalDetalhe>>(
    `/api/fechamentos/mensais/${identificador}`,
  )
  return resposta.data.dados
}

export async function fecharPeriodo(dados: {
  periodo: string
  tipo_cobranca_id?: string
  despesas?: Array<{ movimentacao_id: number; valor_cobrar: number }>
  data_vencimento?: string
  percentual_fundo_reserva?: number
}): Promise<FechamentoMensalDetalhe> {
  const resposta = await http.post<RespostaItem<FechamentoMensalDetalhe>>(
    '/api/fechamentos/mensais',
    dados,
  )
  return resposta.data.dados
}

export async function reabrirFechamento(idPublico: string, motivo: string): Promise<void> {
  await http.post(`/api/fechamentos/mensais/${idPublico}/reabrir`, {
    motivo,
  })
}
