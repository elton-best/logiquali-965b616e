<template>
  <AppBadge
    :icon="config.icon"
    :label="config.label"
    :variant="config.variant"
  />
</template>

<script setup lang="ts">
  import { CheckCircle, Eye, FileEdit } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'

  const props = defineProps<{
    statut: 'brouillon' | 'en_revision' | 'valide' | 'draft' | 'pending_verification' | 'pending_approval' | 'approved' | 'obsolete'
  }>()

  const statusConfig = {
    brouillon: { label: 'brouillon en attente de vérification', icon: FileEdit, variant: 'info' as const },
    draft: { label: 'brouillon en attente de vérification', icon: FileEdit, variant: 'info' as const },
    en_revision: { label: 'brouillon en cours de vérification', icon: Eye, variant: 'warning' as const },
    pending_verification: { label: 'brouillon en cours de vérification', icon: Eye, variant: 'warning' as const },
    pending_approval: { label: 'brouillon - en attente de validation', icon: Eye, variant: 'warning' as const },
    valide: { label: 'validé - version 1', icon: CheckCircle, variant: 'success' as const },
    approved: { label: 'validé - version 1', icon: CheckCircle, variant: 'success' as const },
    obsolete: { label: 'Obsolète', icon: FileEdit, variant: 'error' as const },
  }

  const config = computed(() => statusConfig[props.statut])
</script>
