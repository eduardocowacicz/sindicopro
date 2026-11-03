import { http } from '@/api/http'
import type { RespostaItem } from '@/lib/api-types'

export interface MesFinanceiro {
  competencia: string
  entradas: number
  saidas: number
}

export interface DashboardFinanceiro {
  periodo_inicio: string
  periodo_fim: string
  meses: MesFinanceiro[]
}

export async function obterDashboardFinanceiro(signal?: AbortSignal): Promise<DashboardFinanceiro> {
  const resposta = await http.get<RespostaItem<DashboardFinanceiro>>('/api/dashboard/financeiro', {
    signal,
  })

  return resposta.data.dados
}
