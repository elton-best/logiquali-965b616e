<template>
  <AppBadge
    :icon="config.icon"
    :label="config.label"
    :variant="config.variant"
  />
</template>

<script setup lang="ts">
  import { CheckCircle, Circle, Clock } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'

  const props = defineProps<{
    etat?: 'a_etablir' | 'en_cours' | 'termine' | string | null
  }>()

  const stateConfig: Record<string, { label: string, icon: any, variant: 'info' | 'warning' | 'success' | 'neutral' }> = {
    a_etablir: { label: 'À établir', icon: Circle, variant: 'info' },
    en_cours: { label: 'En cours', icon: Clock, variant: 'warning' },
    termine: { label: 'Terminé', icon: CheckCircle, variant: 'success' },
    default: { label: 'Inconnu', icon: Circle, variant: 'neutral' }
  }

  const config = computed(() => {
    if (!props.etat || !stateConfig[props.etat]) return stateConfig.default
    return stateConfig[props.etat]
  })
</script>
