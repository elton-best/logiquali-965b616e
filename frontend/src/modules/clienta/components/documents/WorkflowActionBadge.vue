<template>
  <v-chip
    :color="badgeConfig.color"
    :prepend-icon="badgeConfig.icon"
    size="small"
    variant="tonal"
    class="action-badge"
  >
    {{ badgeConfig.label }}
  </v-chip>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface BadgeConfig {
    label: string
    color: string
    icon: string
  }

  const props = defineProps<{
    action: 'verification' | 'approval' | 'rejection'
  }>()

  const badgeConfig = computed<BadgeConfig>(() => {
    const config: Record<string, BadgeConfig> = {
      verification: {
        label: '⏱️ Vérification requise',
        color: 'warning',
        icon: 'mdi-eye-check',
      },
      approval: {
        label: '✏️ Approbation requise',
        color: 'error',
        icon: 'mdi-pencil-check',
      },
      rejection: {
        label: '❌ Action après rejet',
        color: 'error',
        icon: 'mdi-alert-octagon',
      },
    }
    return config[props.action] || config.verification
  })
</script>

<style scoped>
.action-badge {
  font-weight: 600;
  letter-spacing: 0.3px;
}
</style>
