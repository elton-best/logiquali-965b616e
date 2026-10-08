<template>
  <div class="audit-filters space-y-4">
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

    <!-- Advanced Filters -->
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
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
          <select
            v-model="localFilters.status"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les statuts</option>
            <option value="planned">Planifié</option>
            <option value="in_progress">En cours</option>
            <option value="completed">Terminé</option>
            <option value="verified">Vérifié</option>
            <option value="approved">Approuvé</option>
            <option value="cancelled">Annulé</option>
          </select>
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
          <select
            v-model="localFilters.audit_type"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les types</option>
            <option value="internal_process">Processus</option>
            <option value="internal_system">Système</option>
            <option value="internal_product">Produit</option>
            <option value="internal_thematic">Thématique</option>
            <option value="supplier">Fournisseur</option>
            <option value="external_certification">Certification</option>
            <option value="external_surveillance">Surveillance</option>
          </select>
        </div>

        <!-- Year -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année</label>
          <select
            v-model="localFilters.year"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Toutes les années</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <!-- Site -->
        <div v-if="sites && sites.length > 0">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
          <select
            v-model="localFilters.site_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les sites</option>
            <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
          </select>
        </div>

        <!-- Axe QHSE -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Axe QHSE</label>
          <select
            v-model="localFilters.axis"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            @change="emitChange"
          >
            <option :value="undefined">Tous les axes</option>
            <option value="Q">Qualité (Q)</option>
            <option value="H">Hygiène (H)</option>
            <option value="S">Sécurité (S)</option>
            <option value="E">Environnement (E)</option>
          </select>
        </div>

        <!-- Search -->
        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
          <input
            v-model="localFilters.search"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            placeholder="Titre, code, objectifs..."
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
  import type { AuditFilters } from '@/types/audit'
  import type { SiteReference } from '@/types/shared'
  import { computed, ref, watch } from 'vue'

  interface QuickFilter {
    label: string
    filters: Partial<AuditFilters>
    count?: number
  }

  interface Props {
    modelValue: AuditFilters
    sites?: SiteReference[]
    counts?: {
      total: number
      planned: number
      in_progress: number
      overdue: number
      completed: number
    }
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [filters: AuditFilters]
    'change': [filters: AuditFilters]
  }>()

  const localFilters = ref<AuditFilters>({ ...props.modelValue })
  const showAdvanced = ref(false)

  // Generate years (current +/- 5)
  const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 11 }, (_, i) => currentYear - 5 + i)
  })

  const quickFilters = computed<QuickFilter[]>(() => [
    {
      label: 'Tous',
      filters: {},
      count: props.counts?.total,
    },
    {
      label: 'Planifiés',
      filters: { status: 'planned' },
      count: props.counts?.planned,
    },
    {
      label: 'En cours',
      filters: { status: 'in_progress' },
      count: props.counts?.in_progress,
    },
    {
      label: 'En retard',
      filters: { overdue: true },
      count: props.counts?.overdue,
    },
    {
      label: 'Terminés',
      filters: { status: 'completed' },
      count: props.counts?.completed,
    },
  ])

  const activeFiltersCount = computed(() => {
    let count = 0
    if (localFilters.value.status) count++
    if (localFilters.value.audit_type) count++
    if (localFilters.value.year) count++
    if (localFilters.value.site_id) count++
    if (localFilters.value.axis) count++
    if (localFilters.value.search) count++
    if (localFilters.value.overdue) count++
    return count
  })

  function isActiveQuickFilter (preset: QuickFilter): boolean {
    const presetKeys = Object.keys(preset.filters)
    if (presetKeys.length === 0) return activeFiltersCount.value === 0

    return presetKeys.every(key => {
      const k = key as keyof AuditFilters
      return localFilters.value[k] === preset.filters[k]
    })
  }

  function applyQuickFilter (preset: QuickFilter) {
    localFilters.value = { ...preset.filters } as AuditFilters
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

  watch(() => props.modelValue, newVal => {
    localFilters.value = { ...newVal }
  }, { deep: true })
</script>
