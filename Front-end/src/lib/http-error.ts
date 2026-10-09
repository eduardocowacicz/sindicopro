import axios from 'axios'

export function errosDeCampos(erro: unknown): Record<string, string> {
  if (!axios.isAxiosError(erro)) return {}

  const dados = erro.response?.data as { errors?: Record<string, string[]> } | undefined
  const erros: Record<string, string> = {}

  Object.entries(dados?.errors ?? {}).forEach(([campo, mensagens]) => {
    if (mensagens[0]) erros[campo] = mensagens[0]
  })

  return erros
}

export function mensagemDeErro(
  erro: unknown,
  mensagemPadrao = 'Não foi possível concluir a operação.',
): string {
  if (axios.isAxiosError(erro)) {
    const dados = erro.response?.data as
      { message?: string; errors?: Record<string, string[]> } | undefined

    if (dados?.errors) {
      const primeiraChave = Object.keys(dados.errors)[0]
      if (primeiraChave) {
        return dados.errors[primeiraChave]?.[0] ?? mensagemPadrao
      }
    }

    if (dados?.message) {
      return dados.message
    }
  }

  return mensagemPadrao
}
