<template>
  <SuperAdminLayout current-page="norms">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex align-center mb-6">
        <v-tooltip location="top" text="Retour">
          <template #activator="{ props: tooltipProps }">
            <v-btn
              v-bind="tooltipProps"
              class="mr-4"
              icon
              variant="text"
              @click="$router.back()"
            >
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-3">
            <h1 class="text-h4 font-weight-bold text-primary">{{ norm?.code || 'Norme' }}</h1>
            <v-chip
              v-if="norm"
              :color="getStatusColor(norm.status)"
              size="x-small"
              variant="tonal"
            >
              {{ getStatusLabel(norm.status) }}
            </v-chip>
          </div>
          <p class="text-subtitle-1 text-grey-darken-1 mt-1">
            {{ norm?.name || 'Chargement...' }}
          </p>
        </div>
        <div class="d-flex align-center ga-2 flex-wrap">
          <input
            ref="pdfInput"
            accept="application/pdf"
            class="d-none"
            type="file"
            @change="handlePdfSelected"
          >
          <v-btn
            color="primary"
            :loading="uploadingPdf"
            prepend-icon="mdi-file-pdf-box"
            variant="tonal"
            @click="triggerPdfUpload"
          >
            {{ norm?.has_pdf_document ? 'Remplacer PDF' : 'Téléverser PDF' }}
          </v-btn>
          <v-btn
            v-if="norm?.pdf_file_url"
            color="info"
            prepend-icon="mdi-open-in-new"
            variant="outlined"
            @click="openPdf"
          >
            Ouvrir PDF
          </v-btn>
          <v-btn
            v-if="norm?.has_pdf_document"
            color="error"
            :loading="deletingPdf"
            prepend-icon="mdi-delete-outline"
            variant="text"
            @click="deletePdf"
          >
            Supprimer PDF
          </v-btn>
          <v-btn
            color="info"
            :disabled="!!norm?.has_pdf_document"
            prepend-icon="mdi-file-excel"
            variant="tonal"
            @click="openImportDialog"
          >
            Importer Excel
          </v-btn>
          <v-btn
            v-if="norm?.status === 'draft'"
            color="success"
            :loading="publishing"
            prepend-icon="mdi-publish"
            variant="flat"
            @click="publishNorm"
          >
            Publier
          </v-btn>
          <v-btn
            v-if="norm?.status === 'published'"
            color="warning"
            :loading="archiving"
            prepend-icon="mdi-archive"
            variant="outlined"
            @click="archiveNorm"
          >
            Archiver
          </v-btn>
          <v-btn
            v-if="norm?.status === 'archived'"
            color="success"
            :loading="unarchiving"
            prepend-icon="mdi-archive-arrow-up-outline"
            variant="flat"
            @click="unarchiveNorm"
          >
            Désarchiver
          </v-btn>
        </div>
      </div>

      <!-- Metadata Card -->
      <v-row class="mb-6">
        <v-col cols="12" md="8">
          <v-card elevation="2">
            <v-card-title class="pa-6 pb-4">
              <div class="d-flex align-center justify-space-between">
                <span class="text-h6">Informations</span>
                <v-select
                  v-if="versions.length > 0"
                  v-model="selectedVersionId"
                  density="compact"
                  hide-details
                  :items="versionOptions"
                  label="Version"
                  style="max-width: 200px"
                  variant="outlined"
                  @update:model-value="loadVersion"
                />
              </div>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="mb-4">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      Code
                    </div>
                    <div class="text-body-1 font-weight-medium">{{ norm?.code || 'N/A' }}</div>
                  </div>
                  <div class="mb-4">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      Domaine
                    </div>
                    <v-chip color="primary" size="x-small" variant="tonal">
                      {{ getDomainLabel(norm?.domain) }}
                    </v-chip>
                  </div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="mb-4">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      Version
                    </div>
                    <div class="text-body-1 font-weight-medium">
                      {{ currentVersion?.version_code || 'N/A' }}
                    </div>
                  </div>
                  <div class="mb-4">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      Date de création
                    </div>
                    <div class="text-body-1">{{ formatDate(norm?.created_at) }}</div>
                  </div>
                </v-col>
                <v-col cols="12">
                  <v-alert
                    v-if="norm?.has_pdf_document"
                    border="start"
                    color="error"
                    density="comfortable"
                    icon="mdi-file-pdf-box"
                    variant="tonal"
                  >
                    Cette norme utilise actuellement un fichier PDF importé. L’import Excel est désactivé tant que ce PDF existe.
                  </v-alert>
                  <v-alert
                    v-else
                    border="start"
                    color="info"
                    density="comfortable"
                    icon="mdi-file-tree-outline"
                    variant="tonal"
                  >
                    Cette norme est actuellement alimentée par sa structure en base de données issue de l’éditeur ou de l’import Excel.
                  </v-alert>
                </v-col>
                <v-col v-if="norm?.pdf_original_name" cols="12">
                  <div class="mb-2">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      PDF importé
                    </div>
                    <div class="text-body-2">
                      {{ norm.pdf_original_name }}
                      <span v-if="norm.pdf_uploaded_at" class="text-medium-emphasis">
                        • téléversé le {{ formatDate(norm.pdf_uploaded_at) }}
                      </span>
                    </div>
                  </div>
                </v-col>
                <v-col v-if="norm?.description" cols="12">
                  <div class="mb-2">
                    <div class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">
                      Description
                    </div>
                    <div class="text-body-2">{{ norm.description }}</div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card elevation="2">
            <v-card-title class="pa-6 pb-4">Statistiques</v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div>
                  <div class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">
                    Total sections
                  </div>
                  <div class="text-h5 font-weight-bold">{{ totalSections }}</div>
                </div>
                <v-avatar color="primary" size="48" variant="tonal">
                  <v-icon color="primary">mdi-file-tree</v-icon>
                </v-avatar>
              </div>
              <v-divider class="my-4" />
              <div class="d-flex align-center justify-space-between mb-4">
                <div>
                  <div class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">
                    Versions
                  </div>
                  <div class="text-h5 font-weight-bold">{{ versions.length }}</div>
                </div>
                <v-avatar color="info" size="48" variant="tonal">
                  <v-icon color="info">mdi-history</v-icon>
                </v-avatar>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Tree View Card -->
      <v-card elevation="2">
        <v-card-title class="pa-6 pb-4">
          <div class="d-flex align-center justify-space-between">
            <span class="text-h6">Structure de la norme</span>
            <div class="d-flex gap-2">
              <v-text-field
                v-model="searchQuery"
                clearable
                density="compact"
                hide-details
                placeholder="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                style="max-width: 300px"
                variant="outlined"
              />
              <v-btn
                color="primary"
                prepend-icon="mdi-unfold-more-horizontal"
                variant="outlined"
                @click="expandAll"
              >
                Tout ouvrir
              </v-btn>
              <v-btn
                prepend-icon="mdi-unfold-less-horizontal"
                variant="outlined"
                @click="collapseAll"
              >
                Tout fermer
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                variant="flat"
                @click="openAddRootDialog"
              >
                Ajouter chapitre
              </v-btn>
            </div>
          </div>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-alert
            v-if="norm?.has_pdf_document"
            border="start"
            class="mb-4"
            color="primary"
            icon="mdi-file-pdf-box"
            variant="tonal"
          >
            La bibliothèque entreprise affichera ce PDF en priorité pour cette norme.
          </v-alert>
          <div v-if="loadingSections" class="text-center py-12">
            <v-progress-circular color="primary" indeterminate size="48" />
            <p class="text-body-2 mt-4">Chargement de la structure...</p>
          </div>

          <div v-else-if="!sections || sections.length === 0" class="py-8">
            <EmptyState
              :description="searchQuery ? 'Aucun résultat pour votre recherche.' : 'Commencez par ajouter un chapitre.'"
              icon="mdi-file-tree-outline"
              title="Aucune section"
            />
            <div v-if="!searchQuery" class="text-center mt-4">
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                variant="flat"
                @click="openAddRootDialog"
              >
                Ajouter un chapitre
              </v-btn>
            </div>
          </div>

          <NormSectionTree
            v-else
            :expanded-sections="expandedSections"
            :sections="filteredSections"
            @add-child="openAddChildDialog"
            @delete-section="openDeleteDialog"
            @edit-section="openEditDialog"
            @toggle-section="toggleSection"
          />
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Edit Section Dialog -->
    <v-dialog v-model="editDialog" max-width="800" persistent>
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4">
          <v-icon class="mr-3" color="primary" size="32">mdi-pencil</v-icon>
          <span class="text-h6 font-weight-bold">Modifier la section</span>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form ref="editFormRef">
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editForm.number"
                  :error-messages="editFieldErrors.number"
                  label="Numéro *"
                  :rules="[(v: any) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="editForm.title"
                  :error-messages="editFieldErrors.title"
                  label="Titre *"
                  :rules="[(v: any) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.content"
                  :error-messages="editFieldErrors.content"
                  label="Contenu"
                  rows="6"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-combobox
                  v-model="editForm.references"
                  chips
                  :error-messages="editFieldErrors.references"
                  hint="Entrez les références et appuyez sur Entrée"
                  label="Références"
                  multiple
                  persistent-hint
                  placeholder="Ex: 4.1, 5.2, 6.1"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn :disabled="saving" variant="text" @click="editDialog = false">
            Annuler
          </v-btn>
          <v-btn color="primary" :loading="saving" variant="flat" @click="handleEdit">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Add Section Dialog -->
    <v-dialog v-model="addDialog" max-width="800" persistent>
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4">
          <v-icon class="mr-3" color="success" size="32">mdi-plus-circle</v-icon>
          <span class="text-h6 font-weight-bold">
            {{ addForm.parent_id ? 'Ajouter une sous-section' : 'Ajouter un chapitre' }}
          </span>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form ref="addFormRef">
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="addForm.type"
                  :error-messages="addFieldErrors.type"
                  :items="typeOptions"
                  label="Type *"
                  :rules="[(v: any) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="addForm.number"
                  :error-messages="addFieldErrors.number"
                  label="Numéro *"
                  :rules="[(v: any) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="addForm.level"
                  label="Niveau"
                  readonly
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="addForm.title"
                  :error-messages="addFieldErrors.title"
                  label="Titre *"
                  :rules="[(v: any) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="addForm.content"
                  :error-messages="addFieldErrors.content"
                  label="Contenu"
                  rows="6"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-combobox
                  v-model="addForm.references"
                  chips
                  :error-messages="addFieldErrors.references"
                  hint="Entrez les références et appuyez sur Entrée"
                  label="Références"
                  multiple
                  persistent-hint
                  placeholder="Ex: 4.1, 5.2, 6.1"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn :disabled="adding" variant="text" @click="addDialog = false">
            Annuler
          </v-btn>
          <v-btn color="success" :loading="adding" variant="flat" @click="handleAdd">
            Ajouter
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Section Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4">
          <v-icon class="mr-3" color="error" size="32">mdi-delete-alert-outline</v-icon>
          <span class="text-h6 font-weight-bold">Supprimer la section</span>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <p class="text-body-1 mb-2">
            Êtes-vous sûr de vouloir supprimer la section <strong>{{ selectedSection?.number }}</strong> ?
          </p>
          <p class="text-body-2" style="color: rgb(var(--v-theme-on-surface-variant))">
            Cette action supprimera également toutes les sous-sections. Cette action est irréversible.
          </p>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn :disabled="deleting" variant="text" @click="deleteDialog = false">
            Annuler
          </v-btn>
          <v-btn color="error" :loading="deleting" variant="flat" @click="handleDelete">
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Import Excel Dialog -->
    <v-dialog v-model="importDialog" max-width="720" persistent>
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4">
          <v-icon class="mr-3" color="info" size="32">mdi-file-excel</v-icon>
          <span class="text-h6 font-weight-bold">Importer une version de norme</span>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-alert class="mb-4" type="info" variant="tonal">
            Le fichier importé doit respecter le template officiel de norme.
          </v-alert>
          <div class="d-flex justify-end mb-4">
            <v-btn
              color="primary"
              :loading="downloadingTemplate"
              prepend-icon="mdi-download"
              variant="tonal"
              @click="downloadTemplate"
            >
              Télécharger le canevas Excel
            </v-btn>
          </div>

          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="importAction"
                item-title="title"
                item-value="value"
                :items="importActionOptions"
                label="Mode d'import"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="importVersionCode"
                label="Code version"
                placeholder="Ex: 2015"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-file-input
                v-model="importFiles"
                accept=".xlsx,.xls"
                :disabled="!!norm?.has_pdf_document"
                label="Fichier Excel"
                prepend-icon="mdi-paperclip"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-checkbox
                v-model="importPublishAfter"
                color="success"
                hide-details
                label="Publier automatiquement après import"
              />
              <div class="text-caption text-medium-emphasis mt-1">
                Recommandé si la norme doit être visible immédiatement dans la bibliothèque des entreprises abonnées.
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn :disabled="importing" variant="text" @click="importDialog = false">
            Annuler
          </v-btn>
          <v-btn
            color="info"
            :disabled="!!norm?.has_pdf_document"
            :loading="importing"
            variant="flat"
            @click="handleImportExcel"
          >
            Importer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Norm, NormSection, NormVersion } from '@/types/api'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import NormSectionTree from '@/modules/superadmin/components/NormSectionTree.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import {
    type NormImportFileModel,
    resolveNormImportFile,
    validateNormImportFile,
  } from '@/modules/superadmin/utils/normImportFile'
  import normService from '@/services/normService'

  const route = useRoute()
  const toast = useToast()

  const norm = ref<Norm | null>(null)
  const versions = ref<NormVersion[]>([])
  const currentVersion = ref<NormVersion | null>(null)
  const selectedVersionId = ref<number | null>(null)
  const sections = ref<NormSection[]>([])
  const expandedSections = ref<Set<number>>(new Set())
  const searchQuery = ref('')

  const loadingSections = ref(false)
  const publishing = ref(false)
  const archiving = ref(false)
  const unarchiving = ref(false)
  const editDialog = ref(false)
  const addDialog = ref(false)
  const deleteDialog = ref(false)
  const importDialog = ref(false)
  const pdfInput = ref<HTMLInputElement | null>(null)
  const selectedSection = ref<NormSection | null>(null)
  const saving = ref(false)
  const adding = ref(false)
  const deleting = ref(false)
  const importing = ref(false)
  const uploadingPdf = ref(false)
  const deletingPdf = ref(false)
  const downloadingTemplate = ref(false)

  const editFormRef = ref<any>(null)
  const addFormRef = ref<any>(null)
  const editFieldErrors = ref<Record<string, string[]>>({})
  const addFieldErrors = ref<Record<string, string[]>>({})
  const { run: runLocked } = useActionLock()
  const importFiles = ref<NormImportFileModel>(null)
  const importAction = ref<'replace' | 'merge'>('replace')
  const importVersionCode = ref('')
  const importPublishAfter = ref(false)

  const importActionOptions = [
    { title: 'Remplacer la structure actuelle', value: 'replace' },
    { title: 'Fusionner avec la structure actuelle', value: 'merge' },
  ]

  const editForm = ref({
    number: '',
    title: '',
    content: '',
    references: [] as string[],
  })

  const addForm = ref({
    type: 'chapter',
    level: 1,
    number: '',
    title: '',
    content: '',
    parent_id: null as number | null,
    references: [] as string[],
  })

  const typeOptions = [
    { title: 'Chapitre', value: 'chapter' },
    { title: 'Sous-chapitre', value: 'subchapter' },
    { title: 'Paragraphe', value: 'paragraph' },
    { title: 'Point', value: 'point' },
    { title: 'Note', value: 'note' },
    { title: 'Annexe', value: 'annex' },
  ]

  const versionOptions = computed(() => {
    return versions.value.map(v => ({
      title: `${v.version_code}${v.is_current ? ' (Actuelle)' : ''}`,
      value: v.id,
    }))
  })

  const totalSections = computed(() => {
    return countSections(sections.value)
  })

  const filteredSections = computed(() => {
    if (!searchQuery.value) return sections.value
    return filterSections(sections.value, searchQuery.value.toLowerCase())
  })

  const hasPdfDocument = computed(() => Boolean(norm.value?.has_pdf_document && norm.value?.pdf_file_url))

  function getRouteNormId (): number | null {
    const rawId = (route.params as Record<string, unknown>).id
    const idValue = typeof rawId === 'string' ? rawId : (Array.isArray(rawId) ? rawId[0] : null)
    if (!idValue) return null
    const parsed = Number.parseInt(idValue, 10)
    return Number.isNaN(parsed) ? null : parsed
  }

  function getCurrentNormVersion (normData: Norm): NormVersion | null {
    const legacyCurrentVersion = (normData as Norm & { current_version?: NormVersion }).current_version
    return normData.currentVersion || legacyCurrentVersion || normData.versions?.find(v => v.is_current) || normData.versions?.[0] || null
  }

  onMounted(async () => {
    await loadNorm()
  })

  watch(selectedVersionId, async newId => {
    if (newId && newId !== currentVersion.value?.id) {
      await loadVersion(newId)
    }
  })

  async function loadNorm () {
    const id = getRouteNormId()
    if (!id) {
      toast.error('Identifiant de norme invalide')
      return
    }

    try {
      const normData = await normService.getNorm(id)
      norm.value = normData

      // Versions are included in the norm data
      versions.value = normData.versions || []

      // Set current version
      currentVersion.value = getCurrentNormVersion(normData)
      selectedVersionId.value = currentVersion.value?.id || null

      if (hasPdfDocument.value) {
        sections.value = []
        expandedSections.value.clear()
      } else if (currentVersion.value) {
        await loadSections(id, currentVersion.value.id)
      }
    } catch {
      toast.error('Impossible de charger la norme')
    }
  }

  async function loadVersion (versionId: number) {
    loadingSections.value = true
    try {
      const id = getRouteNormId()
      if (!id) {
        toast.error('Identifiant de norme invalide')
        return
      }
      await loadSections(id, versionId)
      currentVersion.value = versions.value.find(v => v.id === versionId) || null
    } catch {
      toast.error('Impossible de charger la version')
    } finally {
      loadingSections.value = false
    }
  }

  async function loadSections (normId: number, versionId?: number) {
    loadingSections.value = true
    try {
      const normData = await normService.getNorm(normId)

      // Obtenir la version appropriée
      const version = versionId
        ? normData.versions?.find(v => v.id === versionId)
        : getCurrentNormVersion(normData)

      // Les sections sont déjà organisées en arbre par le backend
      sections.value = version?.sections || []

      // Debug: afficher les sections chargées
      // Expand all sections par défaut pour la première visualisation
      if (sections.value.length > 0 && expandedSections.value.size === 0) {
        expandAll()
      }
    } catch {
      toast.error('Impossible de charger les sections')
      sections.value = []
    } finally {
      loadingSections.value = false
    }
  }

  function countSections (secs: NormSection[]): number {
    let count = secs.length
    for (const s of secs) {
      if (s.children) count += countSections(s.children)
    }
    return count
  }

  function filterSections (secs: NormSection[], query: string): NormSection[] {
    const filtered: NormSection[] = []

    for (const section of secs) {
      const matches
        = section.number?.toLowerCase().includes(query)
          || section.title?.toLowerCase().includes(query)
          || section.content?.toLowerCase().includes(query)

      const childrenFiltered = section.children ? filterSections(section.children, query) : []

      if (matches || childrenFiltered.length > 0) {
        filtered.push({
          ...section,
          children: childrenFiltered.length > 0 ? childrenFiltered : section.children,
        })
        // Auto-expand matching sections
        if (matches) expandedSections.value.add(section.id)
      }
    }

    return filtered
  }

  function toggleSection (id: number) {
    if (expandedSections.value.has(id)) {
      expandedSections.value.delete(id)
    } else {
      expandedSections.value.add(id)
    }
  }

  function expandAll () {
    const allIds: number[] = []
    function collectIds (secs: NormSection[]) {
      for (const s of secs) {
        allIds.push(s.id)
        if (s.children) collectIds(s.children)
      }
    }
    collectIds(sections.value)
    expandedSections.value = new Set(allIds)
  }

  function collapseAll () {
    expandedSections.value.clear()
  }

  function openEditDialog (section: NormSection) {
    selectedSection.value = section
    editForm.value = {
      number: section.number || '',
      title: section.title || '',
      content: section.content || '',
      references: section.references || [],
    }
    editDialog.value = true
  }

  async function handleEdit () {
    const valid = await editFormRef.value?.validate()
    if (!valid?.valid || !selectedSection.value || !currentVersion.value || !norm.value) return

    await runLocked('norm-section-edit', async () => {
      saving.value = true
      try {
        editFieldErrors.value = {}
        const payload = {
          title: editForm.value.title,
          content: editForm.value.content,
          references: editForm.value.references || [],
        }

        await normService.updateSection(selectedSection.value!.id, payload)
        toast.success('Section modifiée')
        editDialog.value = false
        await loadSections(norm.value!.id, currentVersion.value!.id)
      } catch (error: any) {
        if (error?.response?.status === 422 && error?.response?.data?.errors) {
          editFieldErrors.value = error.response.data.errors
        }
      } finally {
        saving.value = false
      }
    })
  }

  function openAddRootDialog () {
    addForm.value = {
      type: 'chapter',
      level: 1,
      number: '',
      title: '',
      content: '',
      parent_id: null,
      references: [],
    }
    addDialog.value = true
  }

  function openAddChildDialog (parent: NormSection) {
    addForm.value = {
      type: 'subchapter',
      level: (parent.level || 0) + 1,
      number: '',
      title: '',
      content: '',
      parent_id: parent.id,
      references: [],
    }
    addDialog.value = true
  }

  async function handleAdd () {
    const valid = await addFormRef.value?.validate()
    if (!valid?.valid || !currentVersion.value || !norm.value) return

    await runLocked('norm-section-add', async () => {
      adding.value = true
      try {
        addFieldErrors.value = {}
        const payload = {
          type: addForm.value.type,
          number: addForm.value.number,
          title: addForm.value.title,
          content: addForm.value.content,
          parent_id: addForm.value.parent_id ?? undefined,
          references: addForm.value.references || [],
        }

        await normService.createSection(currentVersion.value!.id, payload)
        toast.success('Section ajoutée')
        addDialog.value = false
        await loadSections(norm.value!.id, currentVersion.value!.id)
      } catch (error: any) {
        if (error?.response?.status === 422 && error?.response?.data?.errors) {
          addFieldErrors.value = error.response.data.errors
        }
      } finally {
        adding.value = false
      }
    })
  }

  function openDeleteDialog (section: NormSection) {
    selectedSection.value = section
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!selectedSection.value || !currentVersion.value || !norm.value) return

    await runLocked('norm-section-delete', async () => {
      deleting.value = true
      try {
        await normService.deleteSection(selectedSection.value!.id)
        toast.success('Section supprimée')
        deleteDialog.value = false
        await loadSections(norm.value!.id, currentVersion.value!.id)
      } finally {
        deleting.value = false
      }
    })
  }

  async function publishNorm () {
    if (!norm.value) return
    await runLocked('norm-publish', async () => {
      publishing.value = true
      try {
        await normService.publishNorm(norm.value!.id)
        toast.success('Norme publiée')
        await loadNorm()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Publication impossible pour cette norme.')
      } finally {
        publishing.value = false
      }
    })
  }

  async function archiveNorm () {
    if (!norm.value) return
    await runLocked('norm-archive', async () => {
      archiving.value = true
      try {
        await normService.archiveNorm(norm.value!.id)
        toast.success('Norme archivée')
        await loadNorm()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Archivage impossible pour cette norme.')
      } finally {
        archiving.value = false
      }
    })
  }

  async function unarchiveNorm () {
    if (!norm.value) return
    await runLocked('norm-unarchive', async () => {
      unarchiving.value = true
      try {
        await normService.unarchiveNorm(norm.value!.id)
        toast.success('Norme désarchivée')
        await loadNorm()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Désarchivage impossible pour cette norme.')
      } finally {
        unarchiving.value = false
      }
    })
  }

  function openImportDialog () {
    if (!norm.value) return
    if (norm.value.has_pdf_document) {
      toast.error('Import Excel indisponible: cette norme utilise deja un PDF importé.')
      return
    }
    importAction.value = 'replace'
    importFiles.value = null
    importVersionCode.value = currentVersion.value?.version_code || ''
    importPublishAfter.value = norm.value.status !== 'published'
    importDialog.value = true
  }

  function triggerPdfUpload () {
    pdfInput.value?.click()
  }

  async function handlePdfSelected (event: Event) {
    if (!norm.value) return

    const target = event.target as HTMLInputElement | null
    const file = target?.files?.[0]
    if (!file) return

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
      toast.error('Veuillez sélectionner un fichier PDF valide.')
      if (target) target.value = ''
      return
    }

    await runLocked('norm-upload-pdf', async () => {
      uploadingPdf.value = true
      try {
        await normService.uploadNormPdf(norm.value!.id, file)
        toast.success('PDF de la norme téléversé avec succès.')
        importDialog.value = false
        await loadNorm()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Impossible de téléverser le PDF de la norme.')
      } finally {
        uploadingPdf.value = false
        if (target) target.value = ''
      }
    })
  }

  function openPdf () {
    const url = norm.value?.pdf_file_url
    if (!url) return
    window.open(url, '_blank', 'noopener')
  }

  async function deletePdf () {
    if (!norm.value?.has_pdf_document) return

    await runLocked('norm-delete-pdf', async () => {
      deletingPdf.value = true
      try {
        await normService.deleteNormPdf(norm.value!.id)
        toast.success('PDF de la norme supprimé avec succès.')
        await loadNorm()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Impossible de supprimer le PDF de la norme.')
      } finally {
        deletingPdf.value = false
      }
    })
  }

  async function downloadTemplate () {
    downloadingTemplate.value = true
    try {
      const blob = await normService.downloadTemplate()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'norme_iso_template.xlsx'
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      toast.success('Canevas téléchargé avec succès.')
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de télécharger le canevas.')
    } finally {
      downloadingTemplate.value = false
    }
  }

  async function handleImportExcel () {
    if (!norm.value) return

    const selectedFile = resolveNormImportFile(importFiles.value)
    const fileError = validateNormImportFile(selectedFile)
    if (fileError) {
      toast.error(fileError)
      return
    }
    if (!selectedFile) {
      toast.error('Veuillez sélectionner un fichier Excel.')
      return
    }

    const versionCode = importVersionCode.value || currentVersion.value?.version_code || ''
    if (!versionCode) {
      toast.error('Veuillez renseigner le code version.')
      return
    }

    await runLocked('norm-import-excel', async () => {
      importing.value = true
      try {
        const result = await normService.importExcel(selectedFile, {
          code: norm.value!.code,
          name: norm.value!.name,
          description: norm.value!.description || '',
          domain: norm.value!.domain,
          version_code: versionCode,
          action: importAction.value,
        })

        let publishedAfterImport = false
        let publishAfterImportFailed = false
        if (importPublishAfter.value && result.norm.status !== 'published') {
          try {
            await normService.publishNorm(result.norm.id)
            publishedAfterImport = true
          } catch {
            publishAfterImportFailed = true
          }
        }

        const warnings = result.validation_errors?.length || 0
        toast.success(
          publishedAfterImport
            ? (warnings > 0
              ? `Import et publication terminés avec ${warnings} avertissement(s).`
              : 'Import et publication de la norme terminés.')
            : (warnings > 0
              ? `Import terminé avec ${warnings} avertissement(s).`
              : 'Import de la norme terminé.'),
        )
        if (publishAfterImportFailed) {
          toast.error('Import réussi, mais la publication automatique a échoué. Utilisez le bouton "Publier".')
        }
        importDialog.value = false
        await loadNorm()
      } catch (error: any) {
        const message = error?.response?.data?.message || 'Import impossible pour cette norme.'
        toast.error(message)
      } finally {
        importing.value = false
      }
    })
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      draft: 'warning',
      published: 'success',
      archived: 'grey',
    }
    return colors[status] || 'default'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      published: 'Publiée',
      archived: 'Archivée',
    }
    return labels[status] || status
  }

  function getDomainLabel (domain?: string): string {
    if (!domain) return 'N/A'
    const labels: Record<string, string> = {
      quality: 'Qualité',
      environment: 'Environnement',
      security: 'Sécurité',
      food_safety: 'Sécurité alimentaire',
      integrated: 'SMI',
      other: 'Autre',
    }
    return labels[domain] || domain
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
  }
</script>
