export type UserRole = 'master' | 'sindico' | 'subsindico' | 'conselho' | 'morador';

export interface User {
  id: string;
  nome: string;
  email: string;
  roles: UserRole[];
  apartamentoId?: string;
  ativo: boolean;
}

export interface Bloco {
  id: string;
  nome: string;
  andares: number;
  aptsPorAndar: number;
}

export interface Apartamento {
  id: string;
  blocoId: string;
  numero: string;
  andar: number;
  status: 'ocupado' | 'vago';
  moradorId?: string;
  observacoes?: string;
}

export interface Morador {
  id: string;
  nome: string;
  cpf: string;
  rg?: string;
  dataNascimento?: string;
  telefone: string;
  email: string;
  apartamentoId: string;
  tipo: 'proprietario' | 'inquilino';
  status: 'ativo' | 'inativo';
  observacoes?: string;
}

export interface Veiculo {
  id: string;
  moradorId: string;
  placa: string;
  modelo: string;
  cor: string;
  vaga?: string;
}

export interface ContaBancaria {
  id: string;
  nome: string;
  banco: string;
  agencia: string;
  conta: string;
  tipo: 'corrente' | 'poupanca' | 'caixa';
  saldo: number;
  ultimaAtualizacao: string;
}

export interface CategoriaFinanceira {
  id: string;
  nome: string;
  tipo: 'entrada' | 'saida';
}

export interface Movimentacao {
  id: string;
  tipo: 'entrada' | 'saida';
  data: string;
  descricao: string;
  valor: number;
  categoriaId: string;
  contaId: string;
  apartamentoId?: string;
  observacoes?: string;
}

export interface CotaCondominial {
  id: string;
  mes: number;
  ano: number;
  apartamentoId: string;
  valor: number;
  status: 'pago' | 'pendente' | 'atrasado';
  dataPagamento?: string;
}

export interface FechamentoMensal {
  id: string;
  mes: number;
  ano: number;
  totalEntradas: number;
  totalSaidas: number;
  saldo: number;
  fechado: boolean;
  dataFechamento?: string;
}

export interface Reserva {
  id: string;
  ambiente: string;
  data: string;
  horarioInicio: string;
  horarioFim: string;
  apartamentoId: string;
  moradorId: string;
  status: 'confirmada' | 'pendente' | 'cancelada';
  observacoes?: string;
}

export interface Comunicado {
  id: string;
  titulo: string;
  conteudo: string;
  data: string;
  segmento: string[];
  anexo?: string;
  canalEmail: boolean;
  canalWhatsapp: boolean;
}

export interface Ocorrencia {
  id: string;
  numero: number;
  data: string;
  apartamentoId: string;
  tipo: 'reclamacao' | 'sugestao' | 'manutencao';
  assunto: string;
  descricao: string;
  status: 'aberta' | 'em_andamento' | 'finalizada';
  anexo?: string;
  respostas: OcorrenciaResposta[];
}

export interface OcorrenciaResposta {
  id: string;
  data: string;
  autor: string;
  texto: string;
  novoStatus?: Ocorrencia['status'];
}
