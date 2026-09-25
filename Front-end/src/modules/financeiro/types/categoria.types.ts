export interface CategoriaFinanceira {
  id_publico: string
  codigo: string
  nome: string
  direcao: 'ENTRADA' | 'SAIDA'
  ativo: boolean
  inativado_em: string | null
}
