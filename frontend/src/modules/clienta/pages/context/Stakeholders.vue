<template>
  <ClientALayout current-page="stakeholders">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-account-group"
        subtitle="Registre des parties intéressées et leurs exigences"
        title="Parties intéressées"
      >
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="handleAdd">
            Nouvelle partie
          </v-btn>
          <v-btn
            color="secondary"
            :loading="exportingDocument"
            prepend-icon="mdi-file-plus"
            variant="tonal"
            @click="openGenerationDialog('draft')"
          >
            Générer brouillon
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exportingDocument"
            prepend-icon="mdi-eye"
            variant="text"
            @click="handlePreviewDraft"
          >
            Prévisualiser
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exportingDocument"
            prepend-icon="mdi-download"
            variant="text"
            @click="handleDownloadDraft"
          >
            Télécharger
          </v-btn>
          <ColumnVisibilitySelector
            v-if="viewMode === 'list'"
            v-model="visibleColumnKeys"
            :columns="allStakeholderColumns"
            storage-key="stakeholders_table_columns"
          />
          <v-btn
            color="warning"
            :disabled="submittingForVerification"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="openVerifyDialog"
          >
            Vérifier document
          </v-btn>
        </template>
      </PageHeader>

      <StakeholdersStats :stats="stats" />

      <StakeholdersFilters
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        :influence-options="influenceOptions"
        :stakeholder-type-options="stakeholderTypeOptions"
        :view-mode="viewMode"
        @reset="resetFilters"
        @update:filters="applyFilters"
        @update:view-mode="viewMode = $event"
      />

      <!-- Vue Grid -->
      <StakeholdersGrid
        v-if="viewMode === 'grid'"
        :format-date="formatDate"
        :get-deadline-color="getDeadlineColor"
        :get-influence-color="getInfluenceColor"
        :get-stakeholder-actions="getStakeholderActions"
        :get-type-color="getTypeColor"
        :get-type-icon="getTypeIcon"
        :influence-label="influenceLabel"
        :stakeholders="allStakeholders"
        :truncate="truncate"
        :type-label="typeLabel"
        @add="handleAdd"
        @edit="handleEdit"
      />

      <!-- Vue Liste -->
      <StakeholdersTable
        v-if="viewMode === 'list'"
        :format-date="formatDate"
        :get-deadline-color="getDeadlineColor"
        :get-influence-color="getInfluenceColor"
        :get-stakeholder-actions="getStakeholderActions"
        :get-type-color="getTypeColor"
        :get-type-icon="getTypeIcon"
        :headers="listHeaders"
        :influence-label="influenceLabel"
        :items-per-page="listItemsPerPage"
        :stakeholders="allStakeholders"
        :truncate="truncate"
        :type-label="typeLabel"
        @add="handleAdd"
        @edit="handleEdit"
      />

      <!-- Vue Actions -->
      <StakeholderActionsTable v-if="viewMode === 'actions'" :stakeholders="allStakeholders" />

      <GeneratedDocumentConfigDialog
        v-model="generationDialog"
        :loading="exportingDocument"
        :site-id="resolveSiteId()"
        @confirm="confirmGenerationConfig"
      />

      <v-dialog v-model="showDialog" max-width="920" persistent scrollable>
        <v-card class="dialog-card" rounded="xl">
          <div class="dialog-header pa-5" style="background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%)">
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center">
                <v-avatar class="mr-4 elevation-4" color="white" size="48">
                  <v-icon color="primary" size="26">mdi-account-group</v-icon>
                </v-avatar>
                <div>
                  <h2 class="text-h5 text-white font-weight-bold">
                    {{ currentStakeholder?.id ? 'Modifier' : 'Nouvelle' }} partie intéressée
                  </h2>
                  <p class="text-white text-opacity-90 mb-0 text-body-2">Renseignez les informations de la partie intéressée</p>
                </div>
              </div>
              <v-btn
                color="white"
                icon="mdi-close"
                size="default"
                variant="text"
                @click="closeDialog"
              />
            </div>
          </div>

          <v-card-text class="pa-6">
            <v-tabs v-model="dialogStep" class="mb-6" color="primary" density="compact">
              <v-tab value="info">Informations</v-tab>
              <v-tab value="needs">Besoins et actions</v-tab>
              <v-tab :disabled="!canAccessReviewStep" value="review">Récapitulatif</v-tab>
            </v-tabs>

            <div v-if="dialogStep === 'review'" class="mb-8">
              <v-alert class="mb-4" type="info" variant="tonal">
                Vérifiez les données avant enregistrement.
              </v-alert>
              <v-row class="mb-4" dense>
                <v-col cols="12" md="4">
                  <v-card rounded="lg" variant="tonal">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Partie intéressée</div>
                      <div class="text-body-1 font-weight-bold">{{ stakeholderForm.name || 'Non renseigné' }}</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card rounded="lg" variant="tonal">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Type</div>
                      <div class="text-body-1 font-weight-bold">{{ typeLabel(stakeholderForm.type) }}</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card rounded="lg" variant="tonal">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Besoins</div>
                      <div class="text-body-1 font-weight-bold">{{ stakeholderForm.needs.length }}</div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
              <StakeholderRequirementsView
                :collaborator-options="collaboratorOptions"
                :stakeholder="{ needs: stakeholderForm.needs }"
              />
              <v-divider class="my-6" />
            </div>

            <!-- Informations principales -->
            <div v-if="dialogStep === 'info'" class="mb-8">
              <h3 class="text-h5 font-weight-bold mb-4 d-flex align-center">
                <v-icon class="mr-2" color="primary">mdi-information</v-icon>
                Informations principales
              </h3>
              <v-row>
                <v-col cols="12" md="8">
                  <v-text-field
                    v-model="stakeholderForm.name"
                    bg-color="grey-lighten-5"
                    density="comfortable"
                    label="Nom de la partie intéressée *"
                    placeholder="Ex: Clients, Fournisseurs..."
                    prepend-inner-icon="mdi-account-group"
                    rounded="lg"
                    variant="solo"
                  />
                </v-col>

                <v-col cols="12" md="2">
                  <v-select
                    v-model="stakeholderForm.type"
                    bg-color="grey-lighten-5"
                    density="comfortable"
                    item-title="title"
                    item-value="value"
                    :items="stakeholderTypes"
                    label="Type *"
                    prepend-inner-icon="mdi-tag"
                    rounded="lg"
                    variant="solo"
                  />
                </v-col>

                <v-col cols="12" md="2">
                  <v-select
                    v-model="stakeholderForm.influence"
                    bg-color="grey-lighten-5"
                    density="comfortable"
                    item-title="title"
                    item-value="value"
                    :items="influenceLevels"
                    label="Degré de pertinence *"
                    prepend-inner-icon="mdi-chart-line"
                    rounded="lg"
                    variant="solo"
                  />
                </v-col>
              </v-row>
            </div>

            <v-divider v-if="dialogStep === 'info'" class="my-8" />

            <!-- Besoins et attentes -->
            <div v-if="dialogStep === 'needs'">
              <div class="d-flex align-center justify-space-between mb-6">
                <div>
                  <h3 class="text-h5 font-weight-bold d-flex align-center">
                    <v-icon class="mr-2" color="warning">mdi-clipboard-list</v-icon>
                    Besoins et attentes
                  </h3>
                  <p class="text-body-2 text-medium-emphasis mb-0 mt-1">Structure: Besoin → Exigences → Actions</p>
                </div>
                <v-btn color="warning" prepend-icon="mdi-plus" size="large" @click="addNeed">
                  Ajouter un besoin
                </v-btn>
              </div>

              <v-row>
                <v-col
                  v-for="(need, needIndex) in stakeholderForm.needs"
                  :key="needIndex"
                  cols="12"
                >
                  <v-card
                    class="need-card"
                    rounded="xl"
                    style="border: 2px solid #fbbf24;"
                    variant="outlined"
                  >
                    <v-card-text class="pa-6">
                      <div class="d-flex align-center justify-space-between mb-4">
                        <v-chip class="font-weight-bold" color="warning" size="large">
                          <v-icon start>mdi-lightbulb</v-icon>
                          Besoin {{ needIndex + 1 }}
                        </v-chip>
                        <v-btn
                          color="error"
                          icon="mdi-delete"
                          size="small"
                          variant="tonal"
                          @click="removeNeed(needIndex)"
                        />
                      </div>

                      <v-row>
                        <v-col cols="12" md="8">
                          <v-textarea
                            :ref="(el) => setNeedDescriptionRef(needIndex, el)"
                            v-model="need.description"
                            bg-color="white"
                            density="comfortable"
                            label="Description du besoin *"
                            placeholder="Ex: Produits conformes aux spécifications"
                            rounded="lg"
                            rows="2"
                            variant="solo"
                          />
                        </v-col>
                        <v-col cols="12" md="4">
                          <v-select
                            v-model="need.priority"
                            bg-color="white"
                            density="comfortable"
                            item-title="title"
                            item-value="value"
                            :items="priorityLevels"
                            label="Priorité"
                            rounded="lg"
                            variant="solo"
                          />
                        </v-col>
                      </v-row>

                      <!-- Exigences -->
                      <v-divider class="my-4" />
                      <div class="d-flex align-center justify-space-between mb-3">
                        <h4 class="text-subtitle-1 font-weight-bold d-flex align-center">
                          <v-icon class="mr-2" color="info" size="20">mdi-file-document</v-icon>
                          Exigences
                        </h4>
                        <v-btn
                          color="info"
                          prepend-icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addRequirement(needIndex)"
                        >
                          Ajouter exigence
                        </v-btn>
                      </div>

                      <v-row v-if="need.requirements.length > 0">
                        <v-col
                          v-for="(req, reqIndex) in need.requirements"
                          :key="reqIndex"
                          cols="12"
                        >
                          <v-card class="mb-2" color="info" rounded="lg" variant="tonal">
                            <v-card-text class="pa-4">
                              <div class="d-flex align-center justify-space-between mb-3">
                                <v-chip color="info" size="small" variant="flat">
                                  Exigence {{ reqIndex + 1 }}
                                </v-chip>
                                <v-btn
                                  color="error"
                                  icon="mdi-close"
                                  size="x-small"
                                  variant="text"
                                  @click="removeRequirement(needIndex, reqIndex)"
                                />
                              </div>
                              <v-row>
                                <v-col cols="12" md="8">
                                  <v-textarea
                                    :ref="(el) => setRequirementDescriptionRef(needIndex, reqIndex, el)"
                                    v-model="req.description"
                                    bg-color="white"
                                    density="compact"
                                    hide-details
                                    label="Description de l'exigence"
                                    placeholder="Ex: ISO 9001:2015 §8.2 - Exigences relatives aux produits"
                                    rows="2"
                                    variant="outlined"
                                  />
                                </v-col>
                                <v-col cols="12" md="4">
                                  <v-select
                                    v-model="req.type"
                                    bg-color="white"
                                    density="compact"
                                    hide-details
                                    item-title="title"
                                    item-value="value"
                                    :items="requirementTypes"
                                    label="Type"
                                    variant="outlined"
                                  />
                                </v-col>
                              </v-row>

                              <!-- Actions pour cette exigence -->
                              <div class="mt-3">
                                <div class="d-flex align-center justify-space-between mb-2">
                                  <span class="text-caption font-weight-bold d-flex align-center">
                                    <v-icon class="mr-1" size="16">mdi-check-circle</v-icon>
                                    Actions
                                  </span>
                                  <v-btn
                                    color="success"
                                    prepend-icon="mdi-plus"
                                    size="x-small"
                                    variant="text"
                                    @click="addAction(needIndex, reqIndex)"
                                  >
                                    Action
                                  </v-btn>
                                </div>
                                <div v-for="(action, actIndex) in req.actions" :key="actIndex" class="mb-2">
                                  <v-card rounded="lg" variant="outlined">
                                    <v-card-text class="pa-3">
                                      <div class="d-flex gap-2">
                                        <v-text-field
                                          :ref="(el) => setActionDescriptionRef(needIndex, reqIndex, actIndex, el)"
                                          v-model="action.description"
                                          :autofocus="actIndex === 0"
                                          class="flex-grow-1"
                                          density="compact"
                                          hide-details
                                          placeholder="Description de l'action"
                                          variant="plain"
                                        />
                                        <v-autocomplete
                                          v-model="action.responsible_user_id"
                                          density="compact"
                                          hide-details
                                          item-title="label"
                                          item-value="id"
                                          :items="collaboratorOptions"
                                          :loading="collaboratorsLoading"
                                          no-data-text="Aucun collaborateur disponible"
                                          placeholder="Responsable *"
                                          style="max-width: 240px;"
                                          variant="outlined"
                                        />
                                        <v-text-field
                                          v-model="action.deadline"
                                          density="compact"
                                          hide-details
                                          placeholder="Délai *"
                                          style="max-width: 150px;"
                                          type="date"
                                          variant="plain"
                                        />
                                        <v-btn
                                          color="error"
                                          icon="mdi-delete"
                                          size="small"
                                          variant="text"
                                          @click="removeAction(needIndex, reqIndex, actIndex)"
                                        />
                                      </div>
                                    </v-card-text>
                                  </v-card>
                                </div>
                              </div>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                      <v-alert
                        v-else
                        class="mt-2"
                        density="compact"
                        type="info"
                        variant="tonal"
                      >
                        Aucune exigence. Cliquez sur "Ajouter exigence".
                      </v-alert>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-alert
                v-if="stakeholderForm.needs.length === 0"
                class="mt-4"
                rounded="xl"
                type="info"
                variant="tonal"
              >
                <v-icon start>mdi-information</v-icon>
                Aucun besoin ajouté. Cliquez sur "Ajouter un besoin" pour commencer.
              </v-alert>
              <v-alert
                v-else-if="!canProceedToNextStep"
                class="mt-4"
                density="comfortable"
                type="warning"
                variant="tonal"
              >
                Complétez chaque besoin, chaque exigence et chaque action avant d’accéder au récapitulatif.
                Chaque action doit avoir une description, un responsable et un délai.
              </v-alert>
            </div>
          </v-card-text>

          <v-divider />

          <v-card-actions class="pa-6 bg-grey-lighten-5">
            <v-btn
              v-if="dialogStepIndex > 0"
              prepend-icon="mdi-chevron-left"
              variant="outlined"
              @click="goToPreviousStep"
            >
              Précédent
            </v-btn>
            <v-spacer />
            <v-btn size="large" variant="outlined" @click="closeDialog">
              Annuler
            </v-btn>
            <v-btn
              v-if="dialogStep !== 'review'"
              color="primary"
              :disabled="!canProceedToNextStep"
              prepend-icon="mdi-chevron-right"
              size="large"
              @click="goToNextStep"
            >
              Suivant
            </v-btn>
            <v-btn
              v-else
              color="primary"
              :disabled="!canSaveStakeholder"
              prepend-icon="mdi-content-save"
              size="large"
              @click="handleSave"
            >
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="verifyDialog" max-width="560">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 mb-2">Code du document</div>
            <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
            <v-btn color="warning" :loading="submittingForVerification" @click="submitForVerification">
              Confirmer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, nextTick, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StakeholderActionsTable from '@/modules/clienta/components/StakeholderActionsTable.vue'
  import StakeholderRequirementsView from '@/modules/clienta/components/StakeholderRequirementsView.vue'
  import StakeholdersFilters from '@/modules/clienta/pages/iso/context/components/StakeholdersFilters.vue'
  import StakeholdersGrid from '@/modules/clienta/pages/iso/context/components/StakeholdersGrid.vue'
  import StakeholdersStats from '@/modules/clienta/pages/iso/context/components/StakeholdersStats.vue'
  import StakeholdersTable from '@/modules/clienta/pages/iso/context/components/StakeholdersTable.vue'
  import ColumnVisibilitySelector, { type ColumnDefinition } from '@/modules/shared/components/ColumnVisibilitySelector.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()
  const showDialog = ref(false)
  const generationDialog = ref(false)
  const generationAction = ref<'draft' | 'preview' | 'download' | 'verify'>('draft')
  type GeneratedDocumentContext = {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }

  const generationContext = ref<GeneratedDocumentContext | null>(null)

  function resolveSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
    return Number.isFinite(Number(siteId)) ? Number(siteId) : null
  }

  const {
    exporting: exportingDocument, submittingForVerification, verificationSent,
    lastExportedDocumentId, exportedDocumentCode,
    previewDialog, previewBlobUrl, previewFilename,
    verifyDialog, ensureDraftReady, handlePreviewDraft, closePreview,
    handleDownloadDraft, openVerifyDialog, submitForVerification,
  } = useDocumentFlow(
    async () => {
      const siteId = resolveSiteId ? resolveSiteId() : (authStore.currentSite?.id ?? null)
      if (!siteId) { toast.error('Veuillez sélectionner un site.'); return null }
      if (!generationContext.value) { openGenerationDialog('draft'); return null }
      const response = await api.post('/stakeholders/generate-draft', { site_id: siteId, ...generationContext.value })
      const info = extractDocumentInfo(response.data)
      return info.id ? info as { id: number, code: string } : null
    },
    () => `Parties_Interessees_${authStore.currentSite?.name || 'Site'}_${new Date().toISOString().split('T')[0]}.pdf`,
  )
  const currentStakeholder = ref<any>(null)
  const viewMode = ref<'grid' | 'list' | 'actions'>('grid')
  const collaboratorOptions = ref<Array<{
    id: number
    label: string
    email: string
    jobTitle: string
  }>>([])
  const collaboratorsLoading = ref(false)
  const actionDescriptionRefs = ref<Record<string, any>>({})
  const needDescriptionRefs = ref<Record<number, any>>({})
  const requirementDescriptionRefs = ref<Record<string, any>>({})
  type DialogStep = 'info' | 'needs' | 'review'
  const dialogStep = ref<DialogStep>('info')
  const dialogStepOrder: DialogStep[] = ['info', 'needs', 'review']

  const filters = ref({
    search: '',
    type: null,
    influence: null,
  })

  const stakeholderForm = ref({
    name: '',
    type: 'client',
    influence: 'medium',
    needs: [] as Array<{
      description: string
      priority: string
      requirements: Array<{
        description: string
        type: string
        actions: Array<{
          description: string
          responsible_user_id: number | null
          responsible_name: string
          deadline: string
        }>
      }>
    }>,
  })

  const priorityLevels = [
    { title: 'Faible', value: 'low' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Élevée', value: 'high' },
  ]

  const requirementTypes = [
    { title: 'Légale', value: 'legal' },
    { title: 'Réglementaire', value: 'regulatory' },
    { title: 'Contractuelle', value: 'contractual' },
    { title: 'Autre', value: 'other' },
  ]

  const stakeholders = ref<any[]>([])

  function applyFilters (nextFilters: { search: string, type: string | null, influence: string | null }) {
    filters.value = { ...nextFilters }
  }

  function normalizeRequirement (raw: any) {
    return {
      description: String(raw?.description || ''),
      type: String(raw?.type || 'legal'),
      actions: Array.isArray(raw?.actions)
        ? raw.actions.map((action: any) => ({
          description: String(action?.description || ''),
          responsible_user_id: Number(action?.responsible_user_id || 0) || null,
          responsible_name: String(action?.responsible_name || action?.responsible || ''),
          deadline: String(action?.deadline || ''),
        }))
        : [],
    }
  }

  function normalizeNeedPriority (value: unknown): 'low' | 'medium' | 'high' {
    const normalized = String(value || '').toLowerCase().trim()
    if (normalized === 'low') return 'low'
    if (normalized === 'high' || normalized === 'critical') return 'high'
    return 'medium'
  }

  function normalizeNeed (raw: any) {
    return {
      description: String(raw?.description || ''),
      priority: normalizeNeedPriority(raw?.priority),
      requirements: Array.isArray(raw?.requirements)
        ? raw.requirements.map(normalizeRequirement)
        : [],
    }
  }

  function normalizeStakeholder (item: any) {
    const source = item?.attributes && typeof item.attributes === 'object'
      ? item.attributes
      : item

    return {
      id: item?.id ?? source?.id,
      name: String(source?.name || ''),
      type: String(source?.type || 'other'),
      relevance_degree: String(source?.relevance_degree || 'medium'),
      responsible_name: String(source?.responsible_name || ''),
      deadline: String(source?.deadline || ''),
      needs_expectations: String(source?.needs_expectations || ''),
      needs: Array.isArray(source?.needs)
        ? source.needs.map(normalizeNeed)
        : [],
    }
  }

  const allStakeholders = computed(() => {
    let result = [...stakeholders.value]
    const search = String(filters.value.search ?? '').trim().toLowerCase()

    if (search) {
      result = result.filter(s =>
        String(s?.name || '').toLowerCase().includes(search)
        || String(s?.needs_expectations || '').toLowerCase().includes(search),
      )
    }
    if (filters.value.type) result = result.filter(s => s.type === filters.value.type)
    if (filters.value.influence) result = result.filter(s => s.relevance_degree === filters.value.influence)
    return result
  })

  const allStakeholderColumns: ColumnDefinition[] = [
    { key: 'name', title: 'Nom', mandatory: true },
    { key: 'type', title: 'Type', defaultVisible: true },
    { key: 'relevance_degree', title: 'Degré', defaultVisible: true },
    { key: 'actions_summary', title: 'Actions & Délais', defaultVisible: true },
    { key: 'actions', title: 'Actions', mandatory: true },
  ]
  const visibleColumnKeys = ref<string[]>(allStakeholderColumns.map(c => c.key))

  const listHeaders = computed(() => {
    return allStakeholderColumns
      .filter(c => visibleColumnKeys.value.includes(c.key))
      .map(c => ({
        title: c.title,
        key: c.key,
        align: c.key === 'actions' ? ('end' as const) : undefined,
        sortable: !['actions_summary', 'actions'].includes(c.key),
      }))
  })

  const listItemsPerPage = computed(() => Math.max(allStakeholders.value.length, 1))

  const hasActiveFilters = computed(() => {
    return Boolean(String(filters.value.search ?? '').trim() || filters.value.type || filters.value.influence)
  })


  function resolveCollaboratorLabel (userLike: any): string {
    const firstName = String(userLike?.first_name || '').trim()
    const lastName = String(userLike?.last_name || '').trim()
    const fullName = [lastName, firstName].filter(Boolean).join(' ').trim()
    return fullName || String(userLike?.full_name || userLike?.name || userLike?.email || 'Collaborateur')
  }

  async function loadCollaboratorOptions () {
    const siteId = resolveSiteId()
    if (!siteId) {
      collaboratorOptions.value = []
      return
    }

    collaboratorsLoading.value = true
    try {
      const { data } = await api.get('/users/job-description-collaborators', {
        params: { site_id: siteId },
      })
      const rawUsers = Array.isArray(data?.data) ? data.data : []
      collaboratorOptions.value = rawUsers.map((user: any) => ({
        id: Number(user.id),
        label: resolveCollaboratorLabel(user),
        email: String(user.email || ''),
        jobTitle: String(user.job_title || ''),
      }))
    } catch (error) {
      console.error('Erreur chargement collaborateurs:', error)
      collaboratorOptions.value = []
      toast.error('Impossible de charger les collaborateurs pour l’attribution des actions.')
    } finally {
      collaboratorsLoading.value = false
    }
  }

  function resolveActionResponsibleName (action: any): string {
    const userId = Number(action?.responsible_user_id || 0)
    if (userId > 0) {
      const match = collaboratorOptions.value.find(option => option.id === userId)
      if (match) {
        return match.label
      }
    }

    return String(action?.responsible_name || action?.responsible || '').trim()
  }

  function isStakeholderActionComplete (action: any): boolean {
    return Boolean(
      String(action?.description || '').trim()
      && Number(action?.responsible_user_id || 0) > 0
        && String(action?.deadline || '').trim(),
    )
  }

  function isStakeholderRequirementComplete (requirement: any): boolean {
    if (!String(requirement?.description || '').trim()) {
      return false
    }

    const actions = Array.isArray(requirement?.actions) ? requirement.actions : []
    if (actions.length === 0) {
      return false
    }
    return actions.every(isStakeholderActionComplete)
  }

  function isStakeholderNeedComplete (need: any): boolean {
    if (!String(need?.description || '').trim()) {
      return false
    }

    const requirements = Array.isArray(need?.requirements) ? need.requirements : []
    if (requirements.length === 0) {
      return false
    }
    return requirements.every(isStakeholderRequirementComplete)
  }

  function isStakeholderActionEmpty (action: any): boolean {
    return !String(action?.description || '').trim()
      && Number(action?.responsible_user_id || 0) <= 0
      && !String(action?.deadline || '').trim()
  }

  function getSanitizedNeeds (needs: Array<any>) {
    return (Array.isArray(needs) ? needs : [])
      .map(need => ({
        description: String(need?.description || '').trim(),
        priority: normalizeNeedPriority(need?.priority),
        requirements: (Array.isArray(need?.requirements) ? need.requirements : [])
          .map(requirement => ({
            description: String(requirement?.description || '').trim(),
            type: String(requirement?.type || 'legal'),
            actions: (Array.isArray(requirement?.actions) ? requirement.actions : [])
              .map(action => ({
                description: String(action?.description || '').trim(),
                responsible_user_id: Number(action?.responsible_user_id || 0) || null,
                responsible_name: String(action?.responsible_name || ''),
                deadline: String(action?.deadline || '').trim(),
              }))
              .filter(action => !isStakeholderActionEmpty(action)),
          }))
          .filter(requirement => Boolean(requirement.description || requirement.actions.length > 0)),
      }))
      .filter(need => Boolean(need.description || need.requirements.length > 0))
  }

  function sanitizeStakeholderNeedsInPlace () {
    stakeholderForm.value.needs = getSanitizedNeeds(stakeholderForm.value.needs)
  }

  function actionDescriptionRefKey (needIndex: number, reqIndex: number, actIndex: number): string {
    return `${needIndex}-${reqIndex}-${actIndex}`
  }

  function requirementDescriptionRefKey (needIndex: number, reqIndex: number): string {
    return `${needIndex}-${reqIndex}`
  }

  function setNeedDescriptionRef (needIndex: number, element: any) {
    if (element) {
      needDescriptionRefs.value[needIndex] = element
      return
    }
    delete needDescriptionRefs.value[needIndex]
  }

  function setRequirementDescriptionRef (needIndex: number, reqIndex: number, element: any) {
    const key = requirementDescriptionRefKey(needIndex, reqIndex)
    if (element) {
      requirementDescriptionRefs.value[key] = element
      return
    }
    delete requirementDescriptionRefs.value[key]
  }

  function setActionDescriptionRef (needIndex: number, reqIndex: number, actIndex: number, element: any) {
    const key = actionDescriptionRefKey(needIndex, reqIndex, actIndex)
    if (element) {
      actionDescriptionRefs.value[key] = element
      return
    }
    delete actionDescriptionRefs.value[key]
  }

  async function focusNewActionDescription (needIndex: number, reqIndex: number) {
    await nextTick()
    const key = actionDescriptionRefKey(needIndex, reqIndex, 0)
    const target = actionDescriptionRefs.value[key]

    if (typeof target?.focus === 'function') {
      target.focus()
      return
    }

    const input = target?.$el?.querySelector?.('input, textarea') as HTMLInputElement | HTMLTextAreaElement | null
    input?.focus()
  }

  async function focusNewNeedDescription () {
    await nextTick()
    const target = needDescriptionRefs.value[0]

    if (typeof target?.focus === 'function') {
      target.focus()
      return
    }

    const input = target?.$el?.querySelector?.('input, textarea') as HTMLInputElement | HTMLTextAreaElement | null
    input?.focus()
  }

  async function focusNewRequirementDescription (needIndex: number) {
    await nextTick()
    const key = requirementDescriptionRefKey(needIndex, 0)
    const target = requirementDescriptionRefs.value[key]

    if (typeof target?.focus === 'function') {
      target.focus()
      return
    }

    const input = target?.$el?.querySelector?.('input, textarea') as HTMLInputElement | HTMLTextAreaElement | null
    input?.focus()
  }

  const dialogStepIndex = computed(() => dialogStepOrder.indexOf(dialogStep.value))
  const canProceedToNextStep = computed(() => {
    if (dialogStep.value === 'info') {
      return Boolean(String(stakeholderForm.value.name || '').trim() && stakeholderForm.value.type && stakeholderForm.value.influence)
    }

    if (dialogStep.value === 'needs') {
      const sanitizedNeeds = getSanitizedNeeds(stakeholderForm.value.needs)
      return sanitizedNeeds.length > 0
        && sanitizedNeeds.every(isStakeholderNeedComplete)
    }

    return false
  })

  const canAccessReviewStep = computed(() => {
    const hasMainInfo = Boolean(String(stakeholderForm.value.name || '').trim() && stakeholderForm.value.type && stakeholderForm.value.influence)
    const sanitizedNeeds = getSanitizedNeeds(stakeholderForm.value.needs)
    return hasMainInfo && sanitizedNeeds.length > 0 && sanitizedNeeds.every(isStakeholderNeedComplete)
  })

  const canSaveStakeholder = computed(() => {
    return canAccessReviewStep.value
  })

  const stakeholderTypes = [
    { title: 'Client', value: 'client' },
    { title: 'Fournisseur', value: 'supplier' },
    { title: 'Partenaire', value: 'partner' },
    { title: 'Régulateur', value: 'regulator' },
    { title: 'Employé', value: 'employee' },
    { title: 'Actionnaire', value: 'shareholder' },
    { title: 'Autre', value: 'other' },
  ]

  const influenceLevels = [
    { title: 'Faible', value: 'low' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Élevée', value: 'high' },
  ]

  const stakeholderTypeOptions = computed(() => stakeholderTypes.map(type => ({
    label: type.title,
    value: type.value,
  })))

  const influenceOptions = computed(() => influenceLevels.map(level => ({
    label: level.title,
    value: level.value,
  })))

  const stats = computed(() => {
    const total = stakeholders.value.length
    const low = stakeholders.value.filter(s => s.relevance_degree === 'low').length
    const medium = stakeholders.value.filter(s => s.relevance_degree === 'medium').length
    const high = stakeholders.value.filter(s => s.relevance_degree === 'high').length

    return { total, low, medium, high }
  })

  function getTypeColor (type: string) {
    const colors: Record<string, string> = {
      client: '#5b8dd9', supplier: '#22c55e', partner: '#3b82f6',
      regulator: '#f59e0b', employee: '#a855f7', shareholder: '#f59e0b', other: '#64748b',
    }
    return colors[type] || '#64748b'
  }

  function getTypeIcon (type: string) {
    const icons: Record<string, string> = {
      client: 'mdi-account-star', supplier: 'mdi-truck', partner: 'mdi-handshake',
      regulator: 'mdi-gavel', employee: 'mdi-account-tie', shareholder: 'mdi-chart-line', other: 'mdi-dots-horizontal',
    }
    return icons[type] || 'mdi-account'
  }

  function getInfluenceColor (influence: string) {
    return { low: 'success', medium: 'warning', high: 'error' }[influence] || 'grey'
  }

  function truncate (text: string, length: number) {
    if (!text) return ''
    return text.length > length ? text.slice(0, Math.max(0, length)) + '...' : text
  }

  function addNeed () {
    stakeholderForm.value.needs.unshift({
      description: '',
      priority: 'medium',
      requirements: [],
    })
    void focusNewNeedDescription()
  }

  function addRequirement (needIndex: number) {
    const need = stakeholderForm.value.needs[needIndex]
    if (!need) return
    need.requirements.unshift({
      description: '',
      type: 'legal',
      actions: [],
    })
    void focusNewRequirementDescription(needIndex)
  }

  function addAction (needIndex: number, reqIndex: number) {
    const need = stakeholderForm.value.needs[needIndex]
    const requirement = need?.requirements[reqIndex]
    if (!requirement) return
    requirement.actions.unshift({
      description: '',
      responsible_user_id: null,
      responsible_name: '',
      deadline: '',
    })
    void focusNewActionDescription(needIndex, reqIndex)
  }

  function removeRequirement (needIndex: number, reqIndex: number) {
    const need = stakeholderForm.value.needs[needIndex]
    if (!need) return
    need.requirements.splice(reqIndex, 1)
  }

  function removeAction (needIndex: number, reqIndex: number, actIndex: number) {
    const need = stakeholderForm.value.needs[needIndex]
    const requirement = need?.requirements[reqIndex]
    if (!requirement) return
    requirement.actions.splice(actIndex, 1)
  }

  function removeNeed (index: number) {
    stakeholderForm.value.needs.splice(index, 1)
  }

  function handleAdd () {
    currentStakeholder.value = null
    dialogStep.value = 'info'
    stakeholderForm.value = {
      name: '', type: 'client', influence: 'medium',
      needs: [{ description: '', priority: 'medium', requirements: [] }],
    }
    showDialog.value = true
  }

  function resetFilters () {
    filters.value = {
      search: '',
      type: null,
      influence: null,
    }
  }

  function handleEdit (item: any) {
    const normalized = normalizeStakeholder(item)

    currentStakeholder.value = item
    dialogStep.value = 'info'
    stakeholderForm.value = {
      name: normalized.name || '',
      type: normalized.type || 'client',
      influence: normalized.relevance_degree || 'medium',
      needs: normalized.needs.length > 0 ? normalized.needs : [{ description: '', priority: 'medium', requirements: [] }],
    }
    showDialog.value = true
  }

  async function handleSave () {
    const siteId = resolveSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }
    try {
      sanitizeStakeholderNeedsInPlace()
      if (!canAccessReviewStep.value) {
        toast.warning('Complétez les besoins, exigences et actions avant l’enregistrement.')
        return
      }

      const normalizedNeeds = stakeholderForm.value.needs.map(need => ({
        ...need,
        priority: normalizeNeedPriority(need.priority),
        requirements: need.requirements.map(requirement => ({
          ...requirement,
          actions: requirement.actions.map(action => ({
            description: String(action.description || '').trim(),
            responsible_user_id: Number(action.responsible_user_id || 0) || null,
            responsible_name: resolveActionResponsibleName(action),
            deadline: String(action.deadline || ''),
          })),
        })),
      }))

      const payload = {
        site_id: siteId,
        name: stakeholderForm.value.name,
        type: stakeholderForm.value.type,
        relevance_degree: stakeholderForm.value.influence,
        needs: normalizedNeeds,
      }

      if (currentStakeholder.value?.id) {
        await api.put(`/stakeholders/${currentStakeholder.value.id}`, payload)
        toast.success('partie intéressée modifiée.')
      } else {
        await api.post('/stakeholders', payload)
        toast.success('partie intéressée créée.')
      }
      closeDialog()
      await loadStakeholders()
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l\'enregistrement.')
    }
  }

  function closeDialog () {
    showDialog.value = false
    dialogStep.value = 'info'
  }

  function goToNextStep () {
    if (!canProceedToNextStep.value) {
      return
    }

    if (dialogStep.value === 'needs') {
      sanitizeStakeholderNeedsInPlace()
      if (!canAccessReviewStep.value) {
        return
      }
    }

    const nextIndex = dialogStepIndex.value + 1
    if (nextIndex < dialogStepOrder.length) {
      dialogStep.value = dialogStepOrder[nextIndex]
    }
  }

  function goToPreviousStep () {
    const previousIndex = dialogStepIndex.value - 1
    if (previousIndex >= 0) {
      dialogStep.value = dialogStepOrder[previousIndex]
    }
  }

  function openGenerationDialog (action: 'draft' | 'preview' | 'download' | 'verify' = 'draft') {
    generationAction.value = action
    generationDialog.value = true
  }

  async function confirmGenerationConfig (context: GeneratedDocumentContext) {
    generationContext.value = context
    generationDialog.value = false
    if (generationAction.value === 'draft') {
      await generateDraft()
    } else if (generationAction.value === 'preview') {
      await handlePreviewDraft()
    } else if (generationAction.value === 'download') {
      await handleDownloadDraft()
    } else if (generationAction.value === 'verify') {
      const ready = await generateDraft()
      if (ready) {
        verifyDialog.value = true
      }
    }
  }

  function extractDocumentInfo (payload: any): { id: number | null, code: string } {
    const data = payload?.data ?? payload
    const attributes = data?.attributes ?? data
    const id = Number(data?.id || attributes?.id || 0) || null
    const code = String(attributes?.code || '')
    return { id, code }
  }

  async function loadStakeholders () {
    const siteId = resolveSiteId()
    if (!siteId) return
    try {
      const { data } = await api.get('/stakeholders', {
        params: { site_id: siteId },
      })
      stakeholders.value = (data?.data || []).map((item: any) => normalizeStakeholder(item))
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  }

  onMounted(async () => {
    await loadCollaboratorOptions()
    await loadStakeholders()
  })

  watch(
    () => authStore.currentSiteId,
    async () => {
      await loadCollaboratorOptions()
      await loadStakeholders()
    },
  )

  function typeLabel (type: string) {
    return stakeholderTypes.find(t => t.value === type)?.title || type
  }

  function influenceLabel (level: string) {
    return influenceLevels.find(l => l.value === level)?.title || level
  }

  function getStakeholderActions (stakeholder: any) {
    const actions: any[] = []
    if (stakeholder.needs && Array.isArray(stakeholder.needs)) {
      for (const need of stakeholder.needs) {
        if (need.requirements && Array.isArray(need.requirements)) {
          for (const req of need.requirements) {
            if (req.actions && Array.isArray(req.actions)) {
              for (const action of req.actions) {
                actions.push({
                  id: `${stakeholder.id}-${actions.length}`,
                  description: action.description,
                  responsible: resolveActionResponsibleName(action),
                  deadline: action.deadline,
                })
              }
            }
          }
        }
      }
    }
    return actions
  }

  function formatDate (dateString: string) {
    if (!dateString) return 'Non défini'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
    })
  }

  function getDeadlineColor (deadline: string) {
    if (!deadline) return '#64748b'

    const today = new Date()
    const deadlineDate = new Date(deadline)
    const diffDays = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))

    if (diffDays < 0) return '#ef4444'
    if (diffDays <= 7) return '#f59e0b'
    if (diffDays <= 30) return '#3b82f6'
    return '#22c55e'
  }
</script>

<style scoped>
.dialog-card {
  overflow: hidden;
}

.dialog-header {
  position: relative;
}

.dialog-header::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #22c55e 0%, #3b82f6 50%, #a855f7 100%);
}

.need-card {
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.need-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

</style>
