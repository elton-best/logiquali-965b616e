<template>
  <ClientALayout current-page="iso-performance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-emoticon-happy-outline" title="Satisfaction">
        <template #subtitle>
          Enquêtes de satisfaction clients par site (suivi ISO §9.1.2).
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            color="secondary"
            prepend-icon="mdi-tune-vertical"
            variant="outlined"
            @click="goToCriteria"
          >
            Définir critères
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouvelle enquête
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">ISO 9.1.2</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Satisfaction client par site
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Consolidez les enquêtes, mesurez la tendance et déclenchez les plans d’amélioration.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Total fiches</span>
                <strong>{{ stats.total_forms || 0 }}</strong>
              </div>
              <div class="hero-badge">
                <span>Score moyen</span>
                <strong>{{ (stats.average_percentage || 0).toFixed(1) }}%</strong>
              </div>
              <div class="hero-badge">
                <span>Niveau dominant</span>
                <strong>{{ topLevelLabel }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="filters-card" rounded="xl" variant="tonal">
        <v-card-text class="pb-0">
          <v-row dense>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                clearable
                hide-details
                label="Rechercher (ref, client, statut)"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                clearable
                hide-details
                :items="statusFilterItems"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.level"
                clearable
                hide-details
                :items="levelFilterItems"
                label="Niveau"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center justify-end" cols="12" md="2">
              <v-btn
                :disabled="!hasActiveFilters"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <v-card class="mt-4 data-card" rounded="xl">
        <v-card-text>
          <v-data-table :headers="headers" :items="filteredForms" items-per-page="10" :loading="loading">
            <template #[`item.satisfaction_level_label`]="{ item }">
              <v-chip :color="item.satisfaction_color" size="small" variant="tonal">
                {{ item.satisfaction_level_label }}
              </v-chip>
            </template>
            <template #[`item.actions`]="{ item }">
              <v-tooltip v-if="item.status === 'draft'" location="top" text="Soumettre la fiche">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-send"
                    size="small"
                    variant="text"
                    @click="submitForm(item.id)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip v-if="item.status === 'submitted'" location="top" text="Valider la fiche">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-eye-check"
                    size="small"
                    variant="text"
                    @click="reviewForm(item.id)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Télécharger PDF">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-file-pdf-box"
                    size="small"
                    variant="text"
                    @click="downloadPdf(item.id)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Vérifier document">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="warning"
                    :disabled="submittingForVerification"
                    icon="mdi-shield-check"
                    size="small"
                    variant="text"
                    @click="submitExportForVerification(item.id)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Supprimer">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="error"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="removeForm(item.id)"
                  />
                </template>
              </v-tooltip>
            </template>
            <template #no-data>
              <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showDialog" max-width="920" persistent>
        <v-card class="modern-modal" rounded="xl">
          <v-card-title class="modal-title pa-0">
            <div class="modal-title-inner d-flex align-center justify-space-between">
              <div>
                <div class="text-overline mb-1">Fiche de satisfaction</div>
                <div class="text-h6 font-weight-bold">Nouvelle enquête client</div>
              </div>
              <v-chip color="primary" size="small" variant="flat">
                Critères par formulaire
              </v-chip>
            </div>
          </v-card-title>
          <v-card-text class="px-6 pt-6">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.client_name" label="Client" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <AppDatePickerField v-model="form.survey_date" label="Date enquête" mode="date" />
              </v-col>

              <v-col cols="12">
                <v-alert
                  v-if="usesFallbackCriteria"
                  border="start"
                  color="warning"
                  density="comfortable"
                  icon="mdi-alert-outline"
                  variant="tonal"
                >
                  Les critères personnalisés de ce formulaire n'ont pas été trouvés. Les critères par défaut sont appliqués.
                </v-alert>
              </v-col>

              <v-col v-for="criterion in renderedCriteria" :key="criterion.fieldKey" cols="12" md="6">
                <v-select
                  v-model.number="form[criterion.fieldKey]"
                  item-title="title"
                  item-value="value"
                  :items="ratingOptions1to4"
                  :label="criterion.label"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.recommendations" label="Recommandations" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="createForm">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { type DynamicCriterion, useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { useRatingOptions } from '@/modules/clienta/composables/useRatingOptions'
  import { useToast } from '@/modules/shared/composables/useToast'
  import clientSatisfactionFormService from '@/services/clientSatisfactionFormService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()
  const { ratingOptions1to4 } = useRatingOptions()

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const forms = ref<any[]>([])
  const stats = ref<any>({})
  const exportedDocsByFormId = ref<Record<number, { id: number, code: string }>>({})
  const submittingForVerification = ref(false)

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Client', key: 'client_name' },
    { title: 'Date', key: 'survey_date_formatted' },
    { title: 'Score %', key: 'satisfaction_percentage' },
    { title: 'Niveau', key: 'satisfaction_level_label' },
    { title: 'Statut', key: 'status_label' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const satisfactionFieldKeys = [
    'amabilite_ecoute',
    'disponibilite_spontaneite',
    'rapidite_traitement',
    'respect_delais',
    'conformite_produits',
    'traitement_reclamations',
  ] as const

  const satisfactionFallbackCriteria: DynamicCriterion[] = [
    { key: 'amabilite_ecoute', label: 'Amabilité et écoute', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
    { key: 'disponibilite_spontaneite', label: 'Disponibilité / Spontanéité', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
    { key: 'rapidite_traitement', label: 'Rapidité dans le traitement', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
    { key: 'respect_delais', label: 'Respect des délais', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
    { key: 'conformite_produits', label: 'Conformité des produits / services', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
    { key: 'traitement_reclamations', label: 'Traitement des réclamations', description: null, scaleMin: 1, scaleMax: 4, weight: 1, required: true, options: ratingOptions1to4 },
  ]

  const { criteria: criteriaCatalog, usesFallbackCriteria } = useEvaluationCriteria(
    'satisfaction_client',
    satisfactionFallbackCriteria,
  )

  const renderedCriteria = computed(() => {
    return satisfactionFieldKeys.map((fieldKey, index) => ({
      fieldKey,
      label: criteriaCatalog.value[index]?.label || satisfactionFallbackCriteria[index]?.label || fieldKey,
    }))
  })

  const form = reactive<any>({
    client_name: '',
    survey_date: '',
    amabilite_ecoute: 3,
    disponibilite_spontaneite: 3,
    rapidite_traitement: 3,
    respect_delais: 3,
    conformite_produits: 3,
    traitement_reclamations: 3,
    recommendations: '',
  })

  const filters = ref({
    search: '',
    status: null as string | null,
    level: null as string | null,
  })

  const statusFilterItems = computed(() => {
    const values = new Set(
      forms.value
        .map((item: any) => String(item.status_label || '').trim())
        .filter(Boolean),
    )
    return Array.from(values).toSorted((a, b) => a.localeCompare(b))
  })

  const levelFilterItems = computed(() => {
    const values = new Set(
      forms.value
        .map((item: any) => String(item.satisfaction_level_label || '').trim())
        .filter(Boolean),
    )
    return Array.from(values).toSorted((a, b) => a.localeCompare(b))
  })

  const topLevelLabel = computed(() => {
    if (!Array.isArray(forms.value) || forms.value.length === 0) {
      return '—'
    }
    const counts = new Map<string, number>()
    for (const item of forms.value) {
      const label = String(item.satisfaction_level_label || '').trim()
      if (!label) continue
      counts.set(label, (counts.get(label) || 0) + 1)
    }
    if (counts.size === 0) return '—'
    return [...counts.entries()].toSorted((a, b) => b[1] - a[1])[0]?.[0] || '—'
  })

  const filteredForms = computed(() => {
    const query = filters.value.search.trim().toLowerCase()

    return forms.value.filter((item: any) => {
      const matchesSearch
        = !query
          || [
            item.ref,
            item.client_name,
            item.status_label,
            item.satisfaction_level_label,
          ]
            .map(value => String(value || '').toLowerCase())
            .some(value => value.includes(query))

      const matchesStatus = !filters.value.status || String(item.status_label || '').trim() === filters.value.status
      const matchesLevel = !filters.value.level || String(item.satisfaction_level_label || '').trim() === filters.value.level

      return matchesSearch && matchesStatus && matchesLevel
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(filters.value.search.trim() || filters.value.status || filters.value.level)
  })

  const emptyStateMessage = computed(() => {
    if (forms.value.length === 0) {
      return 'Aucune fiche de satisfaction disponible pour ce site.'
    }

    return 'Aucune fiche ne correspond aux filtres sélectionnés.'
  })

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    const resolved = authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
    return Number.isFinite(Number(resolved)) && Number(resolved) > 0 ? Number(resolved) : null
  }

  function resetForm () {
    form.client_name = ''
    form.survey_date = ''
    form.amabilite_ecoute = 3
    form.disponibilite_spontaneite = 3
    form.rapidite_traitement = 3
    form.respect_delais = 3
    form.conformite_produits = 3
    form.traitement_reclamations = 3
    form.recommendations = ''
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: null,
      level: null,
    }
  }

  function openCreateDialog () {
    resetForm()
    showDialog.value = true
  }

  async function loadForms () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      forms.value = []
      stats.value = {}
      return
    }

    loading.value = true
    try {
      const list = await clientSatisfactionFormService.getAll({ site_id: siteId, per_page: 100 })
      forms.value = list?.data || []
      stats.value = await clientSatisfactionFormService.getStatistics({ site_id: siteId })
    } catch (error) {
      console.error(error)
      forms.value = []
    } finally {
      loading.value = false
    }
  }

  async function createForm () {
    const siteId = getCurrentSiteId()
    if (!siteId || !form.client_name || !form.survey_date) {
      toast.error('Site, client et date sont requis.')
      return
    }

    saving.value = true
    try {
      await clientSatisfactionFormService.create({
        site_id: siteId,
        client_name: form.client_name,
        survey_date: form.survey_date,
        amabilite_ecoute: Number(form.amabilite_ecoute),
        disponibilite_spontaneite: Number(form.disponibilite_spontaneite),
        rapidite_traitement: Number(form.rapidite_traitement),
        respect_delais: Number(form.respect_delais),
        conformite_produits: Number(form.conformite_produits),
        traitement_reclamations: Number(form.traitement_reclamations),
        recommendations: form.recommendations || undefined,
        status: 'draft',
      })
      showDialog.value = false
      await loadForms()
      toast.success('Fiche de satisfaction créée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Erreur lors de la création.'))
    } finally {
      saving.value = false
    }
  }

  async function submitForm (id: number) {
    try {
      await clientSatisfactionFormService.submit(id)
      await loadForms()
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible de soumettre la fiche.'))
    }
  }

  async function reviewForm (id: number) {
    try {
      await clientSatisfactionFormService.review(id)
      await loadForms()
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible de valider la fiche.'))
    }
  }

  async function removeForm (id: number) {
    if (!confirm('Supprimer cette fiche ?')) return
    try {
      await clientSatisfactionFormService.delete(id)
      await loadForms()
      toast.success('Fiche supprimée.')
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Suppression impossible.'))
    }
  }

  async function downloadPdf (id: number, download = true): Promise<boolean> {
    try {
      const { blob, generatedDocumentId } = await clientSatisfactionFormService.exportPdfWithMeta(id)
      if (generatedDocumentId) {
        const documentResponse = await api.get(`/documents/${generatedDocumentId}`)
        const code = String(documentResponse.data?.data?.code || documentResponse.data?.code || '')
        exportedDocsByFormId.value[id] = { id: generatedDocumentId, code }
      }
      if (download) {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `satisfaction-${id}.pdf`)
        document.body.append(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      }
      return Boolean(generatedDocumentId)
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible de télécharger le PDF.'))
      return false
    }
  }

  async function submitExportForVerification (formId: number) {
    const exported = exportedDocsByFormId.value[Number(formId)]
    if (!exported?.id || !exported?.code) {
      const form = forms.value.find(item => Number(item.id) === Number(formId))
      if (!form) {
        toast.error('Formulaire introuvable.')
        return
      }
      const generated = await downloadPdf(Number(formId), false)
      if (!generated) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    const documentToSubmit = exportedDocsByFormId.value[Number(formId)]
    if (!documentToSubmit?.id || !documentToSubmit?.code) return
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${documentToSubmit.id}/confirm-code`, {
        needs_verification: true,
        confirmed_code: documentToSubmit.code,
      })
      toast.success('Document envoyé pour vérification.')
      delete exportedDocsByFormId.value[Number(formId)]
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Échec de la soumission pour vérification.'))
    } finally {
      submittingForVerification.value = false
    }
  }

  function goToCriteria () {
    router.push('/company/performance/criteria?form_type=satisfaction_client')
  }

  onMounted(loadForms)
  watch(() => authStore.currentSiteId, loadForms)
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

  .filters-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .data-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .modern-modal {
    border: 1px solid rgba(15, 23, 42, 0.1);
    backdrop-filter: blur(10px);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .modal-title {
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background:
      radial-gradient(140% 180% at 0% -10%, rgba(14, 116, 144, 0.16), transparent 60%),
      radial-gradient(120% 160% at 100% 0%, rgba(59, 130, 246, 0.14), transparent 55%),
      linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
  }

  .modal-title-inner {
    width: 100%;
    padding: 20px 24px;
  }
</style>
