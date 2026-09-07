import type { RouteRecordRaw } from 'vue-router'

export const financeiroRoutes: RouteRecordRaw[] = [
  {
    path: 'financeiro/banco',
    name: 'admin-financeiro-banco',
    component: () => import('./pages/banco/BancoPage.vue'),
    meta: { permissao: 'financeiro.banco.consultar' },
  },
  {
    path: 'financeiro/pagamentos',
    name: 'admin-financeiro-pagamentos',
    component: () => import('./pages/pagamentos/PagamentosPage.vue'),
    meta: { permissao: 'financeiro.pagamentos.consultar' },
  },
  {
    path: 'financeiro/recebimentos',
    name: 'admin-financeiro-recebimentos',
    component: () => import('./pages/recebimentos/RecebimentosPage.vue'),
    meta: { permissao: 'financeiro.recebimentos.consultar' },
  },
  {
    path: 'financeiro/tipos-pagamento',
    name: 'admin-financeiro-tipos-pagamento',
    component: () => import('./pages/tipos-pagamento/TiposPagamentoPage.vue'),
    meta: { permissao: 'financeiro.tipos_pagamento.consultar' },
  },
  {
    path: 'financeiro/relatorios',
    name: 'admin-financeiro-relatorios',
    component: () => import('./pages/relatorios/RelatoriosFinanceirosPage.vue'),
    meta: { permissao: 'financeiro.relatorios.consultar' },
  },
]
