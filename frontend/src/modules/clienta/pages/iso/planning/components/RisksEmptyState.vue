<template>
  <v-card class="empty-card mb-6" elevation="0" rounded="xl">
    <v-card-text class="text-center py-12">
      <v-avatar class="mb-4" color="primary" size="64" variant="tonal">
        <v-icon size="34">mdi-folder-search-outline</v-icon>
      </v-avatar>
      <h3 class="text-h6 font-weight-bold mb-2">Aucun résultat pour cette vue</h3>
      <p class="text-body-2 text-medium-emphasis mb-6">{{ message }}</p>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('add')">
        Ajouter {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    message: {
      type: String,
      required: true,
    },
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
  }>()
</script>
