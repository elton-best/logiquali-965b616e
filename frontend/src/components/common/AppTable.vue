<template>
  <div class="app-table">
    <!-- Optional Toolbar with Column Visibility Selector -->
    <div v-if="enableColumnVisibility || $slots.toolbar" class="flex items-center justify-between gap-3 mb-3">
      <div class="flex-1">
        <slot name="toolbar" />
      </div>

      <div v-if="enableColumnVisibility" class="relative inline-block text-left">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-50 dark:hover:bg-neutral-700 transition shadow-sm"
          @click="showColumnMenu = !showColumnMenu"
        >
          <SlidersHorizontal class="w-3.5 h-3.5 text-neutral-500" />
          <span>Colonnes</span>
          <span class="ml-1 px-1.5 py-0.2 bg-neutral-100 dark:bg-neutral-700 rounded text-[10px] font-semibold text-neutral-600 dark:text-neutral-300">
            {{ visibleHeaders.length }}/{{ headers.length }}
          </span>
          <ChevronDown class="w-3 h-3 text-neutral-400" />
        </button>

        <!-- Dropdown menu with checkboxes -->
        <div
          v-if="showColumnMenu"
          class="absolute right-0 z-50 mt-1 w-64 origin-top-right rounded-xl bg-white dark:bg-neutral-800 p-2 shadow-xl border border-neutral-200 dark:border-neutral-700 focus:outline-none"
        >
          <div class="flex items-center justify-between pb-2 mb-2 border-b border-neutral-200 dark:border-neutral-700 px-2">
            <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-200">Affichage des colonnes</span>
            <button
              type="button"
              class="text-[11px] text-primary-600 hover:text-primary-700 font-medium"
              @click="resetColumns"
            >
              Tout afficher
            </button>
          </div>
          <div class="max-h-60 overflow-y-auto space-y-1">
            <label
              v-for="header in headers"
              :key="'col-' + header.key"
              class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 cursor-pointer text-xs text-neutral-700 dark:text-neutral-200 select-none"
            >
              <input
                type="checkbox"
                class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500 h-4 w-4 cursor-pointer"
                :checked="isColumnVisible(header.key)"
                :disabled="isColumnVisible(header.key) && visibleHeaders.length <= 1"
                @change="toggleColumn(header.key)"
              >
              <span class="truncate">{{ header.label }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
      <table class="w-full">
        <!-- Header -->
        <thead class="bg-neutral-50 dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-700">
          <tr>
            <th
              v-for="header in visibleHeaders"
              :key="header.key"
              :class="[
                'px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider',
                header.align === 'center' && 'text-center',
                header.align === 'end' && 'text-right',
                header.sortable && 'cursor-pointer select-none hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors'
              ]"
              @click="header.sortable && handleSort(header.key)"
            >
              <div class="flex items-center gap-2" :class="[header.align === 'center' && 'justify-center', header.align === 'end' && 'justify-end']">
                <span>{{ header.label }}</span>
                <template v-if="header.sortable">
                  <ChevronUp v-if="sortKey === header.key && sortOrder === 'asc'" class="w-4 h-4" />
                  <ChevronDown v-else-if="sortKey === header.key && sortOrder === 'desc'" class="w-4 h-4" />
                  <ChevronsUpDown v-else class="w-4 h-4 opacity-40" />
                </template>
              </div>
            </th>
          </tr>
        </thead>

        <!-- Body -->
        <tbody v-if="!loading && sortedItems.length > 0" class="divide-y divide-neutral-200 dark:divide-neutral-700">
          <tr
            v-for="(item, index) in paginatedItems"
            :key="getItemKey(item, index)"
            class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors"
          >
            <td
              v-for="header in visibleHeaders"
              :key="header.key"
              :class="[
                'px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100',
                header.align === 'center' && 'text-center',
                header.align === 'end' && 'text-right'
              ]"
            >
              <slot :index="index" :item="item" :name="`item.${header.key}`">
                {{ getNestedValue(item, header.key) }}
              </slot>
            </td>
          </tr>
        </tbody>

        <!-- Loading State -->
        <tbody v-else-if="loading">
          <tr v-for="i in itemsPerPage" :key="`skeleton-${i}`">
            <td v-for="header in visibleHeaders" :key="header.key" class="px-4 py-3">
              <div class="h-4 bg-neutral-200 dark:bg-neutral-700 rounded animate-pulse" />
            </td>
          </tr>
        </tbody>

        <!-- Empty State -->
        <tbody v-else>
          <tr>
            <td class="px-4 py-12" :colspan="visibleHeaders.length">
              <slot name="empty">
                <div class="flex flex-col items-center justify-center text-center">
                  <div class="w-16 h-16 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                    <Inbox class="w-8 h-8 text-neutral-400 dark:text-neutral-600" />
                  </div>
                  <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2">
                    {{ emptyTitle }}
                  </h3>
                  <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                    {{ emptyDescription }}
                  </p>
                  <button
                    v-if="emptyAction"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium"
                    @click="$emit('empty-action')"
                  >
                    {{ emptyActionLabel }}
                  </button>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="!loading && sortedItems.length > 0 && totalPages > 1"
      class="flex items-center justify-between mt-4 px-4"
    >
      <div class="text-sm text-neutral-600 dark:text-neutral-400">
        Affichage de {{ startItem }} à {{ endItem }} sur {{ totalItems }} résultats
      </div>
      <div class="flex items-center gap-2">
        <button
          aria-label="Page précédente"
          class="p-2 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          :disabled="currentPage === 1"
          @click="changePage(currentPage - 1)"
        >
          <ChevronLeft class="w-5 h-5" />
        </button>

        <template v-for="page in visiblePages" :key="page">
          <button
            v-if="typeof page === 'number'"
            :class="[
              'px-3 py-1 rounded-lg transition-colors font-medium',
              page === currentPage
                ? 'bg-primary-600 text-white'
                : 'border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800'
            ]"
            @click="changePage(page)"
          >
            {{ page }}
          </button>
          <span v-else class="px-2 text-neutral-400">...</span>
        </template>

        <button
          aria-label="Page suivante"
          class="p-2 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          :disabled="currentPage === totalPages"
          @click="changePage(currentPage + 1)"
        >
          <ChevronRight class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ChevronDown, ChevronLeft, ChevronRight, ChevronsUpDown, ChevronUp, Inbox, SlidersHorizontal } from 'lucide-vue-next'
  import { computed, ref, watch } from 'vue'

  interface TableHeader {
    key: string
    label: string
    sortable?: boolean
    align?: 'start' | 'center' | 'end'
  }

  interface Props {
    headers: TableHeader[]
    items: any[]
    loading?: boolean
    itemsPerPage?: number
    emptyTitle?: string
    emptyDescription?: string
    emptyAction?: boolean
    emptyActionLabel?: string
    itemKey?: string
    enableColumnVisibility?: boolean
    defaultHiddenColumns?: string[]
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    itemsPerPage: 10,
    emptyTitle: 'Aucune donnée',
    emptyDescription: 'Aucun élément à afficher pour le moment',
    emptyAction: false,
    emptyActionLabel: 'Créer',
    itemKey: 'id',
    enableColumnVisibility: false,
    defaultHiddenColumns: () => [],
  })

  const emit = defineEmits<{
    'empty-action': []
    'page-change': [page: number]
    'sort-change': [key: string, order: 'asc' | 'desc']
  }>()

  const currentPage = ref(1)
  const sortKey = ref<string>('')
  const sortOrder = ref<'asc' | 'desc'>('asc')

  // Column visibility state
  const showColumnMenu = ref(false)
  const hiddenColumns = ref<Set<string>>(new Set(props.defaultHiddenColumns))

  watch(() => props.defaultHiddenColumns, (newVal) => {
    hiddenColumns.value = new Set(newVal || [])
  })

  const visibleHeaders = computed(() => {
    if (!props.enableColumnVisibility) {
      return props.headers
    }
    return props.headers.filter(h => !hiddenColumns.value.has(h.key))
  })

  function isColumnVisible (key: string): boolean {
    return !hiddenColumns.value.has(key)
  }

  function toggleColumn (key: string) {
    const updated = new Set(hiddenColumns.value)
    if (updated.has(key)) {
      updated.delete(key)
    } else {
      // Prevent hiding all columns
      if (props.headers.length - updated.size > 1) {
        updated.add(key)
      }
    }
    hiddenColumns.value = updated
  }

  function resetColumns () {
    hiddenColumns.value = new Set()
  }

  const sortedItems = computed(() => {
    if (!sortKey.value) return props.items

    return [...props.items].toSorted((a, b) => {
      const aVal = getNestedValue(a, sortKey.value)
      const bVal = getNestedValue(b, sortKey.value)

      if (aVal === bVal) return 0

      const comparison = aVal > bVal ? 1 : -1
      return sortOrder.value === 'asc' ? comparison : -comparison
    })
  })

  const totalItems = computed(() => sortedItems.value.length)
  const totalPages = computed(() => Math.ceil(totalItems.value / props.itemsPerPage))

  const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * props.itemsPerPage
    const end = start + props.itemsPerPage
    return sortedItems.value.slice(start, end)
  })

  const startItem = computed(() => {
    if (totalItems.value === 0) return 0
    return (currentPage.value - 1) * props.itemsPerPage + 1
  })

  const endItem = computed(() => {
    const end = currentPage.value * props.itemsPerPage
    return Math.min(end, totalItems.value)
  })

  const visiblePages = computed(() => {
    const pages: (number | string)[] = []
    const total = totalPages.value
    const current = currentPage.value

    if (total <= 7) {
      for (let i = 1; i <= total; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1)

      if (current > 3) {
        pages.push('...')
      }

      const start = Math.max(2, current - 1)
      const end = Math.min(total - 1, current + 1)

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      if (current < total - 2) {
        pages.push('...')
      }

      pages.push(total)
    }

    return pages
  })

  function handleSort (key: string) {
    if (sortKey.value === key) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortOrder.value = 'asc'
    }
    emit('sort-change', sortKey.value, sortOrder.value)
  }

  function changePage (page: number) {
    if (page < 1 || page > totalPages.value) return
    currentPage.value = page
    emit('page-change', page)
  }

  function getNestedValue (obj: any, path: string): any {
    return path.split('.').reduce((acc, part) => acc?.[part], obj) ?? '-'
  }

  function getItemKey (item: any, index: number): string | number {
    return item[props.itemKey] ?? index
  }

  // Expose methods for parent components
  defineExpose({
    resetPage: () => {
      currentPage.value = 1
    },
    resetSort: () => {
      sortKey.value = ''
      sortOrder.value = 'asc'
    },
  })
</script>

<style scoped>
.app-table {
  @apply w-full;
}

@media (prefers-reduced-motion: reduce) {
  .app-table * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
