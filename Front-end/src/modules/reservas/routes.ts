import type { RouteRecordRaw } from 'vue-router'

export const reservasRoutes: RouteRecordRaw[] = [
  {
    path: 'reservas',
    name: 'admin-reservas',
    component: () => import('./pages/agenda/ReservasPage.vue'),
    meta: { permissao: 'reservas.agenda.consultar' },
  },
  {
    path: 'reservas/:id',
    name: 'admin-reserva-detalhe',
    component: () => import('./pages/reserva-detalhe/ReservaDetalhePage.vue'),
    meta: { permissao: 'reservas.agenda.consultar' },
  },
  {
    path: 'reservas/ambientes',
    name: 'admin-reservas-ambientes',
    component: () => import('./pages/ambientes/AmbientesPage.vue'),
    meta: { permissao: 'reservas.ambientes.consultar' },
  },
  {
    path: 'reservas/bloqueios',
    name: 'admin-reservas-bloqueios',
    component: () => import('./pages/bloqueios/BloqueiosPage.vue'),
    meta: { permissao: 'reservas.bloqueios.consultar' },
  },
]
