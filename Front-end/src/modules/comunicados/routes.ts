import type { RouteRecordRaw } from 'vue-router'

export const comunicadosRoutes: RouteRecordRaw[] = [
  {
    path: 'comunicados',
    name: 'admin-comunicados',
    component: () => import('./pages/lista/ComunicadosListaPage.vue'),
    meta: { permissao: 'comunicados.consultar' },
  },
  {
    path: 'comunicados/novo',
    name: 'admin-comunicado-novo',
    component: () => import('./pages/novo/ComunicadoNovoPage.vue'),
    meta: { permissao: 'comunicados.cadastrar' },
  },
  {
    path: 'comunicados/:id',
    name: 'admin-comunicado-detalhe',
    component: () => import('./pages/detalhe/ComunicadoDetalhePage.vue'),
    meta: { permissao: 'comunicados.consultar' },
  },
]
