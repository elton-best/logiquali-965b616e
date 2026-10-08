<template>
  <ClientALayout>
    <PageHeader
      icon="mdi-school"
      subtitle="Gestion du plan annuel de formation"
      title="Compétences & Formations"
    >
      <template #actions>
        <template v-if="sectionTab === 'formations'">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            variant="flat"
            @click="openCreateDialog"
          >
            Nouvelle formation
          </v-btn>
        </template>
        <template v-else>
          <v-btn
            color="success"
            prepend-icon="mdi-file-excel"
            variant="tonal"
            @click="showImportDialog = true"
          >
            Importer plan annuel
          </v-btn>
          <v-btn
            color="info"
            :disabled="!selectedPlan"
            prepend-icon="mdi-sync"
            variant="tonal"
            @click="recalculateSelectedPlan"
          >
            Recalculer le plan
          </v-btn>
          <v-btn
            color="success"
            :disabled="!selectedPlan"
            prepend-icon="mdi-file-excel"
            variant="tonal"
            @click="exportSelectedPlan"
          >
            Exporter plan
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            variant="flat"
            @click="openPlanDialog"
          >
            Nouveau plan annuel
          </v-btn>
        </template>
      </template>
    </PageHeader>

    <v-card class="mb-6" elevation="1">
      <v-tabs v-model="sectionTab" color="primary" density="comfortable">
        <v-tab value="plan">
          <v-icon
            class="me-1"
            size="small"
          >mdi-clipboard-text-clock-outline</v-icon>
          Plan annuel
        </v-tab>
        <v-tab value="formations">
          <v-icon class="me-1" size="small">mdi-school</v-icon>
          Formations
        </v-tab>
      </v-tabs>
    </v-card>

    <template v-if="sectionTab === 'formations'">
      <FormationStats class="mb-6" :stats="stats" />

      <FormationFiltersBar
        :collaborator-options="collaboratorOptions"
        :filters="filters"
        :frequency-options="frequencyOptions"
        :status-options="statusOptions"
        :year-options="yearOptions"
      />

      <v-alert v-if="hasActiveFilters" class="mb-4" type="info" variant="tonal">
        Filtres actifs. Certaines formations peuvent être masquées.
        <v-btn class="ms-2" size="small" variant="text" @click="resetFilters">
          Réinitialiser
        </v-btn>
      </v-alert>

      <FormationViewToolbar
        v-model:view-mode="viewMode"
        :count="filteredFormations.length"
      />

      <v-row v-if="viewMode === 'grid'">
        <v-col
          v-for="formation in filteredFormations"
          :key="formation.id"
          cols="12"
          lg="4"
          md="6"
        >
          <FormationCard
            :formation="formation"
            @complete="openCompleteDialog"
            @delete="deleteFormation"
            @edit="openEditDialog"
            @reschedule="openRescheduleDialog"
            @track="openTrackingForFormation"
            @view="openDetailDialog"
          />
        </v-col>
      </v-row>

      <FormationTable
        v-if="viewMode === 'table'"
        :get-target-labels="getTargetLabels"
        :headers="tableHeaders"
        :items="filteredFormations"
        @delete="deleteFormation"
        @edit="openEditDialog"
        @track="openTrackingForFormation"
        @view="openDetailDialog"
      />

      <v-card v-if="viewMode === 'calendar'" elevation="1">
        <v-card-text class="pa-4 pa-md-6">
          <div
            class="d-flex flex-wrap align-center justify-space-between mb-4 gap-2"
          >
            <div class="text-body-2 text-grey-darken-1">
              Cliquez sur une formation pour ouvrir sa fiche.
            </div>
            <v-btn-toggle
              v-model="calendarView"
              color="primary"
              divided
              mandatory
              variant="outlined"
            >
              <v-btn value="dayGridMonth">Mois</v-btn>
              <v-btn value="timeGridWeek">Semaine</v-btn>
              <v-btn value="timeGridDay">Jour</v-btn>
            </v-btn-toggle>
          </div>

          <div class="formation-calendar">
            <FullCalendar :key="calendarView" :options="calendarOptions" />
          </div>
        </v-card-text>
      </v-card>
    </template>

    <template v-else>
      <PlanHeaderCard
        v-model="planYear"
        :year-options="yearOptions.filter((item) => item.value !== null)"
      />

      <PlanStatsGrid
        :format-currency="formatCurrency"
        :stats="selectedPlan?.stats"
      />

      <PlanTable
        :format-currency="formatCurrency"
        :headers="planTableHeaders"
        :items="trainingPlans"
        @delete="deletePlan"
        @edit="openPlanEditDialog"
        @export="exportPlan"
        @sync="syncPlan"
      />
    </template>

    <v-dialog v-model="showFormDialog" max-width="900" persistent>
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2">mdi-school</v-icon>
          {{ isEditing ? "Modifier la formation" : "Nouvelle formation" }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <FormationForm
            :initial-data="selectedFormationFormData"
            :is-edit="isEditing"
            :loading="formLoading"
            @cancel="showFormDialog = false"
            @submit="handleFormSubmit"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showPlanDialog" max-width="620" persistent>
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2">mdi-clipboard-text-clock-outline</v-icon>
          {{
            isEditingPlan ? "Modifier le plan annuel" : "Nouveau plan annuel"
          }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="planForm.year"
                density="comfortable"
                :disabled="isEditingPlan"
                item-title="title"
                item-value="value"
                :items="yearOptions.filter((item) => item.value !== null)"
                label="Année *"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="planForm.status"
                density="comfortable"
                :items="planStatusOptions"
                label="Statut *"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="planForm.totalBudget"
                density="comfortable"
                label="Budget total (FCFA)"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="planForm.plannedFormations"
                density="comfortable"
                label="Nombre de formations prévues"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="showPlanDialog = false">
            Annuler
          </v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            :loading="planSaving"
            variant="flat"
            @click="createAnnualPlan"
          >
            {{ isEditingPlan ? "Mettre à jour" : "Créer le plan" }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDetailDialog" max-width="1000">
      <v-card v-if="selectedFormation">
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <div class="d-flex align-center">
            <v-icon class="me-2">mdi-school</v-icon>
            {{ selectedFormation.designation }}
          </div>
          <StatusBadge
            :is-incomplete="!selectedFormation.dateDebut || !selectedFormation.dateFin"
            :status="selectedFormation.status"
          />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-tabs v-model="detailTab" color="primary">
            <v-tab value="info">Informations</v-tab>
            <v-tab value="evaluation">Évaluation interne</v-tab>
            <v-tab value="proofs">Preuves</v-tab>
            <v-tab value="history">Historique</v-tab>
          </v-tabs>

          <v-window v-model="detailTab" class="mt-4">
            <v-window-item value="info">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Formateur</div>
                    <div class="text-body-1">
                      {{ selectedFormation.formateur }}
                    </div>
                  </div>
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Dates</div>
                    <div class="text-body-1">
                      <span v-if="selectedFormation.dateDebut && selectedFormation.dateFin">
                        {{ formatDate(selectedFormation.dateDebut) }} -
                        {{ formatDate(selectedFormation.dateFin) }}
                      </span>
                      <span v-else class="text-grey">À compléter</span>
                    </div>
                  </div>
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Récurrence</div>
                    <div class="text-body-1">
                      {{ selectedFormation.frequency }}
                    </div>
                  </div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Personnel ciblé</div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                      <v-chip
                        v-for="target in getTargetLabels(selectedFormation)"
                        :key="target"
                        size="small"
                        variant="outlined"
                      >
                        {{ target }}
                      </v-chip>
                    </div>
                  </div>
                  <div
                    v-if="selectedFormation.observations"
                    class="info-item mb-4"
                  >
                    <div class="text-caption text-grey">Observations</div>
                    <div class="text-body-1">
                      {{ selectedFormation.observations }}
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-window-item>

            <v-window-item value="proofs">
              <ProofUploader
                :existing-proofs="selectedFormation.proofs"
                @delete="handleProofDelete"
                @upload="handleProofUpload"
              />
            </v-window-item>

            <v-window-item value="evaluation">
              <v-alert
                v-if="selectedFormation.status !== 'realisee'"
                type="info"
                variant="tonal"
              >
                La fiche d'évaluation interne est disponible uniquement après
                réalisation de la formation.
              </v-alert>

              <div v-else>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="evaluationForm.scores.pedagogie"
                      density="comfortable"
                      label="Pédagogie (1-3)"
                      max="5"
                      min="1"
                      type="number"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="evaluationForm.scores.contenu"
                      density="comfortable"
                      label="Contenu (1-3)"
                      max="5"
                      min="1"
                      type="number"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="evaluationForm.scores.applicabilite"
                      density="comfortable"
                      label="Applicabilité (1-3)"
                      max="5"
                      min="1"
                      type="number"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="evaluationForm.scores.animation"
                      density="comfortable"
                      label="Animation (1-3)"
                      max="5"
                      min="1"
                      type="number"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-combobox
                      v-model="evaluationForm.strengths"
                      chips
                      clearable
                      density="comfortable"
                      label="Points forts"
                      multiple
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-combobox
                      v-model="evaluationForm.improvements"
                      chips
                      clearable
                      density="comfortable"
                      label="Axes d'amélioration"
                      multiple
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="evaluationForm.comment"
                      density="comfortable"
                      label="Commentaire"
                      rows="3"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
                <div class="d-flex justify-end gap-2">
                  <v-btn
                    v-if="selectedFormation.internalEvaluation"
                    color="error"
                    variant="tonal"
                    @click="deleteInternalEvaluation"
                  >
                    Supprimer fiche
                  </v-btn>
                  <v-btn
                    color="primary"
                    variant="flat"
                    @click="saveInternalEvaluation"
                  >
                    Enregistrer fiche
                  </v-btn>
                </div>
              </div>
            </v-window-item>

            <v-window-item value="history">
              <HistoryTimeline :history="selectedFormation.history" />
            </v-window-item>
          </v-window>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="showDetailDialog = false">
            Fermer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showImportDialog" max-width="900" persistent>
      <ExcelImporter
        @cancel="showImportDialog = false"
        @import="handleImport"
      />
    </v-dialog>

    <v-dialog v-model="showPlanMissingDialog" max-width="600" persistent>
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2" color="warning">mdi-alert-circle</v-icon>
          Plan annuel manquant
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-alert class="mb-4" type="info" variant="tonal">
            Aucun plan annuel n'existe pour l'année
            <strong>{{ planMissingForm.year }}</strong>. Souhaitez-vous le créer
            avant d'importer ?
          </v-alert>
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="planMissingForm.status"
                density="comfortable"
                :items="planStatusOptions"
                label="Statut *"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="planMissingForm.plannedFormations"
                density="comfortable"
                label="Nombre de formations prévues"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="planMissingForm.totalBudget"
                density="comfortable"
                label="Budget total (FCFA)"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="cancelMissingPlanFlow">
            Annuler
          </v-btn>
          <v-spacer />
          <v-btn color="primary" variant="flat" @click="confirmMissingPlanFlow">
            Créer le plan et continuer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showImportSummaryDialog" max-width="520">
      <v-card>
        <v-card-title class="pa-4">
          <v-icon class="me-2" color="success">mdi-check-circle</v-icon>
          Résumé import
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <div v-if="importSummary">
            <div class="d-flex justify-space-between mb-2">
              <span>Créations</span>
              <strong>{{ importSummary.created }}</strong>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span>Mises à jour</span>
              <strong>{{ importSummary.updated }}</strong>
            </div>
            <div class="d-flex justify-space-between">
              <span>Ignorées</span>
              <strong>{{ importSummary.skipped }}</strong>
            </div>
          </div>
          <div v-else>Résumé indisponible.</div>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="showImportSummaryDialog = false">
            Fermer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showCompleteDialog" max-width="600" persistent>
      <v-card>
        <v-card-title class="pa-4">
          <v-icon class="me-2" color="success">mdi-check-circle</v-icon>
          Confirmer la réalisation
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-alert class="mb-4" type="info" variant="tonal">
            Vous pouvez uploader une preuve de réalisation (optionnel).
          </v-alert>
          <ProofUploader @upload="handleCompleteProofUpload" />
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="showCompleteDialog = false">
            Annuler
          </v-btn>
          <v-spacer />
          <v-btn color="success" variant="flat" @click="confirmComplete">
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { CalendarOptions, EventClickArg } from '@fullcalendar/core'
  import type {
    Formation,
    FormationFilters,
    FormationFormData,
    FormationInternalEvaluation,
    TrainingPlan,
  } from '../../types/formation.types'
  import frLocale from '@fullcalendar/core/locales/fr'
  import dayGridPlugin from '@fullcalendar/daygrid'
  import interactionPlugin from '@fullcalendar/interaction'
  import timeGridPlugin from '@fullcalendar/timegrid'
  import FullCalendar from '@fullcalendar/vue3'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { formationApi, trainingPlanApi } from '@/api/formations'
  import { useToast } from '@/modules/shared/composables/useToast'
  import ClientALayout from '../../components/ClientALayout.vue'
  import ExcelImporter from '../../components/competences/ExcelImporter.vue'
  import FormationCard from '../../components/competences/FormationCard.vue'
  import FormationForm from '../../components/competences/FormationForm.vue'
  import FormationStats from '../../components/competences/FormationStats.vue'
  import HistoryTimeline from '../../components/competences/HistoryTimeline.vue'
  import ProofUploader from '../../components/competences/ProofUploader.vue'
  import StatusBadge from '../../components/competences/StatusBadge.vue'
  import PageHeader from '../../components/PageHeader.vue'
  import { useFormations } from '../../composables/useFormations'
  import FormationFiltersBar from './components/FormationFiltersBar.vue'
  import FormationTable from './components/FormationTable.vue'
  import FormationViewToolbar from './components/FormationViewToolbar.vue'
  import PlanHeaderCard from './components/PlanHeaderCard.vue'
  import PlanStatsGrid from './components/PlanStatsGrid.vue'
  import PlanTable from './components/PlanTable.vue'

  const {
    formations,
    stats,
    fetchFormations,
    createFormation,
    updateFormation,
    completeFormation,
    uploadProof,
    deleteProof,
  } = useFormations()

  const router = useRouter()
  const viewMode = ref<'grid' | 'table' | 'calendar'>('grid')
  const toast = useToast()
  const calendarView = ref<'dayGridMonth' | 'timeGridWeek' | 'timeGridDay'>(
    'dayGridMonth',
  )
  const sectionTab = ref<'formations' | 'plan'>('plan')
  const planYear = ref(new Date().getFullYear())
  const trainingPlans = ref<TrainingPlan[]>([])
  const showPlanDialog = ref(false)
  const planSaving = ref(false)
  const isEditingPlan = ref(false)
  const editingPlanId = ref<number | null>(null)
  const showFormDialog = ref(false)
  const showDetailDialog = ref(false)
  const showImportDialog = ref(false)
  const showImportSummaryDialog = ref(false)
  const showPlanMissingDialog = ref(false)
  const pendingImportPayload = ref<{
    year: number
    targetUserIds: number[]
    file: File
  } | null>(null)
  const importSummary = ref<{
    created: number
    updated: number
    skipped: number
  } | null>(null)
  const showCompleteDialog = ref(false)
  const isEditing = ref(false)
  const formLoading = ref(false)
  const selectedFormation = ref<Formation | null>(null)
  const pendingFormation = ref<FormationFormData | null>(null)
  const selectedFormationFormData = computed<
    Partial<FormationFormData> | undefined
  >(() => {
    const formation = selectedFormation.value
    if (!formation) {
      return undefined
    }
    return {
      designation: formation.designation,
      targetUserIds:
        formation.targetUserIds || (formation.targets || []).map(t => t.id),
      formateur: formation.formateur,
      dateDebut: formation.dateDebut,
      dateFin: formation.dateFin,
      periodMode: formation.periodMode || 'custom',
      periodLabel: formation.periodLabel,
      frequency: formation.frequency,
      observations: formation.observations,
    }
  })
  const detailTab = ref('info')
  const completeProofs = ref<File[]>([])
  const evaluationForm = reactive({
    scores: {
      pedagogie: undefined as number | undefined,
      contenu: undefined as number | undefined,
      applicabilite: undefined as number | undefined,
      animation: undefined as number | undefined,
    },
    strengths: [] as string[],
    improvements: [] as string[],
    comment: '',
  })
  const planForm = reactive({
    year: new Date().getFullYear(),
    status: 'active' as 'draft' | 'active' | 'closed',
    totalBudget: undefined as number | undefined,
    plannedFormations: undefined as number | undefined,
  })
  const planMissingForm = reactive({
    year: new Date().getFullYear(),
    status: 'active' as 'draft' | 'active' | 'closed',
    totalBudget: undefined as number | undefined,
    plannedFormations: undefined as number | undefined,
  })

  const filters = ref<FormationFilters>({
    search: '',
    status: [],
    frequency: [],
    year: new Date().getFullYear(),
  })
  const preferenceStorageKey = 'competences_formations_preferences_v1'

  const filteredFormations = computed(() => {
    return formations.value.filter(f => {
      if (
        filters.value.search
        && !f.designation.toLowerCase().includes(filters.value.search.toLowerCase())
      ) {
        return false
      }
      if (
        filters.value.status?.length
        && !filters.value.status.includes(f.status)
      ) {
        return false
      }
      if (
        filters.value.frequency?.length
        && !filters.value.frequency.includes(f.frequency)
      ) {
        return false
      }
      if (filters.value.cibles?.length) {
        const labels = getTargetLabels(f)
        const hasSelectedTarget = labels.some(label =>
          filters.value.cibles?.includes(label),
        )
        if (!hasSelectedTarget) {
          return false
        }
      }
      if (filters.value.year) {
        const sourceDate = f.dateDebut || f.dateFin
        const yearFromDates
          = sourceDate && !Number.isNaN(new Date(sourceDate).getTime())
            ? new Date(sourceDate).getFullYear()
            : null
        const yearToCompare = yearFromDates ?? f.planYear ?? null
        if (yearToCompare === null || yearToCompare !== filters.value.year) {
          return false
        }
      }
      return true
    })
  })

  const hasActiveFilters = computed(() => {
    const hasSearch = Boolean(
      filters.value.search && filters.value.search.trim() !== '',
    )
    const hasStatus = Boolean(
      filters.value.status && filters.value.status.length > 0,
    )
    const hasFrequency = Boolean(
      filters.value.frequency && filters.value.frequency.length > 0,
    )
    const hasTargets = Boolean(
      filters.value.cibles && filters.value.cibles.length > 0,
    )
    const hasYear
      = typeof filters.value.year === 'number'
        && filters.value.year !== currentYear
    return hasSearch || hasStatus || hasFrequency || hasTargets || hasYear
  })

  const collaboratorOptions = computed(() => {
    const unique = new Set<string>()
    for (const formation of formations.value) {
      for (const label of getTargetLabels(formation)) {
        if (label) unique.add(label)
      }
    }

    return Array.from(unique).toSorted((a, b) => a.localeCompare(b))
  })

  const statusOptions = [
    { title: 'Planifiée', value: 'planifiee' },
    { title: 'En attente', value: 'en_attente' },
    { title: 'Réalisée', value: 'realisee' },
    { title: 'Replanifiée', value: 'replanifiee' },
    { title: 'Annulée', value: 'annulee' },
  ]

  const frequencyOptions = [
    { title: 'Ponctuelle', value: 'ponctuelle' },
    { title: 'Annuelle', value: 'annuelle' },
    { title: 'Semestrielle', value: 'semestrielle' },
    { title: 'Trimestrielle', value: 'trimestrielle' },
    { title: 'Mensuelle', value: 'mensuelle' },
    { title: 'Biennale', value: 'biennale' },
    { title: 'Sur demande', value: 'sur_demande' },
  ]

  const currentYear = new Date().getFullYear()
  const yearOptions = [
    { title: 'Toutes les années', value: null },
    ...Array.from({ length: 6 }).map((_, index) => ({
      title: String(currentYear - 1 + index),
      value: currentYear - 1 + index,
    })),
  ]

  const selectedPlan = computed(
    () =>
      trainingPlans.value.find(plan => plan.year === planYear.value) || null,
  )

  const calendarEvents = computed(() =>
    filteredFormations.value
      .filter(formation => formation.dateDebut && formation.dateFin)
      .map(formation => ({
        id: formation.id,
        title: `#${formation.numero} - ${formation.designation}`,
        start: formation.dateDebut,
        end: formation.dateFin,
        allDay: true,
        color: getFormationCalendarColor(formation.status),
      })),
  )

  const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: calendarView.value,
    locale: frLocale,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: '',
    },
    buttonText: {
      today: 'Aujourd\'hui',
      month: 'Mois',
      week: 'Semaine',
      day: 'Jour',
    },
    events: calendarEvents.value,
    height: 'auto',
    dayMaxEvents: 3,
    eventClick: handleCalendarEventClick,
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    },
  }))

  const tableHeaders = [
    { title: 'N°', key: 'numero', sortable: true },
    { title: 'Désignation', key: 'designation', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Alertes', key: 'alerts', sortable: false },
    { title: 'Cibles', key: 'cibles', sortable: false },
    { title: 'Formateur', key: 'formateur', sortable: true },
    { title: 'Date début', key: 'dateDebut', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const planTableHeaders = [
    { title: 'Année', key: 'year', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Formations', key: 'stats.totalFormations', sortable: false },
    { title: 'Formations prévues', key: 'plannedFormations', sortable: false },
    { title: 'Taux', key: 'stats.tauxRealisation', sortable: false },
    { title: 'Budget total', key: 'totalBudget', sortable: true },
    { title: 'Budget engagé', key: 'budgetEngaged', sortable: true },
    { title: 'Budget réalisé', key: 'spentAmount', sortable: true },
    { title: 'Budget restant', key: 'budgetRemaining', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ]
  const planStatusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Actif', value: 'active' },
    { title: 'Clôturé', value: 'closed' },
  ]

  function openCreateDialog () {
    selectedFormation.value = null
    isEditing.value = false
    showFormDialog.value = true
  }

  function openEditDialog (formation: Formation) {
    selectedFormation.value = formation
    isEditing.value = true
    showFormDialog.value = true
  }

  async function openDetailDialog (formation: Formation) {
    selectedFormation.value = formation
    detailTab.value = 'info'
    await loadInternalEvaluation()
    showDetailDialog.value = true
  }

  function openCompleteDialog (formation: Formation) {
    selectedFormation.value = formation
    completeProofs.value = []
    showCompleteDialog.value = true
  }

  function openRescheduleDialog (formation: Formation) {
    selectedFormation.value = formation
    isEditing.value = true
    showFormDialog.value = true
  }

  async function handleFormSubmit (data: FormationFormData) {
    formLoading.value = true
    try {
      const dateSeed = data.dateDebut || data.dateFin
      const formationYear = dateSeed
        ? new Date(dateSeed).getFullYear()
        : currentYear
      if (formationYear < currentYear) {
        toast.info('Formation dans le passé: elle apparaîtra en attente.')
      }
      if (!isEditing.value) {
        const planExists = await ensurePlanForYear(formationYear)
        if (!planExists) {
          pendingFormation.value = data
          toast.warning('Créez le plan annuel avant d\'ajouter cette formation.')
          openPlanDialogWithYear(formationYear)
          return
        }
      }

      const createdOrUpdated = await (isEditing.value && selectedFormation.value
        ? updateFormation(selectedFormation.value.id, data)
        : createFormation(data))

      alignFiltersToFormation(createdOrUpdated)
      await refreshAnnualPlans()
      showFormDialog.value = false
    } catch (error) {
      console.error('Error submitting form:', error)
    } finally {
      formLoading.value = false
    }
  }

  async function handleImport (payload: {
    year: number
    targetUserIds: number[]
    file: File
  }) {
    try {
      const planExists = await ensurePlanForYear(payload.year)
      if (!planExists) {
        pendingImportPayload.value = payload
        planMissingForm.year = payload.year
        planMissingForm.status = 'active'
        planMissingForm.totalBudget = undefined
        planMissingForm.plannedFormations = undefined
        showPlanMissingDialog.value = true
        return
      }
      await performImport(payload)
    } catch (error) {
      handleUiError(error, 'Import échoué')
    }
  }

  async function performImport (payload: {
    year: number
    targetUserIds: number[]
    file: File
  }) {
    const { year, targetUserIds, file } = payload
    const result = await formationApi.importFile({
      file,
      year,
      targetUserIds,
    })
    toast.success(
      `Import terminé: ${result.created} création(s), ${result.updated} mise(s) à jour`,
    )
    importSummary.value = {
      created: result.created,
      updated: result.updated,
      skipped: result.skipped,
    }
    filters.value.year = year
    await refreshAnnualPlans()
    showImportDialog.value = false
    showImportSummaryDialog.value = true
  }

  function cancelMissingPlanFlow () {
    showPlanMissingDialog.value = false
    pendingImportPayload.value = null
  }

  async function confirmMissingPlanFlow () {
    if (!pendingImportPayload.value) {
      showPlanMissingDialog.value = false
      return
    }
    try {
      await trainingPlanApi.create({
        year: planMissingForm.year,
        status: planMissingForm.status,
        total_budget: planMissingForm.totalBudget,
        planned_formations: planMissingForm.plannedFormations,
      })
      await refreshAnnualPlans()
      const payload = pendingImportPayload.value
      pendingImportPayload.value = null
      showPlanMissingDialog.value = false
      await performImport(payload)
    } catch (error) {
      handleUiError(error, 'Impossible de créer le plan annuel')
    }
  }

  async function handleProofUpload (files: File[]) {
    if (!selectedFormation.value) return
    try {
      for (const file of files) {
        await uploadProof(selectedFormation.value.id, file)
      }
    } catch (error) {
      console.error('Error uploading proof:', error)
    }
  }

  async function handleProofDelete (proofId: string) {
    if (!selectedFormation.value) return
    try {
      await deleteProof(selectedFormation.value.id, proofId)
    } catch (error) {
      console.error('Error deleting proof:', error)
    }
  }

  function handleCompleteProofUpload (files: File[]) {
    completeProofs.value = files
  }

  async function confirmComplete () {
    if (!selectedFormation.value) return
    try {
      await completeFormation(selectedFormation.value.id, completeProofs.value)
      await refreshAnnualPlans()
      showCompleteDialog.value = false
    } catch (error) {
      handleUiError(error, 'Impossible de clôturer la formation')
    }
  }

  async function deleteFormation (formation: Formation) {
    if (!confirm('Supprimer cette formation ? Cette action est irréversible.')) {
      return
    }
    try {
      await formationApi.delete(formation.id)
      formations.value = formations.value.filter(
        item => item.id !== formation.id,
      )
      await refreshAnnualPlans()
    } catch (error) {
      handleUiError(error, 'Impossible de supprimer la formation')
    }
  }

  function openTrackingForFormation (formation: Formation) {
    router.push({
      path: '/company/my-tasks',
      query: {
        preselect_type: 'formation',
        preselect_id: String(formation.id),
      },
    })
  }

  async function refreshAnnualPlans () {
    try {
      trainingPlans.value = await trainingPlanApi.getAll({
        year: planYear.value,
      })
    } catch (error) {
      handleUiError(error, 'Impossible de charger les plans annuels')
    }
  }

  function openPlanDialog () {
    planForm.year = planYear.value
    planForm.status = 'active'
    planForm.totalBudget = selectedPlan.value?.totalBudget
    planForm.plannedFormations = selectedPlan.value?.plannedFormations
    isEditingPlan.value = false
    editingPlanId.value = null
    showPlanDialog.value = true
  }

  function openPlanDialogWithYear (year: number) {
    planForm.year = year
    planForm.status = 'active'
    planForm.totalBudget = undefined
    planForm.plannedFormations = undefined
    isEditingPlan.value = false
    editingPlanId.value = null
    showPlanDialog.value = true
  }

  function openPlanEditDialog (plan: TrainingPlan) {
    planForm.year = plan.year
    planForm.status = plan.status
    planForm.totalBudget = plan.totalBudget
    planForm.plannedFormations = plan.plannedFormations
    isEditingPlan.value = true
    editingPlanId.value = plan.id
    showPlanDialog.value = true
  }

  async function createAnnualPlan () {
    planSaving.value = true
    try {
      await (isEditingPlan.value && editingPlanId.value
        ? trainingPlanApi.update(editingPlanId.value, {
          status: planForm.status,
          total_budget: planForm.totalBudget,
          planned_formations: planForm.plannedFormations,
        })
        : trainingPlanApi.create({
          year: planForm.year,
          status: planForm.status,
          total_budget: planForm.totalBudget,
          planned_formations: planForm.plannedFormations,
        }))
      planYear.value = planForm.year
      showPlanDialog.value = false
      await refreshAnnualPlans()
      if (pendingFormation.value) {
        const data = pendingFormation.value
        pendingFormation.value = null
        const created = await createFormation(data)
        alignFiltersToFormation(created)
        await refreshAnnualPlans()
        showFormDialog.value = false
      }
    } catch (error) {
      handleUiError(error, 'Impossible de sauvegarder le plan annuel')
    } finally {
      planSaving.value = false
    }
  }

  async function deletePlan (plan: TrainingPlan) {
    if (!confirm('Supprimer ce plan annuel ? Cette action est irréversible.')) {
      return
    }
    try {
      await trainingPlanApi.delete(plan.id)
      trainingPlans.value = trainingPlans.value.filter(
        item => item.id !== plan.id,
      )
    } catch (error) {
      handleUiError(error, 'Impossible de supprimer le plan annuel')
    }
  }

  async function syncPlan (id: number) {
    try {
      const synced = await trainingPlanApi.sync(id)
      trainingPlans.value = trainingPlans.value.map(plan =>
        plan.id === synced.id ? synced : plan,
      )
    } catch (error) {
      handleUiError(error, 'Impossible de recalculer le plan')
    }
  }

  async function recalculateSelectedPlan () {
    if (!selectedPlan.value) return
    await syncPlan(selectedPlan.value.id)
  }

  async function exportPlan (id: number) {
    try {
      const blob = await trainingPlanApi.exportXlsx(id)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `plan-formation-${id}.xlsx`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error) {
      handleUiError(error, 'Impossible d\'exporter le plan')
    }
  }

  async function exportSelectedPlan () {
    if (!selectedPlan.value) return
    await exportPlan(selectedPlan.value.id)
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: [],
      frequency: [],
      cibles: [],
      year: currentYear,
    }
  }

  function alignFiltersToFormation (formation: Formation) {
    const sourceDate = formation.dateDebut || formation.dateFin
    const formationYear = sourceDate
      ? new Date(sourceDate).getFullYear()
      : formation.planYear || currentYear
    if (filters.value.year && filters.value.year !== formationYear) {
      filters.value.year = formationYear
    }
    if (
      filters.value.status
      && !filters.value.status.includes(formation.status)
    ) {
      filters.value.status = [...filters.value.status, formation.status]
    }
    if (
      filters.value.search
      && !formation.designation
        .toLowerCase()
        .includes(filters.value.search.toLowerCase())
    ) {
      filters.value.search = ''
    }
    toast.info('Filtres ajustés pour afficher la formation créée.')
  }

  async function ensurePlanForYear (year: number) {
    if (selectedPlan.value && selectedPlan.value.year === year) {
      return true
    }

    try {
      const plans = await trainingPlanApi.getAll({ year })
      return plans.length > 0
    } catch (error) {
      handleUiError(error, 'Impossible de vérifier le plan annuel')
      return false
    }
  }

  function handleUiError (error: any, fallbackMessage: string) {
    const message = error?.response?.data?.message || fallbackMessage
    toast.error(message)
  }

  function hydrateEvaluationForm (evaluation: FormationInternalEvaluation | null) {
    evaluationForm.scores.pedagogie = evaluation?.scores?.pedagogie
    evaluationForm.scores.contenu = evaluation?.scores?.contenu
    evaluationForm.scores.applicabilite = evaluation?.scores?.applicabilite
    evaluationForm.scores.animation = evaluation?.scores?.animation
    evaluationForm.strengths = evaluation?.strengths
      ? [...evaluation.strengths]
      : []
    evaluationForm.improvements = evaluation?.improvements
      ? [...evaluation.improvements]
      : []
    evaluationForm.comment = evaluation?.comment || ''
  }

  async function loadInternalEvaluation () {
    if (
      !selectedFormation.value
      || selectedFormation.value.status !== 'realisee'
    ) {
      hydrateEvaluationForm(null)
      return
    }

    try {
      const evaluation = await formationApi.getInternalEvaluation(
        selectedFormation.value.id,
      )
      selectedFormation.value.internalEvaluation = evaluation || undefined
      hydrateEvaluationForm(evaluation)
    } catch (error) {
      hydrateEvaluationForm(null)
      console.error('Error loading internal evaluation:', error)
    }
  }

  async function saveInternalEvaluation () {
    if (!selectedFormation.value) return
    try {
      const evaluation = await formationApi.upsertInternalEvaluation(
        selectedFormation.value.id,
        {
          method: 'combinaison',
          scores: {
            pedagogie: evaluationForm.scores.pedagogie,
            contenu: evaluationForm.scores.contenu,
            applicabilite: evaluationForm.scores.applicabilite,
            animation: evaluationForm.scores.animation,
          },
          strengths: evaluationForm.strengths,
          improvements: evaluationForm.improvements,
          comment: evaluationForm.comment,
        },
      )
      selectedFormation.value.internalEvaluation = evaluation
    } catch (error) {
      console.error('Error saving internal evaluation:', error)
    }
  }

  async function deleteInternalEvaluation () {
    if (!selectedFormation.value) return
    try {
      await formationApi.deleteInternalEvaluation(selectedFormation.value.id)
      selectedFormation.value.internalEvaluation = undefined
      hydrateEvaluationForm(null)
    } catch (error) {
      console.error('Error deleting internal evaluation:', error)
    }
  }

  function formatDate (date: string) {
    if (!date) return 'À compléter'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }

  function formatCurrency (amount: number) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
    }).format(amount)
  }

  function getFormationCalendarColor (status: Formation['status']): string {
    const colors: Record<Formation['status'], string> = {
      planifiee: '#1e88e5',
      replanifiee: '#f9a825',
      en_attente: '#d32f2f',
      realisee: '#2e7d32',
      annulee: '#616161',
    }

    return colors[status] || '#1e88e5'
  }

  async function handleCalendarEventClick (info: EventClickArg) {
    const formation = formations.value.find(
      item => item.id === String(info.event.id),
    )
    if (!formation) return
    await openDetailDialog(formation)
  }

  function getTargetLabels (formation: Formation) {
    if (Array.isArray(formation.targets) && formation.targets.length > 0) {
      return formation.targets.map(target => target.name)
    }

    return Array.isArray(formation.cibles) ? formation.cibles : []
  }

  onMounted(() => {
    try {
      const raw = localStorage.getItem(preferenceStorageKey)
      if (raw) {
        const parsed = JSON.parse(raw)
        if (
          parsed?.viewMode
          && ['grid', 'table', 'calendar'].includes(parsed.viewMode)
        ) {
          viewMode.value = parsed.viewMode
        }
        if (
          parsed?.calendarView
          && ['dayGridMonth', 'timeGridWeek', 'timeGridDay'].includes(
            parsed.calendarView,
          )
        ) {
          calendarView.value = parsed.calendarView
        }
        if (parsed?.filters && typeof parsed.filters === 'object') {
          filters.value = {
            ...filters.value,
            ...parsed.filters,
          }
        }
      }
    } catch (error) {
      console.error('Error loading competences preferences:', error)
    }

    fetchFormations()
    refreshAnnualPlans()
  })

  watch(planYear, () => {
    refreshAnnualPlans()
  })

  watch(
    [viewMode, calendarView, filters],
    () => {
      try {
        localStorage.setItem(
          preferenceStorageKey,
          JSON.stringify({
            viewMode: viewMode.value,
            calendarView: calendarView.value,
            filters: filters.value,
          }),
        )
      } catch (error) {
        console.error('Error saving competences preferences:', error)
      }
    },
    { deep: true },
  )
</script>

<style scoped>
.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.formation-calendar :deep(.fc) {
  --fc-border-color: #e5e7eb;
  --fc-today-bg-color: rgba(30, 136, 229, 0.08);
}

.formation-calendar :deep(.fc .fc-event) {
  border-radius: 8px;
  padding: 2px 6px;
  font-size: 0.82rem;
  border: none;
  cursor: pointer;
}

.formation-calendar :deep(.fc .fc-event:hover) {
  opacity: 0.9;
}
</style>
