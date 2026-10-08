<template>
  <v-dialog :model-value="modelValue" max-width="640" @update:model-value="$emit('update:modelValue', $event)">
    <v-card rounded="xl">
      <v-card-title class="pa-4">{{ title }}</v-card-title>
      <v-card-text class="pa-4">
        <v-alert class="mb-4" type="info" variant="tonal">
          Sélectionnez le type documentaire et le processus à utiliser pour générer le code du brouillon.
        </v-alert>

        <v-select
          v-model="documentTypeCatalogId"
          class="mb-3"
          density="comfortable"
          item-title="title"
          item-value="value"
          :items="documentTypeOptions"
          label="Type documentaire"
          variant="outlined"
        />

        <v-select
          v-model="processId"
          density="comfortable"
          :disabled="Boolean(fixedProcessId)"
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
          Aucun processus enregistré n'est disponible pour ce site.
        </v-alert>
      </v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">Annuler</v-btn>
        <v-btn
          color="primary"
          :disabled="!canConfirm"
          :loading="loading || optionsLoading"
          @click="confirm"
        >
          Continuer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import api from '@/api/client'

  const props = withDefaults(defineProps<{
    modelValue: boolean
    siteId: number | null
    fixedProcessId?: number | null
    loading?: boolean
    title?: string
  }>(), {
    fixedProcessId: null,
    loading: false,
    title: 'Paramètres du document généré',
  })

  type GeneratedDocumentContext = {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    confirm: [value: GeneratedDocumentContext]
  }>()

  const documentTypes = ref<Array<{ id: number, name: string, abbreviation: string }>>([])
  const processes = ref<Array<{ id: number, name: string, abbreviation?: string }>>([])
  const scopeProcesses = ref<Array<{ id?: number, name: string, type?: string, abbreviation?: string }>>([])
  const documentTypeCatalogId = ref<number | null>(null)
  const processId = ref<number | string | null>(null)
  const optionsLoading = ref(false)

  const documentTypeOptions = computed(() =>
    documentTypes.value.map(type => ({
      title: `${type.name} (${type.abbreviation})`,
      value: type.id,
    })),
  )

  function normalizeProcessName (value: string): string {
    return value.trim().replace(/\s+/g, ' ').toLowerCase()
  }

  const processOptions = computed(() => {
    const options: Array<{ title: string, value: number | string }> = []
    const ids = new Set(processes.value.map(process => Number(process.id)))
    const names = new Set(processes.value.map(process => normalizeProcessName(process.name)))

    processes.value.forEach(process => {
      options.push({
        title: `${process.name}${process.abbreviation ? ` (${process.abbreviation})` : ''}`,
        value: process.id,
      })
    })

    scopeProcesses.value.forEach((process, index) => {
      const name = String(process?.name || '').trim()
      if (!name) return
      const normalized = normalizeProcessName(name)
      const id = Number(process?.id || 0)
      if (id > 0 && ids.has(id)) return
      if (!id && names.has(normalized)) return

      options.push({
        title: `${name}${process?.abbreviation ? ` (${process.abbreviation})` : ''}`,
        value: id > 0 ? id : `scope:${index}`,
      })
    })

    return options
  })

  const canConfirm = computed(() =>
    Boolean(documentTypeCatalogId.value && processId.value),
  )

  function resolveProcessName (process: any): string {
    return String(process?.title || process?.name || process?.attributes?.title || process?.attributes?.name || '').trim()
  }

  function resolveProcessType (process: any): string {
    return String(process?.type || process?.category || process?.attributes?.type || process?.attributes?.category || '').trim()
  }

  function resolveSelectedScopeProcess (): { id?: number, name: string, type?: string, abbreviation?: string } | null {
    const selected = processId.value
    if (!selected || typeof selected !== 'string' || !selected.startsWith('scope:')) {
      return null
    }
    const index = Number(selected.replace('scope:', ''))
    const process = scopeProcesses.value[index]
    if (!process || !process.name) return null
    return process
  }

  async function loadOptions () {
    if (!props.siteId) {
      documentTypes.value = []
      processes.value = []
      scopeProcesses.value = []
      return
    }

    optionsLoading.value = true
    try {
      const [typeResponse, processResponse, scopeResponse] = await Promise.all([
        api.get('/document-type-catalogs', { params: { site_id: props.siteId, is_active: true } }),
        api.get('/processes', { params: { site_id: props.siteId, per_page: 500 } }),
        api.get('/application-scopes', { params: { site_id: props.siteId, is_current: true, per_page: 1 } }),
      ])

      const rawTypes = Array.isArray(typeResponse.data)
        ? typeResponse.data
        : (Array.isArray(typeResponse.data?.data) ? typeResponse.data.data : [])
      documentTypes.value = rawTypes
        .map((type: any) => ({
          id: Number(type.id),
          name: String(type.name || ''),
          abbreviation: String(type.abbreviation || '').toUpperCase(),
        }))
        .filter((type: { id: number, name: string, abbreviation: string }) => type.id > 0 && type.name && type.abbreviation)

      const rawProcesses = Array.isArray(processResponse.data?.data)
        ? processResponse.data.data
        : (Array.isArray(processResponse.data) ? processResponse.data : [])
      processes.value = rawProcesses
        .map((process: any) => ({
          id: Number(process?.id || process?.attributes?.id || 0),
          name: resolveProcessName(process),
          abbreviation: String(process?.abbreviation || process?.attributes?.abbreviation || ''),
        }))
        .filter((process: { id: number, name: string }) => process.id > 0 && process.name)

      const scopeRecord = Array.isArray(scopeResponse.data?.data)
        ? scopeResponse.data.data[0]
        : null
      const scopeAttributes = scopeRecord?.attributes ?? scopeRecord ?? {}
      const scopeProcessList = Array.isArray(scopeAttributes?.processes) ? scopeAttributes.processes : []
      scopeProcesses.value = scopeProcessList
        .map((process: any) => ({
          id: Number(process?.id || 0) || undefined,
          name: String(process?.name || process?.title || '').trim(),
          type: resolveProcessType(process),
          abbreviation: String(process?.abbreviation || '').trim(),
        }))
        .filter((process: { name: string }) => Boolean(process.name))

      if (!documentTypeCatalogId.value && documentTypes.value.length === 1) {
        documentTypeCatalogId.value = documentTypes.value[0].id
      }
      if (props.fixedProcessId) {
        processId.value = props.fixedProcessId
      } else if (!processId.value && processOptions.value.length === 1) {
        processId.value = processOptions.value[0].value
      }
    } finally {
      optionsLoading.value = false
    }
  }

  function confirm () {
    if (!documentTypeCatalogId.value || !processId.value) return

    const payload: GeneratedDocumentContext = {
      document_type_catalog_id: documentTypeCatalogId.value,
    }
    const selectedScopeProcess = resolveSelectedScopeProcess()
    if (typeof processId.value === 'number') {
      payload.process_id = processId.value
    } else if (selectedScopeProcess) {
      payload.process_id = selectedScopeProcess.id ?? null
      payload.process_name = selectedScopeProcess.name
      payload.process_type = selectedScopeProcess.type
      payload.process_abbreviation = selectedScopeProcess.abbreviation
    }

    emit('confirm', payload)
  }

  watch(() => props.modelValue, value => {
    if (value) void loadOptions()
  })

  watch(() => props.fixedProcessId, value => {
    if (value) processId.value = value
  }, { immediate: true })

  watch(() => props.siteId, () => {
    documentTypeCatalogId.value = null
    processId.value = props.fixedProcessId ?? null
    scopeProcesses.value = []
    if (props.modelValue) void loadOptions()
  })
</script>
