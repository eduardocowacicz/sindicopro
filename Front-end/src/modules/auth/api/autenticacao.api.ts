import { http } from '@/api/http'
import type { Sessao } from '@/modules/auth/types/sessao.types'

interface RespostaSessao {
  dados: Sessao
}

export async function prepararCsrf(): Promise<void> {
  await http.get('/sanctum/csrf-cookie')
}

export async function entrar(email: string, senha: string): Promise<void> {
  await prepararCsrf()
  await http.post('/login', { email, senha })
}

export async function obterSessao(): Promise<Sessao> {
  const resposta = await http.get<RespostaSessao>('/api/sessao')

  return resposta.data.dados
}

export async function sair(): Promise<void> {
  await http.post('/logout')
}
