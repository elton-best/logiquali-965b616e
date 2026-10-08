<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center mb-6">
        <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
          <v-icon color="white" size="24">mdi-text-box-outline</v-icon>
        </v-avatar>
        <div>
          <h2 class="text-h5 font-weight-bold">Définition du domaine d'application</h2>
          <p class="text-body-2 text-medium-emphasis mb-0">Périmètre et limites du SMQ</p>
        </div>
      </div>

      <v-card class="mb-6" elevation="2" rounded="lg">
        <v-card-text class="pa-6">
          <h3 class="text-h6 font-weight-bold mb-4 d-flex align-center">
            <v-icon class="mr-2" :color="step?.color">mdi-file-document</v-icon>
            Objet du document
          </h3>
          <v-alert class="mb-4" density="compact" type="info" variant="tonal">
            <v-icon size="small" start>mdi-lightbulb</v-icon>
            Texte pré-rempli modifiable selon vos besoins
          </v-alert>
          <v-textarea
            v-model="form.documentObjective"
            bg-color="white"
            density="comfortable"
            rounded="lg"
            rows="6"
            variant="solo"
          />
        </v-card-text>
      </v-card>

      <v-card elevation="2" rounded="lg">
        <v-card-text class="pa-6">
          <h3 class="text-h6 font-weight-bold mb-4 d-flex align-center">
            <v-icon class="mr-2" :color="step?.color">mdi-domain</v-icon>
            Définition du domaine d'application
          </h3>
          <v-textarea
            v-model="form.scopeDefinition"
            bg-color="white"
            density="comfortable"
            placeholder="Décrivez le périmètre et les limites du SMQ..."
            rounded="lg"
            rows="8"
            variant="solo"
          />
        </v-card-text>
      </v-card>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    step: {
      type: Object as PropType<{ label: string, color: string, gradient: string }>,
      required: true,
    },
    form: {
      type: Object as PropType<{ documentObjective: string, scopeDefinition: string }>,
      required: true,
    },
  })
</script>
