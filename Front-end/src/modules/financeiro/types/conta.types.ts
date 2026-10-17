export interface ContaFinanceira {
  id_publico: string
  nome: string
  tipo_conta: 'CONTA_CORRENTE' | 'POUPANCA' | 'CAIXA'
  codigo_banco: string | null
  nome_banco: string | null
  ultimos4_conta: string | null
  saldo_inicial: string
  saldo_fundo_reserva_inicial?: string
  data_saldo_inicial: string
  saldo_atual?: number
  saldo_capital_giro?: number
  saldo_fundo_reserva?: number
  ativo: boolean
  inativado_em: string | null
}
