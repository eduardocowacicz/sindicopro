import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'

import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import type { Sessao } from '@/modules/auth/types/sessao.types'

describe('useAutenticacaoStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('inicia em estado anônimo e sem acesso administrativo', () => {
    const store = useAutenticacaoStore()

    expect(store.autenticado).toBe(false)
    expect(store.temAcessoAdmin).toBe(false)
    expect(store.permissoes).toEqual([])
    expect(store.sessao).toBeNull()
  })

  it('identifica corretamente o acesso de um usuário síndico', () => {
    const store = useAutenticacaoStore()

    const sessaoSindico: Sessao = {
      usuario: {
        id_publico: 'usr-sindico-1',
        email: 'sindico@pro.com',
        deve_alterar_senha: false,
      },
      pessoa: {
        id_publico: 'pes-1',
        nome_completo: 'Síndico Teste',
      },
      perfis: [{ codigo: 'SINDICO', nome: 'Síndico' }],
      permissoes: ['*'],
      acesso_integral: true,
      unidades: [],
    }

    store.$patch((state) => {
      state.estado = 'autenticada'
      state.sessao = sessaoSindico
    })

    expect(store.autenticado).toBe(true)
    expect(store.temAcessoAdmin).toBe(true)
    expect(store.temPermissao('cadastros.blocos.consultar')).toBe(true)
    expect(store.temPermissao('financeiro.movimentacoes.editar')).toBe(true)
  })

  it('restringe morador comum sem permissões administrativas', () => {
    const store = useAutenticacaoStore()

    const sessaoMorador: Sessao = {
      usuario: {
        id_publico: 'usr-morador-1',
        email: 'morador@pro.com',
        deve_alterar_senha: false,
      },
      pessoa: {
        id_publico: 'pes-2',
        nome_completo: 'Mariana Ramos',
      },
      perfis: [{ codigo: 'MORADOR', nome: 'Morador' }],
      permissoes: [],
      acesso_integral: false,
      unidades: [
        {
          id_publico: 'und-102',
          codigo: '102',
          bloco: 'Bloco A',
          tipo_vinculo: 'LOCATARIO',
        },
      ],
    }

    store.$patch((state) => {
      state.estado = 'autenticada'
      state.sessao = sessaoMorador
    })

    expect(store.autenticado).toBe(true)
    expect(store.temAcessoAdmin).toBe(false)
    expect(store.temAcessoMorador).toBe(true)
    expect(store.temPermissao('cadastros.blocos.editar')).toBe(false)
    expect(store.temPermissao('financeiro.contas.cadastrar')).toBe(false)
  })

  it('suporta permissões cumulativas para morador promovido temporariamente a síndico', () => {
    const store = useAutenticacaoStore()

    const sessaoCumulativa: Sessao = {
      usuario: {
        id_publico: 'usr-morador-sindico',
        email: 'morador.eleito@pro.com',
        deve_alterar_senha: false,
      },
      pessoa: {
        id_publico: 'pes-3',
        nome_completo: 'Carlos Morador e Síndico',
      },
      perfis: [
        { codigo: 'MORADOR', nome: 'Morador' },
        { codigo: 'SINDICO', nome: 'Síndico' },
      ],
      permissoes: ['cadastros.blocos.consultar', 'reservas.agenda.validar'],
      acesso_integral: false,
      unidades: [
        {
          id_publico: 'und-101',
          codigo: '101',
          bloco: 'Bloco A',
          tipo_vinculo: 'PROPRIETARIO',
        },
      ],
    }

    store.$patch((state) => {
      state.estado = 'autenticada'
      state.sessao = sessaoCumulativa
    })

    expect(store.autenticado).toBe(true)
    expect(store.temAcessoAdmin).toBe(true)
    expect(store.temAcessoMorador).toBe(true)
    expect(store.temPermissao('reservas.agenda.validar')).toBe(true)
  })
})
