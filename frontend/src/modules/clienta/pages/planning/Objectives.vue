<template>
  <ClientALayout current-page="objectifs">
    <PageHeader
      icon="mdi-target"
      icon-color="primary"
      subtitle="Suivi des objectifs et indicateurs de performance"
      title="Tableau de bord du système"
    />

    <v-progress-linear
      v-if="loading"
      class="mb-6"
      color="primary"
      indeterminate
    />

    <!-- (c), (a), (b) Synthèses & Récapitulatifs (§8 / REQ-6.2-07) -->
    <v-expand-transition>
      <div v-if="showRecaps" class="mb-4">
        <ObjectivesRecapViews :summary="systemSummary" />
      </div>
    </v-expand-transition>

    <!-- Filters & Actions Bar -->
    <v-card class="mb-6" elevation="0" rounded="lg">
      <v-card-text>
        <v-row class="mb-2" justify="end">
          <v-col cols="12">
            <div class="objectives-toolbar-actions d-flex flex-wrap align-center ga-2 justify-end">
              <v-btn
                :color="showRecaps ? 'primary' : 'grey-darken-1'"
                prepend-icon="mdi-chart-box-outline"
                rounded="lg"
                variant="outlined"
                @click="showRecaps = !showRecaps"
              >
                {{ showRecaps ? 'Masquer synthèses' : 'Synthèses système (§8)' }}
              </v-btn>
              <ColumnVisibilitySelector
                v-if="viewMode === 'list'"
                v-model="visibleObjectiveColumns"
                :columns="allObjectiveColumns"
                storage-key="objectives_table_columns"
              />
              <v-btn
                color="success"
                :disabled="loading || filteredObjectives.length === 0"
                :loading="exporting"
                prepend-icon="mdi-file-excel"
                rounded="lg"
                variant="tonal"
                @click="exportObjectivesXlsx"
              >
                Exporter
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                rounded="lg"
                @click="openDialog()"
              >
                Ajouter
              </v-btn>
            </div>
          </v-col>
        </v-row>

        <v-row align="center">
          <v-col cols="12" md="3">
            <v-text-field
              v-model="search"
              density="comfortable"
              hide-details
              placeholder="Rechercher..."
              prepend-inner-icon="mdi-magnify"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterProcessus"
              clearable
              density="comfortable"
              hide-details
              :items="processusList"
              label="Processus"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterAxe"
              clearable
              density="comfortable"
              hide-details
              :items="axisOptions"
              label="Axe"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterFrequence"
              clearable
              density="comfortable"
              hide-details
              :items="['Mensuel', 'Trimestriel', 'Semestriel', 'Annuel']"
              label="Fréquence"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col class="d-flex justify-end" cols="12" md="3" style="gap: 8px;">
            <v-btn-toggle v-model="viewMode" density="comfortable" mandatory rounded="lg">
              <v-btn icon="mdi-view-grid" value="grid" />
              <v-btn icon="mdi-view-list" value="list" />
            </v-btn-toggle>
            <v-btn
              :disabled="!hasActiveFilters"
              prepend-icon="mdi-filter-off"
              rounded="lg"
              variant="outlined"
              @click="resetFilters"
            >
              Réinitialiser
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Grid View -->
    <v-row v-if="viewMode === 'grid'">
      <v-col
        v-for="obj in filteredObjectives"
        :key="obj.id"
        cols="12"
        lg="4"
        md="6"
      >
        <v-card
          class="objective-card"
          elevation="2"
          hover
          rounded="lg"
          @click="$router.push({ path: `/company/planning/objectives/${obj.id}`, query: { process_id: String(obj.processId) } })"
        >
          <div class="card-header" :style="{ background: getAxeGradient(obj.axeStrategique) }">
            <div class="d-flex align-center justify-space-between mb-2">
              <v-chip color="white" size="small" variant="flat">
                {{ obj.processus }}
              </v-chip>
              <v-chip v-if="obj.axesStrategiques.length > 0" :color="getAxeColor(obj.axeStrategique)" size="small" variant="flat">
                {{ axisDisplayLabel(obj.axeStrategique) }}
              </v-chip>
              <v-chip
                v-if="obj.axesStrategiques.length > 1"
                class="ml-1"
                color="primary"
                size="x-small"
                variant="outlined"
              >
                +{{ obj.axesStrategiques.length - 1 }}
              </v-chip>
            </div>
            <h3 class="text-h6 text-white font-weight-bold">{{ obj.titre }}</h3>
          </div>

          <v-card-text class="pt-4">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Indicateur</div>
              <div class="text-body-2 font-weight-medium">{{ obj.indicateur }}</div>
            </div>

            <div class="mb-3">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption text-medium-emphasis">Taux d'atteinte</span>
                <span class="text-h6 font-weight-bold" :style="{ color: getPerformanceColor(obj.tauxAtteinte) }">
                  {{ obj.tauxAtteinte }}%
                </span>
              </div>
              <v-progress-linear
                :color="getPerformanceColor(obj.tauxAtteinte)"
                height="10"
                :model-value="obj.tauxAtteinte"
                rounded
              />
            </div>

            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-2">Performance mensuelle</div>
              <div class="mini-chart">
                <div
                  v-for="(val, idx) in obj.realisationMensuelle"
                  :key="idx"
                  class="mini-bar"
                  :style="{
                    height: `${val}%`,
                    backgroundColor: getPerformanceColor(val)
                  }"
                  :title="`M${idx + 1}: ${val}%`"
                />
              </div>
            </div>

            <v-divider class="my-3" />

            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center">
                <v-icon class="mr-1" size="small">mdi-playlist-check</v-icon>
                <span class="text-caption">{{ obj.actions.length }} action(s)</span>
              </div>
              <v-chip size="x-small" variant="outlined">{{ obj.frequence }}</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- List View with Double Scroll (RT-05 & REQ-6.2-08) -->
    <v-card v-else elevation="2" rounded="lg">
      <DoubleScrollWrapper
        :has-top-scroll="true"
        :sticky-first-col="true"
        :sticky-last-col="true"
      >
        <v-table class="objectives-table">
          <thead>
            <tr>
              <th v-if="isColVisible('num')" class="font-weight-bold">N°</th>
              <th v-if="isColVisible('processus')" class="font-weight-bold">Processus</th>
              <th v-if="isColVisible('titre')" class="font-weight-bold">Objectif</th>
              <th v-if="isColVisible('axe')" class="font-weight-bold">Axe stratégique</th>
              <th v-if="isColVisible('indicateur')" class="font-weight-bold">Indicateur</th>
              <th v-if="isColVisible('frequence')" class="font-weight-bold">Fréquence</th>
              <th v-if="isColVisible('m1')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 0 }">Jan</th>
              <th v-if="isColVisible('m2')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 1 }">Fév</th>
              <th v-if="isColVisible('m3')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 2 }">Mar</th>
              <th v-if="isColVisible('m4')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 3 }">Avr</th>
              <th v-if="isColVisible('m5')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 4 }">Mai</th>
              <th v-if="isColVisible('m6')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 5 }">Juin</th>
              <th v-if="isColVisible('m7')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 6 }">Juil</th>
              <th v-if="isColVisible('m8')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 7 }">Aoû</th>
              <th v-if="isColVisible('m9')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 8 }">Sep</th>
              <th v-if="isColVisible('m10')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 9 }">Oct</th>
              <th v-if="isColVisible('m11')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 10 }">Nov</th>
              <th v-if="isColVisible('m12')" class="text-center font-weight-bold" :class="{ 'text-grey': currentMonthIndex < 11 }">Déc</th>
              <th v-if="isColVisible('taux_actuel')" class="font-weight-bold text-center">Taux actuel (§8)</th>
              <th v-if="isColVisible('actions_count')" class="text-center font-weight-bold">Actions</th>
              <th v-if="isColVisible('actions')" class="text-right font-weight-bold">Opérations</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(obj, idx) in filteredObjectives"
              :key="obj.id"
              class="list-row cursor-pointer"
              @click="$router.push({ path: `/company/planning/objectives/${obj.id}`, query: { process_id: String(obj.processId) } })"
            >
              <td v-if="isColVisible('num')">{{ idx + 1 }}</td>
              <td v-if="isColVisible('processus')">
                <v-chip color="primary" size="small" variant="tonal">
                  {{ obj.processus }}
                </v-chip>
              </td>
              <td v-if="isColVisible('titre')" class="font-weight-medium">{{ obj.titre }}</td>
              <td v-if="isColVisible('axe')">
                <v-chip v-if="obj.axesStrategiques.length > 0" :color="getAxeColor(obj.axeStrategique)" size="small" variant="flat">
                  {{ axisDisplayLabel(obj.axeStrategique) }}
                </v-chip>
              </td>
              <td v-if="isColVisible('indicateur')">{{ obj.indicateur }}</td>
              <td v-if="isColVisible('frequence')">
                <v-chip size="small" variant="outlined">{{ obj.frequence }}</v-chip>
              </td>
              <!-- Months Jan to Dec -->
              <td
                v-for="mIdx in 12"
                v-if="isColVisible(`m${mIdx}`)"
                :key="`m-${mIdx}`"
                class="text-center"
                :class="{ 'bg-grey-lighten-5 text-grey-lighten-1': mIdx - 1 > currentMonthIndex }"
              >
                <v-chip
                  v-if="isRateNA(obj.realisationMensuelle?.[mIdx - 1])"
                  color="grey"
                  size="x-small"
                  variant="tonal"
                >
                  N/A
                </v-chip>
                <span v-else-if="isRateEmpty(obj.realisationMensuelle?.[mIdx - 1])" class="text-grey">—</span>
                <span v-else class="text-caption font-weight-medium">
                  {{ obj.realisationMensuelle?.[mIdx - 1] }}%
                </span>
              </td>
              <!-- Taux actuel (§8) -->
              <td v-if="isColVisible('taux_actuel')" style="min-width: 140px;">
                <div v-if="getTauxActuel(obj) !== null" class="d-flex align-center" style="gap: 8px;">
                  <v-progress-linear
                    :color="getPerformanceColor(getTauxActuel(obj)!)"
                    height="8"
                    :model-value="getTauxActuel(obj)!"
                    rounded
                    style="width: 70px;"
                  />
                  <span class="font-weight-bold text-caption" :style="{ color: getPerformanceColor(getTauxActuel(obj)!) }">
                    {{ getTauxActuel(obj) }}%
                  </span>
                </div>
                <span v-else class="text-grey text-caption">—</span>
              </td>
              <td v-if="isColVisible('actions_count')" class="text-center">
                {{ obj.actions.length }}
              </td>
              <td v-if="isColVisible('actions')" class="actions-cell text-right" @click.stop>
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click.stop="openDialog(obj)"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click.stop="deleteObjective(obj.id)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </DoubleScrollWrapper>
    </v-card>

    <!-- Empty State -->
    <ObjectivesEmptyState
      v-if="filteredObjectives.length === 0"
      :message="emptyStateMessage"
      @add="openDialog()"
    />

    <ObjectivesFormDialog
      v-model="dialog"
      :axis-options="axisOptions"
      :collaborator-options="collaboratorOptions"
      :current-step="currentStep"
      :edit-mode="editMode"
      :form-data="formData"
      :get-col-size="getColSize"
      :get-performance-color="getPerformanceColor"
      :get-period-label="getPeriodLabel"
      :get-period-name="getPeriodName"
      :loading="loading"
      :process-name-options="processNameOptions"
      @add-action="addAction"
      @close="closeDialog"
      @remove-action="removeAction"
      @save="saveObjective"
      @update-realisation="updateRealisationPeriods"
      @update:current-step="currentStep = $event"
    />

    <ObjectivesImportDialog
      v-model="importDialog"
      :filtered-processes="filteredProcesses"
      :search-process="searchProcess"
      :selected-processes="selectedProcesses"
      @import="importObjectives"
      @toggle-process="toggleProcess"
      @update:search-process="searchProcess = $event"
    />
    <PreviewExportModal
      v-model="previewModalOpen"
      :filename="previewFilename"
      :filesize="previewFilesize"
      mime-type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
      :preview-url="previewBlobUrl"
      title="Prévisualisation de l'export Excel des objectifs"
      @confirm-download="handleConfirmDownload"
    />
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ObjectivesEmptyState from '@/modules/clienta/pages/iso/planning/components/ObjectivesEmptyState.vue'
  import ObjectivesFiltersBar from '@/modules/clienta/pages/iso/planning/components/ObjectivesFiltersBar.vue'
  import ObjectivesFormDialog from '@/modules/clienta/pages/iso/planning/components/ObjectivesFormDialog.vue'
  import ObjectivesGrid from '@/modules/clienta/pages/iso/planning/components/ObjectivesGrid.vue'
  import ObjectivesImportDialog from '@/modules/clienta/pages/iso/planning/components/ObjectivesImportDialog.vue'
  import ObjectivesTable from '@/modules/clienta/pages/iso/planning/components/ObjectivesTable.vue'
  import ObjectivesRecapViews from '@/modules/clienta/pages/planning/components/ObjectivesRecapViews.vue'
  import ColumnVisibilitySelector, { type ColumnDefinition } from '@/modules/shared/components/ColumnVisibilitySelector.vue'
  import DoubleScrollWrapper from '@/modules/shared/components/DoubleScrollWrapper.vue'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import {
    calculateCurrentObjectiveRate,
    calculateSystemRecaps,
    isRateNA,
    isRateEmpty,
    type SystemRecapSummary,
  } from '@/modules/clienta/utils/objectiveCalculations'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { leadershipService } from '@/services/leadershipService'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  interface Objective {
    id: number
    processId: number
    indicatorId?: number
    processus: string
    titre: string
    axeStrategique: string
    axesStrategiques: string[]
    normes?: string[]
    indicateur: string
    modeCalcul: string
    frequence: string
    realisationMensuelle: number[]
    tauxAtteinte: number
    observation: string
    actionsAMettreEnOeuvre: string
    targetDate?: string
    measurementFrequency: 'monthly' | 'quarterly' | 'semiannual' | 'annual'
    periodRealizations: number[]
    status?: string
    // responsable: string (retiré - uniquement dans actions)
    ressources: string
    actions: Action[]
  }

  interface Action {
    description: string
    responsableUserId?: number
    responsable: string
    responsablesImpliquesIds: number[]
    responsablesImpliques: string
    delai: string
    progressRate: number
    trackingAvailable?: boolean
    linkedActionId?: number | null
    statut: string
  }

  const authStore = useAuthStore()
  const router = useRouter()
  const toast = useToast()
  const search = ref('')
  const filterProcessus = ref(null)
  const filterAxe = ref(null)
  const filterFrequence = ref(null)
  const viewMode = ref('grid')
  const showRecaps = ref(true)

  const allObjectiveColumns: ColumnDefinition[] = [
    { key: 'num', title: 'N°', mandatory: true },
    { key: 'processus', title: 'Processus', mandatory: true },
    { key: 'titre', title: 'Objectif', mandatory: true },
    { key: 'axe', title: 'Axe stratégique', defaultVisible: true },
    { key: 'indicateur', title: 'Indicateur', defaultVisible: true },
    { key: 'frequence', title: 'Fréquence', defaultVisible: false },
    { key: 'm1', title: 'Jan', defaultVisible: true },
    { key: 'm2', title: 'Fév', defaultVisible: true },
    { key: 'm3', title: 'Mar', defaultVisible: true },
    { key: 'm4', title: 'Avr', defaultVisible: true },
    { key: 'm5', title: 'Mai', defaultVisible: true },
    { key: 'm6', title: 'Juin', defaultVisible: true },
    { key: 'm7', title: 'Juil', defaultVisible: true },
    { key: 'm8', title: 'Aoû', defaultVisible: true },
    { key: 'm9', title: 'Sep', defaultVisible: true },
    { key: 'm10', title: 'Oct', defaultVisible: true },
    { key: 'm11', title: 'Nov', defaultVisible: true },
    { key: 'm12', title: 'Déc', defaultVisible: true },
    { key: 'taux_actuel', title: 'Taux actuel (§8)', mandatory: true },
    { key: 'actions_count', title: 'Actions', defaultVisible: true },
    { key: 'actions', title: 'Opérations', mandatory: true },
  ]
  const visibleObjectiveColumns = ref<string[]>(allObjectiveColumns.map(c => c.key))

  function isColVisible (key: string): boolean {
    return visibleObjectiveColumns.value.includes(key)
  }

  const currentMonthIndex = computed(() => new Date().getMonth())

  const systemSummary = computed<SystemRecapSummary>(() => {
    return calculateSystemRecaps(
      filteredObjectives.value.map(obj => ({
        id: obj.id,
        processId: obj.processId,
        processName: obj.processus,
        strategicAxes: obj.axesStrategiques,
        strategicAxis: obj.axeStrategique,
        norms: obj.normes,
        realisationMensuelle: obj.realisationMensuelle,
      })),
      currentMonthIndex.value,
    )
  })

  function getTauxActuel (obj: Objective): number | null {
    return calculateCurrentObjectiveRate(obj.realisationMensuelle, currentMonthIndex.value)
  }

  // Preview Export Modal State (RT-01, Contract C9)
  const previewModalOpen = ref(false)
  const previewBlobUrl = ref<string | null>(null)
  const previewFilename = ref('objectifs.xlsx')
  const previewFilesize = ref<number | undefined>(undefined)

  function handleConfirmDownload () {
    if (!previewBlobUrl.value) return
    const a = document.createElement('a')
    a.href = previewBlobUrl.value
    a.download = previewFilename.value
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    toast.success('Téléchargement effectué.')
    previewModalOpen.value = false
  }

  const dialog = ref(false)
  const editMode = ref(false)
  const editingId = ref<number | null>(null)
  const importDialog = ref(false)
  const searchProcess = ref('')
  const selectedProcesses = ref<number[]>([])
  const currentStep = ref(1)
  const loading = ref(false)
  const exporting = ref(false)
  const policyAxes = ref<string[]>([])
  const collaborators = ref<Array<{ id: number, name: string }>>([])
  const TEMPLATE_SHEET_OBJECTIVES = 'OBJECTIFS'
  const TEMPLATE_SHEET_EVALUATION = 'EVALUATION'
  const TEMPLATE_FIRST_DATA_ROW = 7
  const TEMPLATE_FIRST_DATA_COL = 0 // A
  const TEMPLATE_LAST_DATA_COL = 24 // Y

  const formData = ref({
    processus: '',
    titre: '',
    axeStrategique: '',
    axesStrategiques: [] as string[],
    indicateur: '',
    modeCalcul: '',
    frequence: 'Mensuel',
    realisationMensuelle: Array.from({ length: 12 }).fill(0) as number[],
    observation: '',
    ressources: '',
    actionsAMettreEnOeuvre: '',
    actions: [] as Action[],
  })

  const objectives = ref<Objective[]>([])
  const availableProcesses = ref<
    Array<{
      id: number
      name: string
      type: string
      objectivesCount: number
      indicators: Array<{ id: number, code: string, name: string }>
      objectives: Array<{ titre: string, indicateur: string }>
    }>
  >([])

  const axisOptions = computed(() => {
    if (policyAxes.value.length > 0) {
      return policyAxes.value
    }

    const fromObjectives = new Set<string>()
    for (const objective of objectives.value) {
      for (const axis of objective.axesStrategiques || []) {
        const normalized = String(axis || '').trim()
        if (normalized.length > 0) {
          fromObjectives.add(normalized)
        }
      }
    }

    return Array.from(fromObjectives)
  })
  const processNameOptions = computed(() =>
    availableProcesses.value.map(p => p.name),
  )
  const collaboratorOptions = computed(() =>
    collaborators.value.map(c => ({ title: c.name, value: c.id })),
  )

  const processusList = computed(() => [
    ...new Set(objectives.value.map(o => o.processus)),
  ])

  const filteredProcesses = computed(() => {
    if (!searchProcess.value) return availableProcesses.value
    return availableProcesses.value.filter(p =>
      p.name.toLowerCase().includes(searchProcess.value.toLowerCase()),
    )
  })

  const filteredObjectives = computed(() => {
    return objectives.value.filter(obj => {
      const matchSearch
        = !search.value
          || obj.titre.toLowerCase().includes(search.value.toLowerCase())
          || obj.indicateur.toLowerCase().includes(search.value.toLowerCase())
      const matchProcessus
        = !filterProcessus.value || obj.processus === filterProcessus.value
      const matchAxe
        = !filterAxe.value
          || obj.axesStrategiques.includes(String(filterAxe.value))
      const matchFrequence
        = !filterFrequence.value || obj.frequence === filterFrequence.value
      return matchSearch && matchProcessus && matchAxe && matchFrequence
    })
  })
  const hasActiveFilters = computed(() => {
    return Boolean(
      search.value.trim()
      || filterProcessus.value
        || filterAxe.value
      || filterFrequence.value,
    )
  })
  const emptyStateMessage = computed(() => {
    if (objectives.value.length === 0) {
      return 'Commencez par créer votre premier objectif système.'
    }

    return 'Aucun objectif ne correspond aux filtres sélectionnés.'
  })

  function resetFilters () {
    search.value = ''
    filterProcessus.value = null
    filterAxe.value = null
    filterFrequence.value = null
  }

  function axisDisplayLabel (axe: string) {
    const index = policyAxes.value.indexOf(axe)
    if (index !== -1) {
      return `Axe ${index + 1}`
    }
    return axe || 'Axe'
  }

  function getAxeGradient (axe: string) {
    const gradients = [
      'linear-gradient(135deg, #667eea 0%, #c0392b 100%)',
      'linear-gradient(135deg, #16a085 0%, #f5576c 100%)',
      'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    ]
    const index = policyAxes.value.indexOf(axe)
    const safeIndex = Math.max(index, 0)
    return gradients[safeIndex % gradients.length]
  }

  function getAxeColor (axe: string) {
    const colors = ['deep-#e67e22', 'pink', 'cyan', '#27ae60', 'teal']
    const index = policyAxes.value.indexOf(axe)
    const safeIndex = Math.max(index, 0)
    return colors[safeIndex % colors.length]
  }

  function getPerformanceColor (value: number) {
    if (value >= 80) return '#22c55e'
    if (value >= 50) return '#f59e0b'
    return '#ef4444'
  }

  function toMeasurementFrequency (
    value: string,
  ): 'monthly' | 'quarterly' | 'semiannual' | 'annual' {
    if (value === 'Trimestriel') return 'quarterly'
    if (value === 'Semestriel') return 'semiannual'
    if (value === 'Annuel') return 'annual'
    return 'monthly'
  }

  function toFrequencyLabel (
    value: 'monthly' | 'quarterly' | 'semiannual' | 'annual',
  ): string {
    if (value === 'quarterly') return 'Trimestriel'
    if (value === 'semiannual') return 'Semestriel'
    if (value === 'annual') return 'Annuel'
    return 'Mensuel'
  }

  function openDialog (objective?: Objective) {
    if (objective) {
      editMode.value = true
      editingId.value = objective.id
      formData.value = {
        processus: objective.processus,
        titre: objective.titre,
        axeStrategique: objective.axeStrategique,
        axesStrategiques:
          objective.axesStrategiques.length > 0
            ? [...objective.axesStrategiques]
            : [objective.axeStrategique],
        indicateur: objective.indicateur,
        modeCalcul: objective.modeCalcul,
        frequence: objective.frequence,
        realisationMensuelle: [...objective.realisationMensuelle],
        observation: objective.observation,
        ressources: objective.ressources,
        actionsAMettreEnOeuvre: objective.actionsAMettreEnOeuvre,
        actions: objective.actions.map(action => ({
          ...action,
          responsablesImpliquesIds: [...(action.responsablesImpliquesIds || [])],
        })),
      }
    } else {
      editMode.value = false
      editingId.value = null
      formData.value = {
        processus: '',
        titre: '',
        axeStrategique: axisOptions.value.length > 0 ? axisOptions.value[0] : '',
        axesStrategiques:
          axisOptions.value.length > 0 ? [axisOptions.value[0]] : [],
        indicateur: '',
        modeCalcul: '',
        frequence: 'Mensuel',
        realisationMensuelle: Array.from({ length: 12 }).fill(0) as number[],
        observation: '',
        ressources: '',
        actionsAMettreEnOeuvre: '',
        actions: [],
      }
    }
    dialog.value = true
  }

  function closeDialog () {
    dialog.value = false
    editMode.value = false
    editingId.value = null
    currentStep.value = 1
  }

  function updateRealisationPeriods () {
    const periods: Record<string, number> = {
      Mensuel: 12,
      Trimestriel: 4,
      Semestriel: 2,
      Annuel: 1,
    }
    const count = periods[formData.value.frequence] || 12
    const current = Array.isArray(formData.value.realisationMensuelle)
      ? formData.value.realisationMensuelle
      : []
    formData.value.realisationMensuelle = Array.from(
      { length: count },
      (_, idx) => Number(current[idx] || 0),
    )
  }

  function getPeriodLabel () {
    const labels: Record<string, string> = {
      Mensuel: '12 mois',
      Trimestriel: '4 trimestres',
      Semestriel: '2 semestres',
      Annuel: '1 an',
    }
    return labels[formData.value.frequence] || '12 mois'
  }

  function getPeriodName (index: number) {
    const freq = formData.value.frequence
    if (freq === 'Mensuel') return `M${index + 1}`
    if (freq === 'Trimestriel') return `T${index + 1}`
    if (freq === 'Semestriel') return `S${index + 1}`
    return 'Année'
  }

  function getColSize () {
    const freq = formData.value.frequence
    if (freq === 'Mensuel') return '2'
    if (freq === 'Trimestriel') return '3'
    if (freq === 'Semestriel') return '6'
    return '12'
  }

  function toPlannedActionStatus (
    value: string,
  ): 'a_faire' | 'en_cours' | 'terminee' {
    const normalized = String(value || '').toLowerCase()
    if (normalized.includes('cours')) return 'en_cours'
    if (normalized.includes('termin')) return 'terminee'
    return 'a_faire'
  }

  function toPlannedActionLabel (value: string): string {
    const normalized = String(value || '').toLowerCase()
    if (normalized === 'a_faire') return 'À faire'
    if (normalized === 'en_cours') return 'En cours'
    if (normalized === 'terminee') return 'Terminé'
    return value || 'À faire'
  }

  function getFrequencyCount (
    frequency: 'monthly' | 'quarterly' | 'semiannual' | 'annual',
  ) {
    const counts = { monthly: 12, quarterly: 4, semiannual: 2, annual: 1 }
    return counts[frequency] || 12
  }

  function normalizePeriodValuesForFrequency (
    values: number[],
    frequency: 'monthly' | 'quarterly' | 'semiannual' | 'annual',
  ) {
    const count = getFrequencyCount(frequency)
    return Array.from({ length: count }, (_, idx) => Number(values[idx] || 0))
  }

  function getObjectiveEvaluation (rate: number) {
    if (rate < 30) {
      return {
        interpretation: 'Mauvais résultat',
        recommendation:
          'Action corrective immédiate, analyse des causes et suivi renforcé',
      }
    }
    if (rate < 50) {
      return {
        interpretation: 'Résultat faible',
        recommendation: 'Actions correctives ciblées et réévaluation régulière',
      }
    }
    if (rate < 75) {
      return {
        interpretation: 'Résultat moyen',
        recommendation: 'Plan d\'amélioration et suivi d\'efficacité',
      }
    }
    if (rate < 90) {
      return {
        interpretation: 'Résultat satisfaisant',
        recommendation:
          'Consolider les acquis et documenter les bonnes pratiques',
      }
    }
    return {
      interpretation: 'Très bonne performance',
      recommendation:
        'Capitaliser, encourager et rechercher l\'amélioration continue',
    }
  }

  async function exportObjectivesXlsx () {
    if (objectives.value.length === 0) {
      toast.info('Aucun objectif à exporter.')
      return
    }

    exporting.value = true
    try {
      const XLSX = await import('xlsx')
      const workbook = await loadObjectiveTemplateWorkbook(XLSX)
      const sheet = resolveTemplateSheet(workbook)
      const templateLastRow = getTemplateLastRow(XLSX, sheet)
      const objectivesToExport = objectives.value
      const exportRows = buildObjectiveActionRows(objectivesToExport)
      const siteAxes = resolveSiteAxesForExport(objectivesToExport)
      const siteId = getCurrentSiteId()
      let documentCode = ''
      if (siteId) {
        try {
          const codeResponse = await api.get('/documents/preview-code', {
            params: {
              site_id: siteId,
              type: 'ENR',
            },
          })
          documentCode = String(codeResponse?.data?.data?.code || '').trim()
        } catch (error) {
          console.warn('[Objectives] document code preview unavailable', error)
          toast.warning('Code documentaire indisponible: export généré sans code.')
        }
      }

      setDefaultActiveSheet(workbook, TEMPLATE_SHEET_EVALUATION)
      clearTemplateDataRows(XLSX, sheet, TEMPLATE_FIRST_DATA_ROW, templateLastRow)
      writeAxesHeaders(XLSX, sheet, siteAxes)
      if (documentCode) {
        writeCell(XLSX, sheet, 2, 0, `Code documentaire: ${documentCode}`)
      }

      let previousObjectiveKey = ''
      let objectiveGroupStartRow = TEMPLATE_FIRST_DATA_ROW
      let previousProcessKey = ''
      let processGroupStartRow = TEMPLATE_FIRST_DATA_ROW

      for (const [index, exportRow] of exportRows.entries()) {
        const rowNumber = TEMPLATE_FIRST_DATA_ROW + index
        const objectiveKey = `${exportRow.objective.processId}-${exportRow.objective.id}`
        const processKey = String(exportRow.objective.processId || exportRow.objective.processus || '')

        if (index === 0) {
          previousObjectiveKey = objectiveKey
          objectiveGroupStartRow = rowNumber
          previousProcessKey = processKey
          processGroupStartRow = rowNumber
        } else if (objectiveKey !== previousObjectiveKey) {
          mergeObjectiveRows(sheet, objectiveGroupStartRow, rowNumber - 1)
          previousObjectiveKey = objectiveKey
          objectiveGroupStartRow = rowNumber
        }

        if (index > 0 && processKey !== previousProcessKey) {
          mergeProcessRows(sheet, processGroupStartRow, rowNumber - 1)
          previousProcessKey = processKey
          processGroupStartRow = rowNumber
        }

        fillObjectiveRow(
          XLSX,
          sheet,
          exportRow.objective,
          exportRow.action,
          exportRow.actionIndex,
          exportRow.actionCount,
          siteAxes,
          exportRow.objectiveSequence,
          rowNumber,
        )
      }
      mergeObjectiveRows(sheet, objectiveGroupStartRow, TEMPLATE_FIRST_DATA_ROW + exportRows.length - 1)
      mergeProcessRows(sheet, processGroupStartRow, TEMPLATE_FIRST_DATA_ROW + exportRows.length - 1)

      const date = new Date().toISOString().slice(0, 10)
      const filenameCode = documentCode ? `_${documentCode.replace(/[^A-Za-z0-9-_]/g, '-')}` : ''
      const targetFilename = `objectifs_${date}${filenameCode}.xlsx`

      const xlsxBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' })
      const blob = new Blob([xlsxBuffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      if (previewBlobUrl.value) {
        URL.revokeObjectURL(previewBlobUrl.value)
      }
      previewBlobUrl.value = URL.createObjectURL(blob)
      previewFilename.value = targetFilename
      previewFilesize.value = blob.size
      previewModalOpen.value = true
    } catch (error) {
      console.error('[Objectives] Export failed', error)
      toast.error('Impossible d\'exporter les objectifs.')
    } finally {
      exporting.value = false
    }
  }

  async function loadObjectiveTemplateWorkbook (XLSX: any) {
    const response = await fetch('/canevas-objectifs.xlsx')
    if (!response.ok) throw new Error('template_unavailable')
    const buffer = await response.arrayBuffer()
    return XLSX.read(buffer, { type: 'array', cellStyles: true })
  }

  function resolveTemplateSheet (workbook: any) {
    const preferred = workbook.Sheets[TEMPLATE_SHEET_OBJECTIVES]
    if (preferred) return preferred
    const firstSheetName = workbook.SheetNames?.[0]
    if (!firstSheetName || !workbook.Sheets[firstSheetName])
      throw new Error('sheet_not_found')
    return workbook.Sheets[firstSheetName]
  }

  function setDefaultActiveSheet (workbook: any, sheetName: string) {
    const sheetIndex = Array.isArray(workbook?.SheetNames)
      ? workbook.SheetNames.indexOf(sheetName)
      : -1
    if (sheetIndex < 0) return

    if (!workbook.Workbook) workbook.Workbook = {}
    if (
      !Array.isArray(workbook.Workbook.WBView)
      || workbook.Workbook.WBView.length === 0
    ) {
      workbook.Workbook.WBView = [{}]
    }

    workbook.Workbook.WBView[0].activeTab = sheetIndex
  }

  function getTemplateLastRow (XLSX: any, sheet: any): number {
    if (!sheet?.['!ref']) return TEMPLATE_FIRST_DATA_ROW
    const range = XLSX.utils.decode_range(sheet['!ref'])
    return Math.max(TEMPLATE_FIRST_DATA_ROW, range.e.r + 1)
  }

  function clearTemplateDataRows (
    XLSX: any,
    sheet: any,
    fromRow: number,
    toRow: number,
  ) {
    for (let rowNumber = fromRow; rowNumber <= toRow; rowNumber++) {
      clearMonthlyMergesForRow(sheet, rowNumber)
      for (
        let colIndex = TEMPLATE_FIRST_DATA_COL;
        colIndex <= TEMPLATE_LAST_DATA_COL;
        colIndex++
      ) {
        writeCell(XLSX, sheet, rowNumber, colIndex, '')
      }
    }
  }

  function resolveSiteAxesForExport (objectivesToExport: Objective[]): string[] {
    const normalizedPolicyAxes = policyAxes.value
      .map(axis => String(axis || '').trim())
      .filter(Boolean)
    if (normalizedPolicyAxes.length > 0) {
      return normalizedPolicyAxes
    }

    const discoveredAxes = new Set<string>()
    for (const objective of objectivesToExport) {
      for (const axis of objective.axesStrategiques || []) {
        const normalized = String(axis || '').trim()
        if (normalized) discoveredAxes.add(normalized)
      }
    }

    return Array.from(discoveredAxes)
  }

  function writeAxesHeaders (XLSX: any, sheet: any, axes: string[]) {
    for (let i = 0; i < 3; i++) {
      const header = axes[i] ? `AXE ${i + 1} - ${axes[i]}` : ''
      writeCell(XLSX, sheet, 6, 3 + i, header)
    }

    // Keep the label in the merged title row aligned with the actual axis count on site.
    writeCell(XLSX, sheet, 5, 3, `AXE STRATEGIQUE (${axes.length})`)
    ensureSheetRange(XLSX, sheet, 4, 5)
  }

  function buildObjectiveActionRows (objectivesToExport: Objective[]) {
    const rows: Array<{
      objective: Objective
      action: Action
      actionIndex: number
      actionCount: number
      objectiveSequence: number
    }> = []

    for (const [objectiveIndex, objective] of objectivesToExport.entries()) {
      const actions = Array.isArray(objective.actions) && objective.actions.length > 0
        ? objective.actions
        : [{
          description: objective.actionsAMettreEnOeuvre || '',
          responsable: '',
          responsablesImpliques: '',
          responsablesImpliquesIds: [],
          delai: '',
          progressRate: 0,
          statut: '',
        } as Action]

      for (const [index, action] of actions.entries()) {
        rows.push({
          objective,
          action,
          actionIndex: index + 1,
          actionCount: actions.length,
          objectiveSequence: objectiveIndex + 1,
        })
      }
    }

    return rows
  }

  function buildResponsibleCellValue (action: Action) {
    const principal = String(action.responsable || '').trim()
      || (action.responsableUserId
        ? (collaborators.value.find(user => user.id === action.responsableUserId)?.name || '')
        : '')

    const impliques = String(action.responsablesImpliques || '').trim()
      || ((action.responsablesImpliquesIds || [])
        .map(id => collaborators.value.find(user => user.id === id)?.name || '')
        .filter(Boolean)
        .join(', '))
    const parts = [
      principal ? `Resp: ${principal}` : '',
      impliques ? `Impliqués: ${impliques}` : '',
    ].filter(Boolean)
    return parts.join(' | ')
  }

  function buildActionMetaCellValue (objective: Objective, action: Action) {
    const parts = [
      action.delai ? `Délai: ${action.delai}` : '',
      action.statut ? `Statut: ${action.statut}` : '',
      `Taux: ${Number(action.progressRate || 0)}%`,
      objective.ressources ? `Ressources: ${objective.ressources}` : '',
    ].filter(Boolean)
    return parts.join(' | ')
  }

  function mergeObjectiveRows (sheet: any, startRow: number, endRow: number) {
    if (!sheet || endRow <= startRow) return

    const merges = Array.isArray(sheet['!merges']) ? sheet['!merges'] : []
    const columnsToMerge = [0, 2, 3, 4, 5, 6, 7, 8, 21] // A + C..I + V (B géré par processus)

    for (const col of columnsToMerge) {
      merges.push({
        s: { r: startRow - 1, c: col },
        e: { r: endRow - 1, c: col },
      })
    }

    sheet['!merges'] = merges
  }

  function mergeProcessRows (sheet: any, startRow: number, endRow: number) {
    if (!sheet || endRow <= startRow) return

    const merges = Array.isArray(sheet['!merges']) ? sheet['!merges'] : []
    merges.push({
      s: { r: startRow - 1, c: 1 }, // B
      e: { r: endRow - 1, c: 1 },
    })
    sheet['!merges'] = merges
  }

  function fillObjectiveRow (
    XLSX: any,
    sheet: any,
    objective: Objective,
    action: Action,
    actionIndex: number,
    actionCount: number,
    siteAxes: string[],
    sequence: number,
    rowNumber: number,
  ) {
    cloneRowStyleFromTemplate(
      XLSX,
      sheet,
      TEMPLATE_FIRST_DATA_ROW,
      rowNumber,
      TEMPLATE_FIRST_DATA_COL,
      TEMPLATE_LAST_DATA_COL,
    )

    const actionDescription = String(action.description || objective.actionsAMettreEnOeuvre || '').trim()
    const actionPrefix = actionCount > 1 ? `Action ${actionIndex}/${actionCount} - ` : ''
    const actionCellValue = `${actionPrefix}${actionDescription}`.trim() || 'Aucune action définie'
    const responsibleCellValue = buildResponsibleCellValue(action)
    const actionMetaCellValue = buildActionMetaCellValue(objective, action)
    const schedule = buildScheduleValue(objective)
    const axisMarks = getAxisMarksForObjective(objective, siteAxes)

    writeCell(XLSX, sheet, rowNumber, 0, sequence) // A
    writeCell(XLSX, sheet, rowNumber, 1, objective.processus || '') // B
    writeCell(XLSX, sheet, rowNumber, 2, objective.titre || '') // C
    writeCell(XLSX, sheet, rowNumber, 3, axisMarks[0]) // D
    writeCell(XLSX, sheet, rowNumber, 4, axisMarks[1]) // E
    writeCell(XLSX, sheet, rowNumber, 5, axisMarks[2]) // F
    writeCell(XLSX, sheet, rowNumber, 6, objective.indicateur || '') // G
    writeCell(XLSX, sheet, rowNumber, 7, objective.modeCalcul || '') // H
    writeCell(XLSX, sheet, rowNumber, 8, schedule) // I

    writeObjectivePeriodValues(XLSX, sheet, rowNumber, objective)

    writeCell(XLSX, sheet, rowNumber, 21, objective.observation || '') // V
    writeCell(XLSX, sheet, rowNumber, 22, actionCellValue) // W
    writeCell(XLSX, sheet, rowNumber, 23, responsibleCellValue) // X
    writeCell(XLSX, sheet, rowNumber, 24, actionMetaCellValue) // Y
  }

  function getAxisMarksForObjective (
    objective: Objective,
    siteAxes: string[],
  ): [string, string, string] {
    const objectiveAxes = new Set(
      (objective.axesStrategiques || [])
        .map(axis => normalizeAxisName(axis))
        .filter(Boolean),
    )

    const marks: [string, string, string] = ['', '', '']
    for (let i = 0; i < 3; i++) {
      const axis = siteAxes[i]
      if (!axis) continue
      marks[i] = objectiveAxes.has(normalizeAxisName(axis)) ? 'X' : ''
    }
    return marks
  }

  function normalizeAxisName (value: string): string {
    return String(value || '')
      .trim()
      .toLowerCase()
  }

  function writeObjectivePeriodValues (
    XLSX: any,
    sheet: any,
    rowNumber: number,
    objective: Objective,
  ) {
    const source = Array.isArray(objective.realisationMensuelle)
      ? objective.realisationMensuelle
      : []
    const periods = resolvePeriodsFromFrequency(objective.measurementFrequency)

    clearMonthlyMergesForRow(sheet, rowNumber)

    for (let i = 0; i < 12; i++) {
      writeCell(XLSX, sheet, rowNumber, 9 + i, '')
    }

    for (const [index, period] of periods.entries()) {
      if (period.endCol > period.startCol) {
        addMerge(sheet, rowNumber, period.startCol, period.endCol)
      }
      writeCell(
        XLSX,
        sheet,
        rowNumber,
        period.startCol,
        Number(source[index] || 0),
      )
    }
  }

  function resolvePeriodsFromFrequency (
    frequency: Objective['measurementFrequency'],
  ) {
    if (frequency === 'quarterly') {
      return [
        { startCol: 9, endCol: 11 }, // J:L
        { startCol: 12, endCol: 14 }, // M:O
        { startCol: 15, endCol: 17 }, // P:R
        { startCol: 18, endCol: 20 }, // S:U
      ]
    }

    if (frequency === 'semiannual') {
      return [
        { startCol: 9, endCol: 14 }, // J:O
        { startCol: 15, endCol: 20 }, // P:U
      ]
    }

    if (frequency === 'annual') {
      return [{ startCol: 9, endCol: 20 }] // J:U
    }

    return Array.from({ length: 12 }, (_, idx) => ({
      startCol: 9 + idx,
      endCol: 9 + idx,
    }))
  }

  function clearMonthlyMergesForRow (sheet: any, rowNumber: number) {
    const merges = Array.isArray(sheet['!merges']) ? sheet['!merges'] : []
    sheet['!merges'] = merges.filter((merge: any) => {
      const intersectsRow
        = merge?.s?.r <= rowNumber - 1 && merge?.e?.r >= rowNumber - 1
      const intersectsMonthlyCols = merge?.s?.c <= 20 && merge?.e?.c >= 9
      return !(intersectsRow && intersectsMonthlyCols)
    })
  }

  function addMerge (
    sheet: any,
    rowNumber: number,
    startCol: number,
    endCol: number,
  ) {
    const merges = Array.isArray(sheet['!merges']) ? sheet['!merges'] : []
    merges.push({
      s: { r: rowNumber - 1, c: startCol },
      e: { r: rowNumber - 1, c: endCol },
    })
    sheet['!merges'] = merges
  }

  function buildScheduleValue (objective: Objective): string {
    const deadline = String(objective.targetDate || '').slice(0, 10)
    const frequency = objective.frequence || ''
    if (deadline && frequency) return `${deadline} / ${frequency}`
    return deadline || frequency
  }

  function writeCell (
    XLSX: any,
    sheet: any,
    rowNumber: number,
    colIndex: number,
    value: unknown,
  ) {
    const cellAddress = XLSX.utils.encode_cell({ r: rowNumber - 1, c: colIndex })
    const currentCell = sheet[cellAddress] || {}
    sheet[cellAddress] = {
      ...currentCell,
      t: typeof value === 'number' ? 'n' : 's',
      v: value ?? '',
    }
    ensureSheetRange(XLSX, sheet, rowNumber - 1, colIndex)
  }

  function ensureSheetRange (
    XLSX: any,
    sheet: any,
    rowIndex: number,
    colIndex: number,
  ) {
    const defaultRange = {
      s: { r: 0, c: 0 },
      e: { r: 0, c: 0 },
    }
    const range = sheet['!ref']
      ? XLSX.utils.decode_range(sheet['!ref'])
      : defaultRange
    range.s.r = Math.min(range.s.r, rowIndex)
    range.s.c = Math.min(range.s.c, colIndex)
    range.e.r = Math.max(range.e.r, rowIndex)
    range.e.c = Math.max(range.e.c, colIndex)
    sheet['!ref'] = XLSX.utils.encode_range(range)
  }

  function cloneRowStyleFromTemplate (
    XLSX: any,
    sheet: any,
    templateRowNumber: number,
    targetRowNumber: number,
    startColIndex: number,
    endColIndex: number,
  ) {
    if (templateRowNumber === targetRowNumber) return

    for (let colIndex = startColIndex; colIndex <= endColIndex; colIndex++) {
      const sourceAddress = XLSX.utils.encode_cell({
        r: templateRowNumber - 1,
        c: colIndex,
      })
      const targetAddress = XLSX.utils.encode_cell({
        r: targetRowNumber - 1,
        c: colIndex,
      })
      const sourceCell = sheet[sourceAddress]
      if (!sourceCell) continue

      sheet[targetAddress] = {
        ...sourceCell,
        v: '',
      }

      ensureSheetRange(XLSX, sheet, targetRowNumber - 1, colIndex)
    }
  }

  function resolveProcessIdFromForm (): number | null {
    if (editMode.value && editingId.value) {
      const existing = objectives.value.find(o => o.id === editingId.value)
      if (existing?.processId) return existing.processId
    }
    const normalizedInput = formData.value.processus.trim().toLowerCase()
    const process = availableProcesses.value.find(
      p => p.name.trim().toLowerCase() === normalizedInput,
    )
    return process?.id || null
  }

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    const resolved
      = authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
    return Number.isFinite(Number(resolved)) ? Number(resolved) : null
  }

  function saveObjective () {
    void persistObjective()
  }

  async function persistObjective () {
    const processId = resolveProcessIdFromForm()
    if (!processId) {
      toast.error('Processus introuvable. Sélectionnez un processus existant.')
      return
    }

    if (!formData.value.indicateur.trim()) {
      toast.error('Le nom de l\'indicateur est requis.')
      return
    }

    if (!formData.value.titre.trim()) {
      toast.error('Le titre de l\'objectif est requis.')
      return
    }
    if (formData.value.axesStrategiques.length === 0) {
      toast.error('Sélectionnez au moins un axe stratégique.')
      return
    }

    loading.value = true
    try {
      const measurementFrequency = toMeasurementFrequency(
        formData.value.frequence,
      )
      const normalizedPeriods = normalizePeriodValuesForFrequency(
        formData.value.realisationMensuelle,
        measurementFrequency,
      )
      const tauxAtteinte
        = normalizedPeriods.length > 0
          ? Math.round(
            normalizedPeriods.reduce((a: number, b: number) => a + b, 0)
            / normalizedPeriods.length,
          )
          : 0
      const evaluation = getObjectiveEvaluation(tauxAtteinte)

      const selectedAxes = formData.value.axesStrategiques
        .map(value => String(value || '').trim())
        .filter(value => value.length > 0)
      const payload = {
        title: formData.value.titre.trim(),
        strategic_axis: selectedAxes[0] || undefined,
        strategic_axes: selectedAxes,
        description: formData.value.actionsAMettreEnOeuvre || undefined,
        calculation_mode: formData.value.modeCalcul || undefined,
        measurement_frequency: measurementFrequency,
        indicator_name: formData.value.indicateur.trim(),
        achievement_percentage: tauxAtteinte,
        period_realizations: normalizedPeriods,
        notes: [formData.value.observation, evaluation.interpretation]
          .filter(Boolean)
          .join(' | '),
        action_plan: formData.value.actionsAMettreEnOeuvre || undefined,
        special_resources: formData.value.ressources || undefined,
        planned_actions: formData.value.actions.map(action => ({
          title: action.description || '',
          responsible_user_id: action.responsableUserId || undefined,
          responsible:
            collaborators.value.find(
              user => user.id === action.responsableUserId,
            )?.name
            || action.responsable
            || '',
          involved_user_ids: action.responsablesImpliquesIds || [],
          involved_users: (action.responsablesImpliquesIds || [])
            .map(id => collaborators.value.find(user => user.id === id)?.name)
            .filter(Boolean),
          due_date: action.delai || undefined,
          progress_rate: Number(action.progressRate || 0),
          status: toPlannedActionStatus(action.statut),
        })),
      }

      await (editMode.value && editingId.value
        ? processService.updateObjective(processId, editingId.value, payload)
        : processService.addObjective(processId, payload))

      await fetchObjectivesForSite()
      toast.success('Objectif enregistré avec succès.')
      closeDialog()
    } catch (error: any) {
      console.error('[Objectives] Save failed', error)
      toast.error(
        error?.response?.data?.message
          || 'Erreur lors de l\'enregistrement de l\'objectif.',
      )
    } finally {
      loading.value = false
    }
  }

  function addAction () {
    formData.value.actions.unshift({
      description: '',
      responsableUserId: undefined,
      responsable: '',
      responsablesImpliquesIds: [],
      responsablesImpliques: '',
      delai: '',
      progressRate: 0,
      statut: 'À faire',
    })
  }

  function removeAction (index: number) {
    formData.value.actions.splice(index, 1)
  }

  function deleteObjective (id: number) {
    const objective = objectives.value.find(o => o.id === id)
    if (!objective) return
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet objectif ?')) return

    loading.value = true
    processService
      .deleteObjective(objective.processId, id)
      .then(() => fetchObjectivesForSite())
      .then(() => toast.success('Objectif supprimé avec succès.'))
      .catch((error: any) => {
        console.error('[Objectives] Delete failed', error)
        toast.error(
          error?.response?.data?.message
            || 'Erreur lors de la suppression de l\'objectif.',
        )
      })
      .finally(() => {
        loading.value = false
      })
  }

  function toggleProcess (id: number) {
    const index = selectedProcesses.value.indexOf(id)
    if (index === -1) {
      selectedProcesses.value.push(id)
    } else {
      selectedProcesses.value.splice(index, 1)
    }
  }

  function importObjectives () {
    const processesToImport = availableProcesses.value.filter(p =>
      selectedProcesses.value.includes(p.id),
    )

    for (const process of processesToImport) {
      for (const [idx, obj] of process.objectives.entries()) {
        objectives.value.push({
          id: Date.now() + idx,
          processId: process.id,
          processus: process.name,
          titre: `OB${objectives.value.length + idx + 1} - ${obj.titre}`,
          axeStrategique: axisOptions.value[0] || '',
          axesStrategiques:
            axisOptions.value.length > 0 ? [axisOptions.value[0]] : [],
          indicateur: obj.indicateur,
          modeCalcul: '',
          frequence: 'Mensuel',
          realisationMensuelle: Array.from({ length: 12 }).fill(0) as number[],
          tauxAtteinte: 0,
          observation: '',
          actionsAMettreEnOeuvre: '',
          targetDate: '',
          measurementFrequency: 'monthly',
          periodRealizations: Array.from({ length: 12 }).fill(0) as number[],
          status: 'not_started',
          ressources: '',
          actions: [],
        })
      }
    }

    selectedProcesses.value = []
    importDialog.value = false
  }

  function buildObjectiveList (processes: any[]) {
    const list: Objective[] = []
    for (const process of processes) {
      const processName
        = process.title || process.name || `Processus #${process.id}`
      const processObjectives = Array.isArray(process.objectives)
        ? process.objectives
        : []
      const processIndicators = Array.isArray(process.indicators)
        ? process.indicators
        : []
      for (const objective of processObjectives) {
        list.push(
          buildObjectiveFromProcessObjective(
            process,
            processName,
            processIndicators,
            objective,
          ),
        )
      }
    }
    objectives.value = list
  }

  function resolveObjectiveAxes (objective: any): string[] {
    const rawAxes
      = Array.isArray(objective.strategic_axes)
        && objective.strategic_axes.length > 0
        ? objective.strategic_axes
          .map((axis: any) => String(axis || '').trim())
          .filter((axis: string) => axis.length > 0)
        : [String(objective.strategic_axis || '').trim()].filter(
          (axis: string) => axis.length > 0,
        )
    const objectiveAxes
      = policyAxes.value.length > 0
        ? rawAxes.filter(axis => policyAxes.value.includes(axis))
        : rawAxes
    return objectiveAxes.length > 0 ? objectiveAxes : rawAxes
  }

  function resolveObjectiveRealizations (
    objective: any,
    measurementFrequency: 'monthly' | 'quarterly' | 'semiannual' | 'annual',
  ) {
    const realizationArray = Array.isArray(objective.period_realizations)
      ? objective.period_realizations.map((value: any) => Number(value || 0))
      : []
    return {
      periodRealizations: realizationArray,
      realisationMensuelle:
        realizationArray.length > 0
          ? realizationArray
          : Array.from({ length: getFrequencyCount(measurementFrequency) }).fill(
            0,
          ),
    }
  }

  function mapPlannedActions (objective: any) {
    const normalizeAction = (rawAction: any): Action | null => {
      if (typeof rawAction === 'string') {
        const description = rawAction.trim()
        return description
          ? {
            description,
            responsableUserId: undefined,
            responsable: '',
            responsablesImpliquesIds: [],
            responsablesImpliques: '',
            delai: '',
            progressRate: 0,
            statut: 'À faire',
          }
          : null
      }

      if (!rawAction || typeof rawAction !== 'object') {
        return null
      }

      const description = String(
        rawAction.title
        || rawAction.description
          || rawAction.action
        || '',
      ).trim()

      const responsibleUserId = Number(rawAction.responsible_user_id || rawAction.responsable_user_id || 0) || undefined
      const involvedUserIdsRaw = rawAction.involved_user_ids || rawAction.responsables_implique_ids || []
      const involvedUsersRaw = rawAction.involved_users || rawAction.responsables_implique || []

      const mapped: Action = {
        description,
        responsableUserId: responsibleUserId,
        responsable: String(rawAction.responsible || rawAction.responsable || '').trim(),
        responsablesImpliquesIds: Array.isArray(involvedUserIdsRaw)
          ? involvedUserIdsRaw.map(Number).filter((id: number) => Number.isFinite(id))
          : [],
        responsablesImpliques: Array.isArray(involvedUsersRaw)
          ? involvedUsersRaw.map((value: any) => String(value || '').trim()).filter(Boolean).join(', ')
          : String(involvedUsersRaw || '').trim(),
        delai: String(rawAction.due_date || rawAction.delai || '').trim(),
        progressRate: Number(rawAction.progress_rate || 0),
        trackingAvailable: Boolean(rawAction.tracking_available),
        linkedActionId: rawAction.linked_action_id ? Number(rawAction.linked_action_id) : null,
        statut: toPlannedActionLabel(String(rawAction.status || rawAction.statut || 'À faire').trim() || 'À faire'),
      }

      const hasAnyField = Boolean(
        mapped.description
        || mapped.responsable
          || mapped.responsablesImpliques
        || mapped.delai
          || mapped.statut,
      )
      return hasAnyField ? mapped : null
    }

    const parseActionsFromActionPlan = (value: unknown): Action[] => {
      if (typeof value !== 'string' || value.trim().length === 0) return []
      return value
        .split(/\r?\n|;/g)
        .map(part => part.replace(/^\s*(?:[-*]|\d+[.)])\s*/, '').trim())
        .filter(Boolean)
        .map(description => ({
          description,
          responsableUserId: undefined,
          responsable: '',
          responsablesImpliquesIds: [],
          responsablesImpliques: '',
          delai: '',
          progressRate: 0,
          statut: 'À faire',
        }))
    }

    const source = objective?.planned_actions
    let sourceArray: any[] = []

    if (Array.isArray(source)) {
      sourceArray = source
    } else if (typeof source === 'string' && source.trim().length > 0) {
      try {
        const parsed = JSON.parse(source)
        if (Array.isArray(parsed)) {
          sourceArray = parsed
        }
      } catch {
        sourceArray = source.split(/\r?\n|;/g).map((part: string) => part.trim()).filter(Boolean)
      }
    }

    const normalized = sourceArray
      .map(normalizeAction)
      .filter(Boolean)

    if (normalized.length > 0) {
      return normalized
    }

    return parseActionsFromActionPlan(objective?.action_plan || objective?.actions_prevues || objective?.description)
  }

  function resolveObjectiveIndicatorName (
    objective: any,
    processIndicators: any[],
  ) {
    const linkedIndicator = processIndicators.find(
      (indicator: any) => Number(indicator.id) === Number(objective.indicator_id),
    )
    return linkedIndicator
      ? linkedIndicator.name || 'Indicateur'
      : objective.indicator_name || objective.indicator?.name || 'Indicateur'
  }

  function buildObjectiveFromProcessObjective (
    process: any,
    processName: string,
    processIndicators: any[],
    objective: any,
  ): Objective {
    const measurementFrequency = (objective.measurement_frequency
      || 'monthly') as 'monthly' | 'quarterly' | 'semiannual' | 'annual'
    const resolvedAxes = resolveObjectiveAxes(objective)
    const realizations = resolveObjectiveRealizations(
      objective,
      measurementFrequency,
    )

    return {
      id: objective.id,
      processId: Number(process.id),
      indicatorId: Number(objective.indicator_id || 0) || undefined,
      processus: processName,
      titre: objective.title || 'Objectif',
      axeStrategique: resolvedAxes[0] || '',
      axesStrategiques: resolvedAxes,
      indicateur: resolveObjectiveIndicatorName(objective, processIndicators),
      modeCalcul: '',
      frequence: toFrequencyLabel(measurementFrequency),
      realisationMensuelle: realizations.realisationMensuelle,
      tauxAtteinte: Number(objective.achievement_percentage || 0),
      observation: '',
      actionsAMettreEnOeuvre: objective.description || '',
      targetDate: objective.target_date || '',
      measurementFrequency,
      periodRealizations: realizations.periodRealizations,
      status: objective.status || 'not_started',
      ressources: '',
      actions: mapPlannedActions(objective),
    }
  }

  async function fetchObjectivesForSite () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      objectives.value = []
      availableProcesses.value = []
      return
    }

    loading.value = true
    try {
      const processes = await fetchAllSiteProcesses(siteId)
      buildObjectiveList(processes)

      availableProcesses.value = processes.map(process => ({
        id: process.id,
        name: process.title || process.name || `Processus #${process.id}`,
        type: process.category || process.type || 'Processus',
        objectivesCount: Array.isArray(process.objectives) ? process.objectives.length : 0,
        indicators: Array.isArray(process.indicators)
          ? process.indicators.map((indicator: any) => ({
            id: Number(indicator.id),
            code: String(indicator.code || ''),
            name: String(indicator.name || ''),
          }))
          : [],
        objectives: (process.objectives || []).map((obj: any) => ({
          titre: obj.title || 'Objectif',
          indicateur: obj.indicator?.name || 'Indicateur',
        })),
      }))
    } catch (error) {
      console.error('[Objectives] Failed to load objectives', error)
      toast.error('Impossible de charger les objectifs.')
      objectives.value = []
      availableProcesses.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchAllSiteProcesses (siteId: number): Promise<any[]> {
    const perPage = 200
    const allProcesses: any[] = []
    let currentPage = 1
    let totalPages = 1

    do {
      const response = await processService.getProcesses({ site_id: siteId }, currentPage, perPage)
      const rows = Array.isArray(response?.data) ? response.data : []
      allProcesses.push(...rows)

      const meta = response?.meta || {}
      totalPages = Number(meta.last_page || totalPages || 1)
      currentPage += 1
    } while (currentPage <= totalPages)

    return allProcesses
  }

  async function fetchPolicyAxes () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      policyAxes.value = []
      return
    }

    try {
      const policy = await leadershipService.getCurrentPolicy(siteId)
      policyAxes.value = Array.isArray(policy?.axes)
        ? policy.axes
          .map((axis: any) => String(axis || '').trim())
          .filter((axis: string) => axis.length > 0)
        : []
    } catch (error) {
      console.warn('[Objectives] Failed to load policy axes', error)
      policyAxes.value = []
    }
  }

  async function fetchCollaborators () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      collaborators.value = []
      return
    }

    try {
      const response = await api.get('/users', {
        params: { site_id: siteId, per_page: 500 },
      })
      const rows = response.data?.data || []
      collaborators.value = rows.map((row: any) => ({
        id: Number(row.id),
        name:
          row.attributes?.name
          || row.name
          || row.attributes?.email
          || `Utilisateur #${row.id}`,
      }))
    } catch (error) {
      console.warn('[Objectives] Failed to load collaborators', error)
      collaborators.value = []
    }
  }

  function openObjectiveDetails (objective: any) {
    router.push({
      path: `/company/iso/planning/objectives/${objective.id}`,
      query: { process_id: String(objective.processId) },
    })
  }

  onMounted(async () => {
    await fetchPolicyAxes()
    await fetchCollaborators()
    await fetchObjectivesForSite()
  })
  watch(
    () => authStore.currentSiteId,
    async () => {
      await fetchPolicyAxes()
      await fetchCollaborators()
      await fetchObjectivesForSite()
    },
  )
  watch(
    () => formData.value.actions,
    actions => {
      for (const action of actions) {
        if (action.statut === 'À faire') {
          action.progressRate = 0
        } else if (action.statut === 'Terminé') {
          action.progressRate = 100
        } else {
          action.progressRate = Math.min(99, Math.max(1, Number(action.progressRate || 1)))
        }
      }
    },
    { deep: true },
  )
</script>

<style scoped>
.objectives-toolbar-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.objective-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.objective-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
}

.card-header {
  padding: 20px;
  border-radius: 12px 12px 0 0;
}

.mini-chart {
  display: flex;
  align-items: flex-end;
  height: 60px;
  gap: 2px;
}

.mini-bar {
  flex: 1;
  min-height: 4px;
  border-radius: 2px;
  transition: all 0.2s ease;
}

.mini-bar:hover {
  opacity: 0.8;
  transform: scaleY(1.1);
}

.list-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.list-row:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.actions-cell {
  white-space: nowrap;
}

.process-list {
  max-height: 400px;
  overflow-y: auto;
}

.process-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.process-card:hover {
  border-color: #5b8dd9;
  transform: translateX(4px);
}

.process-card.selected {
  border-color: #5b8dd9;
  background-color: rgba(91, 141, 217, 0.05);
}

/* Dialog scroll improvements */
.dialog-card {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stepper-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.sticky-stepper {
  position: sticky;
  top: 0;
  z-index: 9;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.stepper-window-scrollable {
  overflow-y: auto;
  max-height: calc(90vh - 280px);
}

.stepper-window-scrollable::-webkit-scrollbar {
  width: 8px;
}

.stepper-window-scrollable::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.stepper-window-scrollable::-webkit-scrollbar-thumb {
  background: #5b8dd9;
  border-radius: 4px;
}

.stepper-window-scrollable::-webkit-scrollbar-thumb:hover {
  background: #4a7bc8;
}
</style>
