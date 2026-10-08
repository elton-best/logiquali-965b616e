<template>
  <ClientBLayout current-page="/clientb/satisfaction-forms">
    <v-container class="pa-6" fluid>
      <!-- En-tête -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-btn
            prepend-icon="mdi-arrow-left"
            variant="text"
            @click="goBack"
          >
            Retour
          </v-btn>
          <h1 class="text-h4 font-weight-bold mt-2">
            {{ isEditMode ? 'Modifier la Fiche' : 'Nouvelle Fiche de Satisfaction' }}
          </h1>
          <p class="text-medium-emphasis">
            Enquête de satisfaction selon le modèle M13-D4
          </p>
        </v-col>
      </v-row>

      <!-- Formulaire -->
      <v-card elevation="2">
        <!-- Message d'en-tête (comme dans M13-D4) -->
        <v-card-text class="pa-6 bg-blue-lighten-5">
          <div class="text-center">
            <v-icon class="mb-3" color="primary" size="48">mdi-clipboard-text</v-icon>
            <h2 class="text-h5 mb-3">Cher client,</h2>
            <p class="text-body-1 mb-2">
              Dans le cadre de l'amélioration de nos prestations, nous souhaitons recueillir
              votre avis quant à la qualité de nos produits et services.
            </p>
            <p class="text-body-2 font-italic">
              Nous vous remercions d'avance pour votre contribution et vous prions d'agréer
              l'expression de nos sincères salutations.
            </p>
          </div>
        </v-card-text>

        <v-divider />

        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <v-card-text class="pa-6">
            <!-- Informations générales -->
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.client_name"
                  :disabled="loading"
                  label="Nom de la structure *"
                  prepend-inner-icon="mdi-office-building"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="form.site_id"
                  :disabled="loading"
                  item-title="name"
                  item-value="id"
                  :items="sites"
                  label="Site *"
                  :loading="loadingSites"
                  prepend-inner-icon="mdi-map-marker"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="form.survey_date"
                  :disabled="loading"
                  label="Date de l'enquête *"
                  prepend-inner-icon="mdi-calendar"
                  :rules="[rules.required]"
                  type="date"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <v-divider class="my-6" />

            <!-- Critères d'évaluation -->
            <div>
              <h3 class="text-h6 mb-4">Critères d'évaluation</h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Veuillez noter chaque critère selon l'échelle suivante
              </p>

              <!-- Légende de l'échelle -->
              <v-card class="mb-6" variant="outlined">
                <v-card-text>
                  <v-row dense>
                    <v-col class="text-center" cols="4">
                      <v-chip color="error" size="small">1</v-chip>
                      <div class="text-caption mt-1">Insatisfait</div>
                    </v-col>
                    <v-col class="text-center" cols="4">
                      <v-chip color="warning" size="small">2</v-chip>
                      <div class="text-caption mt-1">Moyennement satisfait</div>
                    </v-col>
                    <v-col class="text-center" cols="4">
                      <v-chip color="success" size="small">3</v-chip>
                      <div class="text-caption mt-1">Satisfait</div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 1 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Aimabilité et écoute du client *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.amabilite_ecoute"
                        :color="getScoreColor(form.amabilite_ecoute)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.amabilite_ecoute)" size="small">
                            {{ form.amabilite_ecoute }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 2 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Disponibilité / Spontanéité *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.disponibilite_spontaneite"
                        :color="getScoreColor(form.disponibilite_spontaneite)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.disponibilite_spontaneite)" size="small">
                            {{ form.disponibilite_spontaneite }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 3 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Rapidité dans le traitement des requêtes *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.rapidite_traitement"
                        :color="getScoreColor(form.rapidite_traitement)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.rapidite_traitement)" size="small">
                            {{ form.rapidite_traitement }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 4 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Respect des délais de livraison *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.respect_delais"
                        :color="getScoreColor(form.respect_delais)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.respect_delais)" size="small">
                            {{ form.respect_delais }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 5 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Conformité des produits livrés par rapport aux commandes *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.conformite_produits"
                        :color="getScoreColor(form.conformite_produits)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.conformite_produits)" size="small">
                            {{ form.conformite_produits }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Critère 6 -->
              <v-card class="mb-3" variant="outlined">
                <v-card-text>
                  <v-row align="center">
                    <v-col cols="12" md="6">
                      <div class="font-weight-medium">Traitement des réclamations et plaintes *</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-slider
                        v-model="form.traitement_reclamations"
                        :color="getScoreColor(form.traitement_reclamations)"
                        :disabled="loading"
                        max="4"
                        min="1"
                        show-ticks="always"
                        step="1"
                        thumb-label
                        tick-size="4"
                      >
                        <template #append>
                          <v-chip :color="getScoreColor(form.traitement_reclamations)" size="small">
                            {{ form.traitement_reclamations }}
                          </v-chip>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </div>

            <v-divider class="my-6" />

            <!-- Score total (prévisualisation) -->
            <v-card class="mb-6" color="primary" variant="tonal">
              <v-card-text>
                <v-row align="center">
                  <v-col cols="12" md="8">
                    <div class="d-flex align-center">
                      <v-progress-circular
                        :color="satisfactionColor"
                        :model-value="satisfactionPercentage"
                        :size="80"
                        :width="8"
                      >
                        <span class="text-h6">{{ Math.round(satisfactionPercentage) }}%</span>
                      </v-progress-circular>
                      <div class="ml-4">
                        <div class="text-h5 font-weight-bold">Note : {{ totalScore }}/24</div>
                        <div class="text-body-2">
                          Niveau : <strong>{{ satisfactionLabel }}</strong>
                        </div>
                      </div>
                    </div>
                  </v-col>
                  <v-col class="text-center" cols="12" md="4">
                    <v-icon :color="satisfactionColor" size="64">
                      {{ satisfactionIcon }}
                    </v-icon>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Recommandations -->
            <v-textarea
              v-model="form.recommendations"
              :disabled="loading"
              label="Recommandations pour amélioration"
              placeholder="Vos suggestions pour améliorer nos services..."
              prepend-inner-icon="mdi-comment-text-outline"
              rows="4"
              variant="outlined"
            />
          </v-card-text>

          <v-divider />

          <!-- Actions -->
          <v-card-actions class="pa-6">
            <v-btn
              :disabled="loading"
              variant="outlined"
              @click="goBack"
            >
              Annuler
            </v-btn>
            <v-spacer />
            <v-btn
              color="grey"
              :loading="loading"
              prepend-icon="mdi-content-save"
              variant="outlined"
              @click="saveDraft"
            >
              Enregistrer brouillon
            </v-btn>
            <v-btn
              color="primary"
              :loading="loading"
              prepend-icon="mdi-send"
              type="submit"
            >
              Soumettre
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import type { ClientSatisfactionFormData } from '@/services/clientSatisfactionFormService'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { useToast } from '@/composables/useToast'
  import ClientBLayout from '@/modules/clientb/components/ClientBLayout.vue'
  import clientSatisfactionFormService from '@/services/clientSatisfactionFormService'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const formRef = ref()
  const loading = ref(false)
  const loadingSites = ref(false)
  const sites = ref<Array<{ id: number, name: string }>>([])

  const formId = computed(() => Number((route.params as any).id || 0))
  const isEditMode = computed(() => formId.value > 0)

  type SatisfactionFormModel = {
    site_id: number | null
    client_name: string
    survey_date: string
    amabilite_ecoute: number
    disponibilite_spontaneite: number
    rapidite_traitement: number
    respect_delais: number
    conformite_produits: number
    traitement_reclamations: number
    recommendations: string
  }

  const form = ref<SatisfactionFormModel>({
    site_id: null,
    client_name: '',
    survey_date: new Date().toISOString().split('T')[0] || '',
    amabilite_ecoute: 3,
    disponibilite_spontaneite: 3,
    rapidite_traitement: 3,
    respect_delais: 3,
    conformite_produits: 3,
    traitement_reclamations: 3,
    recommendations: '',
  })

  function toPayload (status: ClientSatisfactionFormData['status']): ClientSatisfactionFormData {
    return {
      site_id: Number(form.value.site_id || 0),
      client_name: form.value.client_name,
      survey_date: form.value.survey_date || new Date().toISOString().split('T')[0] || '',
      amabilite_ecoute: form.value.amabilite_ecoute,
      disponibilite_spontaneite: form.value.disponibilite_spontaneite,
      rapidite_traitement: form.value.rapidite_traitement,
      respect_delais: form.value.respect_delais,
      conformite_produits: form.value.conformite_produits,
      traitement_reclamations: form.value.traitement_reclamations,
      recommendations: form.value.recommendations || undefined,
      status,
    }
  }

  function readScore (criteria: Record<string, { label: string, score: number, color: string }>, key: string): number {
    return criteria[key]?.score ?? 3
  }

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  // Calculs automatiques
  const totalScore = computed(() => {
    return (
      form.value.amabilite_ecoute
      + form.value.disponibilite_spontaneite
      + form.value.rapidite_traitement
      + form.value.respect_delais
      + form.value.conformite_produits
      + form.value.traitement_reclamations
    )
  })

  const satisfactionPercentage = computed(() => {
    return (totalScore.value / 24) * 100
  })

  const satisfactionColor = computed(() => {
    const percentage = satisfactionPercentage.value
    if (percentage >= 90) return 'success'
    if (percentage >= 70) return 'info'
    if (percentage >= 50) return 'warning'
    return 'error'
  })

  const satisfactionLabel = computed(() => {
    const percentage = satisfactionPercentage.value
    if (percentage >= 67) return 'Satisfait'
    if (percentage >= 34) return 'Moyennement satisfait'
    return 'Insatisfait'
  })

  const satisfactionIcon = computed(() => {
    const percentage = satisfactionPercentage.value
    if (percentage >= 67) return 'mdi-emoticon-happy'
    if (percentage >= 34) return 'mdi-emoticon-neutral'
    return 'mdi-emoticon-sad'
  })

  function getScoreColor (score: number): string {
    switch (score) {
      case 4: { return 'success'
      }
      case 3: { return 'info'
      }
      case 2: { return 'warning'
      }
      case 1: { return 'error'
      }
      default: { return 'grey'
      }
    }
  }

  async function loadSites () {
    loadingSites.value = true
    try {
      // Utiliser /sites/search au lieu de /sites pour Client B
      const response = await api.get('/sites/search', {
        params: { q: '', per_page: 100 },
      })
      sites.value = response.data.data || response.data
    } catch (error) {
      console.error('Erreur lors du chargement des sites:', error)
      toast.error('Erreur lors du chargement des sites')
    } finally {
      loadingSites.value = false
    }
  }

  async function loadForm () {
    if (!isEditMode.value) return

    loading.value = true
    try {
      const data = await clientSatisfactionFormService.getById(formId.value)
      form.value = {
        site_id: data.site_id || null,
        client_name: data.client_name,
        survey_date: data.survey_date || (new Date().toISOString().split('T')[0] || ''),
        amabilite_ecoute: readScore(data.criteria, 'amabilite_ecoute'),
        disponibilite_spontaneite: readScore(data.criteria, 'disponibilite_spontaneite'),
        rapidite_traitement: readScore(data.criteria, 'rapidite_traitement'),
        respect_delais: readScore(data.criteria, 'respect_delais'),
        conformite_produits: readScore(data.criteria, 'conformite_produits'),
        traitement_reclamations: readScore(data.criteria, 'traitement_reclamations'),
        recommendations: data.recommendations || '',
      }
    } catch (error) {
      console.error('Erreur lors du chargement de la fiche:', error)
      toast.error('Erreur lors du chargement de la fiche')
      goBack()
    } finally {
      loading.value = false
    }
  }

  async function saveDraft () {
    const valid = await formRef.value?.validate()
    if (!valid?.valid) {
      toast.error('Veuillez remplir tous les champs requis')
      return
    }

    loading.value = true
    try {
      const data = toPayload('draft')

      if (isEditMode.value) {
        await clientSatisfactionFormService.update(formId.value, data)
        toast.success('Brouillon mis à jour avec succès')
      } else {
        await clientSatisfactionFormService.create(data)
        toast.success('Brouillon enregistré avec succès')
      }

      goBack()
    } catch (error: any) {
      console.error('Erreur lors de l\'enregistrement:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
    } finally {
      loading.value = false
    }
  }

  async function handleSubmit () {
    const valid = await formRef.value?.validate()
    if (!valid?.valid) {
      toast.error('Veuillez remplir tous les champs requis')
      return
    }

    loading.value = true
    try {
      const data = toPayload('submitted')

      await (isEditMode.value ? clientSatisfactionFormService.update(formId.value, data) : clientSatisfactionFormService.create(data))

      toast.success('Fiche soumise avec succès')
      goBack()
    } catch (error: any) {
      console.error('Erreur lors de la soumission:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la soumission')
    } finally {
      loading.value = false
    }
  }

  function goBack () {
    router.push('/clientb/satisfaction-forms')
  }

  onMounted(() => {
    loadSites()
    if (isEditMode.value) {
      loadForm()
    }
  })
</script>

<style scoped>
  .slider-container {
    margin-bottom: 20px;
  }
</style>
