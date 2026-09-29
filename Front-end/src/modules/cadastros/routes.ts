import type { RouteRecordRaw } from 'vue-router'

export const cadastrosRoutes: RouteRecordRaw[] = [
  {
    path: 'blocos',
    name: 'admin-blocos',
    component: () => import('./pages/blocos-lista/BlocosListaPage.vue'),
    meta: { permissao: 'cadastros.blocos.consultar' },
  },
  {
    path: 'blocos/:id',
    name: 'admin-bloco-detalhe',
    component: () => import('./pages/bloco-detalhe/BlocoDetalhePage.vue'),
    meta: { permissao: 'cadastros.blocos.consultar' },
  },
  {
    path: 'apartamentos',
    name: 'admin-apartamentos',
    component: () => import('./pages/apartamentos-lista/ApartamentosListaPage.vue'),
    meta: { permissao: 'cadastros.unidades.consultar' },
  },
  {
    path: 'apartamentos/:id',
    name: 'admin-apartamento-detalhe',
    component: () => import('./pages/apartamento-detalhe/ApartamentoDetalhePage.vue'),
    meta: { permissao: 'cadastros.unidades.consultar' },
  },
  {
    path: 'pessoas',
    name: 'admin-pessoas',
    component: () => import('./pages/pessoas-lista/PessoasListaPage.vue'),
    meta: { permissao: 'cadastros.pessoas.consultar' },
  },
  {
    path: 'pessoas/:id',
    name: 'admin-pessoa-detalhe',
    component: () => import('./pages/pessoa-detalhe/PessoaDetalhePage.vue'),
    meta: { permissao: 'cadastros.pessoas.consultar' },
  },
  {
    path: 'veiculos',
    name: 'admin-veiculos',
    component: () => import('./pages/veiculos-lista/VeiculosListaPage.vue'),
    meta: { permissao: 'cadastros.veiculos.consultar' },
  },
  {
    path: 'vinculos',
    name: 'admin-vinculos',
    component: () => import('./pages/vinculos-lista/VinculosListaPage.vue'),
    meta: { permissao: 'cadastros.vinculos.consultar' },
  },
]
