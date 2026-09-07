import { createRouter, createWebHistory } from 'vue-router'

import { pinia } from '@/app/pinia'
import { acessoRoutes } from '@/modules/acesso/routes'
import { areaUnidadeRoutes } from '@/modules/area-unidade/routes'
import { auditoriaRoutes } from '@/modules/auditoria/routes'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import { cadastrosRoutes } from '@/modules/cadastros/routes'
import { comunicadosRoutes } from '@/modules/comunicados/routes'
import { fechamentosRoutes } from '@/modules/fechamentos/routes'
import { financeiroRoutes } from '@/modules/financeiro/routes'
import { ocorrenciasRoutes } from '@/modules/ocorrencias/routes'
import { reservasRoutes } from '@/modules/reservas/routes'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/entrar',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { somenteAnonimo: true },
      children: [
        {
          path: '',
          name: 'entrar',
          component: () => import('@/modules/auth/pages/login/LoginPage.vue'),
        },
      ],
    },
    {
      path: '/recuperar-senha',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { somenteAnonimo: true },
      children: [
        {
          path: '',
          name: 'recuperar-senha',
          component: () => import('@/modules/auth/pages/recuperar-senha/RecuperarSenhaPage.vue'),
        },
      ],
    },
    {
      path: '/redefinir-senha/:token',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { somenteAnonimo: true },
      children: [
        {
          path: '',
          name: 'redefinir-senha',
          component: () => import('@/modules/auth/pages/redefinir-senha/RedefinirSenhaPage.vue'),
        },
      ],
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requerAutenticacao: true },
      children: [
        {
          path: '',
          name: 'inicio',
          component: () => import('@/modules/inicio/pages/painel/PainelPage.vue'),
        },
        ...cadastrosRoutes,
        ...financeiroRoutes,
        ...fechamentosRoutes,
        ...reservasRoutes,
        ...comunicadosRoutes,
        ...ocorrenciasRoutes,
        ...acessoRoutes,
        ...auditoriaRoutes,
      ],
    },
    {
      path: '/app',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requerAutenticacao: true },
      children: areaUnidadeRoutes,
    },
    {
      path: '/',
      redirect: '/admin',
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/admin',
    },
  ],
})

router.beforeEach(async (to) => {
  const autenticacao = useAutenticacaoStore(pinia)
  await autenticacao.carregarSessao()

  if (to.meta.requerAutenticacao && !autenticacao.autenticado) {
    return {
      name: 'entrar',
      query: to.fullPath !== '/admin' ? { redirecionar: to.fullPath } : undefined,
    }
  }

  if (to.meta.somenteAnonimo && autenticacao.autenticado) {
    return { name: 'inicio' }
  }

  const permissao = typeof to.meta.permissao === 'string' ? to.meta.permissao : undefined

  if (permissao && !autenticacao.temPermissao(permissao)) {
    return { name: to.path.startsWith('/app') ? 'app-inicio' : 'inicio' }
  }

  return true
})
