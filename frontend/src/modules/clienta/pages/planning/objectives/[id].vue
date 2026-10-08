<template>
  <ClientALayout current-page="details_objectives">
    <v-breadcrumbs class="px-0 pb-4" :items="breadcrumbs">
      <template #divider>
        <v-icon>mdi-chevron-right</v-icon>
      </template>
    </v-breadcrumbs>

    <PageHeader
      icon="mdi-target"
      :subtitle="
        objective.processName
          ? `Processus: ${objective.processName}`
          : 'Détail objectif'
      "
      :title="objective.title || 'Objectif'"
    >
      <template #actions>
        <v-btn
          prepend-icon="mdi-arrow-left"
          variant="outlined"
          @click="goBackToObjectives"
        >Retour</v-btn>
        <v-btn
          color="primary"
          :loading="saving"
          prepend-icon="mdi-content-save"
          @click="saveChanges"
        >
          Enregistrer
        </v-btn>
      </template>
    </PageHeader>

    <v-progress-linear
      v-if="loading"
      class="mb-6"
      color="primary"
      indeterminate
    />
    <v-alert v-if="error" class="mb-6" type="error" variant="tonal">{{
      error
    }}</v-alert>

    <v-row v-if="!loading && !error">
      <v-col cols="12" md="8">
        <v-card class="mb-4" elevation="2" rounded="lg">
          <div
            class="header-gradient"
            :style="{ background: getAxeGradient(primaryStrategicAxis) }"
          >
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center" style="gap: 16px">
                <v-avatar color="white" size="56">
                  <v-icon color="primary" size="30">mdi-target</v-icon>
                </v-avatar>
                <div>
                  <div
                    class="d-flex align-center mb-2"
                    style="gap: 6px; flex-wrap: wrap"
                  >
                    <v-chip color="white" size="x-small" variant="elevated">{{
                      objective.processName || "-"
                    }}</v-chip>
                    <v-chip
                      v-for="axis in displayAxes"
                      :key="`axis-chip-${axis}`"
                      :color="getAxeColor(axis)"
                      size="x-small"
                      variant="elevated"
                    >
                      {{ axisDisplayLabel(axis) }}
                    </v-chip>
                    <v-chip color="white" size="x-small" variant="outlined">{{
                      frequencyLabel(form.measurement_frequency)
                    }}</v-chip>
                  </div>
                  <h1 class="text-h6 text-white font-weight-bold mb-1">
                    {{ form.title }}
                  </h1>
                  <p class="text-body-2 text-white opacity-90 mb-0">
                    {{ objective.indicatorName || "Indicateur" }}
                  </p>
                </div>
              </div>
              <v-chip
                :color="getPerformanceColor(achievementRate)"
                size="large"
                variant="flat"
              >
                {{ achievementRate }}%
              </v-chip>
            </div>
          </div>
        </v-card>

        <v-row class="mb-1">
          <v-col cols="12" md="4">
            <v-card
              class="summary-card"
              elevation="1"
              rounded="lg"
              variant="outlined"
            >
              <v-card-text class="py-3">
                <div class="text-caption text-medium-emphasis mb-1">
                  Fréquence
                </div>
                <div class="text-body-1 font-weight-bold">
                  {{ frequencyLabel(form.measurement_frequency) }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card
              class="summary-card"
              elevation="1"
              rounded="lg"
              variant="outlined"
            >
              <v-card-text class="py-3">
                <div class="text-caption text-medium-emphasis mb-1">
                  Échéance
                </div>
                <div class="text-body-1 font-weight-bold">
                  {{ form.target_date || "Non définie" }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card
              class="summary-card"
              elevation="1"
              rounded="lg"
              variant="outlined"
            >
              <v-card-text class="py-3">
                <div class="text-caption text-medium-emphasis mb-1">
                  Valeur cible
                </div>
                <div class="text-body-1 font-weight-bold">
                  {{ form.target_value ?? "-" }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="mb-4" elevation="2" rounded="lg">
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="d-flex align-center" style="gap: 10px">
              <v-icon color="primary">mdi-chart-bar</v-icon>
              <span>Performance par période</span>
            </div>
            <div class="d-flex align-center ga-2">
              <span class="text-caption font-weight-medium text-grey">Taux actuel (§8) :</span>
              <v-chip
                :color="getPerformanceColor(currentAchievementRate || 0)"
                size="small"
                variant="flat"
              >
                {{ currentAchievementRate !== null ? `${currentAchievementRate}%` : '—' }}
              </v-chip>
            </div>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <div class="progress-bars-container mb-4">
              <div
                v-for="(value, index) in form.period_realizations"
                :key="`bar-${index}`"
                class="progress-item"
              >
                <div
                  class="progress-value"
                  :style="{ color: getPerformanceColor(Number(value || 0)) }"
                >
                  <span v-if="isRateNA(value as any)">N/A</span>
                  <span v-else>{{ Number(value || 0) }}%</span>
                </div>
                <div class="progress-bar-vertical">
                  <div
                    class="progress-fill"
                    :style="{
                      height: `${isRateNA(value as any) ? 0 : Math.min(100, Math.max(0, Number(value || 0)))}%`,
                      background: isRateNA(value as any) ? '#9e9e9e' : getBarGradient(Number(value || 0)),
                    }"
                  />
                </div>
                <div class="progress-label">{{ periodLabels[index] }}</div>
              </div>
            </div>

            <div class="d-flex align-center justify-space-between mb-3 px-1">
              <span class="text-caption text-grey">
                Mois courants et antérieurs pris en compte (≤ M{{ currentMonthIndex + 1 }}).
                Mois futurs et N/A ignorés du calcul (§8).
              </span>
              <v-btn
                density="compact"
                :prepend-icon="allowPastEdit ? 'mdi-lock-open-variant' : 'mdi-lock'"
                size="x-small"
                variant="tonal"
                @click="allowPastEdit = !allowPastEdit"
              >
                {{ allowPastEdit ? 'Verrouiller mois passés' : 'Déverrouiller pour revue (§6.2-06)' }}
              </v-btn>
            </div>

            <v-row>
              <v-col
                v-for="(label, index) in periodInputLabels"
                :key="`${label}-${index}`"
                cols="6"
                lg="3"
                md="4"
                sm="6"
              >
                <v-text-field
                  v-model="form.period_realizations[index]"
                  :disabled="isPeriodLocked(index)"
                  :label="label"
                  max="100"
                  min="0"
                  :prepend-inner-icon="isPeriodLocked(index) ? 'mdi-lock-outline' : undefined"
                  suffix="%"
                  type="text"
                  variant="outlined"
                  @change="clampPeriodRealization(index)"
                >
                  <template #append-inner>
                    <v-btn
                      :disabled="isPeriodLocked(index)"
                      density="compact"
                      size="x-small"
                      :color="(form.period_realizations[index] as any) === 'NA' ? 'grey-darken-1' : 'grey'"
                      variant="tonal"
                      @click.stop="togglePeriodNA(index)"
                    >
                      N/A
                    </v-btn>
                  </template>
                </v-text-field>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card class="mb-4" elevation="2" rounded="lg">
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Informations objectif</span>
            <div class="d-flex align-center ga-2">
              <v-chip
                color="primary"
                size="x-small"
                variant="tonal"
              >Champs principaux</v-chip>
              <v-btn
                color="primary"
                prepend-icon="mdi-tune-variant"
                size="small"
                variant="tonal"
                @click="showAdvancedDialog = true"
              >
                Infos avancées
              </v-btn>
            </div>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="form.title"
                  label="Titre"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.strategic_axes"
                  chips
                  closable-chips
                  item-title="title"
                  item-value="value"
                  :items="strategicAxisOptions"
                  label="Axes stratégiques"
                  multiple
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.measurement_frequency"
                  item-title="title"
                  item-value="value"
                  :items="frequencyOptions"
                  label="Fréquence"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <AppDatePickerField v-model="form.target_date" label="Échéance" mode="date" />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model.number="form.target_value"
                  label="Valeur cible"
                  min="0"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.status"
                  item-title="title"
                  item-value="value"
                  :items="statusOptions"
                  label="Statut"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-alert
                  border="start"
                  color="primary"
                  density="comfortable"
                  variant="tonal"
                >
                  Les champs détaillés sont dans
                  <strong>Infos avancées</strong>.
                </v-alert>
              </v-col>
              <v-col class="d-flex align-center justify-end" cols="12" md="6">
                <v-chip color="primary" variant="outlined">
                  {{ advancedFilledCount }} champ(s) avancé(s) renseigné(s)
                </v-chip>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <ObjectiveSidebar
        :achievement-rate="achievementRate"
        :form="form"
        :frequency-label="frequencyLabel"
        :get-performance-color="getPerformanceColor"
        :max-rate="maxRate"
        :min-rate="minRate"
        :objective="objective"
        :saving="saving"
        @advanced="showAdvancedDialog = true"
        @save="saveChanges"
      />
    </v-row>

    <v-dialog
      v-model="showAdvancedDialog"
      max-width="980"
      persistent
      scrollable
    >
      <v-card class="dialog-card" rounded="lg">
        <v-card-title
          class="d-flex align-center justify-space-between pa-6 bg-primary"
        >
          <div class="d-flex align-center" style="gap: 12px">
            <v-avatar color="white" size="40">
              <v-icon color="primary">mdi-tune-variant</v-icon>
            </v-avatar>
            <span class="text-h5 text-white">Informations avancées</span>
          </div>
          <v-btn
            color="white"
            icon="mdi-close"
            variant="text"
            @click="showAdvancedDialog = false"
          />
        </v-card-title>

        <v-card-text class="pa-8 advanced-dialog-body">
          <div class="advanced-section">
            <div class="d-flex align-center mb-4" style="gap: 10px">
              <v-avatar color="primary" size="42">
                <v-icon
                  color="white"
                  size="24"
                >mdi-file-document-edit-outline</v-icon>
              </v-avatar>
              <div>
                <h3 class="text-h6 font-weight-bold mb-0">
                  Description et calcul
                </h3>
                <p class="text-caption text-medium-emphasis mb-0">
                  Précisez le contexte et le mode de calcul
                </p>
              </div>
            </div>
            <v-divider class="mb-6" />
            <v-row>
              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  label="Description"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.calculation_mode"
                  label="Mode de calcul"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.action_plan"
                  label="Actions à mettre en oeuvre"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <div class="advanced-section">
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="d-flex align-center" style="gap: 10px">
                <v-avatar color="primary" size="42">
                  <v-icon color="white" size="24">mdi-playlist-check</v-icon>
                </v-avatar>
                <div>
                  <h3 class="text-h6 font-weight-bold mb-0">
                    Actions détaillées
                  </h3>
                  <p class="text-caption text-medium-emphasis mb-0">
                    Définissez les actions et responsables
                  </p>
                </div>
              </div>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                size="small"
                variant="elevated"
                @click="addPlannedAction"
              >
                Ajouter
              </v-btn>
            </div>
            <v-divider class="mb-6" />

            <v-row>
              <v-col cols="12">
                <v-card
                  v-for="(action, index) in form.planned_actions"
                  :key="`planned-action-${index}`"
                  class="mb-2"
                  variant="outlined"
                >
                  <v-card-text class="pa-3">
                    <v-row>
                      <v-col cols="12" md="5">
                        <v-text-field
                          v-model="action.title"
                          label="Action"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="3">
                        <v-select
                          v-model="action.responsible_user_id"
                          item-title="title"
                          item-value="value"
                          :items="userOptions"
                          label="Responsable"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="2">
                        <AppDatePickerField v-model="action.due_date" label="Échéance" mode="date" />
                      </v-col>
                      <v-col cols="12" md="2">
                        <v-text-field
                          v-model.number="action.progress_rate"
                          :disabled="action.status === 'a_faire' || action.status === 'terminee'"
                          label="Taux (%)"
                          max="99"
                          min="0"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="2">
                        <v-select
                          v-model="action.status"
                          item-title="title"
                          item-value="value"
                          :items="plannedActionStatusOptions"
                          label="Statut"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col class="d-flex align-center justify-end" cols="12" md="12">
                        <v-btn
                          color="primary"
                          icon="mdi-playlist-check"
                          size="small"
                          variant="text"
                          title="Suivi"
                          @click.prevent="openTrackingForPlannedAction(action, index)"
                        />
                        <v-btn
                          color="error"
                          icon="mdi-delete"
                          size="small"
                          variant="text"
                          @click="removePlannedAction(index)"
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
                <v-alert
                  v-if="form.planned_actions.length === 0"
                  density="comfortable"
                  type="info"
                  variant="tonal"
                >
                  Aucune action détaillée pour cet objectif.
                </v-alert>
              </v-col>
            </v-row>
          </div>

          <div class="advanced-section">
            <div class="d-flex align-center mb-4" style="gap: 10px">
              <v-avatar color="primary" size="42">
                <v-icon color="white" size="24">mdi-note-edit-outline</v-icon>
              </v-avatar>
              <div>
                <h3 class="text-h6 font-weight-bold mb-0">Compléments</h3>
                <p class="text-caption text-medium-emphasis mb-0">
                  Ressources particulières et observations
                </p>
              </div>
            </div>
            <v-divider class="mb-6" />
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.special_resources"
                  label="Ressources particulières"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.notes"
                  label="Observation / Commentaires"
                  placeholder="Observation sur l'atteinte de l'objectif"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>
        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-6 bg-grey-lighten-5">
          <v-spacer />
          <v-btn
            variant="text"
            @click="showAdvancedDialog = false"
          >Fermer</v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            prepend-icon="mdi-content-save"
            variant="elevated"
            @click="saveChanges"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ObjectiveSidebar from '@/modules/clienta/pages/iso/planning/objectives/components/ObjectiveSidebar.vue'
  import { calculateCurrentObjectiveRate, isRateNA } from '@/modules/clienta/utils/objectiveCalculations'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { leadershipService } from '@/services/leadershipService'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  type Frequency = 'monthly' | 'quarterly' | 'semiannual' | 'annual'

  interface ObjectiveDetail {
    id: number
    processId: number
    processName: string
    axeStrategique: string
    title: string
    description: string
    targetDate: string
    targetValue: number | undefined
    measurementFrequency: Frequency
    indicatorCode: string
    indicatorName: string
    indicatorUnit: string
    indicatorFormula: string
    status: 'not_started' | 'in_progress' | 'achieved' | 'failed'
    achievementPercentage: number
    periodRealizations: number[]
    notes: string
    strategicAxes: string[]
    strategicAxis: string
    calculationMode: string
    actionPlan: string
    plannedActions: Array<{
      title: string
      responsible_user_id: number | undefined
      responsible: string
      involved_user_ids: number[]
      involved_users: string[]
      due_date: string
      progress_rate: number
      tracking_available?: boolean
      linked_action_id?: number | null
      status: 'a_faire' | 'en_cours' | 'terminee'
    }>
    specialResources: string
  }

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const showAdvancedDialog = ref(false)
  const users = ref<Array<{ id: number, name: string }>>([])
  const policyAxes = ref<string[]>([])

  const objective = ref<ObjectiveDetail>({
    id: 0,
    processId: 0,
    processName: '',
    axeStrategique: '',
    title: '',
    description: '',
    targetDate: '',
    targetValue: undefined,
    measurementFrequency: 'monthly',
    indicatorCode: '',
    indicatorName: '',
    indicatorUnit: '',
    indicatorFormula: '',
    status: 'not_started',
    achievementPercentage: 0,
    periodRealizations: [],
    notes: '',
    strategicAxes: [],
    strategicAxis: '',
    calculationMode: '',
    actionPlan: '',
    plannedActions: [],
    specialResources: '',
  })

  const form = ref({
    title: '',
    description: '',
    strategic_axis: '',
    strategic_axes: [] as string[],
    calculation_mode: '',
    action_plan: '',
    target_date: '',
    target_value: undefined as number | undefined,
    measurement_frequency: 'monthly' as Frequency,
    status: 'not_started' as
      | 'not_started'
      | 'in_progress'
      | 'achieved'
      | 'failed',
    period_realizations: [] as number[],
    notes: '',
    indicator_name: '',
    planned_actions: [] as Array<{
      title: string
      responsible_user_id: number | undefined
      responsible: string
      involved_user_ids: number[]
      involved_users: string[]
      due_date: string
      progress_rate: number
      tracking_available?: boolean
      linked_action_id?: number | null
      status: 'a_faire' | 'en_cours' | 'terminee'
    }>,
    special_resources: '',
  })

  const frequencyOptions = [
    { title: 'Mensuel', value: 'monthly' },
    { title: 'Trimestriel', value: 'quarterly' },
    { title: 'Semestriel', value: 'semiannual' },
    { title: 'Annuel', value: 'annual' },
  ]

  const statusOptions = [
    { title: 'Non démarré', value: 'not_started' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Atteint', value: 'achieved' },
    { title: 'Non atteint', value: 'failed' },
  ]
  const strategicAxisSource = computed(() => {
    if (policyAxes.value.length > 0) {
      return policyAxes.value
    }
    const fallback = Array.from(
      new Set(
        [
          ...objective.value.strategicAxes,
          ...form.value.strategic_axes,
          form.value.strategic_axis,
        ]
          .map(value => String(value || '').trim())
          .filter(value => value.length > 0),
      ),
    )
    return fallback.length > 0 ? fallback : ['Axe 1', 'Axe 2', 'Axe 3']
  })
  const strategicAxisOptions = computed(() =>
    strategicAxisSource.value.map((axis, index) => ({
      title: `Axe ${index + 1}`,
      value: axis,
    })),
  )
  const plannedActionStatusOptions = [
    { title: 'À faire', value: 'a_faire' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Terminé', value: 'terminee' },
  ]
  const userOptions = computed(() =>
    users.value.map(user => ({ title: user.name, value: user.id })),
  )
  const displayAxes = computed(() => {
    const raw
      = form.value.strategic_axes.length > 0
        ? form.value.strategic_axes
        : [form.value.strategic_axis]
    return Array.from(
      new Set(
        raw
          .map(value => String(value || '').trim())
          .filter(value => value.length > 0),
      ),
    )
  })
  const primaryStrategicAxis = computed(() => displayAxes.value[0] || '')

  const breadcrumbs = computed(() => [
    { title: 'Tableau de bord', to: '/company/iso/planning/objectives' },
    { title: form.value.title || 'Détail objectif', disabled: true },
  ])

  const currentMonthIndex = computed(() => new Date().getMonth())
  const allowPastEdit = ref(false)

  const currentAchievementRate = computed(() => {
    return calculateCurrentObjectiveRate(form.value.period_realizations, currentMonthIndex.value)
  })

  const achievementRate = computed(() => {
    return currentAchievementRate.value ?? 0
  })

  function isPeriodLocked (index: number): boolean {
    if (allowPastEdit.value) return false
    if (form.value.measurement_frequency !== 'monthly') return false
    return index < currentMonthIndex.value
  }

  function clampPeriodRealization (index: number) {
    const val = Number(form.value.period_realizations[index])
    if (val > 100) {
      form.value.period_realizations[index] = 100
      toast.warning('Le taux de réalisation est plafonné à 100 % (REQ-6.2-03).')
    } else if (val < 0) {
      form.value.period_realizations[index] = 0
    }
  }

  function togglePeriodNA (index: number) {
    if ((form.value.period_realizations[index] as any) === 'NA') {
      form.value.period_realizations[index] = 0
    } else {
      ;(form.value.period_realizations as any)[index] = 'NA'
    }
  }

  const minRate = computed(() => {
    if (form.value.period_realizations.length === 0) return 0
    return Math.min(
      ...form.value.period_realizations.map(value => Number(value || 0)),
    )
  })

  const maxRate = computed(() => {
    if (form.value.period_realizations.length === 0) return 0
    return Math.max(
      ...form.value.period_realizations.map(value => Number(value || 0)),
    )
  })

  const periodLabels = computed(() => {
    const prefix = periodPrefix(form.value.measurement_frequency)

    return form.value.period_realizations.map(
      (_, index) => `${prefix}${index + 1}`,
    )
  })

  const periodInputLabels = computed(() => {
    if (form.value.measurement_frequency === 'monthly') {
      return [
        'Janvier',
        'Février',
        'Mars',
        'Avril',
        'Mai',
        'Juin',
        'Juillet',
        'Août',
        'Septembre',
        'Octobre',
        'Novembre',
        'Décembre',
      ]
    }

    if (form.value.measurement_frequency === 'quarterly') {
      return ['Trimestre 1', 'Trimestre 2', 'Trimestre 3', 'Trimestre 4']
    }

    if (form.value.measurement_frequency === 'semiannual') {
      return ['Semestre 1', 'Semestre 2']
    }

    return ['Annuel']
  })

  const advancedFilledCount = computed(() => {
    const values = [
      form.value.calculation_mode,
      form.value.indicator_name,
      form.value.special_resources,
      form.value.notes,
    ]
    const baseCount = values.filter(
      value => String(value || '').trim().length > 0,
    ).length
    return (
      baseCount
      + form.value.planned_actions.filter(
        action => String(action.title || '').trim().length > 0,
      ).length
    )
  })

  function axisDisplayLabel (axis: string) {
    const index = strategicAxisSource.value.indexOf(axis)
    if (index !== -1) {
      return `Axe ${index + 1}`
    }
    return axis || 'Axe'
  }

  function addPlannedAction () {
    form.value.planned_actions.push({
      title: '',
      responsible_user_id: undefined,
      responsible: '',
      involved_user_ids: [],
      involved_users: [],
      due_date: '',
      progress_rate: 0,
      tracking_available: false,
      linked_action_id: null,
      status: 'a_faire',
    })
  }

  function goBackToObjectives () {
    router.push('/company/iso/planning/objectives')
  }

  function removePlannedAction (index: number) {
    form.value.planned_actions.splice(index, 1)
  }

  function mapAxeFromCategory (category?: string) {
    const normalized = (category || '').toLowerCase()
    if (policyAxes.value.length > 0) {
      if (normalized.includes('pilotage') || normalized.includes('management'))
        return policyAxes.value[0] || ''
      if (
        normalized.includes('operationnel')
        || normalized.includes('realisation')
        || normalized.includes('realization')
      )
        return policyAxes.value[1] || policyAxes.value[0] || ''
      if (normalized.includes('support'))
        return policyAxes.value[2] || policyAxes.value[0] || ''
      return policyAxes.value[0] || ''
    }
    if (normalized.includes('pilotage') || normalized.includes('management'))
      return 'Axe 1'
    if (
      normalized.includes('operationnel')
      || normalized.includes('realisation')
      || normalized.includes('realization')
    )
      return 'Axe 2'
    if (normalized.includes('support')) return 'Axe 3'
    return 'Axe 1'
  }

  function frequencyLabel (value: Frequency) {
    if (value === 'quarterly') return 'Trimestriel'
    if (value === 'semiannual') return 'Semestriel'
    if (value === 'annual') return 'Annuel'
    return 'Mensuel'
  }

  function periodPrefix (value: Frequency) {
    if (value === 'quarterly') return 'T'
    if (value === 'semiannual') return 'S'
    if (value === 'annual') return 'A'
    return 'M'
  }

  function computeStatus (
    requested: 'not_started' | 'in_progress' | 'achieved' | 'failed',
    rate: number,
  ) {
    if (requested === 'failed') return 'failed'
    if (rate >= 100) return 'achieved'
    if (rate > 0) return 'in_progress'
    return 'not_started'
  }

  function periodsCountByFrequency (frequency: Frequency) {
    const counts: Record<Frequency, number> = {
      monthly: 12,
      quarterly: 4,
      semiannual: 2,
      annual: 1,
    }
    return counts[frequency]
  }

  function normalizePeriodRealizations (input: number[], frequency: Frequency) {
    const count = periodsCountByFrequency(frequency)
    const current = Array.isArray(input) ? input : []
    return Array.from({ length: count }, (_, index) =>
      Number(current[index] || 0),
    )
  }

  function resizePeriodRealizations () {
    const count = periodsCountByFrequency(form.value.measurement_frequency)
    const current = [...form.value.period_realizations]
    form.value.period_realizations = Array.from({ length: count }, (_, index) =>
      Number(current[index] || 0),
    )
  }

  function getPerformanceColor (value: number) {
    if (value >= 80) return '#22c55e'
    if (value >= 50) return '#f59e0b'
    return '#ef4444'
  }

  function getBarGradient (value: number) {
    if (value >= 80) return 'linear-gradient(180deg, #22c55e 0%, #16a34a 100%)'
    if (value >= 50) return 'linear-gradient(180deg, #f59e0b 0%, #d97706 100%)'
    return 'linear-gradient(180deg, #ef4444 0%, #dc2626 100%)'
  }

  function getAxeGradient (axe: string) {
    const gradients = [
      'linear-gradient(135deg, #1f4c8f 0%, #2e6ac1 100%)',
      'linear-gradient(135deg, #12563b 0%, #1d8e63 100%)',
      'linear-gradient(135deg, #0e7490 0%, #0891b2 100%)',
      'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
      'linear-gradient(135deg, #be123c 0%, #e11d48 100%)',
    ]
    const index = strategicAxisSource.value.indexOf(axe)
    const safeIndex = Math.max(index, 0)
    return gradients[safeIndex % gradients.length]
  }

  function getAxeColor (axe: string) {
    const colors = ['indigo', 'success', 'cyan-darken-1', 'deep-purple', 'pink']
    const index = strategicAxisSource.value.indexOf(axe)
    const safeIndex = Math.max(index, 0)
    return colors[safeIndex % colors.length]
  }

  function getCurrentSiteId () {
    const stored = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
  }

  function resolveResponsibleName (action: any) {
    const responsibleId = Number(action?.responsible_user_id || 0)
    if (responsibleId > 0) {
      const user = users.value.find(row => row.id === responsibleId)
      if (user?.name) return user.name
    }
    return String(action?.responsible || action?.responsible_name || '')
  }

  function normalizePlannedActionStatus (
    status: unknown,
  ): 'a_faire' | 'en_cours' | 'terminee' {
    const candidate = String(status || '')
    if (
      candidate === 'a_faire'
      || candidate === 'en_cours'
      || candidate === 'terminee'
    ) {
      return candidate
    }
    return 'a_faire'
  }

  function mapPlannedActionsForDetail (actions: any[]) {
    return actions.map((action: any) => ({
      title: String(action?.title || ''),
      responsible_user_id: action?.responsible_user_id
        ? Number(action.responsible_user_id)
        : undefined,
      responsible: resolveResponsibleName(action),
      involved_user_ids: Array.isArray(action?.involved_user_ids)
        ? action.involved_user_ids.map(Number).filter((value: number) => Number.isFinite(value))
        : [],
      involved_users: Array.isArray(action?.involved_users)
        ? action.involved_users.map((value: any) => String(value || '').trim()).filter((value: string) => value.length > 0)
        : [],
      due_date: action?.due_date ? String(action.due_date).slice(0, 10) : '',
      progress_rate: Number(action?.progress_rate || 0),
      tracking_available: Boolean(action?.tracking_available),
      linked_action_id: action?.linked_action_id ? Number(action.linked_action_id) : null,
      status: normalizePlannedActionStatus(action?.status),
    }))
  }

  function pickText (...values: unknown[]): string {
    for (const value of values) {
      const text = String(value || '').trim()
      if (text) return text
    }
    return ''
  }

  function normalizeObjectiveStatus (
    status: unknown,
  ): 'not_started' | 'in_progress' | 'achieved' | 'failed' {
    const candidate = String(status || '')
    if (
      candidate === 'not_started'
      || candidate === 'in_progress'
      || candidate === 'achieved'
      || candidate === 'failed'
    ) {
      return candidate
    }
    return 'not_started'
  }

  function mapObjectiveIndicatorData (process: any, rawObjective: any) {
    const indicator = (process.indicators || []).find(
      (entry: any) => Number(entry.id) === Number(rawObjective.indicator_id),
    )

    return {
      indicatorCode: pickText(indicator?.code),
      indicatorName: pickText(indicator?.name, indicator?.code, 'Indicateur'),
      indicatorUnit: pickText(indicator?.unit),
      indicatorFormula: pickText(indicator?.formula),
      calculationMode: pickText(
        rawObjective.calculation_mode,
        indicator?.formula,
      ),
    }
  }

  function mapObjectiveDetailFromProcess (
    process: any,
    rawObjective: any,
  ): ObjectiveDetail {
    const frequency = (rawObjective.measurement_frequency
      || 'monthly') as Frequency
    const rawPeriodRealizations = Array.isArray(rawObjective.period_realizations)
      ? rawObjective.period_realizations
      : (Array.isArray(rawObjective.realisationMensuelle)
        ? rawObjective.realisationMensuelle
        : [])
    const periodRealizations = Array.isArray(rawPeriodRealizations)
      ? rawPeriodRealizations.map((value: any) => Number(value || 0))
      : []
    const plannedActions = Array.isArray(rawObjective.planned_actions)
      ? rawObjective.planned_actions
      : []
    const indicatorData = mapObjectiveIndicatorData(process, rawObjective)
    const processCategory = pickText(process.category, process.type)
    const fallbackAxis = mapAxeFromCategory(processCategory)
    const candidateAxes = Array.isArray(rawObjective.strategic_axes)
      ? rawObjective.strategic_axes
      : [rawObjective.strategic_axis]
    const strategicAxes = Array.from(
      new Set(
        candidateAxes
          .map((axis: any) => String(axis || '').trim())
          .filter((axis: string) => axis.length > 0),
      ),
    )
    if (strategicAxes.length === 0 && fallbackAxis) {
      strategicAxes.push(fallbackAxis)
    }

    return {
      id: Number(rawObjective.id),
      processId: Number(process.id),
      processName: pickText(
        process.title,
        process.name,
        `Processus #${process.id}`,
      ),
      axeStrategique: strategicAxes[0] || fallbackAxis,
      title: pickText(rawObjective.title),
      description: pickText(rawObjective.description),
      strategicAxes,
      strategicAxis: strategicAxes[0] || fallbackAxis,
      calculationMode: indicatorData.calculationMode,
      targetDate: rawObjective.target_date
        ? String(rawObjective.target_date).slice(0, 10)
        : '',
      targetValue:
        rawObjective.target_value == undefined
          ? undefined
          : Number(rawObjective.target_value),
      measurementFrequency: frequency,
      indicatorCode: indicatorData.indicatorCode,
      indicatorName: indicatorData.indicatorName,
      indicatorUnit: indicatorData.indicatorUnit,
      indicatorFormula: indicatorData.indicatorFormula,
      status: normalizeObjectiveStatus(rawObjective.status),
      achievementPercentage: Number(rawObjective.achievement_percentage || 0),
      periodRealizations,
      notes: pickText(rawObjective.notes),
      actionPlan: pickText(rawObjective.action_plan),
      plannedActions: mapPlannedActionsForDetail(plannedActions),
      specialResources: pickText(rawObjective.special_resources),
    } as ObjectiveDetail
  }

  function buildSavePayload (
    computedStatus: 'not_started' | 'in_progress' | 'achieved' | 'failed',
  ) {
    const plannedActions = form.value.planned_actions
      .filter(action => String(action.title || '').trim().length > 0)
      .map(action => ({
        title: action.title.trim(),
        responsible_user_id: action.responsible_user_id || undefined,
        responsible:
          users.value.find(user => user.id === action.responsible_user_id)
            ?.name
          || action.responsible
            || undefined,
        due_date: action.due_date || undefined,
        involved_user_ids: Array.isArray(action.involved_user_ids) ? action.involved_user_ids : [],
        progress_rate: Number(action.progress_rate || 0),
        status: action.status || 'a_faire',
      }))

    return {
      title: form.value.title,
      description: form.value.description || undefined,
      strategic_axis:
        form.value.strategic_axes[0] || form.value.strategic_axis || undefined,
      strategic_axes: form.value.strategic_axes,
      calculation_mode: form.value.calculation_mode || undefined,
      target_value: form.value.target_value ?? undefined,
      target_date: form.value.target_date || undefined,
      measurement_frequency: form.value.measurement_frequency,
      period_realizations: form.value.period_realizations.map(value =>
        Number(value || 0),
      ),
      notes: form.value.notes || undefined,
      action_plan: form.value.action_plan || undefined,
      planned_actions: plannedActions,
      special_resources: form.value.special_resources || undefined,
      achievement_percentage: achievementRate.value,
      status: computedStatus,
    }
  }

  function mergeObjectiveAfterSave (
    updated: any,
    computedStatus: 'not_started' | 'in_progress' | 'achieved' | 'failed',
  ) {
    objective.value = {
      ...objective.value,
      title: updated.title || form.value.title,
      description: updated.description || form.value.description,
      strategicAxes: Array.isArray(updated.strategic_axes)
        ? updated.strategic_axes
          .map((axis: any) => String(axis || '').trim())
          .filter((axis: string) => axis.length > 0)
        : [...form.value.strategic_axes],
      strategicAxis:
        updated.strategic_axis
        || form.value.strategic_axes[0]
        || form.value.strategic_axis,
      calculationMode: updated.calculation_mode || form.value.calculation_mode,
      targetDate: updated.target_date
        ? String(updated.target_date).slice(0, 10)
        : form.value.target_date,
      targetValue:
        updated.target_value == undefined
          ? (form.value.target_value ?? undefined)
          : Number(updated.target_value),
      measurementFrequency: (updated.measurement_frequency
        || form.value.measurement_frequency) as Frequency,
      status: (updated.status || computedStatus) as
        | 'not_started'
        | 'in_progress'
        | 'achieved'
        | 'failed',
      achievementPercentage: Number(
        updated.achievement_percentage || achievementRate.value,
      ),
      periodRealizations: Array.isArray(updated.period_realizations)
        ? updated.period_realizations.map((value: any) => Number(value || 0))
        : [...form.value.period_realizations],
      notes: updated.notes || form.value.notes,
      actionPlan: updated.action_plan || form.value.action_plan,
      plannedActions: Array.isArray(updated.planned_actions)
        ? mapPlannedActionsForDetail(updated.planned_actions)
        : form.value.planned_actions,
      specialResources: updated.special_resources || form.value.special_resources,
    }
  }

  async function fetchUsers () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      users.value = []
      return
    }
    const response = await api.get('/users', {
      params: { site_id: siteId, per_page: 300 },
    })
    const rows = response.data?.data || []
    users.value = rows.map((row: any) => ({
      id: Number(row.id),
      name:
        row.attributes?.name
        || row.name
        || row.attributes?.email
        || `Utilisateur #${row.id}`,
    }))
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
    } catch (error_) {
      console.warn('[ObjectiveDetail] Failed to load policy axes', error_)
      policyAxes.value = []
    }
  }

  function hydrateFormFromObjective () {
    form.value = {
      title: objective.value.title,
      description: objective.value.description,
      strategic_axis: objective.value.strategicAxis,
      strategic_axes: [...objective.value.strategicAxes],
      calculation_mode: objective.value.calculationMode,
      target_date: objective.value.targetDate,
      target_value: objective.value.targetValue ?? undefined,
      measurement_frequency: objective.value.measurementFrequency,
      status: objective.value.status,
      period_realizations: [...objective.value.periodRealizations],
      notes: objective.value.notes,
      action_plan: objective.value.actionPlan,
      indicator_name: objective.value.indicatorName,
      planned_actions: objective.value.plannedActions.map(action => ({
        title: action.title || '',
        responsible_user_id: action.responsible_user_id || undefined,
        responsible: action.responsible || '',
        involved_user_ids: Array.isArray(action.involved_user_ids)
          ? [...action.involved_user_ids]
          : [],
        involved_users: Array.isArray(action.involved_users)
          ? [...action.involved_users]
          : [],
        due_date: action.due_date || '',
        progress_rate: Number(action.progress_rate || 0),
        tracking_available: Boolean(action.tracking_available),
        linked_action_id: action.linked_action_id ? Number(action.linked_action_id) : null,
        status: (action.status || 'a_faire') as
          | 'a_faire'
          | 'en_cours'
          | 'terminee',
      })),
      special_resources: objective.value.specialResources,
    }

    form.value.period_realizations = normalizePeriodRealizations(
      form.value.period_realizations,
      form.value.measurement_frequency,
    )
    if (!Array.isArray(form.value.strategic_axes)) {
      form.value.strategic_axes = []
    }
    form.value.strategic_axis
      = form.value.strategic_axes[0] || form.value.strategic_axis
  }

  async function findObjectiveInProcess (processId: number, objectiveId: number) {
    const processResponse = await processService.getProcess(processId)
    const process = processResponse?.data
    if (!process) return null

    const processObjectives = Array.isArray(process.objectives)
      ? process.objectives
      : []
    const rawObjective = processObjectives.find(
      (item: any) => Number(item.id) === objectiveId,
    )
    if (!rawObjective) return null

    return mapObjectiveDetailFromProcess(process, rawObjective)
  }

  async function fetchObjectiveDetail () {
    const objectiveId = Number((route.params as { id?: string }).id)
    if (!Number.isFinite(objectiveId)) {
      error.value = 'Identifiant objectif invalide.'
      return
    }

    loading.value = true
    error.value = ''

    try {
      const queryProcessId = Number(route.query.process_id)
      if (Number.isFinite(queryProcessId) && queryProcessId > 0) {
        const foundInQueryProcess = await findObjectiveInProcess(
          queryProcessId,
          objectiveId,
        )
        if (foundInQueryProcess) {
          objective.value = foundInQueryProcess
          hydrateFormFromObjective()
          return
        }
      }

      const currentSiteId
        = authStore.currentSiteId
          || Number(localStorage.getItem('current_site_id'))
      if (!currentSiteId) {
        error.value = 'Aucun site sélectionné.'
        return
      }

      const processesResponse = await processService.getProcesses(
        { site_id: currentSiteId },
        1,
        200,
      )
      const processes = processesResponse.data || []

      for (const process of processes) {
        const found = await findObjectiveInProcess(
          Number(process.id),
          objectiveId,
        )
        if (found) {
          objective.value = found
          hydrateFormFromObjective()
          return
        }
      }

      error.value = 'Objectif introuvable pour le site sélectionné.'
    } catch (error_) {
      console.error('[ObjectiveDetail] fetch failed', error_)
      error.value = 'Impossible de charger les détails de cet objectif.'
    } finally {
      loading.value = false
    }
  }

  async function saveChanges () {
    if (!objective.value.processId || !objective.value.id) return

    if (!form.value.title.trim()) {
      toast.error('Le titre est obligatoire.')
      return
    }
    if (form.value.strategic_axes.length === 0) {
      toast.error('Sélectionnez au moins un axe stratégique.')
      return
    }

    saving.value = true
    try {
      const computedStatus = computeStatus(
        form.value.status,
        achievementRate.value,
      )
      const payload = buildSavePayload(computedStatus)
      const response = await processService.updateObjective(
        objective.value.processId,
        objective.value.id,
        payload,
      )
      const updated = response?.data || response

      mergeObjectiveAfterSave(updated, computedStatus)
      hydrateFormFromObjective()
      toast.success('Objectif enregistré avec succès.')
    } catch (error_) {
      console.error('[ObjectiveDetail] save failed', error_)
      toast.error('Erreur lors de l\'enregistrement de l\'objectif.')
    } finally {
      saving.value = false
    }
  }

  async function openTrackingForPlannedAction (plannedAction: any, _index: number) {
    if (!plannedAction?.linked_action_id) {
      toast.info('Enregistrez d\'abord l\'objectif pour générer la tâche de suivi.')
      return
    }

    if (!plannedAction?.tracking_available) {
      toast.warning('Le suivi n\'est pas disponible pour cette action (hors fenêtre d\'activation).')
      return
    }

    router.push('/company/my-tasks')
  }

  watch(() => form.value.measurement_frequency, resizePeriodRealizations)
  watch(
    () => form.value.planned_actions,
    actions => {
      for (const action of actions) {
        if (action.status === 'a_faire') {
          action.progress_rate = 0
        } else if (action.status === 'terminee') {
          action.progress_rate = 100
        } else {
          action.progress_rate = Math.min(99, Math.max(1, Number(action.progress_rate || 1)))
        }
      }
    },
    { deep: true },
  )
  watch(
    () => form.value.strategic_axes,
    axes => {
      const normalized = axes
        .map(axis => String(axis || '').trim())
        .filter(axis => axis.length > 0)
      const unique = Array.from(new Set(normalized))
      if (unique.join('|') !== axes.join('|')) {
        form.value.strategic_axes = unique
      }
      form.value.strategic_axis = form.value.strategic_axes[0] || ''
    },
    { deep: true },
  )

  onMounted(async () => {
    await fetchPolicyAxes()
    await fetchUsers()
    await fetchObjectiveDetail()
  })
  watch(
    () => authStore.currentSiteId,
    async () => {
      await fetchPolicyAxes()
      await fetchUsers()
      await fetchObjectiveDetail()
    },
  )
</script>

<style scoped>
.header-gradient {
  padding: 20px;
  border-radius: 10px;
}

.summary-card {
  border-color: rgba(30, 64, 175, 0.16) !important;
  background: linear-gradient(
    180deg,
    rgba(248, 250, 252, 0.96) 0%,
    rgba(241, 245, 249, 0.78) 100%
  );
}

.advanced-dialog-body {
  max-height: 70vh;
  overflow-y: auto;
}

.advanced-section + .advanced-section {
  margin-top: 28px;
}

.progress-bars-container {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 10px;
  min-height: 220px;
  padding: 12px;
  background: linear-gradient(
    to top,
    rgba(31, 76, 143, 0.06) 0%,
    transparent 100%
  );
  border-radius: 12px;
}

.progress-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.progress-value {
  font-size: 12px;
  font-weight: 800;
}

.progress-bar-vertical {
  width: 100%;
  max-width: 38px;
  height: 140px;
  background: #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

.progress-fill {
  width: 100%;
  border-radius: 0 0 10px 10px;
  transition: height 0.35s ease;
}

.progress-label {
  font-size: 12px;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.7);
  padding: 4px 8px;
  background: rgba(31, 76, 143, 0.08);
  border-radius: 6px;
}

:deep(.kpi-circle-small) {
  width: 132px;
  height: 132px;
  border-radius: 50%;
  border: 8px solid;
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.kpi-value-small) {
  font-size: 30px;
  font-weight: 800;
  line-height: 1;
}

:deep(.info-item) {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

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

.advanced-modal-scrollable {
  overflow-y: auto;
  max-height: calc(90vh - 170px);
}
</style>
