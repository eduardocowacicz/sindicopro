export interface PerfilUsuario {
  id_publico: string
  codigo: string
  nome: string
  descricao?: string | null
  sistema?: boolean
}

export interface VinculoUnidadeUsuario {
  tipo_vinculo: string
  unidade?: {
    codigo: string
    bloco?: {
      nome: string
    }
  }
}

export interface Usuario {
  id_publico: string
  email: string
  ativo: boolean
  bloqueado_em: string | null
  motivo_bloqueio: string | null
  deve_alterar_senha: boolean
  ultimo_acesso_em: string | null
  pessoa?: {
    id_publico: string
    nome_completo: string
    email: string | null
    tipo?: string
    vinculos?: VinculoUnidadeUsuario[]
  }
  perfis_vigentes?: PerfilUsuario[]
}
