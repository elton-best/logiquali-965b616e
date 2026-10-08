<template>
  <v-card class="excel-importer" elevation="2">
    <v-card-text class="pa-6">
      <div class="text-center mb-4">
        <v-icon color="success" size="64">mdi-file-excel</v-icon>
        <h3 class="text-h6 mt-2">Importer depuis Excel</h3>
        <p class="text-body-2 text-grey-darken-1">
          Téléchargez votre fichier de plan annuel de formation
        </p>
        <v-btn
          class="mt-3"
          color="primary"
          prepend-icon="mdi-download"
          variant="tonal"
          @click="downloadTemplate"
        >
          Télécharger le template officiel
        </v-btn>
      </div>

      <v-select
        v-model="selectedYear"
        class="mb-3"
        density="comfortable"
        :items="yearOptions"
        label="Année du plan *"
        variant="outlined"
      />

      <v-autocomplete
        v-model="targetUserIds"
        chips
        class="mb-3"
        clearable
        density="comfortable"
        item-title="full_name"
        item-value="id"
        :items="collaborators"
        label="Collaborateurs (appliqués à toutes les formations) *"
        :loading="loadingCollaborators"
        multiple
        prepend-inner-icon="mdi-account-group"
        variant="outlined"
      >
        <template #item="{ props: itemProps, item }">
          <v-list-item v-bind="itemProps" :subtitle="item.raw.email" :title="item.raw.full_name" />
        </template>
      </v-autocomplete>

      <v-file-input
        v-model="file"
        accept=".xlsx,.xls,.csv,.txt"
        :disabled="parsing"
        label="Sélectionner un fichier"
        :loading="parsing"
        prepend-icon="mdi-file-excel"
        variant="outlined"
        @update:model-value="handleFileSelect"
      />

      <v-alert
        v-if="error"
        class="mt-4"
        closable
        type="error"
        variant="tonal"
        @click:close="error = ''"
      >
        {{ error }}
      </v-alert>

      <v-alert
        v-if="success"
        class="mt-4"
        closable
        type="success"
        variant="tonal"
        @click:close="success = ''"
      >
        {{ success }}
      </v-alert>

      <div v-if="previewData.length > 0" class="mt-4">
        <h4 class="text-subtitle-2 mb-2">Aperçu des données ({{ previewData.length }} formations)</h4>
        <v-table class="preview-table" density="compact">
          <thead>
            <tr>
              <th>N°</th>
              <th>Désignation</th>
              <th>Cibles</th>
              <th>Formateur</th>
              <th>Dates</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in previewData.slice(0, 5)" :key="index">
              <td>{{ item.numero }}</td>
              <td>{{ item.designation }}</td>
              <td>{{ item.cibles.join(', ') }}</td>
              <td>{{ item.formateur }}</td>
              <td>
                <span v-if="item.dateDebut && item.dateFin">
                  {{ item.dateDebut }} - {{ item.dateFin }}
                </span>
                <span v-else class="text-grey">À compléter</span>
              </td>
            </tr>
          </tbody>
        </v-table>
        <p v-if="previewData.length > 5" class="text-caption text-grey text-center mt-2">
          ... et {{ previewData.length - 5 }} autres formations
        </p>
      </div>

      <v-alert
        v-if="skippedRows.length > 0"
        class="mt-4"
        type="warning"
        variant="tonal"
      >
        {{ skippedRows.length }} ligne(s) ignorée(s) car sans Date Suivi ni mois cochés.
        <v-btn class="ms-2" size="small" variant="text" @click="downloadSkippedReport">
          Télécharger le rapport
        </v-btn>
      </v-alert>

      <v-alert
        v-if="incompleteRows.length > 0"
        class="mt-4"
        type="info"
        variant="tonal"
      >
        {{ incompleteRows.length }} formation(s) créées sans période. Elles devront être complétées.
      </v-alert>

      <v-table v-if="skippedRows.length > 0" class="preview-table mt-3" density="compact">
        <thead>
          <tr>
            <th>Ligne</th>
            <th>Numero</th>
            <th>Raison</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in skippedRows.slice(0, 5)" :key="index">
            <td>{{ item.row }}</td>
            <td>{{ item.numero ?? '-' }}</td>
            <td>{{ item.reason }}</td>
          </tr>
        </tbody>
      </v-table>
      <p v-if="skippedRows.length > 5" class="text-caption text-grey text-center mt-2">
        ... et {{ skippedRows.length - 5 }} autres lignes ignorées
      </p>
    </v-card-text>

    <v-card-actions class="px-6 pb-6">
      <v-btn
        variant="text"
        @click="$emit('cancel')"
      >
        Annuler
      </v-btn>
      <v-spacer />
      <v-btn
        color="primary"
        :disabled="!selectedFile"
        :loading="importing"
        variant="flat"
        @click="handleImport"
      >
        Importer le fichier
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { trainingPlanApi } from '@/api/formations'

  const emit = defineEmits<{
    import: [payload: { year: number, targetUserIds: number[], file: File }]
    report: [report: { skipped: Array<{ row: number, numero?: number, reason: string }>, incomplete: Array<{ row: number, numero?: number, reason: string }> }]
    cancel: []
  }>()

  const file = ref<File[] | File | null>(null)
  const selectedFile = ref<File | null>(null)
  const parsing = ref(false)
  const importing = ref(false)
  const error = ref('')
  const success = ref('')
  const previewData = ref<any[]>([])
  const skippedRows = ref<Array<{ row: number, numero?: number, reason: string }>>([])
  const incompleteRows = ref<Array<{ row: number, numero?: number, reason: string }>>([])
  const importSummary = ref<{ created: number, updated: number, skipped: number } | null>(null)
  const selectedYear = ref<number | null>(new Date().getFullYear())
  const targetUserIds = ref<number[]>([])
  const collaborators = ref<{ id: number, full_name: string, email: string }[]>([])
  const loadingCollaborators = ref(false)

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }).map((_, index) => currentYear - 1 + index)
  })

  async function handleFileSelect (files: File | File[] | null) {
    const selectedFiles = Array.isArray(files) ? files : (files ? [files] : [])
    if (selectedFiles.length === 0) return

    parsing.value = true
    error.value = ''
    success.value = ''
    previewData.value = []
    skippedRows.value = []
    incompleteRows.value = []
    importSummary.value = null

    try {
      selectedFile.value = selectedFiles[0] || null
      success.value = `${selectedFile.value?.name || 'Fichier'} prêt pour l'import`
    } catch (error_: any) {
      error.value = 'Erreur lors de la lecture du fichier: ' + error_.message
    } finally {
      parsing.value = false
    }
  }

  function handleImport () {
    if (!selectedYear.value) {
      error.value = 'Veuillez sélectionner l\'année du plan.'
      return
    }
    if (targetUserIds.value.length === 0) {
      error.value = 'Sélectionnez au moins un collaborateur.'
      return
    }
    if (!selectedFile.value) {
      error.value = 'Veuillez sélectionner un fichier.'
      return
    }
    importing.value = true
    emit('import', { year: selectedYear.value, targetUserIds: targetUserIds.value, file: selectedFile.value })
    emit('report', { skipped: skippedRows.value, incomplete: incompleteRows.value })
    importing.value = false
  }

  function downloadSkippedReport () {
    if (skippedRows.value.length === 0) return
    const headers = ['Ligne', 'Numero', 'Raison']
    const rows = skippedRows.value.map(item => [
      String(item.row),
      item.numero === undefined ? '' : String(item.numero),
      item.reason,
    ])
    const csv = [headers, ...rows].map(line => line.map(value => escapeCsv(value)).join(';')).join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'rapport_import_formations_ignores.csv'
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  function escapeCsv (value: string) {
    if (value.includes('"') || value.includes(';') || value.includes('\n')) {
      return `"${value.replace(/\"/g, '""')}"`
    }
    return value
  }

  async function downloadTemplate () {
    try {
      const blob = await trainingPlanApi.downloadTemplate()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'PLAN_ANNUEL_DE_FORMATION.xlsx'
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch {
      error.value = 'Impossible de télécharger le template.'
    }
  }

  async function loadCollaborators () {
    loadingCollaborators.value = true
    try {
      const { data } = await api.get('/users/job-description-collaborators')
      const rows = Array.isArray(data?.data) ? data.data : []
      collaborators.value = rows.map((row: any) => ({
        id: Number(row.id),
        full_name: String(row.full_name || row.name || row.email || `Collaborateur ${row.id}`),
        email: String(row.email || ''),
      }))
    } finally {
      loadingCollaborators.value = false
    }
  }

  onMounted(loadCollaborators)
</script>

<style scoped>
.excel-importer {
  border-radius: 16px;
  max-width: 800px;
  margin: 0 auto;
}

.preview-table {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  overflow: hidden;
}
</style>
