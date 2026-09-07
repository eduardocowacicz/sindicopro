import { useQuery } from '@tanstack/vue-query'

import { obterStatus } from '../api/status.api'

export const statusQueryKey = ['sistema', 'status'] as const

export function useStatusQuery() {
  return useQuery({
    queryKey: statusQueryKey,
    queryFn: ({ signal }) => obterStatus(signal),
    retry: 1,
    refetchInterval: 30_000,
  })
}
