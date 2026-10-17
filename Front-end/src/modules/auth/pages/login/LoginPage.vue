<script setup lang="ts">
import axios from 'axios'
import { Eye, EyeOff, LoaderCircle, LogIn } from '@lucide/vue'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { prepararCsrf } from '@/modules/auth/api/autenticacao.api'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()

const email = ref('')
const senha = ref('')
const mostrarSenha = ref(false)
const enviando = ref(false)
const mensagem = ref('')

onMounted(() => {
  void prepararCsrf().catch(() => undefined)
})

function preencherCredenciais(tipo: 'sindico' | 'morador'): void {
  email.value = tipo === 'sindico' ? 'sindico@pro.com' : 'morador@pro.com'
  senha.value = '123'
  mensagem.value = ''
}

function destinoAposLogin(): string {
  const redirecionamento = route.query.redirecionar

  if (
    typeof redirecionamento === 'string' &&
    redirecionamento.startsWith('/') &&
    !redirecionamento.startsWith('//')
  ) {
    return redirecionamento
  }

  return autenticacao.temAcessoAdmin ? '/admin' : '/app'
}

async function enviar(): Promise<void> {
  mensagem.value = ''
  enviando.value = true

  try {
    await autenticacao.entrar(email.value, senha.value)
    await router.replace(destinoAposLogin())
  } catch (erro: unknown) {
    if (axios.isAxiosError(erro)) {
      const erros = erro.response?.data?.errors as Record<string, string[]> | undefined
      mensagem.value =
        erros?.email?.[0] ?? 'Não foi possível entrar. Verifique os dados e tente novamente.'
    } else {
      mensagem.value = 'Não foi possível entrar. Tente novamente.'
    }
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <Card class="shadow-lg">
    <CardHeader>
      <CardTitle>Entrar</CardTitle>
      <CardDescription>Use seu e-mail e senha para acessar o condomínio.</CardDescription>
    </CardHeader>
    <CardContent>
      <form class="space-y-4" novalidate @submit.prevent="enviar">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="email">E-mail</label>
          <Input
            id="email"
            v-model="email"
            type="email"
            name="email"
            autocomplete="username"
            inputmode="email"
            autofocus
            required
            :aria-invalid="mensagem ? 'true' : undefined"
            placeholder="voce@exemplo.com"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium" for="senha">Senha</label>
          <div class="relative">
            <Input
              id="senha"
              v-model="senha"
              :type="mostrarSenha ? 'text' : 'password'"
              name="senha"
              autocomplete="current-password"
              required
              class="pr-11"
              :aria-invalid="mensagem ? 'true' : undefined"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 grid w-10 place-items-center rounded-r-md text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              :aria-label="mostrarSenha ? 'Ocultar senha' : 'Exibir senha'"
              @click="mostrarSenha = !mostrarSenha"
            >
              <EyeOff v-if="mostrarSenha" class="size-4" aria-hidden="true" />
              <Eye v-else class="size-4" aria-hidden="true" />
            </button>
          </div>
        </div>

        <p
          v-if="mensagem"
          class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
          role="alert"
        >
          {{ mensagem }}
        </p>

        <Button type="submit" class="h-10 w-full" :disabled="enviando || !email || !senha">
          <LoaderCircle v-if="enviando" class="size-4 animate-spin" aria-hidden="true" />
          <LogIn v-else class="size-4" aria-hidden="true" />
          {{ enviando ? 'Entrando...' : 'Entrar' }}
        </Button>

        <div
          class="rounded-lg border border-dashed border-primary/30 bg-primary/5 p-3 text-xs text-muted-foreground"
        >
          <p class="font-medium text-foreground mb-1">Acesso de Demonstração / Teste:</p>
          <p class="mb-2">A senha padrão local é <strong class="text-foreground">123</strong>.</p>
          <div class="flex flex-col gap-1.5 sm:flex-row">
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="h-7 text-xs flex-1"
              @click="preencherCredenciais('sindico')"
            >
              Síndico (sindico@pro.com)
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="h-7 text-xs flex-1"
              @click="preencherCredenciais('morador')"
            >
              Morador (morador@pro.com)
            </Button>
          </div>
        </div>

        <RouterLink
          :to="{ name: 'recuperar-senha' }"
          class="mx-auto block text-sm text-primary underline-offset-4 hover:underline"
        >
          Esqueci minha senha
        </RouterLink>
      </form>
    </CardContent>
  </Card>
</template>
