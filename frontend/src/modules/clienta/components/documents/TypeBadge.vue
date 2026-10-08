<template>
  <v-chip
    :color="color"
    size="x-small"
    variant="tonal"
  >
    {{ label }}
  </v-chip>
</template>

<script setup lang="ts">
import { computed } from 'vue'

/**
 * Badge de type documentaire.
 * Accepte n'importe quelle abréviation définie par l'entreprise
 * (ex: PRC, FOR, QUAL-PROC, POL-RH, etc.)
 *
 * Props :
 * - type : abréviation du type (depuis document.typeConfiguration?.abbreviation)
 * - name : nom complet du type (depuis document.typeConfiguration?.name) — optionnel
 */
const props = defineProps<{
  type?: string | null
  name?: string | null
}>()

// Palette de couleurs cyclique pour les types personnalisés
const COLOR_PALETTE = [
  'primary', 'teal', 'indigo', 'purple', 'orange',
  'cyan', 'green', 'blue', 'deep-orange', 'brown',
]

function hashString (str: string): number {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = ((hash << 5) - hash) + str.charCodeAt(i)
    hash |= 0
  }
  return Math.abs(hash)
}

const label = computed(() => {
  if (props.name) return props.name
  if (props.type) return props.type
  return '—'
})

const color = computed(() => {
  const key = (props.type ?? '').toUpperCase()
  if (!key) return 'grey'
  return COLOR_PALETTE[hashString(key) % COLOR_PALETTE.length]
})
</script>
