<script setup lang="ts">
import { CircleDashed } from '@lucide/vue'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

defineProps<{
  title: string
  description?: string
  items: string[]
}>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ title }}</CardTitle>
      <CardDescription v-if="description">{{ description }}</CardDescription>
    </CardHeader>
    <CardContent>
      <ul class="grid gap-3 sm:grid-cols-2" :aria-label="title">
        <li v-for="item in items" :key="item" class="flex items-start gap-2 text-sm">
          <CircleDashed class="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" />
          <span>{{ item }}</span>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
