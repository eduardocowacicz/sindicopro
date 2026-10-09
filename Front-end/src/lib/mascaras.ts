export function formatarCpf(valor: string): string {
  const digitos = valor.replace(/\D/g, '').slice(0, 11)

  let formatado = digitos.slice(0, 3)
  if (digitos.length > 3) formatado += `.${digitos.slice(3, 6)}`
  if (digitos.length > 6) formatado += `.${digitos.slice(6, 9)}`
  if (digitos.length > 9) formatado += `-${digitos.slice(9, 11)}`

  return formatado
}
