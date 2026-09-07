<script setup lang="ts">
import { ArrowLeft, Mail } from '@lucide/vue'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

const email = ref('')
const mensagem = ref('')

function enviar(): void {
  mensagem.value =
    'A interface está pronta. O envio do link será conectado ao backend quando esta função for desenvolvida.'
}
</script>

<template>
  <Card class="shadow-lg">
    <CardHeader>
      <CardTitle>Recuperar senha</CardTitle>
      <CardDescription>
        Informe o e-mail da sua conta para solicitar um link de redefinição.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form class="space-y-4" @submit.prevent="enviar">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="email-recuperacao">E-mail</label>
          <Input
            id="email-recuperacao"
            v-model="email"
            type="email"
            autocomplete="email"
            autofocus
            required
            placeholder="voce@exemplo.com"
          />
        </div>

        <p v-if="mensagem" class="rounded-md bg-muted px-3 py-2 text-sm" role="status">
          {{ mensagem }}
        </p>

        <Button v-if="mensagem" as-child type="button" variant="outline" class="w-full">
          <RouterLink :to="{ name: 'redefinir-senha', params: { token: 'estrutura-inicial' } }">
            Visualizar tela de redefinição
          </RouterLink>
        </Button>

        <Button type="submit" class="w-full" :disabled="!email">
          <Mail class="size-4" aria-hidden="true" />
          Solicitar link
        </Button>

        <Button as-child type="button" variant="ghost" class="w-full">
          <RouterLink :to="{ name: 'entrar' }">
            <ArrowLeft class="size-4" aria-hidden="true" />
            Voltar para entrar
          </RouterLink>
        </Button>
      </form>
    </CardContent>
  </Card>
</template>
