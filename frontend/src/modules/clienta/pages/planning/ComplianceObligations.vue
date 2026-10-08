<template>
  <ClientALayout :current-page="layoutCurrentPage">
    <PageHeader
      :icon="pageIcon"
      icon-color="primary"
      :subtitle="pageSubtitle"
      :title="pageTitle"
    >
      <template #actions>
        <input
          ref="xlsxInput"
          accept=".xlsx,.xls"
          class="d-none"
          type="file"
          @change="handleImportFileSelected"
        >
        <v-btn
          class="mr-2"
          color="secondary"
          prepend-icon="mdi-upload"
          rounded="lg"
          variant="outlined"
          @click="showImportDialog = true"
        >
          Importer via modèle Excel
        </v-btn>
        <v-btn
          class="mr-2"
          color="primary"
          prepend-icon="mdi-download"
          rounded="lg"
          variant="outlined"
          @click="exportXlsx"
        >
          Export Excel
        </v-btn>
        <v-btn
          class="mr-2"
          color="secondary"
          prepend-icon="mdi-shape-plus"
          rounded="lg"
          variant="tonal"
          @click="openAspectDialog"
        >
          Ajouter un volet/aspect
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openDialog()">
          Ajouter un texte
        </v-btn>
      </template>
    </PageHeader>

    <v-progress-linear v-if="loading" class="mb-4" color="primary" indeterminate />

    <ComplianceFilters
      :aspects="aspects"
      :compliance-options="complianceOptions"
      :filter-compliance="filterCompliance"
      :filter-validity="filterValidity"
      :has-active-filters="hasActiveFilters"
      :loading="loading"
      :search="search"
      :selected-aspect-id="selectedAspectId"
      :validity-options="validityOptions"
      @refresh="fetchData"
      @reset="resetFilters"
      @update:filter-compliance="filterCompliance = ($event as ComplianceStatus | null)"
      @update:filter-validity="filterValidity = ($event as ValidityStatus | null)"
      @update:search="search = $event"
      @update:selected-aspect-id="selectedAspectId = $event"
    />

    <ComplianceTable
      :compliance-color="complianceColor"
      :compliance-label="complianceLabel"
      :empty-state-message="emptyStateMessage"
      :items="filteredItems"
      @edit="openDialog"
      @remove="removeItem"
    />

    <ComplianceDialog
      v-model="dialog"
      :action-status-options="actionStatusOptions"
      :aspect-options="aspectOptions"
      :change-status-options="changeStatusOptions"
      :compliance-options="complianceOptions"
      :create-dialog-title="createDialogTitle"
      :edit-dialog-title="editDialogTitle"
      :form="form"
      :frequency-options="frequencyOptions"
      :is-editing="Boolean(editId)"
      :reference-field-label="referenceFieldLabel"
      :saving="saving"
      :user-options="userOptions"
      :validity-options="validityOptions"
      @add-action="addAction"
      @close="closeDialog"
      @open-aspect="openAspectDialog"
      @remove-action="removeAction"
      @save="saveItem"
    />

    <ComplianceAspectDialog
      v-model="aspectDialog"
      :aspect-form="aspectForm"
      :norm-options="normOptions"
      :saving="savingAspect"
      @close="closeAspectDialog"
      @save="saveAspect"
    />

    <v-dialog v-model="showImportDialog" max-width="760" persistent>
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center justify-space-between pa-6 bg-secondary">
          <div class="d-flex align-center" style="gap: 12px;">
            <v-avatar color="white" size="40">
              <v-icon color="secondary">mdi-file-import-outline</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 text-white">{{ importDialogTitle }}</div>
              <div class="text-body-2 text-white" style="opacity: 0.9;">
                Télécharger le modèle de base puis importer votre fichier complété.
              </div>
            </div>
          </div>
          <v-btn color="white" icon="mdi-close" variant="text" @click="closeImportDialog" />
        </v-card-title>

        <v-card-text class="pa-6">
          <v-alert class="mb-4" type="info" variant="tonal">
            <div class="font-weight-bold mb-1">Mode d'emploi</div>
            <div>{{ importDialogInstructions }}</div>
          </v-alert>

          <div class="d-flex flex-column" style="gap: 16px;">
            <v-btn
              color="secondary"
              prepend-icon="mdi-file-download-outline"
              rounded="lg"
              variant="outlined"
              @click="downloadTemplateXlsx"
            >
              Télécharger le modèle de base
            </v-btn>

            <v-file-input
              v-model="selectedImportFile"
              accept=".xlsx,.xls"
              clearable
              density="comfortable"
              label="Fichier Excel complété"
              prepend-icon=""
              prepend-inner-icon="mdi-paperclip"
              rounded="lg"
              show-size
              variant="outlined"
            />
          </div>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6">
          <v-btn variant="text" @click="closeImportDialog">Annuler</v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            :disabled="!selectedImportFile || loading"
            :loading="loading"
            prepend-icon="mdi-upload"
            rounded="lg"
            @click="submitImportFromDialog"
          >
            Importer le fichier
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ComplianceAspectDialog from '@/modules/clienta/pages/iso/planning/components/ComplianceAspectDialog.vue'
  import ComplianceDialog from '@/modules/clienta/pages/iso/planning/components/ComplianceDialog.vue'
  import ComplianceFilters from '@/modules/clienta/pages/iso/planning/components/ComplianceFilters.vue'
  import ComplianceTable from '@/modules/clienta/pages/iso/planning/components/ComplianceTable.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import {
    type ActionStatus,
    type ComplianceApiNamespace,
    type ComplianceAspect,
    type ComplianceNorm,
    type ComplianceObligation,
    complianceObligationsService,
    type ComplianceStatus,
    type EvaluationFrequency,
    type RegulatoryChangeStatus,
    type ValidityStatus,
  } from '@/services/complianceObligationsService'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const toast = useToast()
  const route = useRoute()

  const isProductRequirementsContext = computed(() => {
    return route.path.includes('/operations/product-service-requirements')
      || route.path.includes('/iso/operations/product-service-requirements')
  })

  const apiNamespace = computed<ComplianceApiNamespace>(() => {
    return isProductRequirementsContext.value ? 'product_requirements' : 'compliance'
  })

  const layoutCurrentPage = computed(() => {
    return isProductRequirementsContext.value ? 'processes' : 'obligations_conformite'
  })

  const pageIcon = computed(() => {
    return isProductRequirementsContext.value ? 'mdi-clipboard-text-search' : 'mdi-gavel'
  })

  const pageTitle = computed(() => {
    return isProductRequirementsContext.value
      ? 'Exigences relatives aux produits et services'
      : 'Obligations de conformité'
  })

  const pageSubtitle = computed(() => {
    return isProductRequirementsContext.value
      ? 'Volet/aspect, exigences applicables et actions de maîtrise'
      : 'Volet/aspect, textes réglementaires et actions associées'
  })

  const hasActiveFilters = computed(() => {
    return Boolean(search.value.trim() || selectedAspectId.value || filterCompliance.value || filterValidity.value)
  })

  const emptyStateMessage = computed(() => {
    if (hasActiveFilters.value) {
      return isProductRequirementsContext.value
        ? 'Aucune exigence ne correspond aux filtres sélectionnés.'
        : 'Aucune obligation ne correspond aux filtres sélectionnés.'
    }

    return isProductRequirementsContext.value
      ? 'Aucune exigence relative aux produits et services trouvée.'
      : 'Aucune obligation de conformité trouvée.'
  })

  const createDialogTitle = computed(() => {
    return isProductRequirementsContext.value ? 'Nouvelle exigence' : 'Nouveau texte réglementaire'
  })

  const editDialogTitle = computed(() => {
    return isProductRequirementsContext.value ? 'Modifier exigence' : 'Modifier texte réglementaire'
  })

  const referenceFieldLabel = computed(() => {
    return isProductRequirementsContext.value
      ? 'Exigence / Référence applicable *'
      : 'Texte / Référence réglementaire *'
  })
  const importDialogTitle = computed(() => {
    return isProductRequirementsContext.value
      ? 'Importer les exigences produits et services'
      : 'Importer les obligations de conformité'
  })
  const importDialogInstructions = computed(() => {
    return isProductRequirementsContext.value
      ? 'Téléchargez le modèle Excel, renseignez vos volets, exigences et actions associées, puis importez le fichier rempli.'
      : 'Téléchargez le modèle Excel, renseignez vos volets, textes réglementaires et actions associées, puis importez le fichier rempli.'
  })

  const loading = ref(false)
  const xlsxInput = ref<HTMLInputElement | null>(null)
  const showImportDialog = ref(false)
  const selectedImportFile = ref<File | null>(null)
  const saving = ref(false)
  const dialog = ref(false)
  const aspectDialog = ref(false)
  const editId = ref<number | null>(null)
  const savingAspect = ref(false)
  const editingAspectId = ref<number | null>(null)

  const items = ref<ComplianceObligation[]>([])
  const aspects = ref<ComplianceAspect[]>([])
  const norms = ref<ComplianceNorm[]>([])
  const users = ref<Array<{ id: number, name: string }>>([])

  const search = ref('')
  const selectedAspectId = ref<number | null>(null)
  const filterCompliance = ref<ComplianceStatus | null>(null)
  const filterValidity = ref<ValidityStatus | null>(null)

  const form = ref({
    aspect_id: null as number | null,
    regulatory_reference: '',
    description: '',
    applicable_requirement: '',
    watch_source: '',
    entry_into_force_date: '',
    regulatory_change_status: null as RegulatoryChangeStatus,
    validity_status: null as ValidityStatus,
    compliance_status: 'not_applicable' as ComplianceStatus,
    actions_corrective_preventive: '',
    deadline: '',
    responsible_id: null as number | null,
    evaluation_frequency: null as EvaluationFrequency,
    comments: '',
    actions: [] as Array<{
      title: string
      responsible_id: number | null
      due_date: string
      status: ActionStatus
      comments: string
    }>,
  })
  const aspectForm = ref({
    name: '',
    description: '',
    norm_ids: [] as number[],
  })

  const complianceOptions = [
    { title: 'Conforme', value: 'compliant' },
    { title: 'Partiellement conforme', value: 'partial' },
    { title: 'Non conforme', value: 'non_compliant' },
    { title: 'Non applicable', value: 'not_applicable' },
  ]
  const validityOptions = [
    { title: 'En vigueur', value: 'in_force' },
    { title: 'Obsolète', value: 'obsolete' },
  ]
  const changeStatusOptions = [
    { title: 'Existant', value: 'existing' },
    { title: 'Nouveau', value: 'new' },
    { title: 'Modifié', value: 'modified' },
  ]
  const frequencyOptions = [
    { title: 'Mensuelle', value: 'monthly' },
    { title: 'Trimestrielle', value: 'quarterly' },
    { title: 'Semestrielle', value: 'semiannual' },
    { title: 'Annuelle', value: 'annual' },
    { title: 'Ponctuelle', value: 'on_demand' },
  ]
  const actionStatusOptions = [
    { title: 'À faire', value: 'pending' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminé', value: 'done' },
    { title: 'Annulée', value: 'cancelled' },
  ]
  const userOptions = computed(() => users.value.map(user => ({ title: user.name, value: user.id })))
  const normOptions = computed(() => norms.value.map(norm => ({ title: `${norm.code} - ${norm.name}`, value: norm.id })))
  const aspectOptions = computed(() => aspects.value.map(aspect => ({ title: aspect.name, value: aspect.id })))

  const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase()
    return items.value.filter(item => {
      const matchesQuery = !q
        || (item.ref || '').toLowerCase().includes(q)
        || (item.aspect?.name || '').toLowerCase().includes(q)
        || (item.regulatory_reference || '').toLowerCase().includes(q)
      const matchesCompliance = !filterCompliance.value || item.compliance_status === filterCompliance.value
      const matchesValidity = !filterValidity.value || item.validity_status === filterValidity.value
      const matchesAspect = !selectedAspectId.value || item.aspect?.id === selectedAspectId.value
      return matchesQuery && matchesCompliance && matchesValidity && matchesAspect
    })
  })

  function resetFilters () {
    search.value = ''
    selectedAspectId.value = null
    filterCompliance.value = null
    filterValidity.value = null
  }

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    const resolved = authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
    return Number.isFinite(Number(resolved)) ? Number(resolved) : null
  }

  function complianceLabel (value: ComplianceStatus) {
    return complianceOptions.find(item => item.value === value)?.title || value
  }

  function complianceColor (value: ComplianceStatus) {
    if (value === 'compliant') return 'success'
    if (value === 'partial') return 'warning'
    if (value === 'non_compliant') return 'error'
    return 'grey'
  }

  function addAction () {
    form.value.actions.unshift({
      title: '',
      responsible_id: null,
      due_date: '',
      status: 'pending',
      comments: '',
    })
  }

  function removeAction (index: number) {
    form.value.actions.splice(index, 1)
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

  async function fetchNorms () {
    const response = await api.get('/norms')
    norms.value = Array.isArray(response.data?.data) ? response.data.data : []
  }

  async function fetchAspects () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      aspects.value = []
      return
    }
    aspects.value = await complianceObligationsService.listAspects(siteId, apiNamespace.value)
  }

  async function fetchTexts () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      items.value = []
      return
    }
    const response = await complianceObligationsService.list({
      site_id: siteId,
      per_page: 300,
      compliance_status: filterCompliance.value || undefined,
      validity_status: filterValidity.value || undefined,
    }, apiNamespace.value)
    items.value = response.data
  }

  async function fetchData () {
    loading.value = true
    try {
      await Promise.all([fetchUsers(), fetchNorms(), fetchAspects(), fetchTexts()])
    } catch (error) {
      console.error('[ComplianceObligations] fetch failed', error)
      toast.error(isProductRequirementsContext.value
        ? 'Impossible de charger les exigences relatives aux produits et services.'
        : 'Impossible de charger les obligations de conformité.')
    } finally {
      loading.value = false
    }
  }

  function resetForm () {
    form.value = {
      aspect_id: null,
      regulatory_reference: '',
      description: '',
      applicable_requirement: '',
      watch_source: '',
      entry_into_force_date: '',
      regulatory_change_status: null,
      validity_status: null,
      compliance_status: 'not_applicable',
      actions_corrective_preventive: '',
      deadline: '',
      responsible_id: null,
      evaluation_frequency: null,
      comments: '',
      actions: [],
    }
  }
  function resetAspectForm () {
    aspectForm.value = {
      name: '',
      description: '',
      norm_ids: [],
    }
  }

  function openDialog (item?: ComplianceObligation) {
    if (!item) {
      editId.value = null
      resetForm()
      if (selectedAspectId.value) {
        form.value.aspect_id = selectedAspectId.value
      }
      dialog.value = true
      return
    }

    editId.value = item.id
    form.value = {
      aspect_id: item.aspect?.id || null,
      regulatory_reference: item.regulatory_reference || '',
      description: item.description || '',
      applicable_requirement: item.applicable_requirement || '',
      watch_source: item.watch_source || '',
      entry_into_force_date: item.entry_into_force_date || '',
      regulatory_change_status: item.regulatory_change_status || null,
      validity_status: item.validity_status || null,
      compliance_status: item.compliance_status || 'not_applicable',
      actions_corrective_preventive: item.actions_corrective_preventive || '',
      deadline: item.deadline || '',
      responsible_id: item.responsible_id || null,
      evaluation_frequency: item.evaluation_frequency || null,
      comments: item.comments || '',
      actions: Array.isArray(item.actions)
        ? item.actions.map(action => ({
          title: action.title || '',
          responsible_id: action.responsible_id || null,
          due_date: action.due_date || '',
          status: action.status || 'pending',
          comments: action.comments || '',
        }))
        : [],
    }
    dialog.value = true
  }

  function closeDialog () {
    dialog.value = false
    editId.value = null
    resetForm()
  }
  function openAspectDialog (aspectId?: number) {
    if (aspectId) {
      const target = aspects.value.find(aspect => aspect.id === aspectId)
      if (target) {
        editingAspectId.value = target.id
        aspectForm.value = {
          name: target.name || '',
          description: target.description || '',
          norm_ids: Array.isArray(target.norms) ? target.norms.map(norm => norm.id) : [],
        }
      } else {
        editingAspectId.value = null
        resetAspectForm()
      }
    } else {
      editingAspectId.value = null
      resetAspectForm()
    }
    aspectDialog.value = true
  }
  function closeAspectDialog () {
    aspectDialog.value = false
    editingAspectId.value = null
    resetAspectForm()
  }

  async function saveAspect () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      toast.error('Aucun site sélectionné.')
      return
    }
    if (!aspectForm.value.name.trim()) {
      toast.error('Le nom du volet/aspect est obligatoire.')
      return
    }

    savingAspect.value = true
    try {
      await (editingAspectId.value
        ? complianceObligationsService.updateAspect(editingAspectId.value, {
          name: aspectForm.value.name.trim(),
          description: aspectForm.value.description || undefined,
          norm_ids: aspectForm.value.norm_ids,
        }, apiNamespace.value)
        : complianceObligationsService.createAspect({
          site_id: siteId,
          name: aspectForm.value.name.trim(),
          description: aspectForm.value.description || undefined,
          norm_ids: aspectForm.value.norm_ids,
        }, apiNamespace.value))
      await fetchAspects()
      const created = aspects.value.find(aspect => aspect.name.trim().toLowerCase() === aspectForm.value.name.trim().toLowerCase())
      if (created?.id) {
        selectedAspectId.value = created.id
        form.value.aspect_id = created.id
      }
      toast.success(editingAspectId.value ? 'Volet/aspect modifié.' : 'Volet/aspect créé.')
      closeAspectDialog()
    } catch (error: any) {
      console.error('[ComplianceObligations] save aspect failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la création du volet/aspect.')
    } finally {
      savingAspect.value = false
    }
  }

  async function saveItem () {
    const siteId = getCurrentSiteId()
    const validationError = validateSaveItemInput(siteId)
    if (validationError) {
      toast.error(validationError)
      return
    }

    saving.value = true
    try {
      const payload = buildCompliancePayload()
      await (editId.value
        ? complianceObligationsService.update(editId.value, payload, apiNamespace.value)
        : complianceObligationsService.create(payload, apiNamespace.value))

      toast.success(isProductRequirementsContext.value
        ? 'Exigence enregistrée avec succès.'
        : 'Obligation enregistrée avec succès.')
      closeDialog()
      await Promise.all([fetchAspects(), fetchTexts()])
    } catch (error: any) {
      console.error('[ComplianceObligations] save failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de l’enregistrement.')
    } finally {
      saving.value = false
    }
  }

  function validateSaveItemInput (siteId: number | null): string | null {
    if (!siteId) {
      return 'Aucun site sélectionné.'
    }
    if (!form.value.regulatory_reference.trim()) {
      return 'Le texte réglementaire est obligatoire.'
    }
    if (!form.value.aspect_id) {
      return 'Sélectionnez un volet/aspect.'
    }

    return null
  }

  function buildCompliancePayload () {
    const actions = form.value.actions
      .filter(action => action.title.trim().length > 0)
      .map(action => ({
        title: action.title.trim(),
        responsible_id: action.responsible_id || undefined,
        due_date: action.due_date || undefined,
        status: action.status || 'pending',
        comments: action.comments || undefined,
      }))

    return {
      aspect_id: form.value.aspect_id || undefined,
      regulatory_reference: form.value.regulatory_reference.trim(),
      description: form.value.description || undefined,
      applicable_requirement: form.value.applicable_requirement || undefined,
      watch_source: form.value.watch_source || undefined,
      entry_into_force_date: form.value.entry_into_force_date || undefined,
      regulatory_change_status: form.value.regulatory_change_status || undefined,
      validity_status: form.value.validity_status || undefined,
      compliance_status: form.value.compliance_status || undefined,
      actions_corrective_preventive: form.value.actions_corrective_preventive || undefined,
      deadline: form.value.deadline || undefined,
      responsible_id: form.value.responsible_id || undefined,
      evaluation_frequency: form.value.evaluation_frequency || undefined,
      comments: form.value.comments || undefined,
      actions,
    }
  }

  async function removeItem (item: ComplianceObligation) {
    if (!confirm(`Supprimer l'obligation ${item.ref || `#${item.id}`} ?`)) return
    loading.value = true
    try {
      await complianceObligationsService.remove(item.id, apiNamespace.value)
      toast.success(isProductRequirementsContext.value ? 'Exigence supprimée.' : 'Obligation supprimée.')
      await fetchTexts()
    } catch (error: any) {
      console.error('[ComplianceObligations] delete failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la suppression.')
    } finally {
      loading.value = false
    }
  }

  async function exportXlsx () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      toast.error('Aucun site sélectionné.')
      return
    }
    loading.value = true
    try {
      const { blob, filename } = await complianceObligationsService.exportXlsx(siteId, apiNamespace.value)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      toast.success('Export Excel généré.')
    } catch (error: any) {
      console.error('[ComplianceObligations] export failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de l’export Excel.')
    } finally {
      loading.value = false
    }
  }

  async function downloadTemplateXlsx () {
    loading.value = true
    try {
      const { blob, filename } = await complianceObligationsService.downloadTemplateXlsx(apiNamespace.value)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      toast.success('Modèle de base téléchargé.')
    } catch (error: any) {
      console.error('[ComplianceObligations] template download failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors du téléchargement du modèle.')
    } finally {
      loading.value = false
    }
  }

  function closeImportDialog () {
    if (loading.value) return
    showImportDialog.value = false
    selectedImportFile.value = null
    if (xlsxInput.value) {
      xlsxInput.value.value = ''
    }
  }

  function submitImportFromDialog () {
    if (!selectedImportFile.value) {
      toast.error('Sélectionnez un fichier Excel à importer.')
      return
    }
    void importXlsxFile(selectedImportFile.value)
  }

  async function importXlsxFile (file: File) {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      toast.error('Aucun site sélectionné.')
      return
    }

    const lowerName = file.name.toLowerCase()
    if (!lowerName.endsWith('.xlsx') && !lowerName.endsWith('.xls')) {
      toast.error('Veuillez sélectionner un fichier Excel (.xlsx ou .xls).')
      return
    }

    loading.value = true
    try {
      const result = await complianceObligationsService.importXlsx(siteId, file, apiNamespace.value)
      toast.success(
        `Import terminé: ${result.imported_rows} ligne(s), ${result.created_aspects} volet(s)/aspect(s) créé(s), ${result.updated_texts} texte(s) mis à jour.`,
      )
      await fetchData()
      closeImportDialog()
    } catch (error: any) {
      console.error('[ComplianceObligations] import failed', error)
      toast.error(error?.response?.data?.message || 'Erreur lors du téléversement du tableau Excel.')
    } finally {
      loading.value = false
      selectedImportFile.value = null
      if (xlsxInput.value) {
        xlsxInput.value.value = ''
      }
    }
  }

  async function handleImportFileSelected (event: Event) {
    const target = event.target as HTMLInputElement | null
    const file = target?.files?.[0]
    if (!file) {
      return
    }

    await importXlsxFile(file)
  }

  watch([filterCompliance, filterValidity], async () => {
    await fetchTexts()
  })
  watch(selectedAspectId, value => {
    if (value && !editId.value && !dialog.value) {
      form.value.aspect_id = value
    }
  })

  onMounted(async () => {
    await fetchData()
  })

  watch(() => authStore.currentSiteId, async () => {
    await fetchData()
  })
</script>
