<script setup lang="ts">
import { Building2, CircleCheck, Clock3, Users, WalletCards } from '@lucide/vue'
import { computed } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useStatusQuery } from '@/modules/inicio/queries/status.queries'

const statusQuery = useStatusQuery()

const statusText = computed(() => {
  if (statusQuery.isPending.value) return 'Verificando API'
  if (statusQuery.isError.value) return 'API indisponível'
  return `${statusQuery.data.value?.application ?? 'SindicoPro'} conectado`
})

const indicators = [
  { label: 'Unidades', value: '—', description: 'Aguardando cadastros', icon: Building2 },
  { label: 'Moradores', value: '—', description: 'Aguardando cadastros', icon: Users },
  { label: 'Saldo atual', value: 'R$ —', description: 'Aguardando financeiro', icon: WalletCards },
]
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-sm font-medium text-primary">Visão geral</p>
        <h1 class="text-2xl font-semibold tracking-tight">Painel do condomínio</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Base inicial pronta para receber as primeiras funções do sistema.
        </p>
      </div>
      <Button
        variant="outline"
        :disabled="statusQuery.isFetching.value"
        @click="statusQuery.refetch()"
      >
        Atualizar conexão
      </Button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <Card v-for="indicator in indicators" :key="indicator.label">
        <CardHeader class="flex-row items-center justify-between pb-2">
          <CardDescription>{{ indicator.label }}</CardDescription>
          <component :is="indicator.icon" class="size-4 text-muted-foreground" aria-hidden="true" />
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-semibold">{{ indicator.value }}</p>
          <p class="mt-1 text-xs text-muted-foreground">{{ indicator.description }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
      <Card>
        <CardHeader>
          <CardTitle>Primeiros passos</CardTitle>
          <CardDescription
            >O projeto está preparado para o desenvolvimento por telas e funções.</CardDescription
          >
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex gap-3 rounded-lg border p-4">
            <CircleCheck class="mt-0.5 size-5 shrink-0 text-success" aria-hidden="true" />
            <div>
              <p class="text-sm font-medium">Layout compartilhado configurado</p>
              <p class="text-sm text-muted-foreground">
                Sidebar, cabeçalho, componentes shadcn-vue e responsividade.
              </p>
            </div>
          </div>
          <div class="flex gap-3 rounded-lg border p-4">
            <Clock3 class="mt-0.5 size-5 shrink-0 text-warning" aria-hidden="true" />
            <div>
              <p class="text-sm font-medium">Próxima entrega</p>
              <p class="text-sm text-muted-foreground">
                Primeiro corte vertical de cadastro de blocos.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Conexão</CardTitle>
          <CardDescription>Comunicação independente com o backend.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <Badge :variant="statusQuery.isError.value ? 'destructive' : 'outline'">
            {{ statusText }}
          </Badge>
          <p class="text-sm text-muted-foreground">
            O frontend inicia mesmo sem a API. Quando o backend estiver disponível, esta verificação
            muda automaticamente.
          </p>
        </CardContent>
      </Card>
    </div>
  </section>
</template>
