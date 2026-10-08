<template>
  <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
    <!-- Mobile -->
    <div class="flex flex-1 justify-between sm:hidden">
      <button
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="currentPage <= 1"
        @click="goToPrevious"
      >
        Précédent
      </button>
      <button
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="currentPage >= totalPages"
        @click="goToNext"
      >
        Suivant
      </button>
    </div>

    <!-- Desktop -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Affichage de
          <span class="font-medium">{{ startItem }}</span>
          à
          <span class="font-medium">{{ endItem }}</span>
          sur
          <span class="font-medium">{{ total }}</span>
          résultats
        </p>
      </div>

      <div>
        <nav aria-label="Pagination" class="isolate inline-flex -space-x-px rounded-md shadow-sm">
          <!-- Previous -->
          <button
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage <= 1"
            @click="goToPrevious"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path clip-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" fill-rule="evenodd" />
            </svg>
          </button>

          <!-- Page numbers -->
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              :class="[
                page === currentPage
                  ? 'relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                  : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
              ]"
              @click="goToPage(page as number)"
            >
              {{ page }}
            </button>
            <span
              v-else
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0"
            >
              ...
            </span>
          </template>

          <!-- Next -->
          <button
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage >= totalPages"
            @click="goToNext"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path clip-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" fill-rule="evenodd" />
            </svg>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    currentPage: number
    totalPages: number
    total: number
    perPage: number
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:currentPage': [page: number]
  }>()

  const startItem = computed(() => {
    return (props.currentPage - 1) * props.perPage + 1
  })

  const endItem = computed(() => {
    return Math.min(props.currentPage * props.perPage, props.total)
  })

  const visiblePages = computed(() => {
    const pages: (number | string)[] = []
    const total = props.totalPages
    const current = props.currentPage

    if (total <= 7) {
      // Show all pages if 7 or fewer
      for (let i = 1; i <= total; i++) {
        pages.push(i)
      }
    } else {
      // Always show first page
      pages.push(1)

      if (current <= 4) {
        // Show pages 2-5 and ellipsis
        for (let i = 2; i <= 5; i++) {
          pages.push(i)
        }
        pages.push('...')
      } else if (current >= total - 3) {
        // Show ellipsis and last 4 pages
        pages.push('...')
        for (let i = total - 4; i <= total - 1; i++) {
          pages.push(i)
        }
      } else {
        // Show ellipsis, current page area, ellipsis
        pages.push('...')
        for (let i = current - 1; i <= current + 1; i++) {
          pages.push(i)
        }
        pages.push('...')
      }

      // Always show last page
      if (total > 1) {
        pages.push(total)
      }
    }

    return pages
  })

  function goToPage (page: number) {
    if (page >= 1 && page <= props.totalPages) {
      emit('update:currentPage', page)
    }
  }

  function goToPrevious () {
    if (props.currentPage > 1) {
      goToPage(props.currentPage - 1)
    }
  }

  function goToNext () {
    if (props.currentPage < props.totalPages) {
      goToPage(props.currentPage + 1)
    }
  }
</script>
