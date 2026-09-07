<script setup lang="ts">
import { ArrowRight, Construction, DatabaseZap } from '@lucide/vue'
import { useRoute } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import PlannedSection from '@/components/app/PlannedSection.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { PlannedPageConfig } from '@/modules/planejamento/planned-pages'

defineProps<{
  config: PlannedPageConfig
}>()

const route = useRoute()
</script>

<template>
  <section class="mx-auto w-full max-w-7xl space-y-6">
    <PageHeader :title="config.title" :description="config.description" :eyebrow="config.module">
      <template #actions>
        <template v-for="action in config.actions" :key="action.label">
          <Button v-if="action.to" as-child :variant="action.primary ? 'default' : 'outline'">
            <RouterLink :to="action.to">
              {{ action.label }}
              <ArrowRight class="size-4" aria-hidden="true" />
            </RouterLink>
          </Button>
          <Button
            v-else
            :variant="action.primary ? 'default' : 'outline'"
            disabled
            title="Será conectado à API durante o desenvolvimento desta função"
          >
            {{ action.label }}
          </Button>
        </template>
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-2">
      <Badge variant="outline" class="gap-1">
        <Construction class="size-3" aria-hidden="true" />
        Estrutura navegável
      </Badge>
      <span class="text-xs text-muted-foreground">{{ route.path }}</span>
    </div>

    <div v-if="config.indicators.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="indicator in config.indicators" :key="indicator">
        <CardHeader class="pb-2">
          <CardDescription>{{ indicator }}</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-semibold">—</p>
          <p class="mt-1 text-xs text-muted-foreground">Aguardando integração com a API</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <PlannedSection
        v-for="section in config.sections"
        :key="section.title"
        :title="section.title"
        :description="section.description"
        :items="section.items"
      />
    </div>

    <Card class="border-dashed bg-muted/30">
      <CardHeader class="flex-row items-center gap-3">
        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-primary/10 text-primary">
          <DatabaseZap class="size-4" aria-hidden="true" />
        </span>
        <div>
          <CardTitle class="text-base">Pronta para iniciar a função</CardTitle>
          <CardDescription>
            A rota, a composição visual e os elementos previstos já existem. Dados e comandos reais
            serão ligados ao backend na etapa funcional.
          </CardDescription>
        </div>
      </CardHeader>
    </Card>
  </section>
</template>
