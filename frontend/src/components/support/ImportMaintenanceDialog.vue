<template>
  <v-dialog v-model="isOpen" max-width="600">
    <v-card rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="primary" start>mdi-upload</v-icon>
        Importer un plan de maintenance
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-6">
        <v-alert class="mb-4" type="info" variant="tonal">
          <div class="text-body-2">
            Formats acceptés: Excel (.xlsx, .xls), CSV.<br>
            Colonnes reconnues (alias acceptés):
            <code>equipement</code>,
            <code>type</code>,
            <code>date_prevue</code>,
            <code>responsable</code>,
            <code>description</code>,
            <code>statut</code>.
          </div>
        </v-alert>

        <div class="d-flex justify-end mb-4">
          <v-btn
            color="primary"
            :loading="loadingTemplate"
            prepend-icon="mdi-file-download-outline"
            variant="text"
            @click="downloadTemplate"
          >
            Télécharger le modèle
          </v-btn>
        </div>

        <v-file-input
          v-model="selectedFile"
          accept=".xlsx,.xls,.csv"
          label="Sélectionner un fichier"
          prepend-icon="mdi-file-excel"
          :rules="[v => !!v || 'Fichier requis']"
          show-size
          variant="outlined"
        />

        <div v-if="parseResult" class="mt-4">
          <v-alert :type="parseResult.success ? 'success' : 'error'" variant="tonal">
            {{ parseResult.message }}
          </v-alert>

          <v-row v-if="parseResult.summary" class="mt-2 mb-2">
            <v-col cols="12" md="4">
              <v-chip color="primary" variant="tonal">Total: {{ parseResult.summary.total }}</v-chip>
            </v-col>
            <v-col cols="12" md="4">
              <v-chip color="success" variant="tonal">Créées: {{ parseResult.summary.created }}</v-chip>
            </v-col>
            <v-col cols="12" md="4">
              <v-chip color="error" variant="tonal">Échecs: {{ parseResult.summary.failed }}</v-chip>
            </v-col>
          </v-row>

          <div v-if="parseResult.preview && parseResult.preview.length > 0" class="mt-4">
            <div class="text-subtitle-2 mb-2">Aperçu ({{ parseResult.preview.length }} lignes)</div>
            <v-list class="bg-surface-variant rounded" density="compact">
              <v-list-item v-for="(item, i) in parseResult.preview.slice(0, 5)" :key="i">
                <v-list-item-title>{{ item.equipement }} - {{ item.type }}</v-list-item-title>
                <v-list-item-subtitle>{{ item.date_prevue }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </div>

          <div v-if="parseResult.report && parseResult.report.length > 0" class="mt-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="text-subtitle-2">Rapport détaillé</div>
              <v-btn
                v-if="failedRows.length > 0"
                color="error"
                prepend-icon="mdi-file-delimited"
                size="small"
                variant="tonal"
                @click="downloadFailedRowsCsv"
              >
                Exporter les échecs (CSV)
              </v-btn>
            </div>
            <v-data-table
              :headers="reportHeaders"
              :items="parseResult.report"
              :items-per-page="5"
            >
              <template #item.status="{ item }">
                <v-chip
                  :color="item.status === 'created' ? 'success' : 'error'"
                  size="small"
                  variant="tonal"
                >
                  {{ item.status }}
                </v-chip>
              </template>
            </v-data-table>
          </div>
        </div>

        <div class="d-flex justify-end gap-2 mt-6">
          <v-btn variant="outlined" @click="close">Annuler</v-btn>
          <v-btn color="primary" :disabled="selectedFile.length === 0" :loading="loading" @click="handleImport">
            Importer
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { MaintenanceImportResult } from '@/services/supportService'
  import { computed, ref, watch } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'

  const props = defineProps<{ modelValue: boolean }>()
  const emit = defineEmits<{ 'update:modelValue': [value: boolean], 'imported': [] }>()

  const store = useSupportStore()
  const isOpen = ref(props.modelValue)
  const loading = ref(false)
  const loadingTemplate = ref(false)
  const selectedFile = ref<File[]>([])
  const parseResult = ref<MaintenanceImportResult | null>(null)
  const reportHeaders = [
    { title: 'Ligne', key: 'row', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Message', key: 'message', sortable: false },
  ]
  const failedRows = computed(() => (parseResult.value?.report || []).filter(row => row.status === 'failed'))

  watch(() => props.modelValue, val => {
    isOpen.value = val
  })
  watch(isOpen, val => {
    emit('update:modelValue', val)
  })

  function close () {
    isOpen.value = false
    selectedFile.value = []
    parseResult.value = null
  }

  async function handleImport () {
    if (selectedFile.value.length === 0) return

    loading.value = true
    try {
      const result = await store.importMaintenances(selectedFile.value[0] as File)
      parseResult.value = result
      if (result.success && (result.summary?.failed ?? 0) === 0) {
        setTimeout(() => {
          emit('imported')
          close()
        }, 2000)
      }
    } catch (error: any) {
      parseResult.value = { success: false, message: error.message || 'Erreur lors de l\'importation' }
    } finally {
      loading.value = false
    }
  }

  async function downloadTemplate () {
    loadingTemplate.value = true
    try {
      await store.downloadMaintenanceTemplate()
    } catch (error) {
      console.error('Template download error:', error)
    } finally {
      loadingTemplate.value = false
    }
  }

  function downloadFailedRowsCsv () {
    if (failedRows.value.length === 0) {
      return
    }

    const escapeCsv = (value: unknown) => `"${String(value ?? '').replaceAll('"', '""')}"`
    const lines = [
      ['row', 'status', 'message'],
      ...failedRows.value.map(item => [item.row, item.status, item.message]),
    ]

    const csvContent = lines.map(row => row.map(cell => escapeCsv(cell)).join(';')).join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `import_maintenances_echecs_${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }
</script>
