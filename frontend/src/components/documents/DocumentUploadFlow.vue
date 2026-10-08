<template>
  <div>
    <div class="mb-4 d-flex ga-3 flex-wrap">
      <v-select
        v-model="selectedLegacyType"
        density="comfortable"
        :items="documentTypeOptions"
        label="Type de document *"
        style="min-width: 240px"
        variant="outlined"
      />
      <v-select
        v-model="selectedProcessId"
        density="comfortable"
        item-title="label"
        item-value="value"
        :items="processOptions"
        label="Processus *"
        style="min-width: 320px"
        variant="outlined"
      />
    </div>

    <!-- Bouton ou Input pour déclencher l'upload -->
    <v-btn
      color="primary"
      :disabled="!selectedLegacyType || !selectedProcessId"
      prepend-icon="mdi-cloud-upload"
      @click="triggerFilePicker"
    >
      Importer un document
    </v-btn>

    <input
      ref="fileInput"
      accept=".pdf,.doc,.docx"
      style="display: none"
      type="file"
      @change="handleFileSelect"
    >

    <!-- Modale de Vérification du Code (Étape 2) -->
    <v-dialog v-model="showCodeVerificationModal" max-width="600" persistent>
      <v-card rounded="xl">
        <v-card-title class="bg-primary text-white pa-4 d-flex align-center">
          <v-icon start>mdi-shield-check</v-icon>
          Vérification de la conformité
        </v-card-title>

        <v-card-text class="pt-6">
          <p class="text-body-1 mb-4">
            Le code unique généré pour ce type de document est :
          </p>

          <v-card class="pa-4 mb-6 text-center" color="info" variant="tonal">
            <div class="text-h4 font-weight-bold letter-spacing-1">
              {{ generatedCode }}
            </div>
          </v-card>

          <p class="text-body-1 font-weight-medium mb-4">
            Ce code est-il strictement identique à celui inscrit dans votre fichier ?
          </p>

          <v-expand-transition>
            <div v-if="showWarning" class="mb-4">
              <v-alert density="compact" type="error" variant="tonal">
                Veuillez modifier le code directement dans votre fichier (Word/PDF) pour qu'il corresponde à <strong>{{ generatedCode }}</strong>, puis recommencez l'importation.
              </v-alert>
            </div>
          </v-expand-transition>

          <v-divider class="my-4" />

          <div v-if="!showWarning">
            <h4 class="text-subtitle-1 font-weight-bold mb-2">Options du workflow</h4>
            <v-checkbox
              v-model="needsVerification"
              color="primary"
              hide-details
              label="Ce document nécessite-t-il une vérification avant approbation ?"
            />
            <p class="text-caption text-medium-emphasis ml-8">
              Si décoché, le document passera directement à l'étape d'approbation.
            </p>
          </div>
        </v-card-text>

        <v-card-actions class="pa-4 bg-grey-lighten-4">
          <v-btn variant="text" @click="cancelUpload">Annuler</v-btn>
          <v-spacer />
          <template v-if="!showWarning">
            <v-btn color="error" variant="outlined" @click="showWarning = true">
              Non, le code est différent
            </v-btn>
            <v-btn color="success" :loading="uploading" variant="elevated" @click="confirmAndUpload">
              Oui, confirmer l'import
            </v-btn>
          </template>
          <template v-else>
            <v-btn color="primary" variant="elevated" @click="cancelUpload">
              J'ai compris
            </v-btn>
          </template>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  type ProcessInput = {
    id: number
    title?: string
    code?: string
  }

  type SourceContextInput = {
    moduleType?: string
    moduleId?: number | null
    sourceType?: string
    sourceModule?: string
    sourceSubmodule?: string
    sourceSection?: string
  }

  const props = defineProps<{
    processes?: ProcessInput[]
    siteId?: number | null
    sourceContext?: SourceContextInput
  }>()

  const emit = defineEmits(['upload-success'])
  const toast = useToast()
  const authStore = useAuthStore()

  const fileInput = ref<HTMLInputElement | null>(null)
  const selectedFile = ref<File | null>(null)
  const showCodeVerificationModal = ref(false)
  const showWarning = ref(false)
  const generatedCode = ref('')
  const needsVerification = ref(true)
  const uploading = ref(false)
  const selectedLegacyType = ref<'POL' | 'PRC' | 'PRD' | 'FOR' | 'ENR'>('PRC')
  const selectedProcessId = ref<number | null>(null)

  const documentTypeOptions = [
    { title: 'Politique', value: 'POL' },
    { title: 'Procédure', value: 'PRC' },
    { title: 'Procédure détaillée', value: 'PRD' },
    { title: 'Formulaire', value: 'FOR' },
    { title: 'Enregistrement', value: 'ENR' },
  ] as const

  const legacyToModernTypeMap: Record<string, string> = {
    POL: 'policy',
    PRC: 'procedure',
    PRD: 'instruction',
    FOR: 'form',
    ENR: 'record',
  }

  const processOptions = computed(() => {
    return (props.processes || []).map(process => ({
      value: process.id,
      label: `${process.code || '???'} - ${process.title || `Processus #${process.id}`}`,
    }))
  })

  const selectedProcessCode = computed(() => {
    const process = (props.processes || []).find(p => Number(p.id) === Number(selectedProcessId.value))
    return process?.code || null
  })

  const selectedModernType = computed(() => legacyToModernTypeMap[selectedLegacyType.value] || 'record')

  function triggerFilePicker () {
    fileInput.value?.click()
  }

  async function handleFileSelect (event: Event) {
    const target = event.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
      selectedFile.value = target.files[0]
      await fetchGeneratedCode()
    }
  }

  async function fetchGeneratedCode () {
    try {
      const siteId = props.siteId
        || authStore.currentSiteId
        || (authStore.user as { site_id?: number } | null)?.site_id
      if (!siteId || !selectedProcessId.value || !selectedProcessCode.value) {
        toast.error('Sélectionnez un type et un processus avant l’import.')
        return
      }
      const response = await api.get('/documents/preview-code', {
        params: {
          type: selectedLegacyType.value,
          site_id: siteId,
          process_id: selectedProcessId.value,
          processus: selectedProcessCode.value,
        },
      })
      generatedCode.value = response.data?.data?.code || response.data?.code || 'PRC-001'

      showWarning.value = false
      needsVerification.value = true
      showCodeVerificationModal.value = true
    } catch {
      toast.error('Impossible de générer le code du document.')
    }
  }

  function cancelUpload () {
    showCodeVerificationModal.value = false
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
  }

  async function confirmAndUpload () {
    if (!selectedFile.value) return

    try {
      uploading.value = true
      const siteId = props.siteId
        || authStore.currentSiteId
        || (authStore.user as { site_id?: number } | null)?.site_id
      if (!siteId || !selectedProcessId.value) {
        toast.error('Sélectionnez un processus valide.')
        return
      }
      const filename = selectedFile.value.name.replace(/\.[^/.]+$/, '')
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('title', filename || 'Document importé')
      formData.append('type', selectedModernType.value)
      formData.append('code', generatedCode.value)
      formData.append('site_id', String(siteId))
      formData.append('process_id', String(selectedProcessId.value))
      formData.append('module_type', props.sourceContext?.moduleType || 'information_documentee')
      formData.append('source_type', props.sourceContext?.sourceType || 'information_documentee')
      if (props.sourceContext?.moduleId) {
        formData.append('module_id', String(props.sourceContext.moduleId))
      }
      if (props.sourceContext?.sourceModule) {
        formData.append('source_module', props.sourceContext.sourceModule)
      }
      if (props.sourceContext?.sourceSubmodule) {
        formData.append('source_submodule', props.sourceContext.sourceSubmodule)
      }
      if (props.sourceContext?.sourceSection) {
        formData.append('source_section', props.sourceContext.sourceSection)
      }

      // Création initiale du document (Draft)
      const response = await api.post('/documents', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const documentId = Number(response.data?.data?.id || response.data?.id)
      if (!Number.isFinite(documentId) || documentId <= 0) {
        throw new Error('Réponse invalide: document_id manquant')
      }

      // Lancement du workflow (Confirmer le code)
      await api.post(`/documents/${documentId}/confirm-code`, {
        needs_verification: needsVerification.value,
        confirmed_code: generatedCode.value,
      })

      toast.success('Document importé et envoyé dans le circuit de validation.')
      showCodeVerificationModal.value = false
      emit('upload-success')
    } catch {
      toast.error('Erreur lors de l\'importation du document.')
    } finally {
      uploading.value = false
      if (fileInput.value) fileInput.value.value = ''
    }
  }
</script>

<style scoped>
.letter-spacing-1 {
  letter-spacing: 2px;
}
</style>
