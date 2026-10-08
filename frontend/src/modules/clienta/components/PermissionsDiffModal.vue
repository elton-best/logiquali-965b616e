<template>
  <v-dialog v-model="visible" max-width="800">
    <v-card>
      <v-card-title>Différences des permissions</v-card-title>
      <v-card-text>
        <div v-if="added.length">
          <h4>Ajoutées</h4>
          <v-chip v-for="p in added" :key="p" class="mr-2 mb-2">{{ mapPermissionLabel(p) }}</v-chip>
        </div>
        <div v-if="removed.length" class="mt-4">
          <h4>Retirées</h4>
          <v-chip v-for="p in removed" :key="p" class="mr-2 mb-2">{{ mapPermissionLabel(p) }}</v-chip>
        </div>
        <div v-if="!added.length && !removed.length" class="text-medium-emphasis">Aucune différence</div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('close')">Fermer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { mapPermissionLabel } from '@/config/permissionLabels'

const props = defineProps<{
  base?: string[]
  compare?: string[]
}>()

defineEmits<{ (e: 'close'): void }>()

const visible = ref(true)

const added = computed<string[]>(() =>
  (props.compare ?? []).filter((permission: string) => !(props.base ?? []).includes(permission)),
)

const removed = computed<string[]>(() =>
  (props.base ?? []).filter((permission: string) => !(props.compare ?? []).includes(permission)),
)

watch(() => props.base, () => {
  visible.value = true
})

watch(() => props.compare, () => {
  visible.value = true
})
</script>
