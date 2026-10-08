<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-truck-check-outline" title="Fiche satisfaction prestataire">
        <template #subtitle>
          Consultation et mise à jour de l'évaluation prestataire.
        </template>
        <template #actions>
          <v-btn prepend-icon="mdi-arrow-left" variant="text" @click="router.push('/company/performance/surveillance/prestataires')">
            Retour à la liste
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Détail satisfaction</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Satisfaction des prestataires
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Évaluez vos partenaires, consolidez les retours et pilotez les plans d’action.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Score total</span>
                <strong>{{ scoreTotal }}/{{ maxTotalScore }}</strong>
              </div>
              <div class="hero-badge">
                <span>Niveau</span>
                <strong>{{ levelLabel }}</strong>
              </div>
              <div class="hero-badge">
                <span>Période</span>
                <strong>{{ periodLabel }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="data-card" rounded="xl">
        <v-card-text>
          <v-alert v-if="loadError" class="mb-4" type="error" variant="tonal">
            Impossible de charger la fiche demandée.
          </v-alert>

          <v-skeleton-loader v-if="loading" type="article" />

          <v-form v-else @submit.prevent="saveSurvey">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.respondent_name" label="Prestataire" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.respondent_email" label="Email" type="email" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.year" label="Année" type="number" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.period" label="Période" placeholder="Ex: T1" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <CriteriaRatingPanel
                  v-model="form.responses"
                  :criteria="criteria"
                  :loading="loadingCriteria"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.recommendations" label="Recommandations" rows="3" variant="outlined" />
              </v-col>
            </v-row>

            <div class="d-flex justify-end ga-2 mt-4">
              <v-btn variant="text" @click="router.push('/company/performance/surveillance/prestataires')">
                Annuler
              </v-btn>
              <v-btn color="primary" :loading="saving" type="submit">
                Enregistrer
              </v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import CriteriaRatingPanel from '@/modules/clienta/components/performance/CriteriaRatingPanel.vue'
  import { type DynamicCriterion, useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { useToast } from '@/modules/shared/composables/useToast'
  import satisfactionSurveyService from '@/services/satisfactionSurveyService'
  import { getErrorMessage } from '@/utils/errorMessage'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const service = satisfactionSurveyService

  const defaultSupplierCriteria: DynamicCriterion[] = [
    {
      key: 'qualite_prestation',
      label: 'Qualité de prestation',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'respect_delais',
      label: 'Respect des délais',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'reactivite',
      label: 'Réactivité',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'conformite',
      label: 'Conformité',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'communication',
      label: 'Communication',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
  ]

  const { criteria, loadingCriteria } = useEvaluationCriteria(
    'satisfaction_fournisseur',
    defaultSupplierCriteria,
  )

  const loading = ref(true)
  const saving = ref(false)
  const loadError = ref(false)
  const surveyLevel = ref<string | null>(null)

  const form = reactive({
    respondent_name: '',
    respondent_email: '',
    year: new Date().getFullYear(),
    period: '',
    responses: {
      qualite_prestation: 3,
      respect_delais: 3,
      reactivite: 3,
      conformite: 3,
      communication: 3,
    } as Record<string, number>,
    recommendations: '',
  })

  const scoreTotal = computed(() => {
    const values = Object.values(form.responses || {}) as Array<number | string | null | undefined>
    return values.reduce<number>((sum, value) => sum + (Number(value) || 0), 0)
  })
  const maxTotalScore = computed(() => {
    return criteria.value.reduce((sum, criterion) => sum + Number(criterion.scaleMax || 3), 0)
  })

  const levelLabel = computed(() => service.getSatisfactionLabel(surveyLevel.value || undefined))

  const periodLabel = computed(() => {
    const year = form.year ? String(form.year) : '—'
    const period = form.period ? String(form.period) : ''
    return period ? `${period} ${year}` : year
  })

  const routeParams = route.params as Record<string, string | string[] | undefined>
  const surveyId = Number(Array.isArray(routeParams.id) ? routeParams.id[0] : routeParams.id)

  function applySurveyToForm (item: any) {
    form.respondent_name = item.respondent_name || ''
    form.respondent_email = item.respondent_email || ''
    form.year = Number(item.year) || new Date().getFullYear()
    form.period = item.period || ''
    surveyLevel.value = item.satisfaction_level || null
    const source = item.responses || {}
    const nextResponses: Record<string, number> = {}
    for (const criterion of criteria.value) {
      const raw = resolveResponseValue(source, criterion)
      nextResponses[criterion.key] = Number.isFinite(raw) && raw >= criterion.scaleMin && raw <= criterion.scaleMax ? raw : criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin
    }
    form.responses = nextResponses
    form.recommendations = item.recommendations || ''
  }

  function resolveResponseValue (source: Record<string, any>, criterion: DynamicCriterion): number {
    const fromKey = Number(source?.[criterion.key])
    if (Number.isFinite(fromKey)) {
      return fromKey
    }

    const criterionId = Number((criterion as any).id)
    if (Number.isFinite(criterionId) && criterionId > 0) {
      const fromId = Number(source?.[criterionId] ?? source?.[String(criterionId)])
      if (Number.isFinite(fromId)) {
        return fromId
      }
    }

    return Number.NaN
  }

  async function loadSurvey () {
    if (!Number.isFinite(surveyId) || surveyId <= 0) {
      loadError.value = true
      loading.value = false
      return
    }

    loading.value = true
    loadError.value = false
    try {
      const survey = await service.getSurvey(surveyId)
      if (survey?.type === 'supplier') {
        applySurveyToForm(survey)
      } else {
        loadError.value = true
      }
    } catch (error) {
      console.error(error)
      loadError.value = true
    } finally {
      loading.value = false
    }
  }

  async function saveSurvey () {
    if (!form.respondent_name) {
      toast.error('Le nom du prestataire est requis.')
      return
    }

    saving.value = true
    try {
      await service.updateSurvey(surveyId, {
        type: 'supplier',
        year: Number(form.year) || new Date().getFullYear(),
        period: form.period || undefined,
        respondent_name: form.respondent_name,
        respondent_email: form.respondent_email || undefined,
        responses: { ...form.responses },
        recommendations: form.recommendations || undefined,
      })
      toast.success('Fiche mise à jour.')
      await loadSurvey()
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Erreur lors de la mise à jour.'))
    } finally {
      saving.value = false
    }
  }

  watch(criteria, () => {
    const keys = criteria.value.map(item => item.key)
    if (keys.length === 0) return
    const normalized: Record<string, number> = {}
    for (const criterion of criteria.value) {
      const raw = Number(form.responses?.[criterion.key])
      normalized[criterion.key] = Number.isFinite(raw) && raw >= criterion.scaleMin && raw <= criterion.scaleMax
        ? raw
        : (criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin)
    }
    form.responses = normalized
  }, { immediate: true })

  onMounted(loadSurvey)
</script>

<style scoped>
  .hero {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  }

  .hero-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    align-items: center;
    justify-content: space-between;
  }

  .hero-kicker {
    color: rgba(15, 23, 42, 0.6);
  }

  .hero-badges {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    min-width: min(420px, 100%);
  }

  .hero-badge {
    padding: 12px 16px;
    border-radius: 16px;
    border: 1px solid rgba(15, 23, 42, 0.1);
    background: rgba(255, 255, 255, 0.8);
    display: grid;
    gap: 4px;
  }

  .hero-badge span {
    font-size: 12px;
    color: rgba(15, 23, 42, 0.6);
  }

  .hero-badge strong {
    font-size: 18px;
    color: #0f172a;
  }

  .data-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }
</style>
