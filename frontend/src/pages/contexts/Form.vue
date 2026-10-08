<template>
  <div class="contexts-form-page pa-6">
    <div class="d-flex align-center mb-6">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        @click="$router.back()"
      />
      <h1 class="text-h4 ml-3">Nouvelle Analyse Contexte</h1>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <p class="text-body-1 mb-4">
          Sélectionnez le type d'analyse contextuelle à réaliser conformément à l'ISO 9001:2015 clause 4.1.
        </p>

        <v-row>
          <v-col cols="12" md="6">
            <v-card
              class="cursor-pointer hover:shadow-lg transition-all"
              :class="selectedType === 'swot' ? 'border-blue-500 border-2' : ''"
              @click="selectedType = 'swot'"
            >
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-3" color="blue" size="large">mdi-chart-box-outline</v-icon>
                <span>Analyse SWOT</span>
              </v-card-title>
              <v-card-text>
                <p class="text-body-2">
                  Analyse des <strong>Forces, Faiblesses, Opportunités et Menaces</strong>
                  de l'organisation dans son contexte stratégique.
                </p>
                <v-chip class="mt-2" color="blue" size="small">Recommandé</v-chip>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card
              class="cursor-pointer hover:shadow-lg transition-all"
              :class="selectedType === 'pestel' ? 'border-green-500 border-2' : ''"
              @click="selectedType = 'pestel'"
            >
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-3" color="green" size="large">mdi-radar</v-icon>
                <span>Analyse PESTEL</span>
              </v-card-title>
              <v-card-text>
                <p class="text-body-2">
                  Analyse des facteurs <strong>Politiques, Économiques, Sociaux,
                    Technologiques, Environnementaux et Légaux</strong> externes.
                </p>
                <v-chip class="mt-2" color="green" size="small">Complémentaire</v-chip>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-alert v-if="selectedType" class="mt-4" type="info">
          <strong>Type sélectionné:</strong>
          {{ selectedType === 'swot' ? 'Analyse SWOT' : 'Analyse PESTEL' }}
        </v-alert>
      </v-card-text>

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          color="grey"
          variant="text"
          @click="$router.back()"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!selectedType"
          @click="openWizard"
        >
          Continuer
          <v-icon end>mdi-arrow-right</v-icon>
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Context Wizard Dialog -->
    <ContextWizard
      v-if="showWizard"
      v-model="showWizard"
      :type="selectedType!"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ContextWizard from '@/components/contexts/ContextWizard.vue'

  const router = useRouter()
  const selectedType = ref<'swot' | 'pestel' | null>(null)
  const showWizard = ref(false)

  function openWizard () {
    if (selectedType.value) {
      showWizard.value = true
    }
  }

  function handleSaved (context: any) {
    showWizard.value = false
    router.push(`/contexts/View/${context.id}`)
  }
</script>

<style scoped>
.contexts-form-page {
  max-width: 1200px;
  margin: 0 auto;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
