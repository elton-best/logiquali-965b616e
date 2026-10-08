<template>
  <div class="global-search-bar">
    <VTextField
      v-model="searchQuery"
      clearable
      density="comfortable"
      hide-details
      :loading="isLoading"
      :placeholder="placeholder"
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
      @click:clear="clearSearch"
      @input="debouncedSearch"
      @keydown.enter="performSearch"
    >
      <template #append>
        <VBtn
          v-if="showFilters"
          icon="mdi-filter-variant"
          size="small"
          variant="text"
          @click="toggleFilters"
        />
      </template>
    </VTextField>

    <!-- Filters Panel -->
    <VExpandTransition>
      <VCard v-if="filtersVisible" class="mt-2 pa-4" elevation="2">
        <VRow>
          <VCol cols="12" md="6">
            <VSelect
              v-model="selectedTypes"
              chips
              density="compact"
              :items="typeOptions"
              label="Types de résultats"
              multiple
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="selectedStatus"
              clearable
              density="compact"
              :items="statusOptions"
              label="Statut"
            />
          </VCol>
        </VRow>
      </VCard>
    </VExpandTransition>

    <!-- Results Dropdown -->
    <VMenu
      v-model="showResults"
      activator="parent"
      :close-on-content-click="false"
      max-width="600"
      offset="8"
    >
      <VCard v-if="results.length > 0" class="overflow-y-auto" max-height="400">
        <VList>
          <VListSubheader>{{ results.length }} résultat(s) trouvé(s)</VListSubheader>

          <VListItem
            v-for="result in results"
            :key="`${result.type}-${result.id}`"
            :to="result.url"
            @click="selectResult(result)"
          >
            <template #prepend>
              <VIcon :color="getTypeColor(result.type)" :icon="getTypeIcon(result.type)" />
            </template>

            <VListItemTitle>{{ result.title }}</VListItemTitle>
            <VListItemSubtitle>
              <VChip
                class="mr-2"
                :color="getTypeColor(result.type)"
                size="x-small"
              >
                {{ getTypeLabel(result.type) }}
              </VChip>
              <span v-if="result.ref">{{ result.ref }}</span>
              <span v-if="result.code"> • {{ result.code }}</span>
            </VListItemSubtitle>

            <template #append>
              <VIcon icon="mdi-chevron-right" size="small" />
            </template>
          </VListItem>
        </VList>
      </VCard>

      <VCard v-else-if="searchQuery && !isLoading" class="pa-4 text-center">
        <VIcon class="mb-2" color="grey" icon="mdi-magnify-remove-outline" size="48" />
        <div class="text-grey">Aucun résultat trouvé pour "{{ searchQuery }}"</div>
      </VCard>
    </VMenu>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'

  interface SearchResult {
    [key: string]: any
    type: string
    id: number
    ref?: string
    title: string
    code?: string
    url: string
  }

  interface Props {
    placeholder?: string
    showFilters?: boolean
    autoFocus?: boolean
  }

  const _props = withDefaults(defineProps<Props>(), {
    placeholder: 'Rechercher documents, processus, risques, NC...',
    showFilters: true,
    autoFocus: false,
  })

  const router = useRouter()

  const searchQuery = ref('')
  const isLoading = ref(false)
  const results = ref<SearchResult[]>([])
  const showResults = ref(false)
  const filtersVisible = ref(false)

  const selectedTypes = ref(['documents', 'processes', 'risks', 'non_conformities'])
  const selectedStatus = ref<string | null>(null)

  const typeOptions = [
    { title: 'Documents', value: 'documents' },
    { title: 'Processus', value: 'processes' },
    { title: 'Risques', value: 'risks' },
    { title: 'Non-Conformités', value: 'non_conformities' },
  ]

  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'En revue', value: 'in_review' },
    { title: 'Validé', value: 'validated' },
    { title: 'Obsolète', value: 'obsolete' },
    { title: 'Archivé', value: 'archived' },
  ]

  let debounceTimeout: ReturnType<typeof setTimeout> | null = null

  function debouncedSearch () {
    if (debounceTimeout) {
      clearTimeout(debounceTimeout)
    }

    debounceTimeout = setTimeout(() => {
      performSearch()
    }, 500) // 500ms debounce
  }

  async function performSearch () {
    if (!searchQuery.value || searchQuery.value.length < 2) {
      results.value = []
      showResults.value = false
      return
    }

    isLoading.value = true
    showResults.value = true

    try {
      const params = new URLSearchParams({
        q: searchQuery.value,
        types: selectedTypes.value.join(','),
        limit: '10',
      })

      if (selectedStatus.value) {
        params.append('status', selectedStatus.value)
      }

      const response = await api.get<{ results?: SearchResult[] }>(`/search?${params.toString()}`)
      const payload = (response?.data ?? response ?? {}) as { results?: SearchResult[] }
      results.value = payload.results || []
    } catch (error) {
      console.error('Search error:', error)
      results.value = []
    } finally {
      isLoading.value = false
    }
  }

  function clearSearch () {
    searchQuery.value = ''
    results.value = []
    showResults.value = false
  }

  function toggleFilters () {
    filtersVisible.value = !filtersVisible.value
  }

  function selectResult (result: SearchResult) {
    showResults.value = false
    router.push(result.url)
  }

  function getTypeIcon (type: string): string {
    const icons: Record<string, string> = {
      document: 'mdi-file-document',
      process: 'mdi-cog',
      risk: 'mdi-alert-circle',
      non_conformity: 'mdi-alert-octagon',
    }
    return icons[type] || 'mdi-file'
  }

  function getTypeColor (type: string): string {
    const colors: Record<string, string> = {
      document: 'blue',
      process: 'purple',
      risk: 'orange',
      non_conformity: 'red',
    }
    return colors[type] || 'grey'
  }

  function getTypeLabel (type: string): string {
    const labels: Record<string, string> = {
      document: 'Document',
      process: 'Processus',
      risk: 'Risque',
      non_conformity: 'NC',
    }
    return labels[type] || type
  }
</script>

<style scoped>
.global-search-bar {
  position: relative;
  width: 100%;
  max-width: 600px;
}
</style>
