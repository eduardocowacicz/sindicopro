export interface TipoCobranca {
  id_publico: string
  codigo: string
  nome: string
  papel_pagador: 'MORADOR' | 'PROPRIETARIO'
  dia_vencimento_padrao: number
  descricao: string | null
  ativo: boolean
}
