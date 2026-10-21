import { useQuery } from '@tanstack/vue-query'

import { obterDashboardFinanceiro } from '../api/dashboard.api'

export const dashboardFinanceiroQueryKey = ['inicio', 'dashboard', 'financeiro'] as const

export function useDashboardFinanceiroQuery() {
  return useQuery({
    queryKey: dashboardFinanceiroQueryKey,
    queryFn: ({ signal }) => obterDashboardFinanceiro(signal),
    retry: 1,
    staleTime: 60_000,
  })
}
