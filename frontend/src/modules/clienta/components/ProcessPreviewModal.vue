<template>
  <v-dialog v-model="show" max-width="1200" persistent scrollable>
    <v-card rounded="xl">
      <!-- Header -->
      <v-card-title class="pa-6 bg-gradient-success text-white">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-avatar class="mr-4" color="white" size="56">
              <v-icon color="success" size="32">mdi-eye-check</v-icon>
            </v-avatar>
            <div>
              <h2 class="text-h4 font-weight-bold">Prévisualisation</h2>
              <p class="text-body-1 mb-0 opacity-90">Vérifiez les informations avant l'enregistrement</p>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="handleClose" />
        </div>
      </v-card-title>

      <v-divider />

      <!-- Stepper Navigation -->
      <v-card-text class="pa-6 bg-grey-lighten-5">
        <div class="stepper-container">
          <div
            v-for="(section, index) in sections"
            :key="index"
            class="step-item"
            :class="{ active: currentSection === index }"
            @click="currentSection = index"
          >
            <div class="step-circle" :style="`background: ${section.color}`">
              <v-icon color="white" size="20">{{ section.icon }}</v-icon>
            </div>
            <div class="step-label">{{ section.label }}</div>
            <div v-if="index < sections.length - 1" class="step-line" />
          </div>
        </div>
      </v-card-text>

      <v-divider />

      <!-- Content -->
      <v-card-text class="pa-8" style="max-height: 60vh;">
        <v-window v-model="currentSection">
          <!-- Section 0: Informations générales -->
          <v-window-item :value="0">
            <v-card elevation="3" rounded="lg">
              <v-card-title class="bg-gradient-primary text-white d-flex align-center">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informations générales
              </v-card-title>
              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="mb-4">
                      <div class="text-caption text-medium-emphasis mb-1">Nom</div>
                      <div class="text-h6 font-weight-bold">{{ data.name || 'Non défini' }}</div>
                    </div>
                    <div class="mb-4">
                      <div class="text-caption text-medium-emphasis mb-1">Catégorie</div>
                      <v-chip :color="getCategoryColor(data.category)" size="large">
                        <v-icon start>{{ getCategoryIcon(data.category) }}</v-icon>
                        {{ getCategoryLabel(data.category) }}
                      </v-chip>
                    </div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="mb-4">
                      <div class="text-caption text-medium-emphasis mb-1">Description</div>
                      <div class="text-body-1 preview-text">{{ data.description || 'Aucune description' }}</div>
                    </div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Section 1: Objectifs -->
          <v-window-item :value="1">
            <v-card elevation="3" rounded="lg">
              <v-card-title class="bg-gradient-success text-white d-flex align-center">
                <v-icon class="mr-2">mdi-target</v-icon>
                Objectifs ({{ data.objectives?.length || 0 }})
              </v-card-title>
              <v-card-text class="pa-6">
                <v-chip-group v-if="data.objectives && data.objectives.length > 0" column>
                  <v-chip
                    v-for="(obj, i) in data.objectives"
                    :key="i"
                    color="success"
                    size="large"
                    variant="tonal"
                  >
                    <v-icon size="small" start>mdi-check-circle</v-icon>
                    {{ obj }}
                  </v-chip>
                </v-chip-group>
                <v-alert v-else type="info" variant="tonal">
                  Aucun objectif défini
                </v-alert>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Section 2: Ressources -->
          <v-window-item :value="2">
            <v-card elevation="3" rounded="lg">
              <v-card-title class="bg-gradient-info text-white d-flex align-center">
                <v-icon class="mr-2">mdi-package-variant</v-icon>
                Ressources ({{ data.resources?.length || 0 }})
              </v-card-title>
              <v-card-text class="pa-6">
                <v-chip-group v-if="data.resources && data.resources.length > 0" column>
                  <v-chip
                    v-for="(res, i) in data.resources"
                    :key="i"
                    color="info"
                    size="large"
                    variant="tonal"
                  >
                    <v-icon size="small" start>mdi-cube</v-icon>
                    {{ res }}
                  </v-chip>
                </v-chip-group>
                <v-alert v-else type="info" variant="tonal">
                  Aucune ressource définie
                </v-alert>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Section 3: Séquences -->
          <v-window-item :value="3">
            <v-card elevation="3" rounded="lg">
              <v-card-title class="bg-gradient-warning text-white d-flex align-center">
                <v-icon class="mr-2">mdi-timeline</v-icon>
                Séquences ({{ data.sequences?.length || 0 }})
              </v-card-title>
              <v-card-text class="pa-6">
                <v-timeline v-if="data.sequences && data.sequences.length > 0" density="compact" side="end">
                  <v-timeline-item
                    v-for="(seq, i) in data.sequences"
                    :key="i"
                    dot-color="warning"
                    size="small"
                  >
                    <div class="mb-2">
                      <div class="font-weight-bold text-h6">{{ seq.name || `Séquence ${i + 1}` }}</div>
                      <div v-if="seq.description" class="text-body-2 text-medium-emphasis">{{ seq.description }}</div>
                    </div>
                  </v-timeline-item>
                </v-timeline>
                <v-alert v-else type="info" variant="tonal">
                  Aucune séquence définie
                </v-alert>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Section 4: Risques et Opportunités -->
          <v-window-item :value="4">
            <v-row>
              <v-col cols="12" md="6">
                <v-card class="h-100" elevation="3" rounded="lg">
                  <v-card-title class="bg-gradient-error text-white d-flex align-center">
                    <v-icon class="mr-2">mdi-alert</v-icon>
                    Risques ({{ data.risks?.length || 0 }})
                  </v-card-title>
                  <v-card-text class="pa-6">
                    <v-chip-group v-if="data.risks && data.risks.length > 0" column>
                      <v-chip v-for="(risk, i) in data.risks" :key="i" color="error" variant="tonal">
                        <v-icon size="small" start>mdi-alert-circle</v-icon>
                        {{ risk }}
                      </v-chip>
                    </v-chip-group>
                    <v-alert v-else type="success" variant="tonal">
                      <v-icon start>mdi-check-circle</v-icon>
                      Aucun risque identifié
                    </v-alert>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-card class="h-100" elevation="3" rounded="lg">
                  <v-card-title class="bg-gradient-success text-white d-flex align-center">
                    <v-icon class="mr-2">mdi-lightbulb</v-icon>
                    Opportunités ({{ data.opportunities?.length || 0 }})
                  </v-card-title>
                  <v-card-text class="pa-6">
                    <v-chip-group v-if="data.opportunities && data.opportunities.length > 0" column>
                      <v-chip v-for="(opp, i) in data.opportunities" :key="i" color="success" variant="tonal">
                        <v-icon size="small" start>mdi-star</v-icon>
                        {{ opp }}
                      </v-chip>
                    </v-chip-group>
                    <v-alert v-else type="info" variant="tonal">
                      Aucune opportunité définie
                    </v-alert>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-divider />

      <!-- Actions -->
      <v-card-actions class="pa-6">
        <v-btn
          v-if="currentSection > 0"
          prepend-icon="mdi-chevron-left"
          size="large"
          variant="tonal"
          @click="currentSection--"
        >
          Précédent
        </v-btn>
        <v-btn prepend-icon="mdi-close" size="large" variant="outlined" @click="handleClose">
          Fermer
        </v-btn>
        <v-spacer />
        <v-btn
          v-if="currentSection < sections.length - 1"
          append-icon="mdi-chevron-right"
          color="primary"
          size="large"
          @click="currentSection++"
        >
          Suivant
        </v-btn>
        <v-btn
          v-else
          color="success"
          :loading="loading"
          prepend-icon="mdi-check-circle"
          size="large"
          @click="handleConfirm"
        >
          Enregistrer définitivement
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  interface ProcessPreviewData {
    name?: string
    category?: string
    description?: string
    objectives?: string[]
    resources?: string[]
    sequences?: Array<{ name?: string, description?: string }>
    risks?: string[]
    opportunities?: string[]
  }

  interface Props {
    modelValue: boolean
    data: ProcessPreviewData
    loading?: boolean
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'confirm'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<Emits>()

  const currentSection = ref(0)

  const sections = [
    { label: 'Informations', icon: 'mdi-information', color: '#5b8dd9' },
    { label: 'Objectifs', icon: 'mdi-target', color: '#22c55e' },
    { label: 'Ressources', icon: 'mdi-package-variant', color: '#3b82f6' },
    { label: 'Séquences', icon: 'mdi-timeline', color: '#f59e0b' },
    { label: 'Risques/Opport.', icon: 'mdi-alert-lightbulb', color: '#ef4444' },
  ]

  const show = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const categoryMap = {
    pilotage: { label: 'Management', color: 'primary', icon: 'mdi-account-tie' },
    management: { label: 'Management', color: 'primary', icon: 'mdi-account-tie' },
    operationnel: { label: 'Réalisation', color: 'success', icon: 'mdi-cogs' },
    realization: { label: 'Réalisation', color: 'success', icon: 'mdi-cogs' },
    support: { label: 'Support', color: 'info', icon: 'mdi-toolbox' },
  }

  function getCategoryColor (category?: string) {
    return categoryMap[category as keyof typeof categoryMap]?.color || 'grey'
  }

  function getCategoryIcon (category?: string) {
    return categoryMap[category as keyof typeof categoryMap]?.icon || 'mdi-cog'
  }

  function getCategoryLabel (category?: string) {
    return categoryMap[category as keyof typeof categoryMap]?.label || category || 'Non défini'
  }

  function handleClose () {
    emit('update:modelValue', false)
  }

  function handleConfirm () {
    emit('confirm')
  }
</script>

<style scoped>
.preview-text {
  white-space: pre-wrap;
  line-height: 1.6;
  color: #475569;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a7bc8 100%);
}

.bg-gradient-success {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.bg-gradient-info {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.bg-gradient-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.bg-gradient-error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stepper-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  cursor: pointer;
  transition: all 0.3s ease;
}

.step-item:hover .step-circle {
  transform: scale(1.1);
}

.step-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 2;
}

.step-item.active .step-circle {
  transform: scale(1.2);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.step-label {
  margin-top: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  transition: all 0.3s ease;
  text-align: center;
}

.step-item.active .step-label {
  color: #1e293b;
  font-size: 13px;
}

.step-line {
  position: absolute;
  top: 24px;
  left: 50%;
  width: 100%;
  height: 3px;
  background: #e2e8f0;
  z-index: 1;
}
</style>
