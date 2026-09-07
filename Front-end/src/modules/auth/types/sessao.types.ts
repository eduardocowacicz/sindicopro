export interface PerfilSessao {
  codigo: string
  nome: string
}

export interface UnidadeSessao {
  id_publico: string
  codigo: string
  bloco: string
  tipo_vinculo: string
}

export interface Sessao {
  usuario: {
    id_publico: string
    email: string
    deve_alterar_senha: boolean
  }
  pessoa: {
    id_publico: string
    nome_completo: string
  }
  perfis: PerfilSessao[]
  permissoes: string[]
  acesso_integral: boolean
  unidades: UnidadeSessao[]
}
