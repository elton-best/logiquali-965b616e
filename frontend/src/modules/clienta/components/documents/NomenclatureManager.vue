<template>
  <div class="nomenclature-manager">
    <div class="manager-intro">
      <div class="intro-title">Codification documentaire par processus</div>
      <p class="intro-subtitle">
        Définissez le code et le format de codification utilisés lors de la génération des documents.
      </p>
      <div class="mt-3">
        <v-btn color="primary" prepend-icon="mdi-plus" variant="tonal" @click="addGlobalRow">
          Ajouter une nomenclature sans processus
        </v-btn>
      </div>
    </div>

    <v-row class="ga-0">
      <v-col
        v-for="row in rows"
        :key="row.processId"
        cols="12"
      >
        <v-card class="nomenclature-row" rounded="xl" variant="flat">
          <v-card-text class="pa-5">
            <div class="row-head">
              <div>
                <div class="d-flex align-center ga-2 flex-wrap">
                  <h4 class="text-subtitle-1 font-weight-bold mb-0">{{ row.processTitle }}</h4>
                  <v-chip
                    :color="row.actif ? 'success' : 'grey'"
                    size="small"
                    variant="tonal"
                  >
                    {{ row.actif ? 'Actif' : 'Inactif' }}
                  </v-chip>
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  <template v-if="row.processId">Processus ID: {{ row.processId }}</template>
                  <template v-else>Portée: Site (globale)</template>
                </div>
              </div>
              <div class="d-flex align-center ga-2 flex-wrap">
                <v-switch
                  v-model="row.actif"
                  color="success"
                  density="compact"
                  hide-details
                  inset
                  label="Actif"
                />
                <v-tooltip text="Supprimer cette nomenclature">
                  <template #activator="{ props: tooltipProps }">
                    <span v-bind="tooltipProps">
                      <v-btn
                        color="error"
                        :disabled="!row.nomenclatureId"
                        icon="mdi-delete-outline"
                        size="small"
                        variant="tonal"
                        @click="row.nomenclatureId && $emit('delete', row.nomenclatureId)"
                      />
                    </span>
                  </template>
                </v-tooltip>
              </div>
            </div>

            <v-row class="mt-1" dense>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="row.code"
                  class="field-modern"
                  density="comfortable"
                  :error="!isValidRow(row)"
                  label="Code processus (2-12 caractères)"
                  maxlength="12"
                  placeholder="PLT"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="row.processTitle"
                  class="field-modern"
                  density="comfortable"
                  label="Libellé"
                  placeholder="Nomenclature globale"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="row.format"
                  class="field-modern"
                  density="comfortable"
                  label="Format du code"
                  placeholder="{TYPE}/{PROCESSUS}/{NUMERO}"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <v-divider class="my-3" />

            <div class="text-subtitle-2 font-weight-bold mb-2">Structure (ordre des parties)</div>
            <div class="d-flex flex-wrap ga-2 mb-3">
              <v-btn size="small" variant="tonal" @click="addTokenPart(row, 'TYPE')">+ TYPE</v-btn>
              <v-btn size="small" variant="tonal" @click="addTokenPart(row, 'PROCESSUS')">+ PROCESSUS</v-btn>
              <v-btn size="small" variant="tonal" @click="addTokenPart(row, 'YEAR')">+ YEAR</v-btn>
              <v-btn size="small" variant="tonal" @click="addTokenPart(row, 'MONTH')">+ MONTH</v-btn>
              <v-btn color="primary" size="small" variant="tonal" @click="addTokenPart(row, 'NUMERO')">+ NUMERO</v-btn>
              <v-btn size="small" variant="outlined" @click="addSeparatorPart(row, '/')">+ /</v-btn>
              <v-btn size="small" variant="outlined" @click="addSeparatorPart(row, '-')">+ -</v-btn>
            </div>

            <div v-for="(part, index) in row.parts" :key="`${row.processId || 'global'}-${index}`" class="d-flex ga-2 align-center mb-2">
              <v-select
                v-model="part.type"
                density="compact"
                hide-details
                :items="partTypeOptions"
                style="max-width: 160px"
                variant="outlined"
              />
              <v-select
                v-if="part.type === 'token'"
                v-model="part.token"
                density="compact"
                hide-details
                :items="tokenOptions"
                style="max-width: 200px"
                variant="outlined"
              />
              <v-text-field
                v-else
                v-model="part.value"
                density="compact"
                hide-details
                label="Séparateur"
                style="max-width: 200px"
                variant="outlined"
              />
              <v-btn
                :disabled="index === 0"
                icon="mdi-arrow-up"
                size="x-small"
                variant="text"
                @click="movePart(row, index, -1)"
              />
              <v-btn
                :disabled="index === row.parts.length - 1"
                icon="mdi-arrow-down"
                size="x-small"
                variant="text"
                @click="movePart(row, index, 1)"
              />
              <v-btn
                color="error"
                icon="mdi-close"
                size="x-small"
                variant="text"
                @click="removePart(row, index)"
              />
            </div>

            <div class="row-footer">
              <div class="example-box">
                <span class="text-caption text-medium-emphasis mr-2">Exemple</span>
                <code>{{ previewExample(row) }}</code>
              </div>
              <v-btn
                color="primary"
                :disabled="!isValidRow(row)"
                prepend-icon="mdi-content-save-outline"
                rounded="lg"
                @click="saveRow(row)"
              >
                Enregistrer
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-alert
      v-if="rows.length === 0"
      class="mt-4"
      density="comfortable"
      type="info"
      variant="tonal"
    >
      Aucun processus détecté. Ajoutez un processus pour configurer la codification.
    </v-alert>
  </div>
</template>

<script setup lang="ts">
  import type { Nomenclature } from '../../types/document.types'
  import { computed, ref, watch } from 'vue'

  const props = defineProps<{
    nomenclatures?: Nomenclature[]
    processes?: Array<{
      id: number
      title?: string
      name?: string
      nom?: string
      code?: string
      attributes?: {
        title?: string
        name?: string
        nom?: string
        code?: string
      }
    }>
  }>()

  const emit = defineEmits<{
    save: [payload: {
      id?: number
      process_id?: number
      processus: string
      nom: string
      format: string
      parts: Array<{ type: string, token?: string, value?: string }>
      actif: boolean
    }]
    delete: [id: number]
  }>()

  const defaultFormat = '{TYPE}/{PROCESSUS}/{NUMERO}'
  const rows = ref<Array<{
    processId: number | null
    processTitle: string
    nomenclatureId?: number
    code: string
    format: string
    parts: Array<{ type: string, token?: string, value?: string }>
    actif: boolean
  }>>([])

  const partTypeOptions = [
    { title: 'Token', value: 'token' },
    { title: 'Séparateur', value: 'separator' },
  ]
  const tokenOptions = ['TYPE', 'PROCESSUS', 'YEAR', 'MONTH', 'NUMERO']

  const rowsComputed = computed(() => {
    const processes = props.processes ?? []
    const nomenclatures = props.nomenclatures ?? []
    const processRows = processes.map(process => {
      const processId = Number(process.id)
      const existing = nomenclatures.find(n => Number(n.process_id) === processId)
      return {
        processId,
        processTitle:
          process.title
          || process.name
          || process.nom
          || process.attributes?.title
          || process.attributes?.name
          || process.attributes?.nom
          || `Processus #${process.id}`,
        nomenclatureId: existing?.id,
        code: existing?.processus || '',
        format: existing?.format || defaultFormat,
        parts: toParts(existing?.format_structure, existing?.format || defaultFormat),
        actif: existing?.actif ?? true,
      }
    })

    const globalRows = nomenclatures
      .filter(n => !n.process_id)
      .map(n => ({
        processId: null,
        processTitle: n.nom || 'Nomenclature globale',
        nomenclatureId: n.id,
        code: n.processus || 'GEN',
        format: n.format || defaultFormat,
        parts: toParts(n.format_structure, n.format || defaultFormat),
        actif: n.actif ?? true,
      }))

    return [...globalRows, ...processRows]
  })

  watch(rowsComputed, value => {
    const localByKey = new Map(
      rows.value.map(row => [`${row.processId ?? 'global'}:${row.nomenclatureId ?? 'new'}`, row]),
    )
    rows.value = value.map(row => {
      const key = `${row.processId ?? 'global'}:${row.nomenclatureId ?? 'new'}`
      const local = localByKey.get(key)
      if (!local) return row
      return {
        ...row,
        processTitle: local.processTitle,
        code: local.code,
        format: local.format,
        parts: local.parts,
        actif: local.actif,
      }
    })
  }, { immediate: true })

  function isValidRow (row: any) {
    const normalized = String(row.code || '').trim()
    const hasNumero = row.parts.some((part: any) => part.type === 'token' && String(part.token || '').toUpperCase() === 'NUMERO')
    const tokenParts = row.parts
      .filter((part: any) => part.type === 'token' && part.token)
      .map((part: any) => String(part.token).toUpperCase())
    const numeroLast = hasNumero && tokenParts.at(-1) === 'NUMERO'
    return normalized.length >= 2 && normalized.length <= 12 && !!row.format && hasNumero && numeroLast
  }

  function previewExample (row: any) {
    const sample = {
      TYPE: 'PRC',
      PROCESSUS: (row.code || 'XXX').toUpperCase(),
      NUMERO: '001',
      YEAR: '2026',
      MONTH: '04',
    }
    const regenerated = partsToFormat(row.parts)
    if (regenerated) {
      row.format = regenerated
    }
    let format = row.format || defaultFormat
    for (const [k, v] of Object.entries(sample)) {
      format = format.replace(`{${k}}`, v as string)
    }
    return format
  }

  function saveRow (row: any) {
    if (!isValidRow(row)) return
    const payload = {
      id: row.nomenclatureId,
      ...(row.processId ? { process_id: row.processId } : {}),
      processus: row.code.toUpperCase(),
      nom: row.processTitle || 'Nomenclature globale',
      format: partsToFormat(row.parts) || row.format,
      parts: row.parts,
      actif: row.actif,
    }
    emit('save', payload)
  }

  function addGlobalRow () {
    rows.value.unshift({
      processId: null,
      processTitle: 'Nomenclature globale',
      code: 'GEN',
      format: defaultFormat,
      parts: toParts(undefined, defaultFormat),
      actif: true,
    })
  }

  function addTokenPart (row: any, token: string) {
    row.parts.push({ type: 'token', token })
    row.format = partsToFormat(row.parts) || row.format
  }

  function addSeparatorPart (row: any, value: string) {
    row.parts.push({ type: 'separator', value })
    row.format = partsToFormat(row.parts) || row.format
  }

  function removePart (row: any, index: number) {
    row.parts.splice(index, 1)
    row.format = partsToFormat(row.parts) || row.format
  }

  function movePart (row: any, index: number, direction: number) {
    const target = index + direction
    if (target < 0 || target >= row.parts.length) return
    const [item] = row.parts.splice(index, 1)
    row.parts.splice(target, 0, item)
    row.format = partsToFormat(row.parts) || row.format
  }

  function partsToFormat (parts: Array<{ type: string, token?: string, value?: string }>): string {
    if (!Array.isArray(parts) || parts.length === 0) return ''
    return parts
      .map(part => {
        if (part.type === 'separator') return String(part.value || '')
        const token = String(part.token || '').toUpperCase().trim()
        return token ? `{${token}}` : ''
      })
      .join('')
  }

  function toParts (structure?: any[] | null, format?: string): Array<{ type: string, token?: string, value?: string }> {
    if (Array.isArray(structure) && structure.length > 0) {
      return structure.map((item: any) => {
        const type = String(item?.type || '').toLowerCase()
        if (type === 'separator' || type === 'literal' || type === 'text') {
          return { type: 'separator', value: String(item?.value || '') }
        }
        const token = String(item?.token || item?.value || '').toUpperCase().trim()
        return { type: 'token', token: token || 'TYPE' }
      })
    }

    const input = String(format || defaultFormat)
    const regex = /(\{[A-Z_]+\}|[^{}]+)/g
    const chunks = input.match(regex) || []
    return chunks.map(chunk => {
      if (chunk.startsWith('{') && chunk.endsWith('}')) {
        return { type: 'token', token: chunk.slice(1, -1) }
      }
      return { type: 'separator', value: chunk }
    })
  }
</script>

<style scoped>
.nomenclature-manager {
  display: grid;
  gap: 14px;
}

.manager-intro {
  border: 1px solid rgba(91, 141, 217, 0.2);
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(91, 141, 217, 0.09), rgba(16, 185, 129, 0.08));
  padding: 14px 16px;
}

.intro-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.intro-subtitle {
  margin-top: 4px;
  margin-bottom: 0;
  font-size: 0.83rem;
  color: #475569;
}

.nomenclature-row {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
}

.row-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.row-footer {
  margin-top: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.example-box {
  border: 1px dashed rgba(15, 23, 42, 0.2);
  border-radius: 10px;
  padding: 8px 10px;
  background: rgba(255, 255, 255, 0.8);
  font-size: 0.8rem;
}

.example-box code {
  color: #1e3a8a;
  font-weight: 700;
}

:deep(.field-modern .v-field) {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.92);
}
</style>
