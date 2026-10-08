<template>
  <v-dialog
    v-model="isOpen"
    max-width="1000px"
    scrollable
    @update:model-value="handleClose"
  >
    <v-card rounded="xl" class="document-preview-modal">
      <!-- Header -->
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-light-blue-50">
        <div>
          <h2 class="text-h6 font-weight-bold">Aperçu du document</h2>
          <p class="text-body-2 text-medium-emphasis mt-1">
            {{ documentTitle }}
            <span v-if="documentVersion" class="ml-2">v{{ documentVersion }}</span>
          </p>
        </div>
        <v-btn icon size="small" variant="text" @click="handleClose">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <!-- Preview Content -->
      <v-card-text class="pa-6">
        <div v-if="loading" class="d-flex justify-center align-center py-16">
          <div class="text-center">
            <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4" />
            <p class="text-medium-emphasis">Chargement du document...</p>
          </div>
        </div>

        <div v-else-if="error" class="d-flex justify-center align-center py-16">
          <div class="text-center text-error">
            <v-icon size="x-large" class="mb-4">mdi-alert-circle</v-icon>
            <p>{{ error }}</p>
            <v-btn size="small" variant="tonal" color="primary" @click="retryLoad" class="mt-4">
              <v-icon start>mdi-refresh</v-icon>
              Réessayer
            </v-btn>
          </div>
        </div>

        <div v-else-if="document">
          <!-- Document Info -->
          <v-row class="mb-6">
            <v-col cols="12" md="6">
              <div class="border-l-4 border-primary pl-4">
                <p class="text-overline text-medium-emphasis">Code</p>
                <p class="text-body-1 font-weight-medium">{{ document.code || 'N/A' }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="border-l-4 border-success pl-4">
                <p class="text-overline text-medium-emphasis">Version</p>
                <p class="text-body-1 font-weight-medium">{{ document.version || '1.0' }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="border-l-4 border-warning pl-4">
                <p class="text-overline text-medium-emphasis">Type</p>
                <p class="text-body-1 font-weight-medium">{{ document.type || 'N/A' }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="border-l-4 border-info pl-4">
                <p class="text-overline text-medium-emphasis">Statut</p>
                <v-chip
                  :color="getStatusConfig(document).color"
                  size="small"
                  variant="tonal"
                >
                  {{ getStatusLabel(document) }}
                </v-chip>
              </div>
            </v-col>
          </v-row>

          <v-divider class="my-6" />

          <!-- Preview -->
          <div class="preview-container bg-grey-100 rounded-lg pa-4" :style="{ minHeight: '500px' }">
            <DocumentPreview
              :document="document"
              :document-id="document.id"
              :is-inventory="false"
              :height="500"
              @download="$emit('download')"
            />
          </div>
        </div>

        <div v-else class="d-flex justify-center align-center py-16">
          <p class="text-medium-emphasis">Aucun document à afficher</p>
        </div>
      </v-card-text>

      <!-- Footer Actions -->
      <v-divider />
      <v-card-actions class="pa-6 d-flex justify-space-between">
        <v-btn variant="tonal" color="primary" @click="$emit('download')">
          <v-icon start>mdi-download</v-icon>
          Télécharger
        </v-btn>
        <v-btn variant="text" @click="handleClose">Fermer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
  import { getDocumentBusinessStatusConfig, getDocumentBusinessStatusLabel } from '@/modules/clienta/utils/documentStatus'
  import DocumentPreview from '@/components/documents/DocumentPreview.vue'

  interface Props {
    modelValue: boolean
    document?: UnifiedDocument | null
    loading?: boolean
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'download'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<Emits>()

  const isOpen = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  const error = ref<string | null>(null)
  const retryCount = ref(0)

  const documentTitle = computed(() => {
    if (!props.document) return ''
    return props.document.title || props.document.nom || props.document.code || 'Document'
  })

  const documentVersion = computed(() => {
    if (!props.document) return ''
    return props.document.version || '1.0'
  })

  function getStatusConfig(doc: any) {
    return getDocumentBusinessStatusConfig(doc)
  }

  function getStatusLabel(doc: any) {
    return getDocumentBusinessStatusLabel(doc)
  }

  function retryLoad() {
    if (retryCount.value < 3) {
      retryCount.value++
      error.value = null
    }
  }

  function handleClose() {
    error.value = null
    retryCount.value = 0
    isOpen.value = false
  }

  watch(
    () => props.modelValue,
    (newVal) => {
      if (!newVal) {
        error.value = null
      }
    }
  )
</script>

<style scoped>
  .document-preview-modal {
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  }

  .preview-container {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: auto;
    background-color: #f5f5f5;
  }

  .border-l-4 {
    border-left-width: 4px;
    border-left-style: solid;
  }
</style>
