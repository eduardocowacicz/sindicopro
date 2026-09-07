<script setup lang="ts">
import { ArrowLeft, KeyRound } from '@lucide/vue'
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

const email = ref('')
const senha = ref('')
const confirmacao = ref('')
const mensagem = ref('')
const formularioValido = computed(
  () => email.value !== '' && senha.value.length >= 8 && senha.value === confirmacao.value,
)

function enviar(): void {
  mensagem.value =
    'A interface está pronta. A redefinição será conectada ao backend quando esta função for desenvolvida.'
}
</script>

<template>
  <Card class="shadow-lg">
    <CardHeader>
      <CardTitle>Redefinir senha</CardTitle>
      <CardDescription
        >Cadastre uma nova senha para concluir a recuperação da conta.</CardDescription
      >
    </CardHeader>
    <CardContent>
      <form class="space-y-4" @submit.prevent="enviar">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="email-redefinicao">E-mail</label>
          <Input
            id="email-redefinicao"
            v-model="email"
            type="email"
            autocomplete="email"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="nova-senha">Nova senha</label>
          <Input
            id="nova-senha"
            v-model="senha"
            type="password"
            autocomplete="new-password"
            required
          />
          <p class="text-xs text-muted-foreground">Use pelo menos 8 caracteres.</p>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium" for="confirmar-senha">Confirmar nova senha</label>
          <Input
            id="confirmar-senha"
            v-model="confirmacao"
            type="password"
            autocomplete="new-password"
            required
          />
        </div>

        <p v-if="mensagem" class="rounded-md bg-muted px-3 py-2 text-sm" role="status">
          {{ mensagem }}
        </p>

        <Button type="submit" class="w-full" :disabled="!formularioValido">
          <KeyRound class="size-4" aria-hidden="true" />
          Salvar nova senha
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
