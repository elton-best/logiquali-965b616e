<template>
  <div class="code-preview-panel">
    <v-card class="sticky-preview" elevation="2" rounded="lg">
      <v-card-title class="d-flex align-center gap-2 pb-2">
        <v-icon color="primary">mdi-eye-outline</v-icon>
        <span>Aperçu du code</span>
      </v-card-title>

      <!-- Format Display -->
      <v-card-text class="pa-3">
        <!-- Format String -->
        <div class="mb-4">
          <div class="text-caption text-medium-emphasis font-weight-bold mb-2">
            Format généré:
          </div>
          <div class="format-box">
            <code>{{ format || '{...}' }}</code>
          </div>
        </div>

        <!-- Parts List -->
        <div class="mb-4">
          <div class="text-caption text-medium-emphasis font-weight-bold mb-2">
            Parties ({{ parts.length }}):
          </div>
          <div class="parts-breakdown">
            <div v-for="(part, index) in parts" :key="index" class="part-row">
              <v-chip
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
        </div>

        <!-- Preview Example -->
        <div class="mb-4">
          <div class="text-caption text-medium-emphasis font-weight-bold mb-2">
            📋 Exemple généré:
          </div>
          <div class="preview-box">
            <code class="example-code">{{ preview || 'N/A' }}</code>
          </div>
          <v-btn
            class="mt-2"
            size="small"
            variant="text"
            @click="copyToClipboard"
          >
            <v-icon size="18" start>mdi-content-copy</v-icon>
            Copier
          </v-btn>
        </div>

        <!-- Legend -->
        <v-divider class="my-3" />

        <div class="text-caption text-medium-emphasis">
          <div class="font-weight-bold mb-2">📚 Légende des tokens:</div>
          <div class="legend-items">
            <div v-for="token in tokens" :key="token.name" class="legend-item">
              <v-chip class="mr-2" color="info" size="small" variant="tonal">
                {{ token.name }}
              </v-chip>
              <span class="text-caption">{{ token.description }}</span>
            </div>
          </div>
        </div>

        <!-- Info Alert -->
        <v-alert
          v-if="parts.length === 0"
          class="mt-4"
          density="comfortable"
          type="info"
        >
          Commencez par ajouter des éléments pour voir un aperçu
        </v-alert>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
  const props = defineProps<{
    parts: Array<{ type: string, token?: string, value?: string }>
    format: string
    preview: string
  }>()

  const tokens = [
    {
      name: 'TYPE',
      description: 'Code type document (ex: POL)',
    },
    {
      name: 'PROCESSUS',
      description: 'Code processus (ex: PLT)',
    },
    {
      name: 'YEAR',
      description: 'Année (ex: 2026)',
    },
    {
      name: 'MONTH',
      description: 'Mois (01-12)',
    },
    {
      name: 'NUMERO',
      description: 'Numéro séquentiel (ex: 00001)',
    },
  ]

  async function copyToClipboard () {
    if (!props.preview) return

    try {
      await navigator.clipboard.writeText(props.preview)
      // Show toast or snackbar
      console.log('Code copié!')
    } catch (error) {
      console.error('Erreur lors de la copie:', error)
    }
  }
</script>

<style scoped lang="scss">
.code-preview-panel {
  .sticky-preview {
    position: sticky;
    top: 20px;
    background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%);
  }

  .format-box {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 12px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 12px;
    overflow-x: auto;
    color: #333;

    code {
      color: #2e7d32;
      font-weight: 500;
    }
  }

  .preview-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 2px solid #2196f3;
    border-radius: 6px;
    padding: 16px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    color: #1565c0;
    letter-spacing: 1px;

    .example-code {
      color: #1565c0;
    }
  }

  .parts-breakdown {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    padding: 8px;
    background: #f9f9f9;
    border-radius: 4px;

    .part-row {
      display: inline-block;
    }
  }

  .legend-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 8px;
    background: #f5f5f5;
    border-radius: 4px;

    .legend-item {
      display: flex;
      align-items: center;
    }
  }
}
</style>
