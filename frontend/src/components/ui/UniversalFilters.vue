<template>
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Filtres</h3>
      <div class="flex items-center space-x-2">
        <button
          v-if="hasActiveFilters"
          class="text-sm text-gray-500 hover:text-gray-700"
          @click="clearAllFilters"
        >
          Effacer tout
        </button>
        <button
          class="text-gray-400 hover:text-gray-600"
          @click="collapsed = !collapsed"
        >
          <svg
            class="w-5 h-5 transform transition-transform"
            :class="{ 'rotate-180': collapsed }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </button>
      </div>
    </div>

    <div v-show="!collapsed" class="space-y-4">
      <!-- Search -->
      <div v-if="showSearch">
        <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
        <input
          v-model="localFilters.search"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Rechercher..."
          type="text"
        >
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <!-- Date Range -->
        <div v-if="showDateRange">
          <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
          <div class="space-y-2">
            <input
              v-model="localFilters.dateFrom"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              type="date"
            >
            <input
              v-model="localFilters.dateTo"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              type="date"
            >
          </div>
        </div>

        <!-- Status Multi-Select -->
        <div v-if="statusOptions.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
          <div class="relative">
            <button
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-left focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center justify-between"
              @click="showStatusDropdown = !showStatusDropdown"
            >
              <span class="truncate">
                {{ selectedStatusText }}
              </span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
            </button>

            <div v-if="showStatusDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
              <label v-for="option in statusOptions" :key="option.value" class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                <input
                  v-model="localFilters.status"
                  class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  type="checkbox"
                  :value="option.value"
                >
                <span class="text-sm">{{ option.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Type Select -->
        <div v-if="typeOptions.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select
            v-model="localFilters.type"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Tous les types</option>
            <option v-for="option in typeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Site Select -->
        <div v-if="siteOptions.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
          <select
            v-model="localFilters.site"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Tous les sites</option>
            <option v-for="option in siteOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Custom Filters Slot -->
        <slot :filters="localFilters" name="custom-filters" />
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <button
          class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
          @click="resetFilters"
        >
          Réinitialiser
        </button>
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="applyFilters"
        >
          Appliquer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  interface FilterOption {
    value: string
    label: string
  }

  interface Props {
    modelValue: Record<string, any>
    statusOptions?: FilterOption[]
    typeOptions?: FilterOption[]
    siteOptions?: FilterOption[]
    showSearch?: boolean
    showDateRange?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    statusOptions: () => [],
    typeOptions: () => [],
    siteOptions: () => [],
    showSearch: true,
    showDateRange: true,
  })

  const emit = defineEmits<{
    'update:modelValue': [filters: Record<string, any>]
    'apply': [filters: Record<string, any>]
  }>()

  const collapsed = ref(false)
  const showStatusDropdown = ref(false)
  const localFilters = ref({ ...props.modelValue })

  const hasActiveFilters = computed(() => {
    return Object.values(localFilters.value).some(value => {
      if (Array.isArray(value)) return value.length > 0
      return value !== '' && value !== null && value !== undefined
    })
  })

  const selectedStatusText = computed(() => {
    const selected = localFilters.value.status || []
    if (selected.length === 0) return 'Tous les statuts'
    if (selected.length === 1) {
      const option = props.statusOptions.find(opt => opt.value === selected[0])
      return option?.label || selected[0]
    }
    return `${selected.length} sélectionnés`
  })

  function applyFilters () {
    emit('update:modelValue', { ...localFilters.value })
    emit('apply', { ...localFilters.value })
  }

  function resetFilters () {
    localFilters.value = {
      search: '',
      dateFrom: '',
      dateTo: '',
      status: [],
      type: '',
      site: '',
    }
    applyFilters()
  }

  function clearAllFilters () {
    resetFilters()
  }

  // Close dropdown when clicking outside
  watch(showStatusDropdown, show => {
    if (show) {
      const handleClickOutside = (event: Event) => {
        const target = event.target as Element
        if (!target.closest('.relative')) {
          showStatusDropdown.value = false
          document.removeEventListener('click', handleClickOutside)
        }
      }
      setTimeout(() => document.addEventListener('click', handleClickOutside), 0)
    }
  })

  // Initialize filters
  watch(() => props.modelValue, newValue => {
    localFilters.value = { ...newValue }
  }, { immediate: true })
</script>
