<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface AlertDescriptionProps {
    class?: string
  }

  const props = defineProps<AlertDescriptionProps>()

  const descriptionClass = computed(() =>
    cn(
      'text-muted-foreground col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed',
      props.class,
    ),
  )
</script>

<template>
  <div :class="descriptionClass" data-slot="alert-description">
    <slot />
  </div>
</template>
