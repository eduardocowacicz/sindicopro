import type { Reserva } from '@/modules/reservas/types/reserva.types'
import type { Comunicado } from '@/modules/comunicados/types/comunicado.types'
import type { Ocorrencia } from '@/modules/ocorrencias/types/ocorrencia.types'
import type { Cobranca } from '@/modules/financeiro/types/cobranca.types'

export interface UnidadeMorador {
  id_publico: string
  codigo: string
  andar: number | null
  metragem: number | string | null
  fracao_ideal: number | string | null
  situacao_ocupacao: string
  observacoes: string | null
  bloco?: {
    id_publico: string
    nome: string
    apelido: string | null
  }
  moradores?: Array<{
    tipo_vinculo: string
    responsavel_financeiro: boolean
    contato_principal: boolean
    pessoa: {
      nome_completo: string
      email: string
      telefone: string | null
    }
  }>
  veiculos?: Array<{
    placa: string
    modelo: string
    cor: string
    vaga: string | null
  }>
}

export interface DashboardMorador {
  unidades: UnidadeMorador[]
  cobrancas_pendentes: Cobranca[]
  reservas_futuras: Reserva[]
  comunicados_recentes: Comunicado[]
  ocorrencias_em_andamento: Ocorrencia[]
}
