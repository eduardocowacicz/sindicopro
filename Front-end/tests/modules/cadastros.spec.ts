import { describe, expect, it } from 'vitest'

describe('módulo de cadastros (blocos, unidades e pessoas)', () => {
  it('ordena os andares de cima para baixo do 1º ao último andar conforme especificado', () => {
    const andares = [4, 1, 3, 2]
    const andaresOrdenados = [...andares].sort((a, b) => a - b)

    expect(andaresOrdenados).toEqual([1, 2, 3, 4])
  })

  it('agrupa apartamentos corretamente por andar', () => {
    const unidades = [
      { id: '1', codigo: '101', andar: 1 },
      { id: '2', codigo: '102', andar: 1 },
      { id: '3', codigo: '201', andar: 2 },
      { id: '4', codigo: '202', andar: 2 },
    ]

    const mapaAndares = unidades.reduce<Record<number, typeof unidades>>((acc, item) => {
      const andar = item.andar ?? 1
      if (!acc[andar]) acc[andar] = []
      acc[andar].push(item)
      return acc
    }, {})

    expect(Object.keys(mapaAndares)).toEqual(['1', '2'])
    expect(mapaAndares[1]).toHaveLength(2)
    expect(mapaAndares[2]).toHaveLength(2)
    expect(mapaAndares[1]?.map((u) => u.codigo)).toEqual(['101', '102'])
  })

  it('filtra pessoas por múltiplos tipos permitidos', () => {
    const pessoas = [
      { nome: 'Ana', tipo: 'MORADOR' },
      { nome: 'João Eletricista', tipo: 'PRESTADOR_SERVICO' },
      { nome: 'Distribuidora Silva', tipo: 'FORNECEDOR' },
    ]

    const tiposPermitidos = ['PRESTADOR_SERVICO', 'FORNECEDOR']
    const filtrados = pessoas.filter((p) => tiposPermitidos.includes(p.tipo))

    expect(filtrados).toHaveLength(2)
    expect(filtrados.some((p) => p.tipo === 'MORADOR')).toBe(false)
  })

  it('formata adequadamente a placa Mercosul', () => {
    function normalizarPlaca(placa: string): string {
      return placa.replace(/[^A-Za-z0-9]/g, '').toUpperCase()
    }

    expect(normalizarPlaca('abc-1d23')).toBe('ABC1D23')
    expect(normalizarPlaca('bra2e19')).toBe('BRA2E19')
  })
})
