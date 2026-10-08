<template>
  <ClientALayout current-page="application-scope">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-file-document-outline"
        subtitle="Définir le périmètre du système de management ISO 9001"
        title="Domaine d'application"
      >
        <template #actions>
          <v-btn
            color="primary"
            :loading="saving"
            prepend-icon="mdi-content-save"
            @click="handleSave"
          >
            Enregistrer
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exporting"
            prepend-icon="mdi-eye"
            variant="text"
            @click="lastExportedDocumentId ? handlePreviewDraft() : openGenerationDialog('preview')"
          >
            Prévisualiser
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exporting"
            prepend-icon="mdi-download"
            variant="text"
            @click="lastExportedDocumentId ? handleDownloadDraft() : openGenerationDialog('download')"
          >
            Télécharger
          </v-btn>
          <v-btn
            color="warning"
            :disabled="submittingForVerification || verificationSent"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="openVerifyDialog"
          >
            Vérifier document
          </v-btn>
        </template>
      </PageHeader>

      <!-- Stepper cliquable -->
      <ApplicationScopeStepper
        :current-step="currentStep"
        :steps="steps"
        @update:current-step="handleStepChange"
      />

      <!-- Contenu des étapes -->
      <v-window v-model="currentStep" class="mb-6">
        <!-- Étape 1: Documents référencés -->
        <v-window-item :value="1">
          <ScopeDocumentsStep
            :documents="form.referencedDocuments"
            :step="steps[0]"
            @add="addDocument"
            @remove="removeDocument"
          />
        </v-window-item>

        <!-- Étape 2: Définition -->
        <v-window-item :value="2">
          <ScopeDefinitionStep :form="form" :step="steps[1]" />
        </v-window-item>

        <!-- Étape 3: Processus -->
        <v-window-item :value="3">
          <ScopeProcessesStep
            :get-process-type-color="getProcessTypeColor"
            :get-process-type-label="getProcessTypeLabel"
            :process-types="processTypes"
            :processes="form.processes"
            :step="steps[2]"
            @add="addProcess"
            @remove="removeProcess"
          />
        </v-window-item>

        <!-- Étape 4: Produits et services -->
        <v-window-item :value="4">
          <ScopeProductsServicesStep
            :items="form.productsServices"
            :step="steps[3]"
            @add="addProductService"
            @remove="removeProductService"
          />
        </v-window-item>

        <!-- Étape 5: Unités organisationnelles -->
        <v-window-item :value="5">
          <ScopeUnitsStep
            :step="steps[4]"
            :units="form.organizationalUnits"
            @add="addOrganizationalUnit"
            @remove="removeOrganizationalUnit"
          />
        </v-window-item>

        <!-- Étape 6: Lieux -->
        <v-window-item :value="6">
          <ScopeLocationsStep
            :locations="form.locations"
            :step="steps[5]"
            @add="addLocation"
            @remove="removeLocation"
          />
        </v-window-item>

        <!-- Étape 7: Exclusions -->
        <v-window-item :value="7">
          <ScopeExclusionsStep
            :chapter-code-of="chapterCodeOf"
            :chapter-title-of="chapterTitleOf"
            :form="form"
            :get-norm-excluded-chapters="getNormExcludedChapters"
            :get-norm-justification="getNormJustification"
            :has-any-norm-exclusion="hasAnyNormExclusion"
            :is-chapter-excluded="isChapterExcluded"
            :loading-norms="loadingNorms"
            :norm-key="normKey"
            :set-norm-justification="setNormJustification"
            :step="steps[6]"
            :subscribed-norms="subscribedNorms"
            :toggle-chapter-exclusion="toggleChapterExclusion"
          />
        </v-window-item>

        <!-- Étape 8: Récapitulatif -->
        <v-window-item :value="8">
          <ScopeSummaryStep
            :form="form"
            :get-norm-excluded-chapters="getNormExcludedChapters"
            :get-norm-exclusion-label="getNormExclusionLabel"
            :get-norm-justification="getNormJustification"
            :get-process-type-color="getProcessTypeColor"
            :get-process-type-label="getProcessTypeLabel"
            :norm-key="normKey"
            :subscribed-norms="subscribedNorms"
            @jump-to-step="handleStepChange"
          />
        </v-window-item>
      </v-window>

      <!-- Navigation -->
      <v-card class="navigation-card" elevation="0" rounded="xl">
        <v-card-actions class="pa-5">
          <v-btn
            v-if="currentStep > 1"
            prepend-icon="mdi-chevron-left"
            size="large"
            variant="tonal"
            @click="currentStep--"
          >
            Précédent
          </v-btn>
          <v-spacer />
          <v-btn
            v-if="currentStep < 8"
            append-icon="mdi-chevron-right"
            :color="steps[currentStep - 1]?.color || 'primary'"
            size="large"
            @click="goToNextStep"
          >
            Suivant
          </v-btn>
          <v-btn
            v-else
            color="success"
            :loading="saving"
            prepend-icon="mdi-check-circle"
            size="large"
            @click="handleSave"
          >
            Enregistrer définitivement
          </v-btn>
        </v-card-actions>
      </v-card>

      <v-dialog v-model="generationDialog" max-width="640">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Paramètres du document généré</v-card-title>
          <v-card-text class="pa-4">
            <v-alert class="mb-4" type="info" variant="tonal">
              Sélectionnez le type documentaire et le processus à utiliser pour générer le code du brouillon.
            </v-alert>
            <v-select
              v-model="generationForm.documentTypeCatalogId"
              class="mb-3"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="documentTypeOptions"
              label="Type documentaire"
              variant="outlined"
            />
            <v-select
              v-model="generationForm.processId"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="processOptions"
              label="Processus lié"
              variant="outlined"
            />
            <v-alert v-if="documentTypeOptions.length === 0" class="mt-3" type="warning" variant="tonal">
              Aucun type documentaire actif n'est disponible. Configurez d'abord les nomenclatures.
            </v-alert>
            <v-alert v-if="processOptions.length === 0" class="mt-3" type="warning" variant="tonal">
              Aucun processus enregistré n'est disponible. Enregistrez d'abord le domaine d'application.
            </v-alert>
            <v-alert v-if="hasScopeProcessesWithoutGlobalId" class="mt-3" type="info" variant="tonal">
              Certains processus viennent du formulaire du domaine d'application. Ils seront synchronisés automatiquement pendant la génération du brouillon.
            </v-alert>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="generationDialog = false">Annuler</v-btn>
            <v-btn
              color="primary"
              :disabled="!canGenerateDocument"
              :loading="exporting || loadingGenerationOptions"
              @click="confirmGenerationConfig"
            >
              Continuer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="verifyDialog" max-width="560">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 mb-2">Code du document</div>
            <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
            <v-alert v-if="!lastExportedDocumentId" class="mt-3" type="warning" variant="tonal">
              Aucun brouillon n’a encore été généré. La confirmation générera le brouillon pour le workflow sans lancer de téléchargement.
            </v-alert>
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

      <!-- Dialog prévisualisation PDF -->
      <v-dialog v-model="previewDialog" fullscreen>
        <v-card>
          <v-toolbar color="primary" density="compact">
            <v-toolbar-title>Prévisualisation — {{ previewFilename }}</v-toolbar-title>
            <v-spacer />
            <v-btn icon="mdi-close" @click="closePreview" />
          </v-toolbar>
          <iframe
            v-if="previewBlobUrl"
            :src="previewBlobUrl"
            style="width:100%; height:calc(100vh - 48px); border:none;"
          />
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ApplicationScopeStepper from '@/modules/clienta/pages/iso/context/components/ApplicationScopeStepper.vue'
  import ScopeDefinitionStep from '@/modules/clienta/pages/iso/context/components/ScopeDefinitionStep.vue'
  import ScopeDocumentsStep from '@/modules/clienta/pages/iso/context/components/ScopeDocumentsStep.vue'
  import ScopeExclusionsStep from '@/modules/clienta/pages/iso/context/components/ScopeExclusionsStep.vue'
  import ScopeLocationsStep from '@/modules/clienta/pages/iso/context/components/ScopeLocationsStep.vue'
  import ScopeProcessesStep from '@/modules/clienta/pages/iso/context/components/ScopeProcessesStep.vue'
  import ScopeProductsServicesStep from '@/modules/clienta/pages/iso/context/components/ScopeProductsServicesStep.vue'
  import ScopeSummaryStep from '@/modules/clienta/pages/iso/context/components/ScopeSummaryStep.vue'
  import ScopeUnitsStep from '@/modules/clienta/pages/iso/context/components/ScopeUnitsStep.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'

  const toast = useToast()
  const authStore = useAuthStore()
  const saving = ref(false)
  const loadingGenerationOptions = ref(false)
  const generationDialog = ref(false)
  const generationAction = ref<'draft' | 'preview' | 'download' | 'verify'>('draft')
  const currentScopeId = ref<number | null>(null)
  const currentStep = ref(1)
  const FIRST_STEP = 1
  const SUMMARY_STEP = 8
  const subscribedNorms = ref<Array<{ id: number, code: string, name: string, chapters: any[] }>>([])
  const loadingNorms = ref(false)

  const steps = [
    { label: 'Documents', color: '#5b8dd9', gradient: 'linear-gradient(135deg, #5b8dd915 0%, #5b8dd905 100%)' },
    { label: 'Définition', color: '#22c55e', gradient: 'linear-gradient(135deg, #22c55e15 0%, #22c55e05 100%)' },
    { label: 'Processus', color: '#3b82f6', gradient: 'linear-gradient(135deg, #3b82f615 0%, #3b82f605 100%)' },
    { label: 'Produits', color: '#f59e0b', gradient: 'linear-gradient(135deg, #f59e0b15 0%, #f59e0b05 100%)' },
    { label: 'Unités', color: '#a855f7', gradient: 'linear-gradient(135deg, #a855f715 0%, #a855f705 100%)' },
    { label: 'Lieux', color: '#06b6d4', gradient: 'linear-gradient(135deg, #06b6d415 0%, #06b6d405 100%)' },
    { label: 'Exclusions', color: '#ef4444', gradient: 'linear-gradient(135deg, #ef444415 0%, #ef444405 100%)' },
    { label: 'Récapitulatif', color: '#10b981', gradient: 'linear-gradient(135deg, #10b98115 0%, #10b98105 100%)' },
  ]

  const form = ref({
    referencedDocuments: [] as string[],
    documentObjective: '',
    scopeDefinition: '',
    processes: [] as Array<{ id?: number, name: string, type: string, abbreviation?: string }>,
    productsServices: [] as string[],
    organizationalUnits: [] as string[],
    locations: [] as string[],
    scopeExclusions: '',
    isoExclusions: [] as string[],
    isoExclusionsJustification: '',
    normExclusions: {} as Record<string, string[]>, // { 'ISO 9001:2015': ['4.3', '7.3'], ... }
    normExclusionsJustifications: {} as Record<string, string>, // { 'ISO 9001:2015': 'justification globale' }
  })
  const systemProcesses = ref<Array<{ id?: number, name: string, type: string, abbreviation?: string }>>([])
  const documentTypes = ref<Array<{ id: number, name: string, abbreviation: string }>>([])
  const generationForm = ref({
    documentTypeCatalogId: null as number | null,
    processId: null as number | string | null,
  })

  const processTypes = [
    { title: 'Management', value: 'management' },
    { title: 'Réalisation', value: 'realization' },
    { title: 'Support', value: 'support' },
  ]

  const documentTypeOptions = computed(() =>
    documentTypes.value.map(type => ({
      title: `${type.name} (${type.abbreviation})`,
      value: type.id,
    })),
  )

  const processOptions = computed(() => {
    const options: Array<{ title: string, value: number | string }> = []
    const seenIds = new Set<number>()
    const seenNames = new Set<string>()

    for (const process of [...systemProcesses.value, ...form.value.processes]) {
      const name = String(process?.name || '').trim()
      if (!name) continue

      const id = Number(process?.id || 0)
      const key = name.toLowerCase()

      if (id > 0) {
        if (seenIds.has(id)) continue
        seenIds.add(id)
        seenNames.add(key)
        options.push({
          title: `${name}${process.abbreviation ? ` (${process.abbreviation})` : ''}`,
          value: id,
        })
        continue
      }

      if (seenNames.has(key)) continue
      seenNames.add(key)
      const formIndex = form.value.processes.findIndex(item => String(item?.name || '').trim().toLowerCase() === key)
      options.push({
        title: `${name}${process.abbreviation ? ` (${process.abbreviation})` : ''} - à synchroniser`,
        value: `scope:${Math.max(formIndex, 0)}`,
      })
    }

    return options
  })

  const canGenerateDocument = computed(() =>
    Boolean(generationForm.value.documentTypeCatalogId && generationForm.value.processId),
  )

  const hasScopeProcessesWithoutGlobalId = computed(() =>
    processOptions.value.some(option => String(option.value).startsWith('scope:')),
  )

  function resolveCurrentSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
  }

  function isActiveSubscriptionEntry (subscription: any, now: Date): boolean {
    const isActive = Boolean(subscription?.is_active || subscription?.attributes?.is_active)
    const expirationRaw = subscription?.expiration_date || subscription?.attributes?.expiration_date
    if (!isActive) return false
    if (!expirationRaw) return true
    return new Date(expirationRaw) > now
  }

  function asArrayOrEmpty (value: unknown): any[] {
    return Array.isArray(value) ? value : []
  }

  function getValueByPath (source: any, path: string): unknown {
    return path
      .split('.')
      .reduce((current: any, segment: string) => (current == null ? undefined : current[segment]), source)
  }

  function extractNormsFromSubscription (subscription: any): any[] {
    const candidatePaths = [
      'offer.norms',
      'attributes.offer.norms',
      'relationships.offer.data.attributes.norms',
      'relationships.offer.relationships.norms.data',
      'relationships.offer.relationships.norms',
      'relationships.offer.data.relationships.norms.data',
      'relationships.offer.data.relationships.norms',
    ]

    for (const path of candidatePaths) {
      const norms = asArrayOrEmpty(getValueByPath(subscription, path))
      if (norms.length > 0) return norms
    }

    return []
  }

  function normalizeNormFromSubscription (norm: any) {
    const attributes = norm?.attributes || norm
    const id = Number(norm?.id ?? attributes?.id)
    const code = String(attributes?.code || attributes?.name || '').trim()
    const name = String(attributes?.name || attributes?.title || attributes?.code || '').trim()
    return { id, code, name }
  }

  function hasNormAlreadyCollected (
    unresolvedNormIds: Set<number>,
    seenNormIds: Set<number>,
    allNorms: Array<{ id: number, code: string, name: string }>,
    id: number,
  ): boolean {
    if (!unresolvedNormIds.has(id)) return true
    if (!seenNormIds.has(id)) return false
    return allNorms.some(entry => entry.id === id)
  }

  function mapFallbackNorm (norm: any) {
    return {
      id: Number(norm?.id),
      code: String(norm?.code || norm?.attributes?.code || norm?.name || '').trim(),
      name: String(norm?.name || norm?.attributes?.name || norm?.title || norm?.code || '').trim(),
    }
  }

  async function resolveNormLabelsFallback (
    unresolvedNormIds: Set<number>,
    seenNormIds: Set<number>,
    allNorms: Array<{ id: number, code: string, name: string }>,
  ): Promise<void> {
    if (unresolvedNormIds.size === 0) {
      return
    }

    try {
      const { data: normsData } = await api.get('/norms')
      const allAccessibleNorms = Array.isArray(normsData?.data) ? normsData.data : []
      for (const norm of allAccessibleNorms) {
        const mapped = mapFallbackNorm(norm)
        if (hasNormAlreadyCollected(unresolvedNormIds, seenNormIds, allNorms, mapped.id)) continue
        allNorms.push(mapped)
      }
    } catch {
      // Silent fallback: on garde uniquement ce qu'on a pu résoudre.
    }
  }

  async function buildNormsWithChapters (
    norms: Array<{ id: number, code: string, name: string }>,
    siteId: number,
  ) {
    return Promise.all(
      norms.map(async norm => {
        try {
          const { data: chaptersData } = await api.get(`/norms/${norm.id}/chapters`, {
            params: { site_id: siteId },
          })
          return {
            id: norm.id,
            code: norm.code || norm.name || `Norme ${norm.id}`,
            name: norm.name || norm.code || `Norme ${norm.id}`,
            chapters: chaptersData?.data || [],
          }
        } catch {
          return {
            id: norm.id,
            code: norm.code || norm.name || `Norme ${norm.id}`,
            name: norm.name || norm.code || `Norme ${norm.id}`,
            chapters: [],
          }
        }
      }),
    )
  }

  async function loadSubscribedNorms () {
    const siteId = resolveCurrentSiteId()

    if (!siteId) {
      subscribedNorms.value = []
      return
    }

    loadingNorms.value = true
    try {
      // Route robuste: inclut offer.norms côté backend
      const { data: subsData } = await api.get('/enterprise-subscriptions', {
        params: { site_id: siteId },
      })
      const subscriptions = subsData?.data || []

      if (subscriptions.length === 0) {
        subscribedNorms.value = []
        return
      }

      const now = new Date()
      const activeSubscriptions = subscriptions.filter((subscription: any) => isActiveSubscriptionEntry(subscription, now))

      const allNorms: any[] = []
      const seenNormIds = new Set<number>()
      const baseSubs = activeSubscriptions.length > 0 ? activeSubscriptions : subscriptions
      const unresolvedNormIds = new Set<number>()

      for (const sub of baseSubs) {
        const norms = extractNormsFromSubscription(sub)
        for (const norm of norms) {
          const normalizedNorm = normalizeNormFromSubscription(norm)
          const id = normalizedNorm.id

          if (!Number.isFinite(id) || seenNormIds.has(id)) continue

          seenNormIds.add(id)
          if (normalizedNorm.code || normalizedNorm.name) {
            allNorms.push(normalizedNorm)
          } else {
            unresolvedNormIds.add(id)
          }
        }
      }

      await resolveNormLabelsFallback(unresolvedNormIds, seenNormIds, allNorms)

      if (allNorms.length === 0) {
        subscribedNorms.value = []
        return
      }

      subscribedNorms.value = await buildNormsWithChapters(allNorms, siteId)
    } catch {
      subscribedNorms.value = []
    } finally {
      loadingNorms.value = false
    }
  }

  const defaultScopeText = computed(() => {
    const siteName = authStore.currentSite?.name || 'NOM DE LA STRUCTURE'
    return `Ce document vise à définir clairement les limites du Système de management de la qualité (SMQ) de ${siteName}.

Il s'applique à toute la documentation et activités au sein du SMQ de ${siteName}.

Les utilisateurs de ce document sont les membres de la direction de ${siteName}, les membres de l'équipe de mise en œuvre du projet de SMQ.`
  })

  const defaultObjectiveText = computed(() => {
    const siteName = authStore.currentSite?.name || 'NOM DE LA STRUCTURE'
    return `Le domaine d'application du système de management de la qualité définit les limites physiques et organisationnelles auxquelles le SMQ s'applique.

${siteName} considère son contexte, les besoins et les attentes des parties intéressées, l'étendue du contrôle et l'influence qui peuvent s'exercer sur ses activités, ses produits et ses services. Le domaine d'application est une déclaration factuelle et représentative des opérations de ${siteName}, inclue dans les limites du SMQ et est disponible pour les parties intéressées.

Tenant compte de la capacité et de la responsabilité de ${siteName} pour assurer la conformité de ses services et l'amélioration de la satisfaction client, le domaine d'application du SMQ est défini comme spécifié dans les points suivants.`
  })

  function addDocument () {
    form.value.referencedDocuments.unshift('')
  }
  function removeDocument (index: number) {
    form.value.referencedDocuments.splice(index, 1)
  }
  function addProcess () {
    form.value.processes.unshift({ name: '', type: 'realization', abbreviation: '' })
  }

  function normalizeProcessType (value: unknown): string {
    const normalized = String(value || '').toLowerCase().trim()
    if (['management', 'pilotage', 'direction', 'strategique', 'stratégique'].includes(normalized)) return 'management'
    if (['realization', 'realisation', 'réalisation', 'operationnel', 'opérationnel', 'production'].includes(normalized)) return 'realization'
    if (['support', 'supporting', 'soutien'].includes(normalized)) return 'support'
    return 'realization'
  }

  function resolveProcessName (process: any): string {
    const attributes = process?.attributes || {}
    return String(
      [
        process?.title,
        process?.name,
        process?.nom,
        attributes.title,
        attributes.name,
        attributes.nom,
        '',
      ].find(Boolean),
    ).trim()
  }

  function resolveProcessType (process: any): string {
    const attributes = process?.attributes || {}
    return normalizeProcessType(
      process?.category
        || process?.type
      || attributes.category
        || attributes.type,
    )
  }

  function getProcessFamilyOrder (type: string): number {
    if (type === 'management') return 0
    if (type === 'realization') return 1
    if (type === 'support') return 2
    return 99
  }

  function sortScopeProcesses () {
    form.value.processes = [...form.value.processes].toSorted((a, b) => {
      const familyDiff = getProcessFamilyOrder(normalizeProcessType(a?.type)) - getProcessFamilyOrder(normalizeProcessType(b?.type))
      if (familyDiff !== 0) return familyDiff
      return String(a?.name || '').localeCompare(String(b?.name || ''), 'fr', { sensitivity: 'base' })
    })
  }

  async function loadSystemProcesses () {
    const siteId = resolveCurrentSiteId()
    if (!siteId) {
      systemProcesses.value = []
      return
    }

    try {
      const { data } = await api.get('/processes', {
        params: { site_id: siteId, per_page: 500 },
      })

      const list = Array.isArray(data?.data)
        ? data.data
        : (Array.isArray(data)
          ? data
          : [])

      systemProcesses.value = list
        .map((process: any) => {
          const id = Number(process?.id || process?.attributes?.id || 0)
          const name = resolveProcessName(process)
          const type = resolveProcessType(process)
          const abbreviation = process?.abbreviation || process?.attributes?.abbreviation || ''

          return { id: id > 0 ? id : undefined, name, type, abbreviation }
        })
        .filter((process: { name: string }) => Boolean(process.name))

      const selectableProcesses = systemProcesses.value.filter(process => Boolean(process.id))
      if (!generationForm.value.processId && selectableProcesses.length === 1) {
        generationForm.value.processId = Number(selectableProcesses[0].id)
      }
    } catch (error) {
      console.error('Erreur chargement des processus système:', error)
      systemProcesses.value = []
    }
  }

  async function loadDocumentTypes () {
    const siteId = resolveCurrentSiteId()
    if (!siteId) {
      documentTypes.value = []
      return
    }

    try {
      const { data } = await api.get('/document-type-catalogs', {
        params: { site_id: siteId, is_active: true },
      })
      const list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : [])
      documentTypes.value = list.map((type: any) => ({
        id: Number(type.id),
        name: String(type.name || ''),
        abbreviation: String(type.abbreviation || '').toUpperCase(),
      })).filter(type => type.id > 0 && type.name && type.abbreviation)

      if (!generationForm.value.documentTypeCatalogId && documentTypes.value.length === 1) {
        generationForm.value.documentTypeCatalogId = documentTypes.value[0].id
      }
    } catch (error) {
      console.error('Erreur chargement types documentaires:', error)
      documentTypes.value = []
    }
  }

  function mergeSystemProcessesIntoScope () {
    const existingByName = new Map(
      (form.value.processes || [])
        .map(process => [String(process?.name || '').trim().toLowerCase(), process] as const)
        .filter(([key]) => Boolean(key)),
    )

    for (const process of systemProcesses.value) {
      const key = process.name.trim().toLowerCase()
      if (!key) continue
      const existing = existingByName.get(key)
      if (existing) {
        if (!existing.abbreviation && process.abbreviation) {
          existing.abbreviation = process.abbreviation
        }
        continue
      }
      form.value.processes.push({ name: process.name, type: process.type, abbreviation: process.abbreviation })
      existingByName.set(key, process as any)
    }

    sortScopeProcesses()
  }

  function getProcessTypeColor (type: string) {
    const colors: Record<string, string> = {
      management: '#5b8dd9',
      realization: '#22c55e',
      support: '#f59e0b',
    }
    return colors[type] || '#64748b'
  }

  function getProcessTypeLabel (type: string) {
    return processTypes.find(t => t.value === type)?.title || type
  }
  function removeProcess (index: number) {
    form.value.processes.splice(index, 1)
    sortScopeProcesses()
  }
  function addProductService () {
    form.value.productsServices.unshift('')
  }
  function removeProductService (index: number) {
    form.value.productsServices.splice(index, 1)
  }
  function addOrganizationalUnit () {
    form.value.organizationalUnits.unshift('')
  }
  function removeOrganizationalUnit (index: number) {
    form.value.organizationalUnits.splice(index, 1)
  }
  function addLocation () {
    form.value.locations.unshift('')
  }
  function removeLocation (index: number) {
    form.value.locations.splice(index, 1)
  }

  function chapterCodeOf (chapter: any) {
    return (chapter?.number || chapter?.code || '').toString().trim()
  }

  function chapterTitleOf (chapter: any) {
    return (chapter?.title || chapter?.name || '').toString().trim()
  }

  function normKey (norm: any) {
    return (norm?.code || norm?.name || '').toString().trim()
  }

  function normalizeNormLabel (value: string) {
    return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim()
  }

  function extractIsoNumber (value: string) {
    const match = String(value || '').toUpperCase().match(/ISO\s*([0-9]{4,5})/)
    return match?.[1] ?? ''
  }

  function parseObjectField (value: any): Record<string, any> {
    if (!value) return {}
    if (typeof value === 'object') return value
    if (typeof value !== 'string') return {}
    try {
      const parsed = JSON.parse(value)
      return parsed && typeof parsed === 'object' ? parsed : {}
    } catch {
      return {}
    }
  }

  function normalizeChapterCodes (input: any): string[] {
    if (!Array.isArray(input)) return []
    return [...new Set(
      input
        .map(item => String(item ?? '').trim())
        .filter(Boolean),
    )]
  }

  function normalizeNormExclusionsMap (input: Record<string, any>) {
    const normalized: Record<string, string[]> = {}
    for (const [key, value] of Object.entries(input || {})) {
      const norm = String(key || '').trim()
      if (!norm) continue
      const chapters = normalizeChapterCodes(value)
      if (chapters.length > 0) {
        normalized[norm] = chapters
      }
    }
    return normalized
  }

  function normalizeNormJustificationsByNorm (input: Record<string, string>) {
    const normalized: Record<string, string> = {}
    for (const [key, value] of Object.entries(input || {})) {
      if (!value) continue
      const normCode = key.includes('::') ? (key.split('::')[0] ?? key) : key
      if (!normalized[normCode]) {
        normalized[normCode] = value
      }
    }
    return normalized
  }

  function reconcileNormExclusionsWithSubscribedNorms () {
    const normKeys = subscribedNorms.value.map(norm => normKey(norm)).filter(Boolean)
    if (normKeys.length === 0) return

    const normalizedToActual = new Map<string, string>()
    const isoNumberToActual = new Map<string, string>()
    for (const key of normKeys) {
      normalizedToActual.set(normalizeNormLabel(key), key)
      const isoNumber = extractIsoNumber(key)
      if (isoNumber && !isoNumberToActual.has(isoNumber)) {
        isoNumberToActual.set(isoNumber, key)
      }
    }

    const remappedExclusions: Record<string, string[]> = {}
    for (const [rawKey, chapters] of Object.entries(form.value.normExclusions || {})) {
      const exactMatch = normalizedToActual.get(normalizeNormLabel(rawKey))
      const isoMatch = isoNumberToActual.get(extractIsoNumber(rawKey))
      const targetKey = exactMatch || isoMatch || rawKey

      if (!remappedExclusions[targetKey]) remappedExclusions[targetKey] = []
      const existing = remappedExclusions[targetKey] || []
      const incoming = normalizeChapterCodes(chapters)
      remappedExclusions[targetKey] = [...new Set([...existing, ...incoming])]
    }
    form.value.normExclusions = remappedExclusions

    const remappedJustifications: Record<string, string> = {}
    for (const [rawKey, text] of Object.entries(form.value.normExclusionsJustifications || {})) {
      if (!text) continue
      const exactMatch = normalizedToActual.get(normalizeNormLabel(rawKey))
      const isoMatch = isoNumberToActual.get(extractIsoNumber(rawKey))
      const targetKey = exactMatch || isoMatch || rawKey
      if (!remappedJustifications[targetKey]) {
        remappedJustifications[targetKey] = text
      }
    }
    form.value.normExclusionsJustifications = remappedJustifications
  }

  function getNormExcludedChapters (normCode: string) {
    const chapters = form.value.normExclusions[normCode]
    return Array.isArray(chapters) ? chapters : []
  }

  function isChapterExcluded (normCode: string, chapterCode: string) {
    return getNormExcludedChapters(normCode).includes(chapterCode)
  }

  function toggleChapterExclusion (normCode: string, chapterCode: string) {
    if (!form.value.normExclusions[normCode]) {
      form.value.normExclusions[normCode] = []
    }

    const index = form.value.normExclusions[normCode].indexOf(chapterCode)
    if (index === -1) {
      form.value.normExclusions[normCode].push(chapterCode)
    } else {
      form.value.normExclusions[normCode].splice(index, 1)
      if (form.value.normExclusions[normCode].length === 0) {
        delete form.value.normExclusions[normCode]
      }
    }
  }

  function getNormJustification (normCode: string) {
    return form.value.normExclusionsJustifications[normCode] || ''
  }

  function setNormJustification (normCode: string, value: string) {
    const cleaned = (value || '').trim()
    if (!cleaned) {
      delete form.value.normExclusionsJustifications[normCode]
      return
    }
    form.value.normExclusionsJustifications[normCode] = cleaned
  }

  function getNormExclusionLabel (normCode: string, chapterCode: string) {
    const norm = subscribedNorms.value.find(n => normKey(n) === normCode)
    if (!norm) return chapterCode

    for (const chapter of norm.chapters || []) {
      if (chapterCodeOf(chapter) === chapterCode) {
        const title = chapterTitleOf(chapter)
        return title ? `${chapterCode} - ${title}` : chapterCode
      }
      for (const subchapter of chapter.subchapters || []) {
        if (chapterCodeOf(subchapter) === chapterCode) {
          const title = chapterTitleOf(subchapter)
          return title ? `${chapterCode} - ${title}` : chapterCode
        }
      }
    }

    return chapterCode
  }

  const hasAnyNormExclusion = computed(() => {
    const hasChapterSelection = Object.values(form.value.normExclusions)
      .some(chapters => (chapters || []).length > 0)
    const hasPerNormText = Object.values(form.value.normExclusionsJustifications || {})
      .some(text => String(text || '').trim().length > 0)
    return hasChapterSelection || hasPerNormText
  })

  function isBlank (value: string) {
    return !String(value || '').trim()
  }

  function hasBlankItems (items: string[]) {
    return items.some(item => isBlank(item))
  }

  function hasInvalidProcesses () {
    return form.value.processes.some(process => isBlank(process.name) || isBlank(process.type))
  }

  function hasMissingNormJustification () {
    for (const [normCode, chapters] of Object.entries(form.value.normExclusions || {})) {
      if (!Array.isArray(chapters) || chapters.length === 0) continue

      if (isBlank(form.value.normExclusionsJustifications[normCode] || '')) {
        return true
      }
    }

    return false
  }

  function getStepValidationMessage (step: number): string | null {
    switch (step) {
      case 1: {
        return hasBlankItems(form.value.referencedDocuments)
          ? 'Étape Documents: complétez ou supprimez les lignes vides.'
          : null
      }
      case 2: {
        if (isBlank(form.value.documentObjective)) {
          return 'Étape Définition: l’objectif du document est requis.'
        }
        if (isBlank(form.value.scopeDefinition)) {
          return 'Étape Définition: la définition du domaine est requise.'
        }
        return null
      }
      case 3: {
        return hasInvalidProcesses()
          ? 'Étape Processus: chaque processus doit avoir un nom et un type.'
          : null
      }
      case 4: {
        return hasBlankItems(form.value.productsServices)
          ? 'Étape Produits et services: complétez ou supprimez les lignes vides.'
          : null
      }
      case 5: {
        return hasBlankItems(form.value.organizationalUnits)
          ? 'Étape Unités: complétez ou supprimez les lignes vides.'
          : null
      }
      case 6: {
        return hasBlankItems(form.value.locations)
          ? 'Étape Lieux: complétez ou supprimez les lignes vides.'
          : null
      }
      case 7: {
        return hasMissingNormJustification()
          ? 'Étape Exclusions: ajoutez une justification pour chaque norme avec exclusions.'
          : null
      }
      default: {
        return null
      }
    }
  }

  function validateSteps (startStep: number, endStep: number) {
    for (let step = startStep; step <= endStep; step++) {
      const validationMessage = getStepValidationMessage(step)
      if (!validationMessage) continue

      currentStep.value = step
      toast.error(validationMessage)
      return false
    }

    return true
  }

  function handleStepChange (targetStep: number) {
    if (targetStep <= currentStep.value) {
      currentStep.value = Math.max(FIRST_STEP, targetStep)
      return
    }

    const canMove = validateSteps(currentStep.value, targetStep - 1)
    if (!canMove) return

    currentStep.value = Math.min(SUMMARY_STEP, targetStep)
  }

  function goToNextStep () {
    handleStepChange(currentStep.value + 1)
  }

  function resetScopeFormToDefaults () {
    currentScopeId.value = null
    form.value.referencedDocuments = []
    form.value.documentObjective = defaultScopeText.value
    form.value.scopeDefinition = defaultObjectiveText.value
    form.value.processes = []
    form.value.productsServices = []
    form.value.organizationalUnits = []
    form.value.locations = []
    form.value.scopeExclusions = ''
    form.value.isoExclusions = []
    form.value.isoExclusionsJustification = ''
    form.value.normExclusions = {}
    form.value.normExclusionsJustifications = {}
  }

  function resolveCurrentScopePayload (data: any) {
    return data?.data?.[0]?.attributes || data?.data?.[0] || null
  }

  function applyScopeToForm (scope: any, scopeId: any) {
    currentScopeId.value = scopeId || scope.id
    form.value.referencedDocuments = scope.referenced_documents || []
    form.value.documentObjective = scope.document_objective || defaultScopeText.value
    form.value.scopeDefinition = scope.scope_definition || defaultObjectiveText.value
    form.value.processes = (scope.processes || []).map((process: any) => ({
      id: Number(process?.id || 0) || undefined,
      name: String(process?.name || '').trim(),
      type: normalizeProcessType(process?.type),
      abbreviation: String(process?.abbreviation || '').trim().toUpperCase(),
    }))
    form.value.productsServices = scope.products_services || []
    form.value.organizationalUnits = scope.organizational_units || []
    form.value.locations = scope.locations || []
    form.value.scopeExclusions = scope.scope_exclusions || ''
    form.value.isoExclusions = scope.iso_exclusions ? scope.iso_exclusions.split(', ').filter(Boolean) : []
    form.value.isoExclusionsJustification = scope.iso_exclusions_justification || ''
    form.value.normExclusions = normalizeNormExclusionsMap(
      parseObjectField(scope.norm_exclusions) as Record<string, any>,
    )
    form.value.normExclusionsJustifications = normalizeNormJustificationsByNorm(
      parseObjectField(scope.norm_exclusions_justifications) as Record<string, string>,
    )
    sortScopeProcesses()
  }

  async function loadCurrentScope () {
    const siteId = resolveCurrentSiteId()
    if (!siteId) {
      resetScopeFormToDefaults()
      return
    }

    try {
      const { data } = await api.get('/application-scopes', {
        params: { site_id: siteId, is_current: true },
      })
      const scope = resolveCurrentScopePayload(data)
      if (scope) {
        applyScopeToForm(scope, data?.data?.[0]?.id)
        await loadSystemProcesses()
        mergeSystemProcessesIntoScope()
        reconcileNormExclusionsWithSubscribedNorms()
      } else {
        resetScopeFormToDefaults()
        await loadSystemProcesses()
        mergeSystemProcessesIntoScope()
      }
    } catch (error) {
      console.error('Erreur chargement:', error)
      resetScopeFormToDefaults()
      await loadSystemProcesses()
      mergeSystemProcessesIntoScope()
    }
  }

  watch(form, () => { verificationSent.value = false }, { deep: true })

  onMounted(async () => {
    if (!resolveCurrentSiteId()) return

    await Promise.all([loadSubscribedNorms(), loadCurrentScope(), loadDocumentTypes()])
    reconcileNormExclusionsWithSubscribedNorms()
  })

  watch(
    () => authStore.currentSiteId,
    async (siteId, previousSiteId) => {
      if (siteId === previousSiteId) return

      if (!resolveCurrentSiteId()) {
        subscribedNorms.value = []
        documentTypes.value = []
        generationForm.value.documentTypeCatalogId = null
        generationForm.value.processId = null
        resetScopeFormToDefaults()
        return
      }

      generationForm.value.documentTypeCatalogId = null
      generationForm.value.processId = null
      await Promise.all([loadSubscribedNorms(), loadCurrentScope(), loadDocumentTypes()])
      reconcileNormExclusionsWithSubscribedNorms()
    },
  )

  async function persistCurrentScope (options: { showSuccess?: boolean } = {}): Promise<boolean> {
    const siteId = resolveCurrentSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return false
    }

    const formIsValid = validateSteps(FIRST_STEP, SUMMARY_STEP - 1)
    if (!formIsValid) return false

    saving.value = true
    try {
      sortScopeProcesses()
      const payload = {
        site_id: siteId,
        referenced_documents: form.value.referencedDocuments,
        document_objective: form.value.documentObjective,
        scope_definition: form.value.scopeDefinition,
        processes: form.value.processes,
        products_services: form.value.productsServices,
        organizational_units: form.value.organizationalUnits,
        locations: form.value.locations,
        scope_exclusions: form.value.scopeExclusions,
        iso_exclusions: form.value.isoExclusions.join(', '),
        iso_exclusions_justification: form.value.isoExclusionsJustification,
        norm_exclusions: form.value.normExclusions,
        norm_exclusions_justifications: form.value.normExclusionsJustifications,
      }

      let savedScope: any = null
      if (currentScopeId.value) {
        const { data } = await api.put(`/application-scopes/${currentScopeId.value}`, payload)
        savedScope = data?.data?.attributes || data?.data || null
      } else {
        const { data } = await api.post('/application-scopes', payload)
        currentScopeId.value = data?.data?.id || null
        savedScope = data?.data?.attributes || data?.data || null
      }

      if (savedScope) {
        applyScopeToForm(savedScope, currentScopeId.value)
      }
      await loadSystemProcesses()
      if (options.showSuccess !== false) {
        toast.success('Domaine d\'application enregistré.')
      }
      return true
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l\'enregistrement.')
      return false
    } finally {
      saving.value = false
    }
  }

  async function handleSave () {
    await persistCurrentScope()
  }

  async function ensureProcessesAreCodifiable (): Promise<boolean> {
    await loadSystemProcesses()

    if (processOptions.value.length > 0) return true
    if (!form.value.processes.some(process => String(process?.name || '').trim())) return false

    const saved = await persistCurrentScope({ showSuccess: false })
    if (!saved) return false

    await loadSystemProcesses()
    return processOptions.value.length > 0
  }

  function resolveSelectedScopeProcess () {
    const value = generationForm.value.processId
    if (typeof value !== 'string' || !value.startsWith('scope:')) return null

    const index = Number(value.replace('scope:', ''))
    const process = form.value.processes[index]
    if (!process || !String(process.name || '').trim()) return null

    return process
  }

  async function openGenerationDialog (action: 'draft' | 'preview' | 'download' | 'verify' = 'draft') {
    if (!currentScopeId.value) {
      toast.error('Veuillez d\'abord enregistrer le domaine d\'application.')
      return
    }

    generationAction.value = action
    loadingGenerationOptions.value = true
    generationDialog.value = true
    try {
      await Promise.all([loadDocumentTypes(), ensureProcessesAreCodifiable()])
    } finally {
      loadingGenerationOptions.value = false
    }
  }

  async function confirmGenerationConfig () {
    if (!canGenerateDocument.value) return
    generationDialog.value = false
    if (generationAction.value === 'draft') {
      await generateDraftAndStore()
    } else if (generationAction.value === 'preview') {
      await generateDraftAndStore()
      if (lastExportedDocumentId.value) await handlePreviewDraft()
    } else if (generationAction.value === 'download') {
      await generateDraftAndStore()
      if (lastExportedDocumentId.value) await handleDownloadDraft()
    } else if (generationAction.value === 'verify') {
      await generateDraftAndStore()
      if (lastExportedDocumentId.value) verifyDialog.value = true
    }
  }

  function extractDocumentInfo (payload: any): { id: number | null, code: string } {
    const data = payload?.data ?? payload
    const attributes = data?.attributes ?? data
    const id = Number(data?.id || attributes?.id || 0) || null
    const code = String(attributes?.code || '')
    return { id, code }
  }

  function buildGenerationParams (): Record<string, number | string | null> {
    const selectedScopeProcess = resolveSelectedScopeProcess()
    const generationParams: Record<string, number | string | null> = {
      document_type_catalog_id: generationForm.value.documentTypeCatalogId,
    }

    if (selectedScopeProcess) {
      generationParams.process_name = selectedScopeProcess.name
      generationParams.process_type = selectedScopeProcess.type
      generationParams.process_abbreviation = selectedScopeProcess.abbreviation || ''
    } else {
      generationParams.process_id = generationForm.value.processId
    }

    return generationParams
  }

  async function generateDraftAndStore (): Promise<boolean> {
    if (!currentScopeId.value) {
      toast.error('Veuillez d\'abord enregistrer le domaine d\'application.')
      return false
    }
    if (!canGenerateDocument.value) return false

    exporting.value = true
    try {
      const response = await api.post(`/application-scopes/${currentScopeId.value}/generate-draft`, {
        ...buildGenerationParams(),
      })
      const info = extractDocumentInfo(response.data)
      if (info.id) {
        lastExportedDocumentId.value = info.id
        exportedDocumentCode.value = info.code
        return true
      }
      toast.error('Impossible de générer le brouillon.')
      return false
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors de la génération du brouillon.')
      return false
    } finally {
      exporting.value = false
    }
  }

  const siteName = () => authStore.currentSite?.name || 'Site'

  const {
    exporting,
    submittingForVerification,
    verificationSent,
    lastExportedDocumentId,
    exportedDocumentCode,
    previewDialog,
    previewBlobUrl,
    previewFilename,
    verifyDialog,
    ensureDraftReady,
    handlePreviewDraft,
    closePreview,
    handleDownloadDraft,
    openVerifyDialog,
    submitForVerification,
  } = useDocumentFlow(
    async () => {
      if (!currentScopeId.value) {
        toast.error('Veuillez d\'abord enregistrer le domaine d\'application.')
        return null
      }
      if (!canGenerateDocument.value) {
        await openGenerationDialog('draft')
        return null
      }
      const response = await api.post(`/application-scopes/${currentScopeId.value}/generate-draft`, {
        ...buildGenerationParams(),
      })
      const info = extractDocumentInfo(response.data)
      return info.id ? info as { id: number, code: string } : null
    },
    () => `Domaine_Application_${siteName()}_${new Date().toISOString().split('T')[0]}.pdf`,
  )
</script>

<style scoped>
:deep(.stepper-container) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

:deep(.step-item) {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  cursor: pointer;
  transition: all 0.3s ease;
}

:deep(.step-item:hover .step-circle) {
  transform: scale(1.1);
}

:deep(.step-circle) {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 16px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 8px rgba(15, 23, 42, 0.12);
  z-index: 2;
}

:deep(.step-item.active .step-circle) {
  transform: scale(1.08);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.2);
}

:deep(.step-number) {
  color: white;
  font-weight: bold;
}

:deep(.step-label) {
  margin-top: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  transition: all 0.3s ease;
}

:deep(.step-item.active .step-label) {
  color: #1e293b;
  font-size: 13px;
}

:deep(.step-line) {
  position: absolute;
  top: 22px;
  left: 50%;
  width: 100%;
  height: 3px;
  background: #e2e8f0;
  z-index: 1;
  transition: all 0.3s ease;
}

:deep(.step-line.filled) {
  background: #22c55e;
}

:deep(.hover-lift) {
  transition: all 0.2s ease;
}

:deep(.hover-lift:hover) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
}

.navigation-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
</style>
