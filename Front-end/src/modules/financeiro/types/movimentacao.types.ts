export interface MovimentacaoFinanceira {
  id_publico: string
  conta_financeira_id: string
  conta?: { id_publico: string; nome: string }
  categoria_financeira_id: string
  categoria?: { id_publico: string; nome: string }
  tipo_cobranca_id: string | null
  tipoCobranca?: { id_publico: string; nome: string } | null
  pessoa_id?: string | null
  pessoa?: { id_publico: string; nome_completo: string; tipo?: string } | null
  direcao: 'ENTRADA' | 'SAIDA'
  descricao: string
  numero_documento: string | null
  nome_contraparte: string | null
  data_competencia: string
  data_efetiva: string
  data_vencimento: string | null
  valor: string
  situacao: 'RASCUNHO' | 'CONTABILIZADO' | 'CANCELADO' | 'ESTORNADO'
  utiliza_fundo_reserva?: boolean
  origem_recurso?: 'CAPITAL_GIRO' | 'FUNDO_RESERVA'
  observacoes: string | null
}
