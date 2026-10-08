<template>
  <ClientALayout current-page="management-reviews">
    <v-container class="pa-6 review-form-theme" fluid>
      <PageHeader icon="mdi-clipboard-text-clock" title="Revues de Direction">
        <template #subtitle>
          Planification, suivi, collecte automatique et décisions (ISO 9001 §9.3).
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openCreateDialog">
            Nouvelle revue
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="mt-6 registry-shell" rounded="xl">
        <v-card-text>
          <v-alert
            v-if="nextUpcomingReview"
            class="mb-4"
            density="comfortable"
            type="warning"
            variant="tonal"
          >
            Revue à venir le {{ formatDate(nextUpcomingReview.planned_date || nextUpcomingReview.scheduled_date) }}:
            <strong>{{ nextUpcomingReview.title || nextUpcomingReview.ref }}</strong>
          </v-alert>

          <v-row class="mb-2 filters-row" dense>
            <v-col cols="12" md="3">
              <AppSelect
                v-model="filters.status"
                label="Statut"
                :options="[
                  { label: 'Tous les statuts', value: '' },
                  ...statusOptions.map(item => ({ label: item.label, value: item.value })),
                ]"
                placeholder="Tous les statuts"
              />
            </v-col>
            <v-col cols="12" md="3">
              <AppSelect
                v-model="filters.quarter"
                label="Trimestre"
                :options="[
                  { label: 'Tous les trimestres', value: '' },
                  ...quarterOptions.map(item => ({ label: item, value: item })),
                ]"
                placeholder="Tous les trimestres"
              />
            </v-col>
            <v-col cols="12" md="3">
              <AppInput
                v-model="filters.year"
                label="Année"
                placeholder="Ex: 2026"
                type="number"
              />
            </v-col>
            <v-col class="d-flex align-center" cols="12" md="3">
              <v-btn color="primary" :loading="reviewStore.loading" @click="loadReviews">Filtrer</v-btn>
            </v-col>
          </v-row>

          <v-data-table
            :headers="headers as any"
            :items="reviews"
            items-per-page="10"
            :loading="reviewStore.loading"
          >
            <template #[`item.planned_date`]="{ item }">{{ formatDate(item.planned_date || item.scheduled_date) }}</template>
            <template #[`item.period`]="{ item }">{{ getPeriodLabel(item) }}</template>
            <template #[`item.chairman`]="{ item }">{{ getChairmanName(item) }}</template>
            <template #[`item.participants`]="{ item }">
              <v-chip color="primary" size="small" variant="tonal">
                {{ getParticipantsCount(item) }} participant(s)
              </v-chip>
            </template>
            <template #[`item.status`]="{ item }">
              <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
                {{ getStatusLabel(item.status) }}
              </v-chip>
            </template>
            <template #[`item.report`]="{ item }">
              <v-chip
                :color="item.report_path || item.status === 'completed' ? 'success' : 'grey'"
                size="small"
                variant="tonal"
              >
                {{ item.report_path || item.status === 'completed' ? 'Disponible' : 'À générer' }}
              </v-chip>
            </template>
            <template #[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-end ga-1">
                <v-tooltip location="top" text="Générer les données d'entrée">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-database-refresh"
                      size="small"
                      variant="text"
                      @click="generateData(item.id)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Envoyer les invitations aux participants">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-email-send-outline"
                      size="small"
                      variant="text"
                      @click="sendReviewInvitations(item)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Ouvrir la revue">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      @click="viewReview(item.id)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Prévisualiser avant export DOCX (RT-01)">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-file-word"
                      size="small"
                      variant="text"
                      @click="openExportPreview(item)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Vérifier document">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      color="warning"
                      icon="mdi-shield-check"
                      size="small"
                      variant="text"
                      @click="submitExportForVerification(item.id)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Supprimer la revue">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      color="error"
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      @click="removeReview(item.id)"
                    />
                  </template>
                </v-tooltip>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showCreateDialog" max-width="780">
        <v-card class="review-create-modal" rounded="xl">
          <v-card-title class="dialog-head pa-5">
            <div>
              <div class="dialog-kicker">Planification</div>
              <div class="text-h6 font-weight-bold">Créer une revue de direction</div>
            </div>
            <v-btn icon="mdi-close" size="small" variant="text" @click="showCreateDialog = false" />
          </v-card-title>
          <v-card-text class="px-6 pb-2">
            <div class="section-caption mb-3">Informations générales</div>
            <v-row>
              <v-col cols="12" md="6">
                <AppInput v-model="form.title" label="Titre" placeholder="Ex: Revue Direction Q2 2026" />
              </v-col>
              <v-col cols="12" md="3">
                <AppSelect
                  v-model="form.quarter"
                  label="Trimestre"
                  :options="quarterOptions.map(item => ({ label: item, value: item }))"
                />
              </v-col>
              <v-col cols="12" md="3">
                <AppInput v-model="form.year" label="Année" type="number" />
              </v-col>
              <v-col cols="12" md="6">
                <AppDatePickerField v-model="form.planned_date" label="Date planifiée" mode="date" />
              </v-col>
              <v-col cols="12" md="6">
                <AppSelect
                  v-model="form.chairman_id"
                  label="Président de séance"
                  :options="users.map(user => ({ label: user.name, value: user.id }))"
                />
              </v-col>
              <v-col cols="12">
                <div class="section-caption mb-2">Participants et points à suivre</div>
                <v-select
                  v-model="form.participants"
                  chips
                  class="field-vuetify"
                  item-title="name"
                  item-value="id"
                  :items="users"
                  label="Participants"
                  multiple
                  placeholder="Sélectionner les participants"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <AppTextarea
                  v-model="form.previous_actions_status"
                  label="Statut actions précédentes"
                  placeholder="Résumez l'état des actions de la revue précédente..."
                  :rows="2"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showCreateDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="createReview">Créer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Prévisualisation avant export (RT-01, Contract C9) -->
      <PreviewExportModal
        v-model="previewModalOpen"
        :blob="previewDocBlob"
        file-type="docx"
        :filename="previewFilename"
        :metadata="previewMetadata"
        title="Prévisualisation avant export — Rapport Revue de direction (DOCX)"
        @download="confirmDownloadExport"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import AppInput from '@/components/common/AppInput.vue'
  import AppSelect from '@/components/common/AppSelect.vue'
  import AppTextarea from '@/components/common/AppTextarea.vue'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'
  import { getErrorMessage } from '@/utils/errorMessage'
  import ClientALayout from '../../components/ClientALayout.vue'
  import PageHeader from '../../components/PageHeader.vue'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const authStore = useAuthStore()
  const reviewStore = useManagementReviewStore()
  const { users, fetchUsers } = useUsers()

  const showCreateDialog = ref(false)
  const saving = ref(false)
  const exportedDocsByReviewId = ref<Record<number, { id: number, code: string }>>({})

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Titre', key: 'title' },
    { title: 'Date', key: 'planned_date' },
    { title: 'Période', key: 'period' },
    { title: 'Responsable', key: 'chairman' },
    { title: 'Participants', key: 'participants', sortable: false },
    { title: 'Statut', key: 'status' },
    { title: 'Rapport', key: 'report', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
  ]

  const statusOptions = [
    { value: 'planned', label: 'Planifiée' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'completed', label: 'Terminée' },
    { value: 'reported', label: 'Reportée' },
  ]
  const quarterOptions = ['Q1', 'Q2', 'Q3', 'Q4']

  const filters = reactive({
    status: '',
    quarter: '',
    year: '',
  })

  const form = reactive({
    title: '',
    planned_date: '',
    year: new Date().getFullYear(),
    quarter: 'Q1',
    chairman_id: null as number | null,
    participants: [] as number[],
    previous_actions_status: '',
  })

  const reviews = computed(() => reviewStore.reviews)
  const nextUpcomingReview = computed(() => reviewStore.nextReview)

  function formatDate (date?: string) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getStatusColor (status: string) {
    if (status === 'completed') return 'success'
    if (status === 'in_progress') return 'warning'
    if (status === 'reported') return 'info'
    return 'primary'
  }

  function getStatusLabel (status: string) {
    return statusOptions.find(s => s.value === status)?.label || status
  }

  function getParticipantsCount (item: any) {
    return Array.isArray(item?.participants) ? item.participants.length : 0
  }

  function getPeriodLabel (item: any) {
    const year = item?.year || '-'
    const quarter = item?.quarter || '-'
    return `${quarter} ${year}`.trim()
  }

  function getChairmanName (item: any) {
    return item?.chairman?.attributes?.name || item?.chairman?.name || '-'
  }

  async function loadReviews () {
    if (!authStore.currentSiteId) return
    await reviewStore.fetchReviews(1, {
      site_id: authStore.currentSiteId,
      status: filters.status || undefined,
      quarter: filters.quarter || undefined,
      year: filters.year || undefined,
    })
  }

  function openCreateDialog () {
    if (!authStore.currentSiteId) {
      toast.error('Sélectionnez un site avant de créer une revue.')
      return
    }
    showCreateDialog.value = true
  }

  async function createReview () {
    if (!authStore.currentSiteId || !form.planned_date) {
      toast.error('Site et date planifiée sont requis.')
      return
    }

    saving.value = true
    try {
      const review = await reviewStore.createReview({
        site_id: authStore.currentSiteId,
        title: form.title || `Revue ${form.quarter} ${form.year}`,
        planned_date: form.planned_date,
        scheduled_date: form.planned_date,
        year: form.year,
        quarter: form.quarter,
        chairman_id: form.chairman_id || undefined,
        participants: form.participants,
        previous_actions_status: form.previous_actions_status || undefined,
        status: 'planned',
      })

      toast.success('Revue créée avec succès.')
      showCreateDialog.value = false
      form.title = ''
      form.planned_date = ''
      form.participants = []
      form.previous_actions_status = ''

      if (review?.id) {
        router.push(`/company/management-reviews/${review.id}`)
      }
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error))
    } finally {
      saving.value = false
    }
  }

  function viewReview (id?: number) {
    if (id) router.push(`/company/management-reviews/${id}`)
  }

  async function generateData (id?: number) {
    if (!id) return false
    try {
      await reviewStore.generateInputData(id)
      toast.success('Données de revue générées.')
      await loadReviews()
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error))
    }
  }

  const previewModalOpen = ref(false)
  const previewDocBlob = ref<Blob | null>(null)
  const previewFilename = ref('')
  const previewMetadata = ref<any>({})
  const selectedReviewIdForExport = ref<number | null>(null)

  function openExportPreview (review: any) {
    if (!review?.id) return
    selectedReviewIdForExport.value = review.id
    previewFilename.value = `Rapport_Revue_Direction_${review.id}_${new Date().toISOString().split('T')[0]}.docx`
    previewMetadata.value = {
      summaryItems: [
        { label: 'Titre', value: review.title || `Revue #${review.id}` },
        { label: 'Statut', value: getStatusLabel(review.status) },
        { label: 'Période', value: `${review.quarter || 'Q1'} ${review.year || new Date().getFullYear()}` },
        { label: 'Responsable', value: review.chairman?.name || 'Président désigné' },
      ],
    }
    previewModalOpen.value = true
  }

  async function confirmDownloadExport () {
    if (!selectedReviewIdForExport.value) return
    await exportDocx(selectedReviewIdForExport.value, true)
    previewModalOpen.value = false
  }

  async function exportDocx (id?: number, download = true): Promise<boolean> {
    if (!id) return false
    try {
      const exportResult = await reviewStore.exportReportDocx(id, download)
      const generatedDocumentId = Number(exportResult?.generatedDocumentId || 0)
      if (generatedDocumentId > 0) {
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        const code = String(docResponse.data?.data?.code || docResponse.data?.code || '')
        exportedDocsByReviewId.value[id] = { id: generatedDocumentId, code }
      }
      return generatedDocumentId > 0
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error))
      return false
    }
  }

  async function submitExportForVerification (reviewId?: number) {
    if (!reviewId) return
    const exported = exportedDocsByReviewId.value[reviewId]
    if (!exported?.id || !exported?.code) {
      const generated = await exportDocx(reviewId, false)
      if (!generated) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    const documentToSubmit = exportedDocsByReviewId.value[reviewId]
    if (!documentToSubmit?.id || !documentToSubmit?.code) return
    try {
      await api.post(`/documents/${documentToSubmit.id}/confirm-code`, {
        needs_verification: true,
        confirmed_code: documentToSubmit.code,
      })
      toast.success('Document envoyé pour vérification.')
      delete exportedDocsByReviewId.value[reviewId]
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Échec de la soumission pour vérification.'))
    }
  }

  async function sendReviewInvitations (item: any) {
    if (!item?.id) return
    try {
      await reviewStore.sendInvitations(item.id, Array.isArray(item.participants) ? item.participants : [])
      toast.success('Invitations envoyées.')
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible d’envoyer les invitations.'))
    }
  }

  async function removeReview (id?: number) {
    if (!id) return
    if (!confirm('Supprimer cette revue ?')) return

    try {
      await reviewStore.deleteReview(id)
      toast.success('Revue supprimée.')
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error))
    }
  }

  watch(() => authStore.currentSiteId, loadReviews)
  watch(
    () => route.query.openCreate,
    value => {
      if (value === '1' || value === 'true') {
        openCreateDialog()
        router.replace({ query: { ...route.query, openCreate: undefined } })
      }
    },
    { immediate: true },
  )

  onMounted(async () => {
    await fetchUsers({ per_page: 200 })
    await loadReviews()
  })
</script>

<style scoped>
.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(900px 320px at 0% -8%, rgba(10, 132, 255, 0.06), transparent 60%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
}

.filters-row {
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(248, 250, 252, 0.72);
  border: 1px solid rgba(148, 163, 184, 0.22);
}

.dialog-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.25);
  background:
    radial-gradient(420px 120px at 0% 0%, rgba(37, 99, 235, 0.14), transparent 70%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.95));
}

.dialog-kicker {
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 700;
}

.section-caption {
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #475569;
  font-weight: 700;
}

.field-vuetify :deep(.v-field.v-field--variant-outlined) {
  border-radius: 12px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
}

.review-form-theme :deep(.input-container),
.review-form-theme :deep(.select-container),
.review-form-theme :deep(.textarea-container),
.review-form-theme :deep(.v-field.v-field--variant-outlined) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
  border-color: rgba(148, 163, 184, 0.55) !important;
}

.review-form-theme :deep(.input-field),
.review-form-theme :deep(.select-field),
.review-form-theme :deep(.textarea-field),
.review-form-theme :deep(.v-field__input) {
  color: #0f172a !important;
}

.review-form-theme :deep(.input-field::placeholder),
.review-form-theme :deep(.select-field::placeholder),
.review-form-theme :deep(.textarea-field::placeholder),
.review-form-theme :deep(.v-field__input::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.input-label),
.review-form-theme :deep(.select-label),
.review-form-theme :deep(.textarea-label),
.review-form-theme :deep(.date-label),
.review-form-theme :deep(.v-label),
.review-form-theme :deep(.field-label) {
  color: #334155 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.v-field-label),
.review-form-theme :deep(.v-field-label--floating) {
  color: #475569 !important;
  opacity: 1 !important;
}

/* Modal de planification (teleported): forcer le thème clair des champs */
.review-create-modal :deep(.input-container),
.review-create-modal :deep(.select-container),
.review-create-modal :deep(.textarea-container),
.review-create-modal :deep(.v-field.v-field--variant-outlined) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
  border-color: rgba(148, 163, 184, 0.55) !important;
}

.review-create-modal :deep(.input-field),
.review-create-modal :deep(.select-field),
.review-create-modal :deep(.textarea-field),
.review-create-modal :deep(.v-field__input) {
  color: #0f172a !important;
}

.review-create-modal :deep(.v-field__input::placeholder),
.review-create-modal :deep(.input-field::placeholder),
.review-create-modal :deep(.select-field::placeholder),
.review-create-modal :deep(.textarea-field::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
}

.review-create-modal :deep(.v-chip) {
  background: rgba(241, 245, 249, 0.95) !important;
  color: #0f172a !important;
}

.review-create-modal :deep(.input-label),
.review-create-modal :deep(.select-label),
.review-create-modal :deep(.textarea-label),
.review-create-modal :deep(.date-label),
.review-create-modal :deep(.v-label) {
  color: #334155 !important;
  opacity: 1 !important;
}

.review-create-modal :deep(.v-field-label),
.review-create-modal :deep(.v-field-label--floating) {
  color: #475569 !important;
  opacity: 1 !important;
}
</style>
