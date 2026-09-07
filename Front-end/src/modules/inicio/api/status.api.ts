import { http } from '@/api/http'

export interface ApiStatus {
  application: string
  status: 'ok'
}

interface ApiStatusResponse {
  data: ApiStatus
}

export async function obterStatus(signal?: AbortSignal): Promise<ApiStatus> {
  const response = await http.get<ApiStatusResponse>('/api/status', { signal })

  return response.data.data
}
