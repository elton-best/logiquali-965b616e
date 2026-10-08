<template>
  <v-dialog max-width="800" :model-value="modelValue" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card v-if="survey">
      <v-card-title class="bg-primary text-white pa-4 d-flex align-center">
        <v-icon class="mr-2">mdi-clipboard-text</v-icon>
        Détails de l'enquête
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <v-row>
          <v-col cols="12" md="6">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-1">Référence</div>
              <div class="text-h6 font-weight-medium">{{ survey.ref }}</div>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-1">Date</div>
              <div>{{ formatDate(survey.created_at) }}</div>
            </div>
          </v-col>

          <v-col cols="12">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-1">Site</div>
              <div class="d-flex align-center">
                <v-icon class="mr-2" color="primary" size="18">mdi-map-marker</v-icon>
                {{ survey.site?.name || 'N/A' }}
              </div>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-1">Score global</div>
              <v-chip
                v-if="survey.total_score"
                :color="getScoreColor(survey.total_score)"
                label
                size="large"
              >
                {{ survey.total_score }}/100
              </v-chip>
              <span v-else class="text-medium-emphasis">-</span>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-1">Niveau de satisfaction</div>
              <v-chip
                :color="getSatisfactionColor(survey.satisfaction_level)"
                size="large"
              >
                <v-icon start>{{ getSatisfactionIcon(survey.satisfaction_level) }}</v-icon>
                {{ getSatisfactionLabel(survey.satisfaction_level) }}
              </v-chip>
            </div>
          </v-col>

          <v-col v-if="survey.recommendations" cols="12">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-2">Recommandations</div>
              <v-card class="pa-4" variant="outlined">
                <p class="text-body-2 mb-0" style="white-space: pre-wrap;">{{ survey.recommendations }}</p>
              </v-card>
            </div>
          </v-col>

          <v-col v-if="survey.responses" cols="12">
            <div class="mb-4">
              <div class="text-caption text-medium-emphasis mb-2">Réponses</div>
              <v-card class="pa-4" variant="outlined">
                <pre class="text-body-2">{{ JSON.stringify(survey.responses, null, 2) }}</pre>
              </v-card>
            </div>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">
          Fermer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  interface Survey {
    [key: string]: any
    id: number
    ref: string
    site_id: number
    site?: any
    total_score?: number
    satisfaction_level: string
    created_at: string
    recommendations?: string
    responses?: any
  }

  interface Props {
    modelValue: boolean
    survey: Survey | null
  }

  defineProps<Props>()
  defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  function getSatisfactionColor (level: string) {
    const colors: Record<string, string> = {
      satisfied: 'success',
      moderately_satisfied: 'warning',
      dissatisfied: 'error',
    }
    return colors[level] || 'grey'
  }

  function getSatisfactionIcon (level: string) {
    const icons: Record<string, string> = {
      satisfied: 'mdi-emoticon-happy',
      moderately_satisfied: 'mdi-emoticon-neutral',
      dissatisfied: 'mdi-emoticon-sad',
    }
    return icons[level] || 'mdi-emoticon'
  }

  function getSatisfactionLabel (level: string) {
    const labels: Record<string, string> = {
      satisfied: 'Satisfait',
      moderately_satisfied: 'Moyennement satisfait',
      dissatisfied: 'Insatisfait',
    }
    return labels[level] || level
  }

  function getScoreColor (score: number) {
    if (score >= 80) return 'success'
    if (score >= 60) return 'info'
    if (score >= 40) return 'warning'
    return 'error'
  }

  function formatDate (dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
</script>
