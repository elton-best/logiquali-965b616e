<template>
  <LeadershipLayout>
    <HeroProgressRow>
      <HeroCard
        :badges="heroBadges"
        icon="mdi-shield-check"
        icon-color="primary"
        :subtitle="policySubtitle"
        :title="policyTitle"
      />

      <ProgressCard
        :completion="completion"
        label="Progression"
        :last-saved="lastSavedLabel"
        :value="`${filledFields}/${totalFields} champs`"
      />
    </HeroProgressRow>

    <GeneratedDocumentConfigDialog
      v-model="generationDialog"
      :loading="loading"
      :site-id="getCurrentSiteId() ?? null"
      @confirm="confirmGenerationConfig"
    />

    <TabsContainer v-model="activeTab" :tabs="tabs" />

    <transition mode="out-in" name="fade-slide">
      <GlassCard v-if="activeTab === 'form'" key="form">
        <div class="form-section">
          <h2 class="section-title">
            <span class="section-number">01</span>
            Informations principales
          </h2>

          <div class="form-grid">
            <div class="form-field">
              <label class="field-label required">Titre</label>
              <input
                v-model="form.title"
                class="field-input"
                placeholder="Ex: Politique QHSE 2026"
              >
            </div>

            <div class="form-field full-width">
              <label class="field-label required">Présentation de la structure</label>
              <textarea
                v-model="form.presentation"
                class="field-textarea"
                placeholder="Décrivez brièvement..."
                rows="4"
              />
            </div>

            <div class="form-field full-width">
              <label class="field-label required">Mission</label>
              <textarea
                v-model="form.mission"
                class="field-textarea"
                placeholder="Votre mission..."
                rows="3"
              />
            </div>

            <div class="form-field full-width">
              <label class="field-label required">Vision</label>
              <textarea
                v-model="form.vision"
                class="field-textarea"
                placeholder="Votre vision..."
                rows="3"
              />
            </div>

            <div class="form-field full-width">
              <label class="field-label">Valeurs</label>
              <textarea
                v-model="form.values"
                class="field-textarea"
                placeholder="Vos valeurs..."
                rows="3"
              />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2 class="section-title">
            <span class="section-number">02</span>
            Axes stratégiques
          </h2>

          <div class="dynamic-list">
            <div
              v-for="(axis, index) in form.axes"
              :key="`axis-${index}`"
              class="list-item"
            >
              <div class="list-number">{{ index + 1 }}</div>
              <input
                :ref="el => setAxisInputRef(el as HTMLInputElement | null, index)"
                v-model="form.axes[index]"
                class="list-input"
                :placeholder="`Axe ${index + 1}`"
              >
              <button
                class="list-remove"
                :disabled="form.axes.length === 1"
                @click="form.axes.splice(index, 1)"
              >
                <v-icon size="18">mdi-close</v-icon>
              </button>
            </div>
            <button class="add-button" @click="addAxis">
              <v-icon size="20">mdi-plus</v-icon>
              Ajouter un axe
            </button>
          </div>
        </div>

        <div class="form-section">
          <h2 class="section-title">
            <span class="section-number">03</span>
            Engagements
          </h2>

          <div class="dynamic-list">
            <div
              v-for="(commitment, index) in form.commitments"
              :key="`commitment-${index}`"
              class="list-item"
            >
              <div class="list-number">{{ index + 1 }}</div>
              <input
                :ref="el => setCommitmentInputRef(el as HTMLInputElement | null, index)"
                v-model="form.commitments[index]"
                class="list-input"
                :placeholder="`Engagement ${index + 1}`"
              >
              <button
                class="list-remove"
                :disabled="form.commitments.length === 1"
                @click="form.commitments.splice(index, 1)"
              >
                <v-icon size="18">mdi-close</v-icon>
              </button>
            </div>
            <button class="add-button" @click="addCommitment">
              <v-icon size="20">mdi-plus</v-icon>
              Ajouter un engagement
            </button>
          </div>
        </div>

        <ActionButtons
          primary-icon="mdi-content-save"
          primary-label="Sauvegarder"
          :primary-loading="loading"
          :secondary-disabled="!isFormValid"
          secondary-icon="mdi-eye"
          secondary-label="Aperçu"
          @primary="savePolicy"
          @secondary="activeTab = 'preview'"
        />

        <div class="workflow-actions">
          <div class="workflow-label">Workflow de validation</div>
          <div class="workflow-buttons">
            <v-btn
              color="warning"
              :disabled="!canSubmitForReview || loading"
              variant="tonal"
              @click="submitForReview"
            >
              Soumettre à validation
            </v-btn>
            <v-btn
              color="success"
              :disabled="!canValidatePolicy || loading"
              variant="flat"
              @click="validatePolicy"
            >
              Valider la politique
            </v-btn>
          </div>
        </div>

        <div v-if="lastSavedAt" class="auto-save-info">
          Dernière sauvegarde : {{ lastSavedLabel }}
        </div>
      </GlassCard>

      <GlassCard v-else key="preview">
        <div class="preview-header">
          <div>
            <h2 class="preview-title">{{ form.title || "Politique QHSE" }}</h2>
            <p class="preview-subtitle">Document généré automatiquement</p>
          </div>
          <div class="preview-actions">
            <v-btn
              prepend-icon="mdi-arrow-left"
              variant="outlined"
              @click="activeTab = 'form'"
            >
              Retour
            </v-btn>
            <v-btn
              color="primary"
              :loading="exportingDoc"
              prepend-icon="mdi-file-plus"
              variant="flat"
              @click="openGenerationDialog('draft')"
            >
              Générer brouillon
            </v-btn>
            <v-btn
              color="primary"
              :disabled="exportingDoc"
              prepend-icon="mdi-eye"
              variant="outlined"
              @click="handlePreviewDraft"
            >
              Prévisualiser
            </v-btn>
            <v-btn
              color="primary"
              :disabled="exportingDoc"
              prepend-icon="mdi-download"
              variant="outlined"
              @click="handleDownloadDraft"
            >
              Télécharger
            </v-btn>
            <v-btn
              color="warning"
              :disabled="submittingForVerification"
              :loading="submittingForVerification"
              prepend-icon="mdi-shield-check"
              variant="outlined"
              @click="openVerifyDialog"
            >
              Vérifier document
            </v-btn>
          </div>
        </div>

        <div class="preview-content">
          <v-progress-linear
            v-if="previewLoading"
            color="primary"
            indeterminate
          />
          <iframe
            v-else
            class="policy-preview-frame"
            :srcdoc="previewHtml"
            title="Aperçu Politique QHSE"
          />
        </div>
      </GlassCard>
    </transition>

    <v-dialog v-model="previewDialog" fullscreen>
      <v-card>
        <v-toolbar color="primary" density="compact">
          <v-toolbar-title>Prévisualisation — {{ previewFilename }}</v-toolbar-title>
          <v-spacer />
          <v-btn icon="mdi-close" @click="closePreview" />
        </v-toolbar>
        <iframe v-if="previewBlobUrl" :src="previewBlobUrl" style="width:100%; height:calc(100vh - 48px); border:none;" />
      </v-card>
    </v-dialog>

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
  </LeadershipLayout>
</template>

<script setup lang="ts">
  import type { QhsePolicy } from '@/services/leadershipService'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ActionButtons from '@/components/leadership/ActionButtons.vue'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import HeroCard from '@/components/leadership/HeroCard.vue'
  import HeroProgressRow from '@/components/leadership/HeroProgressRow.vue'
  import LeadershipLayout from '@/components/leadership/LeadershipLayout.vue'
  import ProgressCard from '@/components/leadership/ProgressCard.vue'
  import TabsContainer from '@/components/leadership/TabsContainer.vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import { leadershipService } from '@/services/leadershipService'
  import { pdfExportService } from '@/services/pdfExportService'
  import { useAuthStore } from '@/stores/auth'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useWorkflowCountsStore } from '@/stores/workflowCounts'
  import { useNorms } from '@/composables/useNorms'

  const authStore = useAuthStore()
  const toast = useToast()
  const workflowCounts = useWorkflowCountsStore()
  const { norms, fetchActiveNorms } = useNorms()

  onMounted(async () => {
    try {
      await fetchActiveNorms()
    } catch {
      // silencieux si déjà initialisé
    }
  })

  const policyTitle = computed(() => {
    if (norms.value.length === 1 && (String(norms.value[0]?.code).includes('9001') || String(norms.value[0]?.name).toLowerCase().includes('qualité'))) {
      return 'Politique Qualité'
    }
    return form.value.title || 'Politique QHSE'
  })

  const policySubtitle = computed(() => {
    return policyTitle.value === 'Politique Qualité'
      ? 'Définissez votre politique qualité et vos orientations stratégiques'
      : 'Définissez votre politique qualité, santé, sécurité et environnement'
  })

  function getCurrentSiteId () {
    const stored = Number(localStorage.getItem('current_site_id'))
    return (
      authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : undefined)
    )
  }
  const form = ref({
    title: 'Politique QHSE',
    presentation: '',
    mission: '',
    vision: '',
    values: '',
    axes: [''],
    commitments: [''],
  })
  const axisInputRefs = ref<Array<HTMLInputElement | null>>([])
  const commitmentInputRefs = ref<Array<HTMLInputElement | null>>([])

  function setAxisInputRef (element: HTMLInputElement | null, index: number) {
    axisInputRefs.value[index] = element
  }

  function setCommitmentInputRef (element: HTMLInputElement | null, index: number) {
    commitmentInputRefs.value[index] = element
  }

  function addAxis () {
    form.value.axes.unshift('')
    void focusTopInsertedField(axisInputRefs.value, 0)
  }

  function addCommitment () {
    form.value.commitments.unshift('')
    void focusTopInsertedField(commitmentInputRefs.value, 0)
  }

  const activeTab = ref('form')
  const lastSavedAt = ref<Date | null>(null)
  const policyId = ref<number | null>(null)
  const policyVersion = ref('1.0')
  const policyStatus = ref<string | null>(null)
  const policyEffectiveDate = ref<string | null>(null)
  const loading = ref(false)
  const previewHtml = ref('')
  const previewLoading = ref(false)
  const generationDialog = ref(false)
  const generationAction = ref<'draft' | 'preview' | 'download' | 'verify'>('draft')
  type GeneratedDocumentContext = {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }

  const generationContext = ref<GeneratedDocumentContext | null>(null)
  const isDirty = ref(false)
  const isHydrating = ref(false)
  const isRefreshingPreview = ref(false)

  const tabs = [
    { value: 'form', label: 'Formulaire', icon: 'mdi-form-select' },
    { value: 'preview', label: 'Aperçu', icon: 'mdi-eye' },
  ]

  const filteredAxes = computed(() =>
    form.value.axes.filter(item => item.trim()),
  )
  const filteredCommitments = computed(() =>
    form.value.commitments.filter(item => item.trim()),
  )

  const requiredFields = computed(() => [
    form.value.title.trim(),
    form.value.presentation.trim(),
    form.value.mission.trim(),
    form.value.vision.trim(),
  ])

  const totalFields = 6
  const filledFields = computed(() => {
    return (
      requiredFields.value.filter(Boolean).length
      + (filteredAxes.value.length > 0 ? 1 : 0)
      + (filteredCommitments.value.length > 0 ? 1 : 0)
    )
  })

  const completion = computed(() =>
    Math.round((filledFields.value / totalFields) * 100),
  )
  const isFormValid = computed(() => requiredFields.value.every(Boolean))
  const lastSavedLabel = computed(
    () => lastSavedAt.value?.toLocaleString() || 'Jamais',
  )
  const policyStatusLabel = computed(() => {
    if (!policyStatus.value) return 'Brouillon'
    if (policyStatus.value === 'validated') return 'Validée'
    if (policyStatus.value === 'en_revision') return 'En révision'
    return 'Brouillon'
  })
  const policyStatusIcon = computed(() => {
    if (policyStatus.value === 'validated') return 'mdi-check-decagram'
    if (policyStatus.value === 'en_revision') return 'mdi-eye-check'
    return 'mdi-pencil'
  })
  const heroBadges = computed(() => [
    {
      icon: 'mdi-check-circle',
      text: `Version ${policyVersion.value}`,
      variant: 'primary' as const,
    },
    {
      icon: policyStatusIcon.value,
      text: policyStatusLabel.value,
      variant: 'secondary' as const,
    },
  ])
  const canSubmitForReview = computed(
    () => Boolean(policyId.value) && policyStatus.value === 'draft',
  )
  const canValidatePolicy = computed(
    () => Boolean(policyId.value) && policyStatus.value === 'en_revision',
  )
  const previewDocumentHtml = computed(() => {
    const valuesList = form.value.values.trim()
      ? form.value.values
        .split('\n')
        .filter(v => v.trim())
        .map(v => `<li>${v}</li>`)
        .join('')
      : ''
    const axesList = filteredAxes.value
      .map(axis => `<li>${axis}</li>`)
      .join('')
    const commitmentsList = filteredCommitments.value
      .map(item => `<li>${item}</li>`)
      .join('')

    return `
      <h1>${form.value.title || 'Politique QHSE'}</h1>
      <h2>Présentation de la structure</h2>
      <p>${form.value.presentation || '—'}</p>
      <h2>Mission</h2>
      <p>${form.value.mission || '—'}</p>
      <h2>Vision</h2>
      <p>${form.value.vision || '—'}</p>
      ${valuesList ? `<h2>Valeurs</h2><ul>${valuesList}</ul>` : ''}
      <h2>Axes stratégiques</h2>
      ${axesList ? `<ul>${axesList}</ul>` : '<p>Aucun axe défini</p>'}
      <h2>Engagements</h2>
      ${commitmentsList ? `<ul>${commitmentsList}</ul>` : '<p>Aucun engagement défini</p>'}
    `
  })

  let autoSaveTimeout: ReturnType<typeof setTimeout> | null = null

  // Sauvegarde automatique (sans validation - auto-save en arrière-plan)
  async function autoSavePolicy () {
    // Ne pas valider pour l'auto-save - sauvegarde silencieuse
    if (!form.value.mission || form.value.mission.trim() === '') {
      console.log('⏸️ [Policy] Auto-save skipped: Mission vide')
      return // Skip auto-save si Mission vide, mais pas d'alerte
    }

    try {
      const data: Partial<QhsePolicy> = {
        site_id: getCurrentSiteId(),
        mission: form.value.mission.trim(),
        vision: form.value.vision?.trim() || undefined,
        values: form.value.values
          ? form.value.values.split('\n').filter((v: string) => v.trim())
          : [],
        axes: filteredAxes.value,
        commitments: filteredCommitments.value,
        quality_policy: form.value.presentation?.trim() || undefined,
      }

      console.log('💾 [Policy] Auto-save...', data)

      if (policyId.value) {
        const updatedPolicy = await leadershipService.updatePolicy(
          policyId.value,
          data,
        )
        policyVersion.value = updatedPolicy?.version || policyVersion.value
        policyStatus.value = updatedPolicy?.status || policyStatus.value
        policyEffectiveDate.value
          = updatedPolicy?.effective_date || policyEffectiveDate.value
        lastSavedAt.value = new Date()
        isDirty.value = false
        console.log('✅ [Policy] Auto-saved')
      }
    } catch (error: any) {
      console.warn(
        '⚠️ [Policy] Auto-save failed:',
        error.response?.data?.message,
      )
    // Pas d'alerte pour l'auto-save - juste log
    }
  }

  function buildPolicyPayload (): QhsePolicy {
    return {
      mission: form.value.mission.trim(),
      vision: form.value.vision?.trim() || undefined,
      values: form.value.values
        ? form.value.values.split('\n').filter((v: string) => v.trim())
        : [],
      axes: filteredAxes.value,
      commitments: filteredCommitments.value,
      quality_policy: form.value.presentation?.trim() || undefined,
    }
  }

  function formatValidationErrors (errors: Record<string, any>) {
    return Object.entries(errors)
      .map(([field, msgs]: [string, any]) => `- ${field}: ${msgs.join(', ')}`)
      .join('\n')
  }

  function showSaveErrorAlert (error: any) {
    const errorMsg = error.response?.data?.message || 'Erreur lors de la sauvegarde'
    const errors = error.response?.data?.errors
    if (errors) {
      const details = Object.entries(errors)
        .map(([field, msgs]: [string, any]) => `${field}: ${msgs.join(', ')}`)
        .join(' | ')
      toast.error(`${errorMsg} — ${details}`)
      return
    }
    toast.error(errorMsg)
  }

  // Sauvegarde manuelle (avec validation et toasts)
  async function savePolicy () {
    if (!form.value.mission || form.value.mission.trim() === '') {
      toast.error('Le champ "Mission" est obligatoire.')
      return
    }

    loading.value = true
    try {
      const data: QhsePolicy = buildPolicyPayload()

      if (policyId.value) {
        const updatedPolicy = await leadershipService.updatePolicy(policyId.value, data)
        policyVersion.value = updatedPolicy?.version || policyVersion.value
        policyStatus.value = updatedPolicy?.status || policyStatus.value
        policyEffectiveDate.value = updatedPolicy?.effective_date || policyEffectiveDate.value
      } else {
        const result = await leadershipService.createPolicy(data)
        policyId.value = result.id
        policyVersion.value = result.version || policyVersion.value
        policyStatus.value = result.status || policyStatus.value
        policyEffectiveDate.value = result.effective_date || null
      }

      lastSavedAt.value = new Date()
      isDirty.value = false
      toast.success('Politique enregistrée avec succès.')
    } catch (error: any) {
      console.error('[Policy] Erreur sauvegarde:', error)
      showSaveErrorAlert(error)
    } finally {
      loading.value = false
    }
  }

  const {
    exporting: exportingDoc, submittingForVerification, verificationSent,
    lastExportedDocumentId, exportedDocumentCode,
    previewDialog, previewBlobUrl, previewFilename,
    verifyDialog, ensureDraftReady, handlePreviewDraft, closePreview,
    handleDownloadDraft, openVerifyDialog, submitForVerification,
  } = useDocumentFlow(
    async () => {
      if (!policyId.value) { await savePolicy() }
      if (!policyId.value) { toast.error('Sauvegardez la politique avant la génération.'); return null }
      if (!generationContext.value) { openGenerationDialog('draft'); return null }
      const response = await api.post(`/qhse-policies/${policyId.value}/generate-draft`, generationContext.value)
      const info = extractDocumentInfo(response.data)
      return info.id ? info as { id: number, code: string } : null
    },
    () => `Politique_QHSE_${authStore.currentSite?.name || 'Site'}_${new Date().toISOString().split('T')[0]}.pdf`,
  )

  watch(verificationSent, sent => {
    if (sent) isDirty.value = true
  })

  function openGenerationDialog (action: 'draft' | 'preview' | 'download' | 'verify' = 'draft') {
    generationAction.value = action
    generationDialog.value = true
  }

  async function confirmGenerationConfig (context: GeneratedDocumentContext) {
    generationContext.value = context
    generationDialog.value = false
    if (generationAction.value === 'draft') {
      await ensureDraftReady()
    } else if (generationAction.value === 'preview') {
      await handlePreviewDraft()
    } else if (generationAction.value === 'download') {
      await handleDownloadDraft()
    } else if (generationAction.value === 'verify') {
      const ready = await ensureDraftReady()
      if (ready) {
        await submitForVerification()
      }
    }
  }

  function extractDocumentInfo (payload: any): { id: number | null, code: string } {
    const data = payload?.data ?? payload
    const attributes = data?.attributes ?? data
    const id = Number(data?.id || attributes?.id || 0) || null
    const code = String(attributes?.code || '')
    return { id, code }
  }

  async function submitForReview () {
    if (!policyId.value) {
      await savePolicy()
    }
    if (!policyId.value) return
    loading.value = true
    try {
      const result = await leadershipService.submitPolicy(policyId.value)
      policyStatus.value = result?.status || policyStatus.value
      policyVersion.value = result?.version || policyVersion.value
      policyEffectiveDate.value
        = result?.effective_date || policyEffectiveDate.value
      lastSavedAt.value = new Date()
      workflowCounts.invalidate()
      void workflowCounts.fetchCounts(true)
      toast.success('Politique soumise à validation. Les vérificateurs ont été notifiés.')
    } catch (error: any) {
      console.error('❌ [Policy] Erreur soumission:', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la soumission.')
    } finally {
      loading.value = false
    }
  }

  async function validatePolicy () {
    if (!policyId.value) return
    loading.value = true
    try {
      const result = await leadershipService.validatePolicy(policyId.value)
      policyStatus.value = result?.status || policyStatus.value
      policyVersion.value = result?.version || policyVersion.value
      policyEffectiveDate.value
        = result?.effective_date || policyEffectiveDate.value
      lastSavedAt.value = new Date()
      workflowCounts.decrementApproval()
      toast.success('Politique validée avec succès.')
    } catch (error: any) {
      console.error('❌ [Policy] Erreur validation:', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la validation.')
    } finally {
      loading.value = false
    }
  }

  async function refreshPreview () {
    if (isRefreshingPreview.value) {
      return
    }

    isRefreshingPreview.value = true
    previewLoading.value = true
    try {
      if (isDirty.value && policyId.value) {
        await autoSavePolicy()
      }

      previewHtml.value = await pdfExportService.previewDocument({
        document_type: 'qhse_policy',
        document_id: policyId.value || 0,
        content: {
          html: previewDocumentHtml.value,
          title: form.value.title || 'Politique QHSE',
          version: policyVersion.value,
          effective_date: policyEffectiveDate.value || undefined,
        },
        layout: 'professional',
      })
    } catch (error) {
      console.error('Erreur aperçu politique:', error)
      alert(
        '❌ Impossible de générer l’aperçu document. Vérifiez la configuration entreprise.',
      )
    } finally {
      previewLoading.value = false
      isRefreshingPreview.value = false
    }
  }

  function applyPolicyState (policy: QhsePolicy | null) {
    isHydrating.value = true

    policyId.value = policy?.id || null
    policyStatus.value = policy?.status || null
    policyVersion.value = policy?.version || '1.0'
    policyEffectiveDate.value = policy?.effective_date || null
    form.value = {
      title: 'Politique QHSE',
      presentation: policy?.quality_policy || '',
      mission: policy?.mission || '',
      vision: policy?.vision || '',
      values: Array.isArray(policy?.values) ? policy.values.join('\n') : '',
      axes:
        Array.isArray(policy?.axes) && policy.axes.length > 0
          ? policy.axes
          : [''],
      commitments:
        Array.isArray(policy?.commitments) && policy.commitments.length > 0
          ? policy.commitments
          : [''],
    }
    lastSavedAt.value = policy?.updated_at
      ? new Date(policy.updated_at)
      : null
    isDirty.value = false

    if (autoSaveTimeout) {
      clearTimeout(autoSaveTimeout)
      autoSaveTimeout = null
    }

    isHydrating.value = false
  }

  onMounted(async () => {
    try {
      const policy = await leadershipService.getCurrentPolicy(getCurrentSiteId())
      applyPolicyState(policy || null)
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  })

  watch(
    form,
    () => {
      if (isHydrating.value) {
        return
      }

      isDirty.value = true
      if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout)
      }
      autoSaveTimeout = setTimeout(() => {
        autoSavePolicy() // ✅ Utilise auto-save silencieux
      }, 2000)
    },
    { deep: true },
  )

  watch(activeTab, value => {
    if (value === 'preview') {
      refreshPreview()
    }
  })

  watch(policyVersion, () => {
    if (activeTab.value === 'preview') {
      refreshPreview()
    }
  })

  watch(
    () => authStore.currentSiteId,
    async () => {
      try {
        const policy
          = await leadershipService.getCurrentPolicy(getCurrentSiteId())
        applyPolicyState(policy || null)
      } catch (error) {
        console.error('Erreur chargement politique (changement de site):', error)
      }
    },
  )
</script>

<style scoped>
.form-section {
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.form-field {
  display: flex;
  flex-direction: column;
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

.dynamic-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.list-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.list-number {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
  display: grid;
  place-items: center;
  font-weight: 600;
  font-size: 0.75rem;
}

.list-input {
  flex: 1;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.list-input:focus {
  outline: none;
  border-color: #4471c4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.12);
}

.list-remove {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.list-remove:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
}

.list-remove:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.add-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.add-button:hover {
  border-color: #5b8dd9;
  color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.preview-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.preview-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
}

.preview-actions {
  display: flex;
  gap: 8px;
}

.preview-content {
  min-height: 460px;
}

.policy-preview-frame {
  width: 100%;
  height: 460px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
}

.preview-section h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.preview-section p {
  font-size: 1rem;
  line-height: 1.7;
  color: #475569;
}

.preview-section ul {
  list-style: none;
  padding: 0;
}

.preview-section li {
  position: relative;
  padding-left: 24px;
  margin-bottom: 12px;
  font-size: 1rem;
  line-height: 1.7;
  color: #475569;
}

.preview-section li::before {
  content: "";
  position: absolute;
  left: 0;
  top: 10px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #5b8dd9;
}

.empty-state {
  color: #94a3b8;
  font-style: italic;
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

.auto-save-info {
  margin-top: 16px;
  padding: 10px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  font-size: 0.8125rem;
  text-align: center;
}

.workflow-actions {
  margin-top: 18px;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.workflow-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

.workflow-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .preview-header {
    flex-direction: column;
    gap: 16px;
  }
}
</style>
