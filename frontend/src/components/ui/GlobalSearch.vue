<template>
  <div class="relative">
    <div class="relative">
      <input
        v-model="searchQuery"
        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        :placeholder="placeholder"
        type="text"
        @focus="showResults = true"
        @input="handleSearch"
      >
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
      </div>
      <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500" />
      </div>
    </div>

    <!-- Results Dropdown -->
    <div
      v-if="showResults && (searchResults.length > 0 || searchQuery.length > 0)"
      class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto"
    >
      <div v-if="searchResults.length === 0 && searchQuery.length > 0" class="p-4 text-gray-500 text-center">
        {{ isSearching ? 'Recherche en cours...' : 'Aucun résultat trouvé' }}
      </div>

      <div v-for="result in searchResults" :key="`${result.type}-${result.id}`" class="border-b border-gray-100 last:border-b-0">
        <button
          class="w-full p-3 text-left hover:bg-gray-50 flex items-center space-x-3"
          @click="selectResult(result)"
        >
          <div class="flex-shrink-0">
            <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="getTypeIcon(result.type)">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path :d="getIconPath(result.type)" />
              </svg>
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ result.title }}</p>
            <p class="text-sm text-gray-500 truncate">{{ result.description }}</p>
            <p class="text-xs text-gray-400">{{ getTypeLabel(result.type) }}</p>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useGlobalSearch } from '@/composables/useGlobalSearch'

  interface Props {
    placeholder?: string
    showFilters?: boolean
  }

  const _props = withDefaults(defineProps<Props>(), {
    placeholder: 'Rechercher...',
    showFilters: false,
  })

  const emit = defineEmits<{
    select: [result: any]
  }>()

  const router = useRouter()
  const { searchQuery, isSearching, searchResults, search } = useGlobalSearch()
  const showResults = ref(false)

  function handleSearch (event: Event) {
    const target = event.target as HTMLInputElement
    search(target.value)
  }

  function selectResult (result: any) {
    showResults.value = false
    emit('select', result)

    // Navigate to result if URL provided
    if (result.url) {
      router.push(result.url)
    }
  }

  function getTypeIcon (type: string) {
    const icons = {
      document: 'bg-blue-100 text-blue-600',
      process: 'bg-green-100 text-green-600',
      audit: 'bg-purple-100 text-purple-600',
      action: 'bg-orange-100 text-orange-600',
      risk: 'bg-red-100 text-red-600',
      user: 'bg-gray-100 text-gray-600',
    }
    return icons[type] || 'bg-gray-100 text-gray-600'
  }

  function getIconPath (type: string) {
    const paths = {
      document: 'M9 2a1 1 0 000 2h2a1 1 0 100-2H9z M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z',
      process: 'M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z',
      audit: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      action: 'M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z',
      risk: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z',
      user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    }
    return paths[type] || paths.document
  }

  function getTypeLabel (type: string) {
    const labels = {
      document: 'Document',
      process: 'Processus',
      audit: 'Audit',
      action: 'Action',
      risk: 'Risque',
      user: 'Utilisateur',
    }
    return labels[type] || type
  }

  // Close results when clicking outside
  watch(() => showResults.value, show => {
    if (show) {
      const handleClickOutside = (event: Event) => {
        const target = event.target as Element
        if (!target.closest('.relative')) {
          showResults.value = false
          document.removeEventListener('click', handleClickOutside)
        }
      }
      setTimeout(() => document.addEventListener('click', handleClickOutside), 0)
    }
  })
</script>
