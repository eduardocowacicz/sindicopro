<script setup lang="ts">
defineProps<{
  title: string
  description: string
  eyebrow?: string
}>()
</script>

<template>
  <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-sm font-medium text-primary">{{ eyebrow }}</p>
      <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
      <p class="mt-1 max-w-3xl text-sm text-muted-foreground">{{ description }}</p>
    </div>

    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
      <slot name="actions" />
    </div>
  </header>
</template>
