<template>
  <v-card class="standard-action-block mb-4" rounded="lg" variant="outlined">
    <v-card-title class="d-flex align-center justify-space-between py-3 px-4 bg-grey-lighten-4">
      <div class="d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-clipboard-check-outline</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ title || 'Synthèse & Décision / Plan d\'action' }}</span>
      </div>
      <v-chip
        v-if="value.statut"
        density="comfortable"
        size="small"
        :color="getStatusColor(value.statut)"
        variant="flat"
      >
        {{ getStatusLabel(value.statut) }}
      </v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-4">
      <!-- 1. Synthèse / Observations -->
      <v-row dense>
        <v-col cols="12">
          <label class="text-caption font-weight-bold text-grey-darken-2 mb-1 d-block">
            Synthèse / Observations
          </label>
          <v-textarea
            v-model="value.synthese"
            auto-grow
            density="comfortable"
            :disabled="readonly"
            hide-details="auto"
            placeholder="Synthétisez les constats, points forts et axes d'amélioration..."
            rounded="lg"
            rows="2"
            variant="outlined"
            @update:model-value="emitChange"
          />
        </v-col>

        <!-- 2. Décision -->
        <v-col cols="12" class="mt-2">
          <label class="text-caption font-weight-bold text-grey-darken-2 mb-1 d-block">
            Décision retenue
          </label>
          <v-textarea
            v-model="value.decision"
            auto-grow
            density="comfortable"
            :disabled="readonly"
            hide-details="auto"
            placeholder="Décision actée lors de la revue..."
            rounded="lg"
            rows="2"
            variant="outlined"
            @update:model-value="emitChange"
          />
        </v-col>
      </v-row>

      <!-- Section Action liée -->
      <v-divider class="my-3" />

      <div class="d-flex align-center justify-space-between mb-2">
        <span class="text-subtitle-2 font-weight-bold text-primary">
          <v-icon size="small" class="mr-1">mdi-flag-outline</v-icon>
          Action associée
        </span>
        <v-switch
          v-if="!readonly"
          v-model="hasAction"
          color="primary"
          density="compact"
          hide-details
          label="Générer une action"
        />
      </div>

      <v-expand-transition>
        <div v-if="hasAction || readonly && value.action_title">
          <v-row dense>
            <!-- Intitulé de l'action -->
            <v-col cols="12" md="12">
              <v-text-field
                v-model="value.action_title"
                density="comfortable"
                :disabled="readonly"
                hide-details="auto"
                label="Intitulé de l'action *"
                placeholder="Action concrète à réaliser..."
                prepend-inner-icon="mdi-format-title"
                rounded="lg"
                variant="outlined"
                @update:model-value="emitChange"
              />
            </v-col>

            <!-- Responsable (Contract C1) -->
            <v-col cols="12" md="4">
              <v-select
                v-model="value.responsable_id"
                density="comfortable"
                :disabled="readonly"
                hide-details="auto"
                item-title="name"
                item-value="id"
                :items="collaborators"
                label="Responsable *"
                prepend-inner-icon="mdi-account"
                rounded="lg"
                variant="outlined"
                @update:model-value="emitChange"
              />
            </v-col>

            <!-- Délai / Échéance (Contract C1) -->
            <v-col cols="12" md="4">
              <v-text-field
                v-model="value.delai"
                density="comfortable"
                :disabled="readonly"
                hide-details="auto"
                label="Délai / Échéance *"
                prepend-inner-icon="mdi-calendar"
                rounded="lg"
                type="date"
                variant="outlined"
                @update:model-value="emitChange"
              />
            </v-col>

            <!-- Norme(s) concernée(s) (RT-11: visible si >= 2 normes) -->
            <v-col v-if="isMultiNorm" cols="12" md="4">
              <v-select
                v-model="value.normes"
                chips
                closable-chips
                density="comfortable"
                :disabled="readonly"
                hide-details="auto"
                :items="availableNorms"
                label="Norme(s) concernée(s) *"
                multiple
                prepend-inner-icon="mdi-shield-check"
                rounded="lg"
                variant="outlined"
                @update:model-value="emitChange"
              />
            </v-col>
          </v-row>
        </div>
      </v-expand-transition>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export interface StandardActionValue {
  synthese?: string
  decision?: string
  action_title?: string
  action_description?: string
  responsable_id?: number | null
  responsable_name?: string
  delai?: string | null
  normes?: string[]
  statut?: 'a_faire' | 'en_cours' | 'realisee' | 'en_retard' | 'replanifiee'
}

const props = withDefaults(
  defineProps<{
    modelValue: StandardActionValue
    title?: string
    collaborators?: Array<{ id: number; name: string }>
    availableNorms?: string[]
    readonly?: boolean
  }>(),
  {
    title: '',
    collaborators: () => [],
    availableNorms: () => [],
    readonly: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [val: StandardActionValue]
  save: [val: StandardActionValue]
}>()

const value = ref<StandardActionValue>({ ...props.modelValue })
const hasAction = ref(Boolean(props.modelValue.action_title || props.modelValue.responsable_id || props.modelValue.delai))

watch(
  () => props.modelValue,
  newVal => {
    value.value = { ...newVal }
    if (newVal.action_title || newVal.responsable_id || newVal.delai) {
      hasAction.value = true
    }
  },
  { deep: true },
)

const isMultiNorm = computed(() => props.availableNorms.length >= 2)

function emitChange() {
  emit('update:modelValue', value.value)
  emit('save', value.value)
}

function getStatusColor(statut?: string): string {
  switch (statut) {
    case 'realisee': return 'success'
    case 'en_cours': return 'warning'
    case 'en_retard': return 'error'
    case 'replanifiee': return 'info'
    default: return 'grey'
  }
}

function getStatusLabel(statut?: string): string {
  switch (statut) {
    case 'realisee': return 'Réalisée'
    case 'en_cours': return 'En cours'
    case 'en_retard': return 'En retard'
    case 'replanifiee': return 'Replanifiée'
    default: return 'À faire'
  }
}
</script>

<style scoped>
.standard-action-block {
  background-color: #fff;
  border-color: rgba(var(--v-border-color), 0.15);
}
</style>

