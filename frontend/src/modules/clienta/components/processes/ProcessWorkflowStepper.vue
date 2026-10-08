<template>
  <v-card variant="outlined">
    <v-card-text>
      <h3 class="text-subtitle-1 mb-4">Workflow de validation</h3>

      <v-stepper
        alt-labels
        hide-actions
        :items="steps"
        :model-value="currentStep"
      >
        <template #item.1>
          <div class="text-center">
            <v-icon :color="currentStep >= 1 ? 'success' : 'grey'" size="large">
              mdi-file-document-edit
            </v-icon>
            <div class="mt-2">
              <div class="text-caption font-weight-bold">Brouillon</div>
              <div v-if="process.created_at" class="text-caption text-medium-emphasis">
                {{ formatDate(process.created_at) }}
              </div>
            </div>
          </div>
        </template>

        <template #item.2>
          <div class="text-center">
            <v-icon :color="currentStep >= 2 ? 'warning' : 'grey'" size="large">
              mdi-eye-check
            </v-icon>
            <div class="mt-2">
              <div class="text-caption font-weight-bold">En révision</div>
              <div v-if="process.verified_at" class="text-caption text-medium-emphasis">
                {{ formatDate(process.verified_at) }}
              </div>
              <div v-if="process.verified_by" class="text-caption text-medium-emphasis">
                Par: {{ getVerifierName() }}
              </div>
            </div>
          </div>
        </template>

        <template #item.3>
          <div class="text-center">
            <v-icon :color="currentStep >= 3 ? 'success' : 'grey'" size="large">
              mdi-check-circle
            </v-icon>
            <div class="mt-2">
              <div class="text-caption font-weight-bold">Actif</div>
              <div v-if="process.validated_at" class="text-caption text-medium-emphasis">
                {{ formatDate(process.validated_at) }}
              </div>
              <div v-if="process.validated_by" class="text-caption text-medium-emphasis">
                Par: {{ getValidatorName() }}
              </div>
            </div>
          </div>
        </template>
      </v-stepper>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    process: any
  }

  const props = defineProps<Props>()

  const steps = [
    { title: 'Brouillon', value: 1 },
    { title: 'En révision', value: 2 },
    { title: 'Actif', value: 3 },
  ]

  const currentStep = computed(() => {
    switch (props.process.status) {
      case 'draft': {
        return 1
      }
      case 'in_review':
      case 'validated': {
        return 2
      }
      case 'active': {
        return 3
      }
      default: {
        return 1
      }
    }
  })

  function formatDate (dateString: string) {
    if (!dateString) return ''
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(date)
  }

  function getVerifierName () {
    // TODO: Get actual user name from verifier
    return 'Vérificateur'
  }

  function getValidatorName () {
    // TODO: Get actual user name from validator
    return 'Validateur'
  }
</script>
