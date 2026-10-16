export type CampoTipo =
  'texto' | 'numero' | 'selecao' | 'data' | 'checkbox' | 'texto-longo' | 'senha' | 'cpf'

export interface OpcaoSelecao {
  valor: string | number
  rotulo: string
}

export interface CampoFormulario {
  chave: string
  rotulo: string
  tipo: CampoTipo
  obrigatorio?: boolean
  opcoes?: OpcaoSelecao[]
  somenteCriacao?: boolean
  somenteEdicao?: boolean
  desabilitado?: boolean
  placeholder?: string
  ajuda?: string
}

export interface ColunaTabela<T> {
  chave: string
  rotulo: string
  render?: (item: T) => string
  classe?: string
}
