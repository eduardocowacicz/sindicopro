import type { RouteRecordRaw } from 'vue-router'

export const fechamentosRoutes: RouteRecordRaw[] = [
  {
    path: 'fechamentos/mensais',
    name: 'admin-fechamentos-mensais',
    component: () => import('./pages/mensais-lista/FechamentosMensaisListaPage.vue'),
    meta: { permissao: 'fechamentos.mensais.consultar' },
  },
  {
    path: 'fechamentos/mensais/:id',
    name: 'admin-fechamento-mensal-detalhe',
    component: () => import('./pages/mensal-detalhe/FechamentoMensalDetalhePage.vue'),
    meta: { permissao: 'fechamentos.mensais.consultar' },
  },
]
