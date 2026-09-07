import { create } from 'zustand';
import type { User, UserRole, Bloco, Apartamento, Morador, Veiculo, ContaBancaria, CategoriaFinanceira, Movimentacao, CotaCondominial, Reserva, Comunicado, Ocorrencia } from '@/types';
import { mockUsers, mockBlocos, mockApartamentos, mockMoradores, mockVeiculos, mockContasBancarias, mockCategorias, mockMovimentacoes, mockCotas, mockReservas, mockComunicados, mockOcorrencias } from '@/data/mockData';

interface AppState {
  // Auth
  currentUser: User | null;
  login: (role: UserRole) => void;
  logout: () => void;
  isAdmin: () => boolean;

  // Data
  users: User[];
  blocos: Bloco[];
  apartamentos: Apartamento[];
  moradores: Morador[];
  veiculos: Veiculo[];
  contasBancarias: ContaBancaria[];
  categorias: CategoriaFinanceira[];
  movimentacoes: Movimentacao[];
  cotas: CotaCondominial[];
  reservas: Reserva[];
  comunicados: Comunicado[];
  ocorrencias: Ocorrencia[];

  // Actions
  addBloco: (b: Bloco) => void;
  addApartamento: (a: Apartamento) => void;
  addMorador: (m: Morador) => void;
  updateMorador: (id: string, m: Partial<Morador>) => void;
  addVeiculo: (v: Veiculo) => void;
  addContaBancaria: (c: ContaBancaria) => void;
  addMovimentacao: (m: Movimentacao) => void;
  addReserva: (r: Reserva) => void;
  updateReservaStatus: (id: string, status: Reserva['status']) => void;
  addComunicado: (c: Comunicado) => void;
  addOcorrencia: (o: Ocorrencia) => void;
  updateOcorrenciaStatus: (id: string, status: Ocorrencia['status'], resposta?: { autor: string; texto: string }) => void;
  addUser: (u: User) => void;
  updateUser: (id: string, u: Partial<User>) => void;
  addCategoria: (c: CategoriaFinanceira) => void;
}

export const useStore = create<AppState>((set, get) => ({
  currentUser: null,
  login: (role) => {
    const user = mockUsers.find(u => u.roles.includes(role));
    if (user) set({ currentUser: user });
  },
  logout: () => set({ currentUser: null }),
  isAdmin: () => {
    const u = get().currentUser;
    return !!u && u.roles.some(r => ['master', 'sindico', 'subsindico', 'conselho'].includes(r));
  },

  users: [...mockUsers],
  blocos: [...mockBlocos],
  apartamentos: [...mockApartamentos],
  moradores: [...mockMoradores],
  veiculos: [...mockVeiculos],
  contasBancarias: [...mockContasBancarias],
  categorias: [...mockCategorias],
  movimentacoes: [...mockMovimentacoes],
  cotas: [...mockCotas],
  reservas: [...mockReservas],
  comunicados: [...mockComunicados],
  ocorrencias: [...mockOcorrencias],

  addBloco: (b) => set(s => ({ blocos: [...s.blocos, b] })),
  addApartamento: (a) => set(s => ({ apartamentos: [...s.apartamentos, a] })),
  addMorador: (m) => set(s => ({ moradores: [...s.moradores, m] })),
  updateMorador: (id, data) => set(s => ({ moradores: s.moradores.map(m => m.id === id ? { ...m, ...data } : m) })),
  addVeiculo: (v) => set(s => ({ veiculos: [...s.veiculos, v] })),
  addContaBancaria: (c) => set(s => ({ contasBancarias: [...s.contasBancarias, c] })),
  addMovimentacao: (m) => set(s => ({ movimentacoes: [...s.movimentacoes, m] })),
  addReserva: (r) => set(s => ({ reservas: [...s.reservas, r] })),
  updateReservaStatus: (id, status) => set(s => ({ reservas: s.reservas.map(r => r.id === id ? { ...r, status } : r) })),
  addComunicado: (c) => set(s => ({ comunicados: [...s.comunicados, c] })),
  addOcorrencia: (o) => set(s => ({ ocorrencias: [...s.ocorrencias, o] })),
  updateOcorrenciaStatus: (id, status, resposta) => set(s => ({
    ocorrencias: s.ocorrencias.map(o => o.id === id ? {
      ...o, status,
      respostas: resposta ? [...o.respostas, { id: `or${Date.now()}`, data: new Date().toISOString().split('T')[0], ...resposta, novoStatus: status }] : o.respostas,
    } : o),
  })),
  addUser: (u) => set(s => ({ users: [...s.users, u] })),
  updateUser: (id, data) => set(s => ({ users: s.users.map(u => u.id === id ? { ...u, ...data } : u) })),
  addCategoria: (c) => set(s => ({ categorias: [...s.categorias, c] })),
}));
