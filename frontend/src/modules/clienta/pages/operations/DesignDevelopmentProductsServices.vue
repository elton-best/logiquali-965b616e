<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      icon="mdi-drawing-box"
      icon-color="primary"
      subtitle="Maîtrise documentaire de la conception et du développement"
      title="Conception et développement de produit et service"
    >
      <template #actions>
        <v-btn
          class="mr-2"
          color="secondary"
          prepend-icon="mdi-file-multiple"
          rounded="lg"
          variant="tonal"
          @click="openBulkDialog"
        >
          Import en lot
        </v-btn>
        <v-btn
          class="mr-2"
          color="primary"
          prepend-icon="mdi-file-document-plus-outline"
          rounded="lg"
          variant="tonal"
          @click="openProcedureDialog"
        >
          Ajouter une procédure
        </v-btn>
        <v-btn
          class="mr-2"
          color="primary"
          prepend-icon="mdi-file-document-edit-outline"
          rounded="lg"
          variant="tonal"
          @click="openTemplateDialog"
        >
          Générer via canevas
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openSingleDialog">
          Ajouter un document
        </v-btn>
      </template>
    </PageHeader>

    <DesignIsoChecklist :items="isoChecklist" />

    <DesignDocumentsKpis :kpis="kpis" />

    <DesignDocumentsFilters
      :etat-options="etatOptions"
      :filters="filters"
      :has-active-filters="hasActiveFilters"
      :loading="loading"
      :process-options="processOptions"
      :statut-options="statutOptions"
      :type-options="typeOptions"
      @refresh="loadDocuments"
      @reset="resetFilters"
    />

    <DesignDocumentsTable
      :documents="filteredDocuments"
      :empty-state-message="emptyStateMessage"
      :etat-color="etatColor"
      :etat-label="etatLabel"
      :format-date="formatDate"
      :headers="headers"
      :loading="loading"
      :process-label="processLabel"
      :statut-color="statutColor"
      :statut-label="statutLabel"
      :type-label="typeLabel"
      @download="downloadFile"
      @view="viewFile"
    />

    <DesignPreviewDialog
      v-model="previewDialog"
      :is-preview-excel="isPreviewExcel"
      :is-preview-image="isPreviewImage"
      :is-preview-pdf="isPreviewPdf"
      :preview-document-name="previewDocumentName"
      :preview-excel-rows="previewExcelRows"
      :preview-loading="previewLoading"
      :preview-object-url="previewObjectUrl"
      @close="closePreviewDialog"
      @download="downloadCurrentPreviewFile"
    />

    <DesignSingleDocumentDialog
      v-model="singleDialog"
      :etat-options="etatOptions"
      :form="singleForm"
      :process-options="processOptions"
      :statut-options="statutOptions"
      :submitting="submittingSingle"
      :type-options="typeOptions"
      @file-selected="onSingleFileSelected"
      @submit="submitSingleDocument"
    />

    <DesignBulkDocumentsDialog
      v-model="bulkDialog"
      :bulk-files-count="bulkFiles.length"
      :etat-options="etatOptions"
      :form="bulkForm"
      :process-options="processOptions"
      :statut-options="statutOptions"
      :submitting="submittingBulk"
      :type-options="typeOptions"
      @files-selected="onBulkFilesSelected"
      @submit="submitBulkDocuments"
    />
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
  import { computed, onMounted, ref, watch } from 'vue'
  import * as XLSX from 'xlsx'
  import api from '@/api/client'
  import { documentsApi } from '@/api/documents'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useDocuments } from '@/modules/clienta/composables/useDocuments'
  import DesignBulkDocumentsDialog from '@/modules/clienta/pages/iso/operations/components/DesignBulkDocumentsDialog.vue'
  import DesignDocumentsFilters from '@/modules/clienta/pages/iso/operations/components/DesignDocumentsFilters.vue'
  import DesignDocumentsKpis from '@/modules/clienta/pages/iso/operations/components/DesignDocumentsKpis.vue'
  import DesignDocumentsTable from '@/modules/clienta/pages/iso/operations/components/DesignDocumentsTable.vue'
  import DesignIsoChecklist from '@/modules/clienta/pages/iso/operations/components/DesignIsoChecklist.vue'
  import DesignPreviewDialog from '@/modules/clienta/pages/iso/operations/components/DesignPreviewDialog.vue'
  import DesignSingleDocumentDialog from '@/modules/clienta/pages/iso/operations/components/DesignSingleDocumentDialog.vue'
  import { DocumentHelpers } from '@/modules/clienta/types/document-unified.types'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  type DocumentType = 'POL' | 'PRC' | 'PRD' | 'FOR' | 'ENR'
  type DocumentStatut = 'brouillon' | 'en_revision' | 'valide'
  type DocumentEtat = 'a_etablir' | 'en_cours' | 'termine'

  const toast = useToast()
  const authStore = useAuthStore()
  const { documents, loading, fetchDocuments, createDocument } = useDocuments()

  const isoChecklist = [
    'Planification de la conception',
    'Données d’entrée formalisées',
    'Revues de conception',
    'Validation des sorties',
    'Maîtrise des modifications',
  ]

  const typeOptions = [
    { title: 'Politique', value: 'POL' },
    { title: 'Procédure', value: 'PRC' },
    { title: 'Procédure détaillée', value: 'PRD' },
    { title: 'Formulaire', value: 'FOR' },
    { title: 'Enregistrement', value: 'ENR' },
  ]

  const procedureTypeOptions = [
    { title: 'Procédure', value: 'PRC' },
    { title: 'Procédure détaillée', value: 'PRD' },
  ]

  const statutOptions = [
    { title: 'Brouillon', value: 'brouillon' },
    { title: 'En révision', value: 'en_revision' },
    { title: 'Validé', value: 'valide' },
  ]

  const etatOptions = [
    { title: 'À établir', value: 'a_etablir' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Terminé', value: 'termine' },
  ]

  const headers = [
    { title: 'Code', key: 'code' },
    { title: 'Nom du document', key: 'nom' },
    { title: 'Type', key: 'type' },
    { title: 'Processus lié', key: 'processus' },
    { title: 'Statut', key: 'statut' },
    { title: 'État', key: 'etat' },
    { title: 'Date création', key: 'date_creation' },
    { title: 'Date validation', key: 'validated_at' },
    { title: 'Actions fichier', key: 'fichier', sortable: false },
    { title: 'Détails', key: 'details', sortable: false },
  ]

  const filters = ref({
    search: '',
    type: null as DocumentType | null,
    processus: null as string | null,
    statut: null as DocumentStatut | null,
    etat: null as DocumentEtat | null,
  })

  const singleDialog = ref(false)
  const bulkDialog = ref(false)
  const templateDialog = ref(false)
  const submittingSingle = ref(false)
  const submittingBulk = ref(false)
  const submittingTemplate = ref(false)
  const singleFile = ref<File | null>(null)
  const bulkFiles = ref<File[]>([])
  const templateAttachments = ref<File[]>([])
  const previewDialog = ref(false)
  const previewLoading = ref(false)
  const previewObjectUrl = ref<string | null>(null)
  const previewMimeType = ref('')
  const previewDocumentName = ref('')
  const currentPreviewDocumentId = ref<number | null>(null)
  const previewExcelRows = ref<Array<Array<string | number | boolean | null>>>([])
  const processOptions = ref<Array<{ title: string, value: string }>>([])
  const detailsDialog = ref(false)
  const selectedDocument = ref<UnifiedDocument | null>(null)
  const addingVersion = ref(false)
  const versionForm = ref({
    version: '',
    modifications: '',
    fichier: null as File | null,
  })

  const singleForm = ref({
    nom: '',
    code: '',
    type: 'PRC' as DocumentType,
    processus: null as string | null,
    statut: 'brouillon' as DocumentStatut,
    etat: 'a_etablir' as DocumentEtat,
    date_creation: new Date().toISOString().split('T')[0],
    validated_at: '',
    periodicite_revision: null as number | null,
    description: '',
  })

  const bulkForm = ref({
    type: 'ENR' as DocumentType,
    processus: null as string | null,
    statut: 'brouillon' as DocumentStatut,
    etat: 'a_etablir' as DocumentEtat,
    date_creation: new Date().toISOString().split('T')[0],
    validated_at: '',
    description: '',
  })

  const templateForm = ref({
    nom: '',
    code: '',
    type: 'PRC' as DocumentType,
    processus: null as string | null,
    statut: 'brouillon' as DocumentStatut,
    etat: 'a_etablir' as DocumentEtat,
    date_creation: new Date().toISOString().split('T')[0],
    validated_at: '',
    periodicite_revision: null as number | null,
    responsable: '',
    objet: '',
    champ_application: '',
    termes_definitions: '',
    references: '',
    diffusion: '',
    acteurs: '',
    support_faits: '',
    formulaire_support: '',
    archivage: '',
    mise_a_jour: '',
    mise_en_oeuvre: [
      { activite: '', details: '', responsable: '', livrables: '', delai: '' },
    ] as Array<{ activite: string, details: string, responsable: string, livrables: string, delai: string }>,
  })

  const scopedDocuments = computed(() => documents.value)
  const isSelectedProcedure = computed(() => ['PRC', 'PRD'].includes(String(selectedDocument.value?.type || '')))
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

  const filteredDocuments = computed(() => {
    const search = filters.value.search.trim().toLowerCase()
    return scopedDocuments.value.filter(doc => {
      if (search && !(`${doc.nom} ${doc.code}`.toLowerCase().includes(search))) return false
      if (filters.value.type && doc.type !== filters.value.type) return false
      if (filters.value.processus && doc.processus !== filters.value.processus) return false
      if (filters.value.statut && doc.statut !== filters.value.statut) return false
      if (filters.value.etat && doc.etat !== filters.value.etat) return false
      return true
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(
      filters.value.search.trim()
        || filters.value.type
      || filters.value.processus
        || filters.value.statut
      || filters.value.etat,
    )
  })

  const emptyStateMessage = computed(() => {
    if (scopedDocuments.value.length === 0) {
      return 'Aucun document de conception et développement disponible pour ce site.'
    }

    return 'Aucun document ne correspond aux filtres sélectionnés.'
  })

  function resetFilters (): void {
    filters.value = {
      search: '',
      type: null,
      processus: null,
      statut: null,
      etat: null,
    }
  }

  async function openDetails (doc: UnifiedDocument): Promise<void> {
    try {
      const response = await documentsApi.getById(doc.id)
      selectedDocument.value = response.data
      resetVersionForm()
      detailsDialog.value = true
    } catch {
      toast.error('Impossible de charger les détails de la procédure.')
    }
  }

  function closeDetails (): void {
    detailsDialog.value = false
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

  const kpis = computed(() => {
    const source = scopedDocuments.value
    const now = new Date()

    return {
      total: source.length,
      valides: source.filter(doc => doc.statut === 'valide').length,
      enRevision: source.filter(doc => doc.statut === 'en_revision').length,
      aReviser: source.filter(doc => doc.prochaine_revision && new Date(doc.prochaine_revision) <= now).length,
    }
  })

  function currentSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId
      ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
      ?? (authStore.user as { site_id?: number } | null)?.site_id
      ?? null
  }

  async function loadDocuments (): Promise<void> {
    const siteId = currentSiteId()
    await fetchDocuments({
      site_id: siteId ?? undefined,
    })
  }

  async function loadProcesses (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      processOptions.value = []
      return
    }

    try {
      const response = await processService.getProcesses({ site_id: siteId }, 1, 300)
      const apiOptions = (response.data || [])
        .map(process => {
          const code = String(process.code || process.ref || '').trim()
          const name = String(process.name || process.title || '').trim()
          if (!name && !code) return null
          return {
            title: code && name ? `${code} - ${name}` : (name || code),
            value: code || name,
          }
        })
        .filter(Boolean) as Array<{ title: string, value: string }>

      let scopeOptions: Array<{ title: string, value: string }> = []
      try {
        let scopeResponse = await api.get('/application-scopes', {
          params: { site_id: siteId, is_current: true },
        })
        let dataRows = Array.isArray(scopeResponse.data?.data) ? scopeResponse.data.data : []
        if (dataRows.length === 0) {
          scopeResponse = await api.get('/application-scopes', {
            params: { site_id: siteId },
          })
          dataRows = Array.isArray(scopeResponse.data?.data) ? scopeResponse.data.data : []
        }

        const list = dataRows.flatMap((row: any) => {
          const scope = row?.attributes || row
          return Array.isArray(scope?.processes) ? scope.processes : []
        })

        scopeOptions = list
          .map((process: any) => {
            const code = String(process?.code || '').trim()
            const name = String(process?.name || process?.title || '').trim()
            if (!name && !code) return null
            return {
              title: code && name ? `${code} - ${name}` : (name || code),
              value: code || name,
            }
          })
          .filter(Boolean) as Array<{ title: string, value: string }>
      } catch {
        scopeOptions = []
      }

      const merged = [...apiOptions]
      const seen = new Set(apiOptions.map(item => item.value.trim().toLowerCase()))
      for (const option of scopeOptions) {
        const key = option.value.trim().toLowerCase()
        if (!key || seen.has(key)) continue
        merged.push(option)
        seen.add(key)
      }

      processOptions.value = merged
    } catch {
      processOptions.value = []
    }
  }

  function isAllowedProcessForSelectedSite (processCode: string | null): boolean {
    if (!processCode) return false
    return processOptions.value.some(item => item.value === processCode)
  }

  function buildFormData (payload: {
    nom: string
    code?: string
    type: DocumentType
    statut: DocumentStatut
    etat: DocumentEtat
    date_creation: string
    validated_at?: string
    description?: string
    periodicite_revision?: number | null
    processus?: string | null
    fichier?: File | null
  }): FormData {
    const siteId = currentSiteId()
    const formData = new FormData()

    formData.append('site_id', String(siteId))
    formData.append('processus', String(payload.processus))
    formData.append('type', payload.type)
    formData.append('statut', payload.statut)
    formData.append('etat', payload.etat)
    formData.append('nom', payload.nom)
    formData.append('date_creation', payload.date_creation)

    if (payload.code?.trim()) formData.append('code', payload.code.trim())
    if (payload.description?.trim()) formData.append('description', payload.description.trim())
    if (payload.validated_at && payload.statut === 'valide') formData.append('validated_at', payload.validated_at)
    if (payload.periodicite_revision && payload.periodicite_revision > 0) formData.append('periodicite_revision', String(payload.periodicite_revision))
    if (payload.fichier instanceof File) formData.append('fichier', payload.fichier)

    return formData
  }

  function resetSingleForm (): void {
    singleForm.value = {
      nom: '',
      code: '',
      type: 'PRC',
      processus: null,
      statut: 'brouillon',
      etat: 'a_etablir',
      date_creation: new Date().toISOString().split('T')[0],
      validated_at: '',
      periodicite_revision: null,
      description: '',
    }
    singleFile.value = null
  }

  function openSingleDialog (): void {
    if (!currentSiteId()) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    resetSingleForm()
    singleDialog.value = true
    if (processOptions.value.length === 0) {
      void loadProcesses()
      toast.info('Aucun processus trouvé pour ce site. Vérifiez le domaine d’application.')
    }
  }

  function openProcedureDialog (): void {
    if (!currentSiteId()) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    resetSingleForm()
    singleForm.value.type = 'PRC'
    singleDialog.value = true
    if (processOptions.value.length === 0) {
      void loadProcesses()
      toast.info('Aucun processus trouvé pour ce site. Vérifiez le domaine d’application.')
    }
  }

  function openBulkDialog (): void {
    if (!currentSiteId()) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    bulkDialog.value = true
    if (processOptions.value.length === 0) {
      void loadProcesses()
      toast.info('Aucun processus trouvé pour ce site. Vérifiez le domaine d’application.')
    }
  }

  function resetTemplateForm (): void {
    templateForm.value = {
      nom: '',
      code: '',
      type: 'PRC',
      processus: null,
      statut: 'brouillon',
      etat: 'a_etablir',
      date_creation: new Date().toISOString().split('T')[0],
      validated_at: '',
      periodicite_revision: null,
      responsable: '',
      objet: '',
      champ_application: '',
      termes_definitions: '',
      references: '',
      diffusion: '',
      acteurs: '',
      support_faits: '',
      formulaire_support: '',
      archivage: '',
      mise_a_jour: '',
      mise_en_oeuvre: [
        { activite: '', details: '', responsable: '', livrables: '', delai: '' },
      ],
    }
    templateAttachments.value = []
  }

  function openTemplateDialog (): void {
    if (!currentSiteId()) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    resetTemplateForm()
    templateDialog.value = true
    if (processOptions.value.length === 0) {
      void loadProcesses()
      toast.info('Aucun processus trouvé pour ce site. Vérifiez le domaine d’application.')
    }
  }

  function addMiseRow (): void {
    templateForm.value.mise_en_oeuvre.push({
      activite: '',
      details: '',
      responsable: '',
      livrables: '',
      delai: '',
    })
  }

  function removeMiseRow (index: number): void {
    templateForm.value.mise_en_oeuvre.splice(index, 1)
    if (templateForm.value.mise_en_oeuvre.length === 0) {
      addMiseRow()
    }
  }

  function toFile (value: unknown): File | null {
    if (value instanceof File) return value

    if (value && typeof value === 'object') {
      const maybeRaw = (value as { raw?: unknown }).raw
      if (maybeRaw instanceof File) return maybeRaw

      const maybeFile = (value as { file?: unknown }).file
      if (maybeFile instanceof File) return maybeFile
    }

    return null
  }

  function normalizeFiles (value: unknown): File[] {
    if (Array.isArray(value)) {
      return value
        .map(item => toFile(item))
        .filter((file): file is File => file instanceof File)
    }

    const file = toFile(value)
    return file ? [file] : []
  }

  function onSingleFileSelected (value: File | File[] | null): void {
    singleFile.value = normalizeFiles(value)[0] ?? null
  }

  function onBulkFilesSelected (value: File[] | null): void {
    bulkFiles.value = normalizeFiles(value)
  }

  async function submitSingleDocument (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    if (!singleForm.value.nom.trim()) {
      toast.error('Le nom du document est obligatoire.')
      return
    }
    if (!singleForm.value.processus) {
      toast.error('Sélectionnez un processus lié.')
      return
    }
    if (!isAllowedProcessForSelectedSite(singleForm.value.processus)) {
      toast.error('Le processus choisi ne correspond pas au site sélectionné.')
      return
    }

    submittingSingle.value = true
    try {
      const formData = buildFormData({
        ...singleForm.value,
        processus: singleForm.value.processus,
        fichier: singleFile.value,
      })

      await createDocument(formData)
      await loadDocuments()
      singleDialog.value = false
      toast.success('Document ajouté avec succès.')
    } catch {
      toast.error('Impossible d’ajouter le document.')
    } finally {
      submittingSingle.value = false
    }
  }

  function fileNameWithoutExtension (filename: string): string {
    return filename.replace(/\.[^/.]+$/, '').trim()
  }

  async function submitBulkDocuments (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    if (bulkFiles.value.length === 0) {
      toast.error('Ajoutez au moins un fichier.')
      return
    }
    if (!bulkForm.value.processus) {
      toast.error('Sélectionnez un processus lié.')
      return
    }
    if (!isAllowedProcessForSelectedSite(bulkForm.value.processus)) {
      toast.error('Le processus choisi ne correspond pas au site sélectionné.')
      return
    }

    submittingBulk.value = true
    let successCount = 0

    try {
      for (const file of bulkFiles.value) {
        const formData = buildFormData({
          nom: fileNameWithoutExtension(file.name),
          type: bulkForm.value.type,
          statut: bulkForm.value.statut,
          etat: bulkForm.value.etat,
          date_creation: bulkForm.value.date_creation,
          validated_at: bulkForm.value.validated_at,
          description: bulkForm.value.description,
          processus: bulkForm.value.processus,
          fichier: file,
        })
        await createDocument(formData)
        successCount += 1
      }

      await loadDocuments()
      bulkDialog.value = false
      bulkFiles.value = []
      toast.success(`${successCount} document(s) importé(s).`)
    } catch {
      toast.error(`Import partiel: ${successCount} document(s) importé(s) avant erreur.`)
    } finally {
      submittingBulk.value = false
    }
  }

  function buildTemplatePayload (): FormData {
    const siteId = currentSiteId()
    const formData = new FormData()
    const payload = templateForm.value

    formData.append('site_id', String(siteId))
    formData.append('processus', String(payload.processus || ''))
    formData.append('type', payload.type)
    formData.append('statut', payload.statut)
    formData.append('etat', payload.etat)
    formData.append('nom', payload.nom)
    formData.append('date_creation', payload.date_creation)
    if (payload.code?.trim()) formData.append('code', payload.code.trim())
    if (payload.validated_at && payload.statut === 'valide') formData.append('validated_at', payload.validated_at)
    if (payload.periodicite_revision && payload.periodicite_revision > 0) {
      formData.append('periodicite_revision', String(payload.periodicite_revision))
    }

    const procedureData = {
      objet: payload.objet,
      champ_application: payload.champ_application,
      termes_definitions: payload.termes_definitions,
      references: payload.references,
      diffusion: payload.diffusion,
      acteurs: payload.acteurs,
      support_faits: payload.support_faits,
      formulaire_support: payload.formulaire_support,
      archivage: payload.archivage,
      mise_a_jour: payload.mise_a_jour,
      responsable: payload.responsable,
      mise_en_oeuvre: payload.mise_en_oeuvre,
      redacteur: payload.responsable,
      verificateur: '',
      approbateur: '',
    }

    formData.append('procedure_data', JSON.stringify(procedureData))

    for (const file of templateAttachments.value) {
      formData.append('attachments[]', file)
    }

    return formData
  }

  async function submitTemplateProcedure (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }
    if (!templateForm.value.nom.trim()) {
      toast.error('Le nom de la procédure est obligatoire.')
      return
    }
    if (!templateForm.value.processus) {
      toast.error('Sélectionnez un processus lié.')
      return
    }
    if (!isAllowedProcessForSelectedSite(templateForm.value.processus)) {
      toast.error('Le processus choisi ne correspond pas au site sélectionné.')
      return
    }

    submittingTemplate.value = true
    try {
      const formData = buildTemplatePayload()
      await documentsApi.generateProcedure(formData)
      await loadDocuments()
      templateDialog.value = false
      toast.success('Procédure générée avec succès.')
    } catch {
      toast.error('Impossible de générer la procédure.')
    } finally {
      submittingTemplate.value = false
    }
  }

  function formatDate (value?: string): string {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '-'
    return date.toLocaleDateString('fr-FR')
  }

  function typeLabel (type: DocumentType): string {
    return typeOptions.find(option => option.value === type)?.title || type
  }

  function statutLabel (statut: DocumentStatut): string {
    return statutOptions.find(option => option.value === statut)?.title || statut
  }

  function statutColor (statut: DocumentStatut): string {
    if (statut === 'valide') return 'success'
    if (statut === 'en_revision') return 'warning'
    return 'grey'
  }

  function etatLabel (etat: DocumentEtat): string {
    return etatOptions.find(option => option.value === etat)?.title || etat
  }

  function etatColor (etat: DocumentEtat): string {
    if (etat === 'termine') return 'success'
    if (etat === 'en_cours') return 'info'
    return 'grey'
  }

  function processLabel (processCode?: string): string {
    if (!processCode) return '-'
    return processOptions.value.find(item => item.value === processCode)?.title || processCode
  }

  const isPreviewPdf = computed(() =>
    previewMimeType.value.includes('pdf') || previewDocumentName.value.toLowerCase().endsWith('.pdf'),
  )

  const isPreviewImage = computed(() =>
    previewMimeType.value.startsWith('image/') || /\.(png|jpg|jpeg|gif|webp|svg)$/i.test(previewDocumentName.value),
  )

  const isPreviewExcel = computed(() =>
    previewMimeType.value.includes('spreadsheet') || /\.(xls|xlsx)$/i.test(previewDocumentName.value),
  )

  function revokePreviewObjectUrl (): void {
    if (previewObjectUrl.value) {
      URL.revokeObjectURL(previewObjectUrl.value)
      previewObjectUrl.value = null
    }
  }

  function closePreviewDialog (): void {
    previewDialog.value = false
    currentPreviewDocumentId.value = null
    previewMimeType.value = ''
    previewDocumentName.value = ''
    previewExcelRows.value = []
    revokePreviewObjectUrl()
  }

  async function viewFile (document: UnifiedDocument): Promise<void> {
    if (!document.fichier) return

    previewLoading.value = true
    previewDialog.value = true
    previewDocumentName.value = document.nom || 'document'
    currentPreviewDocumentId.value = document.id
    revokePreviewObjectUrl()

    try {
      const response = await documentsApi.previewFile(document.id)
      const blob = response.data as Blob
      previewMimeType.value = blob.type || ''

      if (isPreviewExcel.value) {
        const buffer = await blob.arrayBuffer()
        const workbook = XLSX.read(buffer, { type: 'array' })
        const firstSheetName = workbook.SheetNames[0]
        const worksheet = firstSheetName ? workbook.Sheets[firstSheetName] : null
        const rows = worksheet
          ? (XLSX.utils.sheet_to_json(worksheet, { header: 1 }) as Array<Array<string | number | boolean | null>>)
          : []
        previewExcelRows.value = rows.slice(0, 200)
      } else {
        previewObjectUrl.value = URL.createObjectURL(blob)
      }
    } catch {
      toast.error('Impossible de charger la prévisualisation.')
      closePreviewDialog()
    } finally {
      previewLoading.value = false
    }
  }

  function safeFilename (name: string): string {
    return name.replace(/[^\w\-.\s]/g, '').trim() || 'document'
  }

  function parseFilenameFromDisposition (value: string | undefined): string | null {
    if (!value) return null
    const utfMatch = value.match(/filename\*=UTF-8''([^;]+)/i)
    if (utfMatch?.[1]) return decodeURIComponent(utfMatch[1])
    const simpleMatch = value.match(/filename=\"?([^\";]+)\"?/i)
    return simpleMatch?.[1] || null
  }

  function triggerBrowserDownload (blob: Blob, filename: string): void {
    if (!blob || blob.size === 0) {
      throw new Error('empty blob')
    }

    const objectUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = objectUrl
    link.download = filename
    link.style.display = 'none'
    document.body.append(link)
    link.click()
    link.remove()

    // Certains navigateurs annulent le téléchargement si la révocation est immédiate.
    window.setTimeout(() => {
      window.URL.revokeObjectURL(objectUrl)
    }, 1500)
  }

  async function downloadFile (document: UnifiedDocument): Promise<void> {
    if (!document.fichier) return
    const fallbackExt = (document.fichier.split('.').pop() || 'bin').toLowerCase()
    const fallbackName = `${safeFilename(document.nom)}.${fallbackExt}`

    try {
      // Le endpoint preview est déjà validé côté UI : on l'utilise comme source fiable de blob.
      const response = await documentsApi.previewFile(document.id)
      const blob = response.data as Blob
      triggerBrowserDownload(blob, fallbackName)
    } catch {
      try {
        const response = await documentsApi.downloadFile(document.id)
        const blob = response.data as Blob
        const headerName = parseFilenameFromDisposition(response.headers['content-disposition'])
        const finalName = headerName || fallbackName

        triggerBrowserDownload(blob, finalName)
      } catch {
        toast.error('Impossible de télécharger le fichier.')
      }
    }
  }

  async function downloadCurrentPreviewFile (): Promise<void> {
    if (!currentPreviewDocumentId.value) return
    const doc = filteredDocuments.value.find(item => item.id === currentPreviewDocumentId.value)
    if (!doc) return
    await downloadFile(doc)
  }

  onMounted(async () => {
    await Promise.all([loadProcesses(), loadDocuments()])
  })

  watch(() => authStore.currentSiteId, async () => {
    await Promise.all([loadProcesses(), loadDocuments()])
    if (!isAllowedProcessForSelectedSite(singleForm.value.processus)) singleForm.value.processus = null
    if (!isAllowedProcessForSelectedSite(bulkForm.value.processus)) bulkForm.value.processus = null
    if (!isAllowedProcessForSelectedSite(filters.value.processus)) filters.value.processus = null
  })
</script>

<style scoped>
</style>
