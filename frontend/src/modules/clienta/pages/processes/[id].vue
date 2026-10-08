<template>
  <ClientALayout current-page="processes">
    <v-container class="pa-6" fluid>
      <!-- Loading State -->
      <v-progress-linear v-if="loading" indeterminate />

      <template v-else-if="process">
        <ProcessHeader
          :current-version="currentVersion"
          :disable-verify="submittingForVerification"
          :process="process"
          @back="router.push('/company/context/management-system')"
          @edit="router.push(`/company/processes/${process.id}/edit`)"
          @export="format => exportProcess(format || 'docx', true)"
          @verify-document="openVerifyDialog"
        />

        <!-- Tabs -->
        <v-card>
          <v-tabs v-model="activeTab" bg-color="grey-lighten-4">
            <v-tab value="general">
              <v-icon start>mdi-information</v-icon>
              Informations Générales
            </v-tab>
            <v-tab value="sequences">
              <v-icon start>mdi-timeline</v-icon>
              Séquences
              <v-badge
                v-if="sequences.length > 0"
                color="primary"
                :content="sequences.length"
                inline
              />
            </v-tab>
            <v-tab value="resources">
              <v-icon start>mdi-package-variant</v-icon>
              Ressources
            </v-tab>
            <v-tab value="objectives">
              <v-icon start>mdi-target</v-icon>
              Objectifs
              <v-badge
                v-if="objectives.length > 0"
                color="info"
                :content="objectives.length"
                inline
              />
            </v-tab>
            <v-tab value="risks">
              <v-icon start>mdi-alert</v-icon>
              Risques & Opportunités
            </v-tab>
            <v-tab value="versions">
              <v-icon start>mdi-history</v-icon>
              Versions
              <v-badge
                v-if="versions.length > 0"
                color="success"
                :content="versions.length"
                inline
              />
            </v-tab>
            <v-tab value="interactions">
              <v-icon start>mdi-vector-polyline</v-icon>
              Cartographie & Interactions
            </v-tab>
          </v-tabs>

          <v-window v-model="activeTab">
            <!-- General Info Tab -->
            <v-window-item value="general">
              <ProcessGeneralTab
                :current-user="authStore.user"
                :get-type-color="getTypeColor"
                :get-type-label="getTypeLabel"
                :process="process"
                :saving-pilot="savingPilot"
                :selected-pilot-id="selectedPilotId"
                :users="users"
                :users-loading="usersLoading"
                @edit="handleEdit"
                @reject="handleReject"
                @update:selected-pilot-id="(value) => (selectedPilotId = value)"
                @validate="handleValidate"
                @verify="handleVerify"
              />
            </v-window-item>

            <!-- Sequences Tab -->
            <v-window-item value="sequences">
              <v-card-text class="pa-6">
                <ProcessSequenceEditor
                  :process-id="process.id"
                  :sequences="sequences"
                  :users="users"
                  @delete="handleDeleteSequence"
                  @reorder="handleReorderSequences"
                  @save="handleSaveSequence"
                />
              </v-card-text>
            </v-window-item>

            <!-- Resources Tab -->
            <v-window-item value="resources">
              <v-card-text class="pa-6">
                <ProcessResourcesManager
                  :process-id="process.id"
                  :resources="resources"
                  @add="handleAddResource"
                  @delete="handleDeleteResource"
                  @update="handleUpdateResource"
                />
              </v-card-text>
            </v-window-item>

            <!-- Objectives Tab -->
            <v-window-item value="objectives">
              <v-card-text class="pa-6">
                <ProcessObjectivesManager
                  :indicators="indicators"
                  :objectives="objectives"
                  :process-id="process.id"
                  @create="handleCreateObjective"
                  @delete="handleDeleteObjective"
                  @update="handleUpdateObjective"
                />
              </v-card-text>
            </v-window-item>

            <!-- Risks Tab -->
            <v-window-item value="risks">
              <ProcessRisksTab
                :get-criticality-color="getCriticalityColor"
                :process="process"
              />
            </v-window-item>

            <!-- Versions Tab -->
            <v-window-item value="versions">
              <v-card-text class="pa-6">
                <ProcessVersionHistory
                  :can-approve="canApproveVersions"
                  :can-create-version="canCreateVersions"
                  :can-manage-versions="canManageVersions"
                  :can-verify="canVerifyVersions"
                  :process-id="process.id"
                  :versions="versions"
                  @approve="handleApproveVersion"
                  @create="handleCreateVersion"
                  @refresh="fetchVersions"
                  @verify="handleVerifyVersion"
                />
              </v-card-text>
            </v-window-item>

            <!-- Cartography & Interactions Tab -->
            <v-window-item value="interactions">
              <v-card-text class="pa-6">
                <ProcessCartographyViewer
                  :hide-header="true"
                  :process-id="process.id"
                  :site-id="process.site_id"
                />
              </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>
      </template>

      <!-- Error State -->
      <v-alert v-else type="error"> Processus non trouvé </v-alert>

      <v-dialog v-model="verifyDialog" max-width="560">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 mb-2">Code du document</div>
            <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
            <v-btn color="warning" :loading="submittingForVerification" @click="submitForVerification">
              Confirmer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Prévisualisation avant export (RT-01) -->
      <PreviewExportModal
        v-model="previewExportDialog"
        :blob="previewBlob"
        :file-type="previewFileType"
        :filename="previewFilename"
        :loading="loading"
        :metadata="{
          version: process?.version || currentVersion || '1.0',
          summaryItems: [
            { label: 'Code', value: process?.code || '-' },
            { label: 'Titre', value: process?.title || process?.name || '-' },
            { label: 'Catégorie', value: process?.category || '-' },
            { label: 'Séquences', value: sequences?.length || 0 },
          ],
        }"
        :title="previewModalTitle"
        @download="toast.success('Téléchargement de la fiche processus démarré avec succès.')"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type {
    ProcessObjective,
    ProcessSequence,
    ProcessVersion,
  } from '@/services/processService'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcessObjectivesManager from '@/modules/clienta/components/processes/ProcessObjectivesManager.vue'
  import ProcessResourcesManager from '@/modules/clienta/components/processes/ProcessResourcesManager.vue'
  import ProcessSequenceEditor from '@/modules/clienta/components/processes/ProcessSequenceEditor.vue'
  import ProcessVersionHistory from '@/modules/clienta/components/processes/ProcessVersionHistory.vue'
  import ProcessGeneralTab from '@/modules/clienta/pages/processes/components/ProcessGeneralTab.vue'
  import ProcessHeader from '@/modules/clienta/pages/processes/components/ProcessHeader.vue'
  import ProcessRisksTab from '@/modules/clienta/pages/processes/components/ProcessRisksTab.vue'
  import ProcessCartographyViewer from '@/modules/clienta/components/processes/ProcessCartographyViewer.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import userService, { type User } from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'
  import { useProcessStore } from '@/stores/processStore'

  const route = useRoute()
  const router = useRouter()
  const processStore = useProcessStore()
  const authStore = useAuthStore()
  const toast = useToast()

  const loading = ref(false)
  const activeTab = ref('general')
  const sequences = ref<ProcessSequence[]>([])
  const versions = ref<ProcessVersion[]>([])
  const verifyDialog = ref(false)
  const submittingForVerification = ref(false)
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')
  const objectives = ref<ProcessObjective[]>([])
  const users = ref<Array<{ id: number, name: string }>>([])
  const usersLoading = ref(false)
  const savingPilot = ref(false)
  const selectedPilotId = ref<number | null>(null)
  const pilotInitialized = ref(false)
  const updatingPilot = ref(false)
  const indicators = ref<any[]>([])

  const resources = ref({
    human: [],
    technological: [],
    documentary: [],
    material: [],
  })

  const typeOptions = [
    { title: 'Management', value: 'management' },
    { title: 'Support', value: 'support' },
    { title: 'Réalisation', value: 'realization' },
  ]

  const process = computed(() => processStore.currentProcess)
  const currentVersion = computed(
    () =>
      versions.value.find(v => (v as any).is_current)
      || versions.value[0]
      || null,
  )

  // Permissions
  const canManageVersions = computed(() => {
    // TODO: Check actual permissions
    return true
  })

  const canVerifyVersions = computed(() => {
    return true
  })

  const canApproveVersions = computed(() => {
    return true
  })

  const canCreateVersions = computed(() => {
    // TODO: Check actual permissions
    return true
  })

  onMounted(async () => {
    await loadProcess()
  })

  async function loadProcess () {
    loading.value = true
    try {
      const processId = Number((route.params as any).id)
      await processStore.fetchProcess(processId)

      if (process.value) {
        selectedPilotId.value
          = (process.value as any).pilot_id ?? process.value.pilot?.id ?? null
        pilotInitialized.value = true
        // Load related data
        sequences.value = ((process.value as any).sequences
          || []) as ProcessSequence[]
        objectives.value = ((process.value as any).objectives
          || []) as ProcessObjective[]

        // Load versions
        await fetchVersions()

        if (process.value.site_id) {
          await loadUsersBySite(process.value.site_id)
        }
      // TODO: Load indicators, resources from respective stores
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchVersions () {
    if (process.value) {
      const versionData = await processStore.fetchVersions(process.value.id)
      versions.value = versionData
    }
  }

  async function loadUsersBySite (siteId: number) {
    usersLoading.value = true
    try {
      const siteUsers = await userService.getBySite(siteId)
      users.value = siteUsers.map((user: User) => ({
        id: user.id,
        name: user.name || user.username || user.email,
      }))
    } catch (error) {
      console.error('Error loading users for site:', error)
      users.value = []
    } finally {
      usersLoading.value = false
    }
  }

  watch(selectedPilotId, async newPilotId => {
    if (!pilotInitialized.value || !process.value) return
    if ((process.value as any).pilot_id === newPilotId) return
    if (updatingPilot.value) return
    updatingPilot.value = true
    savingPilot.value = true
    try {
      await processStore.updateProcess(process.value.id, {
        pilot_id: newPilotId,
      })
      toast.success('Pilote du processus mis à jour')
    } catch (error) {
      console.error('Error updating pilot:', error)
      toast.error('Erreur lors de la mise à jour du pilote')
      selectedPilotId.value
        = (process.value as any).pilot_id ?? process.value.pilot?.id ?? null
    } finally {
      savingPilot.value = false
      updatingPilot.value = false
    }
  })

  // Sequences Handlers
  async function handleSaveSequence (sequence: ProcessSequence) {
    if (!process.value) return

    try {
      await (sequence.id
        ? processStore.updateSequence(process.value.id, sequence.id, sequence)
        : processStore.addSequence(process.value.id, sequence))
      await loadProcess() // Refresh
    } catch (error) {
      console.error('Error saving sequence:', error)
    }
  }

  async function handleDeleteSequence (sequence: ProcessSequence) {
    if (!process.value || !sequence.id) return

    try {
      await processStore.deleteSequence(process.value.id, sequence.id)
      await loadProcess()
    } catch (error) {
      console.error('Error deleting sequence:', error)
    }
  }

  async function handleReorderSequences (reorderedSequences: ProcessSequence[]) {
    // Save all sequences with new order
    for (const seq of reorderedSequences) {
      if (seq.id) {
        await handleSaveSequence(seq)
      }
    }
  }

  // Objectives Handlers
  async function handleCreateObjective (objective: Partial<ProcessObjective>) {
    if (!process.value) return

    try {
      await processStore.addObjective(process.value.id, objective)
      await loadProcess()
    } catch (error) {
      console.error('Error creating objective:', error)
    }
  }

  async function handleUpdateObjective (
    objective: ProcessObjective | Partial<ProcessObjective>,
  ) {
    if (!process.value || !objective.id) return

    try {
      await processStore.updateObjective(
        process.value.id,
        objective.id,
        objective,
      )
      await loadProcess()
    } catch (error) {
      console.error('Error updating objective:', error)
    }
  }

  async function handleDeleteObjective (
    objective: ProcessObjective | Partial<ProcessObjective>,
  ) {
    if (!process.value || !objective.id) return

    try {
      await processStore.deleteObjective(process.value.id, objective.id)
      await loadProcess()
    } catch (error) {
      console.error('Error deleting objective:', error)
    }
  }

  // Versions Handlers
  async function handleCreateVersion (description: string) {
    if (!process.value) return

    try {
      await processStore.createVersion(process.value.id, {
        changes_description: description,
      })
      await fetchVersions()
    } catch (error) {
      console.error('Error creating version:', error)
    }
  }

  async function handleVerifyVersion (version: ProcessVersion) {
    if (!process.value || !version.id) return

    try {
      await processStore.verifyVersion(process.value.id, version.id)
      await fetchVersions()
    } catch (error) {
      console.error('Error verifying version:', error)
    }
  }

  async function handleApproveVersion (version: ProcessVersion) {
    if (!process.value || !version.id) return

    try {
      await processStore.approveVersion(process.value.id, version.id)
      await fetchVersions()
    } catch (error) {
      console.error('Error approving version:', error)
    }
  }

  // Resources Handlers
  function handleAddResource (type: string, resource: any) {
    // TODO: Implement resource management
    console.log('Add resource:', type, resource)
  }

  function handleUpdateResource (type: string, resource: any) {
    // TODO: Implement resource management
    console.log('Update resource:', type, resource)
  }

  function handleDeleteResource (type: string, resource: any) {
    // TODO: Implement resource management
    console.log('Delete resource:', type, resource)
  }

  const previewExportDialog = ref(false)
  const previewBlob = ref<Blob | null>(null)
  const previewFilename = ref('Fiche_Processus.docx')
  const previewFileType = ref<'docx' | 'pdf'>('docx')
  const previewModalTitle = ref('Prévisualisation — Fiche Processus')

  async function exportProcess (format: 'docx' | 'pdf' = 'docx', preview = true): Promise<boolean> {
    if (!process.value) return false
    try {
      loading.value = true
      previewFileType.value = format
      const isPdf = format === 'pdf'
      const extension = isPdf ? 'pdf' : 'docx'
      const endpoint = isPdf
        ? `/processes/${process.value.id}/export-pdf?preview=1`
        : `/processes/${process.value.id}/export-docx`

      const response = await api.get(
        endpoint,
        {
          responseType: 'blob',
        },
      )
      const generatedDocumentId = Number(response.headers?.['x-generated-document-id'] || 0)
      if (generatedDocumentId > 0) {
        lastExportedDocumentId.value = generatedDocumentId
        try {
          const docResponse = await api.get(`/documents/${generatedDocumentId}`)
          exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
        } catch {
          // ignore doc code lookup error
        }
      }

      const filename = `Fiche_Processus_${process.value.code || process.value.id}.${extension}`
      const mimeType = isPdf
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      const blob = new Blob([response.data], { type: mimeType })

      previewBlob.value = blob
      previewFilename.value = filename
      previewModalTitle.value = `Prévisualisation — Fiche Processus (${format.toUpperCase()})`

      if (preview) {
        previewExportDialog.value = true
      } else {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.append(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        toast.success(`Fiche processus (${format.toUpperCase()}) exportée avec succès`)
      }
      return generatedDocumentId > 0
    } catch (error: any) {
      console.error('Error exporting process:', error)
      toast.error('Erreur lors de l\'export du processus')
      return false
    } finally {
      loading.value = false
    }
  }

  function openVerifyDialog () {
    verifyDialog.value = true
  }

  async function submitForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      const generated = await exportProcess(false)
      if (!generated || !lastExportedDocumentId.value || !exportedDocumentCode.value) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      verifyDialog.value = false
      toast.success('Document envoyé pour vérification.')
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
    } catch {
      toast.error('Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  function getTypeColor (type: string): string {
    switch (type) {
      case 'management': {
        return 'purple'
      }
      case 'support': {
        return 'indigo'
      }
      case 'realization': {
        return 'teal'
      }
      default: {
        return 'grey'
      }
    }
  }

  function getTypeLabel (type: string): string {
    const option = typeOptions.find(opt => opt.value === type)
    return option?.title || type
  }

  function getCriticalityColor (level: string): string {
    switch (level) {
      case 'faible': {
        return 'success'
      }
      case 'moyen': {
        return 'warning'
      }
      case 'eleve': {
        return 'orange'
      }
      case 'critique': {
        return 'error'
      }
      default: {
        return 'grey'
      }
    }
  }

  // Workflow Handlers
  async function handleVerify (comment?: string) {
    if (!process.value) return

    try {
      await processStore.verifyProcess(process.value.id, comment)
      await loadProcess()
    } catch (error) {
      console.error('Error verifying process:', error)
    }
  }

  async function handleValidate (comment?: string) {
    if (!process.value) return

    try {
      await processStore.validateProcess(process.value.id, comment)
      await loadProcess()
    } catch (error) {
      console.error('Error validating process:', error)
    }
  }

  async function handleReject (reason: string) {
    if (!process.value) return

    try {
      await processStore.rejectProcess(process.value.id, reason)
      await loadProcess()
    } catch (error) {
      console.error('Error rejecting process:', error)
    }
  }

  function handleEdit () {
    if (process.value) {
      router.push(`/company/processes/${process.value.id}/edit`)
    }
  }
</script>
