import type { RouteRecordRaw } from 'vue-router'

export const ocorrenciasRoutes: RouteRecordRaw[] = [
  {
    path: 'ocorrencias',
    name: 'admin-ocorrencias',
    component: () => import('./pages/lista/OcorrenciasListaPage.vue'),
    meta: { permissao: 'ocorrencias.consultar' },
  },
  {
    path: 'ocorrencias/tipos',
    name: 'admin-ocorrencias-tipos',
    component: () => import('./pages/tipos/TiposOcorrenciaPage.vue'),
    meta: { permissao: 'ocorrencias.tipos.consultar' },
  },
  {
    path: 'ocorrencias/:id',
    name: 'admin-ocorrencia-detalhe',
    component: () => import('./pages/detalhe/OcorrenciaDetalhePage.vue'),
    meta: { permissao: 'ocorrencias.consultar' },
  },
]
