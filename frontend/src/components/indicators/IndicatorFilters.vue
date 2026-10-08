<template>
  <div class="indicator-filters space-y-4">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="preset in quickFilters"
        :key="preset.label"
        :class="[
          'px-3 py-2 text-sm font-medium rounded-lg border transition-colors',
          isActiveQuickFilter(preset)
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300'
        ]"
        @click="applyQuickFilter(preset)"
      >
        {{ preset.label }}
        <span v-if="preset.count !== undefined" class="ml-1 opacity-75">({{ preset.count }})</span>
      </button>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
      <button
        class="flex items-center justify-between w-full text-sm font-medium mb-3"
        @click="showAdvanced = !showAdvanced"
      >
        <span>Filtres avancés</span>
        <svg :class="['w-5 h-5 transition-transform', showAdvanced && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
      </button>

      <div v-show="showAdvanced" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Type</label>
          <select v-model="localFilters.type" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Tous</option>
            <option value="kpi">KPI</option>
            <option value="objective">Objectif</option>
            <option value="target">Cible</option>
            <option value="metric">Métrique</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Catégorie</label>
          <select v-model="localFilters.category" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Toutes</option>
            <option value="quality">Qualité</option>
            <option value="environment">Environnement</option>
            <option value="health_safety">Santé & Sécurité</option>
            <option value="performance">Performance</option>
            <option value="financial">Financier</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Statut</label>
          <select v-model="localFilters.status" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Tous</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="draft">Brouillon</option>
            <option value="archived">Archivé</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Fréquence</label>
          <select v-model="localFilters.frequency" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Toutes</option>
            <option value="daily">Quotidienne</option>
            <option value="weekly">Hebdomadaire</option>
            <option value="monthly">Mensuelle</option>
            <option value="quarterly">Trimestrielle</option>
            <option value="yearly">Annuelle</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Niveau d'alerte</label>
          <select v-model="localFilters.alert_level" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Tous</option>
            <option value="green">Conforme (Vert)</option>
            <option value="yellow">Attention (Jaune)</option>
            <option value="red">Critique (Rouge)</option>
          </select>
        </div>

        <div class="flex items-end">
          <label class="flex items-center cursor-pointer">
            <input
              v-model="localFilters.active_only"
              class="mr-2"
              type="checkbox"
              @change="emitChange"
            >
            <span class="text-sm font-medium">Actifs uniquement</span>
          </label>
        </div>

        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm font-medium mb-1">Recherche</label>
          <input
            v-model="localFilters.search"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="Nom, code, description..."
            type="text"
            @input="debouncedEmit"
          >
        </div>
      </div>
    </div>

    <div v-if="activeFiltersCount > 0" class="flex items-center justify-between text-sm">
      <span class="text-gray-600">{{ activeFiltersCount }} filtre(s) actif(s)</span>
      <button class="text-blue-600 hover:underline" @click="resetFilters">Réinitialiser</button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { IndicatorAlertLevel, IndicatorFilters } from '@/types/indicator'
  import { computed, ref, watch } from 'vue'

  interface QuickFilter {
    label: string
    filters: Partial<IndicatorFilters>
    count?: number
  }

  interface Props {
    modelValue: IndicatorFilters
    counts?: {
      total: number
      green: number
      yellow: number
      red: number
      active: number
    }
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [filters: IndicatorFilters]
    'change': [filters: IndicatorFilters]
  }>()

  const localFilters = ref<IndicatorFilters>({ ...props.modelValue })
  const showAdvanced = ref(false)

  const quickFilters = computed<QuickFilter[]>(() => [
    { label: 'Tous', filters: {}, count: props.counts?.total },
    { label: 'Actifs', filters: { active_only: true }, count: props.counts?.active },
    { label: 'Conformes', filters: { alert_level: 'green' as IndicatorAlertLevel }, count: props.counts?.green },
    { label: 'Attention', filters: { alert_level: 'yellow' as IndicatorAlertLevel }, count: props.counts?.yellow },
    { label: 'Critiques', filters: { alert_level: 'red' as IndicatorAlertLevel }, count: props.counts?.red },
  ])

  const activeFiltersCount = computed(() => {
    let count = 0
    if (localFilters.value.type) count++
    if (localFilters.value.category) count++
    if (localFilters.value.status) count++
    if (localFilters.value.frequency) count++
    if (localFilters.value.alert_level) count++
    if (localFilters.value.active_only) count++
    if (localFilters.value.search) count++
    return count
  })

  function isActiveQuickFilter (preset: QuickFilter): boolean {
    const presetKeys = Object.keys(preset.filters)
    if (presetKeys.length === 0) return activeFiltersCount.value === 0
    return presetKeys.every(key => {
      const k = key as keyof IndicatorFilters
      return localFilters.value[k] === preset.filters[k]
    })
  }

  function applyQuickFilter (preset: QuickFilter) {
    localFilters.value = { ...preset.filters } as IndicatorFilters
    emitChange()
  }

  let debounceTimer: ReturnType<typeof setTimeout> | null = null

  function debouncedEmit () {
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => emitChange(), 300)
  }

  function emitChange () {
    emit('update:modelValue', localFilters.value)
    emit('change', localFilters.value)
  }

  function resetFilters () {
    localFilters.value = {}
    emitChange()
  }

  watch(() => props.modelValue, newVal => {
    localFilters.value = { ...newVal }
  }, { deep: true })
</script>
