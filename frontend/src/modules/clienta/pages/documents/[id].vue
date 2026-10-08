<template>
  <v-container class="pa-6" fluid>
    <div class="mb-6">
      <v-btn
        prepend-icon="mdi-arrow-left"
        variant="text"
        @click="$router.push('/company/documents')"
      >
        Retour à la liste
      </v-btn>
    </div>

    <v-progress-linear v-if="loading" color="primary" indeterminate />

    <div v-else-if="currentDocument">
      <!-- Header -->
      <v-card class="mb-6" elevation="2">
        <v-card-title class="pa-6">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-overline text-grey">{{ currentDocument.document_number }}</div>
              <h1 class="text-h4">{{ currentDocument.title }}</h1>
              <div class="mt-2">
                <v-chip
                  v-if="currentDocument.category"
                  class="mr-2"
                  :color="getCategoryColor(currentDocument.category.level)"
                  label
                  size="small"
                >
                  {{ currentDocument.category.name }}
                </v-chip>
                <v-chip
                  :color="getStatusColor(currentDocument)"
                  size="small"
                >
                  {{ getStatusLabel(currentDocument) }}
                </v-chip>
              </div>
            </div>

            <div class="d-flex gap-2">
              <v-btn
                v-if="currentDocument.current_version && isApprovedForExport"
                color="primary"
                prepend-icon="mdi-download"
                @click="downloadDoc"
              >
                Télécharger
              </v-btn>

              <v-menu>
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-dots-vertical"
                    variant="text"
                  />
                </template>
                <v-list>
                  <v-list-item
                    v-if="canSubmitDocumentAgain"
                    prepend-icon="mdi-send"
                    @click="submitForApproval"
                  >
                    {{ currentDocument.workflow_status === 'rejected' ? 'Soumettre à nouveau' : 'Soumettre à la vérification' }}
                  </v-list-item>
                  <v-list-item
                    v-if="currentDocument.workflow_status === 'pending_verification' || currentDocument.workflow_status === 'pending_approval'"
                    prepend-icon="mdi-timeline-check"
                    @click="tab = 'workflow'"
                  >
                    Ouvrir la validation
                  </v-list-item>
                  <v-list-item
                    prepend-icon="mdi-upload"
                    @click="showUploadVersionDialog = true"
                  >
                    Nouvelle version
                  </v-list-item>
                  <v-list-item
                    v-if="isApprovedForExport && !currentDocument.is_archived"
                    prepend-icon="mdi-archive"
                    @click="archiveDoc"
                  >
                    Archiver
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </div>
        </v-card-title>
      </v-card>

      <v-alert
        v-if="showSubmitterDecisionAlert"
        class="mb-6"
        type="warning"
        variant="tonal"
      >
        <div class="d-flex flex-column ga-3">
          <div>
            <strong>Document rejeté :</strong>
            confirmez si le code doit être libéré ou gardé pour correction.
          </div>
          <div v-if="currentDocument.rejection_reason">
            <strong>Motif du rejet :</strong> {{ currentDocument.rejection_reason }}
          </div>
          <div class="d-flex ga-2">
            <v-btn
              color="error"
              :loading="confirmingRejectionDecision"
              size="small"
              variant="outlined"
              @click="confirmRejectionDecision(true)"
            >
              Libérer le code
            </v-btn>
            <v-btn
              color="primary"
              :loading="confirmingRejectionDecision"
              size="small"
              variant="flat"
              @click="confirmRejectionDecision(false)"
            >
              Garder le code
            </v-btn>
          </div>
        </div>
      </v-alert>

      <!-- Tabs -->
      <v-card elevation="2">
        <v-tabs v-model="tab" bg-color="primary">
          <v-tab value="details">
            <v-icon start>mdi-information</v-icon>
            Détails
          </v-tab>
          <v-tab value="versions">
            <v-icon start>mdi-history</v-icon>
            Versions ({{ currentDocument.versions?.length || 0 }})
          </v-tab>
          <v-tab value="workflow">
            <v-icon start>mdi-timeline-check</v-icon>
            Workflow ({{ workflowEvents.length }})
          </v-tab>
          <v-tab v-if="currentDocument.current_version" value="viewer">
            <v-icon start>mdi-file-eye</v-icon>
            Aperçu
          </v-tab>
        </v-tabs>

        <v-window v-model="tab">
          <!-- Details Tab -->
          <v-window-item value="details">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="mb-4">
                    <div class="text-overline text-grey">Description</div>
                    <div>{{ currentDocument.description || 'Aucune description' }}</div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Mots-clés</div>
                    <div>{{ currentDocument.keywords || '-' }}</div>
                  </div>

                  <div v-if="currentDocument.tags && currentDocument.tags.length > 0" class="mb-4">
                    <div class="text-overline text-grey">Tags</div>
                    <div>
                      <v-chip
                        v-for="tag in currentDocument.tags"
                        :key="tag"
                        class="mr-2 mt-1"
                        size="small"
                      >
                        {{ tag }}
                      </v-chip>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Langue</div>
                    <div>{{ currentDocument.language?.toUpperCase() || '-' }}</div>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="mb-4">
                    <div class="text-overline text-grey">Date d'effet</div>
                    <div>{{ formatDate(currentDocument.effective_date) }}</div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Prochaine révision</div>
                    <div :class="currentDocument.is_review_overdue ? 'text-error' : ''">
                      {{ formatDate(currentDocument.review_due_date) }}
                      <v-chip
                        v-if="currentDocument.is_review_overdue"
                        class="ml-2"
                        color="error"
                        size="x-small"
                      >
                        En retard
                      </v-chip>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Période de rétention</div>
                    <div>{{ currentDocument.retention_period_years || '-' }} an(s)</div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Confidentialité</div>
                    <div>
                      <v-chip
                        :color="currentDocument.is_confidential ? 'error' : 'success'"
                        size="small"
                      >
                        {{ currentDocument.is_confidential ? 'Confidentiel' : 'Public' }}
                      </v-chip>
                      <span v-if="currentDocument.confidentiality_level" class="ml-2">
                        ({{ currentDocument.confidentiality_level }})
                      </span>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Traçabilité source</div>
                    <div class="text-body-2">
                      <div>
                        Module source:
                        {{ currentDocument.module_type ? `${currentDocument.module_type} #${currentDocument.module_id || '-'}` : '-' }}
                      </div>
                      <div>
                        Type source: {{ currentDocument.source_type || '-' }}
                      </div>
                      <div>
                        Contexte source:
                        {{
                          [
                            currentDocument.source_module,
                            currentDocument.source_submodule,
                            currentDocument.source_section
                          ].filter(Boolean).join(' > ') || '-'
                        }}
                      </div>
                      <div>
                        Inventaire lié:
                        {{ currentDocument.inventory_link ? `${currentDocument.inventory_link.code} (ID ${currentDocument.inventory_link.id})` : '-' }}
                      </div>
                    </div>
                  </div>
                </v-col>

                <v-col cols="12">
                  <v-divider class="my-4" />

                  <div v-if="currentDocument.current_version" class="mb-4">
                    <div class="text-overline text-grey">Version actuelle</div>
                    <div class="d-flex align-center">
                      <v-icon color="primary" start>mdi-file-document</v-icon>
                      <div>
                        <strong>v{{ currentDocument.current_version.version_number }}</strong>
                        - {{ currentDocument.current_version.file_original_name }}
                        ({{ currentDocument.current_version.file_size_human }})
                      </div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="text-overline text-grey">Dates</div>
                    <div class="text-caption">
                      <div>Créé le: {{ formatDate(currentDocument.created_at) }}</div>
                      <div>Modifié le: {{ formatDate(currentDocument.updated_at) }}</div>
                      <div v-if="currentDocument.published_at">
                        Publié le: {{ formatDate(currentDocument.published_at) }}
                      </div>
                      <div v-if="currentDocument.archived_at">
                        Archivé le: {{ formatDate(currentDocument.archived_at) }}
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Versions Tab -->
          <v-window-item value="versions">
            <v-card-text class="pa-6">
              <v-timeline align="start" density="compact" side="end">
                <v-timeline-item
                  v-for="version in currentDocument.versions"
                  :key="version.id"
                  :dot-color="version.is_current ? 'primary' : 'grey'"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ formatDate(version.created_at) }}</div>
                  </template>

                  <v-card elevation="2">
                    <v-card-text>
                      <div class="d-flex justify-space-between align-center">
                        <div>
                          <div class="font-weight-bold">
                            Version {{ version.version_number }}
                            <v-chip
                              v-if="version.is_current"
                              class="ml-2"
                              color="primary"
                              size="x-small"
                            >
                              Actuelle
                            </v-chip>
                          </div>
                          <div class="text-caption text-grey">
                            {{ version.file_original_name }} ({{ version.file_size_human }})
                          </div>
                          <div v-if="version.change_summary" class="mt-2 text-body-2">
                            {{ version.change_summary }}
                          </div>
                        </div>
                        <v-btn
                          icon="mdi-download"
                          size="small"
                          variant="text"
                          @click="downloadVersion(version.id)"
                        />
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-window-item>

          <!-- Workflow Tab -->
          <v-window-item value="workflow">
            <v-card-text class="pa-6">
              <v-alert
                v-if="workflowIntegrity && !workflowIntegrity.valid"
                class="mb-4"
                type="error"
                variant="tonal"
              >
                Integrite de la chaine d'audit invalide. Verifiez les evenements de workflow.
              </v-alert>

              <v-alert
                v-else-if="workflowIntegrity?.valid"
                class="mb-4"
                type="success"
                variant="tonal"
              >
                Chaine d'audit verifiee. Dernier hash: {{ workflowIntegrity.last_hash || '-' }}
              </v-alert>

              <v-alert
                v-if="workflowEvents.length === 0"
                icon="mdi-timeline-outline"
                type="info"
                variant="tonal"
              >
                Aucun evenement workflow. Les transitions apparaitront ici.
              </v-alert>

              <v-timeline v-else align="start" density="compact" side="end">
                <v-timeline-item
                  v-for="event in workflowEvents"
                  :key="event.id"
                  :dot-color="getWorkflowEventColor(event.to_status)"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ formatDateTime(event.occurred_at) }}</div>
                  </template>

                  <v-card elevation="2">
                    <v-card-text>
                      <div class="d-flex justify-space-between align-start ga-3">
                        <div>
                          <div class="font-weight-bold">
                            {{ getWorkflowEventLabel(event.event_type) }}
                          </div>
                          <div class="text-caption text-medium-emphasis mt-1">
                            {{ getEventStatusLabel(event.from_status || 'draft') }} -> {{ getEventStatusLabel(event.to_status) }}
                          </div>
                          <div class="text-caption mt-1">
                            Acteur: {{ event.actor?.name || 'Systeme' }}
                          </div>
                          <div v-if="event.comment" class="text-body-2 mt-2">
                            {{ event.comment }}
                          </div>
                          <div class="text-caption mt-2">
                            Hash: {{ event.event_hash }}
                          </div>
                        </div>
                        <v-chip color="primary" size="x-small" variant="tonal">
                          #{{ event.chain_index }}
                        </v-chip>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-window-item>

          <!-- Viewer Tab -->
          <v-window-item v-if="currentDocument.current_version" value="viewer">
            <v-card-text class="pa-0" style="height: 650px; position: relative;">
              <v-progress-linear v-if="loadingPreview" color="primary" indeterminate style="position: absolute; top: 0; left: 0; z-index: 10;" />
              <iframe
                v-if="previewUrl"
                :src="previewUrl"
                style="width: 100%; height: 100%; border: none;"
                title="Aperçu du document"
              ></iframe>
              <div v-else-if="!loadingPreview" class="text-center text-grey pa-12">
                <v-icon color="grey" size="64">mdi-file-eye-off</v-icon>
                <div class="text-h6 mt-4">Aucun aperçu disponible</div>
                <div class="text-body-2 mb-4">
                  Impossible de charger l'aperçu de ce document.
                </div>
              </div>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <!-- Upload Version Dialog -->
    <v-dialog v-model="showUploadVersionDialog" max-width="600">
      <v-card>
        <v-card-title class="text-h6">Nouvelle version</v-card-title>
        <v-card-text>
          <v-file-input
            v-model="newVersionFile"
            accept=".pdf,.doc,.docx,.xls,.xlsx"
            density="comfortable"
            label="Sélectionner un fichier"
            show-size
            variant="outlined"
          />

          <v-textarea
            v-model="changeSummary"
            class="mt-4"
            density="comfortable"
            label="Résumé des modifications *"
            rows="3"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showUploadVersionDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            :disabled="!newVersionFile || newVersionFile.length === 0 || !changeSummary"
            :loading="loading"
            @click="uploadNewVersion"
          >
            Téléverser
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import type { DocumentWorkflowEvent } from '@/types/document'
  import { format } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getDocumentBusinessStatusConfig, getDocumentBusinessStatusLabel } from '@/modules/clienta/utils/documentStatus'
  import { useAuthStore } from '@/stores/auth'
  import { useDocumentStore } from '@/stores/documentStore'
  import { documentService } from '@/services/documentService'

  const route = useRoute()
  const documentStore = useDocumentStore()
  const authStore = useAuthStore()
  const toast = useToast()
  const { currentDocument, loading } = storeToRefs(documentStore)

  const tab = ref('details')
  const showUploadVersionDialog = ref(false)
  const newVersionFile = ref<File[]>([])
  const changeSummary = ref('')
  const confirmingRejectionDecision = ref(false)
  const workflowIntegrity = ref<{ valid: boolean, last_hash?: string } | null>(null)
  
  const previewUrl = ref('')
  const loadingPreview = ref(false)

  async function loadPreviewBlob () {
    if (!currentDocument.value?.id) return
    try {
      loadingPreview.value = true
      const blob = await documentService.preview(currentDocument.value.id)
      if (previewUrl.value) {
        window.URL.revokeObjectURL(previewUrl.value)
      }
      previewUrl.value = window.URL.createObjectURL(blob)
    } catch (error) {
      console.error('Erreur chargement aperçu:', error)
      toast.error('Impossible de charger l\'aperçu du document.')
    } finally {
      loadingPreview.value = false
    }
  }

  watch(tab, (newTab) => {
    if (newTab === 'viewer') {
      void loadPreviewBlob()
    }
  })

  onUnmounted(() => {
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value)
    }
  })

  const isApprovedForExport = computed(() => {
    const doc = currentDocument.value
    if (!doc) return false
    return doc.workflow_status === 'approved' || 
           doc.status === 'approved' || 
           ['pending_verification', 'pending_approval', 'draft'].includes(doc.workflow_status || doc.status)
  })

  const showSubmitterDecisionAlert = computed(() => {
    const doc = currentDocument.value
    if (!doc || doc.workflow_status !== 'awaiting_submitter_confirmation') {
      return false
    }
    return Number(doc.author_id) === Number(authStore.user?.id)
  })

  const canSubmitDocumentAgain = computed(() => {
    const workflowStatus = currentDocument.value?.workflow_status || currentDocument.value?.status
    return workflowStatus === 'draft' || workflowStatus === 'rejected'
  })

  const workflowEvents = computed<DocumentWorkflowEvent[]>(() => {
    const events = currentDocument.value?.workflow_events
    if (!Array.isArray(events)) {
      return []
    }

    return [...events].toSorted((a, b) => (a.chain_index || 0) - (b.chain_index || 0))
  })

  function getCategoryColor (level?: number) {
    const colors = ['blue', 'green', 'orange', 'purple', 'red']
    return colors[(level || 1) - 1] || 'grey'
  }

  function getStatusColor (document: any) {
    return getDocumentBusinessStatusConfig(document).color
  }

  function getStatusLabel (document: any) {
    return getDocumentBusinessStatusLabel(document)
  }

  function getEventStatusLabel (workflowStatus?: string | null) {
    const doc = currentDocument.value as any
    return getDocumentBusinessStatusLabel({
      workflow_status: workflowStatus,
      version: doc?.version,
      metadata: doc?.metadata,
    })
  }

  function formatDate (dateString?: string) {
    if (!dateString) return '-'
    return format(new Date(dateString), 'dd MMM yyyy', { locale: fr })
  }

  function formatDateTime (dateString?: string) {
    if (!dateString) return '-'
    return format(new Date(dateString), 'dd MMM yyyy HH:mm', { locale: fr })
  }

  function getWorkflowEventLabel (eventType: string) {
    const labels: Record<string, string> = {
      confirm_code: 'Code confirme',
      submit_for_approval: 'Soumission workflow',
      verify: 'Verification effectuee',
      approve: 'Validation effectuee',
      reject: 'Rejet effectue',
      confirm_rejection_decision: 'Decision soumissionnaire',
    }
    return labels[eventType] || eventType
  }

  function getWorkflowEventColor (status: string) {
    const colors: Record<string, string> = {
      pending_verification: 'warning',
      pending_approval: 'info',
      approved: 'success',
      rejected: 'error',
      awaiting_submitter_confirmation: 'orange',
    }
    return colors[status] || 'primary'
  }

  async function refreshWorkflowIntegrity (documentId: number) {
    try {
      const response = await api.get(`/documents/${documentId}/workflow-integrity`)
      workflowIntegrity.value = response.data?.data || null
    } catch {
      workflowIntegrity.value = null
    }
  }

  async function downloadDoc () {
    if (currentDocument.value) {
      await documentStore.downloadDocument(currentDocument.value.id)
    }
  }

  async function downloadVersion (versionId: number) {
    if (currentDocument.value) {
      await documentStore.downloadDocument(currentDocument.value.id, versionId)
    }
  }

  async function submitForApproval () {
    if (currentDocument.value) {
      await documentStore.submitForApproval(currentDocument.value.id)
    }
  }

  async function archiveDoc () {
    if (currentDocument.value && confirm('Êtes-vous sûr de vouloir archiver ce document ?')) {
      await documentStore.archiveDocument(currentDocument.value.id)
    }
  }

  async function uploadNewVersion () {
    if (!currentDocument.value || !newVersionFile.value || newVersionFile.value.length === 0 || !changeSummary.value) {
      return
    }

    await documentStore.uploadVersion(
      currentDocument.value.id,
      newVersionFile.value[0] as File,
      changeSummary.value,
    )

    showUploadVersionDialog.value = false
    newVersionFile.value = []
    changeSummary.value = ''
  }

  async function confirmRejectionDecision (releaseCode: boolean) {
    if (!currentDocument.value) return

    try {
      confirmingRejectionDecision.value = true
      await api.post(`/documents/${currentDocument.value.id}/confirm-rejection-decision`, {
        release_code: releaseCode,
      })
      toast.success(releaseCode ? 'Code libéré.' : 'Code gardé pour correction.')
      await documentStore.fetchDocument(currentDocument.value.id)
      await refreshWorkflowIntegrity(currentDocument.value.id)
    } catch {
      toast.error('Impossible d\'enregistrer votre decision.')
    } finally {
      confirmingRejectionDecision.value = false
    }
  }

  onMounted(async () => {
    const documentId = Number((route.params as any).id)
    if (documentId) {
      await documentStore.fetchDocument(documentId)
      await refreshWorkflowIntegrity(documentId)
    }
  })
</script>
