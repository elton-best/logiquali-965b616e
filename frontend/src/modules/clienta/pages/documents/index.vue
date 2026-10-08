<template>
  <ClientALayout current-page="documents">
    <v-container class="documents-container" fluid>
      <!-- Hero Section -->
      <div class="hero-section mb-6">
        <div class="d-flex align-center justify-space-between flex-wrap ga-4">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">Inventaire Documentaire</h1>
            <p class="text-body-2 text-medium-emphasis">Gestion centralisée de vos documents QHSE</p>
          </div>
          <div class="d-flex ga-2 hero-actions">
            <v-btn
              v-if="canConfigureNomenclature"
              color="primary"
              prepend-icon="mdi-cog"
              variant="tonal"
              @click="showNomenclatures = true"
            >
              Nomenclatures
            </v-btn>
            <v-btn color="primary" prepend-icon="mdi-cloud-upload" variant="outlined" @click="showUploadFlow = true">
              Importer document
            </v-btn>

            <v-btn color="primary" prepend-icon="mdi-download" variant="outlined" @click="exportDocuments">
              Exporter
            </v-btn>
            <v-btn color="primary" prepend-icon="mdi-plus" variant="flat" @click="openForm()">
              Nouveau document
            </v-btn>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <v-row class="mb-6">
        <v-col
          v-for="stat in statsCards"
          :key="stat.id"
          cols="12"
          lg="4"
          sm="6"
        >
          <v-card class="stat-card" elevation="0" rounded="lg" @click="handleStatClick(stat.id)">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div>
                  <p class="text-caption text-medium-emphasis mb-1">{{ stat.label }}</p>
                  <h2 class="text-h4 font-weight-bold">{{ stat.value }}</h2>
                </div>
                <v-avatar :color="stat.iconBg" size="48">
                  <v-icon :color="stat.iconColor" size="24">{{ stat.icon }}</v-icon>
                </v-avatar>
              </div>
              <div class="d-flex align-center">
                <v-icon class="mr-1" :color="stat.trendColor" size="16">{{ stat.trendIcon }}</v-icon>
                <span class="text-caption" :class="`text-${stat.trendColor}`">{{ stat.trend }}</span>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6" elevation="0" rounded="lg">
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.search"
                clearable
                density="comfortable"
                hide-details
                placeholder="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.processus"
                clearable
                density="comfortable"
                hide-details
                :items="processusOptions"
                label="Processus"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.norms"
                clearable
                density="comfortable"
                hide-details
                :items="normsOptions"
                label="Normes"
                multiple
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.type"
                clearable
                density="comfortable"
                hide-details
                :items="typeOptions"
                label="Type"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.module"
                clearable
                density="comfortable"
                hide-details
                :items="[
                  { title: 'Contexte', value: 'context' },
                  { title: 'Support', value: 'support' },
                  { title: 'Management', value: 'management' },
                  { title: 'Processus', value: 'processes' },
                  { title: 'Amélioration', value: 'improvement' },
                ]"
                item-title="title"
                item-value="value"
                label="Module"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.statut"
                clearable
                density="comfortable"
                hide-details
                :items="statutOptions"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center ga-2" cols="12" md="3">
              <v-btn-toggle v-model="view" divided mandatory variant="outlined">
                <v-btn icon="mdi-view-grid" value="grid" />
                <v-btn icon="mdi-table" value="table" />
                <v-btn icon="mdi-folder-tree" value="modules" />
              </v-btn-toggle>
              <v-spacer />
              <v-chip color="primary" variant="tonal">
                {{ filteredDocuments.length }} documents
              </v-chip>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Grid View -->
      <v-row v-if="view === 'grid'">
        <v-col
          v-for="doc in filteredDocuments"
          :key="doc.id"
          cols="12"
          lg="4"
          md="6"
        >
          <DocumentCard :document="doc" @click="openDetails" />
        </v-col>
      </v-row>

      <!-- Table View -->
      <v-card v-else-if="view === 'table'" elevation="0" rounded="lg">
        <v-data-table
          class="elevation-0"
          :headers="tableHeaders"
          :items="filteredDocuments"
          :items-per-page="10"
        >
          <template #item.code="{ item }">
            <code class="text-caption">{{ item.code }}</code>
            <v-chip
              v-if="item.metadata?.code_generated"
              class="ml-2"
              color="amber"
              size="x-small"
              variant="tonal"
            >
              Code auto
            </v-chip>
          </template>
          <template #item.type="{ item }">
            <TypeBadge :type="item.type" :name="(item as any).type_configuration_name" />
          </template>
          <template #item.etat="{ item }">
            <StateBadge :etat="item.etat" />
          </template>
          <template #item.statut="{ item }">
            <StatusBadge :statut="item.statut" />
          </template>
          <template #item.workflow_status="{ item }">
            <WorkflowStatusBadge :status="item.workflow_status" />
          </template>
          <template #item.actions="{ item }">
            <v-btn
              v-if="getWorkflowPrimaryAction(item)"
              class="mr-1"
              :color="getWorkflowPrimaryAction(item)?.color"
              size="small"
              variant="tonal"
              @click.stop="handleWorkflowPrimaryAction(item)"
            >
              {{ getWorkflowPrimaryAction(item)?.label }}
            </v-btn>
            <v-btn icon="mdi-eye" size="small" variant="text" @click="openDetails(item)" />
          </template>
        </v-data-table>
      </v-card>

      <!-- Modules View -->
      <DocumentsByModule
        v-else-if="view === 'modules'"
        :documents="filteredDocuments"
        :loading="loading"
        @open-details="openDetails"
      />
    </v-container>

    <!-- ── Dialog : Wizard Nomenclatures ── -->
    <v-dialog v-model="showNomenclatures" max-width="1020" persistent scrollable>
      <NomenclatureWizard
        v-if="showNomenclatures"
        :site-id="currentSiteId"
        @close="showNomenclatures = false"
        @saved="onNomenclatureSaved"
      />
    </v-dialog>

    <v-dialog v-model="showDetails" max-width="1100">
      <v-card v-if="selectedDocument" rounded="lg">
        <v-card-title class="pa-4">
          <div class="d-flex align-start justify-space-between flex-wrap ga-3 w-100">
            <div>
              <div class="text-h6 font-weight-bold">{{ DocumentHelpers.getTitle(selectedDocument) }}</div>
              <div class="d-flex align-center flex-wrap ga-2 mt-1">
                <code class="text-caption code-pill">{{ selectedDocument.code || '—' }}</code>
                <v-chip
                  v-if="selectedDocument.metadata?.code_generated"
                  color="amber"
                  size="x-small"
                  variant="tonal"
                >
                  Code auto
                </v-chip>
                <span class="text-caption text-medium-emphasis">v{{ selectedDocument.version }}</span>
              </div>
            </div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <TypeBadge :type="selectedDocument.type" :name="(selectedDocument as any).type_configuration_name" />
              <StateBadge :etat="selectedDocument.etat" />
              <v-chip
                :color="getDocumentBusinessStatusConfig(selectedDocument).color"
                :prepend-icon="getDocumentBusinessStatusConfig(selectedDocument).icon"
                size="x-small"
                variant="tonal"
              >
                {{ getDocumentBusinessStatusLabel(selectedDocument) }}
              </v-chip>
              <v-btn icon="mdi-close" size="small" variant="text" @click="showDetails = false" />
            </div>
          </div>
        </v-card-title>
        <v-divider />

        <!-- Bannière workflow si action requise -->
        <v-alert
          v-if="selectedDocument.workflow_status === 'awaiting_submitter_confirmation'"
          class="ma-4 mb-0"
          density="comfortable"
          icon="mdi-alert-circle"
          type="error"
          variant="tonal"
        >
          <div>
            <strong>Document rejeté :</strong> votre décision est requise pour garder ou libérer le code.
          </div>
          <div v-if="selectedDocument.rejection_reason" class="mt-1">
            <strong>Motif du rejet :</strong> {{ selectedDocument.rejection_reason }}
          </div>
          <template #append>
            <v-btn
              color="error"
              size="small"
              variant="flat"
              @click="openWorkflowPage(selectedDocument)"
            >
              Décider
            </v-btn>
          </template>
        </v-alert>

        <v-alert
          v-else-if="selectedDocument.workflow_status === 'draft'"
          class="ma-4 mb-0"
          density="comfortable"
          icon="mdi-information"
          type="info"
          variant="tonal"
        >
          Ce document est en attente de soumission au workflow.
          <template #append>
            <v-btn
              color="primary"
              size="small"
              variant="flat"
              @click="openWorkflowPage(selectedDocument)"
            >
              Voir le workflow
            </v-btn>
          </template>
        </v-alert>

        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12" md="5">
              <v-card class="detail-card" elevation="0" rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Informations</v-card-title>
                <v-card-text class="pt-0">
                  <div class="detail-row">
                    <span class="label">Processus</span>
                    <span class="value">{{ selectedDocument.processus || '—' }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Site</span>
                    <span class="value">{{ selectedDocument.site?.name || `#${selectedDocument.site_id}` }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Création</span>
                    <span class="value">{{ formatDisplayDate(selectedDocument.date_creation ?? selectedDocument.created_at) }}</span>
                  </div>
                  <div v-if="selectedDocument.prochaine_revision" class="detail-row">
                    <span class="label">Prochaine révision</span>
                    <span class="value">{{ formatDisplayDate(selectedDocument.prochaine_revision) }}</span>
                  </div>
                  <div v-if="selectedDocument.description" class="detail-row">
                    <span class="label">Description</span>
                    <span class="value">{{ selectedDocument.description }}</span>
                  </div>
                  <div v-if="selectedDocument.verified_at" class="detail-row">
                    <span class="label">Vérifié le</span>
                    <span class="value">{{ formatDisplayDate(selectedDocument.verified_at) }}</span>
                  </div>
                  <div v-if="selectedDocument.approved_at" class="detail-row">
                    <span class="label">Approuvé le</span>
                    <span class="value">{{ formatDisplayDate(selectedDocument.approved_at) }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Workflow</span>
                    <v-chip
                      :color="getDocumentBusinessStatusConfig(selectedDocument).color"
                      :prepend-icon="getDocumentBusinessStatusConfig(selectedDocument).icon"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ getDocumentBusinessStatusLabel(selectedDocument) }}
                    </v-chip>
                  </div>
                </v-card-text>
              </v-card>

              <v-card class="detail-card mt-4" elevation="0" rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Historique des versions</v-card-title>
                <v-card-text class="pt-0">
                  <VersionHistory
                    :versions="selectedDocument.versions"
                    @download="downloadVersion"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="7">
              <!-- Mode édition/administration (affiché sur action explicite si l'utilisateur a la permission) -->
              <template v-if="showEditMode && hasDocumentWritePermission">
                <v-card class="detail-card" elevation="0" rounded="lg">
                  <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center justify-space-between">
                    <span>Nouvelle version</span>
                    <v-btn size="small" variant="text" @click="showEditMode = false">
                      Retour à la prévisualisation
                    </v-btn>
                  </v-card-title>
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

                <v-card class="detail-card mt-4" elevation="0" rounded="lg">
                  <v-card-title class="text-subtitle-1 font-weight-bold">Liens & Révisions</v-card-title>
                  <v-card-text class="pt-0">
                    <DocumentLinker :links="selectedDocument.liens_source" @unlink="unlinkDocument" />
                    <ReviewScheduler class="mt-4" :reviews="selectedDocument.reviews" />
                  </v-card-text>
                </v-card>

                <!-- Accès rapide au workflow complet -->
                <v-card class="detail-card mt-4" elevation="0" rounded="lg">
                  <v-card-text class="pa-3">
                    <div class="d-flex align-center justify-space-between">
                      <div class="text-caption text-medium-emphasis">
                        <v-icon size="14">mdi-shield-check-outline</v-icon>
                        Workflow de validation complet
                      </div>
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-open-in-new"
                        size="small"
                        variant="tonal"
                        @click="openWorkflowPage(selectedDocument)"
                      >
                        Ouvrir
                      </v-btn>
                      <v-btn
                        v-if="getWorkflowPrimaryAction(selectedDocument)"
                        :color="getWorkflowPrimaryAction(selectedDocument)?.color"
                        size="small"
                        variant="flat"
                        @click="handleWorkflowPrimaryAction(selectedDocument)"
                      >
                        {{ getWorkflowPrimaryAction(selectedDocument)?.label }}
                      </v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </template>

              <!-- Mode prévisualisation (lecture seule par défaut) -->
              <v-card v-else class="detail-card" elevation="0" rounded="lg" style="height: 100%; min-height: 480px;">
                <v-card-title class="text-subtitle-1 font-weight-bold d-flex align-center justify-space-between flex-wrap ga-2">
                  <span>Prévisualisation</span>
                  <v-btn
                    v-if="hasDocumentWritePermission"
                    color="primary"
                    prepend-icon="mdi-pencil-outline"
                    size="small"
                    variant="flat"
                    @click="showEditMode = true"
                  >
                    Nouvelle version / Modifier
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-0">
                  <DocumentPreview
                    :document="selectedDocument"
                    :document-id="selectedDocument.id"
                    :height="550"
                    @download="downloadVersion(selectedDocument.versions?.[0]?.id || selectedDocument.id, DocumentHelpers.getTitle(selectedDocument))"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ── Dialog : Nouveau / Modifier document ── -->
    <v-dialog v-model="showForm" max-width="820" persistent>
      <DynamicDocumentForm
        v-if="showForm"
        :editing-document="editingDocument"
        :process-code-by-id="processCodeById"
        :process-options="processOptions"
        :site-id="currentSiteId"
        :type-options="typeOptions"
        @cancel="closeForm"
        @saved="onDocumentSaved"
      />
    </v-dialog>

    <!-- ── Dialog : Import ── -->
    <v-dialog v-model="showUploadFlow" max-width="820" persistent>
      <v-card rounded="lg">
        <v-card-title class="pa-4 d-flex align-center justify-space-between">
          <span>Assistant d'importation</span>
          <v-btn icon="mdi-close" size="small" variant="text" @click="showUploadFlow = false" />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <DocumentImportWizard
            v-if="showUploadFlow"
            :site-id="importSiteId"
            @close="showUploadFlow = false"
            @completed="handleImportSuccess"
          />
        </v-card-text>
      </v-card>
    </v-dialog>


    <v-dialog v-model="showExportDialog" max-width="620" persistent>
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Exporter les documents validés</span>
          <v-btn icon="mdi-close" size="small" variant="text" @click="showExportDialog = false" />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-alert class="mb-4" density="compact" type="info" variant="tonal">
            Seuls les documents vérifiés et validés seront exportés.
          </v-alert>
          <v-select
            v-model="exportForm.documentTypeCatalogId"
            class="mb-4"
            item-title="title"
            item-value="value"
            :items="exportDocumentTypeOptions"
            label="Type de document *"
            variant="outlined"
          />
          <v-select
            v-model="exportForm.processId"
            item-title="label"
            item-value="value"
            :items="processOptions"
            label="Processus *"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer />
          <v-btn variant="text" @click="showExportDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            :disabled="!exportForm.documentTypeCatalogId || !exportForm.processId"
            :loading="exportingDocuments"
            @click="runExportDocuments"
          >
            Exporter
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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

    <v-dialog v-model="confirmDialog" max-width="460">
      <v-card rounded="lg">
        <v-card-title class="text-subtitle-1 font-weight-bold">{{ confirmDialogTitle }}</v-card-title>
        <v-card-text class="text-body-2 text-medium-emphasis">
          {{ confirmDialogMessage }}
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer />
          <v-btn :disabled="confirmDialogLoading" variant="text" @click="closeConfirmDialog">Annuler</v-btn>
          <v-btn
            :color="confirmDialogColor"
            :loading="confirmDialogLoading"
            variant="flat"
            @click="runConfirmDialogAction"
          >
            {{ confirmDialogConfirmText }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '../../types/document-unified.types'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import { documentsApi } from '@/api/documents'
  import { processesService } from '@/api/services/processes.service'
  import type { DocumentTypeCatalog } from '../../types/document.types'
  import { useNorms } from '@/composables/useNorms'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import ClientALayout from '../../components/ClientALayout.vue'
  import DocumentCard from '../../components/documents/DocumentCard.vue'
  import DocumentImportWizard from '@/components/documents/DocumentImportWizard.vue'
  import DocumentLinker from '../../components/documents/DocumentLinker.vue'
  import DocumentsByModule from '../../components/documents/DocumentsByModule.vue'
  import DynamicDocumentForm from '../../components/documents/DynamicDocumentForm.vue'
  import NomenclatureWizard from '../../components/documents/NomenclatureWizard.vue'
  import ReviewScheduler from '../../components/documents/ReviewScheduler.vue'
  import StateBadge from '../../components/documents/StateBadge.vue'
  import StatusBadge from '../../components/documents/StatusBadge.vue'
  import TypeBadge from '../../components/documents/TypeBadge.vue'
  import VersionHistory from '../../components/documents/VersionHistory.vue'
  import WorkflowStatusBadge from '../../components/documents/WorkflowStatusBadge.vue'
  import DocumentPreview from '@/components/documents/DocumentPreview.vue'
  import { useDocuments } from '../../composables/useDocuments'
  import { useDocumentTypeCatalogs } from '../../composables/useDocumentTypeCatalogs'
  import { useNomenclatures } from '../../composables/useNomenclatures'
  import { DocumentHelpers } from '../../types/document-unified.types'
  import { getDocumentBusinessStatusConfig, getDocumentBusinessStatusLabel } from '../../utils/documentStatus'

  const authStore = useAuthStore()
  const toast = useToast()

  // ── Computed siteId ──────────────────────────────────────────
  const currentSiteId = computed<number>(() => {
    return Number(authStore.currentSiteId ?? (authStore.user as any)?.site_id ?? 1)
  })
  const importSiteId = computed<number | undefined>(() => {
    const raw = authStore.currentSiteId ?? (authStore.user as any)?.site_id
    const parsed = Number(raw)
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined
  })

  // ── Permissions ───────────────────────────────────────────────
  /**
   * Seuls les admins de l'entreprise (user_type = 'company') ou super_admin
   * peuvent configurer la nomenclature.
   */
  const canConfigureNomenclature = computed(() => {
    const userType = (authStore.user as any)?.user_type
    return userType === 'company' || userType === 'super_admin'
  })

  const {
    documents,
    stats,
    fetchDocuments,
    createDocument,
    fetchStats,
    unlinkDocument: unlinkDoc,
    fetchDocument,
    addVersion,
    loading,
    document: currentDocument,
  } = useDocuments()
  const { nomenclatures, fetchNomenclatures, deleteNomenclature: delNom, createNomenclature, updateNomenclature } = useNomenclatures()
  const {
    items: documentTypeCatalogs,
    loading: docTypesLoading,
    error: docTypeError,
    fetchItems: fetchDocumentTypes,
    createItem: createDocumentType,
    updateItem: updateDocumentType,
    deleteItem: deleteDocumentType,
  } = useDocumentTypeCatalogs()
  const {
    norms,
    normsOptions,
    loading: normsLoading,
    fetchActiveNorms,
  } = useNorms()

  type RawProcess = {
    id?: number | string
    title?: string
    name?: string
    nom?: string
    code?: string
    ref?: string
    attributes?: {
      id?: number | string
      title?: string
      name?: string
      nom?: string
      code?: string
      ref?: string
    }
  }

  type ProcessOption = {
    id: number
    title: string
    code?: string
  }

  const processes = ref<ProcessOption[]>([])

  const view = ref<'grid' | 'table'>('grid')
  const filters = ref({ search: '', processus: '', type: '', statut: '', module: '', norms: [] as (string | number)[] })
  const showForm = ref(false)
  const showDetails = ref(false)
  const showEditMode = ref(false)
  const showNomenclatures = ref(false)
  const showUploadFlow = ref(false)

  const hasDocumentWritePermission = computed(() => {
    const user = authStore.user as any
    if (!user) return false
    if (user.user_type === 'company' || user.user_type === 'super_admin') return true
    const permissions = user.permissions || user.effective_permissions || []
    return permissions.includes('support.documents.update') || permissions.includes('support.documents.create')
  })

  const showExportDialog = ref(false)
  const exportingDocuments = ref(false)
  const docTypeDialog = ref(false)
  const confirmDialog = ref(false)
  const confirmDialogLoading = ref(false)
  const confirmDialogTitle = ref('Confirmation')
  const confirmDialogMessage = ref('')
  const confirmDialogConfirmText = ref('Confirmer')
  const confirmDialogColor = ref<'error' | 'primary'>('error')
  const confirmDialogAction = ref<null | (() => Promise<void>)>(null)
  const editingDocTypeId = ref<number | null>(null)
  const editingDocument = ref<UnifiedDocument | null>(null)
  const selectedDocument = ref<UnifiedDocument | null>(null)
  const addingVersion = ref(false)
  const versionForm = ref({
    version: '',
    modifications: '',
    fichier: null as File | null,
  })
  const codePreview = ref('')
  const codePreviewTemplateId = ref<number | null>(null)
  const codePreviewTemplateVersion = ref<number | null>(null)
  const loadingCodePreview = ref(false)
  const docTypeForm = ref({
    name: '',
    abbreviation: '',
    description: '',
    is_active: true,
    display_order: 0,
  })

  const exportForm = ref({
    documentTypeCatalogId: null as number | null,
    processId: null as number | null,
  })

  const processusOptions = computed(() => {
    return (processes.value || []).map(process => {
      const code = processCodeById.value.get(process.id) || '???'
      const label = `${code} - ${process.title || 'Processus'}`
      return { title: label, value: code }
    })
  })

  const fallbackTypeOptions = [
    { title: 'Politique', value: 'POL' },
    { title: 'Procédure', value: 'PRC' },
    { title: 'Proc. Détaillée', value: 'PRD' },
    { title: 'Formulaire', value: 'FOR' },
    { title: 'Enregistrement', value: 'ENR' },
  ]

  const typeOptions = computed(() => {
    const catalog = (documentTypeCatalogs.value || [])
      .filter(item => item.is_active)
      .toSorted((a, b) => (a.display_order || 0) - (b.display_order || 0))
      .map(item => ({
        title: `${item.name} (${item.abbreviation})`,
        value: item.abbreviation,
      }))

    return catalog.length > 0 ? catalog : fallbackTypeOptions
  })

  const exportDocumentTypeOptions = computed(() => {
    return (documentTypeCatalogs.value || [])
      .filter(item => item.is_active)
      .map(item => ({
        title: `${item.name} (${item.abbreviation})`,
        value: Number(item.id),
      }))
  })

  const statutOptions = [
    { title: 'brouillon en attente de vérification', value: 'brouillon' },
    { title: 'brouillon en cours de vérification', value: 'en_revision' },
    { title: 'validé - version 1', value: 'valide' },
  ]

  const tableHeaders = [
    { title: 'Code', key: 'code', sortable: true },
    { title: 'Nom', key: 'title', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'État', key: 'etat', sortable: true },
    { title: 'Workflow', key: 'workflow_status', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const form = ref({
    process_id: null as number | null,
    processus: '',
    type: typeOptions.value[0]?.value || 'PRC',
    nom: '',
    description: '',
    etat: 'a_etablir',
    date_creation: new Date().toISOString().split('T')[0],
    periodicite_revision: null as number | null,
    fichier: null as File | null,
  })

  const isProcedureType = computed(() => false) // Le type est défini par la nomenclature, pas par une valeur prédéfinie
  const isSelectedProcedure = computed(() => false)
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
    return documents.value.filter(doc => {
      const title = DocumentHelpers.getTitle(doc)
      const code = doc.code || ''
      const statut = DocumentHelpers.getStatut(doc)
      
      // Exclure les documents obsolètes par défaut
      if (statut === 'obsolete' && filters.value.statut !== 'obsolete') return false
      
      if (filters.value.search && !title.toLowerCase().includes(filters.value.search.toLowerCase())
        && !code.toLowerCase().includes(filters.value.search.toLowerCase())) return false
      if (filters.value.processus && doc.processus !== filters.value.processus) return false
      // Filtre par type : comparer l'abréviation de la configuration documentaire
      if (filters.value.type) {
        const abbr = (doc as any).typeConfiguration?.abbreviation
          ?? (doc.metadata as any)?.document_type_abbreviation
          ?? ''
        if (abbr !== filters.value.type) return false
      }
      if (filters.value.statut && statut !== filters.value.statut) return false
      if (filters.value.module && (doc as any).source_module !== filters.value.module) return false
      return true
    })
  })

  const processCodeById = computed(() => {
    const map = new Map<number, string>()
    for (const nom of nomenclatures.value || []) {
      if (nom.process_id && nom.processus) {
        map.set(nom.process_id, nom.processus)
      }
    }
    return map
  })

  const processOptions = computed(() => {
    return (processes.value || []).map(process => {
      const code = processCodeById.value.get(process.id) || ''
      const label = code
        ? `${code} - ${process.title || 'Processus'}`
        : `${process.title || 'Processus'} (code non défini)`
      return { label, value: process.id }
    })
  })

  const processCodeWarning = computed(() => {
    if (!form.value.process_id) return null
    const code = processCodeById.value.get(form.value.process_id)
    if (!code) {
      return 'Aucune codification définie pour ce processus. Configurez la codification dans "Configuration avancée" (Information documentée) pour générer automatiquement les codes.'
    }
    return null
  })

  const statsCards = computed(() => {
    if (!stats.value) return []
    return [
      {
        id: 1,
        label: 'Total Documents',
        value: String(stats.value.total || 0),
        trend: '+12% ce mois',
        trendIcon: 'mdi-trending-up',
        trendColor: 'success',
        icon: 'mdi-file-document-multiple',
        iconBg: 'rgba(91, 141, 217, 0.12)',
        iconColor: '#5b8dd9',
      },
      {
        id: 2,
        label: 'validé - version 1',
        value: String(stats.value.par_statut?.find((s: any) => s.statut === 'valide')?.count || 0),
        trend: 'Conformes',
        trendIcon: 'mdi-check-circle',
        trendColor: 'success',
        icon: 'mdi-check-decagram',
        iconBg: 'rgba(34, 197, 94, 0.12)',
        iconColor: '#22c55e',
      },
      {
        id: 3,
        label: 'brouillon en cours de vérification',
        value: String(stats.value.par_statut?.find((s: any) => s.statut === 'en_revision')?.count || 0),
        trend: 'En cours',
        trendIcon: 'mdi-clock-outline',
        trendColor: 'info',
        icon: 'mdi-file-eye',
        iconBg: 'rgba(59, 130, 246, 0.12)',
        iconColor: '#3b82f6',
      },
      {
        id: 4,
        label: 'À réviser',
        value: String(stats.value.a_reviser || 0),
        trend: stats.value.a_reviser > 0 ? 'Action requise' : 'À jour',
        trendIcon: stats.value.a_reviser > 0 ? 'mdi-alert' : 'mdi-check',
        trendColor: stats.value.a_reviser > 0 ? 'warning' : 'success',
        icon: 'mdi-calendar-alert',
        iconBg: 'rgba(245, 158, 11, 0.12)',
        iconColor: '#f59e0b',
      },
      {
        id: 5,
        label: 'Procédures',
        value: String(stats.value.par_type?.find((t: any) => t.type === 'PRC')?.count || stats.value.par_type?.[0]?.count || 0),
        trend: 'Actives',
        trendIcon: 'mdi-file-tree',
        trendColor: 'primary',
        icon: 'mdi-file-tree',
        iconBg: 'rgba(91, 141, 217, 0.12)',
        iconColor: '#5b8dd9',
      },
      {
        id: 6,
        label: 'Formulaires',
        value: String(stats.value.par_type?.find((t: any) => t.type === 'FOR')?.count || stats.value.par_type?.[1]?.count || 0),
        trend: 'Disponibles',
        trendIcon: 'mdi-form-select',
        trendColor: 'success',
        icon: 'mdi-form-select',
        iconBg: 'rgba(34, 197, 94, 0.12)',
        iconColor: '#22c55e',
      },
    ]
  })

  function openForm (document: UnifiedDocument | null = null) {
    editingDocument.value = document
    showForm.value = true
  }

  function closeForm () {
    showForm.value = false
    editingDocument.value = null
  }

  async function onDocumentSaved (doc: UnifiedDocument) {
    closeForm()
    const siteId = currentSiteId.value
    await fetchDocuments({ site_id: siteId })
    await fetchStats({ site_id: siteId })
  }

  async function onNomenclatureSaved () {
    const siteId = currentSiteId.value
    await fetchNomenclatures({ site_id: siteId })
    await fetchDocumentTypes({ site_id: siteId })
  }

  function openWorkflowPage (doc: UnifiedDocument) {
    // Naviguer vers la page de détail du document qui expose le workflow complet
    window.open(`/company/documents/${doc.id}`, '_blank')
  }

  async function openDetails (doc: UnifiedDocument) {
    await fetchDocument(doc.id)
    selectedDocument.value = currentDocument.value ?? doc
    resetVersionForm()
    showEditMode.value = false
    showDetails.value = true
  }

  async function unlinkDocument (linkId: number) {
    if (!selectedDocument.value) return
    await unlinkDoc(selectedDocument.value.id, linkId)
    await fetchDocument(selectedDocument.value.id)
    selectedDocument.value = currentDocument.value ?? selectedDocument.value
  }

  async function deleteNomenclature (id: number) {
    openConfirmDialog({
      title: 'Supprimer la nomenclature',
      message: 'Cette action est définitive. Voulez-vous continuer ?',
      confirmText: 'Supprimer',
      color: 'error',
      action: async () => {
        await delNom(id)
        const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id
        await fetchNomenclatures({ site_id: siteId })
      },
    })
  }

  async function saveNomenclature (payload: any) {
    try {
      const siteId = currentSiteId.value
      const normalizedPayload = {
        ...payload,
        ...(siteId ? { site_id: siteId } : {}),
      }
      await (payload.id ? updateNomenclature(payload.id, normalizedPayload) : createNomenclature(normalizedPayload))
      await fetchNomenclatures({ site_id: siteId })
      toast.success('Codification enregistrée')
    } catch (error) {
      console.error(error)
      toast.error('Impossible d’enregistrer la codification.')
    }
  }

  function startCreateDocType () {
    editingDocTypeId.value = null
    docTypeForm.value = {
      name: '',
      abbreviation: '',
      description: '',
      is_active: true,
      display_order: 0,
    }
    docTypeDialog.value = true
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
    const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id
    const payload = {
      ...docTypeForm.value,
      abbreviation: String(docTypeForm.value.abbreviation || '').toUpperCase().trim(),
      site_id: siteId || undefined,
    }
    if (!payload.name || !payload.abbreviation) return

    await (editingDocTypeId.value ? updateDocumentType(editingDocTypeId.value, payload) : createDocumentType(payload))
    docTypeDialog.value = false
    await fetchDocumentTypes(siteId ? { site_id: siteId } : undefined)
  }

  async function removeDocType (id: number) {
    openConfirmDialog({
      title: 'Supprimer le type documentaire',
      message: 'Le type documentaire sera supprimé. Voulez-vous continuer ?',
      confirmText: 'Supprimer',
      color: 'error',
      action: async () => {
        const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id
        await deleteDocumentType(id)
        await fetchDocumentTypes(siteId ? { site_id: siteId } : undefined)
      },
    })
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

  function normalizeDocumentTypeOnCatalogChange () {
    if (!showForm.value) return
    const allowed = new Set(typeOptions.value.map(option => option.value))
    if (!allowed.has(form.value.type)) {
      form.value.type = typeOptions.value[0]?.value || 'PRC'
    }
  }

  async function refreshCodePreview () {
    const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id
    if (!siteId || !form.value.type || !form.value.process_id) {
      codePreview.value = ''
      codePreviewTemplateId.value = null
      codePreviewTemplateVersion.value = null
      return
    }

    const processus = processCodeById.value.get(form.value.process_id)
    if (!processus) {
      codePreview.value = ''
      codePreviewTemplateId.value = null
      codePreviewTemplateVersion.value = null
      return
    }

    loadingCodePreview.value = true
    try {
      const response = await documentsApi.previewCode({
        site_id: Number(siteId),
        type: String(form.value.type),
        process_id: Number(form.value.process_id),
        processus,
      })
      const payload = response.data?.data as { code?: string, nomenclature_template_id?: number, nomenclature_template_version?: number } | undefined || {}
      codePreview.value = String(payload.code || '')
      codePreviewTemplateId.value = payload.nomenclature_template_id ?? null
      codePreviewTemplateVersion.value = payload.nomenclature_template_version ?? null
    } catch (error) {
      console.error(error)
      codePreview.value = ''
      codePreviewTemplateId.value = null
      codePreviewTemplateVersion.value = null
    } finally {
      loadingCodePreview.value = false
    }
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
      toast.error('Action impossible pour le moment.')
      confirmDialogLoading.value = false
    }
  }

  function handleStatClick (statId: number) {
    console.log('Stat clicked:', statId)
  }

  async function handleUploadFlowSuccess () {
    showUploadFlow.value = false
    const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id
    await fetchDocuments({ site_id: siteId })
    await fetchStats({ site_id: siteId })
  }

  async function handleImportSuccess () {
    showUploadFlow.value = false
    toast.success('Document importé avec succès.')
    const siteId = currentSiteId.value
    await fetchDocuments({ site_id: siteId })
    await fetchStats({ site_id: siteId })
  }

  async function exportDocuments () {
    showExportDialog.value = true
  }

  function openExportDialogForDocument (doc: UnifiedDocument) {
    const processId = Number((doc as any).process_id || 0)
    const typeAbbr = String((doc as any).typeConfiguration?.abbreviation || '')
    const catalog = (documentTypeCatalogs.value || []).find(item => item.abbreviation === typeAbbr)
    exportForm.value.documentTypeCatalogId = catalog ? Number(catalog.id) : null
    exportForm.value.processId = processId > 0 ? processId : null
    showExportDialog.value = true
  }

  function getWorkflowPrimaryAction (doc: UnifiedDocument | null): null | { key: 'verify' | 'validate' | 'export', label: string, color: string } {
    if (!doc) return null
    const workflowStatus = String((doc as any).workflow_status || '')
    if (!workflowStatus || workflowStatus === 'draft') {
      return { key: 'verify', label: 'Vérifier', color: 'primary' }
    }
    if (workflowStatus === 'pending_verification' || workflowStatus === 'pending_approval') {
      return { key: 'validate', label: 'Valider', color: 'warning' }
    }
    if (workflowStatus === 'approved') {
      return { key: 'export', label: 'Exporter', color: 'success' }
    }
    return null
  }

  async function handleWorkflowPrimaryAction (doc: UnifiedDocument) {
    const action = getWorkflowPrimaryAction(doc)
    if (!action) return
    if (action.key === 'verify') {
      try {
        const { getAxiosApiClient } = await import('@/api/client')
        await getAxiosApiClient().post(`/documents/${doc.id}/submit-for-approval`)
        toast.success('Document envoyé à la vérification.')
        await fetchDocuments({ site_id: currentSiteId.value })
      } catch (err: any) {
        toast.error(err?.response?.data?.message || 'Soumission impossible.')
      }
      return
    }
    if (action.key === 'validate') {
      openWorkflowPage(doc)
      return
    }
    if (action.key === 'export') {
      openExportDialogForDocument(doc)
    }
  }

  async function runExportDocuments () {
    try {
      if (!exportForm.value.documentTypeCatalogId || !exportForm.value.processId) {
        toast.error('Type et processus sont obligatoires pour l’export.')
        return
      }
      exportingDocuments.value = true
      const siteId = currentSiteId.value
      // Import direct de l'instance Axios configurée avec les bons headers auth
      const { getAxiosApiClient } = await import('@/api/client')
      const response = await getAxiosApiClient().get('/documents-inventory/export', {
        params: {
          site_id: siteId,
          document_type_catalog_id: exportForm.value.documentTypeCatalogId,
          process_id: exportForm.value.processId,
        },
        responseType: 'blob',
      })
      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      const url = window.URL.createObjectURL(blob)
      const a = window.document.createElement('a')
      a.href = url
      a.download = `inventaire_documentaire_${new Date().toISOString().split('T')[0]}.xlsx`
      window.document.body.appendChild(a)
      a.click()
      window.document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
      toast.success('Export téléchargé avec succès.')
      showExportDialog.value = false
    } catch (err: any) {
      console.error('[Export] Erreur:', err)
      toast.error(err?.response?.data?.message || 'Export impossible. Vérifiez vos permissions.')
    } finally {
      exportingDocuments.value = false
    }
  }

  async function saveDocument () {
    const siteId = authStore.currentSiteId ?? (authStore.user as { site_id?: number } | null)?.site_id ?? 1
    if (!form.value.process_id) {
      toast.error('Le processus est obligatoire.')
      return
    }
    const processCode = processCodeById.value.get(form.value.process_id)
    if (!processCode) {
      toast.error('Aucune codification définie pour ce processus.')
      return
    }
    if (isProcedureType.value && !form.value.fichier) {
      toast.error('Le fichier est obligatoire pour l’ajout d’une procédure.')
      return
    }
    if (!codePreview.value) {
      await refreshCodePreview()
    }
    const formData = new FormData()
    formData.append('site_id', String(siteId))
    formData.append('process_id', String(form.value.process_id))
    formData.append('processus', processCode)
    for (const [key, value] of Object.entries(form.value)) {
      if (value !== null && key !== 'fichier' && key !== 'process_id' && key !== 'processus') {
        formData.append(key, String(value))
      }
    }
    if (form.value.fichier) {
      formData.append('fichier', form.value.fichier)
    }

    try {
      await createDocument(formData)
      toast.success('Document enregistré avec succès.')
      closeForm()
      await fetchDocuments({ site_id: siteId })
    } catch (error) {
      console.error(error)
      toast.error('Impossible d’enregistrer le document.')
    }
  }

  function handleVersionFileChange (e: Event) {
    const target = e.target as HTMLInputElement
    versionForm.value.fichier = target.files?.[0] || null
  }

  function formatDisplayDate (date?: string) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function resetVersionForm () {
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
      await addVersion(selectedDocument.value.id, formData)
      await fetchDocument(selectedDocument.value.id)
      selectedDocument.value = currentDocument.value ?? selectedDocument.value
      resetVersionForm()
      toast.success('Version ajoutée avec succès.')
    } catch (error) {
      console.error(error)
      toast.error('Impossible d’ajouter la version.')
    } finally {
      addingVersion.value = false
    }
  }

  async function downloadVersion (versionId: number, filename: string) {
    if (!selectedDocument.value) return
    try {
      const safeFilename = filename
        ? filename.split('/').pop()
        : undefined
      await documentsApi.downloadVersion(selectedDocument.value.id, versionId, safeFilename)
    } catch (error) {
      console.error(error)
      toast.error('Téléchargement impossible.')
    }
  }

  function normalizeProcess (process: RawProcess): ProcessOption | null {
    const idRaw = process?.id ?? process?.attributes?.id
    const id = Number(idRaw)
    if (!Number.isFinite(id) || id <= 0) return null

    const title = String(
      process?.title
        || process?.name
      || process?.nom
        || process?.attributes?.title
      || process?.attributes?.name
        || process?.attributes?.nom
      || '',
    ).trim()

    const code = String(
      process?.code
        || process?.ref
      || process?.attributes?.code
        || process?.attributes?.ref
      || '',
    ).trim() || undefined

    return {
      id,
      title: title || `Processus #${id}`,
      code,
    }
  }

  async function loadProcessesForInventory (siteId?: number | null) {
    const normalizedSiteId = Number(siteId)
    const hasSiteId = Number.isFinite(normalizedSiteId) && normalizedSiteId > 0

    const processResponse = await processesService.getProcesses({
      per_page: 200,
      ...(hasSiteId ? { site_id: normalizedSiteId } : {}),
    })

    let raw = Array.isArray(processResponse.data) ? processResponse.data : []

    if (raw.length === 0) {
      const fallbackResponse = await processesService.getProcesses({ per_page: 200 })
      raw = Array.isArray(fallbackResponse.data) ? fallbackResponse.data : []
    }

    const baseList = raw
      .map((item: RawProcess) => normalizeProcess(item))
      .filter((item: ProcessOption | null): item is ProcessOption => item !== null)

    const seen = new Set(baseList.map(item => item.id))

    if (hasSiteId) {
      try {
        const scopeResponse = await api.get('/application-scopes', {
          params: { site_id: normalizedSiteId, per_page: 1 },
        })
        const scopeData = scopeResponse?.data?.data
        const scope = Array.isArray(scopeData) ? scopeData[0] : scopeData
        const scopeProcesses = Array.isArray(scope?.processes) ? scope.processes : []

        for (const process of scopeProcesses) {
          const normalized = normalizeProcess(process as RawProcess)
          if (!normalized || seen.has(normalized.id)) continue
          baseList.push(normalized)
          seen.add(normalized.id)
        }
      } catch (error) {
        console.warn('[DocumentInventory] Chargement application-scope impossible:', error)
      }
    }

    processes.value = baseList
  }

  onMounted(async () => {
    const siteId = currentSiteId.value
    await fetchDocuments({ site_id: siteId })
    await fetchStats({ site_id: siteId })
    await fetchNomenclatures({ site_id: siteId })
    await fetchDocumentTypes({ site_id: siteId })
    await fetchActiveNorms()
    try {
      await loadProcessesForInventory(siteId)
    } catch (error) {
      console.error(error)
    }

    normalizeDocumentTypeOnCatalogChange()
  })

  watch(typeOptions, () => {
    normalizeDocumentTypeOnCatalogChange()
  })

  watch(
    () => [form.value.process_id, form.value.type, nomenclatures.value.length],
    () => {
      if (!showForm.value) return
      void refreshCodePreview()
    },
  )
</script>

<style scoped>
.documents-container {
  background: #f8fafc;
  min-height: 100vh;
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
}

.hero-section {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

:deep(.v-card) {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.v-card:hover) {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

:deep(.v-btn) {
  transition: all 0.2s ease;
  text-transform: none;
  font-weight: 500;
}

:deep(.v-btn:active) {
  transform: scale(0.95);
}

.stat-card {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-card:active {
  transform: translateY(-2px) scale(0.98);
}

.detail-card {
  border: 1px solid #e2e8f0;
  background: #ffffff;
}

.detail-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px dashed #e2e8f0;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row .label {
  font-size: 0.8rem;
  color: #64748b;
  min-width: 120px;
}

.detail-row .value {
  font-size: 0.9rem;
  color: #0f172a;
  text-align: right;
  word-break: break-word;
}

.code-pill {
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 999px;
}

.nomenclature-modal {
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
}

.nomenclature-modal-header {
  background:
    radial-gradient(130% 180% at 0% -10%, rgba(14, 116, 144, 0.14), transparent 60%),
    radial-gradient(120% 160% at 100% 0%, rgba(59, 130, 246, 0.12), transparent 55%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
}

@media (max-width: 960px) {
  .documents-container {
    padding: 18px;
  }
}

@media (max-width: 600px) {
  .documents-container {
    padding: 12px;
  }

  .hero-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .hero-actions :deep(.v-btn) {
    flex: 1 1 180px;
  }
}
</style>
