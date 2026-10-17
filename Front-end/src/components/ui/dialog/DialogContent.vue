<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import SheetContent from '@/components/ui/sheet/SheetContent.vue'

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>()
defineEmits<DialogContentEmits>()
</script>

<template>
  <SheetContent side="center" :class="props.class" v-bind="$attrs">
    <slot />
  </SheetContent>
</template>
