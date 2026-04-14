import type { User, Bloco, Apartamento, Morador, Veiculo, ContaBancaria, CategoriaFinanceira, Movimentacao, CotaCondominial, Reserva, Comunicado, Ocorrencia } from '@/types';

export const mockUsers: User[] = [
  { id: 'u1', nome: 'Carlos Admin', email: 'master@condogest.com', roles: ['master'], ativo: true },
  { id: 'u2', nome: 'Roberto Silva', email: 'sindico@condogest.com', roles: ['sindico'], apartamentoId: 'a1', ativo: true },
  { id: 'u3', nome: 'Ana Oliveira', email: 'subsindico@condogest.com', roles: ['subsindico'], apartamentoId: 'a2', ativo: true },
  { id: 'u4', nome: 'Marcos Pereira', email: 'conselho@condogest.com', roles: ['conselho'], apartamentoId: 'a3', ativo: true },
  { id: 'u5', nome: 'Maria Santos', email: 'morador@condogest.com', roles: ['morador'], apartamentoId: 'a4', ativo: true },
  { id: 'u6', nome: 'João Costa', email: 'joao@email.com', roles: ['morador'], apartamentoId: 'a5', ativo: true },
  { id: 'u7', nome: 'Fernanda Lima', email: 'fernanda@email.com', roles: ['morador'], apartamentoId: 'a6', ativo: true },
  { id: 'u8', nome: 'Pedro Souza', email: 'pedro@email.com', roles: ['morador'], apartamentoId: 'a7', ativo: false },
];

export const mockBlocos: Bloco[] = [
  { id: 'b1', nome: 'Bloco A', andares: 5, aptsPorAndar: 2 },
  { id: 'b2', nome: 'Bloco B', andares: 5, aptsPorAndar: 2 },
];

export const mockApartamentos: Apartamento[] = [
  { id: 'a1', blocoId: 'b1', numero: '101', andar: 1, status: 'ocupado', moradorId: 'm1' },
  { id: 'a2', blocoId: 'b1', numero: '102', andar: 1, status: 'ocupado', moradorId: 'm2' },
  { id: 'a3', blocoId: 'b1', numero: '201', andar: 2, status: 'ocupado', moradorId: 'm3' },
  { id: 'a4', blocoId: 'b1', numero: '202', andar: 2, status: 'ocupado', moradorId: 'm4' },
  { id: 'a5', blocoId: 'b1', numero: '301', andar: 3, status: 'ocupado', moradorId: 'm5' },
  { id: 'a6', blocoId: 'b1', numero: '302', andar: 3, status: 'ocupado', moradorId: 'm6' },
  { id: 'a7', blocoId: 'b1', numero: '401', andar: 4, status: 'ocupado', moradorId: 'm7' },
  { id: 'a8', blocoId: 'b1', numero: '402', andar: 4, status: 'vago' },
  { id: 'a9', blocoId: 'b1', numero: '501', andar: 5, status: 'ocupado', moradorId: 'm8' },
  { id: 'a10', blocoId: 'b1', numero: '502', andar: 5, status: 'vago' },
  { id: 'a11', blocoId: 'b2', numero: '101', andar: 1, status: 'ocupado', moradorId: 'm9' },
  { id: 'a12', blocoId: 'b2', numero: '102', andar: 1, status: 'ocupado', moradorId: 'm10' },
  { id: 'a13', blocoId: 'b2', numero: '201', andar: 2, status: 'ocupado', moradorId: 'm11' },
  { id: 'a14', blocoId: 'b2', numero: '202', andar: 2, status: 'ocupado', moradorId: 'm12' },
  { id: 'a15', blocoId: 'b2', numero: '301', andar: 3, status: 'ocupado', moradorId: 'm13' },
  { id: 'a16', blocoId: 'b2', numero: '302', andar: 3, status: 'vago' },
  { id: 'a17', blocoId: 'b2', numero: '401', andar: 4, status: 'ocupado', moradorId: 'm14' },
  { id: 'a18', blocoId: 'b2', numero: '402', andar: 4, status: 'ocupado', moradorId: 'm15' },
  { id: 'a19', blocoId: 'b2', numero: '501', andar: 5, status: 'vago' },
  { id: 'a20', blocoId: 'b2', numero: '502', andar: 5, status: 'vago' },
];

export const mockMoradores: Morador[] = [
  { id: 'm1', nome: 'Roberto Silva', cpf: '12345678901', telefone: '11999887766', email: 'roberto@email.com', apartamentoId: 'a1', tipo: 'proprietario', status: 'ativo' },
  { id: 'm2', nome: 'Ana Oliveira', cpf: '23456789012', telefone: '11999776655', email: 'ana@email.com', apartamentoId: 'a2', tipo: 'proprietario', status: 'ativo' },
  { id: 'm3', nome: 'Marcos Pereira', cpf: '34567890123', telefone: '11999665544', email: 'marcos@email.com', apartamentoId: 'a3', tipo: 'inquilino', status: 'ativo' },
  { id: 'm4', nome: 'Maria Santos', cpf: '45678901234', telefone: '11999554433', email: 'maria@email.com', apartamentoId: 'a4', tipo: 'proprietario', status: 'ativo' },
  { id: 'm5', nome: 'João Costa', cpf: '56789012345', telefone: '11999443322', email: 'joao@email.com', apartamentoId: 'a5', tipo: 'proprietario', status: 'ativo' },
  { id: 'm6', nome: 'Fernanda Lima', cpf: '67890123456', telefone: '11999332211', email: 'fernanda@email.com', apartamentoId: 'a6', tipo: 'inquilino', status: 'ativo' },
  { id: 'm7', nome: 'Pedro Souza', cpf: '78901234567', telefone: '11999221100', email: 'pedro@email.com', apartamentoId: 'a7', tipo: 'proprietario', status: 'inativo' },
  { id: 'm8', nome: 'Juliana Ferreira', cpf: '89012345678', telefone: '11999110099', email: 'juliana@email.com', apartamentoId: 'a9', tipo: 'proprietario', status: 'ativo' },
  { id: 'm9', nome: 'Lucas Almeida', cpf: '90123456789', telefone: '11998899776', email: 'lucas@email.com', apartamentoId: 'a11', tipo: 'proprietario', status: 'ativo' },
  { id: 'm10', nome: 'Camila Rocha', cpf: '01234567890', telefone: '11998788665', email: 'camila@email.com', apartamentoId: 'a12', tipo: 'inquilino', status: 'ativo' },
  { id: 'm11', nome: 'Rafael Mendes', cpf: '11234567890', telefone: '11998677554', email: 'rafael@email.com', apartamentoId: 'a13', tipo: 'proprietario', status: 'ativo' },
  { id: 'm12', nome: 'Patrícia Nunes', cpf: '22345678901', telefone: '11998566443', email: 'patricia@email.com', apartamentoId: 'a14', tipo: 'proprietario', status: 'ativo' },
  { id: 'm13', nome: 'André Barbosa', cpf: '33456789012', telefone: '11998455332', email: 'andre@email.com', apartamentoId: 'a15', tipo: 'inquilino', status: 'ativo' },
  { id: 'm14', nome: 'Beatriz Cardoso', cpf: '44567890123', telefone: '11998344221', email: 'beatriz@email.com', apartamentoId: 'a17', tipo: 'proprietario', status: 'ativo' },
  { id: 'm15', nome: 'Thiago Ribeiro', cpf: '55678901234', telefone: '11998233110', email: 'thiago@email.com', apartamentoId: 'a18', tipo: 'proprietario', status: 'ativo' },
];

export const mockVeiculos: Veiculo[] = [
  { id: 'v1', moradorId: 'm1', placa: 'ABC1D23', modelo: 'Honda Civic', cor: 'Prata', vaga: 'A-01' },
  { id: 'v2', moradorId: 'm2', placa: 'DEF4G56', modelo: 'Toyota Corolla', cor: 'Branco', vaga: 'A-02' },
  { id: 'v3', moradorId: 'm4', placa: 'GHI7J89', modelo: 'VW Golf', cor: 'Preto', vaga: 'A-04' },
  { id: 'v4', moradorId: 'm5', placa: 'JKL0M12', modelo: 'Fiat Argo', cor: 'Vermelho', vaga: 'A-05' },
  { id: 'v5', moradorId: 'm9', placa: 'MNO3P45', modelo: 'Hyundai HB20', cor: 'Azul', vaga: 'B-01' },
  { id: 'v6', moradorId: 'm11', placa: 'PQR6S78', modelo: 'Chevrolet Onix', cor: 'Cinza', vaga: 'B-03' },
];

export const mockContasBancarias: ContaBancaria[] = [
  { id: 'cb1', nome: 'Conta Principal', banco: 'Banco do Brasil', agencia: '1234-5', conta: '56789-0', tipo: 'corrente', saldo: 45320.50, ultimaAtualizacao: '2026-03-18' },
  { id: 'cb2', nome: 'Reserva', banco: 'Caixa Econômica', agencia: '0987-6', conta: '12345-6', tipo: 'poupanca', saldo: 28750.00, ultimaAtualizacao: '2026-03-15' },
  { id: 'cb3', nome: 'Caixa Físico', banco: '-', agencia: '-', conta: '-', tipo: 'caixa', saldo: 1230.00, ultimaAtualizacao: '2026-03-10' },
];

export const mockCategorias: CategoriaFinanceira[] = [
  { id: 'cat1', nome: 'Cotas Condominiais', tipo: 'entrada' },
  { id: 'cat2', nome: 'Multas', tipo: 'entrada' },
  { id: 'cat3', nome: 'Aluguel Salão', tipo: 'entrada' },
  { id: 'cat4', nome: 'Manutenção', tipo: 'saida' },
  { id: 'cat5', nome: 'Limpeza', tipo: 'saida' },
  { id: 'cat6', nome: 'Energia Elétrica', tipo: 'saida' },
  { id: 'cat7', nome: 'Água', tipo: 'saida' },
  { id: 'cat8', nome: 'Segurança', tipo: 'saida' },
  { id: 'cat9', nome: 'Jardinagem', tipo: 'saida' },
  { id: 'cat10', nome: 'Administração', tipo: 'saida' },
];

const months = ['2025-10', '2025-11', '2025-12', '2026-01', '2026-02', '2026-03'];
let movId = 0;
const genMov = (mes: string, tipo: 'entrada' | 'saida', desc: string, valor: number, catId: string, contaId: string): Movimentacao => ({
  id: `mov${++movId}`, tipo, data: `${mes}-${String(Math.floor(Math.random() * 28) + 1).padStart(2, '0')}`, descricao: desc, valor, categoriaId: catId, contaId,
});

export const mockMovimentacoes: Movimentacao[] = months.flatMap(m => [
  genMov(m, 'entrada', 'Cotas condominiais do mês', 12000 + Math.random() * 2000, 'cat1', 'cb1'),
  genMov(m, 'entrada', 'Multa por atraso', 150 + Math.random() * 100, 'cat2', 'cb1'),
  genMov(m, 'entrada', 'Aluguel salão de festas', 500, 'cat3', 'cb1'),
  genMov(m, 'saida', 'Manutenção elevador', 800 + Math.random() * 400, 'cat4', 'cb1'),
  genMov(m, 'saida', 'Serviço de limpeza', 2500, 'cat5', 'cb1'),
  genMov(m, 'saida', 'Conta de energia', 1800 + Math.random() * 500, 'cat6', 'cb1'),
  genMov(m, 'saida', 'Conta de água', 1200 + Math.random() * 300, 'cat7', 'cb1'),
  genMov(m, 'saida', 'Vigilância', 3200, 'cat8', 'cb1'),
  genMov(m, 'saida', 'Jardinagem', 600, 'cat9', 'cb1'),
  genMov(m, 'saida', 'Taxa administradora', 1500, 'cat10', 'cb1'),
]);

export const mockCotas: CotaCondominial[] = mockApartamentos
  .filter(a => a.status === 'ocupado')
  .map((a, i) => ({
    id: `cota${i + 1}`, mes: 3, ano: 2026, apartamentoId: a.id, valor: 850,
    status: i < 10 ? 'pago' as const : i < 13 ? 'pendente' as const : 'atrasado' as const,
    dataPagamento: i < 10 ? '2026-03-10' : undefined,
  }));

export const mockReservas: Reserva[] = [
  { id: 'r1', ambiente: 'Salão de Festas 1', data: '2026-03-22', horarioInicio: '14:00', horarioFim: '22:00', apartamentoId: 'a1', moradorId: 'm1', status: 'confirmada' },
  { id: 'r2', ambiente: 'Salão de Festas 2', data: '2026-03-25', horarioInicio: '10:00', horarioFim: '18:00', apartamentoId: 'a4', moradorId: 'm4', status: 'confirmada' },
  { id: 'r3', ambiente: 'Salão de Festas 1', data: '2026-03-28', horarioInicio: '18:00', horarioFim: '23:00', apartamentoId: 'a11', moradorId: 'm9', status: 'pendente' },
  { id: 'r4', ambiente: 'Salão de Festas 2', data: '2026-03-15', horarioInicio: '09:00', horarioFim: '17:00', apartamentoId: 'a6', moradorId: 'm6', status: 'cancelada' },
];

export const mockComunicados: Comunicado[] = [
  { id: 'c1', titulo: 'Manutenção programada do elevador', conteudo: 'Informamos que haverá manutenção preventiva nos elevadores do Bloco A nos dias 25 e 26 de março. Pedimos desculpas pelo inconveniente.', data: '2026-03-18', segmento: ['Bloco A'], canalEmail: true, canalWhatsapp: true },
  { id: 'c2', titulo: 'Assembleia Geral Ordinária', conteudo: 'Convocamos todos os moradores para a Assembleia Geral Ordinária que será realizada no dia 05/04/2026, às 19h, no Salão de Festas 1.', data: '2026-03-15', segmento: ['Todos'], canalEmail: true, canalWhatsapp: false },
  { id: 'c3', titulo: 'Novas regras para uso da piscina', conteudo: 'A partir de abril, o horário de funcionamento da piscina será das 8h às 20h. É obrigatório o uso de touca.', data: '2026-03-10', segmento: ['Todos'], canalEmail: true, canalWhatsapp: true },
  { id: 'c4', titulo: 'Obra no estacionamento do Bloco B', conteudo: 'Iniciaremos obras de reparo no piso do estacionamento do Bloco B a partir de 01/04. Algumas vagas ficarão temporariamente indisponíveis.', data: '2026-03-08', segmento: ['Bloco B'], canalEmail: true, canalWhatsapp: false },
  { id: 'c5', titulo: 'Campanha de reciclagem', conteudo: 'Participe da nossa campanha de reciclagem! Novos coletores foram instalados na área comum. Separe seus resíduos corretamente.', data: '2026-03-01', segmento: ['Todos'], canalEmail: true, canalWhatsapp: true },
];

export const mockOcorrencias: Ocorrencia[] = [
  { id: 'o1', numero: 1001, data: '2026-03-17', apartamentoId: 'a4', tipo: 'reclamacao', assunto: 'Barulho excessivo', descricao: 'Apartamento vizinho com som alto após as 22h.', status: 'aberta', respostas: [] },
  { id: 'o2', numero: 1002, data: '2026-03-15', apartamentoId: 'a1', tipo: 'manutencao', assunto: 'Vazamento no teto', descricao: 'Há um vazamento no teto do banheiro, possivelmente vindo do apartamento de cima.', status: 'em_andamento', respostas: [{ id: 'or1', data: '2026-03-16', autor: 'Síndico', texto: 'Equipe de manutenção acionada para verificação.', novoStatus: 'em_andamento' }] },
  { id: 'o3', numero: 1003, data: '2026-03-12', apartamentoId: 'a11', tipo: 'sugestao', assunto: 'Instalação de bicicletário', descricao: 'Sugiro a instalação de um bicicletário coberto na área do estacionamento.', status: 'aberta', respostas: [] },
  { id: 'o4', numero: 1004, data: '2026-03-10', apartamentoId: 'a5', tipo: 'reclamacao', assunto: 'Iluminação da garagem', descricao: 'As lâmpadas da garagem do subsolo estão queimadas há semanas.', status: 'finalizada', respostas: [{ id: 'or2', data: '2026-03-11', autor: 'Síndico', texto: 'Lâmpadas substituídas. Problema resolvido.', novoStatus: 'finalizada' }] },
  { id: 'o5', numero: 1005, data: '2026-03-08', apartamentoId: 'a13', tipo: 'manutencao', assunto: 'Portão da garagem travando', descricao: 'O portão da garagem está travando com frequência ao abrir.', status: 'em_andamento', respostas: [{ id: 'or3', data: '2026-03-09', autor: 'Subsíndico', texto: 'Empresa de manutenção agendada para dia 20/03.', novoStatus: 'em_andamento' }] },
  { id: 'o6', numero: 1006, data: '2026-03-05', apartamentoId: 'a2', tipo: 'reclamacao', assunto: 'Lixo na área comum', descricao: 'Resíduos sendo deixados fora dos horários de coleta na área de lixo.', status: 'finalizada', respostas: [{ id: 'or4', data: '2026-03-06', autor: 'Síndico', texto: 'Comunicado enviado reforçando horários. Câmera instalada no local.', novoStatus: 'finalizada' }] },
];
