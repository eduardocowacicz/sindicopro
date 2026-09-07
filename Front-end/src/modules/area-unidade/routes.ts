import type { RouteRecordRaw } from 'vue-router'

export const areaUnidadeRoutes: RouteRecordRaw[] = [
  { path: '', name: 'app-inicio', component: () => import('./pages/inicio/InicioPage.vue') },
  {
    path: 'unidade',
    name: 'app-unidade',
    component: () => import('./pages/unidade/MinhaUnidadePage.vue'),
  },
  {
    path: 'recebimentos',
    name: 'app-recebimentos',
    component: () => import('./pages/recebimentos/MeusRecebimentosPage.vue'),
  },
  {
    path: 'recebimentos/:id',
    name: 'app-recebimento-detalhe',
    component: () => import('./pages/recebimento-detalhe/MeuRecebimentoDetalhePage.vue'),
  },
  {
    path: 'fechamentos',
    name: 'app-fechamentos',
    component: () => import('./pages/fechamentos/FechamentosPublicadosPage.vue'),
  },
  {
    path: 'fechamentos/:id',
    name: 'app-fechamento-detalhe',
    component: () => import('./pages/fechamento-detalhe/FechamentoPublicadoDetalhePage.vue'),
  },
  {
    path: 'reservas',
    name: 'app-reservas',
    component: () => import('./pages/reservas/MinhasReservasPage.vue'),
  },
  {
    path: 'reservas/:id',
    name: 'app-reserva-detalhe',
    component: () => import('./pages/reserva-detalhe/MinhaReservaDetalhePage.vue'),
  },
  {
    path: 'comunicados',
    name: 'app-comunicados',
    component: () => import('./pages/comunicados/MeusComunicadosPage.vue'),
  },
  {
    path: 'comunicados/:id',
    name: 'app-comunicado-detalhe',
    component: () => import('./pages/comunicado-detalhe/MeuComunicadoDetalhePage.vue'),
  },
  {
    path: 'ocorrencias',
    name: 'app-ocorrencias',
    component: () => import('./pages/ocorrencias/MinhasOcorrenciasPage.vue'),
  },
  {
    path: 'ocorrencias/nova',
    name: 'app-ocorrencia-nova',
    component: () => import('./pages/ocorrencia-nova/MinhaOcorrenciaNovaPage.vue'),
  },
  {
    path: 'ocorrencias/:id',
    name: 'app-ocorrencia-detalhe',
    component: () => import('./pages/ocorrencia-detalhe/MinhaOcorrenciaDetalhePage.vue'),
  },
]
