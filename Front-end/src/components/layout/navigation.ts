import type { Component } from 'vue'
import { Building2, CalendarDays, CircleGauge, Megaphone, Users, WalletCards } from '@lucide/vue'

export interface NavigationItem {
  label: string
  routeName: string
  icon: Component
  permission?: string
  relatedRouteNames?: string[]
}

export interface NavigationGroup {
  label: string
  items: NavigationItem[]
}

export const adminNavigationGroups: NavigationGroup[] = [
  {
    label: 'Painel',
    items: [{ label: 'Visão geral', routeName: 'inicio', icon: CircleGauge }],
  },
  {
    label: 'Condomínio',
    items: [
      {
        label: 'Blocos',
        routeName: 'admin-blocos',
        icon: Building2,
        permission: 'cadastros.blocos.consultar',
        relatedRouteNames: ['admin-bloco-detalhe'],
      },
      {
        label: 'Apartamentos',
        routeName: 'admin-apartamentos',
        icon: Building2,
        permission: 'cadastros.unidades.consultar',
        relatedRouteNames: ['admin-apartamento-detalhe'],
      },
      {
        label: 'Pessoas e vínculos',
        routeName: 'admin-pessoas',
        icon: Users,
        permission: 'cadastros.pessoas.consultar',
        relatedRouteNames: ['admin-pessoa-detalhe'],
      },
      {
        label: 'Veículos',
        routeName: 'admin-veiculos',
        icon: Building2,
        permission: 'cadastros.veiculos.consultar',
      },
    ],
  },
  {
    label: 'Financeiro',
    items: [
      {
        label: 'Banco',
        routeName: 'admin-financeiro-banco',
        icon: WalletCards,
        permission: 'financeiro.banco.consultar',
      },
      {
        label: 'Pagamentos',
        routeName: 'admin-financeiro-pagamentos',
        icon: WalletCards,
        permission: 'financeiro.pagamentos.consultar',
      },
      {
        label: 'Recebimentos',
        routeName: 'admin-financeiro-recebimentos',
        icon: WalletCards,
        permission: 'financeiro.recebimentos.consultar',
      },
      {
        label: 'Tipos de pagamentos',
        routeName: 'admin-financeiro-tipos-pagamento',
        icon: WalletCards,
        permission: 'financeiro.tipos_pagamento.consultar',
      },
      {
        label: 'Relatórios',
        routeName: 'admin-financeiro-relatorios',
        icon: WalletCards,
        permission: 'financeiro.relatorios.consultar',
      },
    ],
  },
  {
    label: 'Fechamentos',
    items: [
      {
        label: 'Mensais',
        routeName: 'admin-fechamentos-mensais',
        icon: CalendarDays,
        permission: 'fechamentos.mensais.consultar',
        relatedRouteNames: ['admin-fechamento-mensal-detalhe'],
      },
    ],
  },
  {
    label: 'Reservas',
    items: [
      {
        label: 'Agenda',
        routeName: 'admin-reservas',
        icon: CalendarDays,
        permission: 'reservas.agenda.consultar',
        relatedRouteNames: ['admin-reserva-detalhe'],
      },
      {
        label: 'Ambientes e regras',
        routeName: 'admin-reservas-ambientes',
        icon: CalendarDays,
        permission: 'reservas.ambientes.consultar',
      },
      {
        label: 'Bloqueios',
        routeName: 'admin-reservas-bloqueios',
        icon: CalendarDays,
        permission: 'reservas.bloqueios.consultar',
      },
    ],
  },
  {
    label: 'Comunicados',
    items: [
      {
        label: 'Comunicados',
        routeName: 'admin-comunicados',
        icon: Megaphone,
        permission: 'comunicados.consultar',
        relatedRouteNames: ['admin-comunicado-novo', 'admin-comunicado-detalhe'],
      },
    ],
  },
  {
    label: 'Ocorrências',
    items: [
      {
        label: 'Ocorrências',
        routeName: 'admin-ocorrencias',
        icon: Megaphone,
        permission: 'ocorrencias.consultar',
        relatedRouteNames: ['admin-ocorrencia-detalhe'],
      },
      {
        label: 'Tipos de ocorrência',
        routeName: 'admin-ocorrencias-tipos',
        icon: Megaphone,
        permission: 'ocorrencias.tipos.consultar',
      },
    ],
  },
  {
    label: 'Acesso',
    items: [
      {
        label: 'Usuários',
        routeName: 'admin-usuarios',
        icon: Users,
        permission: 'acesso.usuarios.consultar',
        relatedRouteNames: ['admin-usuario-detalhe'],
      },
      {
        label: 'Perfis e permissões',
        routeName: 'admin-perfis',
        icon: Users,
        permission: 'acesso.perfis.consultar',
        relatedRouteNames: ['admin-perfil-permissoes'],
      },
    ],
  },
  {
    label: 'Auditoria',
    items: [
      {
        label: 'Acessos',
        routeName: 'admin-auditoria-acessos',
        icon: CircleGauge,
        permission: 'auditoria.acessos.consultar',
      },
      {
        label: 'Ações',
        routeName: 'admin-auditoria-acoes',
        icon: CircleGauge,
        permission: 'auditoria.acoes.consultar',
      },
    ],
  },
]

export const userNavigationGroups: NavigationGroup[] = [
  {
    label: 'Minha área',
    items: [
      { label: 'Início', routeName: 'app-inicio', icon: CircleGauge },
      { label: 'Minha unidade', routeName: 'app-unidade', icon: Building2 },
      {
        label: 'Meus boletos',
        routeName: 'app-recebimentos',
        icon: WalletCards,
        relatedRouteNames: ['app-recebimento-detalhe'],
      },
      {
        label: 'Fechamentos',
        routeName: 'app-fechamentos',
        icon: WalletCards,
        relatedRouteNames: ['app-fechamento-detalhe'],
      },
      {
        label: 'Reservas',
        routeName: 'app-reservas',
        icon: CalendarDays,
        relatedRouteNames: ['app-reserva-detalhe'],
      },
      {
        label: 'Comunicados',
        routeName: 'app-comunicados',
        icon: Megaphone,
        relatedRouteNames: ['app-comunicado-detalhe'],
      },
      {
        label: 'Ocorrências',
        routeName: 'app-ocorrencias',
        icon: Megaphone,
        relatedRouteNames: ['app-ocorrencia-nova', 'app-ocorrencia-detalhe'],
      },
    ],
  },
]

export function filtrarNavegacao(
  groups: NavigationGroup[],
  permissions?: ReadonlySet<string>,
): NavigationGroup[] {
  if (permissions === undefined) {
    return groups
  }

  return groups
    .map((group) => ({
      ...group,
      items: group.items.filter(
        (item) => item.permission === undefined || permissions.has(item.permission),
      ),
    }))
    .filter((group) => group.items.length > 0)
}

export function navegacaoAtiva(item: NavigationItem, currentRouteName?: string): boolean {
  if (!currentRouteName) return false

  return (
    item.routeName === currentRouteName ||
    item.relatedRouteNames?.includes(currentRouteName) === true
  )
}
