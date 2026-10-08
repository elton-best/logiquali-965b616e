<template>
  <ClientALayout>
    <PageHeader
      icon="mdi-bullhorn"
      subtitle="Plan de communication & sensibilisation"
      title="Communication & Sensibilisation"
    >
      <template #actions>
        <v-btn
          color="primary"
          prepend-icon="mdi-clipboard-text-outline"
          variant="tonal"
          @click="openPlanDialog"
        >
          Plan annuel
        </v-btn>
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
          prepend-icon="mdi-download"
          variant="tonal"
          @click="exportCommunications"
        >
          Exporter le plan
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          variant="flat"
          @click="openCreateDialog"
        >
          Nouvelle action
        </v-btn>
      </template>
    </PageHeader>

    <CommunicationStats class="mb-6" :stats="stats" />

    <v-card class="mb-6" elevation="1">
      <v-card-text class="pa-4 d-flex flex-wrap gap-4 align-center justify-space-between">
        <div>
          <div class="text-caption text-grey">Plan annuel {{ filters.year || new Date().getFullYear() }}</div>
          <div class="text-body-1">
            {{ selectedPlan ? planStatusLabel(selectedPlan.status) : 'Aucun plan défini' }}
          </div>
        </div>
        <div class="d-flex flex-wrap gap-6">
          <div>
            <div class="text-caption text-grey">Budget prévu</div>
            <div class="text-body-1">
              {{ selectedPlan?.plannedBudget ? formatCurrency(selectedPlan.plannedBudget) : '—' }}
            </div>
          </div>
          <div>
            <div class="text-caption text-grey">Actions prévues</div>
            <div class="text-body-1">
              {{ selectedPlan?.plannedActions ?? '—' }}
            </div>
          </div>
          <div>
            <div class="text-caption text-grey">Budget engagé</div>
            <div class="text-body-1">
              {{ selectedPlan ? formatCurrency(selectedPlan.budgetEngaged) : '—' }}
            </div>
          </div>
        </div>
        <v-btn color="primary" variant="outlined" @click="openPlanDialog">
          {{ selectedPlan ? 'Modifier le plan' : 'Créer le plan' }}
        </v-btn>
      </v-card-text>
    </v-card>

    <v-card class="mb-6" elevation="1">
      <v-card-text class="pa-4">
        <v-row>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.search"
              clearable
              density="comfortable"
              hide-details
              label="Rechercher une action"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="filters.type"
              chips
              clearable
              density="comfortable"
              hide-details
              :items="typeOptions"
              label="Type de plan"
              multiple
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              chips
              clearable
              density="comfortable"
              hide-details
              :items="statusOptions"
              label="Statut"
              multiple
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="filters.year"
              clearable
              density="comfortable"
              hide-details
              :items="yearOptions"
              label="Année du plan"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <div class="d-flex justify-space-between align-center mb-4">
      <v-btn-toggle v-model="viewMode" divided mandatory variant="outlined">
        <v-btn icon="mdi-view-grid" value="grid" />
        <v-btn icon="mdi-table" value="table" />
      </v-btn-toggle>

      <div class="text-body-2 text-grey">
        {{ filteredCommunications.length }} actions
      </div>
    </div>

    <v-row v-if="viewMode === 'grid'">
      <v-col
        v-for="communication in filteredCommunications"
        :key="communication.id"
        cols="12"
        lg="4"
        md="6"
      >
        <CommunicationCard
          :communication="communication"
          @complete="openCompleteDialog"
          @edit="openEditDialog"
          @reschedule="openRescheduleDialog"
          @view="openDetailDialog"
        />
      </v-col>
    </v-row>

    <v-card v-if="viewMode === 'table'" elevation="1">
      <v-data-table
        class="elevation-0"
        :headers="tableHeaders"
        :items="filteredCommunications"
        :items-per-page="10"
      >
        <template #item.numero="{ item }">
          <v-chip color="primary" size="small" variant="flat">
            #{{ item.numero }}
          </v-chip>
        </template>

        <template #item.type="{ item }">
          <v-chip :color="item.type === 'communication' ? 'info' : 'purple'" size="small" variant="tonal">
            {{ item.type === 'communication' ? 'Communication' : 'Sensibilisation' }}
          </v-chip>
        </template>

        <template #item.status="{ item }">
          <StatusBadge :is-incomplete="!item.dateDebut || !item.dateFin" :status="item.status" />
        </template>

        <template #item.alerts="{ item }">
          <AlertIndicator :date-debut="item.dateDebut" />
        </template>

        <template #item.dateDebut="{ item }">
          <span v-if="item.dateDebut">
            {{ formatDate(item.dateDebut) }}
          </span>
          <span v-else class="text-grey">À compléter</span>
        </template>

        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-eye"
            size="small"
            variant="text"
            @click="openDetailDialog(item)"
          />
          <v-btn
            icon="mdi-pencil"
            size="small"
            variant="text"
            @click="openEditDialog(item)"
          />
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="showFormDialog" max-width="900" persistent>
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2">mdi-bullhorn</v-icon>
          {{ isEditing ? 'Modifier la communication' : 'Nouvelle communication' }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <CommunicationForm
            :initial-data="selectedCommunicationFormData"
            :is-edit="isEditing"
            :loading="formLoading"
            @cancel="showFormDialog = false"
            @submit="handleFormSubmit"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDetailDialog" max-width="1000">
      <v-card v-if="selectedCommunication">
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <div class="d-flex align-center">
            <v-icon class="me-2">mdi-bullhorn</v-icon>
            {{ selectedCommunication.designation }}
          </div>
          <StatusBadge
            :is-incomplete="!selectedCommunication.dateDebut || !selectedCommunication.dateFin"
            :status="selectedCommunication.status"
          />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-tabs v-model="detailTab" color="primary">
            <v-tab value="info">Informations</v-tab>
            <v-tab value="proofs">Preuves</v-tab>
            <v-tab value="history">Historique</v-tab>
          </v-tabs>

          <v-window v-model="detailTab" class="mt-4">
            <v-window-item value="info">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Type</div>
                    <v-chip :color="selectedCommunication.type === 'communication' ? 'info' : 'purple'" size="small" variant="tonal">
                      {{ selectedCommunication.type === 'communication' ? 'Communication' : 'Sensibilisation' }}
                    </v-chip>
                  </div>
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Responsable</div>
                    <div class="text-body-1">{{ selectedCommunication.responsable }}</div>
                  </div>
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Dates</div>
                    <div class="text-body-1">
                      <span v-if="selectedCommunication.dateDebut && selectedCommunication.dateFin">
                        {{ formatDate(selectedCommunication.dateDebut) }} - {{ formatDate(selectedCommunication.dateFin) }}
                      </span>
                      <span v-else class="text-grey">À compléter</span>
                    </div>
                  </div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Personnel ciblé</div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                      <v-chip
                        v-for="cible in selectedCommunication.cibles"
                        :key="cible"
                        size="small"
                        variant="outlined"
                      >
                        {{ cible }}
                      </v-chip>
                    </div>
                  </div>
                  <div class="info-item mb-4">
                    <div class="text-caption text-grey">Moyens</div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                      <v-chip
                        v-for="moyen in selectedCommunication.moyens"
                        :key="moyen"
                        color="info"
                        size="small"
                        variant="outlined"
                      >
                        {{ moyen }}
                      </v-chip>
                    </div>
                  </div>
                  <div v-if="selectedCommunication.cout" class="info-item mb-4">
                    <div class="text-caption text-grey">Budget</div>
                    <div class="text-body-1">{{ formatCurrency(selectedCommunication.cout) }}</div>
                  </div>
                </v-col>
              </v-row>
            </v-window-item>

            <v-window-item value="proofs">
              <ProofUploader
                :existing-proofs="selectedCommunication.proofs"
                @delete="handleProofDelete"
                @upload="handleProofUpload"
              />
            </v-window-item>

            <v-window-item value="history">
              <HistoryTimeline :history="selectedCommunication.history" />
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
                v-model.number="planMissingForm.plannedActions"
                density="comfortable"
                label="Nombre d'actions prévues"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="planMissingForm.plannedBudget"
                density="comfortable"
                label="Budget prévu (FCFA)"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="cancelMissingPlanFlow">Annuler</v-btn>
          <v-spacer />
          <v-btn color="primary" variant="flat" @click="confirmMissingPlanFlow">
            Créer le plan et continuer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showPlanDialog" max-width="620" persistent>
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2">mdi-clipboard-text-outline</v-icon>
          {{ isEditingPlan ? 'Modifier le plan annuel' : 'Nouveau plan annuel' }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="planForm.year"
                density="comfortable"
                :disabled="isEditingPlan"
                :items="yearOptions"
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
                v-model.number="planForm.plannedBudget"
                density="comfortable"
                label="Budget prévu (FCFA)"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model.number="planForm.plannedActions"
                density="comfortable"
                label="Nombre d'actions prévues"
                min="0"
                type="number"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="showPlanDialog = false">Annuler</v-btn>
          <v-spacer />
          <v-btn color="primary" :loading="planSaving" variant="flat" @click="savePlan">
            {{ isEditingPlan ? 'Mettre à jour' : 'Créer le plan' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showImportSummary" max-width="520">
      <v-card>
        <v-card-title class="d-flex align-center pa-4">
          <v-icon class="me-2" color="success">mdi-check-circle</v-icon>
          Import terminé
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" sm="4">
              <div class="text-caption text-grey">Créées</div>
              <div class="text-h6">{{ importSummary.created }}</div>
            </v-col>
            <v-col cols="12" sm="4">
              <div class="text-caption text-grey">Mises à jour</div>
              <div class="text-h6">{{ importSummary.updated }}</div>
            </v-col>
            <v-col cols="12" sm="4">
              <div class="text-caption text-grey">Ignorées</div>
              <div class="text-h6">{{ importSummary.skipped }}</div>
            </v-col>
            <v-col cols="12" sm="4">
              <div class="text-caption text-grey">À compléter</div>
              <div class="text-h6">{{ importSummary.incomplete }}</div>
            </v-col>
          </v-row>
          <v-alert v-if="importSummary.skipped > 0" class="mt-4" type="warning" variant="tonal">
            Des lignes ont été ignorées (thème manquant ou numéro en doublon).
          </v-alert>
          <v-alert v-if="importSummary.incomplete > 0" class="mt-2" type="info" variant="tonal">
            Certaines actions ont été créées sans dates et doivent être complétées.
          </v-alert>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn color="primary" variant="flat" @click="showImportSummary = false">Fermer</v-btn>
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
            Veuillez uploader une preuve de réalisation
          </v-alert>
          <ProofUploader
            @upload="handleCompleteProofUpload"
          />
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="showCompleteDialog = false">
            Annuler
          </v-btn>
          <v-spacer />
          <v-btn
            color="success"
            :disabled="completeProofs.length === 0"
            variant="flat"
            @click="confirmComplete"
          >
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { Communication, CommunicationFilters, CommunicationFormData, CommunicationPlan } from '../../types/communication.types'
  import { computed, ref, watch } from 'vue'
  import { communicationApi, communicationPlanApi } from '@/api/communications'
  import { useToast } from '@/modules/shared/composables/useToast'
  import ClientALayout from '../../components/ClientALayout.vue'
  import AlertIndicator from '../../components/communications/AlertIndicator.vue'
  import CommunicationCard from '../../components/communications/CommunicationCard.vue'
  import CommunicationForm from '../../components/communications/CommunicationForm.vue'
  import CommunicationStats from '../../components/communications/CommunicationStats.vue'
  import ExcelImporter from '../../components/communications/ExcelImporter.vue'
  import HistoryTimeline from '../../components/communications/HistoryTimeline.vue'
  import ProofUploader from '../../components/communications/ProofUploader.vue'
  import StatusBadge from '../../components/communications/StatusBadge.vue'
  import PageHeader from '../../components/PageHeader.vue'
  import { useCommunications } from '../../composables/useCommunications'

  const {
    communications,
    stats,
    fetchCommunications,
    createCommunication,
    updateCommunication,
    completeCommunication,
    uploadProof,
    deleteProof,
  } = useCommunications()

  const viewMode = ref<'grid' | 'table'>('grid')
  const toast = useToast()
  const showFormDialog = ref(false)
  const showDetailDialog = ref(false)
  const showImportDialog = ref(false)
  const showImportSummary = ref(false)
  const showCompleteDialog = ref(false)
  const showPlanDialog = ref(false)
  const showPlanMissingDialog = ref(false)
  const planSaving = ref(false)
  const communicationPlans = ref<CommunicationPlan[]>([])
  const isEditingPlan = ref(false)
  const editingPlanId = ref<number | null>(null)
  const isEditing = ref(false)
  const formLoading = ref(false)
  const selectedCommunication = ref<Communication | null>(null)
  const detailTab = ref('info')
  const completeProofs = ref<File[]>([])
  const importSummary = ref({ created: 0, updated: 0, skipped: 0, incomplete: 0 })
  const pendingImportPayload = ref<{
    year: number
    file: File
  } | null>(null)
  const planForm = ref({
    year: new Date().getFullYear(),
    status: 'active' as 'draft' | 'active' | 'closed',
    plannedBudget: undefined as number | undefined,
    plannedActions: undefined as number | undefined,
  })
  const planMissingForm = ref({
    year: new Date().getFullYear(),
    status: 'active' as 'draft' | 'active' | 'closed',
    plannedBudget: undefined as number | undefined,
    plannedActions: undefined as number | undefined,
  })

  const filters = ref<CommunicationFilters>({
    search: '',
    type: [],
    status: [],
    year: new Date().getFullYear(),
  })

  const filteredCommunications = computed(() => {
    return communications.value.filter(c => {
      if (filters.value.search && !c.designation.toLowerCase().includes(filters.value.search.toLowerCase())) {
        return false
      }
      if (filters.value.type?.length && !filters.value.type.includes(c.type)) {
        return false
      }
      if (filters.value.status?.length && !filters.value.status.includes(c.status)) {
        return false
      }
      if (filters.value.year) {
        const sourceDate = c.dateDebut || c.dateFin
        const yearFromDates = sourceDate && !Number.isNaN(new Date(sourceDate).getTime())
          ? new Date(sourceDate).getFullYear()
          : null
        const yearToCompare = yearFromDates ?? c.planYear ?? null
        if (yearToCompare === null || yearToCompare !== filters.value.year) {
          return false
        }
      }
      return true
    })
  })

  const selectedCommunicationFormData = computed<Partial<CommunicationFormData> | undefined>(() => {
    const communication = selectedCommunication.value
    if (!communication) {
      return undefined
    }

    return {
      type: communication.type,
      designation: communication.designation,
      cibles: communication.cibles,
      moyens: communication.moyens,
      chronogramme: communication.chronogramme,
      responsable: communication.responsable,
      cout: communication.cout,
      dateDebut: communication.dateDebut,
      dateFin: communication.dateFin,
      frequency: communication.frequency,
      observations: communication.observations,
    }
  })

  const typeOptions = [
    { title: 'Communication', value: 'communication' },
    { title: 'Sensibilisation', value: 'sensibilisation' },
  ]

  const statusOptions = [
    { title: 'Planifiée', value: 'planifiee' },
    { title: 'En attente', value: 'en_attente' },
    { title: 'Réalisée', value: 'realisee' },
    { title: 'Replanifiée', value: 'replanifiee' },
    { title: 'Annulée', value: 'annulee' },
  ]

  const planStatusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Actif', value: 'active' },
    { title: 'Clôturé', value: 'closed' },
  ]

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }).map((_, index) => currentYear - 1 + index)
  })

  const selectedPlan = computed(() => {
    const year = filters.value.year || new Date().getFullYear()
    return communicationPlans.value.find(plan => plan.year === year) || null
  })

  const tableHeaders = [
    { title: 'N°', key: 'numero', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Désignation', key: 'designation', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Alertes', key: 'alerts', sortable: false },
    { title: 'Responsable', key: 'responsable', sortable: true },
    { title: 'Date début', key: 'dateDebut', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function openCreateDialog () {
    selectedCommunication.value = null
    isEditing.value = false
    showFormDialog.value = true
  }

  function openEditDialog (communication: Communication) {
    selectedCommunication.value = communication
    isEditing.value = true
    showFormDialog.value = true
  }

  function openDetailDialog (communication: Communication) {
    selectedCommunication.value = communication
    detailTab.value = 'info'
    showDetailDialog.value = true
  }

  function openCompleteDialog (communication: Communication) {
    selectedCommunication.value = communication
    completeProofs.value = []
    showCompleteDialog.value = true
  }

  function openRescheduleDialog (communication: Communication) {
    selectedCommunication.value = communication
    isEditing.value = true
    showFormDialog.value = true
  }

  function openPlanDialog () {
    const year = filters.value.year || new Date().getFullYear()
    planForm.value.year = year
    planForm.value.status = selectedPlan.value?.status || 'active'
    planForm.value.plannedBudget = selectedPlan.value?.plannedBudget
    planForm.value.plannedActions = selectedPlan.value?.plannedActions
    isEditingPlan.value = !!selectedPlan.value
    editingPlanId.value = selectedPlan.value?.id || null
    showPlanDialog.value = true
  }

  async function handleFormSubmit (data: CommunicationFormData) {
    formLoading.value = true
    try {
      await (isEditing.value && selectedCommunication.value ? updateCommunication(selectedCommunication.value.id, data) : createCommunication(data))
      showFormDialog.value = false
    } catch (error) {
      console.error('Error submitting form:', error)
    } finally {
      formLoading.value = false
    }
  }

  async function ensurePlanForYear (year: number, defaultActions?: number) {
    const existingPlans = await communicationPlanApi.getAll({ year })
    if (existingPlans.length > 0) {
      return true
    }

    planMissingForm.value.year = year
    planMissingForm.value.status = 'active'
    planMissingForm.value.plannedActions = defaultActions
    planMissingForm.value.plannedBudget = undefined
    showPlanMissingDialog.value = true
    return false
  }

  async function performImport (payload: { year: number, file: File }) {
    const { year, file } = payload
    try {
      const result = await communicationApi.importFile({ file, year })

      importSummary.value = {
        created: result.created,
        updated: result.updated,
        skipped: result.skipped,
        incomplete: result.incomplete,
      }
      toast.success(`Import terminé: ${result.created} création(s), ${result.updated} mise(s) à jour`)
      filters.value.year = year
      await fetchCommunications()
      showImportDialog.value = false
      showImportSummary.value = true
    } catch (error) {
      toast.error(error?.response?.data?.message || 'Import échoué')
    }
  }

  async function handleImport (payload: { year: number, file: File }) {
    pendingImportPayload.value = payload
    const canContinue = await ensurePlanForYear(payload.year)
    if (!canContinue) {
      return
    }
    await performImport(payload)
    pendingImportPayload.value = null
  }

  async function confirmMissingPlanFlow () {
    if (!pendingImportPayload.value) {
      showPlanMissingDialog.value = false
      return
    }
    planSaving.value = true
    try {
      await communicationPlanApi.create({
        year: planMissingForm.value.year,
        status: planMissingForm.value.status,
        planned_budget: planMissingForm.value.plannedBudget,
        planned_actions: planMissingForm.value.plannedActions,
      })
      await refreshPlans()
      showPlanMissingDialog.value = false
      const payload = pendingImportPayload.value
      pendingImportPayload.value = null
      await performImport(payload)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de créer le plan annuel')
    } finally {
      planSaving.value = false
    }
  }

  function cancelMissingPlanFlow () {
    showPlanMissingDialog.value = false
    pendingImportPayload.value = null
    toast.info('Import annulé : aucun plan annuel créé')
  }

  async function handleProofUpload (files: File[]) {
    if (!selectedCommunication.value) return
    try {
      for (const file of files) {
        await uploadProof(selectedCommunication.value.id, file)
      }
    } catch (error) {
      console.error('Error uploading proof:', error)
    }
  }

  async function handleProofDelete (proofId: string) {
    if (!selectedCommunication.value) return
    try {
      await deleteProof(selectedCommunication.value.id, proofId)
    } catch (error) {
      console.error('Error deleting proof:', error)
    }
  }

  function handleCompleteProofUpload (files: File[]) {
    completeProofs.value = files
  }

  async function confirmComplete () {
    if (!selectedCommunication.value) return
    try {
      await completeCommunication(selectedCommunication.value.id, completeProofs.value)
      showCompleteDialog.value = false
    } catch (error) {
      console.error('Error completing communication:', error)
    }
  }

  function exportCommunications () {
    console.log('Export communications')
  }

  function planStatusLabel (status: 'draft' | 'active' | 'closed') {
    if (status === 'draft') return 'Brouillon'
    if (status === 'closed') return 'Clôturé'
    return 'Actif'
  }

  async function refreshPlans () {
    const year = filters.value.year || undefined
    communicationPlans.value = await communicationPlanApi.getAll({ year: year || undefined })
  }

  async function savePlan () {
    planSaving.value = true
    try {
      await (isEditingPlan.value && editingPlanId.value
        ? communicationPlanApi.update(editingPlanId.value, {
          status: planForm.value.status,
          planned_budget: planForm.value.plannedBudget,
          planned_actions: planForm.value.plannedActions,
        })
        : communicationPlanApi.create({
          year: planForm.value.year,
          status: planForm.value.status,
          planned_budget: planForm.value.plannedBudget,
          planned_actions: planForm.value.plannedActions,
        }))
      await refreshPlans()
      showPlanDialog.value = false
      toast.success('Plan annuel enregistré')
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de sauvegarder le plan annuel')
    } finally {
      planSaving.value = false
    }
  }

  function formatDate (date: string | null | undefined) {
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

  watch(() => filters.value.year, year => {
    fetchCommunications({ year: year || undefined })
    refreshPlans()
  }, { immediate: true })

</script>

<style scoped>
.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}
</style>
