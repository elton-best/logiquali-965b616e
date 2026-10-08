<template>
  <ClientALayout current-page="management-system">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-sitemap"
        subtitle="Liste des processus et leurs interactions"
        title="Système de management"
      >
        <template #actions>
          <v-btn
            class="mr-3"
            color="primary"
            prepend-icon="mdi-plus-circle"
            rounded="lg"
            @click="openCreateModal"
          >
            Créer processus
          </v-btn>
          <v-btn
            class="mr-3"
            color="indigo-darken-1"
            prepend-icon="mdi-map-marker-path"
            rounded="lg"
            variant="tonal"
            @click="router.push('/company/processes/cartography')"
          >
            Cartographie des processus
          </v-btn>
          <v-btn-toggle
            v-model="viewMode"
            class="mr-4"
            color="primary"
            mandatory
            rounded="lg"
          >
            <v-btn icon="mdi-view-grid" value="grid" />
            <v-btn icon="mdi-view-list" value="list" />
          </v-btn-toggle>
          <v-btn
            color="secondary"
            :disabled="exporting"
            :loading="exporting"
            prepend-icon="mdi-file-pdf"
            variant="tonal"
            @click="handleExportPDF"
          >
            Exporter PDF
          </v-btn>
          <v-btn
            class="ml-2"
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

      <ManagementStats :stats="stats" />

      <!-- Vue Grid -->
      <ManagementGrid
        v-if="viewMode === 'grid'"
        :get-process-name="getProcessName"
        :get-processes-by-category="getProcessesByCategory"
        :handle-view-process="handleViewProcess"
        :process-categories="processCategories"
      />

      <!-- Vue Liste -->
      <ManagementList
        v-if="viewMode === 'list'"
        :get-category-color="getCategoryColor"
        :get-category-icon="getCategoryIcon"
        :get-category-title="getCategoryTitle"
        :get-process-name="getProcessName"
        :handle-view-process="handleViewProcess"
        :list-headers="listHeaders"
        :list-items-per-page="listItemsPerPage"
        :loading="loading"
        :open-create-modal="openCreateModal"
        :processes="processes"
      />

      <ProcessCreateDialog
        v-model="createDialog"
        :add-objective="addObjective"
        :add-opportunity="addOpportunity"
        :add-risk="addRisk"
        :add-sequence="addSequence"
        :add-sub-activity="addSubActivity"
        :available-norms="availableNorms"
        :close-create-modal="closeCreateModal"
        :collaborators="collaborators"
        :create-category-options="createCategoryOptions"
        :create-form="createForm"
        :create-step="createStep"
        :create-steps="createSteps"
        :creating-process="creatingProcess"
        :get-sequence-process-items="getSequenceProcessItems"
        :move-sequence="moveSequence"
        :next-create-step="nextCreateStep"
        :opened-sequence-panels="openedSequencePanels"
        :prev-create-step="prevCreateStep"
        :remove-objective="removeObjective"
        :remove-opportunity="removeOpportunity"
        :remove-risk="removeRisk"
        :remove-sequence="removeSequence"
        :remove-sub-activity="removeSubActivity"
        :submit-create-process="submitCreateProcess"
        @update:create-step="(value) => (createStep = value)"
        @update:opened-sequence-panels="(value) => (openedSequencePanels = value)"
      />

      <v-dialog v-model="verifyDialog" max-width="560">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 mb-2">Code du document</div>
            <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
            <v-alert v-if="!lastExportedDocumentId" class="mt-3" type="warning" variant="tonal">
              Aucun brouillon n’a encore été généré. La confirmation générera le brouillon pour le workflow sans lancer de téléchargement.
            </v-alert>
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
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ManagementGrid from '@/modules/clienta/pages/iso/context/components/ManagementGrid.vue'
  import ManagementList from '@/modules/clienta/pages/iso/context/components/ManagementList.vue'
  import ManagementStats from '@/modules/clienta/pages/iso/context/components/ManagementStats.vue'
  import ProcessCreateDialog from '@/modules/clienta/pages/iso/context/components/ProcessCreateDialog.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const authStore = useAuthStore()
  interface ProcessItem {
    id: number | string
    title?: string
    name?: string
    category?: string
    source?: 'process' | 'scope'
  }

  const viewMode = ref('grid')
  const loading = ref(false)
  const exporting = ref(false)
  const verifyDialog = ref(false)
  const submittingForVerification = ref(false)
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')
  const processes = ref<ProcessItem[]>([])
  const createDialog = ref(false)
  const creatingProcess = ref(false)
  const createStep = ref(1)
  const collaborators = ref<Array<{ id: number, name: string }>>([])
  const availableNorms = ref<Array<{ value: string, label: string }>>([])
  const openedSequencePanels = ref<number[]>([])

  const createForm = ref({
    name: '',
    category: 'management',
    pilot_id: null as number | null,
    copilot_ids: [] as number[],
    purpose: '',
    sequences: [newSequence(1)],
    objectives: [] as Array<{ uid: string, name: string, indicator: string }>,
    resources: {
      human: [] as string[],
      technological: [] as string[],
      material: [] as string[],
      documentary: [] as string[],
    },
    risks: [] as Array<{ description: string, norms: string[] }>,
    opportunities: [] as string[],
  })

  const processCategories = [
    { title: 'Management', value: 'pilotage', icon: 'mdi-account-tie', color: 'primary', bgColor: 'rgba(91, 141, 217, 0.1)' },
    { title: 'Réalisation', value: 'operationnel', icon: 'mdi-cogs', color: 'success', bgColor: 'rgba(34, 197, 94, 0.1)' },
    { title: 'Support', value: 'support', icon: 'mdi-toolbox', color: 'info', bgColor: 'rgba(59, 130, 246, 0.1)' },
  ]
  const listHeaders = [
    { title: 'Processus', key: 'name', sortable: false },
    { title: 'Catégorie', key: 'category', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false },
  ]
  const createCategoryOptions = [
    { title: 'Management', value: 'management' },
    { title: 'Réalisation', value: 'realization' },
    { title: 'Support', value: 'support' },
  ]
  const createSteps = [
    { value: 1, label: 'Général' },
    { value: 2, label: 'Séquences' },
    { value: 3, label: 'Objectifs' },
    { value: 4, label: 'Ressources' },
    { value: 5, label: 'Risques/Opportunités' },
  ]
  const categoryIndex = computed(() => {
    return processCategories.reduce((acc, category) => {
      acc[category.value] = category
      return acc
    }, {} as Record<string, typeof processCategories[number]>)
  })
  const stats = computed(() => {
    const total = processes.value.length
    const management = processes.value.filter(p => p.category === 'pilotage').length
    const realization = processes.value.filter(p => p.category === 'operationnel').length
    const support = processes.value.filter(p => p.category === 'support').length
    return {
      total,
      management,
      realization,
      support,
    }
  })
  const listItemsPerPage = computed(() => Math.max(processes.value.length, 1))
  function getProcessesByCategory (category: string) {
    return processes.value
      .filter(p => p.category === category)
      .toSorted((a, b) => getProcessName(a).localeCompare(getProcessName(b), 'fr', { sensitivity: 'base' }))
  }

  function getCategoryOrder (category?: string): number {
    if (category === 'pilotage') return 0
    if (category === 'operationnel') return 1
    if (category === 'support') return 2
    return 99
  }

  function sortProcessesByFamily (rows: ProcessItem[]): ProcessItem[] {
    return [...rows].toSorted((a, b) => {
      const byFamily = getCategoryOrder(a.category) - getCategoryOrder(b.category)
      if (byFamily !== 0) return byFamily
      return getProcessName(a).localeCompare(getProcessName(b), 'fr', { sensitivity: 'base' })
    })
  }

  function getCategoryColor (category?: string) {
    if (!category) return 'grey'
    return categoryIndex.value[category]?.color || 'grey'
  }

  function getCategoryIcon (category?: string) {
    if (!category) return 'mdi-cog'
    return categoryIndex.value[category]?.icon || 'mdi-cog'
  }

  function getCategoryTitle (category?: string) {
    if (!category) return 'N/A'
    return categoryIndex.value[category]?.title || category
  }

  function getProcessName (process: ProcessItem) {
    return process.title || process.name || `Processus #${process.id}`
  }

  function handleViewProcess (process: ProcessItem) {
    const name = getProcessName(process)
    const category = process.category || ''

    if (process.source === 'scope') {
      router.push({
        path: '/company/context/management-system/new',
        query: {
          name,
          category,
        },
      })
      return
    }

    router.push(`/company/context/management-system/${process.id}`)
  }

  function handleCreateProcess () {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const selectedSiteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)

    if (!selectedSiteId) {
      toast.warning('Veuillez d’abord sélectionner un site')
      router.push({
        path: '/company/context/management-system',
        query: { openCreate: '1' },
      })
      return
    }

    router.push({
      path: '/company/context/management-system',
      query: {
        site_id: String(selectedSiteId),
        openCreate: '1',
      },
    })
  }

  function shouldOpenCreateModalFromQuery (): boolean {
    const raw = String(route.query.openCreate || '').toLowerCase()
    return raw === '1' || raw === 'true' || raw === 'yes'
  }

  async function handleCreateModalRouteQuery (): Promise<void> {
    if (!shouldOpenCreateModalFromQuery() || createDialog.value) {
      return
    }

    await openCreateModal()

    const nextQuery = { ...route.query }
    delete nextQuery.openCreate

    router.replace({
      path: route.path,
      query: nextQuery,
    })
  }

  async function handleExportPDF (download = true) {
    const siteId = resolveSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }

    exporting.value = true
    try {
      const response = await api.get('/contexts/export-docx', {
        params: { site_id: siteId },
        responseType: 'blob',
      })
      const generatedDocumentId = Number(response.headers?.['x-generated-document-id'] || 0)
      if (generatedDocumentId > 0) {
        lastExportedDocumentId.value = generatedDocumentId
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
      }

      if (download) {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        const siteName = authStore.currentSite?.name || 'Site'
        const filename = `Contexte_Organisme_${siteName}_${new Date().toISOString().split('T')[0]}.docx`
        link.setAttribute('download', filename)
        document.body.append(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        toast.success('Document exporté avec succès')
      }
    } catch (error) {
      console.error('[ManagementSystem] Export failed:', error)
      toast.error('Erreur lors de l\'export du document')
    } finally {
      exporting.value = false
    }
  }

  function openVerifyDialog () {
    verifyDialog.value = true
  }

  async function submitForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      await handleExportPDF(false)
    }

    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      toast.error('Impossible de générer le brouillon à soumettre.')
      return
    }
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      verifyDialog.value = false
      toast.success('Document envoyé pour vérification.')
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
    } catch {
      toast.error('Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  function normalizeCategory (raw?: string): string {
    if (!raw) return ''
    const value = raw.toLowerCase()
    if (value === 'management') return 'pilotage'
    if (value === 'realization') return 'operationnel'
    if (value === 'support') return 'support'
    return value
  }

  function normalizeCreationCategory (raw: string): 'pilotage' | 'operationnel' | 'support' {
    if (raw === 'management') return 'pilotage'
    if (raw === 'realization') return 'operationnel'
    return 'support'
  }

  function resolveSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
    return Number.isFinite(Number(siteId)) ? Number(siteId) : null
  }

  function newSequence (order = 1) {
    return {
      uid: `seq-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
      sequenceOrder: order,
      supplierProcesses: [] as string[],
      inputs: '',
      activities: '',
      subActivities: [] as string[],
      newSubActivity: '',
      outputs: '',
      clientProcesses: [] as string[],
    }
  }

  function resetCreateForm () {
    createForm.value = {
      name: '',
      category: 'management',
      pilot_id: null,
      copilot_ids: [],
      purpose: '',
      sequences: [newSequence(1)],
      objectives: [],
      resources: {
        human: [],
        technological: [],
        material: [],
        documentary: [],
      },
      risks: [],
      opportunities: [],
    }
  }

  function addSequence () {
    createForm.value.sequences.unshift(newSequence(1))
    for (const [idx, seq] of createForm.value.sequences.entries()) {
      seq.sequenceOrder = idx + 1
    }
    openedSequencePanels.value = [0]
  }

  function removeSequence (index: number) {
    if (createForm.value.sequences.length <= 1) return
    createForm.value.sequences.splice(index, 1)
    for (const [idx, seq] of createForm.value.sequences.entries()) {
      seq.sequenceOrder = idx + 1
    }
    openedSequencePanels.value = createForm.value.sequences.map((_, idx) => idx)
  }

  function moveSequence (index: number, direction: 'up' | 'down') {
    const targetIndex = direction === 'up' ? index - 1 : index + 1
    if (targetIndex < 0 || targetIndex >= createForm.value.sequences.length) return
    const temp = createForm.value.sequences[index]
    if (!temp) return
    createForm.value.sequences[index] = createForm.value.sequences[targetIndex]!
    createForm.value.sequences[targetIndex] = temp
    for (const [idx, seq] of createForm.value.sequences.entries()) {
      seq.sequenceOrder = idx + 1
    }
    openedSequencePanels.value = createForm.value.sequences.map((_, idx) => idx)
  }

  function addSubActivity (seq: { subActivities: string[], newSubActivity?: string }) {
    const value = String(seq.newSubActivity || '').trim()
    if (!value) return
    if (!Array.isArray(seq.subActivities)) {
      seq.subActivities = []
    }
    if (!seq.subActivities.includes(value)) {
      seq.subActivities.push(value)
    }
    seq.newSubActivity = ''
  }

  function removeSubActivity (seq: { subActivities: string[] }, subIndex: number) {
    if (!Array.isArray(seq.subActivities)) return
    seq.subActivities.splice(subIndex, 1)
  }

  function normalizeStringArray (value: any): string[] {
    if (!value) return []

    if (Array.isArray(value)) {
      return value
        .map(v => {
          if (typeof v === 'string' || typeof v === 'number') return String(v).trim()
          if (v && typeof v === 'object') return String(v.name || v.title || v.label || v.value || '').trim()
          return ''
        })
        .filter(Boolean)
    }

    if (typeof value === 'string') {
      if (value.startsWith('[') || value.startsWith('{')) {
        try {
          const parsed = JSON.parse(value)
          if (Array.isArray(parsed)) return normalizeStringArray(parsed)
        } catch {
        // ignore invalid JSON
        }
      }
      return value.split(',').map(v => v.trim()).filter(Boolean)
    }

    if (value && typeof value === 'object') {
      const values = Object.values(value)
      if (values.length > 0) return normalizeStringArray(values)
    }

    return []
  }

  function flushPendingSubActivities () {
    for (const seq of createForm.value.sequences) {
      const pending = String(seq.newSubActivity || '').trim()
      if (pending) {
        if (!Array.isArray(seq.subActivities)) seq.subActivities = []
        if (!seq.subActivities.includes(pending)) seq.subActivities.push(pending)
        seq.newSubActivity = ''
      }
      seq.subActivities = normalizeStringArray(seq.subActivities)
    }
  }

  function getSequenceProcessItems (seq: { supplierProcesses: string[], clientProcesses: string[] }) {
    const selected = [...(seq.supplierProcesses || []), ...(seq.clientProcesses || [])]
      .map(value => String(value || '').trim())
      .filter(Boolean)
      .map(name => ({ id: `saved-${name}`, name }))

    const base = (processes.value || [])
      .map(item => ({ id: item.id, name: String(getProcessName(item) || '').trim() }))
      .filter(item => item.name)

    const merged = [...base]
    const seen = new Set(base.map(item => item.name.toLowerCase()))
    for (const item of selected) {
      const key = item.name.toLowerCase()
      if (!seen.has(key)) {
        merged.push(item)
        seen.add(key)
      }
    }

    return merged
  }

  function addObjective () {
    createForm.value.objectives.unshift({
      uid: `obj-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
      name: '',
      indicator: '',
    })
  }

  function removeObjective (index: number) {
    createForm.value.objectives.splice(index, 1)
  }

  function addRisk () {
    createForm.value.risks.unshift({
      description: '',
      norms: [],
    })
  }

  function removeRisk (index: number) {
    createForm.value.risks.splice(index, 1)
  }

  function addOpportunity () {
    createForm.value.opportunities.unshift('')
  }

  function removeOpportunity (index: number) {
    createForm.value.opportunities.splice(index, 1)
  }

  function extractNormCode (value: string): string | null {
    const text = value.toUpperCase()
    const match = text.match(/(?:ISO[\s:-]*)?(\d{4,5})/)
    return match?.[1] || null
  }

  function normalizeNormsForSelect (values: string[]): string[] {
    if (!Array.isArray(values) || values.length === 0) return []
    if (availableNorms.value.length === 0) return Array.from(new Set(values.map(v => String(v).trim()).filter(Boolean)))

    const normalize = (v: string) => v.toLocaleLowerCase().replace(/\s+/g, ' ').trim()
    const mapped: string[] = []

    for (const raw of values) {
      const value = String(raw || '').trim()
      if (!value) continue

      const exact = availableNorms.value.find(n => normalize(n.value) === normalize(value))
      if (exact) {
        mapped.push(exact.value)
        continue
      }

      const byLabel = availableNorms.value.find(n => normalize(n.label) === normalize(value))
      if (byLabel) {
        mapped.push(byLabel.value)
        continue
      }

      const code = extractNormCode(value)
      if (code) {
        const byCode = availableNorms.value.find(n => extractNormCode(n.value) === code || extractNormCode(n.label) === code)
        if (byCode) {
          mapped.push(byCode.value)
          continue
        }
      }

      mapped.push(value)
    }

    return Array.from(new Set(mapped))
  }

  async function openCreateModal () {
    resetCreateForm()
    createStep.value = 1
    createDialog.value = true
    await Promise.all([
      loadNormsForSite(),
      loadCollaborators(),
    ])
    openedSequencePanels.value = createForm.value.sequences.map((_, idx) => idx)
  }

  function closeCreateModal () {
    if (creatingProcess.value) return
    createDialog.value = false
    createStep.value = 1
  }

  function nextCreateStep () {
    createStep.value = Math.min(createSteps.length, createStep.value + 1)
  }

  function prevCreateStep () {
    createStep.value = Math.max(1, createStep.value - 1)
  }

  async function loadCollaborators () {
    const siteId = resolveSiteId()
    if (!siteId) {
      collaborators.value = []
      return
    }

    try {
      const response = await api.get('/users', { params: { site_id: siteId, per_page: 500 } })
      const rows = response.data?.data || []
      collaborators.value = rows.map((row: any) => ({
        id: Number(row.id),
        name: String(row.attributes?.name || row.name || row.full_name || `Utilisateur #${row.id}`),
      }))
    } catch (error) {
      console.warn('[ManagementSystem] Failed to load collaborators:', error)
      collaborators.value = []
    }
  }

  function asArrayOrEmpty (value: unknown): any[] {
    return Array.isArray(value) ? value : []
  }

  function getValueByPath (source: any, path: string): unknown {
    return path
      .split('.')
      .reduce((current: any, segment: string) => (current == null ? undefined : current[segment]), source)
  }

  function extractNormsFromSubscription (sub: any): any[] {
    const candidatePaths = [
      'offer.norms',
      'attributes.offer.norms',
      'relationships.offer.relationships.norms.data',
      'relationships.offer.relationships.norms',
      'relationships.offer.data.relationships.norms.data',
      'relationships.offer.data.relationships.norms',
      'attributes.norms',
      'norms',
    ]

    for (const path of candidatePaths) {
      const values = asArrayOrEmpty(getValueByPath(sub, path))
      if (values.length > 0) return values
    }

    return []
  }

  async function loadNormsForSite () {
    const siteId = resolveSiteId()
    if (!siteId) {
      availableNorms.value = []
      return
    }

    try {
      const response = await api.get('/enterprise-subscriptions', { params: { site_id: siteId } })
      const subscriptions = response.data?.data || []
      const now = new Date()

      const getStatus = (sub: any) => String(sub?.attributes?.status ?? sub?.status ?? '').toLowerCase()
      const activeSubscriptions = subscriptions.filter((sub: any) => {
        const isActive = Boolean(sub?.attributes?.is_active ?? sub?.is_active)
        const expirationRaw = sub?.attributes?.expiration_date ?? sub?.expiration_date
        const status = getStatus(sub)

        if (status === 'cancelled' || status === 'expired') return false
        if (!isActive) return false
        if (!expirationRaw) return true
        const expiration = new Date(expirationRaw)
        return Number.isNaN(expiration.getTime()) || expiration > now
      })

      const baseSubs = activeSubscriptions.length > 0 ? activeSubscriptions : subscriptions
      const options: Array<{ value: string, label: string }> = []
      const seen = new Set<string>()
      const unresolvedNormIds = new Set<number>()

      for (const subscription of baseSubs) {
        const norms = extractNormsFromSubscription(subscription)
        for (const norm of norms) {
          const attributes = norm?.attributes || norm
          const name = String(attributes?.name || attributes?.title || '').trim()
          const resolvedCode = String(attributes?.code || '').trim()
          const value = resolvedCode || name
          const normId = Number(norm?.id)
          if (!value) {
            if (Number.isFinite(normId)) unresolvedNormIds.add(normId)
            continue
          }
          const key = value.toLowerCase()
          if (seen.has(key)) continue
          seen.add(key)
          options.push({
            value,
            label: resolvedCode && name ? `${resolvedCode} - ${name}` : value,
          })
        }
      }

      if (options.length > 0 || unresolvedNormIds.size === 0) {
        availableNorms.value = options
        return
      }

      const normsResponse = await api.get('/norms')
      const allAccessibleNorms = normsResponse.data?.data || []
      const fallbackNorms = allAccessibleNorms
        .filter((norm: any) => unresolvedNormIds.has(Number(norm?.id)))
        .map((norm: any) => {
          const code = String(norm?.code || norm?.attributes?.code || '').trim()
          const name = String(norm?.name || norm?.attributes?.name || norm?.title || '').trim()
          const value = code || name
          if (!value) return null
          return {
            value,
            label: code && name ? `${code} - ${name}` : value,
          }
        })
        .filter(Boolean) as Array<{ value: string, label: string }>

      availableNorms.value = fallbackNorms
    } catch (error) {
      console.warn('[ManagementSystem] Failed to load norms:', error)
      availableNorms.value = []
    }
  }

  async function submitCreateProcess () {
    if (!createForm.value.name.trim()) {
      toast.error('Le nom du processus est requis.')
      return
    }
    if (!createForm.value.purpose.trim()) {
      toast.error('La finalité du processus est requise.')
      return
    }

    const siteId = resolveSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }

    creatingProcess.value = true
    try {
      flushPendingSubActivities()
      const payload = {
        site_id: siteId,
        title: createForm.value.name.trim(),
        category: normalizeCreationCategory(createForm.value.category),
        pilot_id: createForm.value.pilot_id || undefined,
        copilot_ids: createForm.value.copilot_ids,
        copilot_id: createForm.value.copilot_ids[0] || null,
        purpose: createForm.value.purpose.trim(),
        ressources: {
          human: createForm.value.resources.human,
          technological: createForm.value.resources.technological,
          material: createForm.value.resources.material,
          documentary: createForm.value.resources.documentary,
        },
        sequences: createForm.value.sequences.map((sequence, index) => ({
          sequence_order: index + 1,
          input_description: sequence.inputs || '',
          activity_description: sequence.activities || '',
          output_description: sequence.outputs || '',
          sub_activities: normalizeStringArray(sequence.subActivities).filter(Boolean),
          supplier_processes: normalizeStringArray(sequence.supplierProcesses).filter(Boolean),
          client_processes: normalizeStringArray(sequence.clientProcesses).filter(Boolean),
        })),
      }

      const created = await processService.createProcess(payload as any)
      const processId = Number(created?.data?.id || created?.id || created?.data?.data?.id)

      if (Number.isFinite(processId) && processId > 0) {
        for (const objective of createForm.value.objectives) {
          if (!objective.name.trim()) continue
          const indicatorName = String(objective.indicator || '').trim()
          await processService.addObjective(processId, {
            title: objective.name.trim(),
            indicator_name: indicatorName || `Indicateur - ${objective.name.trim()}`,
            description: '',
          })
        }

        for (const risk of createForm.value.risks) {
          if (!risk.description.trim()) continue
          await processService.addRiskOpportunity(processId, {
            type: 'risque',
            title: risk.description.slice(0, 255),
            description: risk.description,
            normes_iso: normalizeNormsForSelect(risk.norms || []),
            probabilite: 3,
            gravite: 3,
          } as any)
        }

        for (const opportunity of createForm.value.opportunities) {
          const description = String(opportunity || '').trim()
          if (!description) continue
          await processService.addRiskOpportunity(processId, {
            type: 'opportunite',
            title: description.slice(0, 255),
            description,
            probabilite: 3,
            gravite: 3,
          } as any)
        }
      }

      toast.success('Processus créé avec succès.')
      createDialog.value = false
      await loadProcesses()
    } catch (error) {
      console.error('[ManagementSystem] Failed to create process:', error)
      toast.error('Impossible de créer le processus.')
    } finally {
      creatingProcess.value = false
    }
  }

  async function loadProcesses () {
    loading.value = true
    try {
      const storedSiteId = Number(localStorage.getItem('current_site_id'))
      const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
      const filters = siteId ? { site_id: siteId } : {}
      const response = await processService.getProcesses(filters, 1, 500)
      const apiProcesses = (response.data || []).map((process: any) => ({
        id: process.id,
        title: process.title,
        name: process.name,
        category: normalizeCategory(process.category || process.type || process.process_type),
        source: 'process' as const,
      }))

      let scopeProcesses: ProcessItem[] = []
      if (siteId) {
        try {
          const scopeResponse = await api.get('/application-scopes', {
            params: { site_id: siteId, is_current: true },
          })
          const scope = scopeResponse.data?.data?.[0]?.attributes || scopeResponse.data?.data?.[0]
          const list = scope?.processes || []
          scopeProcesses = list.map((p: any, index: number) => ({
            id: `scope-${index}`,
            name: p.name,
            category: normalizeCategory(p.type),
            source: 'scope' as const,
          }))
        } catch (error) {
          console.warn('[ManagementSystem] Failed to load scope processes:', error)
        }
      }

      const merged = [...apiProcesses]
      const seen = new Set(apiProcesses.map((p: ProcessItem) => `${p.name || p.title}-${p.category}`))
      for (const p of scopeProcesses) {
        const key = `${p.name || p.title}-${p.category}`
        if (!seen.has(key)) {
          merged.push(p)
        }
      }

      processes.value = sortProcessesByFamily(merged)
    } catch (error) {
      console.error('[ManagementSystem] Failed to load processes:', error)
      toast.error('Erreur lors du chargement des processus')
    } finally {
      loading.value = false
    }
  }

  onMounted(async () => {
    await loadProcesses()
    await handleCreateModalRouteQuery()
  })
  watch(() => route.query.openCreate, () => {
    void handleCreateModalRouteQuery()
  })
  watch(() => authStore.currentSiteId, () => {
    loadProcesses()
    loadNormsForSite()
    loadCollaborators()
    createForm.value.risks = createForm.value.risks.map(risk => ({
      ...risk,
      norms: normalizeNormsForSelect(risk.norms || []),
    }))
  })
</script>
