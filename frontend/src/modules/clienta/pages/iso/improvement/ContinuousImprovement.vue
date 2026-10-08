<template>
  <ClientALayout current-page="iso-improvement">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-lightbulb-on-outline"
        subtitle="Chaque collaborateur propose des améliorations du système et des conduites du site sélectionné"
        title="Amélioration continue - Suggestions"
      >
        <template #actions>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            size="large"
            @click="openCreateDialog"
          >
            Proposer une suggestion
          </v-btn>
        </template>
      </PageHeader>

      <v-alert class="mb-4" density="comfortable" type="info" variant="tonal">
        Proposer des suggestions d'amélioration par rapport au système (chaque
        collaborateur), selon le site sélectionné.
      </v-alert>

      <SuggestionStats
        :adopted="adoptedCount"
        :implemented="implementedCount"
        :pending="pendingCount"
        :total="suggestions.length"
        @filter="filters.status = $event"
      />

      <SuggestionsFilters
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        :impact-options="impactFilterOptions"
        :process-options="processFilterOptions"
        :status-options="statusFilterOptions"
        @create="openCreateDialog"
        @reset="resetFilters"
      />

      <v-row>
        <v-col cols="12" lg="8">
          <SuggestionsTable
            :count="filteredSuggestions.length"
            :empty-state-message="emptyStateMessage"
            :headers="headers"
            :impact-color="impactColor"
            :items="filteredSuggestions"
            :status-color="statusColor"
            :status-label="statusLabel"
            @delete="deleteSuggestion"
            @edit="openEditDialog"
            @select="selectSuggestion"
          />
        </v-col>

        <v-col cols="12" lg="4">
          <SuggestionDetailsCard
            :status-label="statusLabel"
            :suggestion="selectedSuggestion"
            @delete="deleteSuggestion"
            @edit="openEditDialog"
          />
        </v-col>
      </v-row>

      <v-dialog v-model="createDialog" max-width="900">
        <v-card rounded="lg">
          <v-card-text>
            <v-stepper v-model="stepper" alt-labels>
              <v-stepper-header>
                <v-stepper-item
                  :complete="stepper > 1"
                  icon="mdi-lightbulb-outline"
                  title="Idée"
                  :value="1"
                />
                <v-stepper-item
                  :complete="stepper > 2"
                  icon="mdi-account"
                  title="Auteur"
                  :value="2"
                />
                <v-stepper-item
                  :complete="stepper > 3"
                  icon="mdi-chart-box-outline"
                  title="Impact"
                  :value="3"
                />
                <v-stepper-item
                  icon="mdi-file-check-outline"
                  title="Récapitulatif"
                  :value="4"
                />
              </v-stepper-header>

              <v-stepper-window>
                <v-stepper-window-item :value="1">
                  <v-row>
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
                        v-model="draft.title"
                        label="Titre de la suggestion"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.description"
                        label="Description de la suggestion"
                        rows="4"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="2">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.proposer_id"
                        item-title="title"
                        item-value="value"
                        :items="collaboratorSelectItems"
                        label="Collaborateur auteur"
                        :loading="loadingReferences"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="draft.date"
                        label="Date (JJ/MM/AAAA)"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="3">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.impact"
                        :items="['Faible', 'Moyen', 'Élevé']"
                        label="Impact attendu"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.status"
                        item-title="title"
                        item-value="value"
                        :items="[
                          { title: 'À évaluer', value: 'pending' },
                          { title: 'En mise en œuvre', value: 'in_progress' },
                          { title: 'Adoptée', value: 'adopted' },
                          { title: 'Non retenue', value: 'rejected' },
                        ]"
                        label="Statut initial"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.follow_up"
                        label="Commentaire de suivi"
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
                        Récapitulatif avant soumission
                      </div>
                      <div class="detail-line">
                        <strong>Processus:</strong> {{ selectedProcessTitle }}
                      </div>
                      <div class="detail-line">
                        <strong>Titre:</strong> {{ draft.title || "—" }}
                      </div>
                      <div class="detail-block">
                        <strong>Description:</strong><br>{{
                          draft.description || "—"
                        }}
                      </div>
                      <div class="detail-line">
                        <strong>Collaborateur:</strong>
                        {{ selectedProposerName }}
                      </div>
                      <div class="detail-line">
                        <strong>Date:</strong> {{ draft.date || "—" }}
                      </div>
                      <div class="detail-line">
                        <strong>Impact:</strong> {{ draft.impact }}
                      </div>
                      <div class="detail-line">
                        <strong>Statut:</strong> {{ statusLabel(draft.status) }}
                      </div>
                      <div class="detail-block">
                        <strong>Suivi:</strong><br>{{
                          draft.follow_up || "—"
                        }}
                      </div>
                    </v-card-text>
                  </v-card>
                </v-stepper-window-item>
              </v-stepper-window>
            </v-stepper>
          </v-card-text>

          <v-card-actions>
            <v-btn
              :disabled="stepper === 1"
              variant="text"
              @click="stepper--"
            >Précédent</v-btn>
            <v-spacer />
            <v-btn variant="text" @click="closeCreateDialog">Annuler</v-btn>
            <v-btn
              v-if="stepper < 4"
              color="primary"
              @click="stepper++"
            >Suivant</v-btn>
            <v-btn
              v-else
              color="primary"
              @click="createSuggestion"
            >Soumettre</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="editDialog" max-width="780">
        <v-card rounded="lg">
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Mettre à jour la suggestion</span>
            <v-chip color="primary" variant="tonal">{{
              editDraft.reference || "Suggestion"
            }}</v-chip>
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editDraft.process_id"
                  item-title="title"
                  item-value="value"
                  :items="processSelectItems"
                  label="Processus concerné"
                  :loading="loadingReferences"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editDraft.proposer_id"
                  item-title="title"
                  item-value="value"
                  :items="collaboratorSelectItems"
                  label="Collaborateur auteur"
                  :loading="loadingReferences"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="editDraft.title"
                  label="Titre"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editDraft.description"
                  label="Description"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editDraft.date"
                  label="Date (JJ/MM/AAAA)"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editDraft.impact"
                  :items="['Faible', 'Moyen', 'Élevé']"
                  label="Impact"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="editDraft.status"
                  item-title="title"
                  item-value="value"
                  :items="[
                    { title: 'À évaluer', value: 'pending' },
                    { title: 'En mise en œuvre', value: 'in_progress' },
                    { title: 'Adoptée', value: 'adopted' },
                    { title: 'Non retenue', value: 'rejected' },
                  ]"
                  label="Statut"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editDraft.follow_up"
                  label="Commentaire de suivi"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="closeEditDialog">Annuler</v-btn>
            <v-btn
              color="primary"
              @click="saveEditedSuggestion"
            >Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import SuggestionDetailsCard from '@/modules/clienta/pages/iso/improvement/components/SuggestionDetailsCard.vue'
  import SuggestionsFilters from '@/modules/clienta/pages/iso/improvement/components/SuggestionsFilters.vue'
  import SuggestionsTable from '@/modules/clienta/pages/iso/improvement/components/SuggestionsTable.vue'
  import SuggestionStats from '@/modules/clienta/pages/iso/improvement/components/SuggestionStats.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { improvementSuggestionService } from '@/services/improvementSuggestionService'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  type SuggestionStatus = 'pending' | 'in_progress' | 'adopted' | 'rejected'

  interface SuggestionItem {
    id: number
    reference: string
    process: string
    title: string
    description: string
    proposer: string
    process_id: number | null
    proposer_id: number | null
    impact: 'Faible' | 'Moyen' | 'Élevé'
    status: SuggestionStatus
    created_at: string
    proposed_at_iso?: string
    follow_up?: string
  }

  interface SelectItem {
    title: string
    value: number
  }

  const authStore = useAuthStore()
  const toast = useToast()

  const headers = [
    { title: 'Référence', key: 'reference', sortable: true },
    { title: 'Titre', key: 'title', sortable: true },
    { title: 'Processus', key: 'process', sortable: true },
    { title: 'Proposé par', key: 'proposer', sortable: true },
    { title: 'Impact', key: 'impact', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: '', key: 'actions', sortable: false },
  ]

  const processSelectItems = ref<SelectItem[]>([])
  const collaboratorSelectItems = ref<SelectItem[]>([])
  const processOptions = ref<string[]>([])
  const loadingReferences = ref(false)

  const suggestions = ref<SuggestionItem[]>([])

  const filters = ref({
    search: '',
    status: 'Tous les statuts',
    process: 'Tous les processus',
    impact: 'Tous les impacts',
  })

  const statusFilterOptions = [
    'Tous les statuts',
    'À évaluer',
    'En mise en œuvre',
    'Adoptée',
    'Non retenue',
  ]
  const impactFilterOptions = ['Tous les impacts', 'Faible', 'Moyen', 'Élevé']

  const selectedId = ref<number | null>(null)

  const createDialog = ref(false)
  const editDialog = ref(false)
  const stepper = ref(1)
  const draft = ref({
    process_id: null as number | null,
    title: '',
    description: '',
    proposer_id: null as number | null,
    date: formatToday(),
    impact: 'Moyen' as 'Faible' | 'Moyen' | 'Élevé',
    status: 'pending' as SuggestionStatus,
    follow_up: '',
  })
  const editDraft = ref({
    id: null as number | null,
    reference: '',
    process_id: null as number | null,
    proposer_id: null as number | null,
    title: '',
    description: '',
    date: formatToday(),
    impact: 'Moyen' as 'Faible' | 'Moyen' | 'Élevé',
    status: 'pending' as SuggestionStatus,
    follow_up: '',
  })

  const filteredSuggestions = computed(() => {
    return suggestions.value.filter(item => {
      const q = filters.value.search.trim().toLowerCase()
      const matchSearch
        = q.length === 0
          || item.reference.toLowerCase().includes(q)
          || item.title.toLowerCase().includes(q)
          || item.description.toLowerCase().includes(q)
          || item.proposer.toLowerCase().includes(q)

      const matchStatus
        = filters.value.status === 'Tous les statuts'
          || statusLabel(item.status) === filters.value.status
      const matchProcess
        = filters.value.process === 'Tous les processus'
          || item.process === filters.value.process
      const matchImpact
        = filters.value.impact === 'Tous les impacts'
          || item.impact === filters.value.impact

      return matchSearch && matchStatus && matchProcess && matchImpact
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(
      filters.value.search.trim()
      || filters.value.status !== 'Tous les statuts'
        || filters.value.process !== 'Tous les processus'
      || filters.value.impact !== 'Tous les impacts',
    )
  })

  const emptyStateMessage = computed(() => {
    if (suggestions.value.length === 0) {
      return 'Aucune suggestion d\'amélioration disponible pour ce site.'
    }

    return 'Aucune suggestion ne correspond aux filtres sélectionnés.'
  })

  const selectedSuggestion = computed(() => {
    const pool
      = filteredSuggestions.value.length > 0
        ? filteredSuggestions.value
        : suggestions.value
    return pool.find(item => item.id === selectedId.value) || pool[0] || null
  })

  const pendingCount = computed(
    () => suggestions.value.filter(item => item.status === 'pending').length,
  )
  const implementedCount = computed(
    () =>
      suggestions.value.filter(item => item.status === 'in_progress').length,
  )
  const adoptedCount = computed(
    () => suggestions.value.filter(item => item.status === 'adopted').length,
  )

  const processFilterOptions = computed(() => [
    'Tous les processus',
    ...processOptions.value,
  ])

  const currentSiteId = computed<number | null>(() => {
    const direct = authStore.currentSiteId
    if (typeof direct === 'number' && Number.isFinite(direct)) return direct

    const stored = Number(localStorage.getItem('current_site_id'))
    return Number.isFinite(stored) && stored > 0 ? stored : null
  })

  const currentSiteLabel = computed(() =>
    currentSiteId.value ? `#${currentSiteId.value}` : 'Non défini',
  )

  const selectedProcessTitle = computed(() => {
    const found = processSelectItems.value.find(
      item => item.value === draft.value.process_id,
    )
    return found?.title || '—'
  })

  const selectedProposerName = computed(() => {
    const found = collaboratorSelectItems.value.find(
      item => item.value === draft.value.proposer_id,
    )
    return found?.title || '—'
  })

  onMounted(async () => {
    await loadDynamicReferences()
    await loadSuggestions()
  })

  watch(
    () => authStore.currentSiteId,
    async () => {
      await loadDynamicReferences()
      await loadSuggestions()
    },
  )

  async function loadSuggestions (): Promise<void> {
    if (!currentSiteId.value) {
      suggestions.value = []
      selectedId.value = null
      return
    }

    try {
      const rows = await improvementSuggestionService.list({
        site_id: currentSiteId.value,
        per_page: 200,
      })
      suggestions.value = rows.map((row: any) => ({
        id: Number(row.id),
        reference: row.ref || `SUG-${row.id}`,
        process:
          row.process?.title || row.process?.name || 'Processus non défini',
        process_id: row.process_id ? Number(row.process_id) : null,
        title: row.title || '',
        description: row.description || '',
        proposer: row.proposer?.name || 'Collaborateur non défini',
        proposer_id: row.proposer_id ? Number(row.proposer_id) : null,
        impact: mapImpactToLabel(row.impact),
        status: row.status,
        created_at: row.proposed_at
          ? formatIsoDate(row.proposed_at)
          : formatIsoDate(row.created_at),
        proposed_at_iso: row.proposed_at || row.created_at,
        follow_up: row.follow_up || '',
      }))

      selectedId.value = suggestions.value[0]?.id ?? null
    } catch (error) {
      console.error('[ContinuousImprovement] load suggestions failed', error)
      toast.error(
        getErrorMessage(error, 'Impossible de charger les suggestions.'),
      )
      suggestions.value = []
      selectedId.value = null
    }
  }

  async function loadDynamicReferences (): Promise<void> {
    if (!currentSiteId.value) {
      processSelectItems.value = []
      collaboratorSelectItems.value = []
      processOptions.value = []
      return
    }

    loadingReferences.value = true
    try {
      await Promise.all([
        loadProcesses(currentSiteId.value),
        loadCollaborators(currentSiteId.value),
      ])
    } catch (error) {
      console.error('[ContinuousImprovement] load references failed', error)
      toast.error(
        getErrorMessage(
          error,
          'Impossible de charger les processus/collaborateurs du site.',
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
      .map((row: any) => ({
        value: Number(row?.id || 0),
        title: String(row?.title || row?.name || row?.code || ''),
      }))
      .filter((item: SelectItem) => item.value > 0 && item.title.length > 0)

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

    if (!draft.value.proposer_id && collaboratorSelectItems.value.length > 0) {
      draft.value.proposer_id = collaboratorSelectItems.value[0]?.value || null
    }
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

    return { value: id, title: fullName }
  }

  function selectSuggestion (id: number): void {
    selectedId.value = id
  }

  function openCreateDialog (): void {
    stepper.value = 1
    createDialog.value = true
  }

  function resetFilters (): void {
    filters.value = {
      search: '',
      status: 'Tous les statuts',
      process: 'Tous les processus',
      impact: 'Tous les impacts',
    }
  }

  function closeCreateDialog (): void {
    createDialog.value = false
    stepper.value = 1
  }

  function openEditDialog (id: number): void {
    const item = suggestions.value.find(s => s.id === id)
    if (!item) return
    editDraft.value = {
      id: item.id,
      reference: item.reference,
      process_id: item.process_id ?? null,
      proposer_id: item.proposer_id ?? null,
      title: item.title,
      description: item.description,
      date: item.proposed_at_iso
        ? formatIsoDate(item.proposed_at_iso)
        : formatToday(),
      impact: item.impact,
      status: item.status,
      follow_up: item.follow_up || '',
    }
    editDialog.value = true
  }

  function closeEditDialog (): void {
    editDialog.value = false
    editDraft.value = {
      id: null,
      reference: '',
      process_id: null,
      proposer_id: null,
      title: '',
      description: '',
      date: formatToday(),
      impact: 'Moyen',
      status: 'pending',
      follow_up: '',
    }
  }

  async function createSuggestion (): Promise<void> {
    if (!currentSiteId.value || !draft.value.title || !draft.value.description) {
      toast.error('Site, titre et description sont requis.')
      return
    }

    try {
      const payload = {
        site_id: currentSiteId.value,
        process_id: draft.value.process_id || null,
        proposer_id: draft.value.proposer_id || null,
        title: draft.value.title,
        description: draft.value.description,
        impact: mapImpactToApi(draft.value.impact),
        status: draft.value.status,
        follow_up: draft.value.follow_up || null,
        proposed_at: parseFrDateToIso(draft.value.date),
      }

      const created = await improvementSuggestionService.create(payload as any)

      createDialog.value = false
      stepper.value = 1
      resetDraft()

      await loadSuggestions()
      if (created?.id) {
        selectedId.value = Number(created.id)
      }
      toast.success('Suggestion enregistrée.')
    } catch (error) {
      console.error('[ContinuousImprovement] create suggestion failed', error)
      toast.error(
        getErrorMessage(error, 'Impossible d’enregistrer la suggestion.'),
      )
    }
  }

  async function saveEditedSuggestion (): Promise<void> {
    if (
      !editDraft.value.id
      || !editDraft.value.title
      || !editDraft.value.description
    ) {
      toast.error('Titre et description sont requis.')
      return
    }

    try {
      await improvementSuggestionService.update(editDraft.value.id, {
        process_id: editDraft.value.process_id || null,
        proposer_id: editDraft.value.proposer_id || null,
        title: editDraft.value.title,
        description: editDraft.value.description,
        impact: mapImpactToApi(editDraft.value.impact),
        status: editDraft.value.status,
        follow_up: editDraft.value.follow_up || null,
        proposed_at: parseFrDateToIso(editDraft.value.date),
      } as any)

      editDialog.value = false
      await loadSuggestions()
      if (editDraft.value.id) {
        selectedId.value = editDraft.value.id
      }
      closeEditDialog()
      toast.success('Suggestion mise à jour.')
    } catch (error) {
      console.error('[ContinuousImprovement] update suggestion failed', error)
      toast.error(
        getErrorMessage(error, 'Impossible de mettre à jour la suggestion.'),
      )
    }
  }

  async function deleteSuggestion (id: number): Promise<void> {
    const item = suggestions.value.find(s => s.id === id)
    if (!item) return
    if (!confirm(`Supprimer la suggestion ${item.reference} ?`)) return

    try {
      await improvementSuggestionService.remove(id)
      if (selectedId.value === id) {
        selectedId.value = null
      }
      await loadSuggestions()
      toast.success('Suggestion supprimée.')
    } catch (error) {
      console.error('[ContinuousImprovement] delete suggestion failed', error)
      toast.error(
        getErrorMessage(error, 'Impossible de supprimer la suggestion.'),
      )
    }
  }

  function resetDraft (): void {
    draft.value = {
      process_id: processSelectItems.value[0]?.value || null,
      title: '',
      description: '',
      proposer_id: collaboratorSelectItems.value[0]?.value || null,
      date: formatToday(),
      impact: 'Moyen',
      status: 'pending',
      follow_up: '',
    }
  }

  function statusLabel (
    status: SuggestionStatus,
  ): 'À évaluer' | 'En mise en œuvre' | 'Adoptée' | 'Non retenue' {
    if (status === 'in_progress') return 'En mise en œuvre'
    if (status === 'adopted') return 'Adoptée'
    if (status === 'rejected') return 'Non retenue'
    return 'À évaluer'
  }

  function statusColor (status: SuggestionStatus): string {
    if (status === 'in_progress') return 'info'
    if (status === 'adopted') return 'success'
    if (status === 'rejected') return 'error'
    return 'warning'
  }

  function impactColor (impact: 'Faible' | 'Moyen' | 'Élevé'): string {
    if (impact === 'Élevé') return 'error'
    if (impact === 'Moyen') return 'warning'
    return 'success'
  }

  function formatToday (): string {
    const d = new Date()
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}/${month}/${year}`
  }

  function parseFrDateToIso (value: string): string {
    const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
    if (!match) {
      return new Date().toISOString().slice(0, 10)
    }
    const [, dd, mm, yyyy] = match
    return `${yyyy}-${mm}-${dd}`
  }

  function formatIsoDate (value?: string): string {
    if (!value) return formatToday()
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return formatToday()
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}/${month}/${year}`
  }

  function mapImpactToApi (
    impact: 'Faible' | 'Moyen' | 'Élevé',
  ): 'low' | 'medium' | 'high' {
    if (impact === 'Faible') return 'low'
    if (impact === 'Élevé') return 'high'
    return 'medium'
  }

  function mapImpactToLabel (
    impact: 'low' | 'medium' | 'high' | string,
  ): 'Faible' | 'Moyen' | 'Élevé' {
    if (impact === 'low') return 'Faible'
    if (impact === 'high') return 'Élevé'
    return 'Moyen'
  }
</script>

<style scoped>
.kpi-compact :deep(.v-card-text) {
  padding: 12px 14px !important;
}

.kpi-compact :deep(.text-h4) {
  font-size: 1.35rem !important;
  margin-bottom: 2px !important;
}

.kpi-compact :deep(.v-avatar) {
  width: 42px !important;
  height: 42px !important;
}

.kpi-compact :deep(.v-icon) {
  font-size: 20px !important;
}

:deep(.section-title) {
  font-weight: 700;
  margin-bottom: 8px;
  color: rgb(var(--v-theme-primary));
}

:deep(.detail-line) {
  margin-bottom: 6px;
  font-size: 0.92rem;
}

:deep(.detail-block) {
  margin-bottom: 10px;
  font-size: 0.92rem;
  line-height: 1.45;
}

:deep(.sticky-card) {
  position: sticky;
  top: 20px;
}

:deep(.link-cell-btn) {
  color: rgb(var(--v-theme-primary));
  font-weight: 700;
  text-decoration: underline;
}

.recap-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}
</style>
