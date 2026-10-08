<template>
  <div class="step-4-review-publish">
    <v-row class="ga-4">
      <!-- Left: Summary -->
      <v-col cols="12" md="8">
        <v-card class="mb-4" elevation="0" rounded="lg">
          <v-card-title class="d-flex align-center gap-2">
            <v-icon color="success">mdi-check-circle-outline</v-icon>
            <span>Étape 4: Résumé et publication</span>
          </v-card-title>
          <v-card-text>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Vérifiez les informations saisies avant de publier votre configuration de nomenclature.
            </p>

            <!-- Document Types Summary -->
            <v-card class="mb-4" elevation="0" rounded="lg" variant="tonal">
              <v-card-title class="text-subtitle-2">
                📄 Types documentaires ({{ documentTypes.length }})
              </v-card-title>
              <v-card-text>
                <div class="summary-list">
                  <div v-for="type in documentTypes" :key="type.id" class="summary-item">
                    <v-icon color="primary" size="20">mdi-file-document-outline</v-icon>
                    <div class="flex-grow-1">
                      <div class="font-weight-bold">{{ type.name }}</div>
                      <div class="text-caption text-medium-emphasis">
                        Abréviation: <code>{{ type.abbreviation }}</code>
                      </div>
                    </div>
                    <v-chip
                      :color="type.is_active ? 'success' : 'grey'"
                      size="small"
                      variant="tonal"
                    >
                      {{ type.is_active ? 'Actif' : 'Inactif' }}
                    </v-chip>
                  </div>
                </div>
              </v-card-text>
            </v-card>

            <!-- Process Mapping Summary -->
            <v-card class="mb-4" elevation="0" rounded="lg" variant="tonal">
              <v-card-title class="text-subtitle-2">
                ⚙️ Processus mappés ({{ processMappingArray.length }})
              </v-card-title>
              <v-card-text>
                <div v-if="processMappingArray.length > 0" class="summary-list">
                  <div v-for="[processId, mapping] in processMappingArray" :key="processId" class="summary-item">
                    <v-icon color="primary" size="20">mdi-network</v-icon>
                    <div class="flex-grow-1">
                      <div class="font-weight-bold">{{ mapping.title }}</div>
                      <div class="text-caption text-medium-emphasis">
                        ID: {{ processId }}
                      </div>
                    </div>
                    <v-chip color="success" size="small" variant="tonal">
                      ✓ Mappé
                    </v-chip>
                  </div>
                </div>
                <div v-else class="text-center py-4 text-medium-emphasis">
                  Aucun processus mappé
                </div>
              </v-card-text>
            </v-card>

            <!-- Naming Pattern Summary -->
            <v-card elevation="0" rounded="lg" variant="tonal">
              <v-card-title class="text-subtitle-2">
                🔤 Format de codification
              </v-card-title>
              <v-card-text>
                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis mb-2">Format:</div>
                  <div class="format-display">
                    <code>{{ namingPattern.format }}</code>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="text-caption text-medium-emphasis mb-2">Exemple généré:</div>
                  <div class="example-display">
                    <code>{{ namingPattern.preview }}</code>
                  </div>
                </div>

                <div>
                  <div class="text-caption text-medium-emphasis mb-2">Parties ({{ namingPattern.parts.length }}):</div>
                  <div class="parts-display">
                    <v-chip
                      v-for="(part, index) in namingPattern.parts"
                      :key="index"
                      class="ma-1"
                      :color="part.type === 'token' ? 'info' : 'grey'"
                      size="small"
                      variant="tonal"
                    >
                      <template v-if="part.type === 'token'">
                        {{ part.token }}
                      </template>
                      <template v-else>
                        {{ part.value }}
                      </template>
                    </v-chip>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right: Checklist & Actions -->
      <v-col cols="12" md="4">
        <v-card class="sticky-checklist" elevation="2" rounded="lg">
          <v-card-title class="text-subtitle-2 pb-2">
            ✅ Checklist final
          </v-card-title>
          <v-card-text class="pa-3">
            <!-- Checks -->
            <div class="checklist-items mb-4">
              <div class="checklist-item">
                <v-icon
                  :color="documentTypes.length > 0 ? 'success' : 'grey'"
                  size="20"
                >
                  {{ documentTypes.length > 0 ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
                <span class="ml-2 text-caption">
                  Types documentaires configurés
                  <span v-if="documentTypes.length > 0" class="font-weight-bold">
                    ({{ documentTypes.length }})
                  </span>
                </span>
              </div>

              <div class="checklist-item">
                <v-icon
                  :color="processMappingArray.length > 0 ? 'success' : 'grey'"
                  size="20"
                >
                  {{ processMappingArray.length > 0 ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
                <span class="ml-2 text-caption">
                  Processus sélectionnés
                  <span v-if="processMappingArray.length > 0" class="font-weight-bold">
                    ({{ processMappingArray.length }})
                  </span>
                </span>
              </div>

              <div class="checklist-item">
                <v-icon
                  :color="namingPattern.parts.length > 0 ? 'success' : 'grey'"
                  size="20"
                >
                  {{ namingPattern.parts.length > 0 ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
                <span class="ml-2 text-caption">
                  Format de code défini
                  <span v-if="namingPattern.parts.length > 0" class="font-weight-bold">
                    ({{ namingPattern.parts.length }} parties)
                  </span>
                </span>
              </div>

              <div class="checklist-item">
                <v-icon
                  :color="allValid ? 'success' : 'grey'"
                  size="20"
                >
                  {{ allValid ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
                <span class="ml-2 text-caption font-weight-bold">
                  ✓ Prêt à publier
                </span>
              </div>
            </div>

            <!-- Publication Info -->
            <v-alert
              v-if="allValid"
              class="mb-4"
              density="comfortable"
              type="success"
            >
              Tous les éléments sont configurés. Vous pouvez publier votre nomenclature.
            </v-alert>

            <v-alert
              v-else
              class="mb-4"
              density="comfortable"
              type="warning"
            >
              Veuillez compléter tous les éléments avant de pouvoir publier.
            </v-alert>

            <!-- Statistics -->
            <v-divider class="my-3" />

            <div class="stats-section text-caption">
              <div class="stat-row">
                <span class="text-medium-emphasis">Documents</span>
                <span class="font-weight-bold">{{ documentTypes.length }}</span>
              </div>
              <div class="stat-row">
                <span class="text-medium-emphasis">Processus</span>
                <span class="font-weight-bold">{{ processMappingArray.length }}</span>
              </div>
              <div class="stat-row">
                <span class="text-medium-emphasis">Parties</span>
                <span class="font-weight-bold">{{ namingPattern.parts.length }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentTypeCatalog } from '../../../types/document.types'
  import { computed } from 'vue'

  interface Props {
    documentTypes: DocumentTypeCatalog[]
    selectedProcess: any | null
    processMapping: Map<number, any>
    namingPattern: {
      parts: Array<{ type: string, token?: string, value?: string }>
      format: string
      preview: string
    }
    selectedDocType: number | null
    isSubmitting: boolean
  }

  interface Emits {
    (e: 'publish'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    documentTypes: () => [],
    processMapping: () => new Map(),
  })

  defineEmits<Emits>()

  // Computed
  const processMappingArray = computed(() => {
    return Array.from(props.processMapping.entries())
  })

  const allValid = computed(() => {
    return (
      props.documentTypes.length > 0
      && processMappingArray.value.length > 0
      && props.namingPattern.parts.length > 0
    )
  })
</script>

<style scoped lang="scss">
.step-4-review-publish {
  .sticky-checklist {
    position: sticky;
    top: 20px;
  }

  .summary-list {
    display: flex;
    flex-direction: column;
    gap: 12px;

    .summary-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      background: rgba(0, 0, 0, 0.02);
      border-radius: 6px;

      &:hover {
        background: rgba(0, 0, 0, 0.05);
      }
    }
  }

  .format-display,
  .example-display {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 12px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 12px;
    overflow-x: auto;

    code {
      color: #2e7d32;
      font-weight: 500;
    }
  }

  .example-display {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 2px solid #2196f3;

    code {
      color: #1565c0;
      font-weight: 600;
      font-size: 14px;
    }
  }

  .parts-display {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    padding: 8px;
    background: #f9f9f9;
    border-radius: 4px;
  }

  .checklist-items {
    display: flex;
    flex-direction: column;
    gap: 8px;

    .checklist-item {
      display: flex;
      align-items: center;
      padding: 8px;
      border-radius: 4px;
      transition: all 0.2s ease;

      &:hover {
        background: rgba(0, 0, 0, 0.02);
      }
    }
  }

  .stats-section {
    display: flex;
    flex-direction: column;
    gap: 6px;

    .stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 6px 0;
      border-bottom: 1px solid rgba(0, 0, 0, 0.06);

      &:last-child {
        border-bottom: none;
      }
    }
  }
}
</style>
