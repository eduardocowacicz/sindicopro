import type { Bloco } from '@/modules/cadastros/types/bloco.types'

export interface MoradorUnidade {
  id: string
  id_publico?: string
  nome: string
  nome_completo?: string
  email: string | null
  telefone: string | null
  tipo_vinculo?: string
}

export interface VeiculoUnidade {
  id_publico: string
  placa: string
  modelo: string
  cor: string | null
  tipo?: string | null
  vaga?: string | null
  observacoes?: string | null
  ativo?: boolean
}

export interface Unidade {
  id_publico: string
  bloco_id: string
  bloco?: Bloco
  codigo: string
  numero?: string
  andar: number | null
  numero_andar: number | null
  situacao_ocupacao: 'OCUPADO' | 'VAGO'
  observacoes: string | null
  ativo: boolean
  inativado_em: string | null
  moradores?: MoradorUnidade[]
  veiculos?: VeiculoUnidade[]
}
