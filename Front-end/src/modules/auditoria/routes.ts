import type { RouteRecordRaw } from 'vue-router'

export const auditoriaRoutes: RouteRecordRaw[] = [
  {
    path: 'auditoria/acessos',
    name: 'admin-auditoria-acessos',
    component: () => import('./pages/acessos/LogsAcessoPage.vue'),
    meta: { permissao: 'auditoria.acessos.consultar' },
  },
  {
    path: 'auditoria/acoes',
    name: 'admin-auditoria-acoes',
    component: () => import('./pages/acoes/LogsAcoesPage.vue'),
    meta: { permissao: 'auditoria.acoes.consultar' },
  },
]
