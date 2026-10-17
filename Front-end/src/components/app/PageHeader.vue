<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    description?: string
    eyebrow?: string
    titulo?: string
    descricao?: string
    modulo?: string
  }>(),
  {
    title: '',
    description: '',
    eyebrow: undefined,
    titulo: undefined,
    descricao: undefined,
    modulo: undefined,
  },
)
</script>

<template>
  <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
      <p v-if="eyebrow || modulo" class="text-sm font-medium text-primary">
        {{ eyebrow || modulo }}
      </p>
      <h1 class="text-2xl font-bold tracking-tight">{{ title || titulo }}</h1>
      <p v-if="description || descricao" class="mt-1 max-w-3xl text-sm text-muted-foreground">
        {{ description || descricao }}
      </p>
    </div>

    <div v-if="$slots.actions || $slots.acoes" class="flex shrink-0 flex-wrap gap-2">
      <slot name="actions" />
      <slot name="acoes" />
    </div>
  </header>
</template>
