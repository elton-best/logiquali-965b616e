<template>
  <div class="import-logs">
    <GlassCard class="mb-4">
      <div class="filters-header">
        <h3 class="filters-title">Filtres</h3>
        <div class="filters-subtitle">Affinez l’historique des imports</div>
      </div>

      <v-row dense>
        <v-col cols="12" md="4">
          <v-select
            v-model="filters.status"
            hide-details
            :items="statusOptions"
            label="Statut"
            variant="outlined"
            @update:model-value="loadLogs"
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.from_date"
            hide-details
            label="Date début"
            type="date"
            variant="outlined"
            @update:model-value="loadLogs"
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.to_date"
            hide-details
            label="Date fin"
            type="date"
            variant="outlined"
            @update:model-value="loadLogs"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-btn
            class="w-100"
            prepend-icon="mdi-refresh"
            variant="outlined"
            @click="resetFilters"
          >
            Réinitialiser
          </v-btn>
        </v-col>
      </v-row>
    </GlassCard>

    <GlassCard>
      <div class="table-header">
        <h3 class="filters-title">Historique</h3>
        <div class="filters-subtitle">Imports de fiches de poste</div>
      </div>

      <v-progress-linear
        v-if="isLoading"
        class="mb-4"
        color="primary"
        indeterminate
      />

      <div v-else-if="logs.length === 0" class="empty-state">
        <v-icon color="#cbd5e1" size="56">mdi-file-document-outline</v-icon>
        <div class="empty-title">Aucun import</div>
        <div class="empty-subtitle">
          Commencez par importer un fichier Excel de fiches de poste.
        </div>
      </div>

      <v-table v-else class="imports-table" density="comfortable">
        <thead>
          <tr>
            <th class="text-left">Date</th>
            <th class="text-left">Fichier</th>
            <th class="text-left">Statut</th>
            <th class="text-center">Lignes</th>
            <th class="text-center">Taux succès</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td class="text-sm text-strong">
              {{ formatDate(log.created_at) }}
            </td>
            <td>
              <div class="text-strong">{{ log.file_name }}</div>
              <div class="text-muted">{{ formatFileSize(log.file_size) }}</div>
            </td>
            <td>
              <ImportStatusBadge :status="log.status" />
            </td>
            <td class="text-center">
              <div class="text-strong">
                {{ log.processed_rows }} / {{ log.total_rows }}
              </div>
              <div class="text-muted">
                <span class="text-success">{{ log.successful_rows }} ✓</span>
                <span v-if="log.failed_rows > 0" class="text-danger ml-2">{{ log.failed_rows }} ✗</span>
              </div>
            </td>
            <td class="text-center">
              <div class="text-strong" :class="getSuccessRateColor(log.success_rate)">
                {{ log.success_rate }}%
              </div>
            </td>
            <td class="text-center">
              <div class="table-actions">
                <v-btn
                  color="primary"
                  icon="mdi-eye"
                  title="Voir les détails"
                  variant="text"
                  @click="viewPreview(log.id)"
                />
                <v-btn
                  v-if="log.error_report_available"
                  color="warning"
                  icon="mdi-download"
                  :loading="downloadingErrorId === log.id"
                  title="Télécharger le rapport d'erreurs"
                  variant="text"
                  @click="downloadErrors(log.id)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>

      <div v-if="meta && meta.last_page > 1" class="pagination-row">
        <div class="pagination-info">
          Affichage de
          <span class="text-strong">{{ (meta.current_page - 1) * meta.per_page + 1 }}</span>
          à
          <span class="text-strong">{{ Math.min(meta.current_page * meta.per_page, meta.total) }}</span>
          sur
          <span class="text-strong">{{ meta.total }}</span>
          résultats
        </div>
        <div class="pagination-actions">
          <v-btn
            :disabled="meta.current_page === 1"
            size="small"
            variant="outlined"
            @click="goToPage(meta.current_page - 1)"
          >
            Précédent
          </v-btn>
          <v-btn
            v-for="page in visiblePages"
            :key="page"
            color="primary"
            size="small"
            :variant="page === meta.current_page ? 'flat' : 'outlined'"
            @click="goToPage(page)"
          >
            {{ page }}
          </v-btn>
          <v-btn
            :disabled="meta.current_page === meta.last_page"
            size="small"
            variant="outlined"
            @click="goToPage(meta.current_page + 1)"
          >
            Suivant
          </v-btn>
        </div>
      </div>
    </GlassCard>
  </div>
</template>

<script setup lang="ts">
  import type { ImportLogsFilters, JobDescriptionImportLog } from '@/services/jobDescriptionImportService'
  import { computed, onMounted, ref } from 'vue'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import { jobDescriptionImportService } from '@/services/jobDescriptionImportService'
  import ImportStatusBadge from './ImportStatusBadge.vue'

  interface Emits {
    (e: 'view-preview', importId: number): void
  }

  const emit = defineEmits<Emits>()

  const isLoading = ref(false)
  const logs = ref<JobDescriptionImportLog[]>([])
  const meta = ref<any>(null)
  const downloadingErrorId = ref<number | null>(null)

  const filters = ref<ImportLogsFilters>({
    status: undefined,
    from_date: undefined,
    to_date: undefined,
    per_page: 20,
    page: 1,
  })

  const statusOptions = [
    { title: 'Tous', value: undefined },
    { title: 'En attente', value: 'pending' },
    { title: 'Validation', value: 'validating' },
    { title: 'En cours', value: 'processing' },
    { title: 'Terminé', value: 'completed' },
    { title: 'Échec', value: 'failed' },
  ]

  const visiblePages = computed(() => {
    if (!meta.value) return []

    const current = meta.value.current_page
    const last = meta.value.last_page
    const delta = 2
    const pages: number[] = []

    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
      pages.push(i)
    }

    return pages
  })

  async function loadLogs () {
    isLoading.value = true

    try {
      const response = await jobDescriptionImportService.getLogs(filters.value)
      logs.value = response.data
      meta.value = response.meta
    } catch (error) {
      console.error('Load logs error:', error)
    } finally {
      isLoading.value = false
    }
  }

  function resetFilters () {
    filters.value = {
      status: undefined,
      from_date: undefined,
      to_date: undefined,
      per_page: 20,
      page: 1,
    }
    loadLogs()
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadLogs()
  }

  function viewPreview (importId: number) {
    emit('view-preview', importId)
  }

  async function downloadErrors (logId: number) {
    downloadingErrorId.value = logId

    try {
      const blob = await jobDescriptionImportService.downloadErrorReport(logId)
      jobDescriptionImportService.downloadFile(blob, `rapport_erreurs_${logId}.xlsx`)
    } catch (error) {
      console.error('Download error report error:', error)
    } finally {
      downloadingErrorId.value = null
    }
  }

  function formatDate (dateString: string): string {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatFileSize (bytes: number): string {
    return jobDescriptionImportService.formatFileSize(bytes)
  }

  function getSuccessRateColor (rate: number): string {
    if (rate >= 90) return 'text-success'
    if (rate >= 70) return 'text-warning'
    return 'text-danger'
  }

  onMounted(() => {
    loadLogs()
  })

  defineExpose({ loadLogs })
</script>

<style scoped>
.import-logs {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.filters-header {
  margin-bottom: 16px;
}

.filters-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
}

.filters-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
}

.table-header {
  margin-bottom: 12px;
}

.imports-table th {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
  font-weight: 600;
}

.imports-table td {
  padding-top: 12px;
  padding-bottom: 12px;
}

.text-strong {
  font-weight: 700;
  color: #1e293b;
}

.text-muted {
  font-size: 0.75rem;
  color: #64748b;
}

.text-success {
  color: #16a34a;
}

.text-warning {
  color: #f59e0b;
}

.text-danger {
  color: #dc2626;
}

.table-actions {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.empty-state {
  text-align: center;
  padding: 32px 12px;
}

.empty-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin-top: 8px;
}

.empty-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
}

.pagination-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
}

.pagination-info {
  font-size: 0.8125rem;
  color: #64748b;
}

.pagination-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
