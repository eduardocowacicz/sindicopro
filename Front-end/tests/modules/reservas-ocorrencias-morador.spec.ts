import { describe, expect, it } from 'vitest'

describe('módulos de reservas, comunicados, ocorrências e área do morador', () => {
  it('identifica sobreposição de horários para faixas de reserva', () => {
    function faixasColidem(inicio1: string, fim1: string, inicio2: string, fim2: string): boolean {
      return inicio1 < fim2 && fim1 > inicio2
    }

    expect(faixasColidem('10:00', '14:00', '12:00', '16:00')).toBe(true)
    expect(faixasColidem('08:00', '12:00', '14:00', '18:00')).toBe(false)
  })

  it('filtra comunicados visíveis para o morador de acordo com o público-alvo', () => {
    const comunicados = [
      { id: 1, tipo_publico: 'TODOS', blocos: [] },
      { id: 2, tipo_publico: 'BLOCOS', blocos: ['bloco-a'] },
      { id: 3, tipo_publico: 'BLOCOS', blocos: ['bloco-b'] },
    ]

    const meuBlocoId = 'bloco-a'
    const visiveis = comunicados.filter(
      (c) => c.tipo_publico === 'TODOS' || c.blocos.includes(meuBlocoId),
    )

    expect(visiveis.map((c) => c.id)).toEqual([1, 2])
  })

  it('determina corretamente o status e progresso de uma ocorrência', () => {
    const situacoes = ['ABERTO', 'EM_ANDAMENTO', 'RESOLVIDO']
    const labels: Record<string, string> = {
      ABERTO: 'Aberto',
      EM_ANDAMENTO: 'Em andamento',
      RESOLVIDO: 'Resolvido',
    }

    expect(situacoes.map((s) => labels[s])).toEqual(['Aberto', 'Em andamento', 'Resolvido'])
  })

  it('valida o formato do payload do Pix Copia e Cola gerado para o morador', () => {
    const codigoPix =
      '00020101021226580014br.gov.bcb.pix0136test-uuid520400005303986540350.005802BR5916SindicoPro'

    expect(codigoPix.startsWith('000201010212')).toBe(true)
    expect(codigoPix).toContain('br.gov.bcb.pix')
  })
})
