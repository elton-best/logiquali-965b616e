<template>
  <div class="nomenclature-template-builder">
    <v-alert
      v-if="error"
      class="mb-4"
      closable
      density="comfortable"
      type="error"
      variant="tonal"
      @click:close="error = null"
    >
      {{ error }}
    </v-alert>

    <!-- Sélection du type de document -->
    <v-card class="mb-4" elevation="0" rounded="lg">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        <v-icon class="mr-2" color="primary">mdi-file-document-outline</v-icon>
        Type de document
      </v-card-title>
      <v-card-text>
        <v-select
          v-model="selectedDocumentType"
          clearable
          density="comfortable"
          hide-details
          :items="documentTypeOptions"
          label="Sélectionner un type de document"
          variant="outlined"
        />
      </v-card-text>
    </v-card>

    <!-- Builder de structure -->
    <v-card elevation="0" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center">
          <v-icon class="mr-2" color="primary">mdi-code-braces</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Structure du code</span>
        </div>
        <v-chip color="info" size="small" variant="tonal">
          {{ parts.length }} partie(s)
        </v-chip>
      </v-card-title>
      <v-card-text>
        <!-- Liste des parties (drag & drop) -->
        <draggable
          v-model="parts"
          class="parts-list"
          handle=".drag-handle"
          item-key="id"
          @end="updateOrders"
        >
          <template #item="{ element, index }">
            <v-card
              class="part-card mb-3"
              :class="{ 'part-locked': element.type === 'sequential_number' }"
              elevation="1"
              rounded="lg"
            >
              <v-card-text class="pa-3">
                <div class="d-flex align-center ga-3">
                  <!-- Drag handle -->
                  <v-icon
                    class="drag-handle"
                    :class="{ 'cursor-not-allowed': element.type === 'sequential_number' }"
                    color="grey"
                    size="20"
                  >
                    {{ element.type === 'sequential_number' ? 'mdi-lock' : 'mdi-drag-vertical' }}
                  </v-icon>

                  <!-- Ordre -->
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ index + 1 }}
                  </v-chip>

                  <!-- Type -->
                  <v-select
                    v-model="element.type"
                    class="flex-grow-0"
                    density="compact"
                    :disabled="element.type === 'sequential_number'"
                    hide-details
                    :items="partTypeOptions"
                    style="max-width: 200px"
                    variant="outlined"
                    @update:model-value="onPartTypeChange(element)"
                  />

                  <!-- Label -->
                  <v-text-field
                    v-model="element.label"
                    class="flex-grow-1"
                    density="compact"
                    :disabled="element.type === 'sequential_number'"
                    hide-details
                    placeholder="Libellé"
                    variant="outlined"
                  />

                  <!-- Longueur -->
                  <v-text-field
                    v-model.number="element.length"
                    class="flex-grow-0"
                    density="compact"
                    :disabled="element.type === 'sequential_number' || element.auto"
                    hide-details
                    max="20"
                    min="1"
                    placeholder="Long."
                    style="max-width: 80px"
                    type="number"
                    variant="outlined"
                  />

                  <!-- Valeur par défaut -->
                  <v-text-field
                    v-if="element.type !== 'sequential_number'"
                    v-model="element.value"
                    class="flex-grow-0"
                    density="compact"
                    :disabled="!element.editable"
                    hide-details
                    placeholder="Valeur"
                    style="max-width: 120px"
                    variant="outlined"
                  />

                  <!-- Actions -->
                  <v-btn
                    v-if="element.type !== 'sequential_number'"
                    color="error"
                    icon="mdi-delete-outline"
                    size="small"
                    variant="text"
                    @click="removePart(index)"
                  />
                </div>
              </v-card-text>
            </v-card>
          </template>
        </draggable>

        <!-- Bouton ajouter une partie -->
        <v-btn
          block
          color="primary"
          prepend-icon="mdi-plus"
          variant="tonal"
          @click="addPart"
        >
          Ajouter une partie
        </v-btn>
      </v-card-text>
    </v-card>

    <!-- Séparateur et prévisualisation -->
    <v-card class="mt-4" elevation="0" rounded="lg">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        <v-icon class="mr-2" color="primary">mdi-eye-outline</v-icon>
        Prévisualisation
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-select
              v-model="separator"
              density="comfortable"
              hide-details
              :items="separatorOptions"
              label="Séparateur"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="9">
            <v-alert
              border="start"
              color="info"
              density="comfortable"
              icon="mdi-barcode-scan"
              variant="tonal"
            >
              <div class="d-flex align-center justify-space-between flex-wrap ga-3">
                <div>
                  <div class="text-caption text-medium-emphasis">Code généré</div>
                  <div class="text-h6 font-weight-bold font-monospace">
                    {{ previewCode || 'Configurez la structure' }}
                  </div>
                </div>
                <v-btn
                  color="primary"
                  :disabled="!canGeneratePreview"
                  :loading="loadingPreview"
                  prepend-icon="mdi-refresh"
                  size="small"
                  variant="tonal"
                  @click="generatePreview"
                >
                  Rafraîchir
                </v-btn>
              </div>
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Actions -->
    <div class="d-flex justify-end ga-2 mt-4">
      <v-btn variant="text" @click="$emit('cancel')">
        Annuler
      </v-btn>
      <v-btn
        color="primary"
        :disabled="!canSave"
        :loading="saving"
        prepend-icon="mdi-content-save"
        variant="flat"
        @click="save"
      >
        Enregistrer
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import draggable from 'vuedraggable'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'

  interface Part {
    id: string
    order: number
    type: string
    label: string
    length: number
    value: string | null
    editable: boolean
    auto: boolean
  }

  interface Props {
    documentTypes?: Array<{ id: number, name: string, abbreviation: string }>
    initialTemplate?: any
  }

  const props = withDefaults(defineProps<Props>(), {
    documentTypes: () => [],
    initialTemplate: null,
  })

  const emit = defineEmits<{
    (e: 'save', template: any): void
    (e: 'cancel'): void
  }>()

  const toast = useToast()

  const selectedDocumentType = ref<number | null>(null)
  const parts = ref<Part[]>([])
  const separator = ref('-')
  const previewCode = ref('')
  const loadingPreview = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)

  const partTypeOptions = [
    { title: 'Type de document', value: 'document_type' },
    { title: 'Code processus', value: 'process_code' },
    { title: 'Code sous-processus', value: 'subprocess_code' },
    { title: 'Personnalisé', value: 'custom' },
    { title: 'Numéro séquentiel', value: 'sequential_number' },
  ]

  const separatorOptions = [
    { title: 'Tiret (-)', value: '-' },
    { title: 'Underscore (_)', value: '_' },
    { title: 'Point (.)', value: '.' },
    { title: 'Slash (/)', value: '/' },
  ]

  const documentTypeOptions = computed(() => {
    return props.documentTypes.map(dt => ({
      title: `${dt.name} (${dt.abbreviation})`,
      value: dt.id,
    }))
  })

  const canGeneratePreview = computed(() => {
    return parts.value.some(p => p.type === 'sequential_number')
  })

  const canSave = computed(() => {
    return (
      selectedDocumentType.value !== null
      && parts.value.some(p => p.type === 'sequential_number')
      && parts.value.every(p => p.label.trim() !== '' && p.length > 0)
    )
  })

  function addPart () {
    const newPart: Part = {
      id: `part-${Date.now()}`,
      order: parts.value.length + 1,
      type: 'custom',
      label: '',
      length: 3,
      value: null,
      editable: true,
      auto: false,
    }
    parts.value.push(newPart)
  }

  function removePart (index: number) {
    parts.value.splice(index, 1)
    updateOrders()
  }

  function updateOrders () {
    for (const [index, part] of parts.value.entries()) {
      part.order = index + 1
    }
  }

  function onPartTypeChange (part: Part) {
    if (part.type === 'sequential_number') {
      part.label = 'Numéro séquentiel'
      part.auto = true
      part.editable = false
      part.value = null
    } else {
      part.auto = false
      part.editable = true
    }
  }

  async function generatePreview () {
    if (!canGeneratePreview.value) return

    loadingPreview.value = true
    error.value = null

    try {
      const response = await api.post('/nomenclature-templates/preview-code', {
        format_structure: parts.value.map(p => ({
          order: p.order,
          type: p.type,
          label: p.label,
          length: p.length,
          value: p.value,
          editable: p.editable,
          auto: p.auto,
        })),
        separator: separator.value,
      })

      previewCode.value = response.data?.data?.preview || ''
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la génération de la prévisualisation.'
      console.error(error_)
    } finally {
      loadingPreview.value = false
    }
  }

  async function save () {
    if (!canSave.value) return

    saving.value = true
    error.value = null

    try {
      const template = {
        document_type_catalog_id: selectedDocumentType.value,
        name: `Template ${props.documentTypes.find(dt => dt.id === selectedDocumentType.value)?.name || 'Document'}`,
        separator: separator.value,
        format_structure: parts.value.map(p => ({
          order: p.order,
          type: p.type,
          label: p.label,
          length: p.length,
          value: p.value,
          editable: p.editable,
          auto: p.auto,
        })),
        preview_example: previewCode.value,
        status: 'draft',
        is_active: true,
      }

      emit('save', template)
      toast.success('Template enregistré avec succès.')
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de l\'enregistrement.'
      console.error(error_)
    } finally {
      saving.value = false
    }
  }

  function initializeFromTemplate () {
    if (props.initialTemplate) {
      // Charger depuis le template existant
      selectedDocumentType.value = props.initialTemplate.document_type_catalog_id
      separator.value = props.initialTemplate.separator || '-'
      parts.value = (props.initialTemplate.format_structure || []).map((p: any, index: number) => ({
        id: `part-${index}`,
        ...p,
      }))
      previewCode.value = props.initialTemplate.preview_example || ''
    } else {
      // Initialiser avec une structure par défaut
      parts.value = [
        {
          id: 'part-1',
          order: 1,
          type: 'document_type',
          label: 'Type de document',
          length: 3,
          value: null,
          editable: false,
          auto: false,
        },
        {
          id: 'part-2',
          order: 2,
          type: 'process_code',
          label: 'Code processus',
          length: 2,
          value: null,
          editable: true,
          auto: false,
        },
        {
          id: 'part-3',
          order: 3,
          type: 'sequential_number',
          label: 'Numéro séquentiel',
          length: 3,
          value: null,
          editable: false,
          auto: true,
        },
      ]
      separator.value = '-'
    }
  }

  watch(
    () => [parts.value.length, separator.value],
    () => {
      if (canGeneratePreview.value) {
        generatePreview()
      }
    },
    { deep: true },
  )

  onMounted(() => {
    initializeFromTemplate()
    if (canGeneratePreview.value) {
      generatePreview()
    }
  })
</script>

<style scoped>
.nomenclature-template-builder {
  max-width: 100%;
}

.parts-list {
  min-height: 100px;
}

.part-card {
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.part-card:hover {
  border-color: rgba(91, 141, 217, 0.3);
}

.part-locked {
  background: rgba(158, 158, 158, 0.05);
}

.drag-handle {
  cursor: grab;
}

.drag-handle:active {
  cursor: grabbing;
}

.cursor-not-allowed {
  cursor: not-allowed !important;
}

.font-monospace {
  font-family: 'Courier New', Courier, monospace;
}
</style>
