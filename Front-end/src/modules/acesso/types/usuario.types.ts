export interface Usuario {
  id_publico: string
  email: string
  ativo: boolean
  bloqueado_em: string | null
  motivo_bloqueio: string | null
  deve_alterar_senha: boolean
  ultimo_acesso_em: string | null
  pessoa?: { id_publico: string; nome_completo: string; email: string | null }
}
