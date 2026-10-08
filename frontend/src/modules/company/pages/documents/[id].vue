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
                  :color="getStatusColor(currentDocument.status)"
                  size="small"
                >
                  {{ getStatusLabel(currentDocument.status) }}
                </v-chip>
              </div>
            </div>

            <div class="d-flex gap-2">
              <v-btn
                v-if="currentDocument.current_version"
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
                    v-if="currentDocument.status === 'draft'"
                    prepend-icon="mdi-send"
                    @click="submitForApproval"
                  >
                    Soumettre pour approbation
                  </v-list-item>
                  <v-list-item
                    prepend-icon="mdi-upload"
                    @click="showUploadVersionDialog = true"
                  >
                    Nouvelle version
                  </v-list-item>
                  <v-list-item
                    v-if="currentDocument.status === 'approved' && !currentDocument.is_archived"
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

          <!-- Viewer Tab -->
          <v-window-item v-if="currentDocument.current_version" value="viewer">
            <v-card-text class="pa-6">
              <div class="text-center text-grey">
                <v-icon color="grey" size="64">mdi-file-eye</v-icon>
                <div class="text-h6 mt-4">Aperçu du document</div>
                <div class="text-body-2 mb-4">
                  L'aperçu PDF sera disponible avec l'intégration de vue-pdf-embed
                </div>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-download"
                  @click="downloadDoc"
                >
                  Télécharger pour voir
                </v-btn>
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
  import { format } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { storeToRefs } from 'pinia'
  import { onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { useDocumentStore } from '@/stores/documentStore'

  const route = useRoute()
  const documentStore = useDocumentStore()
  const { currentDocument, loading } = storeToRefs(documentStore)

  const tab = ref('details')
  const showUploadVersionDialog = ref(false)
  const newVersionFile = ref<File[]>([])
  const changeSummary = ref('')

  function getCategoryColor (level?: number) {
    const colors = ['blue', 'green', 'orange', 'purple', 'red']
    return colors[(level || 1) - 1] || 'grey'
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'warning',
      pending_approval: 'info',
      approved: 'success',
      obsolete: 'grey',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      pending_approval: 'En attente',
      approved: 'Approuvé',
      obsolete: 'Obsolète',
    }
    return labels[status] || status
  }

  function formatDate (dateString?: string) {
    if (!dateString) return '-'
    return format(new Date(dateString), 'dd MMM yyyy', { locale: fr })
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

  onMounted(async () => {
    const documentId = Number((route.params as any).id)
    if (documentId) {
      await documentStore.fetchDocument(documentId)
    }
  })
</script>
