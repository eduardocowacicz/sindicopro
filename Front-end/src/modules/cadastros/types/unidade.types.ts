import type { Bloco } from '@/modules/cadastros/types/bloco.types'

export interface Unidade {
  id_publico: string
  bloco_id: string
  bloco?: Bloco
  codigo: string
  numero_andar: number | null
  situacao_ocupacao: 'OCUPADO' | 'VAGO'
  observacoes: string | null
  ativo: boolean
  inativado_em: string | null
}
