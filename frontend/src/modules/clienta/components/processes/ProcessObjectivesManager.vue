<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <span>Objectifs du Processus</span>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        size="small"
        @click="showDialog = true"
      >
        Ajouter un objectif
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-alert v-if="objectives.length === 0" class="mb-4" type="info" variant="tonal">
        Aucun objectif défini. Ajoutez des objectifs pour mesurer la performance de ce processus.
      </v-alert>

      <!-- Objectives List -->
      <v-row>
        <v-col
          v-for="objective in objectives"
          :key="objective.id"
          cols="12"
          md="6"
        >
          <v-card variant="outlined">
            <v-card-title class="d-flex align-center justify-space-between">
              <div class="d-flex align-center gap-2">
                <v-icon :color="getStatusColor(objective.status)">
                  {{ getStatusIcon(objective.status) }}
                </v-icon>
                <span class="text-body-1">{{ objective.title }}</span>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-1">
                <v-btn
                  icon="mdi-pencil"
                  size="x-small"
                  variant="text"
                  @click="editObjective(objective)"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="x-small"
                  variant="text"
                  @click="deleteObjective(objective)"
                />
              </div>
            </v-card-title>

            <v-card-text>
              <!-- Description -->
              <p v-if="objective.description" class="text-body-2 mb-3">
                {{ objective.description }}
              </p>

              <!-- Indicator Link -->
              <v-chip
                v-if="objective.indicator"
                class="mb-3"
                color="info"
                prepend-icon="mdi-chart-line"
                size="small"
              >
                {{ objective.indicator.name }}
              </v-chip>

              <!-- Progress -->
              <div class="mb-2">
                <div class="d-flex justify-space-between mb-1">
                  <span class="text-caption">Progression</span>
                  <span class="text-caption font-weight-bold">
                    {{ objective.achievement_percentage }}%
                  </span>
                </div>
                <v-progress-linear
                  :color="getProgressColor(objective.achievement_percentage)"
                  height="8"
                  :model-value="objective.achievement_percentage"
                  rounded
                />
              </div>

              <!-- Target & Date -->
              <v-row dense>
                <v-col v-if="objective.target_value" cols="6">
                  <div class="text-caption text-medium-emphasis">Cible</div>
                  <div class="text-body-2 font-weight-medium">
                    {{ objective.target_value }}
                  </div>
                </v-col>
                <v-col v-if="objective.target_date" cols="6">
                  <div class="text-caption text-medium-emphasis">Échéance</div>
                  <div class="text-body-2 font-weight-medium">
                    {{ formatDate(objective.target_date) }}
                  </div>
                </v-col>
              </v-row>

              <!-- Status Badge -->
              <v-chip
                class="mt-2"
                :color="getStatusColor(objective.status)"
                size="small"
              >
                {{ getStatusLabel(objective.status) }}
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="showDialog" max-width="700">
      <v-card>
        <v-card-title>
          {{ editingObjective ? 'Modifier l\'objectif' : 'Nouvel objectif' }}
        </v-card-title>

        <v-card-text>
          <v-form ref="formRef">
            <v-text-field
              v-model="formData.title"
              label="Titre de l'objectif *"
              placeholder="Ex: Réduire le temps de traitement"
              :rules="[rules.required]"
              variant="outlined"
            />

            <v-textarea
              v-model="formData.description"
              class="mt-3"
              label="Description"
              placeholder="Décrivez l'objectif en détail..."
              rows="3"
              variant="outlined"
            />

            <v-autocomplete
              v-model="formData.indicator_id"
              class="mt-3"
              item-title="name"
              item-value="id"
              :items="availableIndicators"
              label="Indicateur associé *"
              placeholder="Sélectionner un indicateur"
              :rules="[rules.required]"
              variant="outlined"
            >
              <template #item="{ props: optionProps, item }">
                <v-list-item v-bind="optionProps">
                  <template #prepend>
                    <v-icon>mdi-chart-line</v-icon>
                  </template>
                  <template #subtitle>
                    {{ item.raw.code }}
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>

            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.target_value"
                  label="Valeur cible"
                  placeholder="Ex: 90"
                  type="number"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <AppDatePickerField v-model="formData.target_date" label="Date d'échéance" mode="date" />
              </v-col>
            </v-row>

            <v-slider
              v-if="editingObjective"
              v-model="formData.achievement_percentage"
              class="mt-3"
              :color="getProgressColor(formData.achievement_percentage)"
              label="Progression (%)"
              :max="100"
              :min="0"
              show-ticks="always"
              :step="5"
              thumb-label
            />

            <v-select
              v-if="editingObjective"
              v-model="formData.status"
              class="mt-3"
              :items="statusOptions"
              label="Statut"
              variant="outlined"
            />
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeDialog">Annuler</v-btn>
          <v-btn color="primary" @click="saveObjective">
            {{ editingObjective ? 'Modifier' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
  import type { ProcessObjective } from '@/services/processService'
  import { computed, ref } from 'vue'

  interface Props {
    processId: number
    objectives: ProcessObjective[]
    indicators?: any[]
  }

  interface Emits {
    (e: 'create' | 'update' | 'delete', objective: ProcessObjective | Partial<ProcessObjective>): void
  }
  interface ObjectiveFormData {
    title: string
    description: string
    indicator_id: number | null
    target_value: number | null
    target_date: string
    achievement_percentage: number
    status: 'not_started' | 'in_progress' | 'achieved' | 'failed'
  }

  const props = withDefaults(defineProps<Props>(), {
    indicators: () => [],
  })

  const emit = defineEmits<Emits>()

  const showDialog = ref(false)
  const editingObjective = ref<ProcessObjective | null>(null)
  const formRef = ref()

  const formData = ref<ObjectiveFormData>({
    title: '',
    description: '',
    indicator_id: null,
    target_value: null,
    target_date: '',
    achievement_percentage: 0,
    status: 'not_started',
  })

  const statusOptions = [
    { title: 'Non démarré', value: 'not_started' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Atteint', value: 'achieved' },
    { title: 'Échoué', value: 'failed' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  // Filter out already used indicators (1-to-1 relationship)
  const availableIndicators = computed(() => {
    const usedIds = new Set(props.objectives
      .filter(obj => obj.id !== editingObjective.value?.id)
      .map(obj => obj.indicator_id))
    return props.indicators.filter(ind => !usedIds.has(ind.id))
  })

  function getStatusIcon (status: string): string {
    switch (status) {
      case 'not_started': { return 'mdi-circle-outline'
      }
      case 'in_progress': { return 'mdi-progress-clock'
      }
      case 'achieved': { return 'mdi-check-circle'
      }
      case 'failed': { return 'mdi-close-circle'
      }
      default: { return 'mdi-help-circle'
      }
    }
  }

  function getStatusColor (status: string): string {
    switch (status) {
      case 'not_started': { return 'grey'
      }
      case 'in_progress': { return 'info'
      }
      case 'achieved': { return 'success'
      }
      case 'failed': { return 'error'
      }
      default: { return 'grey'
      }
    }
  }

  function getStatusLabel (status: string): string {
    const option = statusOptions.find(opt => opt.value === status)
    return option?.title || status
  }

  function getProgressColor (percentage: number): string {
    if (percentage >= 100) return 'success'
    if (percentage >= 75) return 'info'
    if (percentage >= 50) return 'warning'
    return 'error'
  }

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function editObjective (objective: ProcessObjective) {
    editingObjective.value = objective
    formData.value = {
      title: objective.title,
      description: objective.description || '',
      indicator_id: objective.indicator_id,
      target_value: objective.target_value || null,
      target_date: objective.target_date || '',
      achievement_percentage: objective.achievement_percentage,
      status: objective.status,
    }
    showDialog.value = true
  }

  function deleteObjective (objective: ProcessObjective) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet objectif ?')) {
      emit('delete', objective)
    }
  }

  async function saveObjective () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    const objectiveData: Partial<ProcessObjective> = {
      ...formData.value,
      process_id: props.processId,
      indicator_id: formData.value.indicator_id ?? undefined,
      target_value: formData.value.target_value ?? undefined,
    }

    if (editingObjective.value) {
      emit('update', { ...editingObjective.value, ...objectiveData })
    } else {
      emit('create', objectiveData)
    }

    closeDialog()
  }

  function closeDialog () {
    showDialog.value = false
    editingObjective.value = null
    formData.value = {
      title: '',
      description: '',
      indicator_id: null,
      target_value: null,
      target_date: '',
      achievement_percentage: 0,
      status: 'not_started',
    }
    formRef.value?.resetValidation()
  }
</script>
