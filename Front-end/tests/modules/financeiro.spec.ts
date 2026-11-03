import { describe, expect, it } from 'vitest'

describe('módulo financeiro e cobranças', () => {
  it('formata valores monetários em padrão Real brasileiro', () => {
    function formatarMoeda(valor: number): string {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      }).format(valor)
    }

    expect(formatarMoeda(1250.5)).toContain('1.250,50')
    expect(formatarMoeda(0)).toContain('0,00')
  })

  it('calcula o valor destinado ao fundo de reserva e capital de giro', () => {
    const totalArrecadado = 18000.0
    const percentualFundo = 0.1 // 10%

    const valorFundoReserva = totalArrecadado * percentualFundo
    const valorCapitalDeGiro = totalArrecadado - valorFundoReserva

    expect(valorFundoReserva).toBe(1800.0)
    expect(valorCapitalDeGiro).toBe(16200.0)
  })

  it('determina corretamente a classe visual para movimentações de entrada e saída', () => {
    function classeDirecao(direcao: 'ENTRADA' | 'SAIDA'): string {
      return direcao === 'ENTRADA'
        ? 'text-emerald-600 font-semibold'
        : 'text-rose-600 font-semibold'
    }

    expect(classeDirecao('ENTRADA')).toContain('emerald')
    expect(classeDirecao('SAIDA')).toContain('rose')
  })

  it('mapeia situações de cobranças para suas respectivas variantes visuais', () => {
    function badgeSituacao(situacao: 'ABERTO' | 'PAGO' | 'CANCELADO'): string {
      switch (situacao) {
        case 'PAGO':
          return 'default'
        case 'ABERTO':
          return 'secondary'
        case 'CANCELADO':
          return 'outline'
      }
    }

    expect(badgeSituacao('PAGO')).toBe('default')
    expect(badgeSituacao('ABERTO')).toBe('secondary')
    expect(badgeSituacao('CANCELADO')).toBe('outline')
  })
})
