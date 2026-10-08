<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-4">
      <h3 class="text-h6">
        <v-icon class="mr-2">mdi-chart-line</v-icon>
        Indicateurs de Performance (KPI)
      </h3>
      <v-btn
        v-if="canEdit"
        color="primary"
        prepend-icon="mdi-plus"
        size="small"
        variant="outlined"
        @click="showAddDialog = true"
      >
        Ajouter un KPI
      </v-btn>
    </div>

    <!-- KPI Cards -->
    <v-row v-if="indicators && indicators.length > 0">
      <v-col
        v-for="indicator in indicators"
        :key="indicator.id"
        cols="12"
        md="6"
      >
        <v-card :color="getKPICardColor(indicator)" variant="tonal">
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-3">
              <div>
                <div class="text-overline text-medium-emphasis">
                  {{ getTypeLabel(indicator.type) }}
                </div>
                <h4 class="text-h6 font-weight-bold">{{ indicator.name }}</h4>
              </div>
              <v-chip
                :color="getCategoryColor(indicator.category)"
                size="small"
              >
                {{ getCategoryLabel(indicator.category) }}
              </v-chip>
            </div>

            <!-- Current Value Display -->
            <div class="d-flex align-center gap-4 mb-3">
              <div class="flex-1-1">
                <div class="text-caption text-medium-emphasis">Valeur actuelle</div>
                <div class="text-h4 font-weight-bold">
                  {{ getCurrentValue(indicator) }} {{ indicator.unit }}
                </div>
              </div>
              <v-divider vertical />
              <div class="flex-1-1">
                <div class="text-caption text-medium-emphasis">Objectif</div>
                <div class="text-h5 font-weight-medium">
                  {{ indicator.target_value || 'N/A' }} {{ indicator.unit }}
                </div>
              </div>
            </div>

            <!-- Progress Bar -->
            <v-progress-linear
              v-if="indicator.target_value"
              :color="getProgressColor(indicator)"
              height="8"
              :model-value="calculateProgress(indicator)"
              rounded
            />

            <!-- Performance Badge -->
            <div class="mt-3 d-flex justify-space-between align-center">
              <v-chip
                :color="getPerformanceColor(indicator)"
                prepend-icon="mdi-trending-up"
                size="small"
                variant="flat"
              >
                {{ getPerformanceLabel(indicator) }}
              </v-chip>
              <div class="text-caption text-medium-emphasis">
                <v-icon size="16">mdi-account</v-icon>
                {{ indicator.responsible_user?.name || 'Non assigné' }}
              </div>
            </div>
          </v-card-text>

          <v-card-actions v-if="canEdit">
            <v-spacer />
            <v-btn
              icon="mdi-chart-box"
              size="small"
              variant="text"
              @click="viewDetails(indicator)"
            />
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="editIndicator(indicator)"
            />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteIndicator(indicator)"
            />
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-alert v-else type="info" variant="tonal">
      <div class="text-center py-4">
        <v-icon size="48">mdi-chart-line-variant</v-icon>
        <div class="text-body-1 mt-2">Aucun indicateur défini</div>
        <v-btn
          v-if="canEdit"
          class="mt-3"
          color="primary"
          prepend-icon="mdi-plus"
          size="small"
          variant="outlined"
          @click="showAddDialog = true"
        >
          Créer un indicateur
        </v-btn>
      </div>
    </v-alert>

    <!-- Add/Edit Indicator Dialog -->
    <v-dialog v-model="showAddDialog" max-width="600">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ editingIndicator ? 'Modifier' : 'Ajouter' }} un KPI</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form" @submit.prevent="saveIndicator">
            <v-text-field
              v-model="formData.name"
              label="Nom de l'indicateur *"
              :rules="[v => !!v || 'Requis']"
              variant="outlined"
            />

            <v-select
              v-model="formData.type"
              :items="typeOptions"
              label="Type *"
              :rules="[v => !!v || 'Requis']"
              variant="outlined"
            />

            <v-select
              v-model="formData.category"
              :items="categoryOptions"
              label="Catégorie *"
              :rules="[v => !!v || 'Requis']"
              variant="outlined"
            />

            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model="formData.unit"
                  label="Unité *"
                  placeholder="%, €, jours..."
                  :rules="[v => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model.number="formData.target_value"
                  label="Valeur cible"
                  type="number"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <v-textarea
              v-model="formData.description"
              label="Description"
              rows="3"
              variant="outlined"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="saveIndicator"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import type { ProcessIndicator } from '@/services/processService'
  import { ref } from 'vue'

  interface ExtendedProcessIndicator extends ProcessIndicator {
    current_value?: number
    description?: string
    responsible_user?: {
      name?: string
    }
  }

  interface Props {
    indicators?: ExtendedProcessIndicator[]
    processId: number
    canEdit?: boolean
  }

  withDefaults(defineProps<Props>(), {
    indicators: () => [],
    canEdit: true,
  })

  const emit = defineEmits(['add', 'update', 'delete', 'view-details'])

  const showAddDialog = ref(false)
  const editingIndicator = ref<ExtendedProcessIndicator | null>(null)
  const formData = ref({
    name: '',
    type: '',
    category: '',
    unit: '',
    target_value: null as number | null,
    description: '',
  })

  const typeOptions = [
    { title: 'Efficacité', value: 'efficacite' },
    { title: 'Efficience', value: 'efficience' },
    { title: 'Conformité', value: 'conformite' },
    { title: 'Performance', value: 'performance' },
  ]

  const categoryOptions = [
    { title: 'Qualité', value: 'qualite' },
    { title: 'Environnement', value: 'environnement' },
    { title: 'Santé & Sécurité', value: 'sante_securite' },
    { title: 'Global', value: 'global' },
  ]

  function getTypeLabel (type: string) {
    return typeOptions.find(t => t.value === type)?.title || type
  }

  function getCategoryLabel (category: string) {
    return categoryOptions.find(c => c.value === category)?.title || category
  }

  function getCategoryColor (category: string) {
    const colors = {
      qualite: 'primary',
      environnement: 'success',
      sante_securite: 'error',
      global: 'info',
    }
    return colors[category as keyof typeof colors] || 'grey'
  }

  function getCurrentValue (indicator: ExtendedProcessIndicator): number {
    if (typeof indicator.current_value === 'number') return indicator.current_value
    return indicator.values?.[0]?.value ?? 0
  }

  function getKPICardColor (indicator: ExtendedProcessIndicator) {
    const progress = calculateProgress(indicator)
    if (progress >= 90) return 'success'
    if (progress >= 70) return 'warning'
    return 'error'
  }

  function calculateProgress (indicator: ExtendedProcessIndicator) {
    const currentValue = getCurrentValue(indicator)
    if (!indicator.target_value || !currentValue) return 0
    return Math.min(100, (currentValue / indicator.target_value) * 100)
  }

  function getProgressColor (indicator: ExtendedProcessIndicator) {
    const progress = calculateProgress(indicator)
    if (progress >= 90) return 'success'
    if (progress >= 70) return 'warning'
    return 'error'
  }

  function getPerformanceLabel (indicator: ExtendedProcessIndicator) {
    const progress = calculateProgress(indicator)
    if (progress >= 90) return 'Excellent'
    if (progress >= 70) return 'Correct'
    if (progress >= 50) return 'À améliorer'
    return 'Critique'
  }

  function getPerformanceColor (indicator: ExtendedProcessIndicator) {
    const progress = calculateProgress(indicator)
    if (progress >= 90) return 'success'
    if (progress >= 70) return 'warning'
    return 'error'
  }

  function editIndicator (indicator: ExtendedProcessIndicator) {
    editingIndicator.value = indicator
    formData.value = {
      name: indicator.name,
      type: indicator.type,
      category: indicator.category,
      unit: indicator.unit,
      target_value: indicator.target_value ?? null,
      description: indicator.description || '',
    }
    showAddDialog.value = true
  }

  function deleteIndicator (indicator: ExtendedProcessIndicator) {
    if (confirm(`Supprimer l'indicateur "${indicator.name}" ?`)) {
      emit('delete', indicator)
    }
  }

  function viewDetails (indicator: ExtendedProcessIndicator) {
    emit('view-details', indicator)
  }

  function saveIndicator () {
    if (editingIndicator.value) {
      emit('update', editingIndicator.value.id, formData.value)
    } else {
      emit('add', formData.value)
    }
    closeDialog()
  }

  function closeDialog () {
    showAddDialog.value = false
    editingIndicator.value = null
    formData.value = {
      name: '',
      type: '',
      category: '',
      unit: '',
      target_value: null,
      description: '',
    }
  }
</script>

<style scoped>
.flex-1-1 {
  flex: 1 1 auto;
}
</style>
