export interface Pessoa {
  id_publico: string
  nome_completo: string
  email: string | null
  telefone: string | null
  observacoes: string | null
  ativo: boolean
  inativado_em: string | null
}
