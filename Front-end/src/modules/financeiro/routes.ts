import type { RouteRecordRaw } from 'vue-router'

export const financeiroRoutes: RouteRecordRaw[] = [
  {
    path: 'financeiro/contas',
    name: 'admin-financeiro-contas',
    component: () => import('./pages/contas/ContasListaPage.vue'),
    meta: { permissao: 'financeiro.contas.consultar' },
  },
  {
    path: 'financeiro/categorias',
    name: 'admin-financeiro-categorias',
    component: () => import('./pages/categorias/CategoriasListaPage.vue'),
    meta: { permissao: 'financeiro.categorias.consultar' },
  },
  {
    path: 'financeiro/tipos-cobranca',
    name: 'admin-financeiro-tipos-cobranca',
    component: () => import('./pages/tipos-cobranca/TiposCobrancaListaPage.vue'),
    meta: { permissao: 'financeiro.tipos_cobranca.consultar' },
  },
  {
    path: 'financeiro/lancamentos',
    name: 'admin-financeiro-lancamentos',
    component: () => import('./pages/lancamentos/LancamentosListaPage.vue'),
    meta: { permissao: 'financeiro.movimentacoes.consultar' },
  },
]
