<template>
  <v-chip
    class="font-weight-medium"
    :color="chipColor"
    :prepend-icon="icon"
    :size="size"
    :variant="variant"
  >
    {{ label }}
  </v-chip>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    status: string
    size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
    variant?: 'flat' | 'text' | 'elevated' | 'tonal' | 'outlined' | 'plain'
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'small',
    variant: 'tonal',
  })

  const statusConfig: Record<string, { color: string, label: string, icon?: string }> = {
    // Document statuses
    draft: { color: 'grey', label: 'Brouillon', icon: 'mdi-file-edit-outline' },
    in_review: { color: 'info', label: 'En revue', icon: 'mdi-eye' },
    validated: { color: 'success', label: 'Validé', icon: 'mdi-check-circle' },
    diffused: { color: 'primary', label: 'Diffusé', icon: 'mdi-send' },
    obsolete: { color: 'warning', label: 'Obsolète', icon: 'mdi-alert' },
    archived: { color: 'grey-darken-2', label: 'Archivé', icon: 'mdi-archive' },

    // Process statuses
    active: { color: 'success', label: 'Actif', icon: 'mdi-check-circle' },
    inactive: { color: 'grey', label: 'Inactif', icon: 'mdi-minus-circle' },
    under_review: { color: 'info', label: 'En revue', icon: 'mdi-clock-outline' },

    // Generic statuses
    pending: { color: 'warning', label: 'En attente', icon: 'mdi-clock-outline' },
    approved: { color: 'success', label: 'Approuvé', icon: 'mdi-check' },
    rejected: { color: 'error', label: 'Rejeté', icon: 'mdi-close-circle' },
    completed: { color: 'success', label: 'Terminé', icon: 'mdi-check-all' },
    cancelled: { color: 'grey', label: 'Annulé', icon: 'mdi-cancel' },

    // Subscription statuses
    trial: { color: 'info', label: 'Essai', icon: 'mdi-timer-sand' },
    subscribed: { color: 'success', label: 'Actif', icon: 'mdi-check-circle' },
    expired: { color: 'error', label: 'Expiré', icon: 'mdi-alert-circle' },
    suspended: { color: 'warning', label: 'Suspendu', icon: 'mdi-pause-circle' },
  }

  const config = computed(() => statusConfig[props.status] || { color: 'grey', label: props.status, icon: undefined })
  const chipColor = computed(() => config.value.color)
  const label = computed(() => config.value.label)
  const icon = computed(() => config.value.icon)
</script>
