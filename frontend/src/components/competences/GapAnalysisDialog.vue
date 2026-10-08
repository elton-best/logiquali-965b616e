<template>
  <v-dialog v-model="dialog" max-width="800px">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-avatar class="mr-3">{{ user?.name?.charAt(0) }}</v-avatar>
        <div>
          <div class="text-h6">{{ user?.name }}</div>
          <div class="text-caption text-medium-emphasis">
            {{ competence ? `Compétence: ${competence.name}` : 'Analyse complète des écarts' }}
          </div>
        </div>
      </v-card-title>

      <v-card-text>
        <v-row v-if="loading">
          <v-col class="text-center" cols="12">
            <v-progress-circular indeterminate />
          </v-col>
        </v-row>

        <div v-else-if="gapAnalysis">
          <!-- Summary Stats -->
          <v-row class="mb-4">
            <v-col cols="4">
              <v-card color="success" variant="tonal">
                <v-card-text class="text-center">
                  <div class="text-h4">{{ gapAnalysis.summary.acquired }}</div>
                  <div class="text-caption">Acquises</div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="4">
              <v-card color="warning" variant="tonal">
                <v-card-text class="text-center">
                  <div class="text-h4">{{ gapAnalysis.summary.gaps }}</div>
                  <div class="text-caption">Écarts</div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="4">
              <v-card color="error" variant="tonal">
                <v-card-text class="text-center">
                  <div class="text-h4">{{ gapAnalysis.summary.missing }}</div>
                  <div class="text-caption">Manquantes</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Competence Details -->
          <v-expansion-panels v-if="!competence" multiple>
            <v-expansion-panel
              v-for="category in gapAnalysis.categories"
              :key="category.name"
              :title="category.name"
            >
              <v-expansion-panel-text>
                <div v-for="item in category.competences" :key="item.id" class="mb-3">
                  <CompetenceGapItem :item="item" />
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>

          <!-- Single Competence Detail -->
          <div v-else>
            <CompetenceGapItem detailed :item="singleCompetenceGap" />
          </div>

          <!-- Recommendations -->
          <v-card v-if="gapAnalysis.recommendations?.length" class="mt-4" variant="tonal">
            <v-card-title class="text-h6">Recommandations</v-card-title>
            <v-card-text>
              <v-list>
                <v-list-item
                  v-for="(rec, index) in gapAnalysis.recommendations"
                  :key="index"
                  :prepend-icon="rec.priority === 'high' ? 'mdi-alert' : 'mdi-information'"
                >
                  <v-list-item-title>{{ rec.title }}</v-list-item-title>
                  <v-list-item-subtitle>{{ rec.description }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Fermer</v-btn>
        <v-btn
          v-if="!competence"
          color="primary"
          :loading="generatingPlan"
          @click="generateTrainingPlan"
        >
          Générer plan de formation
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import { competenceService } from '@/services/competenceService'
  import CompetenceGapItem from './CompetenceGapItem.vue'

  const props = defineProps<{
    modelValue: boolean
    user?: any
    competence?: any
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
  }>()

  const dialog = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const loading = ref(false)
  const generatingPlan = ref(false)
  const gapAnalysis = ref(null)

  const singleCompetenceGap = computed(() => {
    if (!props.competence || !gapAnalysis.value) return null

    return gapAnalysis.value.categories
      .flatMap(cat => cat.competences)
      .find(comp => comp.id === props.competence.id)
  })

  async function loadGapAnalysis () {
    if (!props.user?.id) return

    loading.value = true
    try {
      gapAnalysis.value = await competenceService.getGapAnalysis(props.user.id)
    } finally {
      loading.value = false
    }
  }

  async function generateTrainingPlan () {
    generatingPlan.value = true
    try {
      const plan = await competenceService.generateTrainingPlan(props.user.id)
      // Handle plan download or display
      console.log('Training plan:', plan)
    } finally {
      generatingPlan.value = false
    }
  }

  function close () {
    dialog.value = false
  }

  watch(() => props.user, loadGapAnalysis, { immediate: true })
</script>
