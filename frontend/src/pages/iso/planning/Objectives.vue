<template>
  <ClientALayout>
    <div class="page-header mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Objectifs QHSE</h1>
          <p class="mt-2 text-gray-600">Définir et suivre les objectifs par site.</p>
        </div>
        <div class="flex items-center gap-2">
          <v-btn
            color="success"
            :disabled="loading || exporting || objectives.length === 0"
            :loading="exporting"
            prepend-icon="mdi-file-excel"
            variant="tonal"
            @click="exportObjectivesTemplate"
          >
            Exporter
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">Ajouter</v-btn>
        </div>
      </div>
    </div>

    <Card padding="none" variant="bordered">
      <v-data-table
        :headers="headers"
        :items="objectives"
        :items-per-page="10"
        :loading="loading"
      >
        <template #item.type="{ item }">
          <Badge size="sm" variant="info">{{ typeLabel(item.type) }}</Badge>
        </template>

        <template #item.responsible="{ item }">
          <span>{{ item.responsible?.name || '—' }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="flex gap-1">
            <v-btn icon="mdi-pencil" size="small" variant="text" @click.stop="openEdit(item)" />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click.stop="handleDelete(item)"
            />
          </div>
        </template>

        <template #no-data>
          <div class="text-center py-12">
            <p class="text-gray-400 mb-4">Aucun objectif enregistré</p>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
              Ajouter le premier
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </Card>

    <v-dialog v-model="showDialog" max-width="720">
      <v-card>
        <v-card-title>{{ editingId ? 'Modifier' : 'Ajouter' }} un objectif</v-card-title>
        <v-card-text>
          <div class="space-y-4">
            <FormField label="Titre" required>
              <v-text-field v-model="form.title" density="compact" variant="outlined" />
            </FormField>

            <FormField label="Type" required>
              <v-select
                v-model="form.type"
                density="compact"
                item-title="title"
                item-value="value"
                :items="objectiveTypes"
                variant="outlined"
              />
            </FormField>

            <FormField label="Description">
              <v-textarea v-model="form.description" rows="3" variant="outlined" />
            </FormField>

            <div class="grid grid-cols-2 gap-4">
              <FormField label="Valeur cible" required>
                <v-text-field v-model.number="form.target_value" density="compact" type="number" variant="outlined" />
              </FormField>
              <FormField label="Unité">
                <v-text-field v-model="form.unit" density="compact" variant="outlined" />
              </FormField>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <FormField label="Échéance" required>
                <v-text-field v-model="form.deadline" density="compact" type="date" variant="outlined" />
              </FormField>
              <FormField label="Responsable" required>
                <v-select
                  v-model="form.responsible_id"
                  density="compact"
                  item-title="name"
                  item-value="id"
                  :items="responsibleUsers"
                  variant="outlined"
                />
              </FormField>
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="handleSave">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import api from '@/api/client'
  import FormField from '@/components/forms/FormField.vue'
  import Badge from '@/components/ui/Badge.vue'
  import Card from '@/components/ui/Card.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import userService from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()

  const objectives = ref<any[]>([])
  const loading = ref(false)
  const exporting = ref(false)
  const showDialog = ref(false)
  const editingId = ref<number | null>(null)
  const responsibleUsers = ref<any[]>([])

  const headers = [
    { title: 'Titre', key: 'title', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Cible', key: 'target_value', sortable: true },
    { title: 'Unité', key: 'unit', sortable: false },
    { title: 'Échéance', key: 'deadline', sortable: true },
    { title: 'Responsable', key: 'responsible', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ] as const

  const objectiveTypes = [
    { title: 'Stratégique', value: 'strategic' },
    { title: 'Opérationnel', value: 'operational' },
    { title: 'Qualité', value: 'quality' },
    { title: 'Sécurité', value: 'safety' },
    { title: 'Environnement', value: 'environmental' },
  ]

  const form = ref({
    title: '',
    type: 'strategic',
    description: '',
    target_value: null as number | null,
    unit: '',
    deadline: '',
    responsible_id: null as number | null,
  })

  const TEMPLATE_SHEET_OBJECTIVES = 'Tableau de bord_ objectifs'
  const TEMPLATE_SHEET_EVALUATION = 'Grille Eva Obj'
  const TEMPLATE_FIRST_DATA_ROW = 8
  const TEMPLATE_FIRST_DATA_COL = 0 // A
  const TEMPLATE_LAST_DATA_COL = 24 // Y

  onMounted(async () => {
    await fetchObjectives()
    if (authStore.currentSiteId) {
      responsibleUsers.value = await userService.getBySite(authStore.currentSiteId)
    }
  })

  async function fetchObjectives () {
    if (!authStore.currentSiteId) return
    loading.value = true
    try {
      const { data } = await api.get('/objectives', { params: { site_id: authStore.currentSiteId } })
      objectives.value = data || []
    } catch (error) {
      console.error('Erreur chargement objectifs:', error)
    } finally {
      loading.value = false
    }
  }

  function openCreate () {
    editingId.value = null
    form.value = {
      title: '',
      type: 'strategic',
      description: '',
      target_value: null,
      unit: '',
      deadline: '',
      responsible_id: null,
    }
    showDialog.value = true
  }

  function openEdit (item: any) {
    editingId.value = item.id
    form.value = {
      title: item.title || '',
      type: item.type || 'strategic',
      description: item.description || '',
      target_value: item.target_value || null,
      unit: item.unit || '',
      deadline: item.deadline ? item.deadline.slice(0, 10) : '',
      responsible_id: item.responsible_id || null,
    }
    showDialog.value = true
  }

  async function handleSave () {
    if (!authStore.currentSiteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }
    if (!form.value.title || !form.value.target_value || !form.value.deadline || !form.value.responsible_id) {
      toast.error('Veuillez renseigner le titre, la cible, l’échéance et le responsable.')
      return
    }
    try {
      const payload = {
        site_id: authStore.currentSiteId,
        title: form.value.title,
        type: form.value.type,
        description: form.value.description,
        target_value: form.value.target_value,
        unit: form.value.unit,
        deadline: form.value.deadline,
        responsible_id: form.value.responsible_id,
      }

      await (editingId.value ? api.put(`/objectives/${editingId.value}`, payload) : api.post('/objectives', payload))

      showDialog.value = false
      await fetchObjectives()
      toast.success('Objectif enregistré.')
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l’enregistrement.')
    }
  }

  async function handleDelete (item: any) {
    if (!confirm('Supprimer cet objectif ?')) return
    try {
      await api.delete(`/objectives/${item.id}`)
      await fetchObjectives()
      toast.success('Objectif supprimé.')
    } catch (error) {
      console.error('Erreur suppression:', error)
      toast.error('Erreur lors de la suppression.')
    }
  }

  function typeLabel (type: string) {
    return objectiveTypes.find(t => t.value === type)?.title || type
  }

  async function exportObjectivesTemplate () {
    if (objectives.value.length === 0) {
      toast.info('Aucun objectif à exporter.')
      return
    }

    exporting.value = true
    try {
      const XLSX = await import('xlsx')
      const workbook = await loadTemplateWorkbook(XLSX)
      const sheet = resolveTemplateSheet(workbook)
      const templateLastRow = getTemplateLastRow(XLSX, sheet)

      setDefaultActiveSheet(workbook, TEMPLATE_SHEET_EVALUATION)
      clearTemplateDataRows(XLSX, sheet, TEMPLATE_FIRST_DATA_ROW, templateLastRow)

      for (const [index, objective] of objectives.value.entries()) {
        const rowNumber = TEMPLATE_FIRST_DATA_ROW + index
        fillObjectiveRow(XLSX, sheet, objective, index + 1, rowNumber)
      }

      const date = new Date().toISOString().slice(0, 10)
      XLSX.writeFile(workbook, `tableau_bord_objectifs_${date}.xlsx`, { cellStyles: true })
      toast.success('Export des objectifs généré.')
    } catch (error) {
      console.error('[Objectives] Export template failed:', error)
      toast.error('Impossible de générer l’export Excel.')
    } finally {
      exporting.value = false
    }
  }

  async function loadTemplateWorkbook (XLSX: any) {
    const response = await fetch('/canevas-objectifs.xlsx')
    if (!response.ok) throw new Error('template_unavailable')
    const fileBuffer = await response.arrayBuffer()
    return XLSX.read(fileBuffer, { type: 'array', cellStyles: true })
  }

  function resolveTemplateSheet (workbook: any) {
    const preferred = workbook.Sheets[TEMPLATE_SHEET_OBJECTIVES]
    if (preferred) return preferred
    const firstSheetName = workbook.SheetNames?.[0]
    if (!firstSheetName || !workbook.Sheets[firstSheetName]) throw new Error('sheet_not_found')
    return workbook.Sheets[firstSheetName]
  }

  function setDefaultActiveSheet (workbook: any, sheetName: string) {
    const index = Array.isArray(workbook?.SheetNames)
      ? workbook.SheetNames.indexOf(sheetName)
      : -1

    if (index < 0) return

    if (!workbook.Workbook) workbook.Workbook = {}
    if (!Array.isArray(workbook.Workbook.WBView) || workbook.Workbook.WBView.length === 0) {
      workbook.Workbook.WBView = [{}]
    }
    workbook.Workbook.WBView[0].activeTab = index
  }

  function getTemplateLastRow (XLSX: any, sheet: any): number {
    if (!sheet?.['!ref']) return TEMPLATE_FIRST_DATA_ROW
    const range = XLSX.utils.decode_range(sheet['!ref'])
    return Math.max(TEMPLATE_FIRST_DATA_ROW, range.e.r + 1)
  }

  function clearTemplateDataRows (XLSX: any, sheet: any, fromRow: number, toRow: number) {
    for (let rowNumber = fromRow; rowNumber <= toRow; rowNumber++) {
      for (let colIndex = TEMPLATE_FIRST_DATA_COL; colIndex <= TEMPLATE_LAST_DATA_COL; colIndex++) {
        writeCell(XLSX, sheet, rowNumber, colIndex, '')
      }
    }
  }

  function fillObjectiveRow (XLSX: any, sheet: any, objective: any, sequence: number, rowNumber: number) {
    cloneRowStyleFromTemplate(
      XLSX,
      sheet,
      TEMPLATE_FIRST_DATA_ROW,
      rowNumber,
      TEMPLATE_FIRST_DATA_COL,
      TEMPLATE_LAST_DATA_COL,
    )

    const monthlyValues = getMonthlyValues(objective)
    const strategicAxes = normalizeStrategicAxes(objective)
    const processName = objective.process?.title
      || objective.process?.name
      || objective.process_name
      || objective.processus
      || ''

    writeCell(XLSX, sheet, rowNumber, 0, sequence) // A
    writeCell(XLSX, sheet, rowNumber, 1, processName) // B
    writeCell(XLSX, sheet, rowNumber, 2, objective.title || '') // C
    writeCell(XLSX, sheet, rowNumber, 3, strategicAxes[0] || '') // D
    writeCell(XLSX, sheet, rowNumber, 4, strategicAxes[1] || '') // E
    writeCell(XLSX, sheet, rowNumber, 5, strategicAxes[2] || '') // F
    writeCell(XLSX, sheet, rowNumber, 6, objective.indicator_name || objective.indicator || objective.indicateur?.name || '') // G
    writeCell(XLSX, sheet, rowNumber, 7, objective.indicator_formula || objective.calculation_mode || '') // H
    writeCell(XLSX, sheet, rowNumber, 8, buildScheduleValue(objective)) // I

    for (let i = 0; i < 12; i++) {
      writeCell(XLSX, sheet, rowNumber, 9 + i, monthlyValues[i] ?? '') // J -> U
    }

    writeCell(XLSX, sheet, rowNumber, 21, objective.observation || objective.description || objective.notes || '') // V
    writeCell(XLSX, sheet, rowNumber, 22, normalizeActions(objective)) // W
    writeCell(XLSX, sheet, rowNumber, 23, objective.responsible?.name || '') // X
    writeCell(XLSX, sheet, rowNumber, 24, objective.required_resources || objective.resources || '') // Y
  }

  function writeCell (XLSX: any, sheet: any, rowNumber: number, colIndex: number, value: unknown) {
    const cellAddress = XLSX.utils.encode_cell({ r: rowNumber - 1, c: colIndex })
    const currentCell = sheet[cellAddress] || {}
    sheet[cellAddress] = {
      ...currentCell,
      t: typeof value === 'number' ? 'n' : 's',
      v: value ?? '',
    }
    ensureSheetRange(XLSX, sheet, rowNumber - 1, colIndex)
  }

  function ensureSheetRange (XLSX: any, sheet: any, rowIndex: number, colIndex: number) {
    const defaultRange = {
      s: { r: 0, c: 0 },
      e: { r: 0, c: 0 },
    }
    const range = sheet['!ref'] ? XLSX.utils.decode_range(sheet['!ref']) : defaultRange
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
      const sourceAddress = XLSX.utils.encode_cell({ r: templateRowNumber - 1, c: colIndex })
      const targetAddress = XLSX.utils.encode_cell({ r: targetRowNumber - 1, c: colIndex })
      const sourceCell = sheet[sourceAddress]
      if (!sourceCell) continue

      sheet[targetAddress] = {
        ...sourceCell,
        v: '',
      }

      ensureSheetRange(XLSX, sheet, targetRowNumber - 1, colIndex)
    }
  }

  function normalizeStrategicAxes (objective: any): string[] {
    const axes = Array.isArray(objective.axes) ? objective.axes : []
    if (axes.length > 0) {
      return axes
        .map((axis: any) => axis?.name || axis?.title || axis?.code || '')
        .filter(Boolean)
        .slice(0, 3)
    }

    const singleAxis = objective.strategic_axis?.name
      || objective.strategic_axis?.title
      || objective.strategic_axis
      || objective.strategicAxis?.name
      || objective.strategicAxis?.title
      || objective.strategicAxis

    return singleAxis ? [String(singleAxis)] : []
  }

  function getMonthlyValues (objective: any): Array<string | number> {
    const values = Array.from({ length: 12 }, () => '')
    const rows = Array.isArray(objective.realizations) ? objective.realizations : []

    for (const row of rows) {
      const monthIndex = resolveMonthIndex(row?.period_date)
      if (monthIndex < 0 || monthIndex > 11) continue
      values[monthIndex] = row?.achievement_rate ?? row?.value ?? row?.progress ?? ''
    }

    return values
  }

  function resolveMonthIndex (periodDate: unknown): number {
    if (!periodDate) return -1
    const date = new Date(String(periodDate))
    if (Number.isNaN(date.getTime())) return -1
    return date.getMonth()
  }

  function buildScheduleValue (objective: any): string {
    const frequency = String(objective.measurement_frequency || '').trim()
    const deadline = String(objective.deadline || '').slice(0, 10)
    if (frequency && deadline) return `${deadline} / ${frequency}`
    return deadline || frequency
  }

  function normalizeActions (objective: any): string {
    const actionRows = Array.isArray(objective.actions) ? objective.actions : []
    if (actionRows.length > 0) {
      return actionRows
        .map((action: any) => action?.title || action?.description || '')
        .filter(Boolean)
        .join(' | ')
    }

    if (typeof objective.action_plan === 'string') return objective.action_plan
    return ''
  }
</script>

<style scoped>
.page-header {
  @apply pb-6 border-b border-gray-200;
}
</style>
