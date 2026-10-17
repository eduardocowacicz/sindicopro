export interface Pessoa {
  id_publico: string
  nome_completo: string
  tipo: 'MORADOR' | 'PRESTADOR_SERVICO' | 'FORNECEDOR'
  email: string | null
  telefone: string | null
  observacoes: string | null
  ativo: boolean
  inativado_em: string | null
}
