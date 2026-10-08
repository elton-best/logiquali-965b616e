<template>
  <v-app>
    <v-main class="evaluation-form-bg">
      <v-container class="pa-4" fluid>
        <!-- Loading state -->
        <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 80vh;">
          <v-progress-circular color="primary" indeterminate size="64" />
        </div>

        <!-- Error state -->
        <v-card
          v-else-if="error"
          class="mx-auto mt-8"
          color="error"
          max-width="600"
          variant="tonal"
        >
          <v-card-item>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-alert-circle</v-icon>
              Formulaire non disponible
            </v-card-title>
          </v-card-item>
          <v-card-text>
            <p>{{ errorMessage }}</p>
            <div class="mt-4 d-flex flex-wrap ga-2">
              <v-btn
                v-if="canRequestReset"
                color="white"
                :loading="requestingReset"
                variant="outlined"
                @click="requestNewLink"
              >
                Demander un nouveau lien
              </v-btn>
              <v-btn
                v-if="newGeneratedLink"
                color="white"
                :href="newGeneratedLink"
                target="_blank"
                variant="text"
              >
                Ouvrir le nouveau lien
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <!-- Success state -->
        <v-card v-else-if="submitted" class="mx-auto mt-8" max-width="600">
          <v-card-text class="text-center py-8">
            <v-icon class="mb-4" color="success" size="80">mdi-check-circle</v-icon>
            <h2 class="text-h5 mb-2">Merci pour votre évaluation !</h2>
            <p class="text-body-1 text-medium-emphasis mb-4">
              Votre réponse a bien été enregistrée.
            </p>
            <v-chip color="primary" size="large">
              Score global : {{ submittedScore?.toFixed(1) }}/{{ formData?.criteria[0]?.scale_max || 5 }}
            </v-chip>
            <p class="mt-6 text-body-2 text-medium-emphasis">
              Vous pouvez maintenant fermer cette page.
            </p>
          </v-card-text>
        </v-card>

        <!-- Form -->
        <v-card v-else-if="formData" class="mx-auto" max-width="800">
          <v-card-item>
            <v-card-title class="text-h5">{{ formData.request.title }}</v-card-title>
            <v-card-subtitle v-if="formData.request.instructions">
              {{ formData.request.instructions }}
            </v-card-subtitle>
          </v-card-item>

          <v-divider />

          <v-card-text>
            <v-form ref="formRef" v-model="formValid" @submit.prevent="submitForm">
              <DynamicCriteriaForm
                :comments="response.comments"
                :criteria="sortedCriteria"
                :scores="response.scores"
                :show-comment-for="showCommentFor"
                @toggle-comment="toggleCriterionComment"
                @update-comment="updateCriterionComment"
                @update-score="updateCriterionScore"
              />

              <!-- Global comment -->
              <v-divider class="mb-4" />
              <h4 class="text-body-1 font-weight-medium mb-2">Commentaire général</h4>
              <v-textarea
                v-model="response.global_comment"
                counter
                label="Partagez vos impressions générales (optionnel)"
                maxlength="1000"
                rows="4"
                variant="outlined"
              />

              <!-- Submit -->
              <div class="d-flex justify-end mt-4">
                <v-btn
                  color="primary"
                  :disabled="!allCriteriaRated"
                  :loading="submitting"
                  size="large"
                  type="submit"
                >
                  <v-icon start>mdi-send</v-icon>
                  Soumettre mon évaluation
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>

        <!-- Footer -->
        <div class="text-center mt-8 text-body-2 text-medium-emphasis">
          Propulsé par BestQHSE
        </div>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import evaluationRequestsApi, { type PublicFormData } from '@/api/services/evaluationRequests'
  import DynamicCriteriaForm from '@/components/forms/DynamicCriteriaForm.vue'

  const route = useRoute()
  const token = computed(() => {
    const params = route.params as Record<string, string | string[] | undefined>
    const value = params.token
    return Array.isArray(value) ? (value[0] || '') : (value || '')
  })

  const loading = ref(true)
  const error = ref(false)
  const errorMessage = ref('')
  const formData = ref<PublicFormData | null>(null)
  const formRef = ref()
  const formValid = ref(false)
  const submitting = ref(false)
  const submitted = ref(false)
  const submittedScore = ref<number | null>(null)
  const canRequestReset = ref(false)
  const requestingReset = ref(false)
  const newGeneratedLink = ref('')

  const response = reactive({
    scores: {} as Record<number, number>,
    comments: {} as Record<number, string>,
    global_comment: '',
  })

  const showCommentFor = reactive<Record<number, boolean>>({})

  const sortedCriteria = computed(() => {
    if (!formData.value) return []
    return [...formData.value.criteria].toSorted((a, b) => a.display_order - b.display_order)
  })

  const allCriteriaRated = computed(() => {
    if (!formData.value) return false
    return formData.value.criteria.every(
      c => response.scores[c.id] !== undefined && response.scores[c.id] >= c.scale_min,
    )
  })

  function updateCriterionScore (criterionId: number, value: number): void {
    response.scores[criterionId] = value
  }

  function updateCriterionComment (criterionId: number, value: string): void {
    response.comments[criterionId] = value
  }

  function toggleCriterionComment (criterionId: number): void {
    showCommentFor[criterionId] = !showCommentFor[criterionId]
  }

  async function loadForm () {
    loading.value = true
    error.value = false
    canRequestReset.value = false
    newGeneratedLink.value = ''

    try {
      const { data } = await evaluationRequestsApi.public.getForm(token.value)
      formData.value = data

      // Initialize scores with minimum values
      for (const criterion of data.criteria) {
        response.scores[criterion.id] = criterion.scale_min
      }
    } catch (error_: any) {
      error.value = true
      if (error_.response?.status === 404) {
        errorMessage.value = 'Ce lien d\'évaluation n\'existe pas ou a expiré.'
      } else if (error_.response?.status === 410) {
        errorMessage.value = 'Cette évaluation a expiré ou a été annulée.'
        canRequestReset.value = true
      } else {
        errorMessage.value = error_.response?.data?.message || 'Une erreur est survenue lors du chargement du formulaire.'
      }
    } finally {
      loading.value = false
    }
  }

  async function submitForm () {
    if (!formRef.value || !allCriteriaRated.value) return

    submitting.value = true

    try {
      // Filter out empty comments
      const comments: Record<number, string> = {}
      for (const [id, comment] of Object.entries(response.comments)) {
        if (comment?.trim()) {
          comments[Number(id)] = comment.trim()
        }
      }

      const { data } = await evaluationRequestsApi.public.submit(token.value, {
        scores: response.scores,
        comments: Object.keys(comments).length > 0 ? comments : undefined,
        global_comment: response.global_comment?.trim() || undefined,
      })

      submitted.value = true
      submittedScore.value = data.overall_score
    } catch (error_: any) {
      error.value = true
      errorMessage.value = error_.response?.data?.message || 'Une erreur est survenue lors de l\'envoi.'
    } finally {
      submitting.value = false
    }
  }

  async function requestNewLink () {
    requestingReset.value = true

    try {
      const { data } = await evaluationRequestsApi.public.requestResetLink(token.value)
      errorMessage.value = data.message
      newGeneratedLink.value = data.new_public_url || ''
      canRequestReset.value = false
    } catch (error_: any) {
      errorMessage.value = error_.response?.data?.message || 'Impossible de générer un nouveau lien.'
    } finally {
      requestingReset.value = false
    }
  }

  onMounted(loadForm)
</script>

<style scoped>
.evaluation-form-bg {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
  min-height: 100vh;
}
</style>
