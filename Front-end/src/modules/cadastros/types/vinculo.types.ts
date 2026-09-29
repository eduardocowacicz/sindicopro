export interface Vinculo {
  id_publico: string
  unidade_id: string
  unidade?: { id_publico: string; codigo: string }
  pessoa_id: string
  pessoa?: { id_publico: string; nome_completo: string }
  tipo_vinculo: 'PROPRIETARIO' | 'LOCATARIO' | 'MORADOR' | 'DEPENDENTE'
  papel_cobranca: 'PROPRIETARIO' | 'MORADOR' | null
  contato_principal: boolean
  responsavel_financeiro: boolean
  inicio_vigencia: string
  fim_vigencia: string | null
  observacoes: string | null
}
