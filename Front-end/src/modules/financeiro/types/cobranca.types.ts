export interface ItemCobrancaInfo {
  tipo_item: string
  descricao: string
  valor: number
}

export interface CobrancaItem {
  id_publico: string
  codigo_referencia: string
  unidade_codigo?: string
  bloco_nome?: string
  morador_nome?: string
  morador_email?: string | null
  morador_telefone?: string | null
  papel_pagador?: string
  descricao: string
  titulo?: string
  data_emissao: string
  data_vencimento: string
  data_competencia?: string
  valor_original: number
  valor_total?: number
  valor_cota_ordinaria?: number
  valor_fundo_reserva?: number
  situacao: 'ABERTO' | 'PAGO' | 'VENCIDO' | 'CANCELADO' | 'PARCIALMENTE_PAGO' | 'RENEGOCIADO'
  pago_em?: string | null
  codigo_barras?: string
  pix_copia_e_cola?: string
  itens?: ItemCobrancaInfo[]
  unidade?: {
    codigo: string
    bloco?: {
      nome: string
    }
  }
  fechamento?: {
    id_publico: string
    periodo: string | null
  } | null
}

export type Cobranca = CobrancaItem

export interface CobrancaKPIs {
  total_geral: number
  total_aberto: number
  total_pago: number
  total_vencido: number
  qtd_total: number
  qtd_aberto: number
  qtd_pago: number
  qtd_vencido: number
  taxa_adimplencia: number
}

export interface RespostaCobrancas {
  dados: CobrancaItem[]
  kpis: CobrancaKPIs
  meta: {
    pagina_atual: number
    por_pagina: number
    total: number
    ultima_pagina: number
  }
}

export interface FiltrosCobranca {
  situacao?: string
  mes?: number
  ano?: number
  busca?: string
  pagina?: number
  por_pagina?: number
}

export interface RegistrarPagamentoPayload {
  data_pagamento?: string
  valor_pago?: number
  conta_financeira_id?: string
  forma_pagamento?: 'PIX' | 'BOLETO' | 'TRANSFERENCIA' | 'DINHEIRO' | 'OUTRO'
  observacoes?: string
}
