import type { RouteRecordRaw } from 'vue-router'

export const acessoRoutes: RouteRecordRaw[] = [
  {
    path: 'usuarios',
    name: 'admin-usuarios',
    component: () => import('./pages/usuarios-lista/UsuariosListaPage.vue'),
    meta: { permissao: 'acesso.usuarios.consultar' },
  },
  {
    path: 'usuarios/:id',
    name: 'admin-usuario-detalhe',
    component: () => import('./pages/usuario-detalhe/UsuarioDetalhePage.vue'),
    meta: { permissao: 'acesso.usuarios.consultar' },
  },
  {
    path: 'perfis',
    name: 'admin-perfis',
    component: () => import('./pages/perfis-lista/PerfisListaPage.vue'),
    meta: { permissao: 'acesso.perfis.consultar' },
  },
  {
    path: 'perfis/:id/permissoes',
    name: 'admin-perfil-permissoes',
    component: () => import('./pages/perfil-permissoes/PerfilPermissoesPage.vue'),
    meta: { permissao: 'acesso.perfis.consultar' },
  },
]
