<template>
  <BaseBadge :dot="dot" :label="displayLabel" :size="size" :variant="variantColor" />
</template>

<script setup lang="ts">
  import type { DocumentType } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  interface Props {
    type: string
    /** Libellé personnalisé de l'entreprise (typeConfiguration.name) — prioritaire sur type */
    name?: string | null
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
    name: null,
  })

  const LABELS: Partial<Record<DocumentType, string>> = {
    procedure: 'Procédure',
    instruction: 'Instruction',
    form: 'Formulaire',
    record: 'Enregistrement',
    manual: 'Manuel',
    policy: 'Politique',
    other: 'Autre',
  }

  const VARIANTS: Partial<Record<DocumentType, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'>> = {
    procedure: 'primary',
    instruction: 'info',
    form: 'purple',
    record: 'success',
    manual: 'warning',
    policy: 'info',
    other: 'gray',
  }

  const displayLabel = computed(() =>
    props.name || LABELS[props.type as DocumentType] || props.type || '—',
  )

  const variantColor = computed(() => VARIANTS[props.type as DocumentType] ?? 'gray')
</script>
