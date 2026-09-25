export interface Perfil {
  id_publico: string
  codigo: string
  nome: string
  descricao: string | null
  sistema: boolean
  ativo: boolean
}

export interface PermissaoPerfil {
  id_publico: string
  codigo: string
  modulo: string
  acao: string
  nome: string
  sensivel: boolean
  concedida: boolean
}
