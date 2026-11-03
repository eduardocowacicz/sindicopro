import type { Unidade } from '@/modules/cadastros/types/unidade.types'

export interface Bloco {
  id_publico: string
  codigo: string
  nome: string
  apelido: string | null
  andares: number
  apartamentos_por_andar: number
  quantidade_andares?: number | null
  observacoes: string | null
  ativo: boolean
  situacao?: 'ATIVO' | 'INATIVO'
  inativado_em: string | null
  unidades?: Unidade[]
}
