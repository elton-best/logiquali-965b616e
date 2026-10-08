<template>
  <div class="step-3-naming-pattern">
    <v-row class="ga-4">
      <!-- Left: Builder -->
      <v-col cols="12" md="8">
        <v-card elevation="0" rounded="lg">
          <v-card-title class="d-flex align-center gap-2">
            <v-icon color="primary">mdi-code-braces</v-icon>
            <span>Étape 3: Configuration du format de codification</span>
          </v-card-title>
          <v-card-text>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Construisez le format de codification en combinant des éléments (type, processus, année, etc.)
            </p>

            <v-alert
              v-if="!isValid"
              class="mb-4"
              density="comfortable"
              type="warning"
            >
              ⚠️ Vous devez configurer au moins 1 partie pour continuer
            </v-alert>

            <!-- Document Type Selection -->
            <v-card class="mb-4" elevation="0" rounded="lg" variant="tonal">
              <v-card-text>
                <div class="text-caption font-weight-bold mb-2">Appliquer à :</div>
                <v-select
                  v-model="selectedDocType"
                  clearable
                  density="comfortable"
                  item-title="name"
                  item-value="id"
                  :items="documentTypes"
                  label="Sélectionner un type de document (optionnel)"
                  variant="outlined"
                />
              </v-card-text>
            </v-card>

            <!-- Parts Builder -->
            <div class="mb-4">
              <div class="text-subtitle-2 font-weight-bold mb-2">🧩 Éléments du code</div>
              <div class="d-flex gap-2 flex-wrap mb-3">
                <v-btn size="small" variant="tonal" @click="addToken('TYPE')">
                  + TYPE
                </v-btn>
                <v-btn size="small" variant="tonal" @click="addToken('PROCESSUS')">
                  + PROCESSUS
                </v-btn>
                <v-btn size="small" variant="tonal" @click="addToken('YEAR')">
                  + YEAR
                </v-btn>
                <v-btn size="small" variant="tonal" @click="addToken('MONTH')">
                  + MONTH
                </v-btn>
                <v-btn color="primary" size="small" variant="tonal" @click="addToken('NUMERO')">
                  + NUMERO
                </v-btn>
                <v-btn size="small" variant="outlined" @click="addSeparator('/')">
                  + /
                </v-btn>
                <v-btn size="small" variant="outlined" @click="addSeparator('-')">
                  + -
                </v-btn>
              </div>

              <!-- Parts List -->
              <draggable
                v-model="namingPattern.parts"
                class="parts-list"
                handle=".drag-handle"
                item-key="id"
              >
                <template #item="{ element, index }">
                  <v-card class="part-item mb-2" elevation="1" rounded="lg">
                    <v-card-text class="d-flex align-center gap-2 pa-3">
                      <!-- Drag Handle -->
                      <v-icon
                        class="drag-handle"
                        color="grey"
                        size="20"
                      >
                        mdi-drag-vertical
                      </v-icon>

                      <!-- Order Badge -->
                      <v-chip color="primary" size="small" variant="tonal">
                        {{ index + 1 }}
                      </v-chip>

                      <!-- Type Display -->
                      <div class="flex-grow-1">
                        <template v-if="element.type === 'token'">
                          <v-chip color="info" size="small" variant="tonal">
                            {{ element.token }}
                          </v-chip>
                        </template>
                        <template v-else>
                          <code class="text-caption">{{ element.value }}</code>
                        </template>
                      </div>

                      <!-- Delete -->
                      <v-btn
                        color="error"
                        icon="mdi-close"
                        size="x-small"
                        variant="text"
                        @click="removePart(index)"
                      />
                    </v-card-text>
                  </v-card>
                </template>
              </draggable>

              <div v-if="namingPattern.parts.length === 0" class="text-center py-6 text-medium-emphasis">
                Aucune partie définie.
                <br>
                <v-btn
                  color="primary"
                  size="small"
                  variant="text"
                  @click="addToken('TYPE')"
                >
                  Commencer par ajouter le TYPE →
                </v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right: Preview -->
      <v-col cols="12" md="4">
        <CodePreviewPanel
          :format="namingPattern.format"
          :parts="namingPattern.parts"
          :preview="namingPattern.preview"
        />
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentTypeCatalog } from '../../../types/document.types'
  import { computed, ref, watch } from 'vue'
  import draggable from 'vuedraggable'
  import CodePreviewPanel from '../CodePreviewPanel.vue'

  interface Props {
    documentTypes: DocumentTypeCatalog[]
    namingPattern: {
      parts: Array<{ type: string, token?: string, value?: string, id?: string }>
      format: string
      preview: string
    }
    selectedDocType: number | null
    isValid: boolean
  }

  interface Emits {
    (e: 'update:namingPattern', value: Props['namingPattern']): void
    (e: 'update:selectedDocType', value: number | null): void
  }

  const props = withDefaults(defineProps<Props>(), {
    documentTypes: () => [],
    isValid: false,
  })

  const emit = defineEmits<Emits>()

  // Computed
  const namingPattern = computed({
    get: () => props.namingPattern,
    set: value => emit('update:namingPattern', value),
  })

  const selectedDocType = computed({
    get: () => props.selectedDocType,
    set: value => emit('update:selectedDocType', value),
  })

  // Methods
  function addToken (token: string) {
    const newPart = {
      id: `part-${Date.now()}`,
      type: 'token',
      token,
    }
    namingPattern.value = {
      ...namingPattern.value,
      parts: [...namingPattern.value.parts, newPart],
    }
  }

  function addSeparator (value: string) {
    const newPart = {
      id: `sep-${Date.now()}`,
      type: 'separator',
      value,
    }
    namingPattern.value = {
      ...namingPattern.value,
      parts: [...namingPattern.value.parts, newPart],
    }
  }

  function removePart (index: number) {
    const newParts = namingPattern.value.parts.filter((_, i) => i !== index)
    namingPattern.value = {
      ...namingPattern.value,
      parts: newParts,
    }
  }

  // Update format string when parts change
  watch(
    () => namingPattern.value.parts,
    parts => {
      const format = parts
        .map(p => (p.type === 'token' ? `{${p.token}}` : p.value))
        .join('')

      namingPattern.value = {
        ...namingPattern.value,
        format,
        preview: generatePreview(parts),
      }
    },
    { deep: true },
  )

  function generatePreview (parts: any[]): string {
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')

    return parts
      .map(p => {
        switch (p.token) {
          case 'TYPE': {
            return 'POL'
          }
          case 'PROCESSUS': {
            return 'PLT'
          }
          case 'YEAR': {
            return String(year)
          }
          case 'MONTH': {
            return month
          }
          case 'NUMERO': {
            return '00001'
          }
          default: {
            return p.value || ''
          }
        }
      })
      .join('')
  }
</script>

<style scoped lang="scss">
.step-3-naming-pattern {
  .parts-list {
    min-height: 80px;
  }

  .part-item {
    transition: all 0.2s ease;

    &:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
  }
}
</style>
