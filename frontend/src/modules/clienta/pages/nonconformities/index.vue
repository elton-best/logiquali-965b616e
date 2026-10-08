<template>
  <ClientALayout current-page="nonconformities">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-clipboard-alert-outline"
        subtitle="Suivi des constats, analyse des causes et plan de traitement"
        title="Fiches de traitement des non-conformités"
      >
        <template #actions>
          <v-btn
            v-if="canCreateNc"
            color="primary"
            prepend-icon="mdi-plus"
            size="large"
            @click="openCreateDialog"
          >
            Nouvelle fiche
          </v-btn>
        </template>
      </PageHeader>

      <NonConformitiesKpiGrid
        :closed-count="closedCount"
        :in-progress-count="inProgressCount"
        :major-count="majorCount"
        :total="ncItems.length"
        @all="filters.status = 'Tous les statuts'"
        @closed="filters.status = 'Clôturée'"
        @in-progress="filters.status = 'En traitement'"
        @major="filters.typeNc = 'N-C majeure'"
      />

      <NonConformitiesFilters
        :closed-count="closedCount"
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        :in-progress-count="inProgressCount"
        :open-count="ncItems.filter((i) => i.status === 'open').length"
        :process-filter-options="processFilterOptions"
        :status-options="statusOptions"
        :type-nc-options="typeNcOptions"
        @reset="resetFilters"
        @update:filters="applyFilters"
      />

      <v-row>
        <v-col cols="12" lg="8">
          <NonConformitiesTable
            :can-read-nc="canReadNc"
            :can-update-nc="canUpdateNc"
            :headers="headers"
            :is-overdue="isOverdue"
            :items="filteredItems"
            :selected-id="selectedId"
            :status-color="statusColor"
            :status-label="statusLabel"
            @edit="openEditDialog"
            @select="selectNc"
          />
        </v-col>

        <v-col cols="12" lg="4">
          <NonConformitiesDetails
            :can-update-nc="canUpdateNc"
            :selected-nc="selectedNc"
            :status-label="statusLabel"
            @edit="openEditDialog"
          />
        </v-col>
      </v-row>

      <v-dialog
        v-model="createDialog"
        :fullscreen="smAndDown"
        max-width="980"
        persistent
        scrollable
      >
        <v-card class="dialog-card" rounded="lg">
          <v-card-title
            class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header"
          >
            <div class="d-flex align-center" style="gap: 12px">
              <v-avatar color="white" size="40">
                <v-icon color="primary">mdi-clipboard-alert-outline</v-icon>
              </v-avatar>
              <div class="d-flex flex-column">
                <span class="text-h6 text-white">{{ ncDialogTitle }}</span>
                <small
                  class="text-white"
                  style="opacity: 0.9"
                >Étape {{ stepper }} / 4</small>
              </div>
            </div>
            <div class="d-flex align-center ga-2">
              <v-chip
                color="white"
                variant="flat"
              >Site: {{ currentSiteLabel }}</v-chip>
              <v-btn
                color="white"
                icon="mdi-close"
                variant="text"
                @click="closeCreateDialog"
              />
            </div>
          </v-card-title>

          <v-card-text class="pa-8 form-scrollable">
            <v-stepper v-model="stepper" alt-labels class="nc-stepper">
              <v-stepper-header>
                <v-stepper-item
                  :color="stepper >= 1 ? 'primary' : undefined"
                  :complete="stepper > 1"
                  :editable="canGoToStep(1)"
                  icon="mdi-information-outline"
                  title="Identification"
                  :value="1"
                  @click="goToStep(1)"
                />
                <v-stepper-item
                  :color="stepper >= 2 ? 'primary' : undefined"
                  :complete="stepper > 2"
                  :editable="canGoToStep(2)"
                  icon="mdi-tools"
                  title="Traitement (optionnel)"
                  :value="2"
                  @click="goToStep(2)"
                />
                <v-stepper-item
                  :color="stepper >= 3 ? 'primary' : undefined"
                  :complete="stepper > 3"
                  :editable="canGoToStep(3)"
                  icon="mdi-account-group"
                  title="Acteurs"
                  :value="3"
                  @click="goToStep(3)"
                />
                <v-stepper-item
                  :color="stepper >= 4 ? 'primary' : undefined"
                  :editable="canGoToStep(4)"
                  icon="mdi-file-check-outline"
                  title="Récapitulatif"
                  :value="4"
                  @click="goToStep(4)"
                />
              </v-stepper-header>

              <v-stepper-window>
                <v-stepper-window-item :value="1">
                  <v-row class="stepper-form-row">
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.process_id"
                        item-title="title"
                        item-value="value"
                        :items="processSelectItems"
                        label="Processus concerné"
                        :loading="loadingReferences"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="draft.date"
                        label="Date d'ouverture (JJ/MM/AAAA)"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.type_constat"
                        :items="['Non-conformité', 'Écart']"
                        label="Type de constat"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.source_detection"
                        item-title="title"
                        item-value="value"
                        :items="[
                          { title: 'Audit interne', value: 'internal_audit' },
                          { title: 'Audit externe', value: 'external_audit' },
                          {
                            title: 'Réclamation client',
                            value: 'customer_complaint',
                          },
                          {
                            title: 'Contrôle interne',
                            value: 'internal_control',
                          },
                          {
                            title: 'Revue de direction',
                            value: 'management_review',
                          },
                          { title: 'Autre', value: 'other' },
                        ]"
                        label="Source de détection"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.type_nc"
                        :items="['N-C majeure', 'N-C mineure']"
                        label="Type de NC"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-text-field
                        v-model="draft.requirement"
                        label="Exigence non respectée"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.description"
                        label="Description"
                        rows="3"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="2">
                  <v-row>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.cause"
                        label="Cause(s) de l'apparition"
                        rows="3"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-alert
                        density="comfortable"
                        icon="mdi-information-outline"
                        type="info"
                        variant="tonal"
                      >
                        Le plan d’action peut être défini après la création de
                        la fiche.
                      </v-alert>
                    </v-col>
                    <v-col cols="12">
                      <div
                        class="d-flex align-center justify-space-between mb-2 action-toolbar"
                      >
                        <div class="text-subtitle-2 font-weight-medium">
                          Actions de traitement (optionnel)
                        </div>
                        <v-btn
                          color="primary"
                          prepend-icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addDraftAction"
                        >
                          Ajouter une action
                        </v-btn>
                      </div>
                      <v-row dense>
                        <v-col
                          v-for="(action, index) in draftActions"
                          :key="`draft-action-${index}`"
                          cols="12"
                        >
                          <v-card
                            class="action-row"
                            rounded="lg"
                            variant="outlined"
                          >
                            <v-card-text>
                              <v-row dense>
                                <v-col cols="12" md="6">
                                  <v-text-field
                                    v-model="action.description"
                                    label="Action à réaliser"
                                    prepend-inner-icon="mdi-hammer-wrench"
                                    variant="outlined"
                                  />
                                </v-col>
                                <v-col cols="12" md="6">
                                  <v-select
                                    v-model="action.type"
                                    item-title="title"
                                    item-value="value"
                                    :items="actionTypeOptions"
                                    label="Type"
                                    prepend-inner-icon="mdi-shape-outline"
                                    variant="outlined"
                                  />
                                </v-col>
                                <v-col cols="12" md="6">
                                  <v-text-field
                                    v-model="action.deadline"
                                    label="Délai (JJ/MM/AAAA)"
                                    prepend-inner-icon="mdi-calendar-clock-outline"
                                    variant="outlined"
                                  />
                                </v-col>
                                <v-col cols="12" md="6">
                                  <v-select
                                    v-model="action.responsibleId"
                                    item-title="title"
                                    item-value="value"
                                    :items="collaboratorSelectItems"
                                    label="Responsable"
                                    prepend-inner-icon="mdi-account-outline"
                                    variant="outlined"
                                  />
                                </v-col>
                                <v-col
                                  class="d-flex align-center justify-end action-row-cta"
                                  cols="12"
                                >
                                  <v-btn
                                    v-if="draftActions.length > 1"
                                    color="error"
                                    prepend-icon="mdi-delete-outline"
                                    size="small"
                                    variant="text"
                                    @click="removeDraftAction(index)"
                                  >
                                    Retirer
                                  </v-btn>
                                </v-col>
                              </v-row>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="3">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.responsible_id"
                        item-title="title"
                        item-value="value"
                        :items="collaboratorSelectItems"
                        label="Responsable"
                        :loading="loadingReferences"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col v-if="isEditMode" cols="12" md="6">
                      <v-select
                        v-model="draft.status"
                        item-title="title"
                        item-value="value"
                        :items="statusSelectItems"
                        label="Statut"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.implicated_ids"
                        chips
                        clearable
                        item-title="title"
                        item-value="value"
                        :items="collaboratorSelectItems"
                        label="Collaborateurs impliqués"
                        :loading="loadingReferences"
                        multiple
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.result"
                        label="Résultat attendu / commentaire"
                        rows="3"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="4">
                  <v-card class="recap-card" variant="outlined">
                    <v-card-text>
                      <div class="section-title">
                        Récapitulatif avant validation
                      </div>
                      <div class="detail-line">
                        <strong>Processus:</strong> {{ selectedProcessTitle }}
                      </div>
                      <div class="detail-line">
                        <strong>Date:</strong> {{ draft.date || "—" }}
                      </div>
                      <div class="detail-line">
                        <strong>Type constat:</strong> {{ draft.type_constat }}
                      </div>
                      <div class="detail-line">
                        <strong>Source:</strong>
                        {{ sourceLabel(draft.source_detection) }}
                      </div>
                      <div class="detail-line">
                        <strong>Type NC:</strong> {{ draft.type_nc }}
                      </div>
                      <div class="detail-line">
                        <strong>Exigence:</strong>
                        {{ draft.requirement || "—" }}
                      </div>
                      <div class="detail-block">
                        <strong>Description:</strong><br>{{
                          draft.description || "—"
                        }}
                      </div>
                      <div class="detail-block">
                        <strong>Cause(s):</strong><br>{{ draft.cause || "—" }}
                      </div>
                      <div class="detail-block">
                        <strong>Actions:</strong>
                        <div
                          v-if="draftActions.length > 0 && hasAnyDraftAction"
                        >
                          <div
                            v-for="(action, index) in draftActions"
                            :key="`recap-action-${index}`"
                            class="action-preview"
                          >
                            <div class="action-index">{{ index + 1 }}</div>
                            <div class="action-body">
                              <div class="action-title">
                                {{ action.description || "—" }}
                              </div>
                              <div class="action-meta">
                                <span>{{ actionTypeLabel(action.type) }}</span>
                                <span>•</span>
                                <span>{{
                                  actionResponsibleName(action.responsibleId)
                                }}</span>
                                <span>•</span>
                                <span>Délai: {{ action.deadline || "—" }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div v-else class="text-medium-emphasis mt-1">
                          Plan d’action à définir après création.
                        </div>
                      </div>
                      <div class="detail-line">
                        <strong>Responsable (fiche):</strong>
                        {{ selectedResponsibleName }}
                      </div>
                      <div class="detail-block">
                        <strong>Résultat attendu:</strong><br>{{
                          draft.result || "—"
                        }}
                      </div>
                    </v-card-text>
                  </v-card>
                </v-stepper-window-item>
              </v-stepper-window>
            </v-stepper>
            <v-progress-linear
              class="mt-4"
              color="primary"
              height="8"
              :model-value="stepProgress"
              rounded
            />
            <div class="text-caption text-medium-emphasis mt-2">
              {{ stepHint }}
            </div>
            <div class="text-caption text-medium-emphasis mt-1 stepper-hint">
              Cliquez sur un onglet déjà validé pour revenir en arrière.
            </div>
          </v-card-text>

          <v-divider />
          <v-card-actions class="pa-6 bg-grey-lighten-5">
            <v-btn
              :disabled="stepper === 1"
              variant="text"
              @click="previousStep"
            >Précédent</v-btn>
            <v-spacer />
            <v-btn variant="text" @click="closeCreateDialog">Annuler</v-btn>
            <v-btn
              v-if="stepper < 4"
              color="primary"
              :disabled="!canGoNextStep"
              @click="nextStep"
            >Suivant</v-btn>
            <v-btn
              v-else-if="isEditMode ? canUpdateNc : canCreateNc"
              color="primary"
              prepend-icon="mdi-content-save"
              variant="elevated"
              @click="submitNc"
            >{{ ncSubmitLabel }}</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type {
    CreateNCDTO,
    NCSource,
    NCType,
  } from '@/api/services/nonconformities.service'
  import { AlertOctagon, CheckCircle, Clock, FileText } from 'lucide-vue-next'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useDisplay } from 'vuetify'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useNonConformities } from '@/modules/clienta/composables/useNonConformities'
  import NonConformitiesDetails from '@/modules/clienta/pages/nonconformities/components/NonConformitiesDetails.vue'
  import NonConformitiesFilters from '@/modules/clienta/pages/nonconformities/components/NonConformitiesFilters.vue'
  import NonConformitiesKpiGrid from '@/modules/clienta/pages/nonconformities/components/NonConformitiesKpiGrid.vue'
  import NonConformitiesTable from '@/modules/clienta/pages/nonconformities/components/NonConformitiesTable.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { getErrorMessage } from '@/utils/errorMessage'
  import { expandPermissionAliases } from '@/utils/permissions'

  type NcStatus = 'open' | 'in_progress' | 'closed'

  interface NonConformityItem {
    id: number
    reference: string
    process: string
    type_constat: 'Non-conformité' | 'Écart'
    source_detection: string
    type_nc: 'N-C majeure' | 'N-C mineure'
    requirement: string
    description: string
    cause: string
    actions?: Array<{
      description: string
      type: 'corrective' | 'preventive' | 'improvement'
      typeLabel: string
      responsibleName: string
      deadline: string
    }>
    responsible: string
    deadline: string
    result: string
    status: NcStatus
    date: string
    closure_date: string | null
  }

  interface SelectItem {
    title: string
    value: number
  }

  const authStore = useAuthStore()
  const toast = useToast()
  const {
    ncs,
    fetchNonConformities,
    createNonConformity,
    updateNonConformity,
    updateStatus,
  } = useNonConformities()
  const { smAndDown } = useDisplay()

  const headers = [
    { title: 'Référence', key: 'reference', sortable: true },
    { title: 'Processus', key: 'process', sortable: true },
    { title: 'Type constat', key: 'type_constat', sortable: true },
    { title: 'Source', key: 'source_detection', sortable: true },
    { title: 'Type NC', key: 'type_nc', sortable: true },
    { title: 'Exigence', key: 'requirement', sortable: true },
    { title: 'Responsable', key: 'responsible', sortable: true },
    { title: 'Délai', key: 'deadline', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: '', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const filters = ref({
    search: '',
    typeNc: 'Tous les types NC',
    status: 'Tous les statuts',
    process: 'Tous les processus',
  })

  const typeNcOptions = ['Tous les types NC', 'N-C majeure', 'N-C mineure']
  const statusOptions = [
    'Tous les statuts',
    'Ouverte',
    'En traitement',
    'Clôturée',
  ]
  const statusSelectItems = [
    { title: 'Ouverte', value: 'open' },
    { title: 'En traitement', value: 'in_progress' },
    { title: 'Clôturée', value: 'closed' },
  ]

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (
      currentUser?.user_type === 'super_admin'
      || isEnterpriseAdminUser(currentUser)
    ) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canReadNc = computed(() => canAccess(['amelioration.non_conformites.read']))
  const canCreateNc = computed(() => canAccess(['amelioration.non_conformites.create']))
  const canUpdateNc = computed(() => canAccess(['non_conformities.update']))

  const processOptions = ref<string[]>([])
  const processSelectItems = ref<SelectItem[]>([])
  const collaboratorSelectItems = ref<SelectItem[]>([])
  const loadingReferences = ref(false)

  function applyFilters (nextFilters: {
    search: string
    typeNc: string
    status: string
    process: string
  }) {
    filters.value = { ...nextFilters }
  }

  const ncItems = ref<NonConformityItem[]>([])

  const selectedId = ref<number | null>(ncItems.value[0]?.id ?? null)

  const createDialog = ref(false)
  const isEditMode = ref(false)
  const editingNcId = ref<number | null>(null)
  const editingReference = ref('')
  const stepper = ref(1)
  const draft = ref({
    process_id: null as number | null,
    date: formatToday(),
    type_constat: 'Non-conformité' as 'Non-conformité' | 'Écart',
    source_detection: 'internal_control',
    type_nc: 'N-C mineure' as 'N-C majeure' | 'N-C mineure',
    requirement: '',
    description: '',
    cause: '',
    status: 'open' as NcStatus,
    responsible_id: null as number | null,
    implicated_ids: [] as number[],
    result: 'En attente',
  })

  type ActionDraft = {
    description: string
    responsibleId: number | null
    deadline: string
    type: 'corrective' | 'preventive' | 'improvement'
  }

  const actionTypeOptions = [
    { title: 'Corrective', value: 'corrective' },
    { title: 'Préventive', value: 'preventive' },
    { title: 'Amélioration', value: 'improvement' },
  ]

  const draftActions = ref<ActionDraft[]>([])

  function buildActionDraft (): ActionDraft {
    return {
      description: '',
      responsibleId: null,
      deadline: '',
      type: 'corrective',
    }
  }

  function addDraftAction () {
    draftActions.value.push(buildActionDraft())
  }

  function removeDraftAction (index: number) {
    draftActions.value.splice(index, 1)
  }

  const filteredItems = computed(() => {
    return ncItems.value.filter(item => {
      const q = filters.value.search.trim().toLowerCase()
      const matchSearch
        = q.length === 0
          || item.reference.toLowerCase().includes(q)
          || item.process.toLowerCase().includes(q)
          || item.requirement.toLowerCase().includes(q)
          || item.description.toLowerCase().includes(q)
          || item.cause.toLowerCase().includes(q)
          || item.responsible.toLowerCase().includes(q)

      const matchTypeNc
        = filters.value.typeNc === 'Tous les types NC'
          || item.type_nc === filters.value.typeNc
      const statusUi = statusLabel(item.status)
      const matchStatus
        = filters.value.status === 'Tous les statuts'
          || statusUi === filters.value.status
      const matchProcess
        = filters.value.process === 'Tous les processus'
          || item.process === filters.value.process

      return matchSearch && matchTypeNc && matchStatus && matchProcess
    })
  })

  const selectedNc = computed(() => {
    const pool
      = filteredItems.value.length > 0 ? filteredItems.value : ncItems.value
    return pool.find(item => item.id === selectedId.value) || pool[0] || null
  })

  const majorCount = computed(
    () => ncItems.value.filter(item => item.type_nc === 'N-C majeure').length,
  )
  const inProgressCount = computed(
    () =>
      ncItems.value.filter(
        item => item.status === 'open' || item.status === 'in_progress',
      ).length,
  )
  const closedCount = computed(
    () => ncItems.value.filter(item => item.status === 'closed').length,
  )

  const processFilterOptions = computed(() => [
    'Tous les processus',
    ...processOptions.value,
  ])
  const hasActiveFilters = computed(
    () =>
      filters.value.search.trim().length > 0
      || filters.value.typeNc !== 'Tous les types NC'
      || filters.value.status !== 'Tous les statuts'
      || filters.value.process !== 'Tous les processus',
  )
  const stepProgress = computed(() => (stepper.value / 4) * 100)
  const stepHint = computed(() => {
    if (stepper.value === 1)
      return 'Renseignez les informations d’identification de la fiche.'
    if (stepper.value === 2)
      return 'Décrivez les causes. Le plan d’action peut être défini plus tard.'
    if (stepper.value === 3)
      return 'Affectez les acteurs responsables du traitement (optionnel).'
    return 'Vérifiez le récapitulatif avant enregistrement.'
  })
  const ncDialogTitle = computed(() =>
    isEditMode.value
      ? `Modifier la fiche ${editingReference.value || ''}`.trim()
      : 'Nouvelle fiche de traitement',
  )
  const ncSubmitLabel = computed(() =>
    isEditMode.value ? 'Mettre à jour' : 'Créer la fiche',
  )
  const canGoNextStep = computed(() => {
    if (stepper.value === 1) {
      return (
        !!draft.value.process_id
        && !!fromFrenchDate(draft.value.date)
        && !!draft.value.type_constat
        && !!draft.value.source_detection
        && !!draft.value.type_nc
        && draft.value.requirement.trim().length > 0
        && draft.value.description.trim().length > 0
      )
    }
    if (stepper.value === 2) {
      return draft.value.cause.trim().length > 0
    }
    if (stepper.value === 3) {
      return true
    }
    return true
  })

  const currentSiteId = computed<number | null>(() => {
    const direct = authStore.currentSiteId
    if (typeof direct === 'number' && Number.isFinite(direct)) {
      return direct
    }

    const stored = Number(localStorage.getItem('current_site_id'))
    return Number.isFinite(stored) && stored > 0 ? stored : null
  })

  const currentSiteLabel = computed(() => {
    const readSiteLabel = (site: any): string => {
      const label = String(
        site?.name
        || site?.title
          || site?.attributes?.name
        || site?.attributes?.title
          || '',
      ).trim()
      return label
    }

    const currentSite = authStore.currentSite as any
    const directLabel = readSiteLabel(currentSite)
    if (directLabel) {
      return directLabel
    }

    const siteId = currentSiteId.value
    if (!siteId) {
      return 'Non défini'
    }

    const fallbackSite = (Array.isArray(authStore.availableSites)
      ? authStore.availableSites
      : []
    ).find((site: any) => {
      const candidateId = Number(site?.id || site?.value || site?.attributes?.id || 0)
      return candidateId === Number(siteId)
    })

    const fallbackLabel = readSiteLabel(fallbackSite)
    return fallbackLabel || `Site ${siteId}`
  })

  const selectedProcessTitle = computed(() => {
    const found = processSelectItems.value.find(
      item => item.value === draft.value.process_id,
    )
    return found?.title || '—'
  })

  const selectedResponsibleName = computed(() => {
    const found = collaboratorSelectItems.value.find(
      item => item.value === draft.value.responsible_id,
    )
    return found?.title || '—'
  })

  onMounted(async () => {
    await Promise.all([loadDynamicReferences(), refreshNcItems()])
  })

  watch(
    () => authStore.currentSiteId,
    async () => {
      await Promise.all([loadDynamicReferences(), refreshNcItems()])
    },
  )

  function toFrenchDate (date: string | null | undefined): string {
    if (!date) return '—'
    const parsed = new Date(date)
    if (Number.isNaN(parsed.getTime())) return date
    const day = String(parsed.getDate()).padStart(2, '0')
    const month = String(parsed.getMonth() + 1).padStart(2, '0')
    const year = parsed.getFullYear()
    return `${day}/${month}/${year}`
  }

  function fromFrenchDate (date: string): string | undefined {
    const parts = date.split('/')
    if (parts.length !== 3) return undefined
    const day = Number(parts[0])
    const month = Number(parts[1])
    const year = Number(parts[2])
    if (!day || !month || !year) return undefined
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
  }

  function sourceLabel (source?: string | null): string {
    switch (source) {
      case 'internal_audit': {
        return 'Audit interne'
      }
      case 'external_audit': {
        return 'Audit externe'
      }
      case 'customer_complaint': {
        return 'Réclamation client'
      }
      case 'internal_control': {
        return 'Contrôle interne'
      }
      case 'management_review': {
        return 'Revue de direction'
      }
      case 'other': {
        return 'Autre'
      }
      default: {
        return 'Contrôle interne'
      }
    }
  }

  function cleanRequirementText (raw: unknown, fallback?: unknown): string {
    const normalize = (value: unknown): string =>
      typeof value === 'string' ? value.trim() : ''
    const primary = normalize(raw)
    const secondary = normalize(fallback)

    const pick = primary || secondary
    if (!pick) return '—'

    if (pick.startsWith('{') || pick.startsWith('[')) {
      try {
        const parsed = JSON.parse(pick)
        if (typeof parsed === 'string' && parsed.trim()) {
          return parsed.trim()
        }
        return secondary
          && !secondary.startsWith('{')
          && !secondary.startsWith('[')
          ? secondary
          : '—'
      } catch {
        return '—'
      }
    }

    return pick
  }

  async function refreshNcItems (): Promise<void> {
    if (!currentSiteId.value) {
      ncItems.value = []
      return
    }

    await fetchNonConformities({
      per_page: 200,
      site_id: currentSiteId.value,
    })

    ncItems.value = ncs.value.map((nc: any) => {
      const processName
        = nc.process?.title || nc.process?.name || 'Processus non défini'
      const typeNc = nc.type === 'majeure' ? 'N-C majeure' : 'N-C mineure'
      const mappedActions = Array.isArray(nc.actions)
        ? nc.actions
          .map((action: any) => ({
            description: String(
              action.description || action.title || '',
            ).trim(),
            type: action.type || 'corrective',
            typeLabel: actionTypeLabel(action.type),
            responsibleName: action.responsible?.name || 'Non affecté',
            deadline: toFrenchDate(action.deadline),
          }))
          .filter((action: any) => Boolean(action.description))
        : []
      const rawFallback = String(
        nc.corrective_action || nc.immediate_action || '',
      ).trim()
      const fallbackActions
        = mappedActions.length === 0 && rawFallback
          ? rawFallback
            .split(/\r?\n/)
            .map((item: string) => item.trim())
            .filter(Boolean)
            .map(item => ({
              description: item,
              type: 'corrective',
              typeLabel: actionTypeLabel('corrective'),
              responsibleName: nc.responsible?.name || 'Non affecté',
              deadline: toFrenchDate(nc.due_date),
            }))
          : []
      return {
        id: nc.id,
        reference: nc.reference,
        process: processName,
        type_constat: nc.finding_type === 'gap' ? 'Écart' : 'Non-conformité',
        source_detection: sourceLabel(nc.detection_source),
        type_nc: typeNc,
        requirement: cleanRequirementText(
          nc.requirement_reference,
          nc.description,
        ),
        description: nc.description || '—',
        cause: nc.root_cause || '—',
        actions: mappedActions.length > 0 ? mappedActions : fallbackActions,
        responsible: nc.responsible?.name || 'Non affecté',
        deadline: toFrenchDate(nc.due_date),
        result: nc.result_summary || '—',
        status:
          nc.status === 'closed'
            ? 'closed'
            : (nc.status === 'open'
              ? 'open'
              : 'in_progress'),
        date: toFrenchDate(nc.detected_date),
        closure_date: nc.closure_date ? toFrenchDate(nc.closure_date) : null,
      }
    })

    if (!selectedId.value && ncItems.value.length > 0) {
      selectedId.value = ncItems.value[0]?.id || null
    }
  }

  async function loadDynamicReferences (): Promise<void> {
    if (!currentSiteId.value) {
      processOptions.value = []
      processSelectItems.value = []
      collaboratorSelectItems.value = []
      return
    }

    loadingReferences.value = true
    try {
      await Promise.all([
        loadProcesses(currentSiteId.value),
        loadCollaborators(currentSiteId.value),
      ])
    } catch (error) {
      console.error('[NC] load references failed', error)
      toast.error(
        getErrorMessage(
          error,
          'Impossible de charger les données dynamiques (processus/collaborateurs).',
        ),
      )
    } finally {
      loadingReferences.value = false
    }
  }

  async function loadProcesses (siteId: number): Promise<void> {
    const response = await processService.getProcesses(
      { site_id: siteId },
      1,
      200,
    )
    const rows = Array.isArray(response?.data) ? response.data : []

    const mapped = rows
      .map((row: any) => {
        const id = Number(row?.id || 0)
        const title = String(
          row?.title || row?.name || row?.code || `Processus ${id}`,
        )
        return { value: id, title }
      })
      .filter((item: SelectItem) => item.value > 0)

    processSelectItems.value = mapped
    processOptions.value = mapped.map(item => item.title)

    if (!draft.value.process_id && mapped.length > 0) {
      draft.value.process_id = mapped[0]?.value || null
    }
  }

  async function loadCollaborators (siteId: number): Promise<void> {
    let rows: any[] = []

    try {
      const { data } = await api.get('/users/job-description-collaborators', {
        params: { site_id: siteId, per_page: 300 },
      })
      rows = extractRows(data)
    } catch {
      const { data } = await api.get('/users', {
        params: { site_id: siteId, per_page: 300 },
      })
      rows = extractRows(data)
    }

    collaboratorSelectItems.value = rows
      .map((row: any) => normalizeUserRow(row))
      .filter((item: SelectItem) => item.value > 0)
  }

  function extractRows (payload: any): any[] {
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload)) return payload
    return []
  }

  function normalizeUserRow (raw: any): SelectItem {
    const attrs = raw?.attributes || raw || {}
    const id = Number(raw?.id || attrs?.id || 0)
    const fullName = String(
      attrs?.name
        || `${attrs?.first_name || ''} ${attrs?.last_name || ''}`.trim()
      || attrs?.email
        || `Collaborateur ${id}`,
    )

    return {
      value: id,
      title: fullName,
    }
  }

  function selectNc (id: number): void {
    selectedId.value = id
  }

  function getRawNcById (id: number): any | null {
    return ncs.value.find((nc: any) => Number(nc.id) === Number(id)) || null
  }

  function openCreateDialog (): void {
    if (!canCreateNc.value) {
      return
    }
    isEditMode.value = false
    editingNcId.value = null
    editingReference.value = ''
    resetDraft()
    stepper.value = 1
    createDialog.value = true
  }

  function closeCreateDialog (): void {
    createDialog.value = false
    stepper.value = 1
    isEditMode.value = false
    editingNcId.value = null
    editingReference.value = ''
    resetDraft()
  }

  function openEditDialog (id: number): void {
    if (!canUpdateNc.value) {
      return
    }
    const nc = getRawNcById(id)
    if (!nc) {
      toast.error('Fiche introuvable.')
      return
    }

    isEditMode.value = true
    editingNcId.value = Number(nc.id)
    editingReference.value = String(nc.reference || '')
    draft.value = {
      process_id: nc.process_id ?? null,
      date: toFrenchDate(nc.detected_date),
      type_constat: nc.finding_type === 'gap' ? 'Écart' : 'Non-conformité',
      source_detection: nc.detection_source || 'internal_control',
      type_nc: nc.type === 'majeure' ? 'N-C majeure' : 'N-C mineure',
      requirement: nc.requirement_reference || '',
      description: nc.description || '',
      cause: nc.root_cause || '',
      // actions structurées si disponibles
      // sinon on garde une action vide
      responsible_id: nc.responsible_id ?? null,
      result: nc.result_summary || 'En attente',
      status:
        nc.status === 'closed'
          ? 'closed'
          : (nc.status === 'open'
            ? 'open'
            : 'in_progress'),
      implicated_ids: [],
    }
    const structured
      = Array.isArray(nc.actions) && nc.actions.length > 0
        ? nc.actions
          .map((action: any) => ({
            description: String(
              action.description || action.title || '',
            ).trim(),
            responsibleId:
              action.responsible_id ?? action.responsible?.id ?? null,
            deadline: toFrenchDate(action.deadline),
            type: action.type || 'corrective',
          }))
          .filter((action: any) => Boolean(action.description))
        : []
    const fromText
      = structured.length === 0
        && String(nc.corrective_action || nc.immediate_action || '').trim()
        ? String(nc.corrective_action || nc.immediate_action || '')
          .trim()
          .split(/\r?\n/)
          .map((item: string) => item.trim())
          .filter(Boolean)
          .map((item: string) => ({
            description: item,
            responsibleId: nc.responsible_id ?? null,
            deadline: toFrenchDate(nc.due_date),
            type: 'corrective',
          }))
        : []
    draftActions.value = structured.length > 0 ? structured : fromText
    stepper.value = 1
    createDialog.value = true
  }

  function nextStep (): void {
    if (canGoNextStep.value && stepper.value < 4) {
      stepper.value += 1
    }
  }

  function canGoToStep (target: number): boolean {
    return target <= stepper.value
  }

  function goToStep (target: number): void {
    if (canGoToStep(target)) {
      stepper.value = target
    }
  }

  function previousStep (): void {
    if (stepper.value > 1) {
      stepper.value -= 1
    }
  }

  function resetFilters (): void {
    filters.value = {
      search: '',
      typeNc: 'Tous les types NC',
      status: 'Tous les statuts',
      process: 'Tous les processus',
    }
  }

  async function submitNc (): Promise<void> {
    if (isEditMode.value && !canUpdateNc.value) {
      return
    }
    if (!isEditMode.value && !canCreateNc.value) {
      return
    }
    if (!currentSiteId.value) {
      toast.error('Sélectionnez un site avant de créer une fiche.')
      return
    }
    if (!draft.value.process_id) {
      toast.error('Le processus est obligatoire.')
      return
    }
    if (isEditMode.value && !editingNcId.value) {
      toast.error('Identifiant de fiche invalide.')
      return
    }

    const actions = normalizeDraftActions(draftActions.value)
    if (actions.some(action => !action.responsible_id || !action.deadline)) {
      toast.error('Chaque action doit avoir un responsable et un délai.')
      stepper.value = 2
      return
    }
    const isMajor = draft.value.type_nc === 'N-C majeure'

    const findingType: CreateNCDTO['finding_type']
      = draft.value.type_constat === 'Écart' ? 'gap' : 'non_conformity'

    const payload: CreateNCDTO = {
      title: `${draft.value.type_constat} - ${selectedProcessTitle.value}`,
      description: draft.value.description || 'Description non renseignée',
      type: (isMajor ? 'majeure' : 'mineure') as NCType,
      source: (draft.value.source_detection === 'customer_complaint'
        ? 'reclamation'
        : 'interne') as NCSource,
      site_id: currentSiteId.value,
      process_id: draft.value.process_id || undefined,
      detected_date:
        fromFrenchDate(draft.value.date) || new Date().toISOString().slice(0, 10),
      severity: isMajor ? 4 : 2,
      root_cause: draft.value.cause,
      corrective_action:
        actions.length > 0
          ? actions.map(item => item.description).join('\n')
          : undefined,
      responsible_id:
        actions[0]?.responsible_id || draft.value.responsible_id || undefined,
      due_date: actions[0]?.deadline
        ? fromFrenchDate(actions[0].deadline)
        : undefined,
      actions:
        actions.length > 0
          ? actions.map(action => ({
            type: action.type,
            description: action.description,
            responsible_id: action.responsible_id,
            deadline: fromFrenchDate(action.deadline),
          }))
          : undefined,
      requirement_reference: draft.value.requirement,
      finding_type: findingType,
      detection_source: draft.value.source_detection as any,
      result_summary: draft.value.result,
    }

    try {
      if (isEditMode.value && editingNcId.value) {
        await updateNonConformity(editingNcId.value, payload)
        if (draft.value.status) {
          await updateStatus(editingNcId.value, {
            status: mapStatusForApi(draft.value.status),
          })
        }
      } else {
        await createNonConformity(payload)
      }

      await refreshNcItems()
      if (isEditMode.value && editingNcId.value) {
        selectedId.value = editingNcId.value
      }
      createDialog.value = false
      stepper.value = 1
      const wasEdit = isEditMode.value
      isEditMode.value = false
      editingNcId.value = null
      editingReference.value = ''
      resetDraft()
      toast.success(
        wasEdit
          ? 'Fiche de non-conformité mise à jour.'
          : 'Fiche de non-conformité créée.',
      )
    } catch (error: any) {
      const message = error?.response?.data?.message || error?.message
      if (error?.response?.status === 403) {
        toast.error(
          'Accès refusé: vous n’avez pas les autorisations nécessaires.',
        )
      } else {
        toast.error(message || 'Erreur lors de l’enregistrement de la fiche.')
      }
    }
  }

  function resetDraft (): void {
    draft.value = {
      process_id: processSelectItems.value[0]?.value || null,
      date: formatToday(),
      type_constat: 'Non-conformité',
      source_detection: 'internal_control',
      type_nc: 'N-C mineure',
      requirement: '',
      description: '',
      cause: '',
      status: 'open',
      responsible_id: collaboratorSelectItems.value[0]?.value || null,
      implicated_ids: [],
      result: 'En attente',
    }
    draftActions.value = []
  }

  function statusLabel (
    status: NcStatus,
  ): 'Ouverte' | 'En traitement' | 'Clôturée' {
    if (status === 'closed') return 'Clôturée'
    if (status === 'in_progress') return 'En traitement'
    return 'Ouverte'
  }

  function statusColor (status: NcStatus): string {
    if (status === 'closed') return 'success'
    if (status === 'in_progress') return 'warning'
    return 'error'
  }

  function mapStatusForApi (
    status: NcStatus,
  ): 'open' | 'analysis' | 'corrective_action' | 'verification' | 'closed' {
    if (status === 'in_progress') return 'corrective_action'
    return status
  }

  function actionTypeLabel (
    value: ActionDraft['type'] | string | undefined,
  ): string {
    if (value === 'preventive') return 'Préventive'
    if (value === 'improvement') return 'Amélioration'
    return 'Corrective'
  }

  function actionResponsibleName (responsibleId: number | null): string {
    const found = collaboratorSelectItems.value.find(
      item => item.value === responsibleId,
    )
    return found?.title || 'Non affecté'
  }

  function normalizeDraftActions (actions: ActionDraft[]) {
    return actions
      .map(item => ({
        type: item.type || 'corrective',
        description: item.description?.trim() || '',
        responsible_id: item.responsibleId ?? null,
        deadline: item.deadline || '',
      }))
      .filter(item => item.description)
  }

  const hasAnyDraftAction = computed(() =>
    draftActions.value.some(action => action.description?.trim()),
  )

  function isOverdue (deadline: string): boolean {
    const parts = deadline.split('/')
    if (parts.length !== 3) return false

    const day = Number(parts[0])
    const month = Number(parts[1])
    const year = Number(parts[2])
    if (!day || !month || !year) return false

    const due = new Date(year, month - 1, day)
    const today = new Date()
    return due < today
  }

  function formatToday (): string {
    const d = new Date()
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}/${month}/${year}`
  }
</script>

<style scoped>
.recap-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.action-row {
  border-color: rgba(15, 23, 42, 0.12);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}

.action-toolbar {
  flex-wrap: wrap;
  gap: 8px;
}

.action-row-cta {
  justify-content: flex-end;
}

.dialog-card {
  max-height: 92vh;
  overflow: hidden;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 2;
}

.form-scrollable {
  max-height: calc(92vh - 160px);
  overflow-y: auto;
}

.stepper-hint {
  font-style: italic;
}

:deep(.nc-stepper .v-stepper-header) {
  gap: 8px;
  padding: 8px 0 6px;
}

:deep(.nc-stepper .v-stepper-item) {
  min-height: 40px;
  padding: 6px 10px;
  border-radius: 10px;
  background: rgba(248, 250, 252, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.18);
  box-shadow: inset 0 0 18px rgba(148, 163, 184, 0.18);
  backdrop-filter: blur(6px);
  transition: all 0.2s ease;
}

:deep(.nc-stepper .v-stepper-item--active) {
  background: linear-gradient(
    135deg,
    rgba(91, 141, 217, 0.16),
    rgba(91, 141, 217, 0.04)
  );
  border-color: rgba(91, 141, 217, 0.35);
  box-shadow: 0 6px 12px rgba(91, 141, 217, 0.16);
}

:deep(.nc-stepper .v-stepper-item__title) {
  font-weight: 600;
  font-size: 0.85rem;
  line-height: 1.2;
}

:deep(.nc-stepper .v-stepper-item--active .v-stepper-item__title) {
  color: #1d4ed8;
}

:deep(.nc-stepper .v-stepper-item__avatar) {
  width: 24px;
  height: 24px;
  font-size: 0.75rem;
}

:deep(.nc-stepper .v-stepper-window) {
  padding-top: 6px;
}

.stepper-form-row {
  margin-top: 4px;
}

.stepper-form-row :deep(.v-field__outline),
.stepper-form-row :deep(.v-field__outline__start),
.stepper-form-row :deep(.v-field__outline__end) {
  margin-top: 6px;
}

:deep(.nc-stepper .v-stepper-item--active .v-stepper-item__avatar) {
  background: #1d4ed8;
  color: #fff;
}

:deep(.nc-stepper .v-stepper-item--complete) {
  border-color: rgba(34, 197, 94, 0.4);
  background: rgba(34, 197, 94, 0.06);
}

:deep(.nc-stepper .v-stepper-item--complete .v-stepper-item__avatar) {
  background: #22c55e;
  color: #fff;
}

:deep(.nc-stepper .v-stepper-item--editable) {
  cursor: pointer;
}

@media (max-width: 600px) {
  .action-row-cta {
    justify-content: flex-start;
  }
}
</style>
