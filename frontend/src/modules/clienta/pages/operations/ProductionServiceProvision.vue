<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      icon="mdi-factory"
      subtitle="Production et prestation de service"
      title="Production et prestation de service"
    />

    <v-card class="hero mb-6" elevation="0" rounded="xl">
      <v-card-text class="pa-6 pa-md-8">
        <div class="hero-grid">
          <div>
            <h2 class="text-h4 font-weight-black mb-2">Registre des protocoles opérationnels</h2>
            <p class="text-body-1 text-medium-emphasis mb-0">
              Processus, procédures et activités sont déjà en base. Cette interface sert à rattacher et piloter les protocoles.
            </p>
          </div>
          <div class="hero-badges">
            <div class="hero-badge">
              <span>Processus</span>
              <strong>{{ processOptions.length }}</strong>
            </div>
            <div class="hero-badge">
              <span>Procédures</span>
              <strong>{{ procedureOptions.length }}</strong>
            </div>
            <div class="hero-badge">
              <span>Activités</span>
              <strong>{{ activityOptions.length }}</strong>
            </div>
            <div class="hero-badge">
              <span>Protocoles</span>
              <strong>{{ protocolRows.length }}</strong>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="registry-shell" rounded="xl">
      <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
        <div>
          <div class="text-h6 font-weight-bold">Bibliothèque des protocoles</div>
          <div class="text-body-2 text-medium-emphasis">Vue globale, filtrage, prévisualisation, téléchargement et archivage.</div>
        </div>
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-btn-toggle v-model="viewMode" color="primary" mandatory variant="outlined">
            <v-btn prepend-icon="mdi-table" value="table">Tableau</v-btn>
            <v-btn prepend-icon="mdi-view-grid-outline" value="grid">Grille</v-btn>
          </v-btn-toggle>
          <v-btn
            color="primary"
            prepend-icon="mdi-file-document-plus-outline"
            rounded="lg"
            variant="tonal"
            @click="openProcedureDialog()"
          >
            Ajouter une procédure
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openDialog">
            Ajouter un protocole
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text class="pb-2">
        <v-row dense>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="search"
              clearable
              label="Rechercher"
              prepend-inner-icon="mdi-magnify"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterProcessId"
              clearable
              item-title="title"
              item-value="value"
              :items="processOptions"
              label="Filtrer par processus"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="statusFilter"
              clearable
              :items="statusOptions"
              label="Statut"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model.number="selectedSmPlanYear"
              item-title="title"
              item-value="value"
              :items="smPlanYearOptions"
              label="Année du plan SM"
              prepend-inner-icon="mdi-calendar-range"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col class="d-flex align-center" cols="12" md="2">
            <v-btn
              block
              :disabled="!hasActiveFilters"
              prepend-icon="mdi-filter-off"
              rounded="lg"
              variant="outlined"
              @click="resetFilters"
            >
              Réinitialiser
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-text class="pt-4 pb-2">
        <v-alert density="comfortable" type="info" variant="tonal">
          <strong>Plan SM {{ selectedSmPlanYear }}:</strong>
          <span v-if="smPlanHasData">
            {{ activityOptions.length }} activité(s) disponible(s) pour cette année.
          </span>
          <span v-else>
            aucun plan trouvé pour cette année. Enregistrez/importez le plan SM de {{ selectedSmPlanYear }} pour l’afficher ici.
          </span>
        </v-alert>
      </v-card-text>

      <v-data-table
        v-if="viewMode === 'table'"
        :headers="headers"
        :items="filteredProtocols"
        :loading="loading"
      >
        <template #[`item.status`]="{ item }">
          <v-chip :color="item.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal">
            {{ item.status === 'active' ? 'Actif' : 'Archivé' }}
          </v-chip>
        </template>
        <template #[`item.fileSize`]="{ item }">
          {{ formatFileSize(item.fileSize) }}
        </template>
        <template #[`item.uploadedAt`]="{ item }">
          {{ formatDate(item.uploadedAt) }}
        </template>
        <template #[`item.actions`]="{ item }">
          <div class="d-flex ga-1">
            <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="previewProtocol(item)" />
            <v-btn icon="mdi-download" size="small" variant="text" @click="downloadProtocol(item)" />
            <v-btn
              v-if="item.status === 'active'"
              color="warning"
              icon="mdi-archive-outline"
              size="small"
              variant="text"
              @click="archiveProtocol(item)"
            />
          </div>
        </template>
        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
        </template>
      </v-data-table>

      <v-card-text v-else>
        <v-row dense>
          <v-col
            v-for="item in filteredProtocols"
            :key="item.id"
            cols="12"
            md="6"
            xl="4"
          >
            <v-card class="protocol-card" rounded="lg" variant="outlined">
              <v-card-item>
                <v-card-title class="text-subtitle-1 font-weight-bold">{{ item.name }}</v-card-title>
                <v-card-subtitle>{{ item.fileName }}</v-card-subtitle>
                <template #append>
                  <v-chip :color="item.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal">
                    {{ item.status === 'active' ? 'Actif' : 'Archivé' }}
                  </v-chip>
                </template>
              </v-card-item>
              <v-divider />
              <v-card-text class="pt-4">
                <div class="meta-row"><span>Processus</span><strong>{{ item.processName }}</strong></div>
                <div class="meta-row"><span>Procédure</span><strong>{{ item.procedureName || '-' }}</strong></div>
                <div class="meta-row"><span>Activité</span><strong>{{ item.activityName || '-' }}</strong></div>
                <div class="meta-row"><span>Taille</span><strong>{{ formatFileSize(item.fileSize) }}</strong></div>
                <div class="meta-row"><span>Téléversé le</span><strong>{{ formatDate(item.uploadedAt) }}</strong></div>
              </v-card-text>
              <v-card-actions>
                <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="previewProtocol(item)" />
                <v-btn icon="mdi-download" size="small" variant="text" @click="downloadProtocol(item)" />
                <v-spacer />
                <v-btn
                  v-if="item.status === 'active'"
                  color="warning"
                  prepend-icon="mdi-archive-outline"
                  size="small"
                  variant="tonal"
                  @click="archiveProtocol(item)"
                >
                  Archiver
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text v-if="!loading && filteredProtocols.length === 0" class="text-center text-medium-emphasis py-8">
        {{ emptyStateMessage }}
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="1100" persistent scrollable>
      <v-card class="dialog-card" rounded="lg">
        <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
          <div class="d-flex align-center" style="gap: 12px;">
            <v-avatar color="white" size="40">
              <v-icon color="primary">mdi-file-document-plus-outline</v-icon>
            </v-avatar>
            <span class="text-h5 text-white">Nouveau protocole</span>
          </div>
          <v-btn color="white" icon="mdi-close" variant="text" @click="closeDialog" />
        </v-card-title>

        <v-card-text class="pa-8 form-scrollable">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.processId"
                item-title="title"
                item-value="value"
                :items="processOptions"
                label="Processus *"
                prepend-inner-icon="mdi-source-branch"
                rounded="lg"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.protocolName"
                label="Nom du protocole *"
                prepend-inner-icon="mdi-shield-check-outline"
                rounded="lg"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.procedureKey"
                :disabled="!form.processId"
                item-title="title"
                item-value="value"
                :items="proceduresForSelectedProcess"
                label="Procédure (optionnel)"
                prepend-inner-icon="mdi-file-document-multiple-outline"
                rounded="lg"
                variant="outlined"
              />
              <div class="d-flex align-center justify-space-between mt-1">
                <span class="text-caption text-medium-emphasis">Procédure absente ? Téléversez-la ici.</span>
                <v-btn
                  density="comfortable"
                  prepend-icon="mdi-upload"
                  size="small"
                  variant="text"
                  @click="openProcedureDialog(form.processId)"
                >
                  Ajouter une procédure
                </v-btn>
              </div>
              <div v-if="form.processId" class="text-caption text-medium-emphasis mt-1">
                {{ proceduresForSelectedProcess.length }} procédure(s) disponible(s) pour ce processus.
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.activityId"
                :disabled="!form.processId"
                item-title="title"
                item-value="value"
                :items="activitiesForSelectedProcess"
                label="Activité (optionnel)"
                prepend-inner-icon="mdi-format-list-bulleted-square"
                rounded="lg"
                variant="outlined"
              />
              <div v-if="form.processId" class="text-caption text-medium-emphasis mt-1">
                {{ activitiesForSelectedProcess.length }} activité(s) disponible(s) pour ce processus.
              </div>
            </v-col>

            <v-col cols="12">
              <v-alert density="comfortable" icon="mdi-information-outline" type="info" variant="tonal">
                Règle métier: le protocole doit être lié soit à une procédure, soit à une activité (ou les deux).
              </v-alert>
            </v-col>

            <v-col cols="12">
              <v-file-input
                v-model="form.file"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                label="Fichier protocole *"
                prepend-icon="mdi-paperclip"
                rounded="lg"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="pa-6 sticky-footer">
          <v-spacer />
          <v-btn rounded="lg" variant="text" @click="closeDialog">Annuler</v-btn>
          <v-btn
            color="primary"
            :loading="uploading"
            prepend-icon="mdi-content-save"
            rounded="lg"
            @click="createProtocol"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ProcedureUploadDialog
      v-model="procedureDialog"
      :default-process-id="procedureDialogProcessId"
      @created="handleProcedureCreated"
    />

    <ProtocolPreviewDialog
      v-model="previewDialog"
      :is-pdf="previewMime.includes('pdf')"
      :preview-download-id="previewDownloadId"
      :preview-error="previewError"
      :preview-file-url="previewFileUrl"
      :preview-loading="previewLoading"
      :preview-title="previewTitle"
      @close="closePreviewDialog"
      @download="downloadPreviewSource"
    />
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcedureUploadDialog from '@/modules/clienta/components/documents/ProcedureUploadDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ProductionServiceHero from '@/modules/clienta/pages/iso/operations/components/ProductionServiceHero.vue'
  import ProtocolCreateDialog from '@/modules/clienta/pages/iso/operations/components/ProtocolCreateDialog.vue'
  import ProtocolPreviewDialog from '@/modules/clienta/pages/iso/operations/components/ProtocolPreviewDialog.vue'
  import ProtocolsRegistry from '@/modules/clienta/pages/iso/operations/components/ProtocolsRegistry.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  interface ProcessOption {
    value: number
    title: string
    code: string
    ref: string
  }

  interface ProcedureOption {
    value: string
    id: number | string
    title: string
    processId: number | null
    source: 'modern' | 'legacy'
  }

  interface ActivityOption {
    value: number
    title: string
    processId: number | null
  }

  interface ProtocolRow {
    id: number
    documentId: number
    processId: number | null
    processName: string
    procedureName: string
    activityName: string
    name: string
    fileName: string
    fileSize: number
    uploadedAt: string
    status: 'active' | 'archived'
  }

  const toast = useToast()
  const authStore = useAuthStore()
  const loading = ref(false)
  const uploading = ref(false)
  const dialog = ref(false)
  const procedureDialog = ref(false)
  const procedureDialogProcessId = ref<number | null>(null)
  const previewDialog = ref(false)
  const viewMode = ref<'table' | 'grid'>('table')
  const previewLoading = ref(false)
  const previewError = ref('')
  const previewFileUrl = ref('')
  const previewObjectUrl = ref('')
  const previewMime = ref('')
  const previewTitle = ref('')
  const previewDownloadId = ref<number | null>(null)

  const processOptions = ref<ProcessOption[]>([])
  const procedureOptions = ref<ProcedureOption[]>([])
  const activityOptions = ref<ActivityOption[]>([])
  const smPlanYears = ref<number[]>([])
  const protocolRows = ref<ProtocolRow[]>([])
  const selectedSmPlanYear = ref(new Date().getFullYear())

  const search = ref('')
  const filterProcessId = ref<number | null>(null)
  const statusFilter = ref<'active' | 'archived' | null>(null)

  const form = reactive({
    processId: null as number | null,
    procedureKey: null as string | null,
    activityId: null as number | null,
    protocolName: '',
    file: null as File | null,
  })

  const statusOptions = [
    { title: 'Actif', value: 'active' },
    { title: 'Archivé', value: 'archived' },
  ]

  const headers = [
    { title: 'Processus', key: 'processName' },
    { title: 'Procédure', key: 'procedureName' },
    { title: 'Activité', key: 'activityName' },
    { title: 'Protocole', key: 'name' },
    { title: 'Fichier', key: 'fileName' },
    { title: 'Taille', key: 'fileSize' },
    { title: 'Téléversé le', key: 'uploadedAt' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const proceduresForSelectedProcess = computed(() => {
    const selected = Number(form.processId || 0)
    if (!selected) return []
    return procedureOptions.value
      .filter(item => Number(item.processId || 0) === selected)
      .toSorted((a, b) => a.title.localeCompare(b.title, 'fr'))
  })

  const activitiesForSelectedProcess = computed(() => {
    if (!form.processId) return activityOptions.value
    const linked = activityOptions.value.filter(item => item.processId === form.processId)
    if (linked.length > 0) return linked
    return activityOptions.value.filter(item => item.processId === null)
  })

  const filteredProtocols = computed(() => {
    const searchValue = search.value.trim().toLowerCase()
    return protocolRows.value.filter(item => {
      const haystack = [item.processName, item.procedureName, item.activityName, item.name, item.fileName]
        .join(' ')
        .toLowerCase()

      const matchesSearch = !searchValue || haystack.includes(searchValue)
      const matchesProcess = !filterProcessId.value || item.processId === filterProcessId.value
      const matchesStatus = !statusFilter.value || item.status === statusFilter.value
      return matchesSearch && matchesProcess && matchesStatus
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(search.value.trim() || filterProcessId.value || statusFilter.value)
  })

  const smPlanYearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    const rollingYears = Array.from({ length: 11 }, (_, index) => currentYear + 5 - index)
    const allYears = Array.from(new Set([...rollingYears, ...smPlanYears.value]))
      .filter(year => Number.isFinite(year))
      .toSorted((a, b) => b - a)

    return allYears.map(year => ({ title: String(year), value: year }))
  })

  const smPlanHasData = computed(() => activityOptions.value.length > 0)

  const emptyStateMessage = computed(() => {
    if (protocolRows.value.length === 0) {
      return 'Aucun protocole disponible pour ce site.'
    }

    return 'Aucun protocole trouvé avec ces filtres.'
  })

  function resetFilters (): void {
    search.value = ''
    filterProcessId.value = null
    statusFilter.value = null
  }

  function parseJsonApiCollection<T = any> (responseData: any): T[] {
    const data = responseData?.data
    if (!Array.isArray(data)) return []
    return data as T[]
  }

  function normalizeKey (value: unknown): string {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036F]/g, '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim()
  }

  function resetForm (): void {
    form.processId = null
    form.procedureKey = null
    form.activityId = null
    form.protocolName = ''
    form.file = null
  }

  function openDialog (): void {
    resetForm()
    dialog.value = true
  }

  function closeDialog (): void {
    dialog.value = false
    resetForm()
  }

  function openProcedureDialog (processId: number | null = null): void {
    procedureDialogProcessId.value = processId ?? form.processId ?? null
    procedureDialog.value = true
  }

  async function loadProcesses (): Promise<void> {
    const response = await processService.getProcesses({}, 1, 500)
    const rows = Array.isArray(response?.data) ? response.data : []
    processOptions.value = rows.map((item: any) => ({
      value: Number(item.id),
      title: String(item.title || item.name || item.code || `Processus #${item.id}`),
      code: String(item.code || item.ref || ''),
      ref: String(item.ref || ''),
    }))
  }

  function resolveProcessIdFromJsonApi (item: any): number | null {
    const relProcess = item?.relationships?.process
    const relId = Number(
      relProcess?.id
      || relProcess?.data?.id
        || relProcess?.attributes?.id
      || 0,
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

  async function loadActivities (): Promise<void> {
    const siteId = authStore.currentSiteId || undefined
    const targetYear = Number(selectedSmPlanYear.value || new Date().getFullYear())
    const rows: ActivityOption[] = []
    let activities: unknown[] = []

    const yearResponse = await api.get('/plans', {
      params: { site_id: siteId, type: 'smq', year: targetYear, per_page: 100 },
    })
    const yearItems = parseJsonApiCollection<any>(yearResponse.data)
    const yearPlan = yearItems[0]
    const yearContent = yearPlan?.attributes?.content
    if (yearContent && typeof yearContent === 'object' && Array.isArray((yearContent as any).activities)) {
      activities = (yearContent as any).activities
    }

    let sequence = 1
    for (const raw of activities) {
      const source = (raw && typeof raw === 'object') ? raw as Record<string, any> : {}
      const title = String(source.label || source.title || '').trim()
      if (!title) continue

      const explicitProcessId = Number(
        source.process_id
        ?? source.processId
          ?? source.linked_process_id
        ?? source.linkedProcessId
          ?? 0,
      ) || null

      rows.push({
        value: sequence,
        title,
        processId: explicitProcessId,
      })
      sequence += 1

      const subActivities = Array.isArray(source.subActivities) ? source.subActivities : []
      for (const rawSub of subActivities) {
        const subSource = (rawSub && typeof rawSub === 'object') ? rawSub as Record<string, any> : {}
        const subLabel = String(subSource.label || '').trim()
        if (!subLabel) continue

        rows.push({
          value: sequence,
          title: `${title} • ${subLabel}`,
          processId: explicitProcessId,
        })
        sequence += 1
      }
    }

    activityOptions.value = rows
  }

  async function loadSmPlanYears (): Promise<void> {
    const siteId = authStore.currentSiteId || undefined
    const response = await api.get('/plans', {
      params: { site_id: siteId, type: 'smq', per_page: 300 },
    })
    const rows = parseJsonApiCollection<any>(response.data)
    smPlanYears.value = Array.from(
      new Set(
        rows
          .map(item => Number(item?.attributes?.year ?? item?.year))
          .filter(year => Number.isFinite(year) && year >= 2020 && year <= 2100),
      ),
    ).toSorted((a, b) => b - a)
  }

  async function loadProcedures (): Promise<void> {
    const rows: ProcedureOption[] = []

    const modernResponse = await api.get('/documents', { params: { type: 'procedure' } })
    const modernItems = parseJsonApiCollection<any>(modernResponse.data)

    for (const item of modernItems) {
      const attributes = item?.attributes || {}
      rows.push({
        value: `modern:${item.id}`,
        id: Number(item.id),
        title: String(attributes.title || `Procédure #${item.id}`),
        processId: resolveProcessIdFromJsonApi(item),
        source: 'modern',
      })
    }

    const [legacyPRC, legacyPRD] = await Promise.all([
      api.get('/documents-inventory', { params: { type: 'PRC' } }),
      api.get('/documents-inventory', { params: { type: 'PRD' } }),
    ])

    const processByCode = new Map(processOptions.value.map(item => [normalizeKey(item.code), item.value]))
    const processByName = new Map(processOptions.value.map(item => [normalizeKey(item.title), item.value]))
    const processByRef = new Map(processOptions.value.map(item => [normalizeKey(item.ref), item.value]))

    for (const source of [legacyPRC.data, legacyPRD.data]) {
      const legacyItems = Array.isArray(source) ? source : []
      for (const item of legacyItems) {
        const processRaw = item?.processus || item?.process_name || item?.process?.title || item?.process?.code || ''
        const processLower = normalizeKey(processRaw)
        const directProcessId = Number(item?.process_id || item?.process?.id || 0)
        rows.push({
          value: `legacy:${item.id}`,
          id: Number(item.id),
          title: String(item?.nom || item?.code || `Procédure #${item.id}`),
          processId: directProcessId > 0
            ? directProcessId
            : (processByCode.get(processLower) || processByName.get(processLower) || processByRef.get(processLower) || null),
          source: 'legacy',
        })
      }
    }

    const deduped = new Map<string, ProcedureOption>()

    for (const row of rows) {
      const key = `${Number(row.processId || 0)}|${normalizeKey(row.title)}`
      const existing = deduped.get(key)

      // Prioriser les procédures modernes lorsqu'un doublon existe.
      if (!existing || (existing.source === 'legacy' && row.source === 'modern')) {
        deduped.set(key, row)
      }
    }

    procedureOptions.value = Array.from(deduped.values())
      .toSorted((a, b) => a.title.localeCompare(b.title, 'fr'))
  }

  function mapProtocolDocumentToRow (item: any): ProtocolRow | null {
    const id = Number(item?.id || 0)
    const attributes = item?.attributes || {}
    const metadata = attributes.metadata || {}

    if (!id || metadata?.module !== 'production_service_provision_protocol') {
      return null
    }

    const currentVersion = item?.relationships?.current_version || null
    const fileName = String(
      currentVersion?.file_original_name
      || attributes.file_path?.split('/').pop()
        || `${attributes.title || 'protocole'}.pdf`,
    )

    const statusRaw = String(attributes.status || '').toLowerCase()
    const status: 'active' | 'archived' = statusRaw === 'obsolete' || statusRaw === 'archived' ? 'archived' : 'active'

    return {
      id,
      documentId: id,
      processId: metadata.process_id ? Number(metadata.process_id) : null,
      processName: String(metadata.process_name || '-'),
      procedureName: String(metadata.procedure_name || '-'),
      activityName: String(metadata.activity_name || '-'),
      name: String(attributes.title || 'Protocole'),
      fileName,
      fileSize: Number(currentVersion?.file_size || 0),
      uploadedAt: String(attributes.created_at || ''),
      status,
    }
  }

  async function loadProtocols (): Promise<void> {
    const response = await api.get('/documents', { params: { type: 'instruction' } })
    const documents = parseJsonApiCollection<any>(response.data)

    protocolRows.value = documents
      .map(mapProtocolDocumentToRow)
      .filter(Boolean)
  }

  function getSelectedProcedure (): ProcedureOption | null {
    if (!form.procedureKey) return null
    return procedureOptions.value.find(item => item.value === form.procedureKey) || null
  }

  function getSelectedActivity (): ActivityOption | null {
    if (!form.activityId) return null
    return activityOptions.value.find(item => item.value === form.activityId) || null
  }

  function getSelectedProcess (): ProcessOption | null {
    if (!form.processId) return null
    return processOptions.value.find(item => item.value === form.processId) || null
  }

  async function createProtocol (): Promise<void> {
    const process = getSelectedProcess()
    const procedure = getSelectedProcedure()
    const activity = getSelectedActivity()

    if (!process) {
      toast.error('Sélectionnez le processus.')
      return
    }

    if (!procedure && !activity) {
      toast.error('Renseignez au moins une liaison: procédure ou activité.')
      return
    }

    const protocolName = form.protocolName.trim()
    if (!protocolName) {
      toast.error('Le nom du protocole est obligatoire.')
      return
    }

    if (!form.file) {
      toast.error('Le fichier protocole est obligatoire.')
      return
    }

    uploading.value = true
    try {
      const metadata = {
        module: 'production_service_provision_protocol',
        sm_plan_year: selectedSmPlanYear.value,
        process_id: process.value,
        process_name: process.title,
        procedure_id: procedure?.id ?? null,
        procedure_name: procedure?.title ?? null,
        procedure_source: procedure?.source ?? null,
        activity_id: activity?.value ?? null,
        activity_name: activity?.title ?? null,
      }

      const payload = new FormData()
      payload.append('file', form.file)
      payload.append('title', protocolName)
      payload.append('type', 'instruction')
      payload.append('category', 'qualite')
      payload.append('process_id', String(process.value))
      payload.append(
        'description',
        `Protocole pour ${activity?.title || 'activité non spécifiée'} / ${procedure?.title || 'procédure non spécifiée'}`,
      )
      payload.append('metadata[module]', metadata.module)
      payload.append('metadata[sm_plan_year]', String(metadata.sm_plan_year))
      payload.append('metadata[process_id]', String(metadata.process_id))
      payload.append('metadata[process_name]', metadata.process_name)
      if (metadata.procedure_id !== null && metadata.procedure_id !== undefined) {
        payload.append('metadata[procedure_id]', String(metadata.procedure_id))
      }
      if (metadata.procedure_name) {
        payload.append('metadata[procedure_name]', metadata.procedure_name)
      }
      if (metadata.procedure_source) {
        payload.append('metadata[procedure_source]', metadata.procedure_source)
      }
      if (metadata.activity_id !== null && metadata.activity_id !== undefined) {
        payload.append('metadata[activity_id]', String(metadata.activity_id))
      }
      if (metadata.activity_name) {
        payload.append('metadata[activity_name]', metadata.activity_name)
      }

      await api.upload('/documents', payload)

      toast.success('Protocole enregistré en base de données.')
      closeDialog()
      await loadProtocols()
    } catch {
      toast.error('Impossible d\'enregistrer le protocole.')
    } finally {
      uploading.value = false
    }
  }

  async function handleProcedureCreated (id: number): Promise<void> {
    await loadProcedures()
    form.procedureKey = id ? `legacy:${id}` : null
  }

  async function archiveProtocol (item: ProtocolRow): Promise<void> {
    if (!confirm(`Archiver le protocole "${item.name}" ?`)) return

    try {
      await api.post(`/documents/${item.documentId}/archive`)
      toast.success('Protocole archivé.')
      await loadProtocols()
    } catch {
      toast.error('Impossible d\'archiver le protocole.')
    }
  }

  async function downloadProtocol (item: ProtocolRow): Promise<void> {
    try {
      await api.download(`/documents/${item.documentId}/download`, item.fileName)
    } catch {
      toast.error('Téléchargement impossible.')
    }
  }

  async function previewProtocol (item: ProtocolRow): Promise<void> {
    closePreviewDialog()
    previewDialog.value = true
    previewLoading.value = true
    previewError.value = ''
    previewTitle.value = item.name
    previewDownloadId.value = item.documentId

    try {
      const response = await api.get(`/documents/${item.documentId}/download`, {
        responseType: 'arraybuffer',
        headers: { Accept: 'application/pdf,*/*' },
      })
      const bytes = new Uint8Array(response.data as ArrayBuffer)
      const header = new TextDecoder().decode(bytes.slice(0, 5))
      const isPdf = header === '%PDF-'
      const mimeFromResponse = String(response.headers?.['content-type'] || '').toLowerCase()
      const finalMime = (isPdf || item.fileName.toLowerCase().endsWith('.pdf') || mimeFromResponse.includes('pdf'))
        ? 'application/pdf'
        : (mimeFromResponse || 'application/octet-stream')

      previewMime.value = finalMime
      const blob = new Blob([bytes], { type: finalMime })
      const baseUrl = URL.createObjectURL(blob)
      previewObjectUrl.value = baseUrl
      previewFileUrl.value = finalMime.includes('pdf') ? `${baseUrl}#toolbar=1&navpanes=0&view=FitH` : baseUrl
    } catch {
      previewError.value = 'Prévisualisation impossible pour ce protocole.'
    } finally {
      previewLoading.value = false
    }
  }

  function closePreviewDialog (): void {
    previewDialog.value = false
    previewLoading.value = false
    previewError.value = ''
    previewMime.value = ''
    previewTitle.value = ''
    previewDownloadId.value = null
    if (previewObjectUrl.value) {
      URL.revokeObjectURL(previewObjectUrl.value)
      previewObjectUrl.value = ''
    }
    if (previewFileUrl.value) {
      previewFileUrl.value = ''
    }
  }

  async function downloadPreviewSource (): Promise<void> {
    if (!previewDownloadId.value) return
    try {
      await api.download(`/documents/${previewDownloadId.value}/download`, `${previewTitle.value || 'protocole'}.pdf`)
    } catch {
      toast.error('Téléchargement impossible.')
    }
  }

  function formatDate (value: string): string {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('fr-FR')
  }

  function formatFileSize (bytes: number): string {
    if (!bytes || bytes <= 0) return '-'
    if (bytes < 1024) return `${bytes} o`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
  }

  watch(() => form.processId, () => {
    form.procedureKey = null
    form.activityId = null
  })

  watch(previewDialog, value => {
    if (!value) closePreviewDialog()
  })

  watch(selectedSmPlanYear, async () => {
    try {
      loading.value = true
      await loadActivities()
    } catch {
      toast.error('Impossible de charger le plan SM pour cette année.')
      activityOptions.value = []
    } finally {
      loading.value = false
    }
  })

  onMounted(async () => {
    loading.value = true
    try {
      await loadProcesses()
      await Promise.all([loadSmPlanYears(), loadProcedures()])
      await loadActivities()
      await loadProtocols()
    } catch {
      toast.error('Chargement des données impossible pour ce module.')
    } finally {
      loading.value = false
    }
  })
</script>

<style scoped>
</style>
