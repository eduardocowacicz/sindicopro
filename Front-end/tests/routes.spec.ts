import { describe, expect, it } from 'vitest'

import { router } from '@/router'

const plannedPaths = [
  '/entrar',
  '/recuperar-senha',
  '/redefinir-senha/:token',
  '/admin',
  '/admin/blocos',
  '/admin/blocos/:id',
  '/admin/apartamentos',
  '/admin/apartamentos/:id',
  '/admin/pessoas',
  '/admin/pessoas/:id',
  '/admin/veiculos',
  '/admin/financeiro/banco',
  '/admin/financeiro/pagamentos',
  '/admin/financeiro/recebimentos',
  '/admin/financeiro/tipos-pagamento',
  '/admin/financeiro/relatorios',
  '/admin/fechamentos/mensais',
  '/admin/fechamentos/mensais/:id',
  '/admin/reservas',
  '/admin/reservas/:id',
  '/admin/reservas/ambientes',
  '/admin/reservas/bloqueios',
  '/admin/comunicados',
  '/admin/comunicados/novo',
  '/admin/comunicados/:id',
  '/admin/ocorrencias',
  '/admin/ocorrencias/:id',
  '/admin/ocorrencias/tipos',
  '/admin/usuarios',
  '/admin/usuarios/:id',
  '/admin/perfis',
  '/admin/perfis/:id/permissoes',
  '/admin/auditoria/acessos',
  '/admin/auditoria/acoes',
  '/app',
  '/app/unidade',
  '/app/recebimentos',
  '/app/recebimentos/:id',
  '/app/fechamentos',
  '/app/fechamentos/:id',
  '/app/reservas',
  '/app/reservas/:id',
  '/app/comunicados',
  '/app/comunicados/:id',
  '/app/ocorrencias',
  '/app/ocorrencias/nova',
  '/app/ocorrencias/:id',
]

describe('rotas planejadas', () => {
  it('registra todas as 47 telas definidas no guia', () => {
    const registeredPaths = new Set(router.getRoutes().map((route) => route.path))

    expect(plannedPaths).toHaveLength(47)
    expect(plannedPaths.filter((path) => !registeredPaths.has(path))).toEqual([])
  })

  it('prioriza páginas estáticas sobre detalhes dinâmicos', () => {
    expect(router.resolve('/admin/reservas/ambientes').name).toBe('admin-reservas-ambientes')
    expect(router.resolve('/admin/comunicados/novo').name).toBe('admin-comunicado-novo')
    expect(router.resolve('/admin/ocorrencias/tipos').name).toBe('admin-ocorrencias-tipos')
    expect(router.resolve('/app/ocorrencias/nova').name).toBe('app-ocorrencia-nova')
  })
})
