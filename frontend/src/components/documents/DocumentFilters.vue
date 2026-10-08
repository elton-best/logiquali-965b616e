<template>
  <div class="document-filters space-y-4">
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
      <button class="flex items-center justify-between w-full text-sm font-medium mb-3" @click="showAdvanced = !showAdvanced">
        <span>Filtres avancés</span>
        <svg :class="['w-5 h-5 transition-transform', showAdvanced && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
      </button>

      <div v-show="showAdvanced" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Statut</label>
          <select v-model="localFilters.status" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Tous</option>
            <option value="draft">brouillon en attente de vérification</option>
            <option value="review">brouillon en cours de vérification</option>
            <option value="approved">validé - version 1</option>
            <option value="archived">Archivé</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Type</label>
          <select v-model="localFilters.type" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Tous</option>
            <option value="procedure">Procédure</option>
            <option value="instruction">Instruction</option>
            <option value="form">Formulaire</option>
            <option value="record">Enregistrement</option>
            <option value="manual">Manuel</option>
          </select>
        </div>

        <div v-if="categories && categories.length > 0">
          <label class="block text-sm font-medium mb-1">Catégorie</label>
          <select v-model="localFilters.category_id" class="w-full px-3 py-2 border rounded-lg" @change="emitChange">
            <option :value="undefined">Toutes</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>

        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm font-medium mb-1">Recherche</label>
          <input
            v-model="localFilters.search"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="Titre, code, mots-clés..."
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
  import type { DocumentCategory, DocumentFilters } from '@/types/document'
  import { computed, ref, watch } from 'vue'

  interface QuickFilter {
    label: string
    filters: Partial<DocumentFilters>
    count?: number
  }

  interface Props {
    modelValue: DocumentFilters
    categories?: DocumentCategory[]
    counts?: {
      total: number
      draft: number
      review: number
      approved: number
      archived: number
      review_overdue: number
    }
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [filters: DocumentFilters]
    'change': [filters: DocumentFilters]
  }>()

  const localFilters = ref<DocumentFilters>({ ...props.modelValue })
  const showAdvanced = ref(false)

  const quickFilters = computed<QuickFilter[]>(() => [
    { label: 'Tous', filters: {}, count: props.counts?.total },
    { label: 'brouillon en attente de vérification', filters: { status: 'draft' }, count: props.counts?.draft },
    { label: 'brouillon en cours de vérification', filters: { status: 'under_review' }, count: props.counts?.review },
    { label: 'validé - version 1', filters: { status: 'approved' }, count: props.counts?.approved },
    { label: 'Révision échue', filters: { review_overdue: true }, count: props.counts?.review_overdue },
  ])

  const activeFiltersCount = computed(() => {
    let count = 0
    if (localFilters.value.status) count++
    if (localFilters.value.type) count++
    if (localFilters.value.category_id) count++
    if (localFilters.value.search) count++
    if (localFilters.value.review_overdue) count++
    return count
  })

  function isActiveQuickFilter (preset: QuickFilter): boolean {
    const presetKeys = Object.keys(preset.filters)
    if (presetKeys.length === 0) return activeFiltersCount.value === 0
    return presetKeys.every(key => {
      const k = key as keyof DocumentFilters
      return localFilters.value[k] === preset.filters[k]
    })
  }

  function applyQuickFilter (preset: QuickFilter) {
    localFilters.value = { ...preset.filters } as DocumentFilters
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
