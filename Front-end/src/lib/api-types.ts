export interface MetaPaginacao {
  pagina_atual: number
  por_pagina: number
  total: number
  ultima_pagina: number
}

export interface RespostaPaginada<T> {
  dados: T[]
  meta: MetaPaginacao
}

export interface RespostaItem<T> {
  dados: T
}

export interface RegistroAuditoria {
  acao: string
  motivo: string | null
  dados_anteriores: string | null
  dados_posteriores: string | null
  ocorrido_em: string
  usuario_nome: string | null
}
