<script setup lang="ts">
import { Building2 } from '@lucide/vue'
</script>

<template>
  <main class="grid min-h-screen place-items-center bg-muted/60 p-4">
    <section class="w-full max-w-md">
      <header class="mb-6 text-center">
        <span
          class="mx-auto mb-3 grid size-11 place-items-center rounded-lg bg-primary text-primary-foreground shadow-sm"
        >
          <Building2 class="size-6" aria-hidden="true" />
        </span>
        <p class="text-2xl font-bold tracking-tight">SindicoPro</p>
        <p class="mt-1 text-sm text-muted-foreground">Sistema de Gestão Condominial</p>
      </header>

      <RouterView />
    </section>
  </main>
</template>
