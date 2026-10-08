<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface CardActionProps {
    class?: string
  }

  const props = defineProps<CardActionProps>()
  const classes = computed(() => cn(
    'col-start-2 row-span-2 row-start-1 self-start justify-self-end',
    props.class,
  ))
</script>

<template>
  <div :class="classes" data-slot="card-action">
    <slot />
  </div>
</template>
