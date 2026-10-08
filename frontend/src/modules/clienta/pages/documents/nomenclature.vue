<template>
  <ClientALayout>
    <div class="nomenclature-page">
      <v-container fluid>
        <v-row>
          <v-col cols="12">
            <v-card elevation="2">
              <v-card-title class="d-flex align-center justify-space-between ga-2">
                <div class="d-flex align-center ga-2">
                  <v-icon color="primary">mdi-cog</v-icon>
                  <span>Configuration de la nomenclature</span>
                </div>
                <v-btn color="primary" prepend-icon="mdi-wizard-hat" variant="flat" @click="activeTab = 'assistant'">
                  Assistant de codification
                </v-btn>
              </v-card-title>
              <v-card-text>
                <!-- Onglets -->
                <v-tabs v-model="activeTab" color="primary">
                  <v-tab value="assistant">Assistant de codification</v-tab>
                  <v-tab value="types">Types documentaires</v-tab>
                  <v-tab disabled value="nomenclatures">Nomenclatures (remplacé)</v-tab>
                  <v-tab value="builder">Configuration avancée</v-tab>
                </v-tabs>

                <v-window v-model="activeTab" class="mt-4">
                  <!-- Onglet 0: Assistant de codification -->
                  <v-window-item value="assistant">
                    <NomenclatureStepper
                      :document-type-catalogs="documentTypeCatalogs"
                      :nomenclatures="nomenclatures"
                      :processes="processes"
                      @cancel="handleStepperCancelToTypes"
                      @save="handleStepperSave"
                    />
                  </v-window-item>

                  <!-- Onglet 1: Types documentaires -->
                  <v-window-item value="types">
                    <v-card elevation="0" rounded="lg">
                      <v-card-title class="d-flex align-center justify-space-between ga-2">
                        <div class="d-flex align-center ga-2">
                          <v-icon color="primary">mdi-tag-multiple-outline</v-icon>
                          <span>Types documentaires</span>
                        </div>
                        <v-btn color="primary" prepend-icon="mdi-plus" variant="tonal" @click="startCreateDocType">
                          Ajouter un type
                        </v-btn>
                      </v-card-title>
                      <v-card-text>
                        <v-alert
                          v-if="docTypeError"
                          class="mb-3"
                          density="comfortable"
                          type="error"
                          variant="tonal"
                        >
                          {{ docTypeError }}
                        </v-alert>
                        <v-table density="comfortable">
                          <thead>
                            <tr>
                              <th>Nom</th>
                              <th>Abréviation</th>
                              <th>Ordre</th>
                              <th>Statut</th>
                              <th class="text-right">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in documentTypeCatalogs" :key="item.id">
                              <td>{{ item.name }}</td>
                              <td><code>{{ item.abbreviation }}</code></td>
                              <td>{{ item.display_order }}</td>
                              <td>
                                <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" variant="tonal">
                                  {{ item.is_active ? 'Actif' : 'Inactif' }}
                                </v-chip>
                              </td>
                              <td class="text-right">
                                <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="startEditDocType(item)" />
                                <v-btn
                                  color="error"
                                  icon="mdi-delete-outline"
                                  size="small"
                                  variant="text"
                                  @click="removeDocType(item.id)"
                                />
                              </td>
                            </tr>
                            <tr v-if="documentTypeCatalogs.length === 0">
                              <td class="text-medium-emphasis py-4" colspan="5">Aucun type documentaire défini.</td>
                            </tr>
                          </tbody>
                        </v-table>
                      </v-card-text>
                    </v-card>
                  </v-window-item>

                  <!-- Onglet 2: Nomenclatures -->
                  <v-window-item value="nomenclatures">
                    <v-alert border="start" class="mb-3" color="info" variant="tonal">
                      Ce système a été remplacé par <strong>Configuration avancée</strong>.
                    </v-alert>
                    <v-btn color="primary" prepend-icon="mdi-arrow-right" variant="tonal" @click="activeTab = 'builder'">
                      Aller vers Configuration avancée
                    </v-btn>
                  </v-window-item>

                  <!-- Onglet 3: Configuration avancée (Builder) -->
                  <v-window-item value="builder">
                    <NomenclatureTemplateBuilder
                      :document-types="documentTypeCatalogs"
                      :initial-template="selectedTemplate"
                      @cancel="selectedTemplate = null"
                      @save="saveTemplate"
                    />
                  </v-window-item>
                </v-window>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </div>
  </ClientALayout>

  <v-dialog v-model="docTypeDialog" max-width="620">
    <v-card>
      <v-card-title>{{ editingDocTypeId ? 'Modifier le type' : 'Ajouter un type' }}</v-card-title>
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="8">
            <v-text-field v-model="docTypeForm.name" label="Nom" required variant="outlined" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="docTypeForm.abbreviation"
              label="Abréviation"
              maxlength="20"
              required
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="8">
            <v-text-field v-model="docTypeForm.description" label="Description" variant="outlined" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="docTypeForm.display_order"
              label="Ordre"
              min="0"
              type="number"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-switch v-model="docTypeForm.is_active" color="success" inset label="Actif" />
            <div class="text-caption text-medium-emphasis mt-1">
              L’activation nécessite un template publié actif pour ce type.
            </div>
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="px-6 pb-4">
        <v-spacer />
        <v-btn variant="text" @click="docTypeDialog = false">Annuler</v-btn>
        <v-btn color="primary" :loading="docTypesLoading" @click="saveDocType">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Nomenclature Stepper Dialog -->
  <v-dialog v-model="stepperDialog" fullscreen scrollable>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between sticky-header">
        <div class="d-flex align-center gap-2">
          <v-icon color="primary" size="28">mdi-wizard-hat</v-icon>
          <span class="text-h6">Assistant de Configuration - Nomenclatures</span>
        </div>
        <v-btn icon="mdi-close" @click="stepperDialog = false" />
      </v-card-title>
      <v-card-text class="pa-6">
        <NomenclatureStepper
          :document-type-catalogs="documentTypeCatalogs"
          :nomenclatures="nomenclatures"
          :processes="processes"
          @cancel="closeStepperDialog"
          @save="handleStepperSave"
        />
      </v-card-text>
    </v-card>
  </v-dialog>

  <v-dialog v-model="confirmDialog" max-width="460">
    <v-card rounded="lg">
      <v-card-title class="text-subtitle-1 font-weight-bold">{{ confirmDialogTitle }}</v-card-title>
      <v-card-text class="text-body-2 text-medium-emphasis">
        {{ confirmDialogMessage }}
      </v-card-text>
      <v-card-actions class="px-6 pb-4">
        <v-spacer />
        <v-btn :disabled="confirmDialogLoading" variant="text" @click="closeConfirmDialog">Annuler</v-btn>
        <v-btn :color="confirmDialogColor" :loading="confirmDialogLoading" variant="flat" @click="runConfirmDialogAction">
          {{ confirmDialogConfirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { DocumentTypeCatalog, Nomenclature } from '@/modules/clienta/types/document.types'
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { processesService } from '@/api/services/processes.service'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import NomenclatureManager from '@/modules/clienta/components/documents/NomenclatureManager.vue'
  import NomenclatureTemplateBuilder from '@/modules/clienta/components/documents/NomenclatureTemplateBuilder.vue'
  import NomenclatureStepper from '@/modules/clienta/components/nomenclature/NomenclatureStepper.vue'
  import { useDocumentTypeCatalogs } from '@/modules/clienta/composables/useDocumentTypeCatalogs'
  import { useNomenclatures } from '@/modules/clienta/composables/useNomenclatures'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  type RawProcess = {
    id: number
    title?: string
    nom?: string
    code?: string
  }

  function handleStepperCancelToTypes () {
    activeTab.value = 'types'
  }

  function closeStepperDialog () {
    stepperDialog.value = false
  }

  const authStore = useAuthStore()
  const toast = useToast()
  const loading = ref(false)
  const processes = ref<Array<{ id: number, title: string, code: string }>>([])
  const activeTab = ref('assistant')
  const selectedTemplate = ref<any>(null)
  const stepperDialog = ref(false)

  const selectedSiteId = computed(
    () => authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id ?? null,
  )

  const {
    nomenclatures,
    createNomenclature,
    updateNomenclature,
    deleteNomenclature,
    fetchNomenclatures,
  } = useNomenclatures()

  const {
    items: documentTypeCatalogs,
    loading: docTypesLoading,
    error: docTypeError,
    fetchItems: fetchDocumentTypes,
    createItem: createDocumentType,
    updateItem: updateDocumentType,
    deleteItem: deleteDocumentType,
  } = useDocumentTypeCatalogs()

  const docTypeDialog = ref(false)
  const confirmDialog = ref(false)
  const confirmDialogLoading = ref(false)
  const confirmDialogTitle = ref('Confirmation')
  const confirmDialogMessage = ref('')
  const confirmDialogConfirmText = ref('Confirmer')
  const confirmDialogColor = ref<'error' | 'primary'>('error')
  const confirmDialogAction = ref<null | (() => Promise<void>)>(null)
  const editingDocTypeId = ref<number | null>(null)
  const docTypeForm = ref({
    name: '',
    abbreviation: '',
    description: '',
    is_active: false,
    display_order: 0,
  })

  async function loadProcesses () {
    loading.value = true
    try {
      const params = selectedSiteId.value ? { site_id: selectedSiteId.value } : undefined
      const response = await processesService.getProcesses(params || {})
      let processList: RawProcess[] = []

      if (Array.isArray(response?.data)) processList = response.data

      if (processList.length === 0) {
        const fallbackResponse = await processesService.getProcesses()
        if (Array.isArray(fallbackResponse?.data)) processList = fallbackResponse.data
      }

      processes.value = processList.map((process: RawProcess) => ({
        id: process.id,
        title: process.title || process.nom || `Processus #${process.id}`,
        code: process.code || 'XXX',
      }))
    } finally {
      loading.value = false
    }
  }

  async function reloadData () {
    await Promise.all([
      fetchNomenclatures(selectedSiteId.value ? { site_id: selectedSiteId.value } : undefined),
      fetchDocumentTypes(selectedSiteId.value ? { site_id: selectedSiteId.value } : undefined),
      loadProcesses(),
    ])
  }

  async function handleSave (payload: Partial<Nomenclature> & { id?: number }) {
    const scopedPayload = selectedSiteId.value ? { ...payload, site_id: selectedSiteId.value } : payload
    await (payload.id ? updateNomenclature(payload.id, scopedPayload) : createNomenclature(scopedPayload))
    await reloadData()
  }

  async function handleStepperSave (stepperData: any) {
    try {
      const mappingEntries = stepperData.processMapping instanceof Map
        ? Array.from(stepperData.processMapping.entries())
        : Array.isArray(stepperData.processMapping)
          ? stepperData.processMapping
          : []

      if (mappingEntries.length === 0 && stepperData.selectedProcess?.id) {
        mappingEntries.push([stepperData.selectedProcess.id, stepperData.selectedProcess])
      }

      const selectedDocTypeId = typeof stepperData.selectedDocType === 'object'
        ? stepperData.selectedDocType?.id
        : stepperData.selectedDocType

      const selectedDocType = documentTypeCatalogs.value.find(item => Number(item.id) === Number(selectedDocTypeId))

      const nomenclaturesToSave = mappingEntries.map(([processId, mapping]: any) => ({
        document_type_catalog_id: selectedDocTypeId || null,
        process_id: processId,
        processus: mapping?.code || mapping?.title || `PROC_${processId}`,
        name: [
          'Codification',
          selectedDocType?.name || 'document',
          mapping?.title || mapping?.code || `processus ${processId}`,
        ].join(' - '),
        nom: stepperData.namingPattern.format,
        format: stepperData.namingPattern.format,
        separator: detectStepperSeparator(stepperData.namingPattern.parts, stepperData.namingPattern.format),
        format_structure: stepperData.namingPattern.parts,
        preview_example: stepperData.namingPattern.preview,
        status: 'published',
        actif: true,
        is_active: true,
        site_id: selectedSiteId.value,
      }))

      for (const nomenclature of nomenclaturesToSave) {
        await createNomenclature(nomenclature)
      }

      toast.success('Codification documentaire publiée avec succès.')
      stepperDialog.value = false
      activeTab.value = 'builder'
      await reloadData()
    } catch (error: any) {
      console.error(error)
      toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde.')
    }
  }

  function detectStepperSeparator (parts: any[] = [], format = '') {
    const explicit = parts.find(part => part?.type === 'separator' && part?.value)?.value
    if (explicit) return explicit
    if (format.includes('/')) return '/'
    if (format.includes('_')) return '_'
    return '-'
  }

  async function handleDelete (id: number) {
    await deleteNomenclature(id)
    await reloadData()
  }

  function startCreateDocType () {
    editingDocTypeId.value = null
    docTypeForm.value = {
      name: '',
      abbreviation: '',
      description: '',
      is_active: false,
      display_order: 0,
    }
    docTypeDialog.value = true
  }

  function startStepper () {
    stepperDialog.value = true
  }

  function startEditDocType (item: DocumentTypeCatalog) {
    editingDocTypeId.value = item.id
    docTypeForm.value = {
      name: item.name,
      abbreviation: item.abbreviation,
      description: item.description || '',
      is_active: item.is_active,
      display_order: item.display_order || 0,
    }
    docTypeDialog.value = true
  }

  async function saveDocType () {
    const payload = {
      ...docTypeForm.value,
      abbreviation: String(docTypeForm.value.abbreviation || '').toUpperCase().trim(),
      site_id: selectedSiteId.value || undefined,
    }

    if (!payload.name || !payload.abbreviation) return
    try {
      await (editingDocTypeId.value ? updateDocumentType(editingDocTypeId.value, payload) : createDocumentType(payload))
      docTypeDialog.value = false
      await fetchDocumentTypes(selectedSiteId.value ? { site_id: selectedSiteId.value } : undefined)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible d\'enregistrer le type.')
    }
  }

  async function removeDocType (id: number) {
    openConfirmDialog({
      title: 'Supprimer le type documentaire',
      message: 'Le type documentaire sera supprimé. Voulez-vous continuer ?',
      confirmText: 'Supprimer',
      color: 'error',
      action: async () => {
        await deleteDocumentType(id)
        await fetchDocumentTypes(selectedSiteId.value ? { site_id: selectedSiteId.value } : undefined)
      },
    })
  }

  async function saveTemplate (template: any) {
    try {
      const payload = {
        ...template,
        site_id: selectedSiteId.value,
      }

      const response = await api.post('/nomenclature-templates', payload)
      toast.success('Template enregistré avec succès.')
      selectedTemplate.value = null
      await reloadData()
    } catch (error: any) {
      console.error(error)
      toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement du template.')
    }
  }

  function openConfirmDialog ({
    title,
    message,
    confirmText,
    color,
    action,
  }: {
    title: string
    message: string
    confirmText?: string
    color?: 'error' | 'primary'
    action: () => Promise<void>
  }) {
    confirmDialogTitle.value = title
    confirmDialogMessage.value = message
    confirmDialogConfirmText.value = confirmText || 'Confirmer'
    confirmDialogColor.value = color || 'primary'
    confirmDialogAction.value = action
    confirmDialog.value = true
  }

  function closeConfirmDialog () {
    confirmDialog.value = false
    confirmDialogLoading.value = false
    confirmDialogAction.value = null
  }

  async function runConfirmDialogAction () {
    if (!confirmDialogAction.value) return
    confirmDialogLoading.value = true
    try {
      await confirmDialogAction.value()
      closeConfirmDialog()
    } catch (error) {
      console.error(error)
      confirmDialogLoading.value = false
    }
  }

  onMounted(async () => {
    await reloadData()
  })
</script>

<style scoped>
.nomenclature-page {
  min-height: 100vh;
  background: rgb(var(--v-theme-background));
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
