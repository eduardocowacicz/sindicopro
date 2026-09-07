<script setup lang="ts">
import PlannedPage from '@/components/app/PlannedPage.vue'
import { plannedPages } from '@/modules/planejamento/planned-pages'
</script>

<template><PlannedPage :config="plannedPages.occurrenceTypes" /></template>
