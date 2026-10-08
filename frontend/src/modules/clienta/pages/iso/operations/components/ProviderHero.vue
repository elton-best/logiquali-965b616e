<template>
  <v-card class="hero mb-6" elevation="0" rounded="xl">
    <v-card-text class="py-6 px-6 px-md-8">
      <div class="d-flex align-start justify-space-between flex-wrap ga-4">
        <div>
          <div class="text-overline hero-kicker mb-2">ISO 9001 - 8.4</div>
          <h2 class="text-h4 text-md-h5 font-weight-black">Maîtrise des prestataires externes</h2>
          <p class="text-body-1 text-medium-emphasis mt-1 mb-0">
            Digitalisation de la base des prestataires et contractualisation guidée par formulaire.
          </p>
        </div>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('create')">
          Nouveau prestataire
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  const emit = defineEmits<{
    (event: 'create'): void
  }>()
</script>
