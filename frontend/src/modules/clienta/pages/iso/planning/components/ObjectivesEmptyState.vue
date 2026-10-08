<template>
  <v-card class="text-center pa-12" elevation="0" rounded="lg">
    <v-icon color="grey-lighten-1" size="64">mdi-target-variant</v-icon>
    <h3 class="text-h6 mt-4 mb-2">Aucun objectif trouvé</h3>
    <p class="text-body-2 text-medium-emphasis mb-4">
      {{ message }}
    </p>
    <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('add')">
      Créer un objectif
    </v-btn>
  </v-card>
</template>

<script setup lang="ts">
  defineProps({
    message: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
  }>()
</script>
