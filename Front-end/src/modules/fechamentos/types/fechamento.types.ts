export interface FechamentoMensalItem {
  mes: number
  ano: number
  nome_mes: string
  periodo: string
  id_publico: string
  periodo_id_publico: string | null
  situacao: 'PUBLICADO' | 'ABERTO' | 'SEM_MOVIMENTO'
  versao: number | null
  total_entradas: number
  total_saidas: number
  saldo_inicial: number
  saldo_final: number
  resultado: number
  total_rateado: number
  quantidade_movimentacoes: number
  quantidade_boletos: number
  fechado_em: string | null
  fechado_por: string | null
  data_competencia?: string
  fundo_reserva_arrecadado?: number
}

export type FechamentoMensal = FechamentoMensalItem

export interface MovimentacaoFechamentoItem {
  id: number
  movimentacao_id?: number
  direcao: 'ENTRADA' | 'SAIDA'
  categoria: string
  tipo_cobranca?: string | null
  descricao: string
  data_efetiva: string
  valor_cobrado: number
  valor_original: number
  saldo_pendente: number
  cobrado_parcial: boolean
}

export interface DespesaDisponivelItem {
  id: number
  id_publico: string
  descricao: string
  categoria: string
  fornecedor: string
  data_efetiva: string
  valor_original: number
  valor_ja_cobrado: number
  valor_pendente: number
  valor_a_cobrar: number
  cobrado_parcial: boolean
  pago_com_fundo_reserva?: boolean
  selecionado: boolean
}

export interface BoletoFechamentoItem {
  id_publico: string
  codigo_referencia: string
  unidade_codigo: string
  bloco_nome: string
  morador_nome: string
  papel_pagador: string
  descricao: string
  data_emissao: string
  data_vencimento: string
  valor: number
  valor_cota_ordinaria?: number
  valor_fundo_reserva?: number
  situacao: string
}

export interface ContaFechamentoItem {
  conta_id: string
  nome: string
  tipo: string
  entradas: number
  saidas: number
  saldo: number
  saldo_capital_giro?: number
  saldo_fundo_reserva?: number
}

export interface CategoriaFechamentoItem {
  categoria_id: string | null
  nome: string
  direcao: 'ENTRADA' | 'SAIDA'
  total: number
  quantidade: number
}

export interface UnidadeRateioItem {
  id_publico: string
  codigo: string
  bloco: string
  responsavel: string
  participante: boolean
  isento: boolean
  motivo_isencao?: string | null
  valor: number
}

export interface ReaberturaFechamentoItem {
  id_publico: string
  motivo: string
  reaberto_em: string
  reaberto_por: string
}

export interface FechamentoMensalDetalhe {
  id_publico: string
  periodo_id_publico: string | null
  periodo: string
  mes: number
  ano: number
  competencia: string
  nome_mes: string
  situacao: 'PUBLICADO' | 'ABERTO'
  versao: number | null
  saldo_inicial: number
  total_entradas: number
  total_saidas: number
  saldo_final: number
  resultado: number
  total_rateado: number
  percentual_fundo_reserva?: number
  valor_fundo_reserva?: number
  fundo_reserva_arrecadado?: number
  data_competencia?: string
  valor_despesas_ordinarias?: number
  publicado_em: string | null
  publicado_por: string | null
  contas: ContaFechamentoItem[]
  categorias: CategoriaFechamentoItem[]
  movimentacoes: MovimentacaoFechamentoItem[]
  despesas_disponiveis: DespesaDisponivelItem[]
  despesas_rateadas?: Array<{
    descricao?: string
    valor?: number
    valor_cobrado?: number
    movimentacao?: {
      descricao?: string
      categoria?: { nome?: string }
      pessoa?: { nome_completo?: string }
      nome_contraparte?: string
    }
  }>
  boletos: BoletoFechamentoItem[]
  rateio: {
    tipo_cobranca: string
    papel_pagador: string
    valor_total: number
    unidades_participantes: number
    unidades: UnidadeRateioItem[]
  }
  reaberturas: ReaberturaFechamentoItem[]
}
