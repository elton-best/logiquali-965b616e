<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface CardContentProps {
    class?: string
  }

  const props = defineProps<CardContentProps>()
  const classes = computed(() => cn('px-6 [&:last-child]:pb-6', props.class))
</script>

<template>
  <div :class="classes" data-slot="card-content">
    <slot />
  </div>
</template>
