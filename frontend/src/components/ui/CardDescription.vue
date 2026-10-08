<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface CardDescriptionProps {
    class?: string
  }

  const props = defineProps<CardDescriptionProps>()
  const classes = computed(() => cn('text-muted-foreground', props.class))
</script>

<template>
  <p :class="classes" data-slot="card-description">
    <slot />
  </p>
</template>
