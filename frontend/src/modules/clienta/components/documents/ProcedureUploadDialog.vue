<template>
  <v-dialog v-model="dialogModel" max-width="860" persistent scrollable>
    <v-card class="dialog-card" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="primary">mdi-file-document-edit-outline</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">Ajouter une procédure</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-card-text class="pt-6">
        <v-row>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="form.nom"
              label="Nom de la procédure *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.code"
              label="Code procédure (optionnel)"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.type"
              :items="typeOptions"
              label="Type *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="8">
            <v-select
              v-model="form.processus"
              clearable
              item-title="title"
              item-value="value"
              :items="processOptions"
              :label="form.type === 'PRC' || form.type === 'PRD' ? 'Processus lié *' : 'Processus lié'"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.statut"
              :items="statutOptions"
              label="Statut *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.date_creation"
              label="Date de création *"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.validated_at"
              :disabled="form.statut !== 'valide'"
              label="Date de validation"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-file-input
              accept=".pdf,.doc,.docx,.xls,.xlsx"
              hint="Le fichier est obligatoire pour l'ajout d'une procédure."
              label="Fichier (PDF, Word, Excel) *"
              persistent-hint
              prepend-icon="mdi-paperclip"
              rounded="lg"
              show-size
              variant="outlined"
              @update:model-value="onFileSelected"
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              label="Description"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions class="pa-6 bg-grey-lighten-5">
        <v-spacer />
        <v-btn variant="text" @click="close">Annuler</v-btn>
        <v-btn color="primary" :loading="submitting" @click="save">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, reactive, ref, watch } from 'vue'
  import api from '@/api/client'
  import { documentsApi } from '@/api/documents'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  type DocumentType = 'PRC' | 'PRD'
  type DocumentStatut = 'brouillon' | 'en_revision' | 'valide'

  interface ProcessOption {
    id?: number
    value: string
    title: string
  }

  const props = defineProps<{
    modelValue: boolean
    defaultProcessId?: number | null
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'created', id: number): void
  }>()

  const toast = useToast()
  const authStore = useAuthStore()
  const processOptions = ref<ProcessOption[]>([])
  const submitting = ref(false)
  const selectedFile = ref<File | null>(null)

  const dialogModel = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const typeOptions = [
    { title: 'Procédure', value: 'PRC' },
    { title: 'Procédure détaillée', value: 'PRD' },
  ]

  const statutOptions = [
    { title: 'brouillon en attente de vérification', value: 'brouillon' },
    { title: 'brouillon en cours de vérification', value: 'en_revision' },
    { title: 'validé - version 1', value: 'valide' },
  ]

  const form = reactive({
    nom: '',
    code: '',
    type: 'PRC' as DocumentType,
    processus: null as string | null,
    statut: 'brouillon' as DocumentStatut,
    date_creation: new Date().toISOString().split('T')[0],
    validated_at: '',
    description: '',
  })

  watch(
    () => props.modelValue,
    async value => {
      if (!value) return
      if (!currentSiteId()) {
        toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
        dialogModel.value = false
        return
      }
      resetForm()
      await loadProcesses()
      hydrateDefaultProcess()
    },
  )

  function currentSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId
      ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
      ?? (authStore.user as { site_id?: number } | null)?.site_id
      ?? null
  }

  async function loadProcesses (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      processOptions.value = []
      return
    }

    try {
      const response = await processService.getProcesses({ site_id: siteId }, 1, 300)
      const apiOptions = (response.data || [])
        .map(process => {
          const code = String(process.code || process.ref || '').trim()
          const name = String(process.name || process.title || '').trim()
          if (!name && !code) return null
          return {
            id: Number(process.id),
            title: code && name ? `${code} - ${name}` : (name || code),
            value: code || name,
          }
        })
        .filter(Boolean) as ProcessOption[]

      let scopeOptions: ProcessOption[] = []
      try {
        let scopeResponse = await api.get('/application-scopes', {
          params: { site_id: siteId, is_current: true },
        })
        let dataRows = Array.isArray(scopeResponse.data?.data) ? scopeResponse.data.data : []
        if (dataRows.length === 0) {
          scopeResponse = await api.get('/application-scopes', {
            params: { site_id: siteId },
          })
          dataRows = Array.isArray(scopeResponse.data?.data) ? scopeResponse.data.data : []
        }

        const list = dataRows.flatMap((row: any) => {
          const scope = row?.attributes || row
          return Array.isArray(scope?.processes) ? scope.processes : []
        })

        scopeOptions = list
          .map((process: any) => {
            const code = String(process?.code || '').trim()
            const name = String(process?.name || process?.title || '').trim()
            if (!name && !code) return null
            return {
              title: code && name ? `${code} - ${name}` : (name || code),
              value: code || name,
            }
          })
          .filter(Boolean) as ProcessOption[]
      } catch {
        scopeOptions = []
      }

      const merged = [...apiOptions]
      const seenIds = new Set(
        apiOptions
          .map(item => Number(item.id || 0))
          .filter(id => Number.isFinite(id) && id > 0),
      )
      const seenSemanticNames = new Set(
        apiOptions
          .map(item => semanticProcessName(item.title || item.value || ''))
          .filter(Boolean),
      )

      for (const option of scopeOptions) {
        const optionId = Number(option.id || 0)
        const semanticName = semanticProcessName(option.title || option.value || '')

        // Évite les doublons API/scope (même id ou même nom métier avec/sans code).
        if (optionId > 0 && seenIds.has(optionId)) continue
        if (semanticName && seenSemanticNames.has(semanticName)) continue

        merged.push(option)
        if (optionId > 0) seenIds.add(optionId)
        if (semanticName) seenSemanticNames.add(semanticName)
      }

      processOptions.value = merged
    } catch {
      processOptions.value = []
    }
  }

  function hydrateDefaultProcess (): void {
    if (!props.defaultProcessId) return
    const match = processOptions.value.find(option => option.id === props.defaultProcessId)
    if (match) {
      form.processus = match.value
    }
  }

  function semanticProcessName (raw: string): string {
    const value = String(raw || '')
    const base = value.includes(' - ')
      ? value.split(' - ').at(-1)
      : value

    return base
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036F]/g, '')
      .replace(/\s+/g, ' ')
      .trim()
  }

  function isAllowedProcessForSelectedSite (processCode: string | null): boolean {
    if (!processCode) return false
    return processOptions.value.some(item => item.value === processCode)
  }

  function resetForm (): void {
    form.nom = ''
    form.code = ''
    form.type = 'PRC'
    form.processus = null
    form.statut = 'brouillon'
    form.date_creation = new Date().toISOString().split('T')[0]
    form.validated_at = ''
    form.description = ''
    selectedFile.value = null
  }

  function close (): void {
    dialogModel.value = false
    resetForm()
  }

  function toFile (value: unknown): File | null {
    if (value instanceof File) return value

    if (value && typeof value === 'object') {
      const maybeRaw = (value as { raw?: unknown }).raw
      if (maybeRaw instanceof File) return maybeRaw

      const maybeFile = (value as { file?: unknown }).file
      if (maybeFile instanceof File) return maybeFile
    }

    return null
  }

  function onFileSelected (value: File | File[] | null): void {
    if (Array.isArray(value)) {
      selectedFile.value = toFile(value[0])
      return
    }
    selectedFile.value = toFile(value)
  }

  function buildFormData (): FormData {
    const siteId = currentSiteId()
    const formData = new FormData()

    formData.append('site_id', String(siteId))
    formData.append('processus', String(form.processus || ''))
    formData.append('type', form.type)
    formData.append('statut', form.statut)
    // Champ maintenu côté payload pour compatibilité backend, masqué en interface.
    formData.append('etat', 'a_etablir')
    formData.append('nom', form.nom)
    formData.append('date_creation', form.date_creation)

    if (form.code.trim()) formData.append('code', form.code.trim())
    if (form.description.trim()) formData.append('description', form.description.trim())
    if (form.validated_at && form.statut === 'valide') formData.append('validated_at', form.validated_at)
    if (selectedFile.value) formData.append('fichier', selectedFile.value)

    return formData
  }

  async function save (): Promise<void> {
    const siteId = currentSiteId()
    if (!siteId) {
      toast.error('Sélectionnez d’abord un site dans la barre supérieure.')
      return
    }

    if (!form.nom.trim()) {
      toast.error('Le nom de la procédure est obligatoire.')
      return
    }

    if (!form.processus) {
      toast.error('Sélectionnez un processus lié.')
      return
    }
    if (!isAllowedProcessForSelectedSite(form.processus)) {
      toast.error('Le processus choisi ne correspond pas au site sélectionné.')
      return
    }
    if (!selectedFile.value) {
      toast.error('Le fichier est obligatoire pour l\'ajout d\'une procédure.')
      return
    }

    submitting.value = true
    try {
      const formData = buildFormData()
      const response = await documentsApi.create(formData)
      const createdId = Number((response as any)?.data?.id || 0)
      toast.success('Procédure ajoutée avec succès.')
      emit('created', createdId || 0)
      close()
    } catch {
      toast.error('Impossible d’ajouter la procédure.')
    } finally {
      submitting.value = false
    }
  }
</script>

<style scoped>
.dialog-card {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
