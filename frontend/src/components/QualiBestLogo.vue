<template>
  <AppLogo
    :show-text="showText"
    :subtitle="showText ? 'Plateforme QHSE' : ''"
    title="BestQHSE"
    variant="mark"
  />
</template>

<script setup lang="ts">
  import AppLogo from '@/components/branding/AppLogo.vue'

  interface Props {
    size?: 'sm' | 'md' | 'lg'
    showText?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showText: true,
  })
</script>

<style scoped>
</style>
