import axios from 'axios'
import { defineStore } from 'pinia'

import * as autenticacaoApi from '@/modules/auth/api/autenticacao.api'
import type { Sessao } from '@/modules/auth/types/sessao.types'

type EstadoAutenticacao = 'desconhecida' | 'carregando' | 'autenticada' | 'anonima'

export const useAutenticacaoStore = defineStore('autenticacao', {
  state: () => ({
    sessao: null as Sessao | null,
    estado: 'desconhecida' as EstadoAutenticacao,
  }),

  getters: {
    autenticado: (state): boolean => state.estado === 'autenticada' && state.sessao !== null,
    acessoIntegral: (state): boolean => state.sessao?.acesso_integral ?? false,
    permissoes: (state): string[] => state.sessao?.permissoes ?? [],
  },

  actions: {
    async carregarSessao(): Promise<void> {
      if (this.estado !== 'desconhecida') return

      this.estado = 'carregando'

      try {
        this.sessao = await autenticacaoApi.obterSessao()
        this.estado = 'autenticada'
      } catch (erro: unknown) {
        if (!axios.isAxiosError(erro) || ![401, 419].includes(erro.response?.status ?? 0)) {
          console.warn('Não foi possível consultar a sessão da API.', erro)
        }

        this.limparSessao()
      }
    },

    async entrar(email: string, senha: string): Promise<void> {
      await autenticacaoApi.entrar(email, senha)
      this.sessao = await autenticacaoApi.obterSessao()
      this.estado = 'autenticada'
    },

    async sair(): Promise<void> {
      try {
        await autenticacaoApi.sair()
      } finally {
        this.limparSessao()
      }
    },

    limparSessao(): void {
      this.sessao = null
      this.estado = 'anonima'
    },

    temPermissao(codigo: string): boolean {
      return this.acessoIntegral || this.permissoes.includes(codigo)
    },
  },
})
