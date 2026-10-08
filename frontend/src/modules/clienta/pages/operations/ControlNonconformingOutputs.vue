<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      icon="mdi-alert-decagram"
      subtitle="ISO 9001 §8.7 - Maîtrise des éléments de sortie non conformes"
      title="Maîtrise des éléments de sortie non-conformes"
    >
      <template #actions>
        <v-btn
          color="primary"
          prepend-icon="mdi-file-document-plus-outline"
          variant="tonal"
          @click="procedureDialog = true"
        >
          Ajouter une procédure
        </v-btn>
      </template>
    </PageHeader>

    <div class="control-nc-page">
      <ControlNcHero
        :open-nc-count="openNcCount"
        :reclamation-count="reclamationCount"
        @help="openHowItWorks"
      />

      <!--<ProceduresPanel ref="proceduresPanel" /> -->

      <ControlNcTabs
        v-model="activeTab"
        :action-type-options="actionTypeOptions"
        :detection-source-options="detectionSourceOptions"
        :editing-nc-id="editingNcId"
        :finding-type-options="findingTypeOptions"
        :format-date="formatDate"
        :loading-nc="loadingNc"
        :loading-reclamations="loadingReclamations"
        :nc-actions="ncActions"
        :nc-form="ncForm"
        :nc-headers="ncHeaders"
        :nc-status-options="ncStatusOptions"
        :nc-type-options="ncTypeOptions"
        :non-conformities="nonConformities"
        :process-options="processOptions"
        :reclamation-actions="reclamationActions"
        :reclamation-form="reclamationForm"
        :reclamation-headers="reclamationHeaders"
        :reclamation-status-options="reclamationStatusOptions"
        :reclamation-type-options="reclamationTypeOptions"
        :reclamations="reclamations"
        :saving-nc="savingNc"
        :saving-reclamation="savingReclamation"
        :status-color="statusColor"
        :status-label="statusLabel"
        :user-options="userOptions"
        @add-nc-action="addNcAction"
        @add-reclamation-action="addReclamationAction"
        @edit-nc="openEditNc"
        @edit-reclamation="openEditReclamation"
        @export-nc="exportNonConformities"
        @export-reclamations="exportReclamations"
        @remove-nc-action="removeNcAction"
        @remove-reclamation-action="removeReclamationAction"
        @submit-nc="submitNonConformity"
        @submit-reclamation="submitReclamation"
      />
    </div>
    <HowItWorksDialog
      v-model="showHowItWorks"
      :active-tab="activeTab"
      @dismiss="dismissHowItWorks"
    />

    <ProcedureUploadDialog v-model="procedureDialog" @created="proceduresPanel?.refresh()" />
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { DetectionSource, NCSource } from '@/api/services/nonconformities.service'
  import type ProceduresPanel from '@/modules/clienta/components/documents/ProceduresPanel.vue'
  import { computed, onMounted, reactive, ref } from 'vue'
  import * as XLSX from 'xlsx'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcedureUploadDialog from '@/modules/clienta/components/documents/ProcedureUploadDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useNonConformities } from '@/modules/clienta/composables/useNonConformities'
  import ControlNcHero from '@/modules/clienta/pages/iso/operations/components/ControlNcHero.vue'
  import ControlNcTabs from '@/modules/clienta/pages/iso/operations/components/ControlNcTabs.vue'
  import HowItWorksDialog from '@/modules/clienta/pages/iso/operations/components/HowItWorksDialog.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { complaintService } from '@/services/complaintService'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  interface ProcessOption {
    value: number
    title: string
  }

  interface UserOption {
    id: number
    name: string
  }

  const authStore = useAuthStore()
  const toast = useToast()
  const { ncs, fetchNonConformities, createNonConformity, updateNonConformity, updateStatus } = useNonConformities()
  const activeTab = ref<'nc' | 'complaint'>('nc')
  const savingNc = ref(false)
  const savingReclamation = ref(false)
  const loadingNc = ref(false)
  const loadingReclamations = ref(false)
  const editingNcId = ref<number | null>(null)
  const editingReclamationId = ref<number | null>(null)
  const showHowItWorks = ref(false)
  const procedureDialog = ref(false)
  const proceduresPanel = ref<InstanceType<typeof ProceduresPanel> | null>(null)

  const processOptions = ref<ProcessOption[]>([])
  const userOptions = ref<UserOption[]>([])
  const nonConformities = ref<any[]>([])
  const reclamations = ref<any[]>([])
  const rawNonConformities = ref<any[]>([])
  const actionTypeOptions = [
    { title: 'Corrective', value: 'corrective' },
    { title: 'Préventive', value: 'preventive' },
    { title: 'Amélioration', value: 'improvement' },
  ]

  const currentSiteId = computed<number | null>(() => {
    const direct = authStore.currentSiteId
    if (typeof direct === 'number' && Number.isFinite(direct)) {
      return direct
    }

    const stored = Number(localStorage.getItem('current_site_id') || localStorage.getItem('active_site_id'))
    return Number.isFinite(stored) && stored > 0 ? stored : null
  })

  const findingTypeOptions = [
    { title: 'Non-conformité', value: 'non_conformity' },
    { title: 'Écart', value: 'gap' },
  ]

  const detectionSourceOptions = [
    { title: 'Audit interne', value: 'internal_audit' },
    { title: 'Audit externe', value: 'external_audit' },
    { title: 'Réclamation client', value: 'customer_complaint' },
    { title: 'Contrôle interne', value: 'internal_control' },
    { title: 'Revue de direction', value: 'management_review' },
    { title: 'Autre', value: 'other' },
  ]

  const ncTypeOptions = [
    { title: 'N-C majeure', value: 'N-C majeure' },
    { title: 'N-C mineure', value: 'N-C mineure' },
  ]

  const ncStatusOptions = [
    { title: 'Ouverte', value: 'open' },
    { title: 'En traitement', value: 'in_progress' },
    { title: 'Clôturée', value: 'closed' },
  ]

  const reclamationTypeOptions = [
    { title: 'Produit', value: 'product' },
    { title: 'Service', value: 'service' },
    { title: 'Livraison', value: 'delivery' },
    { title: 'Qualité', value: 'quality' },
    { title: 'Sécurité', value: 'safety' },
    { title: 'Autre', value: 'other' },
  ]

  const reclamationStatusOptions = [
    { title: 'En attente', value: 'pending' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Résolue', value: 'resolved' },
    { title: 'Fermée', value: 'closed' },
  ]

  const ncHeaders = [
    { title: 'Réf', key: 'reference' },
    { title: 'Processus', key: 'process' },
    { title: 'Type constat', key: 'type_constat' },
    { title: 'Source', key: 'source_detection' },
    { title: 'Type NC', key: 'type_nc' },
    { title: 'Exigence', key: 'requirement' },
    { title: 'Responsable', key: 'responsible' },
    { title: 'Délai', key: 'deadline' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', align: 'end' as const, sortable: false },
  ]

  const reclamationHeaders = [
    { title: 'Réf', key: 'reference' },
    { title: 'Client', key: 'client_name' },
    { title: 'Objet', key: 'title' },
    { title: 'Date', key: 'received_date' },
    { title: 'Actions', key: 'actions', align: 'end' as const, sortable: false },
  ]

  type FindingType = 'non_conformity' | 'gap'
  type NcFormState = {
    processId: number | null
    date: string
    typeConstat: FindingType
    sourceDetection: DetectionSource
    typeNc: 'N-C majeure' | 'N-C mineure'
    requirement: string
    description: string
    cause: string
    status: 'open' | 'in_progress' | 'closed'
    implicatedIds: number[]
    result: string
  }

  type ReclamationStatus = 'pending' | 'in_progress' | 'resolved' | 'closed'
  type ReclamationCategory = 'product' | 'service' | 'delivery' | 'quality' | 'safety' | 'other'
  type ReclamationFormState = {
    reference: string
    receivedDate: string
    type: ReclamationCategory
    customerName: string
    address: string
    customerEmail: string
    customerPhone: string
    wantsMail: boolean
    dueDate: string
    processId: number | null
    title: string
    description: string
    requestedSolutions: string
    probableCauses: string
    assignedTo: number | null
    status: ReclamationStatus
  }

  const ncForm = reactive<NcFormState>({
    processId: null as number | null,
    date: new Date().toISOString().slice(0, 10),
    typeConstat: 'non_conformity',
    sourceDetection: 'internal_control',
    typeNc: 'N-C mineure',
    requirement: '',
    description: '',
    cause: '',
    status: 'open',
    implicatedIds: [] as number[],
    result: 'En attente',
  })

  const reclamationForm = reactive<ReclamationFormState>({
    reference: `REC-${new Date().getFullYear()}-${String(Date.now()).slice(-5)}`,
    receivedDate: new Date().toISOString().slice(0, 10),
    type: 'service',
    customerName: '',
    address: '',
    customerEmail: '',
    customerPhone: '',
    wantsMail: true,
    dueDate: '',
    processId: null as number | null,
    title: '',
    description: '',
    requestedSolutions: '',
    probableCauses: '',
    assignedTo: null as number | null,
    status: 'pending',
  })

  type ActionDraft = {
    description: string
    responsibleId: number | null
    deadline: string
    type: 'corrective' | 'preventive' | 'improvement'
  }

  const ncActions = ref<ActionDraft[]>([buildActionDraft()])
  const reclamationActions = ref<ActionDraft[]>([buildActionDraft()])

  function buildActionDraft (): ActionDraft {
    return {
      description: '',
      responsibleId: null,
      deadline: '',
      type: 'corrective',
    }
  }

  function addNcAction () {
    ncActions.value.push(buildActionDraft())
  }

  function removeNcAction (index: number) {
    ncActions.value.splice(index, 1)
  }

  function addReclamationAction () {
    reclamationActions.value.push(buildActionDraft())
  }

  function removeReclamationAction (index: number) {
    reclamationActions.value.splice(index, 1)
  }

  const openNcCount = computed(() => nonConformities.value.length)
  const reclamationCount = computed(() => reclamations.value.length)

  function formatDate (value: string): string {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleDateString('fr-FR')
  }

  function downloadExcel (filename: string, headers: string[], rows: unknown[][], sheetName: string): void {
    const workbook = XLSX.utils.book_new()
    const sheetRows = [headers, ...rows]
    const worksheet = XLSX.utils.aoa_to_sheet(sheetRows)
    worksheet['!cols'] = headers.map(() => ({ wch: 22 }))
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName)
    XLSX.writeFile(workbook, filename)
  }

  function downloadExcelWithMerges (
    filename: string,
    headers: string[],
    rows: unknown[][],
    sheetName: string,
    merges: Array<{ s: { r: number, c: number }, e: { r: number, c: number } }>,
  ): void {
    const workbook = XLSX.utils.book_new()
    const sheetRows = [headers, ...rows]
    const worksheet = XLSX.utils.aoa_to_sheet(sheetRows)
    worksheet['!cols'] = headers.map(() => ({ wch: 22 }))
    worksheet['!merges'] = merges
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName)
    XLSX.writeFile(workbook, filename)
  }

  function sourceLabel (source?: string | null): string {
    switch (source) {
      case 'internal_audit': { return 'Audit interne'
      }
      case 'external_audit': { return 'Audit externe'
      }
      case 'customer_complaint': { return 'Réclamation client'
      }
      case 'internal_control': { return 'Contrôle interne'
      }
      case 'management_review': { return 'Revue de direction'
      }
      case 'other': { return 'Autre'
      }
      default: { return 'Contrôle interne'
      }
    }
  }

  function statusLabel (status: 'open' | 'in_progress' | 'closed'): string {
    if (status === 'closed') return 'Clôturée'
    if (status === 'in_progress') return 'En traitement'
    return 'Ouverte'
  }

  function statusColor (status: 'open' | 'in_progress' | 'closed'): string {
    if (status === 'closed') return 'success'
    if (status === 'in_progress') return 'warning'
    return 'error'
  }

  function mapStatusForApi (status: 'open' | 'in_progress' | 'closed'): 'open' | 'analysis' | 'corrective_action' | 'verification' | 'closed' {
    if (status === 'in_progress') return 'corrective_action'
    return status
  }

  function mapStatusUi (status: string): 'open' | 'in_progress' | 'closed' {
    if (status === 'closed') return 'closed'
    if (status === 'open') return 'open'
    return 'in_progress'
  }

  function cleanRequirementText (raw: unknown, fallback?: unknown): string {
    const normalize = (value: unknown): string => (typeof value === 'string' ? value.trim() : '')
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
        return secondary && !secondary.startsWith('{') && !secondary.startsWith('[') ? secondary : '—'
      } catch {
        return '—'
      }
    }

    return pick
  }

  function mapNcRow (nc: any) {
    const processName = nc.process?.title || nc.process?.name || 'Processus non défini'
    const statusUi = mapStatusUi(nc.status)
    const typeNc = nc.type === 'majeure' ? 'N-C majeure' : 'N-C mineure'
    return {
      id: nc.id,
      reference: nc.reference,
      process: processName,
      type_constat: nc.finding_type === 'gap' ? 'Écart' : 'Non-conformité',
      source_detection: sourceLabel(nc.detection_source),
      type_nc: typeNc,
      requirement: cleanRequirementText(nc.requirement_reference, nc.description),
      responsible: nc.responsible?.name || 'Non affecté',
      deadline: formatDate(nc.due_date),
      status: statusUi,
    }
  }

  function resolveUserNames (ids: number[]): string {
    const names = ids
      .map(id => userOptions.value.find(user => Number(user.id) === Number(id))?.name)
      .filter(Boolean)
    return names.join(', ')
  }

  function formatImplicatedNames (entity: any): string {
    if (!entity) return ''
    const candidates = entity.implicated_users || entity.implicated || entity.implicated_collaborators || entity.implicated_users_list
    if (Array.isArray(candidates) && candidates.length > 0) {
      if (typeof candidates[0] === 'string') return candidates.join(', ')
      if (typeof candidates[0] === 'number') return resolveUserNames(candidates)
      return candidates.map((item: any) => item?.name || item?.full_name).filter(Boolean).join(', ')
    }

    const idCandidates = entity.implicated_ids || entity.implicatedIds || entity.implicated_user_ids
    if (Array.isArray(idCandidates) && idCandidates.length > 0) {
      return resolveUserNames(idCandidates)
    }

    return typeof entity.implicated_names === 'string' ? entity.implicated_names : ''
  }

  function exportNonConformities (): void {
    const headers = [
      'Reference',
      'Processus',
      'Type constat',
      'Source detection',
      'Type NC',
      'Exigence non respectee',
      'Description',
      'Cause(s)',
      'Statut',
      'Date ouverture',
      'Resultat',
      'Action',
      'Type action',
      'Responsable action',
      'Impliqués',
      'Delai action',
    ]
    const rows: unknown[][] = []
    const merges: Array<{ s: { r: number, c: number }, e: { r: number, c: number } }> = []

    for (const nc of ncs.value) {
      const implicatedNames = formatImplicatedNames(nc)
      const actions = Array.isArray(nc.actions) && nc.actions.length > 0
        ? nc.actions
          .map((action: any) => ({
            description: String(action.description || action.title || '').trim(),
            type: action.type || '',
            responsible: action.responsible?.name || '',
            deadline: action.deadline || '',
          }))
          .filter((action: any) => Boolean(action.description))
        : []

      const groupSize = Math.max(actions.length, 1)
      const baseRow = [
        nc.reference || '',
        nc.process?.title || nc.process?.name || '',
        nc.finding_type === 'gap' ? 'Écart' : 'Non-conformité',
        sourceLabel(nc.detection_source),
        nc.type === 'majeure' ? 'N-C majeure' : 'N-C mineure',
        cleanRequirementText(nc.requirement_reference, nc.description),
        nc.description || '',
        nc.root_cause || '',
        statusLabel(mapStatusUi(nc.status)),
        formatDate(nc.detected_date),
        nc.result_summary || '',
      ]

      for (let i = 0; i < groupSize; i += 1) {
        const action = actions[i]
        rows.push([
          ...(i === 0 ? baseRow : baseRow.map(() => '')),
          action?.description || '',
          action?.type || '',
          action?.responsible || '',
          implicatedNames,
          formatDate(action?.deadline || ''),
        ])
      }

      if (groupSize > 1) {
        const startRow = rows.length - groupSize + 1
        const endRow = rows.length
        for (let col = 0; col <= 10; col += 1) {
          merges.push({ s: { r: startRow, c: col }, e: { r: endRow, c: col } })
        }
      }
    }

    const dateToken = new Date().toISOString().slice(0, 10)
    downloadExcelWithMerges(`non_conformites_${dateToken}.xlsx`, headers, rows, 'Non-conformités', merges)
    toast.success('Export des non-conformités généré.')
  }

  function exportReclamations (): void {
    const headers = [
      'Reference',
      'Client',
      'Email',
      'Structure',
      'Categorie',
      'Objet',
      'Description',
      'Date reception',
      'Date echeance',
      'Statut',
      'Responsable',
      'Date creation',
      'Action',
      'Type action',
      'Responsable action',
      'Impliqués',
      'Delai action',
    ]
    const rows: unknown[][] = []
    const merges: Array<{ s: { r: number, c: number }, e: { r: number, c: number } }> = []

    reclamations.value.forEach((item: any) => {
      const implicatedNames = formatImplicatedNames(item)
      const actions = Array.isArray(item.actions) && item.actions.length > 0
        ? item.actions
          .map((action: any) => ({
            description: String(action.description || action.title || '').trim(),
            type: action.type || '',
            responsible: action.responsible?.name || '',
            deadline: action.deadline || '',
          }))
          .filter((action: any) => Boolean(action.description))
        : []

      const groupSize = Math.max(actions.length, 1)
      const baseRow = [
        item.reference || item.ref || '',
        item.client_name || item.customer_name || '',
        item.client_email || item.customer_email || '',
        item.client_company || '',
        item.category || item.type || '',
        item.title || '',
        item.description || '',
        item.received_date || '',
        item.due_date || '',
        item.status || '',
        item.assigned_user?.name || item.responsible?.name || '',
        item.created_at || '',
      ]

      for (let i = 0; i < groupSize; i += 1) {
        const action = actions[i]
        rows.push([
          ...(i === 0 ? baseRow : baseRow.map(() => '')),
          action?.description || '',
          action?.type || '',
          action?.responsible || '',
          implicatedNames,
          formatDate(action?.deadline || ''),
        ])
      }

      if (groupSize > 1) {
        const startRow = rows.length - groupSize + 1
        const endRow = rows.length
        for (let col = 0; col <= 11; col += 1) {
          merges.push({ s: { r: startRow, c: col }, e: { r: endRow, c: col } })
        }
      }
    })

    const dateToken = new Date().toISOString().slice(0, 10)
    downloadExcelWithMerges(`reclamations_${dateToken}.xlsx`, headers, rows, 'Réclamations', merges)
    toast.success('Export des réclamations généré.')
  }

  function normalizeActions (actions: ActionDraft[], processId?: number | null) {
    return actions
      .map(item => ({
        type: item.type || 'corrective',
        description: item.description?.trim() || '',
        responsible_id: item.responsibleId ?? null,
        deadline: item.deadline || null,
        process_id: processId ?? null,
      }))
      .filter(item => Boolean(item.description))
  }

  function mapSourceFromDetection (detectionSource: DetectionSource): NCSource {
    if (detectionSource === 'customer_complaint') return 'reclamation'
    return 'interne'
  }

  function getProcessTitle (processId: number | null): string {
    const found = processOptions.value.find(item => item.value === processId)
    return found?.title || 'Processus'
  }

  function resetNcForm (): void {
    ncForm.processId = processOptions.value[0]?.value || null
    ncForm.date = new Date().toISOString().slice(0, 10)
    ncForm.typeConstat = 'non_conformity'
    ncForm.sourceDetection = 'internal_control'
    ncForm.typeNc = 'N-C mineure'
    ncForm.requirement = ''
    ncForm.description = ''
    ncForm.cause = ''
    ncForm.status = 'open'
    ncForm.implicatedIds = []
    ncForm.result = 'En attente'
    ncActions.value = [buildActionDraft()]
  }

  function resetReclamationForm (): void {
    reclamationForm.reference = `REC-${new Date().getFullYear()}-${String(Date.now()).slice(-5)}`
    reclamationForm.receivedDate = new Date().toISOString().slice(0, 10)
    reclamationForm.type = 'service'
    reclamationForm.customerName = ''
    reclamationForm.customerEmail = ''
    reclamationForm.customerPhone = ''
    reclamationForm.address = ''
    reclamationForm.wantsMail = true
    reclamationForm.dueDate = ''
    reclamationForm.title = ''
    reclamationForm.description = ''
    reclamationForm.requestedSolutions = ''
    reclamationForm.probableCauses = ''
    reclamationForm.assignedTo = null
    reclamationForm.status = 'pending'
    reclamationActions.value = [buildActionDraft()]
  }

  async function loadProcesses (): Promise<void> {
    const response = await processService.getProcesses({ site_id: currentSiteId.value || undefined }, 1, 500)
    const rows = Array.isArray(response?.data) ? response.data : []
    processOptions.value = rows.map((item: any) => ({
      value: Number(item.id),
      title: String(item.title || item.name || item.code || `Processus #${item.id}`),
    }))
    if (!ncForm.processId && processOptions.value.length > 0) {
      ncForm.processId = processOptions.value[0]?.value || null
    }
    if (!reclamationForm.processId && processOptions.value.length > 0) {
      reclamationForm.processId = processOptions.value[0]?.value || null
    }
  }

  async function loadUsers (): Promise<void> {
    const { data } = await api.get('/users/job-description-collaborators', {
      params: { site_id: currentSiteId.value || undefined },
    })
    const rows = Array.isArray(data?.data) ? data.data : []
    userOptions.value = rows.map((u: any) => ({
      id: Number(u.id),
      name: String(u.full_name || `${u.last_name || ''} ${u.first_name || ''}`.trim() || u.name || u.email || `User #${u.id}`),
    }))
  }

  async function loadNonConformities (): Promise<void> {
    if (!currentSiteId.value) {
      nonConformities.value = []
      return
    }
    loadingNc.value = true
    try {
      await fetchNonConformities({
        site_id: currentSiteId.value,
        per_page: 200,
      })
      rawNonConformities.value = Array.isArray(ncs.value) ? ncs.value : []
      nonConformities.value = ncs.value.map(nc => mapNcRow(nc))
    } finally {
      loadingNc.value = false
    }
  }

  async function loadReclamations (): Promise<void> {
    loadingReclamations.value = true
    try {
      const response = await complaintService.getComplaints({
        site_id: currentSiteId.value || undefined,
        per_page: 10,
      })
      reclamations.value = Array.isArray(response?.data) ? response.data : []
    } finally {
      loadingReclamations.value = false
    }
  }

  function getRawNcById (id: number): any | null {
    return rawNonConformities.value.find(item => Number(item.id) === Number(id)) || null
  }

  function openEditNc (id: number) {
    const nc = getRawNcById(id)
    if (!nc) {
      toast.error('Fiche NC introuvable.')
      return
    }

    editingNcId.value = Number(nc.id)
    activeTab.value = 'nc'
    ncForm.processId = nc.process_id ?? null
    ncForm.date = nc.detected_date || new Date().toISOString().slice(0, 10)
    ncForm.typeConstat = nc.finding_type === 'gap' ? 'gap' : 'non_conformity'
    ncForm.sourceDetection = nc.detection_source || 'internal_control'
    ncForm.typeNc = nc.type === 'majeure' ? 'N-C majeure' : 'N-C mineure'
    ncForm.requirement = nc.requirement_reference || ''
    ncForm.description = nc.description || ''
    ncForm.cause = nc.root_cause || ''
    ncForm.result = nc.result_summary || 'En attente'
    ncForm.status = mapStatusUi(nc.status)

    const structured = Array.isArray(nc.actions) && nc.actions.length > 0
      ? nc.actions.map((action: any) => ({
        description: String(action.description || action.title || '').trim(),
        responsibleId: action.responsible_id ?? action.responsible?.id ?? null,
        deadline: String(action.deadline || '').slice(0, 10),
        type: action.type || 'corrective',
      })).filter((action: any) => Boolean(action.description))
      : []
    const fromText = structured.length === 0 && String(nc.corrective_action || nc.immediate_action || '').trim()
      ? String(nc.corrective_action || nc.immediate_action || '').trim().split(/\r?\n/).map((item: string) => item.trim()).filter(Boolean).map((item: string) => ({
        description: item,
        responsibleId: nc.responsible_id ?? null,
        deadline: String(nc.due_date || '').slice(0, 10),
        type: 'corrective',
      }))
      : []

    ncActions.value = structured.length > 0 ? structured : fromText
  }

  function normalizeReclamationStatus (value?: string): ReclamationStatus {
    switch (value) {
      case 'pending':
      case 'in_progress':
      case 'resolved':
      case 'closed': {
        return value
      }
      default: {
        return 'pending'
      }
    }
  }

  async function openEditReclamation (item: any) {
    if (!item) return
    const reclamationId = Number(item.id)
    editingReclamationId.value = reclamationId
    activeTab.value = 'complaint'
    let payload = item
    try {
      payload = await complaintService.getComplaint(reclamationId)
    } catch {}

    reclamationForm.reference = payload.reference || payload.ref || reclamationForm.reference
    reclamationForm.receivedDate = (payload.received_date || new Date().toISOString()).slice(0, 10)
    reclamationForm.type = payload.category || payload.type || 'service'
    reclamationForm.customerName = payload.client_name || payload.customer_name || ''
    reclamationForm.customerEmail = payload.client_email || payload.customer_email || ''
    reclamationForm.customerPhone = payload.client_phone || payload.customer_phone || ''
    reclamationForm.address = payload.customer_address || payload.address || ''
    reclamationForm.wantsMail = Boolean(payload.wants_mail)
    reclamationForm.dueDate = (payload.due_date || '').slice(0, 10)
    reclamationForm.title = payload.title || ''
    reclamationForm.description = payload.description || ''
    reclamationForm.requestedSolutions = payload.expected_solution || payload.recommandations || ''
    reclamationForm.probableCauses = payload.analysis || ''
    reclamationForm.assignedTo = payload.assigned_to ?? null
    reclamationForm.status = normalizeReclamationStatus(payload.status)
    reclamationForm.processId = payload.actions?.[0]?.process_id ?? payload.actions?.[0]?.process?.id ?? reclamationForm.processId

    const structured = Array.isArray(payload.actions) && payload.actions.length > 0
      ? payload.actions.map((action: any) => ({
        description: String(action.description || action.title || '').trim(),
        responsibleId: action.responsible_id ?? action.responsible?.id ?? null,
        deadline: String(action.deadline || '').slice(0, 10),
        type: action.type || 'corrective',
      })).filter((action: any) => Boolean(action.description))
      : []
    reclamationActions.value = structured.length > 0 ? structured : [buildActionDraft()]
  }

  async function submitNonConformity (): Promise<void> {
    if (!currentSiteId.value) {
      toast.error('Sélectionnez un site avant de créer une fiche.')
      return
    }
    if (!ncForm.processId) {
      toast.error('Le processus est obligatoire.')
      return
    }
    if (!ncForm.date) {
      toast.error('La date d’ouverture est obligatoire.')
      return
    }
    if (!ncForm.typeConstat) {
      toast.error('Le type de constat est obligatoire.')
      return
    }
    if (!ncForm.sourceDetection) {
      toast.error('La source de détection est obligatoire.')
      return
    }
    if (!ncForm.typeNc) {
      toast.error('Le type de NC est obligatoire.')
      return
    }
    if (!ncForm.requirement.trim()) {
      toast.error('L’exigence non respectée est obligatoire.')
      return
    }
    if (!ncForm.description.trim()) {
      toast.error('La description est obligatoire.')
      return
    }
    if (!ncForm.cause.trim()) {
      toast.error('Les causes sont obligatoires.')
      return
    }
    const actions = normalizeActions(ncActions.value, ncForm.processId)
    if (actions.some(action => !action.responsible_id || !action.deadline)) {
      toast.error('Chaque action doit avoir un responsable et un délai.')
      return
    }

    savingNc.value = true
    try {
      const isMajor = ncForm.typeNc === 'N-C majeure'
      const payload = {
        title: `${ncForm.typeConstat === 'gap' ? 'Écart' : 'Non-conformité'} - ${getProcessTitle(ncForm.processId)}`,
        description: ncForm.description || 'Description non renseignée',
        type: (isMajor ? 'majeure' : 'mineure') as 'majeure' | 'mineure',
        source: mapSourceFromDetection(ncForm.sourceDetection),
        site_id: currentSiteId.value,
        process_id: ncForm.processId || undefined,
        detected_date: ncForm.date || new Date().toISOString().slice(0, 10),
        severity: isMajor ? 4 : 2,
        root_cause: ncForm.cause,
        corrective_action: actions.length > 0 ? actions.map(item => item.description).join('\n') : undefined,
        responsible_id: actions[0]?.responsible_id || undefined,
        due_date: actions[0]?.deadline || undefined,
        actions: actions.length > 0
          ? actions.map(action => ({
            type: action.type,
            description: action.description,
            responsible_id: action.responsible_id,
            deadline: action.deadline,
            process_id: action.process_id || undefined,
          }))
          : undefined,
        requirement_reference: ncForm.requirement,
        finding_type: (ncForm.typeConstat === 'gap' ? 'gap' : 'non_conformity') as 'gap' | 'non_conformity',
        detection_source: ncForm.sourceDetection,
        result_summary: ncForm.result,
      }

      if (editingNcId.value) {
        await updateNonConformity(editingNcId.value, payload)
        if (ncForm.status) {
          await updateStatus(editingNcId.value, { status: mapStatusForApi(ncForm.status) })
        }
        toast.success('Fiche de non-conformité mise à jour.')
      } else {
        await createNonConformity(payload)
        toast.success('Fiche de non-conformité enregistrée.')
      }

      await loadNonConformities()
      resetNcForm()
      editingNcId.value = null
    } catch (error: any) {
      const message = error?.response?.data?.message || error?.message
      toast.error(message || 'Enregistrement de la fiche NC impossible.')
    } finally {
      savingNc.value = false
    }
  }

  async function submitReclamation (): Promise<void> {
    if (!currentSiteId.value) {
      toast.error('Aucun site actif sélectionné.')
      return
    }
    if (!reclamationForm.customerName.trim() || !reclamationForm.title.trim() || !reclamationForm.description.trim()) {
      toast.error('Nom client, objet et description sont obligatoires.')
      return
    }
    const actions = normalizeActions(reclamationActions.value, reclamationForm.processId)
    if (actions.length > 0 && !reclamationForm.processId) {
      toast.error('Le processus concerné est obligatoire pour enregistrer les actions.')
      return
    }
    if (actions.some(action => !action.responsible_id || !action.deadline)) {
      toast.error('Chaque action doit avoir un responsable et un délai.')
      return
    }
    if (actions.length === 0) {
      reclamationActions.value = [buildActionDraft()]
    }

    savingReclamation.value = true
    try {
      const payload = {
        site_id: currentSiteId.value,
        customer_name: reclamationForm.customerName.trim(),
        customer_email: reclamationForm.customerEmail || undefined,
        customer_phone: reclamationForm.customerPhone || undefined,
        client_name: reclamationForm.customerName.trim(),
        client_email: reclamationForm.customerEmail || undefined,
        client_phone: reclamationForm.customerPhone || undefined,
        client_company: reclamationForm.customerName.trim() || undefined,
        category: reclamationForm.type,
        title: reclamationForm.title.trim(),
        description: reclamationForm.description.trim(),
        received_date: reclamationForm.receivedDate || undefined,
        due_date: reclamationForm.dueDate || undefined,
        wants_mail: reclamationForm.wantsMail,
        expected_solution: reclamationForm.requestedSolutions || undefined,
        analysis: reclamationForm.probableCauses || undefined,
        actions: actions.length > 0
          ? actions.map(action => ({
            type: action.type,
            description: action.description,
            responsible_id: action.responsible_id,
            deadline: action.deadline,
            process_id: action.process_id || undefined,
          }))
          : undefined,
        assigned_to: reclamationForm.assignedTo || undefined,
        status: (reclamationForm.status || 'pending') as 'pending' | 'in_progress' | 'resolved' | 'closed',
      }

      if (editingReclamationId.value) {
        await complaintService.updateComplaint(editingReclamationId.value, payload)
        toast.success('Fiche de plainte/réclamation mise à jour.')
      } else {
        await complaintService.createComplaint(payload)
        toast.success('Fiche de plainte/réclamation enregistrée.')
      }
      await loadReclamations()
      editingReclamationId.value = null
      resetReclamationForm()
    } catch (error: any) {
      const message = error?.response?.data?.message || error?.message
      toast.error(message || 'Enregistrement de la fiche réclamation impossible.')
    } finally {
      savingReclamation.value = false
    }
  }

  onMounted(async () => {
    const tabKey = activeTab.value === 'nc' ? 'nc' : 'complaint'
    showHowItWorks.value = localStorage.getItem(`control_nc_howitworks_seen_${tabKey}`) !== '1'

    try {
      await Promise.all([
        loadProcesses(),
        loadUsers(),
        loadNonConformities(),
        loadReclamations(),
      ])
    } catch {
      toast.error('Chargement initial impossible.')
    }
  })

  function openHowItWorks () {
    showHowItWorks.value = true
  }

  function dismissHowItWorks () {
    const tabKey = activeTab.value === 'nc' ? 'nc' : 'complaint'
    localStorage.setItem(`control_nc_howitworks_seen_${tabKey}`, '1')
    showHowItWorks.value = false
  }
</script>

<style scoped>
.control-nc-page {
  max-width: 100%;
  margin: 0 auto;
  padding: 0 2px 24px;
}

@media (max-width: 600px) {
  .control-nc-page {
    padding: 0 0 20px;
  }
}
</style>
