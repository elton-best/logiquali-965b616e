<template>
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom end"
    offset="8"
  >
    <template #activator="{ props: menuProps }">
      <v-btn
        v-bind="menuProps"
        color="grey-darken-1"
        density="comfortable"
        prepend-icon="mdi-view-column-outline"
        rounded="lg"
        variant="outlined"
      >
        Colonnes
        <v-badge
          v-if="visibleCount !== totalCount"
          class="ml-2"
          color="primary"
          :content="`${visibleCount}/${totalCount}`"
          inline
        />
      </v-btn>
    </template>

    <v-card class="column-selector-card" min-width="260" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between text-subtitle-2 font-weight-bold py-2 px-3 bg-grey-lighten-4">
        <span>Colonnes affichées</span>
        <v-btn
          density="compact"
          icon="mdi-close"
          size="small"
          variant="text"
          @click="menuOpen = false"
        />
      </v-card-title>

      <v-divider />

      <div class="px-2 py-1 d-flex gap-1 justify-space-between border-b">
        <v-btn
          density="compact"
          size="x-small"
          variant="text"
          @click="selectAll"
        >
          Tout afficher
        </v-btn>
        <v-btn
          density="compact"
          size="x-small"
          variant="text"
          @click="resetToDefault"
        >
          Réinitialiser
        </v-btn>
      </div>

      <v-card-text class="pa-2 column-list-scrollable">
        <v-checkbox
          v-for="col in columns"
          :key="col.key"
          :disabled="col.mandatory"
          density="compact"
          hide-details
          :label="col.title"
          :model-value="isColVisible(col.key)"
          @update:model-value="toggleCol(col.key, $event)"
        >
          <template #label>
            <span class="text-caption" :class="{ 'font-weight-bold': col.mandatory }">
              {{ col.title }}
              <v-chip
                v-if="col.mandatory"
                class="ml-1"
                color="grey"
                density="compact"
                size="x-small"
                variant="outlined"
              >
                Fixe
              </v-chip>
            </span>
          </template>
        </v-checkbox>
      </v-card-text>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

export interface ColumnDefinition {
  key: string
  title: string
  mandatory?: boolean
  defaultVisible?: boolean
}

const props = withDefaults(
  defineProps<{
    storageKey: string
    columns: ColumnDefinition[]
    modelValue: string[]
  }>(),
  {
    modelValue: () => [],
  },
)

const emit = defineEmits<{
  'update:modelValue': [keys: string[]]
}>()

const menuOpen = ref(false)

const totalCount = computed(() => props.columns.length)
const visibleCount = computed(() => props.modelValue.length)

function isColVisible(key: string): boolean {
  return props.modelValue.includes(key)
}

function toggleCol(key: string, visible: boolean | null) {
  const current = new Set(props.modelValue)
  if (visible) {
    current.add(key)
  } else {
    // Check if mandatory
    const colDef = props.columns.find(c => c.key === key)
    if (colDef?.mandatory) return
    current.delete(key)
  }
  const next = Array.from(current)
  saveState(next)
  emit('update:modelValue', next)
}

function selectAll() {
  const allKeys = props.columns.map(c => c.key)
  saveState(allKeys)
  emit('update:modelValue', allKeys)
}

function resetToDefault() {
  const defaults = props.columns
    .filter(c => c.mandatory || c.defaultVisible !== false)
    .map(c => c.key)
  saveState(defaults)
  emit('update:modelValue', defaults)
}

function saveState(keys: string[]) {
  try {
    if (props.storageKey) {
      localStorage.setItem(`col_visibility_${props.storageKey}`, JSON.stringify(keys))
    }
  } catch (e) {
    console.warn('Failed to save column visibility:', e)
  }
}

function loadState(): string[] | null {
  try {
    if (!props.storageKey) return null
    const raw = localStorage.getItem(`col_visibility_${props.storageKey}`)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed) && parsed.length > 0) {
        // Ensure mandatory columns are always present
        const mandatoryKeys = props.columns.filter(c => c.mandatory).map(c => c.key)
        const combined = Array.from(new Set([...mandatoryKeys, ...parsed]))
        return combined
      }
    }
  } catch (e) {
    console.warn('Failed to load column visibility:', e)
  }
  return null
}

onMounted(() => {
  const stored = loadState()
  if (stored) {
    emit('update:modelValue', stored)
  } else if (props.modelValue.length === 0) {
    resetToDefault()
  }
})
</script>

<style scoped>
.column-selector-card {
  max-height: 400px;
  display: flex;
  flex-direction: column;
}
.column-list-scrollable {
  max-height: 280px;
  overflow-y: auto;
}
</style>

