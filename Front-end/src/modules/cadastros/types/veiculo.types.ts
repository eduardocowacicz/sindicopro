export interface Veiculo {
  id_publico: string
  unidade_id: string
  unidade?: { id_publico: string; codigo: string }
  pessoa_id: string | null
  pessoa?: { id_publico: string; nome_completo: string } | null
  placa: string
  modelo: string
  cor: string | null
  vaga: string | null
  ativo: boolean
  inativado_em: string | null
}
