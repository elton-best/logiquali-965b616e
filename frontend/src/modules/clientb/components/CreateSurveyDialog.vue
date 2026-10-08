<template>
  <v-dialog
    max-width="900"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="bg-primary text-white pa-4 d-flex align-center sticky-header">
        <v-icon class="mr-2">mdi-clipboard-text-plus</v-icon>
        Nouvelle Enquête de Satisfaction
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <!-- Site Selection -->
          <v-card class="mb-6" elevation="0" variant="outlined">
            <v-card-text class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-4">
                <v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
                Site concerné
              </h3>
              <v-autocomplete
                v-model="form.site_id"
                v-model:search="siteSearch"
                clearable
                density="comfortable"
                item-title="label"
                item-value="id"
                :items="sitesOptions"
                :loading="sitesLoading"
                placeholder="Rechercher un site..."
                prepend-inner-icon="mdi-magnify"
                :rules="[rules.required]"
                variant="outlined"
              >
                <template #item="{ props: optionProps }">
                  <v-list-item v-bind="optionProps">
                    <template #prepend>
                      <v-icon>mdi-map-marker</v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-card-text>
          </v-card>

          <!-- Satisfaction Rating -->
          <v-card class="mb-6" elevation="0" variant="outlined">
            <v-card-text class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-4">
                <v-icon class="mr-2" color="primary">mdi-star</v-icon>
                Évaluation générale
              </h3>

              <div class="mb-6">
                <label class="text-subtitle-2 font-weight-bold mb-3 d-block">
                  Note globale de satisfaction <span class="text-error">*</span>
                </label>
                <div class="d-flex align-center">
                  <v-slider
                    v-model="form.global_rating"
                    class="flex-grow-1 mr-4"
                    color="primary"
                    :max="10"
                    :min="0"
                    show-ticks
                    :step="1"
                    thumb-label
                    track-color="grey-lighten-2"
                  />
                  <v-chip color="primary" label size="large">
                    {{ form.global_rating }}/10
                  </v-chip>
                </div>
              </div>

              <div class="mb-6">
                <label class="text-subtitle-2 font-weight-bold mb-3 d-block">
                  Qualité du service
                </label>
                <div class="text-center">
                  <v-rating
                    v-model="form.service_quality"
                    active-color="warning"
                    color="warning"
                    hover
                    :length="5"
                    size="large"
                  />
                </div>
              </div>

              <div>
                <label class="text-subtitle-2 font-weight-bold mb-3 d-block">
                  Vitesse de service
                </label>
                <div class="text-center">
                  <v-rating
                    v-model="form.service_speed"
                    active-color="info"
                    color="info"
                    hover
                    :length="5"
                    size="large"
                  />
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Additional Questions -->
          <v-card class="mb-6" elevation="0" variant="outlined">
            <v-card-text class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-4">
                <v-icon class="mr-2" color="primary">mdi-comment-question</v-icon>
                Questions complémentaires
              </h3>

              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Recommanderiez-vous nos services ?
                </label>
                <v-btn-toggle
                  v-model="form.would_recommend"
                  color="primary"
                  divided
                  mandatory
                  variant="outlined"
                >
                  <v-btn prepend-icon="mdi-thumb-up" value="yes">Oui</v-btn>
                  <v-btn prepend-icon="mdi-help" value="maybe">Peut-être</v-btn>
                  <v-btn prepend-icon="mdi-thumb-down" value="no">Non</v-btn>
                </v-btn-toggle>
              </div>

              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Propreté et hygiène
                </label>
                <v-slider
                  v-model="form.cleanliness"
                  color="success"
                  :max="10"
                  :min="1"
                  show-ticks="always"
                  :step="1"
                  tick-size="4"
                >
                  <template #append>
                    <v-chip color="success" size="small">{{ form.cleanliness }}/10</v-chip>
                  </template>
                </v-slider>
              </div>

              <div>
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Rapport qualité/prix
                </label>
                <v-slider
                  v-model="form.value_for_money"
                  color="orange"
                  :max="10"
                  :min="1"
                  show-ticks="always"
                  :step="1"
                  tick-size="4"
                >
                  <template #append>
                    <v-chip color="orange" size="small">{{ form.value_for_money }}/10</v-chip>
                  </template>
                </v-slider>
              </div>
            </v-card-text>
          </v-card>

          <!-- Comments -->
          <v-card elevation="0" variant="outlined">
            <v-card-text class="pa-4">
              <h3 class="text-h6 font-weight-bold mb-4">
                <v-icon class="mr-2" color="primary">mdi-comment-text</v-icon>
                Vos commentaires
              </h3>

              <v-textarea
                v-model="form.recommendations"
                counter
                :maxlength="1000"
                placeholder="Partagez vos suggestions, recommandations ou remarques..."
                rows="5"
                variant="outlined"
              />
            </v-card-text>
          </v-card>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          :disabled="submitting"
          variant="text"
          @click="handleCancel"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :loading="submitting"
          prepend-icon="mdi-send"
          size="large"
          @click="handleSubmit"
        >
          Soumettre l'enquête
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  interface Props {
    modelValue: boolean
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'created'): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()

  const authStore = useAuthStore()
  const toast = useToast()

  const formRef = ref()
  const submitting = ref(false)

  const form = ref({
    site_id: null as number | null,
    global_rating: 7,
    service_quality: 4,
    service_speed: 4,
    cleanliness: 8,
    value_for_money: 7,
    would_recommend: 'yes',
    recommendations: '',
  })

  // Site search
  const siteSearch = ref('')
  const sitesOptions = ref<any[]>([])
  const sitesLoading = ref(false)

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  // Load sites on mount
  watch(() => props.modelValue, newVal => {
    if (newVal) {
      loadSites()
    }
  })

  async function loadSites () {
    try {
      sitesLoading.value = true
      // Utiliser /sites/search au lieu de /sites pour Client B
      const { data } = await api.get('/sites/search', {
        params: { q: '', per_page: 100 },
      })
      const sites = data.data || data || []
      sitesOptions.value = sites.map((site: any) => ({
        id: site.id,
        label: `${site.name} - ${site.city || site.location || ''}`,
        ...site,
      }))
    } catch (error) {
      console.error('Error loading sites:', error)
    } finally {
      sitesLoading.value = false
    }
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    submitting.value = true
    try {
      // Calculate total score (simple average for demo)
      const totalScore = Math.round(
        ((form.value.global_rating * 10)
          + (form.value.service_quality * 20)
          + (form.value.service_speed * 20)
          + (form.value.cleanliness * 10)
          + (form.value.value_for_money * 10)) / 7,
      )

      // Determine satisfaction level based on total score
      let satisfaction_level = 'moderately_satisfied'
      if (totalScore >= 67) satisfaction_level = 'satisfied'
      else if (totalScore >= 34) satisfaction_level = 'moderately_satisfied'
      else satisfaction_level = 'dissatisfied'

      const { data } = await api.post('/satisfaction-surveys', {
        site_id: form.value.site_id,
        type: 'client',
        respondent_name: authStore.user?.name || authStore.user?.username,
        respondent_email: authStore.user?.email,
        responses: {
          global_rating: form.value.global_rating,
          service_quality: form.value.service_quality,
          service_speed: form.value.service_speed,
          cleanliness: form.value.cleanliness,
          value_for_money: form.value.value_for_money,
          would_recommend: form.value.would_recommend,
        },
        total_score: totalScore,
        satisfaction_level: satisfaction_level,
        recommendations: form.value.recommendations,
      })

      if (data.success) {
        toast.success('Enquête soumise avec succès. Merci pour votre retour !')
        emit('created')
        emit('update:modelValue', false)
        resetForm()
      }
    } catch (error: any) {
      console.error('Error submitting survey:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la soumission')
    } finally {
      submitting.value = false
    }
  }

  function handleCancel () {
    emit('update:modelValue', false)
    resetForm()
  }

  function resetForm () {
    form.value = {
      site_id: null,
      global_rating: 7,
      service_quality: 4,
      service_speed: 4,
      cleanliness: 8,
      value_for_money: 7,
      would_recommend: 'yes',
      recommendations: '',
    }
    formRef.value?.reset()
  }
</script>

<style scoped>
.sticky-header {
  position: sticky;
  top: 0;
  z-index: 1;
}
</style>
