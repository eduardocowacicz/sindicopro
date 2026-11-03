import { describe, expect, it } from 'vitest'

import {
  adminNavigationGroups,
  filtrarNavegacao,
  navegacaoAtiva,
} from '@/components/layout/navigation'

describe('filtrarNavegacao', () => {
  it('mantém somente itens públicos e permitidos', () => {
    const result = filtrarNavegacao(adminNavigationGroups, new Set(['reservas.agenda.consultar']))

    expect(result.map((group) => group.label)).toEqual(['Painel', 'Reservas'])
    expect(result.flatMap((group) => group.items.map((item) => item.label))).toEqual([
      'Visão geral',
      'Agenda',
    ])
  })

  it('mantém o item de lista ativo em uma rota de detalhe relacionada', () => {
    const item = adminNavigationGroups
      .flatMap((group) => group.items)
      .find((candidate) => candidate.routeName === 'admin-blocos')

    expect(item).toBeDefined()
    expect(navegacaoAtiva(item!, 'admin-bloco-detalhe')).toBe(true)
  })

  it('mantém as áreas do financeiro no menu', () => {
    const financeiro = adminNavigationGroups.find((group) => group.label === 'Financeiro')

    expect(financeiro?.items.map((item) => item.label)).toEqual([
      'Contas bancárias',
      'Plano de contas',
      'Tipos de cobrança',
      'Lançamentos',
      'Boletos e cobranças',
    ])
  })

  it('possui ícones configurados para todos os grupos administrativos', () => {
    for (const group of adminNavigationGroups) {
      expect(group.icon).toBeDefined()
    }
  })

  it('identifica corretamente o grupo correspondente a uma rota ativa', () => {
    function encontrarGrupo(nomeRota: string) {
      return (
        adminNavigationGroups
          .filter((g) => g.label !== 'Painel')
          .find((g) => g.items.some((item) => navegacaoAtiva(item, nomeRota)))?.label ?? null
      )
    }

    expect(encontrarGrupo('admin-financeiro-lancamentos')).toBe('Financeiro')
    expect(encontrarGrupo('admin-blocos')).toBe('Condomínio')
    expect(encontrarGrupo('admin-bloco-detalhe')).toBe('Condomínio')
    expect(encontrarGrupo('admin-fechamentos-mensais')).toBe('Fechamentos')
    expect(encontrarGrupo('inicio')).toBeNull()
  })
})
