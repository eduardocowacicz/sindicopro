export interface ComunicadoDestinatarioItem {
  id: number
  email_registrado: string
  situacao_entrega: string
  enviado_em: string | null
  entregue_em: string | null
  lido_em: string | null
  pessoa?: {
    id_publico: string
    nome_completo: string
  }
  unidade?: {
    id_publico: string
    codigo: string
    bloco?: { nome: string }
  }
}

export interface Comunicado {
  id_publico: string
  titulo: string
  conteudo: string
  tipo_publico: 'TODOS' | 'BLOCOS'
  situacao: 'RASCUNHO' | 'PUBLICADO' | 'CANCELADO'
  publicado_em: string | null
  destinatarios_count?: number
  lidos_count?: number
  publicado_por?: {
    email: string
    pessoa?: { nome_completo: string }
  }
  blocos?: Array<{
    id_publico: string
    nome: string
  }>
  destinatarios?: ComunicadoDestinatarioItem[]
  lido_em?: string | null
}
