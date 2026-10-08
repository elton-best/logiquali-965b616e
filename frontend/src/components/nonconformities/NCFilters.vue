<template>
  <div class="nc-filters space-y-4">
    <!-- Quick Filters -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="preset in quickFilters"
        :key="preset.label"
        :class="[
          'px-3 py-2 text-sm font-medium rounded-lg border transition-colors',
          isActiveQuickFilter(preset)
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
        ]"
        @click="applyQuickFilter(preset)"
      >
        {{ preset.label }}
        <span v-if="preset.count !== undefined" class="ml-1 opacity-75">
          ({{ preset.count }})
        </span>
      </button>
    </div>

    <!-- Advanced Filters (collapsible) -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
      <button
        class="flex items-center justify-between w-full text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
        @click="showAdvanced = !showAdvanced"
      >
        <span>Filtres avancés</span>
        <svg
          :class="['w-5 h-5 transition-transform', showAdvanced && 'rotate-180']"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
      </button>

      <div v-show="showAdvanced" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Statut
          </label>
          <select
            v-model="localFilters.status"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les statuts</option>
            <option value="nouveau">Nouveau</option>
            <option value="en_analyse">En analyse</option>
            <option value="action_en_cours">Action en cours</option>
            <option value="en_verification">En vérification</option>
            <option value="clos">Clôturé</option>
            <option value="rejete">Rejeté</option>
          </select>
        </div>

        <!-- Severity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Gravité
          </label>
          <select
            v-model="localFilters.severity"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Toutes gravités</option>
            <option value="majeur">Majeure</option>
            <option value="mineur">Mineure</option>
            <option value="observation">Observation</option>
          </select>
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Type
          </label>
          <select
            v-model="localFilters.type"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les types</option>
            <option value="audit_interne">Audit interne</option>
            <option value="audit_externe">Audit externe</option>
            <option value="reclamation_client">Réclamation client</option>
            <option value="incident">Incident</option>
            <option value="non_conformite_produit">NC Produit</option>
            <option value="autre">Autre</option>
          </select>
        </div>

        <!-- Site -->
        <div v-if="sites && sites.length > 0">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Site
          </label>
          <select
            v-model="localFilters.site_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les sites</option>
            <option v-for="site in sites" :key="site.id" :value="site.id">
              {{ site.name }}
            </option>
          </select>
        </div>

        <!-- Date From -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Détectée après le
          </label>
          <input
            v-model="localFilters.detected_from"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            type="date"
            @change="emitChange"
          >
        </div>

        <!-- Date To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Détectée avant le
          </label>
          <input
            v-model="localFilters.detected_to"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            type="date"
            @change="emitChange"
          >
        </div>

        <!-- Search -->
        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Recherche
          </label>
          <input
            v-model="localFilters.search"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            placeholder="Titre, description, référence..."
            type="text"
            @input="debouncedEmit"
          >
        </div>
      </div>
    </div>

    <!-- Active Filters Count + Reset -->
    <div v-if="activeFiltersCount > 0" class="flex items-center justify-between text-sm">
      <span class="text-gray-600 dark:text-gray-400">
        {{ activeFiltersCount }} filtre(s) actif(s)
      </span>
      <button
        class="text-blue-600 dark:text-blue-400 hover:underline"
        @click="resetFilters"
      >
        Réinitialiser
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { NCFilters } from '@/types/nonConformity'
  import type { SiteReference } from '@/types/shared'
  import { computed, ref, watch } from 'vue'

  interface QuickFilter {
    label: string
    filters: Partial<NCFilters>
    count?: number
  }

  interface Props {
    modelValue: NCFilters
    sites?: SiteReference[]
    counts?: {
      total: number
      nouveau: number
      en_analyse: number
      action_en_cours: number
      overdue: number
      major: number
    }
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [filters: NCFilters]
    'change': [filters: NCFilters]
  }>()

  const localFilters = ref<NCFilters>({ ...props.modelValue })
  const showAdvanced = ref(false)

  // Quick Filters Presets
  const quickFilters = computed<QuickFilter[]>(() => [
    {
      label: 'Toutes',
      filters: {},
      count: props.counts?.total,
    },
    {
      label: 'Nouvelles',
      filters: { status: 'nouveau' },
      count: props.counts?.nouveau,
    },
    {
      label: 'En cours',
      filters: { status: 'action_en_cours' },
      count: props.counts?.action_en_cours,
    },
    {
      label: 'En retard',
      filters: { overdue: true },
      count: props.counts?.overdue,
    },
    {
      label: 'Majeures',
      filters: { severity: 'majeur' },
      count: props.counts?.major,
    },
  ])

  const activeFiltersCount = computed(() => {
    let count = 0
    if (localFilters.value.status) count++
    if (localFilters.value.severity) count++
    if (localFilters.value.type) count++
    if (localFilters.value.site_id) count++
    if (localFilters.value.detected_from) count++
    if (localFilters.value.detected_to) count++
    if (localFilters.value.search) count++
    if (localFilters.value.overdue) count++
    return count
  })

  function isActiveQuickFilter (preset: QuickFilter): boolean {
    const presetKeys = Object.keys(preset.filters)
    if (presetKeys.length === 0) return activeFiltersCount.value === 0

    return presetKeys.every(key => {
      const k = key as keyof NCFilters
      return localFilters.value[k] === preset.filters[k]
    })
  }

  function applyQuickFilter (preset: QuickFilter) {
    localFilters.value = { ...preset.filters } as NCFilters
    emitChange()
  }

  let debounceTimer: ReturnType<typeof setTimeout> | null = null

  function debouncedEmit () {
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      emitChange()
    }, 300)
  }

  function emitChange () {
    emit('update:modelValue', localFilters.value)
    emit('change', localFilters.value)
  }

  function resetFilters () {
    localFilters.value = {}
    emitChange()
  }

  // Watch for external changes
  watch(() => props.modelValue, newVal => {
    localFilters.value = { ...newVal }
  }, { deep: true })
</script>
