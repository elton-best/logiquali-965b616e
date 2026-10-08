<template>
  <div class="text-center mb-8">
    <v-avatar class="mb-4 elevation-8" color="white" size="80">
      <v-icon color="success" size="50">mdi-check-circle-outline</v-icon>
    </v-avatar>
    <h1 class="text-h4 font-weight-bold text-white mb-2">Inscription réussie !</h1>
    <p v-if="type === 'enterprise'" class="text-subtitle-1 text-white opacity-90">
      Votre demande a été soumise avec succès
    </p>
    <p v-else class="text-subtitle-1 text-white opacity-90">
      Votre compte a été créé avec succès
    </p>
  </div>
</template>

<script setup lang="ts">
  const props = defineProps({
    type: {
      type: String,
      required: true,
    },
  })

  void props
</script>
