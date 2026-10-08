<template>
  <ClientALayout current-page="risks-opportunities">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-shield-alert"
        subtitle="Pilotage des risques et opportunités du site"
        title="Risques & Opportunités"
      />

      <RisksHero
        :average-priority-label="averagePriorityLabel"
        :avg-criticite="Number(avgCriticite)"
        :completion-rate="completionRate"
        :high-priority-count="highPriorityCount"
        :score-color="scoreColor"
        :total-items="modeItems.length"
        :view-mode="viewMode"
        @add="openCreateDialog"
        @export="handleExport"
      />

      <RisksWidgets
        :closed-count="closedCount"
        :high-priority-count="highPriorityCount"
        :in-progress-count="inProgressCount"
        :total="filteredItems.length"
      />

      <RisksFiltersBar
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        :niveau-options="niveauOptions"
        :process-options="processOptions"
        :status-items="statusItems"
        :view-mode="viewMode"
        :view-type="viewType"
        @reset="resetFilters"
        @update:filters="applyFilters"
        @update:view-mode="viewMode = $event"
        @update:view-type="viewType = $event"
      />

      <RisksEmptyState
        v-if="filteredItems.length === 0"
        :message="emptyStateMessage"
        :view-mode="viewMode"
        @add="openCreateDialog"
      />

      <RisksGrid
        v-else-if="viewType === 'grid'"
        :items="filteredItems"
        :priority-label="priorityLabel"
        :score-color="scoreColor"
        :status-color="statusColor"
        :status-items="statusItems"
        :view-mode="viewMode"
        @delete="removeItem"
        @edit="openEditDialog"
        @view="goToDetail"
      />

      <RisksTable
        v-else
        :empty-state-message="emptyStateMessage"
        :headers="tableHeaders"
        :items="filteredItems"
        :loading="loading"
        :score-color="scoreColor"
        :status-color="statusColor"
        :status-items="statusItems"
        :view-mode="viewMode"
        @delete="removeItem"
        @edit="openEditDialog"
        @view="goToDetail"
      />

      <RiskOpportunityDialog
        v-model="showDialog"
        :form="form"
        :frequency-options="frequencyOptions"
        :is-editing="Boolean(editingItem?.id)"
        :process-options="processOptions"
        :saving="saving"
        :score-type="scoreType"
        :status-items="statusItems"
        :user-options="userOptions"
        :view-mode="viewMode"
        @add-action="addDialogAction"
        @remove-action="removeDialogAction"
        @save="saveItem"
      />
    </v-container>
  </ClientALayout>
</template>
<script setup lang="ts">
  import { computed, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useDisplay } from 'vuetify'
  import * as XLSX from 'xlsx'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import RiskOpportunityDialog from '@/modules/clienta/pages/iso/planning/components/RiskOpportunityDialog.vue'
  import RisksEmptyState from '@/modules/clienta/pages/iso/planning/components/RisksEmptyState.vue'
  import RisksFiltersBar from '@/modules/clienta/pages/iso/planning/components/RisksFiltersBar.vue'
  import RisksGrid from '@/modules/clienta/pages/iso/planning/components/RisksGrid.vue'
  import RisksHero from '@/modules/clienta/pages/iso/planning/components/RisksHero.vue'
  import RisksTable from '@/modules/clienta/pages/iso/planning/components/RisksTable.vue'
  import RisksWidgets from '@/modules/clienta/pages/iso/planning/components/RisksWidgets.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const router = useRouter()
  const authStore = useAuthStore()
  const { mdAndDown } = useDisplay()
  const isMobile = computed(() => mdAndDown.value)

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const viewMode = ref<'risque' | 'opportunite'>('risque')
  const viewType = ref<'grid' | 'list'>('grid')
  const editingItem = ref<any>(null)

  const tableHeaders = computed(() => (
    isMobile.value
      ? [
        { title: 'Code', key: 'code', sortable: true, width: '84px' },
        { title: 'Description', key: 'description', sortable: true },
        { title: 'Score', key: 'score', sortable: true, width: '88px' },
        { title: 'Statut', key: 'status', sortable: true, width: '110px' },
        { title: 'Actions', key: 'actions', sortable: false, width: '122px', align: 'end' as const },
      ]
      : [
        { title: 'Code', key: 'code', sortable: true, width: '92px' },
        { title: 'Processus', key: 'process_name', sortable: true, width: '150px' },
        { title: 'Description', key: 'description', sortable: true },
        { title: 'Évaluation', key: 'evaluation', sortable: false, width: '136px' },
        { title: viewMode.value === 'risque' ? 'Criticité' : 'Priorité', key: 'score', sortable: true, width: '90px' },
        { title: 'Responsable', key: 'responsable_name', sortable: true, width: '145px' },
        { title: 'Statut', key: 'status', sortable: true, width: '122px' },
        { title: 'Actions', key: 'actions', sortable: false, width: '122px', align: 'end' as const },
      ]
  ))

  const items = ref<any[]>([])
  const rawItems = ref<any[]>([])
  const processes = ref<Array<{ id: number, name?: string, title?: string }>>([])
  const users = ref<Array<{ id: number, name: string }>>([])
  const isPageLoading = ref(false)
  const loadedSiteId = ref<number | null>(null)

  const statusItems = [
    { value: 'identifie', label: 'Identifié' },
    { value: 'en_cours', label: 'En cours' },
    { value: 'traite', label: 'Traité' },
    { value: 'surveille', label: 'Surveillé' },
    { value: 'cloture', label: 'Clôturé' },
  ]

  const niveauOptions = [
    { label: 'Faible', value: 'faible' },
    { label: 'Moyen', value: 'moyen' },
    { label: 'Élevé', value: 'eleve' },
    { label: 'Critique', value: 'critique' },
  ]

  const frequencyOptions = [
    { label: 'En continu', value: 'En continu' },
    { label: 'Hebdomadaire', value: 'Hebdomadaire' },
    { label: 'Bihebdomadaire', value: 'Bihebdomadaire' },
    { label: 'Mensuel', value: 'Mensuel' },
    { label: 'Bimestriel', value: 'Bimestriel' },
    { label: 'Trimestriel', value: 'Trimestriel' },
    { label: 'Quadrimestriel', value: 'Quadrimestriel' },
    { label: 'Semestriel', value: 'Semestriel' },
    { label: 'Annuel', value: 'Annuel' },
    { label: 'Biennal', value: 'Biennal' },
  ]

  const filters = reactive({
    search: '',
    process_id: null as number | null,
    status: null as string | null,
    niveau: null as string | null,
  })

  const form = reactive({
    process_id: null as number | null,
    description: '',
    cause: '',
    probabilite: 1,
    gravite: 1,
    status: 'identifie',
    actions: [] as Array<{
      description: string
      responsible_user_id: number | null
      implicated_user_ids: number[]
      deadline_frequency: string
      showDatePicker: boolean
    }>,
  })

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
  }

  const processOptions = computed(() => processes.value.map(process => ({
    title: process.title || process.name || `Processus #${process.id}`,
    value: process.id,
  })))

  const userOptions = computed(() => users.value.map((user: any) => ({
    title: user.name,
    value: user.id,
  })))

  function applyFilters (nextFilters: {
    search: string
    process_id: number | null
    status: string | null
    niveau: string | null
  }) {
    Object.assign(filters, nextFilters)
  }

  function addDialogAction () {
    form.actions.unshift({
      description: '',
      responsible_user_id: null,
      implicated_user_ids: [],
      deadline_frequency: '',
      showDatePicker: false,
    })
  }

  function removeDialogAction (index: number) {
    if (index < 0 || index >= form.actions.length) return
    form.actions.splice(index, 1)
  }

  const modeItems = computed(() => items.value.filter(item => item.type === viewMode.value))
  const highPriorityCount = computed(() => modeItems.value.filter(item => Number(item.score) >= 15).length)
  const inProgressCount = computed(() => modeItems.value.filter(item => ['en_cours', 'surveille'].includes(item.status)).length)
  const closedCount = computed(() => modeItems.value.filter(item => ['traite', 'cloture'].includes(item.status)).length)
  const completionRate = computed(() => {
    if (modeItems.value.length === 0) return 0
    return Math.round((closedCount.value / modeItems.value.length) * 100)
  })

  const avgCriticite = computed(() => {
    if (modeItems.value.length === 0) return 0
    const total = modeItems.value.reduce((sum, item) => sum + (item.score || 0), 0)
    return Math.round((total / modeItems.value.length) * 10) / 10
  })
  const averagePriorityLabel = computed(() => priorityLabel(Number(avgCriticite.value)))

  const filteredItems = computed(() => {
    const search = filters.search.trim().toLowerCase()
    return items.value
      .filter(item => item.type === viewMode.value)
      .filter(item => !filters.process_id || Number(item.process_id) === Number(filters.process_id))
      .filter(item => !filters.status || item.status === filters.status)
      .filter(item => !filters.niveau || item.niveau === filters.niveau)
      .filter(item => {
        if (!search) return true
        const text = `${item.description || ''} ${item.process_name || ''} ${item.cause || ''}`.toLowerCase()
        return text.includes(search)
      })
  })
  const hasActiveFilters = computed(() => {
    return Boolean(filters.search.trim() || filters.process_id || filters.status || filters.niveau)
  })
  const emptyStateMessage = computed(() => {
    if (modeItems.value.length === 0) {
      return viewMode.value === 'risque'
        ? 'Aucun risque enregistré pour ce site. Ajoutez votre premier risque.'
        : 'Aucune opportunité enregistrée pour ce site. Ajoutez votre première opportunité.'
    }

    return 'Ajustez les filtres ou créez un nouvel élément.'
  })

  function resetFilters () {
    filters.search = ''
    filters.process_id = null
    filters.status = null
    filters.niveau = null
  }

  function parsePlannedActions (value: any): any[] {
    if (!value) return []
    if (Array.isArray(value)) return value
    if (typeof value !== 'string') return []
    try {
      const parsed = JSON.parse(value)
      return Array.isArray(parsed) ? parsed : []
    } catch {
      return []
    }
  }

  function resolveUserName (id: number | null | undefined): string {
    if (!id) return ''
    return users.value.find(user => user.id === id)?.name || ''
  }

  function uniqueNames (names: Array<string | undefined | null>): string[] {
    const unique = new Set<string>()
    for (const name of names) {
      if (name) unique.add(name)
    }
    return Array.from(unique)
  }

  function buildActionsText (plannedActions: any[]): string {
    const lines = plannedActions.map((action: any, index: number) => {
      const parts = [`Action ${index + 1}: ${action.description || ''}`]
      const responsibleName = resolveUserName(action.responsible_user_id)
      if (responsibleName) parts.push(`Responsable: ${responsibleName}`)

      if (Array.isArray(action.implicated_user_ids) && action.implicated_user_ids.length > 0) {
        const implNames = action.implicated_user_ids
          .map((id: number) => resolveUserName(id))
          .filter(Boolean)
          .join(', ')
        if (implNames) parts.push(`Impliqués: ${implNames}`)
      }

      if (action.deadline_frequency) parts.push(`Échéance: ${action.deadline_frequency}`)
      return parts.join(' | ')
    })
    return lines.join('\n')
  }

  function resolveResponsables (plannedActions: any[]): string {
    const names = plannedActions
      .map((action: any) => resolveUserName(action.responsible_user_id))
      .filter(Boolean)
    return uniqueNames(names).join(', ')
  }

  function resolveImplicated (plannedActions: any[]): string {
    const names = plannedActions
      .flatMap((action: any) => action.implicated_user_ids || [])
      .map((id: number) => resolveUserName(id))
      .filter(Boolean)
    return uniqueNames(names).join(', ')
  }

  function resolveDeadlines (plannedActions: any[]): string {
    return plannedActions
      .map((action: any) => action.deadline_frequency)
      .filter(Boolean)
      .join(', ')
  }

  function mapItem (item: any) {
    const process = item.process || {}
    const probabilite = Number(item.probabilite || 1)
    const gravite = Number(item.gravite || 1)
    const plannedActions = parsePlannedActions(item.planned_actions)

    const firstAction = plannedActions[0] || {}
    const responsibleId = Number(firstAction.responsible_user_id || 0)
    const responsibleName = resolveUserName(responsibleId)
    const implicatedIds = Array.isArray(firstAction.implicated_user_ids) ? firstAction.implicated_user_ids : []

    const allActionsText = buildActionsText(plannedActions)
    const allResponsibles = resolveResponsables(plannedActions)
    const allImplicated = resolveImplicated(plannedActions)
    const allDeadlines = resolveDeadlines(plannedActions)

    return {
      id: Number(item.id),
      code: item.code || item.id,
      type: item.type,
      process_id: Number(item.process_id),
      process_name: process.title || process.name || `Processus #${item.process_id}`,
      description: item.description || item.title || '',
      cause: item.cause || '',
      probabilite,
      gravite,
      score: Number(item.criticite || probabilite * gravite),
      status: item.status || 'identifie',
      actions_prevues: allActionsText || firstAction.description || '',
      responsible_user_id: responsibleId || null,
      responsable_name: allResponsibles || responsibleName,
      responsables_implique_ids: implicatedIds,
      responsables_implique_display: allImplicated,
      delai_frequence: allDeadlines || firstAction.deadline_frequency || '',
    }
  }

  function setSheetCellValue (
    sheet: XLSX.WorkSheet,
    address: string,
    value: string | number,
  ) {
    const existingCell = sheet[address]
    if (existingCell) {
      existingCell.v = value
      existingCell.t = typeof value === 'number' ? 'n' : 's'
      delete existingCell.w
      return
    }

    sheet[address] = {
      t: typeof value === 'number' ? 'n' : 's',
      v: value,
    } as XLSX.CellObject
  }

  function ensureSheetRange (sheet: XLSX.WorkSheet, row: number, col: number) {
    const range = sheet['!ref']
      ? XLSX.utils.decode_range(sheet['!ref'])
      : { s: { c: col, r: row }, e: { c: col, r: row } }

    range.s.r = Math.min(range.s.r, row)
    range.s.c = Math.min(range.s.c, col)
    range.e.r = Math.max(range.e.r, row)
    range.e.c = Math.max(range.e.c, col)

    sheet['!ref'] = XLSX.utils.encode_range(range)
  }

  function writeCell (
    sheet: XLSX.WorkSheet,
    rowIndex: number,
    colIndex: number,
    value: string | number | null | undefined = '',
  ) {
    const finalValue = value ?? ''
    const cellAddress = XLSX.utils.encode_cell({ r: rowIndex - 1, c: colIndex })
    setSheetCellValue(sheet, cellAddress, finalValue)
    ensureSheetRange(sheet, rowIndex - 1, colIndex)
  }

  function normalizeHexColor (value: string): string {
    return value.replace('#', '').toUpperCase()
  }

  function getScorePalette (value: number, type: 'risque' | 'opportunite') {
    if (type === 'opportunite') {
      if (value >= 15) return { color: '#1565C0', text: '#FFFFFF' }
      if (value >= 8) return { color: '#00897B', text: '#FFFFFF' }
      return { color: '#7CB342', text: '#0F172A' }
    }

    if (value >= 15) return { color: '#D32F2F', text: '#FFFFFF' }
    if (value >= 8) return { color: '#F57C00', text: '#0F172A' }
    return { color: '#2E7D32', text: '#FFFFFF' }
  }

  function applyFilledCellStyle (
    sheet: XLSX.WorkSheet,
    rowIndex: number,
    colIndex: number,
    backgroundColor: string,
    textColor: string,
  ) {
    const cellAddress = XLSX.utils.encode_cell({ r: rowIndex - 1, c: colIndex })
    const currentCell = (sheet[cellAddress] || {}) as XLSX.CellObject & { s?: Record<string, any> }

    sheet[cellAddress] = {
      ...currentCell,
      s: {
        ...currentCell.s,
        fill: {
          patternType: 'solid',
          fgColor: { rgb: normalizeHexColor(backgroundColor) },
          bgColor: { rgb: normalizeHexColor(backgroundColor) },
        },
        font: {
          ...currentCell.s?.font,
          bold: true,
          color: { rgb: normalizeHexColor(textColor) },
        },
        alignment: {
          ...currentCell.s?.alignment,
          horizontal: 'center',
          vertical: 'center',
        },
      },
    }
  }

  function cloneRowStyleFromTemplate (
    sheet: XLSX.WorkSheet,
    sourceRowIndex: number,
    targetRowIndex: number,
    fromColIndex: number,
    toColIndex: number,
  ) {
    for (let colIndex = fromColIndex; colIndex <= toColIndex; colIndex++) {
      const sourceAddress = XLSX.utils.encode_cell({ r: sourceRowIndex - 1, c: colIndex })
      const targetAddress = XLSX.utils.encode_cell({ r: targetRowIndex - 1, c: colIndex })
      const sourceCell = sheet[sourceAddress] as XLSX.CellObject | undefined
      const targetCell = sheet[targetAddress] as XLSX.CellObject | undefined

      if (!sourceCell) continue

      const clonedCell: XLSX.CellObject = {
        ...sourceCell,
        v: '',
        t: sourceCell.t || 's',
      }
      delete clonedCell.w

      sheet[targetAddress] = targetCell
        ? {
          ...targetCell,
          s: clonedCell.s,
          z: clonedCell.z,
          t: clonedCell.t,
          v: '',
        }
        : clonedCell

      ensureSheetRange(sheet, targetRowIndex - 1, colIndex)
    }
  }

  function mergeProcessCells (
    sheet: XLSX.WorkSheet,
    startRow: number,
    endRow: number,
  ) {
    if (endRow <= startRow) return

    const merges = sheet['!merges'] || []
    merges.push({
      s: { r: startRow - 1, c: 2 }, // C
      e: { r: endRow - 1, c: 2 }, // C
    })
    sheet['!merges'] = merges
  }

  function buildExportRowsByProcess () {
    const grouped = new Map<number, {
      processName: string
      risks: any[]
      opportunities: any[]
    }>()

    for (const rawItem of rawItems.value) {
      const processId = Number(rawItem.process_id || 0)
      const process = rawItem.process || {}
      const processName = process.title || process.name || `Processus #${processId}`

      if (!grouped.has(processId)) {
        grouped.set(processId, {
          processName,
          risks: [],
          opportunities: [],
        })
      }

      const group = grouped.get(processId)!

      let plannedActions = rawItem.planned_actions
      if (typeof plannedActions === 'string') {
        try {
          plannedActions = JSON.parse(plannedActions)
        } catch {
          plannedActions = []
        }
      }
      if (!Array.isArray(plannedActions)) plannedActions = []

      const actionsToExport = plannedActions.length > 0 ? plannedActions : [{}]

      for (const [actionIndex, action] of actionsToExport.entries()) {
        const responsibleName = action.responsible_user_id
          ? users.value.find(u => u.id === action.responsible_user_id)?.name || ''
          : ''

        const implicatedNames = Array.isArray(action.implicated_user_ids)
          ? action.implicated_user_ids
            .map((id: number) => users.value.find(u => u.id === id)?.name)
            .filter(Boolean)
            .join(', ')
          : ''

        const exportRow = {
          id: rawItem.id,
          code: rawItem.code || rawItem.id,
          type: rawItem.type,
          description: rawItem.description || rawItem.title || '',
          cause: rawItem.cause || '',
          probabilite: Number(rawItem.probabilite || 1),
          gravite: Number(rawItem.gravite || 1),
          score: Number(rawItem.criticite || (rawItem.probabilite * rawItem.gravite)),
          action_description: action.description || '',
          action_responsible: responsibleName,
          action_implicated: implicatedNames,
          action_deadline: action.deadline_frequency || '',
          is_first_action: actionIndex === 0,
          actions_count: actionsToExport.length,
        }

        if (rawItem.type === 'risque') {
          group.risks.push(exportRow)
        } else if (rawItem.type === 'opportunite') {
          group.opportunities.push(exportRow)
        }
      }
    }

    return Array.from(grouped.values()).toSorted((a: any, b: any) => a.processName.localeCompare(b.processName))
  }

  async function loadExportWorkbook () {
    const response = await fetch('/plan-maitrise-risques-opportunites-template.xlsx')
    if (!response.ok) throw new Error('template_unavailable')

    const fileBuffer = await response.arrayBuffer()
    const workbook = XLSX.read(fileBuffer, { type: 'array', cellStyles: true })
    const sheetName = workbook.SheetNames[0]
    if (!sheetName) throw new Error('no_sheet_found')
    const sheet = workbook.Sheets[sheetName]
    if (!sheet) throw new Error('sheet_not_found')

    return { workbook, sheet }
  }

  function fillExportRow (
    sheet: XLSX.WorkSheet,
    excelRow: number,
    sequence: number,
    processName: string,
    isFirstOfGroup: boolean,
    risk?: any,
    opportunity?: any,
  ) {
    const value = (source: any, key: string) => source && source[key] ? source[key] : ''

    cloneRowStyleFromTemplate(sheet, 7, excelRow, 1, 23)

    writeCell(sheet, excelRow, 1, sequence) // B - N°
    writeCell(sheet, excelRow, 2, isFirstOfGroup ? processName : '') // C - Processus
    writeCell(sheet, excelRow, 3, value(risk, 'description')) // D - Risque
    writeCell(sheet, excelRow, 4, value(risk, 'cause')) // E - Causes profondes
    writeCell(sheet, excelRow, 5, value(risk, 'probabilite')) // F
    writeCell(sheet, excelRow, 6, value(risk, 'gravite')) // G
    writeCell(sheet, excelRow, 7, value(risk, 'score')) // H
    writeCell(sheet, excelRow, 8, value(risk, 'actions_prevues')) // I
    writeCell(sheet, excelRow, 9, value(risk, 'responsable_name')) // J
    writeCell(sheet, excelRow, 10, value(risk, 'responsables_implique_display')) // K
    writeCell(sheet, excelRow, 11, value(risk, 'delai_frequence')) // L
    writeCell(sheet, excelRow, 12, '') // M - Efficacité (non saisi ici)
    writeCell(sheet, excelRow, 13, '') // N - Commentaires (non saisi ici)

    writeCell(sheet, excelRow, 14, value(opportunity, 'description')) // O - Opportunité
    writeCell(sheet, excelRow, 15, value(opportunity, 'probabilite')) // P
    writeCell(sheet, excelRow, 16, value(opportunity, 'gravite')) // Q
    writeCell(sheet, excelRow, 17, value(opportunity, 'score')) // R
    writeCell(sheet, excelRow, 18, value(opportunity, 'actions_prevues')) // S
    writeCell(sheet, excelRow, 19, value(opportunity, 'responsable_name')) // T
    writeCell(sheet, excelRow, 20, value(opportunity, 'responsables_implique_display')) // U
    writeCell(sheet, excelRow, 21, value(opportunity, 'delai_frequence')) // V
    writeCell(sheet, excelRow, 22, '') // W - Efficacité (non saisi ici)
    writeCell(sheet, excelRow, 23, '') // X - Commentaires (non saisi ici)

    if (risk?.score !== undefined && risk?.score !== null && risk?.score !== '') {
      const palette = getScorePalette(Number(risk.score), 'risque')
      applyFilledCellStyle(sheet, excelRow, 7, palette.color, palette.text)
    }

    if (opportunity?.score !== undefined && opportunity?.score !== null && opportunity?.score !== '') {
      const palette = getScorePalette(Number(opportunity.score), 'opportunite')
      applyFilledCellStyle(sheet, excelRow, 17, palette.color, palette.text)
    }
  }

  function fillSheetByProcessGroups (sheet: XLSX.WorkSheet, groupedRows: Array<any>) {
    let excelRow = 7
    let sequence = 1

    for (const group of groupedRows) {
      const processStartRow = excelRow
      const rowCount = Math.max(group.risks.length, group.opportunities.length, 1)

      for (let i = 0; i < rowCount; i++) {
        fillExportRow(
          sheet,
          excelRow,
          sequence,
          group.processName,
          i === 0,
          group.risks[i],
          group.opportunities[i],
        )
        excelRow += 1
        sequence += 1
      }

      mergeProcessCells(sheet, processStartRow, excelRow - 1)
    }
  }

  function downloadWorkbook (workbook: XLSX.WorkBook) {
    const exportBuffer = XLSX.write(workbook, {
      type: 'array',
      bookType: 'xlsx',
      cellStyles: true,
    })
    const blob = new Blob([exportBuffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `plan_risques_opportunites_${new Date().toISOString().slice(0, 10)}.xlsx`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function handleExport () {
    try {
      const siteId = getCurrentSiteId()
      const response = await api.get('/risks-opportunities/export-xlsx', {
        params: siteId ? { site_id: siteId } : {},
        responseType: 'blob',
      })
      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `plan_risques_opportunites_${new Date().toISOString().slice(0, 10)}.xlsx`
      link.click()
      URL.revokeObjectURL(url)
      toast.success('Export Excel généré.')
    } catch (error) {
      console.error(error)
      toast.error('Impossible de générer l’export Excel.')
    }
  }

  function scoreColor (value: number, type: 'risque' | 'opportunite' = viewMode.value) {
    return getScorePalette(value, type).color
  }

  function scoreType (value: number, type: 'risque' | 'opportunite' = viewMode.value): 'success' | 'error' | 'warning' | 'info' {
    if (type === 'opportunite') {
      if (value >= 15) return 'info'
      if (value >= 8) return 'success'
      return 'info'
    }

    if (value >= 15) return 'error'
    if (value >= 8) return 'warning'
    return 'success'
  }

  function priorityLabel (value: number) {
    if (value >= 15) return 'Critique'
    if (value >= 8) return 'Moyenne'
    return 'Faible'
  }

  function statusColor (status: string) {
    if (status === 'cloture' || status === 'traite') return 'success'
    if (status === 'en_cours' || status === 'surveille') return 'warning'
    return 'info'
  }

  function goToDetail (id: number) {
    router.push(`/company/iso/planning/risks-opportunities/${id}`)
  }

  async function fetchProcesses () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      processes.value = []
      return
    }

    const response = await processService.getProcesses({ site_id: siteId }, 1, 500)
    processes.value = response.data || []
  }

  async function fetchUsers () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      users.value = []
      return
    }

    const response = await api.get('/users', { params: { site_id: siteId, per_page: 300 } })
    const rows = response.data?.data || []
    users.value = rows.map((row: any) => ({
      id: Number(row.id),
      name: row.attributes?.name || row.name || row.attributes?.email || `Utilisateur #${row.id}`,
    }))
  }

  async function fetchItems () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      items.value = []
      rawItems.value = []
      return
    }

    loading.value = true
    try {
      const response = await api.get('/risks-opportunities', { params: { site_id: siteId } })
      const rows = response.data?.data || []
      rawItems.value = rows
      items.value = rows.map((item: any) => mapItem(item))
    } catch (error) {
      console.error(error)
      toast.error('Impossible de charger les risques et opportunités.')
      items.value = []
      rawItems.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadPageData () {
    if (isPageLoading.value) return

    const siteId = getCurrentSiteId()
    if (!siteId) {
      loadedSiteId.value = null
      items.value = []
      processes.value = []
      users.value = []
      return
    }

    isPageLoading.value = true
    try {
      await Promise.all([fetchProcesses(), fetchUsers()])
      await fetchItems()
      loadedSiteId.value = siteId
    } finally {
      isPageLoading.value = false
    }
  }

  function resetForm () {
    form.process_id = null
    form.description = ''
    form.cause = ''
    form.probabilite = 1
    form.gravite = 1
    form.status = 'identifie'
    form.actions = []
  }

  function openCreateDialog () {
    editingItem.value = null
    resetForm()
    showDialog.value = true
  }

  async function openEditDialog (item: any) {
    editingItem.value = item
    form.process_id = item.process_id
    form.description = item.description
    form.cause = item.cause || ''
    form.probabilite = item.probabilite
    form.gravite = item.gravite
    form.status = item.status

    try {
      const response = await api.get(`/process-risks-opportunities/${item.id}`)
      const data = response.data?.data
      let plannedActions = data?.planned_actions || data?.attributes?.planned_actions

      if (typeof plannedActions === 'string') {
        plannedActions = JSON.parse(plannedActions)
      }

      form.actions = Array.isArray(plannedActions) && plannedActions.length > 0
        ? plannedActions.map((action: any) => ({
          description: action.description || '',
          responsible_user_id: action.responsible_user_id || null,
          implicated_user_ids: Array.isArray(action.implicated_user_ids) ? action.implicated_user_ids : [],
          deadline_frequency: action.deadline_frequency || '',
          showDatePicker: false,
        }))
        : []
    } catch (error) {
      console.error('Error loading actions:', error)
      form.actions = []
    }

    showDialog.value = true
  }

  async function saveItem () {
    const hasDescription = Boolean(form.description.trim())
    const hasCause = Boolean(form.cause.trim())

    if (!form.process_id || (viewMode.value === 'risque' ? (!hasDescription && !hasCause) : !hasDescription)) {
      toast.error(viewMode.value === 'risque'
        ? 'Le processus et au moins la description ou les causes profondes sont obligatoires.'
        : 'Le processus et la description sont obligatoires.')
      return
    }

    saving.value = true
    try {
      const cleanActions = form.actions.map(action => ({
        description: action.description,
        responsible_user_id: action.responsible_user_id,
        implicated_user_ids: action.implicated_user_ids,
        deadline_frequency: action.deadline_frequency,
      }))

      const payload = {
        type: viewMode.value,
        title: (form.description.trim() || form.cause.trim() || `${viewMode.value === 'risque' ? 'Risque' : 'Opportunité'} sans description`).slice(0, 255),
        description: form.description.trim() || null,
        cause: viewMode.value === 'risque' ? (form.cause || null) : null,
        probabilite: Number(form.probabilite),
        gravite: Number(form.gravite),
        status: form.status,
        planned_actions: JSON.stringify(cleanActions),
      }

      await (editingItem.value?.id ? api.put(`/risks-opportunities/${editingItem.value.id}`, payload) : api.post(`/processes/${form.process_id}/risks-opportunities`, payload))

      showDialog.value = false
      await fetchItems()
      toast.success('Enregistrement effectué.')
    } catch (error) {
      console.error(error)
      toast.error('Erreur lors de l\'enregistrement.')
    } finally {
      saving.value = false
    }
  }

  async function removeItem (item: any) {
    if (!confirm('Supprimer cet élément ?')) return
    try {
      await api.delete(`/risks-opportunities/${item.id}`)
      await fetchItems()
      toast.success('Élément supprimé.')
    } catch (error) {
      console.error(error)
      toast.error('Erreur lors de la suppression.')
    }
  }

  watch(
    () => authStore.currentSiteId,
    async siteId => {
      if (!siteId) {
        await loadPageData()
        return
      }

      if (loadedSiteId.value === siteId) {
        return
      }

      await loadPageData()
    },
    { immediate: true },
  )
  watch(viewMode, mode => {
    if (mode === 'opportunite') form.cause = ''
  })
</script>
