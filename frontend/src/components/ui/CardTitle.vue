<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface CardTitleProps {
    class?: string
  }

  const props = defineProps<CardTitleProps>()
  const classes = computed(() => cn('leading-none', props.class))
</script>

<template>
  <h4 :class="classes" data-slot="card-title">
    <slot />
  </h4>
</template>
