<template>
  <LeadershipLayout>
    <HeroProgressRow>
      <HeroCard
        :badges="[
          {
            icon: 'mdi-file-check',
            text: `${jobDescriptions.length} fiches`,
            variant: 'primary',
          },
          { icon: 'mdi-draw', text: 'Signature requise', variant: 'secondary' },
        ]"
        icon="mdi-file-document-edit"
        icon-color="primary"
        subtitle="Définissez les fiches de poste avec signature des collaborateurs"
        title="Fiche de poste"
      >
        <template #actions>
          <v-btn
            class="mr-2"
            color="secondary"
            prepend-icon="mdi-upload"
            variant="outlined"
            @click="handleImportClick"
          >
            Importer
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            variant="flat"
            @click="createNew"
          >
            Nouvelle fiche
          </v-btn>
        </template>
      </HeroCard>

      <ProgressCard
        :completion="completion"
        label="Complétude"
        :last-saved="lastSavedLabel"
        :value="`${filledCount}/15 champs`"
      />
    </HeroProgressRow>

    <TabsContainer v-model="activeTab" :tabs="tabs" />

    <transition mode="out-in" name="fade-slide">
      <GlassCard v-if="activeTab === 'form'" key="form">
        <h2 class="section-title">
          <span class="section-number">01</span>
          Informations de la fiche
        </h2>

        <v-expansion-panels
          v-model="openPanels"
          class="pro-accordion"
          variant="accordion"
        >
          <v-expansion-panel elevation="0">
            <v-expansion-panel-title>
              <div class="panel-title">
                Informations du poste
                <span class="panel-subtitle">Champs essentiels</span>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div class="form-grid">
                <div class="form-field">
                  <label class="field-label">Collaborateur</label>
                  <select v-model="form.id_collaborateur" class="field-input">
                    <option value="">Sélectionner un collaborateur</option>
                    <option
                      v-for="collab in collaborateurs"
                      :key="collab.id"
                      :value="collab.id"
                    >
                      {{ collab.full_name }} -
                      {{ collab.site_name || "Site non affecté" }}
                    </option>
                  </select>
                  <div class="field-help">
                    Optionnel — pré-remplit l’intitulé du poste
                  </div>
                </div>

                <div class="form-field">
                  <label class="field-label required">Intitulé du poste</label>
                  <input
                    v-model="form.titre_du_poste"
                    class="field-input"
                    placeholder="Ex: Responsable Qualité"
                  >
                </div>

                <div class="form-field full-width">
                  <label class="field-label required">Mission principale</label>
                  <textarea
                    v-model="form.mission"
                    class="field-textarea"
                    placeholder="Décrivez la mission..."
                    rows="3"
                  />
                </div>

                <div class="form-field">
                  <label class="field-label">Département</label>
                  <input
                    v-model="form.departement"
                    class="field-input"
                    placeholder="Ex: QHSE, RH, IT"
                  >
                </div>

                <div class="form-field">
                  <label class="field-label">Supérieur hiérarchique</label>
                  <select
                    v-model="form.superieur_hierarchique_id"
                    class="field-input"
                  >
                    <option value="">Sélectionner un collaborateur</option>
                    <option
                      v-for="collab in collaborateurs"
                      :key="collab.id"
                      :value="collab.id"
                    >
                      {{ collab.full_name }} -
                      {{ collab.site_name || "Site non affecté" }}
                    </option>
                  </select>
                </div>

                <div class="form-field">
                  <label class="field-label">Poste du remplaçant</label>
                  <input
                    v-model="form.titre_de_remplacement"
                    class="field-input"
                    placeholder="Ex: Adjoint Qualité"
                  >
                </div>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel elevation="0">
            <v-expansion-panel-title>
              <div class="panel-title">
                Activités
                <span class="panel-subtitle">Détails opérationnels</span>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div class="form-grid">
                <div class="form-field full-width">
                  <label class="field-label">Activités (description globale)</label>
                  <textarea
                    v-model="form.activites"
                    class="field-textarea"
                    placeholder="Décrivez les activités principales du poste..."
                    rows="3"
                  />
                </div>

                <div class="form-field">
                  <label class="field-label">Activités principales</label>
                  <textarea
                    v-model="form.activites_principales"
                    class="field-textarea"
                    placeholder="Ex: Suivi audits; Gestion non-conformités"
                    rows="3"
                  />
                  <div class="field-help">Séparer par “;”</div>
                </div>

                <div class="form-field">
                  <label class="field-label">Activités secondaires</label>
                  <textarea
                    v-model="form.activites_secondaires"
                    class="field-textarea"
                    placeholder="Ex: Formation; Veille réglementaire"
                    rows="3"
                  />
                  <div class="field-help">Séparer par “;”</div>
                </div>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel elevation="0">
            <v-expansion-panel-title>
              <div class="panel-title">
                Conditions de travail
                <span class="panel-subtitle">Organisation & contraintes</span>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div class="form-grid">
                <div class="form-field">
                  <label class="field-label">Lieu de travail</label>
                  <input
                    v-model="form.lieu_de_travail"
                    class="field-input"
                    placeholder="Ex: Siège, Site Nord, Télétravail"
                  >
                </div>

                <div class="form-field">
                  <label class="field-label">Horaire de travail</label>
                  <input
                    v-model="form.horaire_de_travail"
                    class="field-input"
                    placeholder="Ex: 8h–17h, Temps partiel"
                  >
                </div>

                <div class="form-field">
                  <label class="field-label">Déplacements requis</label>
                  <v-btn-toggle
                    v-model="form.deplacement_requis"
                    class="toggle-group"
                    divided
                    :mandatory="false"
                  >
                    <v-btn value="oui" variant="outlined">Oui</v-btn>
                    <v-btn value="non" variant="outlined">Non</v-btn>
                  </v-btn-toggle>
                  <div class="field-help">
                    Optionnel — sélectionner si applicable
                  </div>
                </div>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel elevation="0">
            <v-expansion-panel-title>
              <div class="panel-title">
                Compétences & Profil
                <span class="panel-subtitle">Exigences du poste</span>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div class="form-grid">
                <div class="form-field full-width">
                  <label class="field-label">Compétences requises</label>
                  <textarea
                    v-model="form.competences_requises"
                    class="field-textarea"
                    placeholder="Ex: ISO 9001; Audit interne; Excel"
                    rows="3"
                  />
                  <div class="field-help">Séparer par “;”</div>
                </div>

                <div class="form-field">
                  <label class="field-label">Expérience requise</label>
                  <input
                    v-model="form.experience_requise"
                    class="field-input"
                    placeholder="Ex: 3 ans minimum"
                  >
                </div>

                <div class="form-field">
                  <label class="field-label">Formation requise</label>
                  <input
                    v-model="form.formation_requise"
                    class="field-input"
                    placeholder="Ex: Bac+5 QHSE"
                  >
                </div>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <div class="signature-section">
          <h3 class="signature-title">
            <v-icon color="#5b8dd9" size="20">mdi-draw</v-icon>
            Signature du collaborateur
          </h3>
          <p class="signature-info">
            La signature est automatiquement récupérée depuis le profil du
            collaborateur sélectionné.
          </p>

          <div v-if="form.signature" class="approval-section">
            <img
              alt="Signature collaborateur"
              class="signature-preview-image"
              :src="form.signature"
            >
          </div>

          <div v-if="form.signature" class="approval-section">
            <v-icon color="#22c55e" size="24">mdi-check-decagram</v-icon>
            <div class="approval-text">
              <div class="approval-title">Lu et approuvé</div>
              <div class="approval-date">
                Le {{ new Date().toLocaleDateString("fr-FR") }}
              </div>
            </div>
          </div>
          <div v-else class="signature-missing">
            Aucune signature disponible pour ce collaborateur.
          </div>
        </div>

        <ActionButtons
          :primary-disabled="!isFormValid"
          primary-icon="mdi-content-save"
          primary-label="Enregistrer la fiche"
          secondary-icon="mdi-refresh"
          secondary-label="Réinitialiser"
          @primary="saveJobDescription"
          @secondary="resetForm"
        />
      </GlassCard>

      <GlassCard v-else key="list">
        <h2 class="section-title">
          <span class="section-number">02</span>
          Fiches de poste créées
        </h2>

        <v-card class="mb-4" rounded="xl" variant="tonal">
          <v-card-text class="pa-4">
            <v-row dense>
              <v-col cols="12" md="7">
                <v-text-field
                  v-model="listFilters.search"
                  clearable
                  hide-details
                  label="Rechercher une fiche"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="listFilters.signature"
                  hide-details
                  :items="signatureFilterOptions"
                  label="Statut signature"
                  variant="outlined"
                />
              </v-col>
              <v-col class="d-flex align-center justify-end" cols="12" md="2">
                <v-btn
                  prepend-icon="mdi-refresh"
                  variant="outlined"
                  @click="resetListFilters"
                >
                  Réinitialiser
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <div v-if="filteredJobDescriptions.length > 0" class="job-list">
          <div
            v-for="job in filteredJobDescriptions"
            :key="job.id"
            class="job-card"
          >
            <div class="job-header">
              <div class="job-icon">
                <v-icon color="white" size="24">mdi-file-document</v-icon>
              </div>
              <div class="job-info">
                <div class="job-title">{{ job.intitule }}</div>
                <div class="job-collab">{{ job.collaborateur }}</div>
              </div>
              <div class="job-status">
                <v-icon
                  v-if="job.signature"
                  color="#22c55e"
                  size="20"
                >mdi-check-circle</v-icon>
                <v-icon
                  v-else
                  color="#f59e0b"
                  size="20"
                >mdi-clock-outline</v-icon>
              </div>
            </div>
            <div class="job-actions">
              <v-btn
                color="primary"
                prepend-icon="mdi-eye"
                size="small"
                variant="outlined"
                @click="viewJob(job)"
              >
                Voir
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-download"
                size="small"
                variant="flat"
                @click="downloadJobPDF(job.id)"
              >
                PDF
              </v-btn>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <v-icon color="#cbd5e1" size="64">mdi-file-document-outline</v-icon>
          <div class="empty-title">Aucune fiche de poste</div>
          <div class="empty-subtitle">Créez votre première fiche de poste</div>
        </div>
      </GlassCard>
    </transition>

    <!-- Onglet Historique imports -->
    <transition mode="out-in" name="fade-slide">
      <GlassCard v-if="activeTab === 'imports'" key="imports">
        <h2 class="section-title">
          <span class="section-number">03</span>
          Historique des imports
        </h2>

        <ImportLogsTable
          ref="importLogsTableRef"
          @view-preview="
            (importId) => {
              currentImportId = importId;
              showImportPreview = true;
            }
          "
        />
      </GlassCard>
    </transition>

    <!-- Dialog pour visualiser une fiche -->
    <v-dialog v-model="showViewDialog" max-width="800">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Fiche de poste - {{ selectedJob?.job_title }}</span>
          <v-btn icon @click="showViewDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text
          v-if="selectedJob"
          style="max-height: 70vh; overflow-y: auto"
        >
          <v-progress-linear
            v-if="previewLoading"
            class="mb-4"
            color="primary"
            indeterminate
          />

          <iframe
            v-if="previewHtml"
            class="job-preview-frame"
            :srcdoc="previewHtml"
            title="Aperçu fiche de poste"
          />

          <div v-else class="job-preview">
            <div class="preview-section">
              <h3>Intitulé du poste</h3>
              <p>{{ selectedJob.job_title }}</p>
            </div>
            <div
              v-if="selectedJob.replacement_job_title"
              class="preview-section"
            >
              <h3>Poste du remplaçant (absence)</h3>
              <p>{{ selectedJob.replacement_job_title }}</p>
            </div>
            <div class="preview-section">
              <h3>Mission principale</h3>
              <p>{{ selectedJob.mission }}</p>
            </div>
            <div v-if="selectedJob.activities" class="preview-section">
              <h3>Activités principales</h3>
              <p>{{ selectedJob.activities }}</p>
            </div>
            <div
              v-if="
                selectedJob.required_skills &&
                  selectedJob.required_skills.length > 0
              "
              class="preview-section"
            >
              <h3>Compétences requises</h3>
              <ul>
                <li
                  v-for="(skill, idx) in selectedJob.required_skills"
                  :key="idx"
                >
                  {{ skill }}
                </li>
              </ul>
            </div>
            <div v-if="selectedJob.required_education" class="preview-section">
              <h3>Niveau d'études</h3>
              <p>{{ selectedJob.required_education }}</p>
            </div>
            <div v-if="selectedJob.required_experience" class="preview-section">
              <h3>Expérience requise</h3>
              <p>{{ selectedJob.required_experience }}</p>
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showViewDialog = false"
          >Fermer</v-btn>
          <v-btn
            color="secondary"
            :loading="exportingJob"
            prepend-icon="mdi-eye"
            variant="tonal"
            @click="jobDocumentId ? handlePreviewJob() : openJobConfig('preview')"
          >
            Prévisualiser
          </v-btn>
          <v-btn
            color="primary"
            :loading="exportingJob"
            prepend-icon="mdi-download"
            variant="flat"
            @click="jobDocumentId ? handleDownloadJob() : openJobConfig('download')"
          >
            Télécharger PDF
          </v-btn>
          <v-btn
            color="warning"
            :disabled="submittingForVerification || verificationSent"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="jobDocumentId ? openJobVerifyDialog() : openJobConfig('verify')"
          >
            Vérifier document
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Import Dialogs -->
    <ImportJobDescriptionsDialog
      v-model="showImportDialog"
      @uploaded="handleImportUploaded"
    />

    <ImportPreviewDialog
      v-model="showImportPreview"
      :import-id="currentImportId"
      @confirmed="handleImportConfirmed"
    />

    <!-- Dialog config génération document -->
    <GeneratedDocumentConfigDialog
      v-model="jobGenerationDialog"
      :site-id="jobSiteId"
      title="Paramètres de la fiche de poste"
      @confirm="onJobGenerationConfirm"
    />

    <!-- Dialog vérification -->
    <v-dialog v-model="jobVerifyDialog" max-width="560">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Envoyer en vérification</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal">Code : {{ jobDocumentCode || '—' }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="jobVerifyDialog = false">Annuler</v-btn>
          <v-btn color="warning" :loading="submittingForVerification" @click="submitJobForVerification">
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog prévisualisation PDF -->
    <v-dialog v-model="jobPreviewDialog" fullscreen>
      <v-card>
        <v-toolbar color="primary" density="compact">
          <v-toolbar-title>Prévisualisation — {{ jobPreviewFilename }}</v-toolbar-title>
          <v-spacer />
          <v-btn icon="mdi-close" @click="closeJobPreview" />
        </v-toolbar>
        <iframe v-if="jobPreviewBlobUrl" :src="jobPreviewBlobUrl" style="width:100%; height:calc(100vh - 48px); border:none;" />
      </v-card>
    </v-dialog>
  </LeadershipLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ActionButtons from '@/components/leadership/ActionButtons.vue'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import HeroCard from '@/components/leadership/HeroCard.vue'
  import HeroProgressRow from '@/components/leadership/HeroProgressRow.vue'
  import LeadershipLayout from '@/components/leadership/LeadershipLayout.vue'
  import ProgressCard from '@/components/leadership/ProgressCard.vue'
  import TabsContainer from '@/components/leadership/TabsContainer.vue'
  import { leadershipService } from '@/services/leadershipService'
  import { pdfExportService } from '@/services/pdfExportService'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import ImportJobDescriptionsDialog from './components/ImportJobDescriptionsDialog.vue'
  import ImportLogsTable from './components/ImportLogsTable.vue'
  import ImportPreviewDialog from './components/ImportPreviewDialog.vue'

  const form = ref({
    titre_du_poste: '',
    mission: '',
    id_collaborateur: '',
    departement: '',
    superieur_hierarchique_id: '',
    titre_de_remplacement: '',
    activites: '',
    activites_principales: '',
    activites_secondaires: '',
    lieu_de_travail: '',
    horaire_de_travail: '',
    deplacement_requis: '',
    competences_requises: '',
    experience_requise: '',
    formation_requise: '',
    signature: '',
  })

  const jobDescriptions = ref<any[]>([])
  const activeTab = ref('form')
  const openPanels = ref(0)
  const lastSavedAt = ref<Date | null>(null)
  const loading = ref(false)
  const listFilters = ref({
    search: '',
    signature: 'all',
  })
  const showViewDialog = ref(false)
  const selectedJob = ref<any>(null)
  const previewLoading = ref(false)
  const previewHtml = ref('')

  const toast = useToast()
  const {
    exporting: exportingJob,
    submittingForVerification,
    verificationSent,
    lastExportedDocumentId: jobDocumentId,
    exportedDocumentCode: jobDocumentCode,
    previewDialog: jobPreviewDialog,
    previewBlobUrl: jobPreviewBlobUrl,
    previewFilename: jobPreviewFilename,
    verifyDialog: jobVerifyDialog,
    handlePreviewDraft: handlePreviewJob,
    closePreview: closeJobPreview,
    handleDownloadDraft: handleDownloadJob,
    openVerifyDialog: openJobVerifyDialog,
    submitForVerification: submitJobForVerification,
    resetDraft: resetJobDraft,
  } = useDocumentFlow(
    async () => {
      const jobId = Number(selectedJob.value?.id || 0)
      if (!jobId) { toast.error('Aucune fiche sélectionnée.'); return null }
      const response = await api.post(`/job-descriptions/${jobId}/generate-draft`, {
        document_type_catalog_id: jobGenerationCatalogId.value,
        process_id: jobGenerationProcessId.value,
      })
      const data = response.data?.data ?? response.data
      const attrs = data?.attributes ?? data
      const id = Number(data?.id || attrs?.id || 0) || null
      const code = String(attrs?.code || '')
      return id ? { id, code } : null
    },
    () => `Fiche_Poste_${selectedJob.value?.job_title || 'document'}_${new Date().toISOString().split('T')[0]}.pdf`,
  )

  const jobGenerationCatalogId = ref<number | null>(null)
  const jobGenerationProcessId = ref<number | null>(null)
  const jobGenerationDialog = ref(false)
  const jobPendingAction = ref<'download' | 'preview' | 'verify'>('download')

  const authStore = useAuthStore()
  const jobSiteId = computed(() => authStore.currentSite?.id ?? null)

  function onJobGenerationConfirm (ctx: { document_type_catalog_id: number, process_id?: number | null }) {
    jobGenerationCatalogId.value = ctx.document_type_catalog_id
    jobGenerationProcessId.value = ctx.process_id ?? null
    if (jobPendingAction.value === 'preview') handlePreviewJob()
    else if (jobPendingAction.value === 'verify') openJobVerifyDialog()
    else handleDownloadJob()
  }

  function openJobConfig (action: 'download' | 'preview' | 'verify') {
    jobPendingAction.value = action
    jobGenerationDialog.value = true
  }

  watch(selectedJob, () => resetJobDraft())

  // Import functionality refs
  const showImportDialog = ref(false)
  const showImportPreview = ref(false)
  const currentImportId = ref<number | null>(null)
  const importLogsTableRef = ref<InstanceType<typeof ImportLogsTable> | null>(
    null,
  )

  // Handler for import button click (with debug)
  function handleImportClick () {
    console.log('🔘 Import button clicked')
    console.log('📋 Before: showImportDialog =', showImportDialog.value)
    showImportDialog.value = true
    console.log('📋 After: showImportDialog =', showImportDialog.value)
  }

  const collaborateurs = ref<any[]>([])

  const tabs = [
    { value: 'form', label: 'Créer une fiche', icon: 'mdi-file-document-edit' },
    { value: 'list', label: 'Fiches créées', icon: 'mdi-format-list-bulleted' },
    {
      value: 'imports',
      label: 'Historique imports',
      icon: 'mdi-upload-multiple',
    },
  ]

  const selectedCollaborator = computed(() => {
    const selectedId = Number(form.value.id_collaborateur || 0)
    if (!Number.isFinite(selectedId) || selectedId <= 0) {
      return null
    }
    return (
      collaborateurs.value.find(collab => Number(collab.id) === selectedId)
      || null
    )
  })

  function pickJobField (
    attributes: any,
    candidate: any,
    key: string,
    fallback = '',
  ) {
    return attributes?.[key] ?? candidate?.[key] ?? fallback
  }

  function normalizeRequiredSkills (requiredSkills: any) {
    if (Array.isArray(requiredSkills)) {
      return requiredSkills
    }
    return requiredSkills ? [String(requiredSkills)] : []
  }

  function resolveCollaboratorLabel (userAttributes: any) {
    const firstName = userAttributes?.first_name || ''
    const lastName = userAttributes?.last_name || ''
    const fullName = `${firstName} ${lastName}`.trim()
    return (userAttributes?.name ?? fullName) || 'Collaborateur non défini'
  }

  function normalizeJobDescription (raw: any) {
    const candidate = raw?.data ?? raw
    const attributes = candidate?.attributes ?? {}
    const relationships = candidate?.relationships ?? {}
    const userAttributes = relationships?.user?.attributes ?? {}

    const id = Number(candidate?.id)
    const normalizedSkills = normalizeRequiredSkills(attributes?.required_skills)
    const normalizedMainActivities = normalizeRequiredSkills(
      attributes?.main_activities,
    )
    const normalizedSecondaryActivities = normalizeRequiredSkills(
      attributes?.secondary_activities,
    )

    return {
      id: Number.isFinite(id) && id > 0 ? id : undefined,
      job_title: pickJobField(attributes, candidate, 'job_title'),
      intitule: pickJobField(attributes, candidate, 'job_title'),
      replacement_job_title: pickJobField(
        attributes,
        candidate,
        'replacement_job_title',
      ),
      mission: pickJobField(attributes, candidate, 'mission'),
      activities: pickJobField(attributes, candidate, 'activities'),
      required_skills: normalizedSkills,
      required_education: pickJobField(
        attributes,
        candidate,
        'required_education',
      ),
      required_experience: pickJobField(
        attributes,
        candidate,
        'required_experience',
      ),
      department: pickJobField(attributes, candidate, 'department'),
      reports_to_id: pickJobField(attributes, candidate, 'reports_to_id', null),
      main_activities: normalizedMainActivities,
      secondary_activities: normalizedSecondaryActivities,
      work_location: pickJobField(attributes, candidate, 'work_location'),
      work_schedule: pickJobField(attributes, candidate, 'work_schedule'),
      travel_required: pickJobField(attributes, candidate, 'travel_required', null),
      collaborateur: resolveCollaboratorLabel(userAttributes),
      signature: pickJobField(attributes, candidate, 'signature', null),
    }
  }

  function resolveCollaboratorNameById (id: number | string | null | undefined) {
    const numericId = Number(id || 0)
    if (!Number.isFinite(numericId) || numericId <= 0) {
      return ''
    }
    const match = collaborateurs.value.find(
      collab => Number(collab.id) === numericId,
    )
    return match?.full_name || ''
  }

  const isFormValid = computed(
    () =>
      form.value.titre_du_poste.trim()
      && form.value.mission.trim(),
  )

  const filledCount = computed(() => {
    let count = 0
    if (form.value.titre_du_poste.trim()) count++
    if (form.value.mission.trim()) count++
    if (form.value.id_collaborateur) count++
    if (form.value.departement.trim()) count++
    if (form.value.superieur_hierarchique_id) count++
    if (form.value.titre_de_remplacement.trim()) count++
    if (form.value.activites.trim()) count++
    if (form.value.activites_principales.trim()) count++
    if (form.value.activites_secondaires.trim()) count++
    if (form.value.lieu_de_travail.trim()) count++
    if (form.value.horaire_de_travail.trim()) count++
    if (form.value.deplacement_requis) count++
    if (form.value.competences_requises.trim()) count++
    if (form.value.experience_requise.trim()) count++
    if (form.value.formation_requise.trim()) count++
    return count
  })

  const completion = computed(() => Math.round((filledCount.value / 15) * 100))
  const lastSavedLabel = computed(
    () => lastSavedAt.value?.toLocaleString() || 'Jamais',
  )
  const signatureFilterOptions = [
    { title: 'Toutes', value: 'all' },
    { title: 'Signées', value: 'signed' },
    { title: 'En attente', value: 'pending' },
  ]
  const filteredJobDescriptions = computed(() => {
    const query = listFilters.value.search.trim().toLowerCase()

    return jobDescriptions.value.filter(job => {
      const matchesSearch
        = !query
          || [job.intitule, job.job_title, job.collaborateur]
            .map(value => String(value || '').toLowerCase())
            .some(value => value.includes(query))

      const isSigned = Boolean(job.signature)
      const matchesSignature
        = listFilters.value.signature === 'all'
          || (listFilters.value.signature === 'signed' && isSigned)
          || (listFilters.value.signature === 'pending' && !isSigned)

      return matchesSearch && matchesSignature
    })
  })

  function resetListFilters () {
    listFilters.value = {
      search: '',
      signature: 'all',
    }
  }

  async function downloadJobPDF (jobId: number) {
    const job = jobDescriptions.value.find((j: any) => j.id === jobId)
    if (job) {
      selectedJob.value = job
    }
    openJobConfig('download')
  }

  async function saveJobDescription () {
    loading.value = true
    try {
      const parseList = (value: string) =>
        value
          .split(';')
          .map(item => item.trim())
          .filter(item => item.length > 0)

      const data = {
        user_id: form.value.id_collaborateur
          ? Number(form.value.id_collaborateur)
          : undefined,
        reports_to_id: form.value.superieur_hierarchique_id
          ? Number(form.value.superieur_hierarchique_id)
          : undefined,
        job_title: form.value.titre_du_poste,
        replacement_job_title: form.value.titre_de_remplacement || null,
        department: form.value.departement || null,
        mission: form.value.mission,
        activities: form.value.activites || null,
        main_activities: form.value.activites_principales
          ? parseList(form.value.activites_principales)
          : [],
        secondary_activities: form.value.activites_secondaires
          ? parseList(form.value.activites_secondaires)
          : [],
        work_location: form.value.lieu_de_travail || null,
        work_schedule: form.value.horaire_de_travail || null,
        travel_required: form.value.deplacement_requis
          ? form.value.deplacement_requis === 'oui'
          : null,
        required_skills: form.value.competences_requises
          ? parseList(form.value.competences_requises)
          : [],
        required_education: form.value.formation_requise || null,
        required_experience: form.value.experience_requise || null,
      }

      const result = await leadershipService.createJobDescription(data)
      const normalizedResult = normalizeJobDescription(result)

      if (!normalizedResult.id) {
        throw new Error('Réponse API invalide: ID de fiche manquant')
      }
      jobDescriptions.value.push(normalizedResult)

      alert('✅ Fiche de poste enregistrée !')
      resetForm()
      activeTab.value = 'list'
      lastSavedAt.value = new Date()
    } catch (error) {
      console.error('Erreur sauvegarde fiche de poste:', error)
      alert('❌ Erreur lors de la sauvegarde')
    } finally {
      loading.value = false
    }
  }

  function resetForm () {
    form.value = {
      titre_du_poste: '',
      mission: '',
      id_collaborateur: '',
      departement: '',
      superieur_hierarchique_id: '',
      titre_de_remplacement: '',
      activites: '',
      activites_principales: '',
      activites_secondaires: '',
      lieu_de_travail: '',
      horaire_de_travail: '',
      deplacement_requis: '',
      competences_requises: '',
      experience_requise: '',
      formation_requise: '',
      signature: '',
    }
  }

  function createNew () {
    activeTab.value = 'form'
    resetForm()
  }

  function escapeHtml (value: string): string {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
  }

  function buildPreviewDocumentHtml (job: any): string {
    const title = escapeHtml(job?.job_title || '')
    const replacement = escapeHtml(job?.replacement_job_title || '')
    const mission = escapeHtml(job?.mission || '')
    const activities = escapeHtml(job?.activities || '')
    const education = escapeHtml(job?.required_education || '')
    const experience = escapeHtml(job?.required_experience || '')
    const department = escapeHtml(job?.department || '')
    const workLocation = escapeHtml(job?.work_location || '')
    const workSchedule = escapeHtml(job?.work_schedule || '')
    const skills = Array.isArray(job?.required_skills) ? job.required_skills : []
    const mainActivities = Array.isArray(job?.main_activities)
      ? job.main_activities
      : []
    const secondaryActivities = Array.isArray(job?.secondary_activities)
      ? job.secondary_activities
      : []
    const collaboratorLabel = escapeHtml(job?.collaborateur || '')
    const reportsToLabel = escapeHtml(
      resolveCollaboratorNameById(job?.reports_to_id)
      || String(job?.reports_to_id || ''),
    )
    const travelRequired
      = job?.travel_required === null || job?.travel_required === undefined
        ? ''
        : (job?.travel_required
          ? 'Oui'
          : 'Non')

    const skillsHtml
      = skills.length > 0
        ? `<h3>Compétences requises</h3><ul>${skills.map((skill: string) => `<li>${escapeHtml(String(skill))}</li>`).join('')}</ul>`
        : ''

    const mainActivitiesHtml
      = mainActivities.length > 0
        ? `<h3>Activités principales (détail)</h3><ul>${mainActivities.map((item: string) => `<li>${escapeHtml(String(item))}</li>`).join('')}</ul>`
        : ''

    const secondaryActivitiesHtml
      = secondaryActivities.length > 0
        ? `<h3>Activités secondaires</h3><ul>${secondaryActivities.map((item: string) => `<li>${escapeHtml(String(item))}</li>`).join('')}</ul>`
        : ''

    return `
      <h2>Fiche de poste</h2>
      <h3>Intitulé du poste</h3>
      <p>${title}</p>
      ${collaboratorLabel ? `<h3>Collaborateur</h3><p>${collaboratorLabel}</p>` : ''}
      ${department ? `<h3>Département</h3><p>${department}</p>` : ''}
      ${reportsToLabel ? `<h3>Supérieur hiérarchique</h3><p>${reportsToLabel}</p>` : ''}
      ${replacement ? `<h3>Poste du remplaçant (absence)</h3><p>${replacement}</p>` : ''}
      <h3>Mission principale</h3>
      <p>${mission}</p>
      ${activities ? `<h3>Activités principales</h3><p>${activities}</p>` : ''}
      ${mainActivitiesHtml}
      ${secondaryActivitiesHtml}
      ${workLocation ? `<h3>Lieu de travail</h3><p>${workLocation}</p>` : ''}
      ${workSchedule ? `<h3>Horaire de travail</h3><p>${workSchedule}</p>` : ''}
      ${travelRequired ? `<h3>Déplacements requis</h3><p>${travelRequired}</p>` : ''}
      ${skillsHtml}
      ${education ? `<h3>Niveau d'études</h3><p>${education}</p>` : ''}
      ${experience ? `<h3>Expérience requise</h3><p>${experience}</p>` : ''}
    `
  }

  async function refreshBrandedPreview (job: any) {
    const normalizedId = Number(job?.id || 0)
    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
      previewHtml.value = ''
      return
    }

    previewLoading.value = true
    try {
      previewHtml.value = await pdfExportService.previewDocument({
        document_type: 'job_description',
        document_id: normalizedId,
        content: {
          html: buildPreviewDocumentHtml(job),
          title: job?.job_title || 'Fiche de poste',
        },
        layout: 'professional',
      })
    } catch (error) {
      console.error('Erreur aperçu branding fiche de poste:', error)
      previewHtml.value = ''
    } finally {
      previewLoading.value = false
    }
  }

  async function viewJob (job: any) {
    const normalizedJob = normalizeJobDescription(job)
    if (!normalizedJob?.id) {
      console.error('Fiche invalide:', job)
      alert('❌ Impossible d\'afficher cette fiche (ID manquant)')
      return
    }
    selectedJob.value = normalizedJob
    showViewDialog.value = true
    await refreshBrandedPreview(normalizedJob)
  }

  // Import functionality handlers
  function handleImportUploaded (importId: number) {
    currentImportId.value = importId
    showImportDialog.value = false
    showImportPreview.value = true
  }

  function handleImportConfirmed () {
    showImportPreview.value = false
    currentImportId.value = null

    // Refresh logs table if it exists
    if (importLogsTableRef.value) {
      importLogsTableRef.value.loadLogs()
    }

    // Refresh job descriptions list
    loadJobDescriptions()

    // Switch to imports tab to show the result
    activeTab.value = 'imports'
  }

  async function loadJobDescriptions () {
    try {
      const jobsResponse = await leadershipService.getJobDescriptions()
      const rawJobs = Array.isArray(jobsResponse?.data)
        ? jobsResponse.data
        : (Array.isArray(jobsResponse)
          ? jobsResponse
          : [])
      jobDescriptions.value = rawJobs
        .map((item: any) => normalizeJobDescription(item))
        .filter((job: any) => !!job.id)
    } catch (error) {
      console.error('Erreur chargement fiches de poste:', error)
    }
  }

  onMounted(async () => {
    try {
      // Charger les collaborateurs via endpoint dédié (Option A)
      const collabsResponse
        = await leadershipService.getJobDescriptionCollaborators()

      collaborateurs.value = (collabsResponse || []).map((user: any) => ({
        id: user.id,
        full_name:
          user.full_name
          || `${user.last_name || ''} ${user.first_name || ''}`.trim()
          || user.email,
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        job_title: user.job_title || '',
        site_id: user.site_id,
        site_name: user.site_name,
        is_headquarter_site: !!user.is_headquarter_site,
        signature: user.signature_url || user.signature_path || null,
      }))

      // Charger les fiches de poste
      await loadJobDescriptions()
    } catch (error) {
      console.error('Erreur chargement fiche de poste:', error)
    }
  })

  watch(
    selectedCollaborator,
    collaborator => {
      form.value.signature = collaborator?.signature || ''
      form.value.titre_du_poste = collaborator?.job_title || ''
    },
    { immediate: true },
  )
</script>

<style scoped>
.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-help {
  font-size: 0.75rem;
  color: #64748b;
}

.pro-accordion {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.panel-title {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-weight: 700;
  color: #1e293b;
}

.panel-subtitle {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.toggle-group {
  display: flex;
  gap: 8px;
}

.form-field.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1e293b;
}

.field-label.required::after {
  content: "*";
  color: #ef4444;
  margin-left: 4px;
}

.field-input,
.field-textarea {
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #1e293b;
  transition: all 0.2s;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #4471c4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.12);
}

.field-textarea {
  resize: vertical;
  font-family: inherit;
}

.signature-section {
  margin: 20px 0;
  padding: 16px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.signature-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}

.signature-info {
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 12px;
}

.signature-preview-image {
  max-width: 240px;
  max-height: 96px;
  object-fit: contain;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 6px;
}

.approval-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
  padding: 12px;
  background: rgba(34, 197, 94, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.approval-text {
  flex: 1;
}

.approval-title {
  font-weight: 700;
  color: #16a34a;
}

.approval-date {
  font-size: 0.8125rem;
  color: #64748b;
}

.signature-missing {
  margin-top: 12px;
  font-size: 0.8125rem;
  color: #b45309;
}

.job-list {
  display: grid;
  gap: 12px;
}

.job-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  transition: all 0.2s;
}

.job-card:hover {
  border-color: #5b8dd9;
  box-shadow: 0 2px 8px rgba(91, 141, 217, 0.1);
}

.job-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.job-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  display: grid;
  place-items: center;
}

.job-info {
  flex: 1;
}

.job-title {
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.job-collab {
  font-size: 0.8125rem;
  color: #64748b;
}

.job-actions {
  display: flex;
  gap: 8px;
}

.empty-state {
  text-align: center;
  padding: 40px 24px;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin: 12px 0 6px;
}

.empty-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.preview-section {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.preview-section:last-child {
  border-bottom: none;
}

.preview-section h3 {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 6px;
}

.preview-section p {
  color: #475569;
  line-height: 1.5;
}

.preview-section ul {
  margin-left: 20px;
  color: #475569;
}

.job-preview {
  padding: 12px 0;
}

.job-preview-frame {
  width: 100%;
  min-height: 560px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
</style>
