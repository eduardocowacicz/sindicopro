export interface TipoOcorrencia {
  id_publico: string
  codigo: string
  nome: string
  descricao: string | null
  ativo: boolean
}

export interface ComentarioOcorrencia {
  id_publico: string
  conteudo: string
  visibilidade:
    | 'SOMENTE_ADMINISTRACAO'
    | 'UNIDADE_SOLICITANTE'
    | 'UNIDADES_RELACIONADAS'
    | 'TODOS_PARTICIPANTES'
  criado_em: string
  autor?: {
    email: string
    pessoa?: { nome_completo: string }
  }
}

export interface Ocorrencia {
  id_publico: string
  numero_protocolo: number
  motivo: string
  descricao: string
  interna: boolean
  situacao: 'ABERTO' | 'EM_ANDAMENTO' | 'RESOLVIDO' | 'FECHADO' | 'CANCELADO'
  aberto_em: string
  resolvido_em: string | null
  fechado_em: string | null
  tipo?: TipoOcorrencia
  registrado_por?: {
    id_publico: string
    email: string
    pessoa?: { nome_completo: string }
  }
  unidades?: Array<{
    tipo_relacao: string
    unidade?: {
      id_publico: string
      codigo: string
      bloco?: { nome: string }
    }
  }>
  comentarios?: ComentarioOcorrencia[]
}
