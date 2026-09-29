export interface Bloco {
  id_publico: string
  codigo: string
  nome: string
  quantidade_andares: number | null
  observacoes: string | null
  ativo: boolean
  inativado_em: string | null
}
