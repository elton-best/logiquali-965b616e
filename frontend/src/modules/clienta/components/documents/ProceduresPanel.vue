<template>
  <v-card class="mb-6" rounded="xl">
    <v-card-title
      class="d-flex flex-wrap align-center justify-space-between ga-3"
    >
      <div>
        <div class="text-subtitle-1 font-weight-bold">Procédures associées</div>
        <div class="text-caption text-medium-emphasis">
          Retrouvez ici les procédures téléversées pour ce chapitre.
        </div>
      </div>
      <div class="d-flex ga-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-refresh"
          size="small"
          title="Rafraîchir la liste des procédures"
          variant="tonal"
          @click="loadData"
        >
          Actualiser
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text class="pt-0">
      <v-row dense>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="search"
            clearable
            hide-details
            label="Rechercher (titre, processus, version, statut...)"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex justify-end align-center" cols="12" md="6">
          <v-chip color="primary" size="small" variant="tonal">
            {{ filteredProcedures.length }} procédure(s)
          </v-chip>
        </v-col>
      </v-row>
    </v-card-text>

    <v-divider />

    <v-data-table
      class="modern-table"
      density="comfortable"
      :headers="headers"
      :items="filteredProcedures"
      :loading="loading"
    >
      <template #[`item.processName`]="{ item }">
        <span class="font-weight-medium">{{ item.processName || "—" }}</span>
      </template>
      <template #[`item.status`]="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>
      <template #[`item.source`]="{ item }">
        <v-chip size="x-small" variant="outlined">
          {{ item.source === "modern" ? "Nouveau" : "Inventaire" }}
        </v-chip>
      </template>
      <template #[`item.updatedAt`]="{ item }">
        <span class="text-medium-emphasis">{{ formatDate(item.updatedAt) }}</span>
      </template>
      <template #[`item.actions`]="{ item }">
        <v-btn
          icon="mdi-eye"
          size="small"
          title="Voir les détails et les versions de la procédure"
          variant="text"
          @click="openDetails(item)"
        />
      </template>
      <template #no-data>
        <div class="text-center py-6 text-medium-emphasis">
          Aucune procédure disponible pour le moment.
        </div>
      </template>
    </v-data-table>
  </v-card>

  <v-dialog v-model="showDetails" max-width="1100">
    <v-card v-if="selectedDocument" class="dialog-card" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="primary">mdi-file-document-edit-outline</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">Détails de la procédure</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="closeDetails" />
      </v-card-title>

      <v-card-text class="pt-6">
        <v-row>
          <v-col cols="12" md="6">
            <h3 class="text-subtitle-1 font-weight-bold mb-2">Versions</h3>
            <VersionHistory
              :versions="documentVersionsForHistory"
              @download="downloadVersion"
            />
            <v-card class="mt-4" variant="tonal">
              <v-card-title class="text-subtitle-1">Nouvelle version</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="versionForm.version"
                      label="Version *"
                      required
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-file-input
                      :hint="versionFileHint"
                      :label="isSelectedProcedure ? 'Fichier *' : 'Fichier'"
                      :persistent-hint="Boolean(versionFileHint)"
                      variant="outlined"
                      @change="handleVersionFileChange"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="versionForm.modifications"
                      label="Modifications"
                      rows="2"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </v-card-text>
              <v-card-actions class="pt-0">
                <v-spacer />
                <v-btn variant="text" @click="resetVersionForm">Effacer</v-btn>
                <v-btn color="primary" :loading="addingVersion" @click="submitVersion">Ajouter</v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <h3 class="text-subtitle-1 font-weight-bold mb-2">Informations</h3>
            <v-list density="compact">
              <v-list-item :subtitle="DocumentHelpers.getTitle(selectedDocument)" title="Nom" />
              <v-list-item :subtitle="selectedDocument.code || '—'" title="Code" />
              <v-list-item :subtitle="selectedDocument.processus || '—'" title="Processus" />
              <v-list-item :subtitle="selectedDocument.statut || '—'" title="Statut" />
              <v-list-item :subtitle="selectedDocument.version || '—'" title="Version actuelle" />
            </v-list>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
  import type { DocumentVersion as CoreDocumentVersion } from '@/types/document'
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { documentsApi } from '@/api/documents'
  import VersionHistory from '@/components/documents/VersionHistory.vue'
  import { DocumentHelpers } from '@/modules/clienta/types/document-unified.types'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'
  import { parseJsonApiCollection } from '@/utils/json-api-parser'

  interface ProcedureRow {
    id: number
    title: string
    processId: number | null
    processName: string
    status: string
    version: string
    source: 'modern' | 'legacy'
    updatedAt: string
  }

  interface ProcessOption {
    value: number
    title: string
  }

  const headers = [
    { title: 'Titre', key: 'title' },
    { title: 'Processus', key: 'processName' },
    { title: 'Statut', key: 'status' },
    { title: 'Version', key: 'version' },
    { title: 'Source', key: 'source' },
    { title: 'Dernière mise à jour', key: 'updatedAt' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const toast = useToast()
  const authStore = useAuthStore()
  const search = ref('')
  const loading = ref(false)
  const procedures = ref<ProcedureRow[]>([])
  const processOptions = ref<ProcessOption[]>([])
  const showDetails = ref(false)
  const selectedDocument = ref<UnifiedDocument | null>(null)
  const addingVersion = ref(false)
  const versionForm = ref({
    version: '',
    modifications: '',
    fichier: null as File | null,
  })

  const filteredProcedures = computed(() => {
    const query = search.value.trim().toLowerCase()
    if (!query) return procedures.value
    return procedures.value.filter(item => {
      const haystack = [
        item.title,
        item.processName,
        item.status,
        item.version,
        item.source,
      ]
        .join(' ')
        .toLowerCase()
      return haystack.includes(query)
    })
  })

  onMounted(() => {
    loadData()
  })

  defineExpose({
    refresh: loadData,
  })

  const isSelectedProcedure = computed(() => {
    return ['PRC', 'PRD'].includes(String(selectedDocument.value?.type || ''))
  })

  const hasLegacyFile = computed(() => {
    if (!selectedDocument.value) return false
    if (selectedDocument.value.fichier) return true
    return Boolean(selectedDocument.value.versions?.some(version => version.fichier))
  })

  const versionFileHint = computed(() => {
    if (isSelectedProcedure.value) {
      return 'Le fichier est obligatoire pour une procédure.'
    }
    if (!hasLegacyFile.value) {
      return 'Document sans fichier initial. Le fichier est requis pour la première version.'
    }
    return undefined
  })

  const documentVersionsForHistory = computed<CoreDocumentVersion[]>(() => {
    const versions = selectedDocument.value?.versions || []
    return versions.map((version: any) => ({
      id: Number(version?.id || 0),
      created_at: String(version?.created_at || new Date().toISOString()),
      updated_at: String(version?.updated_at || version?.created_at || new Date().toISOString()),
      document_id: Number(version?.document_id || selectedDocument.value?.id || 0),
      version: String(version?.version || '1.0'),
      version_number: Number(version?.version_number || 1),
      file_path: String(version?.file_path || version?.fichier || ''),
      file_original_name: version?.file_original_name ? String(version.file_original_name) : undefined,
      file_size: typeof version?.file_size === 'number' ? version.file_size : undefined,
      change_summary: version?.change_summary ? String(version.change_summary) : version?.modifications,
      created_by_id: Number(version?.created_by_id || version?.created_by || 0),
      created_by: version?.created_by,
      is_current: Boolean(version?.is_current ?? false),
      status: (version?.status || 'approved') as CoreDocumentVersion['status'],
    }))
  })

  async function loadData (): Promise<void> {
    loading.value = true
    try {
      await loadProcesses()
      await loadProcedures()
    } finally {
      loading.value = false
    }
  }

  async function loadProcesses (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      processOptions.value = []
      return
    }

    try {
      const response = await processService.getProcesses({ site_id: siteId }, 1, 500)
      const rows = Array.isArray(response?.data) ? response.data : []
      processOptions.value = rows.map((item: any) => ({
        value: Number(item.id),
        title: String(item.title || item.name || item.code || `Processus #${item.id}`),
      }))
    } catch {
      processOptions.value = []
    }
  }

  function resolveProcessIdFromJsonApi (item: any): number | null {
    const relProcess = item?.relationships?.process
    const relId = Number(
      relProcess?.id || relProcess?.data?.id || relProcess?.attributes?.id || 0,
    )
    if (relId > 0) return relId

    const attrProcessId = Number(
      item?.attributes?.process_id
      || item?.attributes?.metadata?.process_id
        || item?.process_id
      || 0,
    )
    return attrProcessId > 0 ? attrProcessId : null
  }

  async function loadProcedures (): Promise<void> {
    const rows: ProcedureRow[] = []
    const siteId = currentSiteId()
    if (!siteId) {
      procedures.value = []
      return
    }

    try {
      const modernResponse = await api.get('/documents', { params: { type: 'procedure', site_id: siteId } })
      const modernItems = parseJsonApiCollection(modernResponse.data)

      for (const item of modernItems) {
        const attributes = item?.attributes || {}
        const processId = resolveProcessIdFromJsonApi(item)
        const processName = resolveProcessName(processId)
        rows.push({
          id: Number(item.id),
          title: String(attributes.title || `Procédure #${item.id}`),
          processId,
          processName,
          status: String(attributes.status || 'draft'),
          version: String(attributes.version || attributes.version_number || '-'),
          source: 'modern',
          updatedAt: String(attributes.updated_at || attributes.created_at || ''),
        })
      }
    } catch {
    // Ignore modern failures to allow legacy fallback
    }

    try {
      const [legacyPRC, legacyPRD] = await Promise.all([
        api.get('/documents-inventory', { params: { type: 'PRC', site_id: siteId } }),
        api.get('/documents-inventory', { params: { type: 'PRD', site_id: siteId } }),
      ])

      for (const source of [legacyPRC.data, legacyPRD.data]) {
        const legacyItems = Array.isArray(source) ? source : []
        for (const item of legacyItems) {
          const processId
            = Number(item?.process_id || item?.process?.id || 0) || null
          const processName = String(
            item?.processus
            || item?.process_name
              || item?.process?.title
            || item?.process?.code
              || resolveProcessName(processId)
            || '',
          )
          rows.push({
            id: Number(item?.id || 0),
            title: String(
              item?.nom || item?.code || `Procédure #${item?.id || ''}`,
            ),
            processId,
            processName,
            status: String(item?.statut || item?.status || 'draft'),
            version: String(item?.version || item?.indice || '-'),
            source: 'legacy',
            updatedAt: String(item?.updated_at || item?.created_at || ''),
          })
        }
      }
    } catch {
    // Ignore legacy failures
    }

    procedures.value = rows
  }

  function currentSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId
      ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
      ?? (authStore.user as { site_id?: number } | null)?.site_id
      ?? null
  }

  function resolveProcessName (processId: number | null): string {
    if (!processId) return ''
    const match = processOptions.value.find(item => item.value === processId)
    return match?.title || ''
  }

  function statusLabel (status: string): string {
    switch (String(status || '').toLowerCase()) {
      case 'pending_approval': {
        return 'brouillon - en attente de validation'
      }
      case 'approved': {
        return 'validé - version 1'
      }
      case 'obsolete':
      case 'archived': {
        return 'Obsolète'
      }
      case 'valid': {
        return 'Valide'
      }
      case 'draft':
      default: {
        return 'brouillon en attente de vérification'
      }
    }
  }

  function statusColor (status: string): string {
    switch (String(status || '').toLowerCase()) {
      case 'pending_approval': {
        return 'warning'
      }
      case 'approved':
      case 'valid': {
        return 'success'
      }
      case 'obsolete':
      case 'archived': {
        return 'grey'
      }
      default: {
        return 'info'
      }
    }
  }

  function formatDate (value: string): string {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'
    return date.toLocaleDateString('fr-FR')
  }

  async function openDetails (item: ProcedureRow): Promise<void> {
    if (item.source !== 'legacy') {
      toast.info('Les détails complets ne sont disponibles que pour l’inventaire documentaire.')
      return
    }

    try {
      const response = await documentsApi.getById(item.id)
      selectedDocument.value = response.data
      resetVersionForm()
      showDetails.value = true
    } catch {
      toast.error('Impossible de charger les détails de la procédure.')
    }
  }

  function closeDetails (): void {
    showDetails.value = false
    selectedDocument.value = null
    resetVersionForm()
  }

  function handleVersionFileChange (e: Event) {
    const target = e.target as HTMLInputElement
    versionForm.value.fichier = target.files?.[0] || null
  }

  function resetVersionForm (): void {
    versionForm.value = {
      version: '',
      modifications: '',
      fichier: null,
    }
  }

  async function submitVersion () {
    if (!selectedDocument.value) return
    if (!versionForm.value.version.trim()) {
      toast.error('La version est obligatoire.')
      return
    }
    if (isSelectedProcedure.value && !versionForm.value.fichier) {
      toast.error('Le fichier est obligatoire pour une procédure.')
      return
    }
    if (!hasLegacyFile.value && !versionForm.value.fichier) {
      toast.error('Ajoutez un fichier pour la première version de ce document.')
      return
    }

    const formData = new FormData()
    formData.append('version', versionForm.value.version.trim())
    if (versionForm.value.modifications.trim()) {
      formData.append('modifications', versionForm.value.modifications.trim())
    }
    if (versionForm.value.fichier) {
      formData.append('fichier', versionForm.value.fichier)
    }

    addingVersion.value = true
    try {
      await documentsApi.addVersion(selectedDocument.value.id, formData)
      const refreshed = await documentsApi.getById(selectedDocument.value.id)
      selectedDocument.value = refreshed.data
      resetVersionForm()
      toast.success('Version ajoutée avec succès.')
    } catch {
      toast.error('Impossible d’ajouter la version.')
    } finally {
      addingVersion.value = false
    }
  }

  async function downloadVersion (versionId: number, filename: string) {
    if (!selectedDocument.value) return
    try {
      const safeFilename = filename ? filename.split('/').pop() : undefined
      await documentsApi.downloadVersion(selectedDocument.value.id, versionId, safeFilename)
    } catch {
      toast.error('Téléchargement impossible.')
    }
  }
</script>

<style scoped>
.dialog-card {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
