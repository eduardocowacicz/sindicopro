import axios from 'axios'

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
