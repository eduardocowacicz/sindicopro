export interface FaixaHorario {
  id_publico: string
  nome: string
  inicia_em: string
  termina_em: string
  ativo: boolean
}

export interface Ambiente {
  id_publico: string
  codigo: string
  nome: string
  descricao: string | null
  horas_antecedencia_minima_morador: number
  horas_limite_cancelamento_morador: number
  bloquear_unidades_inadimplentes: boolean
  aprovacao_automatica: boolean
  ativo: boolean
  faixas_horario?: FaixaHorario[]
}

export interface BloqueioAmbiente {
  id_publico: string
  ambiente_id?: string
  ambiente?: Ambiente
  faixa_horario?: FaixaHorario
  inicio_vigencia: string
  fim_vigencia: string
  motivo: string
  cancelado_em: string | null
  bloqueado_por_usuario?: {
    email: string
    pessoa?: { nome_completo: string }
  }
}

export interface Reserva {
  id_publico: string
  data_reserva: string
  inicia_em: string
  termina_em: string
  valor_taxa: number | string
  situacao: 'CONFIRMADO' | 'CANCELADO' | 'CONCLUIDO'
  criado_pela_administracao: boolean
  validado_em: string | null
  cancelado_em: string | null
  observacoes: string | null
  ambiente?: Ambiente
  faixa_horario?: FaixaHorario
  unidade?: {
    id_publico: string
    codigo: string
    bloco?: { nome: string }
  }
  solicitado_por?: {
    id_publico: string
    email: string
    pessoa?: { nome_completo: string }
  }
}
