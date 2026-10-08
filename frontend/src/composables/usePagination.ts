/**
 * Composable pour gérer la pagination des listes
 * Réutilisable dans tous les modules
 */

import type { PaginationLinks, PaginationMeta } from '@/types/shared'
import { computed, ref, watch } from 'vue'

export interface UsePaginationOptions {
  initialPage?: number
  initialPerPage?: number
  onPageChange?: (page: number) => void
}

export function usePagination (options: UsePaginationOptions = {}) {
  const {
    initialPage = 1,
    initialPerPage = 20,
    onPageChange,
  } = options

  // State
  const currentPage = ref(initialPage)
  const perPage = ref(initialPerPage)
  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)

  // Computed
  const totalPages = computed(() => meta.value?.last_page || 0)
  const totalItems = computed(() => meta.value?.total || 0)
  const from = computed(() => meta.value?.from || 0)
  const to = computed(() => meta.value?.to || 0)

  const hasNextPage = computed(() => !!links.value?.next)
  const hasPrevPage = computed(() => !!links.value?.prev)

  const isFirstPage = computed(() => currentPage.value === 1)
  const isLastPage = computed(() => currentPage.value === totalPages.value)

  // Actions
  function setMeta (newMeta: PaginationMeta) {
    meta.value = newMeta
    currentPage.value = newMeta.current_page
    perPage.value = newMeta.per_page
  }

  function setLinks (newLinks: PaginationLinks) {
    links.value = newLinks
  }

  function goToPage (page: number) {
    if (page < 1 || page > totalPages.value) {
      return
    }
    currentPage.value = page
    onPageChange?.(page)
  }

  function nextPage () {
    if (hasNextPage.value) {
      goToPage(currentPage.value + 1)
    }
  }

  function prevPage () {
    if (hasPrevPage.value) {
      goToPage(currentPage.value - 1)
    }
  }

  function firstPage () {
    goToPage(1)
  }

  function lastPage () {
    goToPage(totalPages.value)
  }

  function changePerPage (newPerPage: number) {
    perPage.value = newPerPage
    currentPage.value = 1 // Reset to first page
    onPageChange?.(1)
  }

  function reset () {
    currentPage.value = initialPage
    perPage.value = initialPerPage
    meta.value = null
    links.value = null
  }

  // Génère un texte de pagination ("Affichage de 1 à 20 sur 100")
  const paginationText = computed(() => {
    if (!meta.value) {
      return ''
    }
    const { from, to, total } = meta.value
    if (!from || !to) {
      return `Total: ${total}`
    }
    return `Affichage de ${from} à ${to} sur ${total}`
  })

  // Génère les numéros de pages à afficher (avec ellipses)
  const pageNumbers = computed(() => {
    const total = totalPages.value
    const current = currentPage.value
    const delta = 2 // Nombre de pages avant/après la page courante

    if (total <= 7) {
      // Si peu de pages, les afficher toutes
      return Array.from({ length: total }, (_, i) => i + 1)
    }

    const pages: (number | 'ellipsis')[] = [1]

    // Toujours afficher la première page

    if (current > delta + 2) {
      pages.push('ellipsis')
    }

    // Pages autour de la page courante
    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
      pages.push(i)
    }

    if (current < total - delta - 1) {
      pages.push('ellipsis')
    }

    // Toujours afficher la dernière page
    if (total > 1) {
      pages.push(total)
    }

    return pages
  })

  // Watch pour réinitialiser en cas de changement de perPage
  watch(perPage, () => {
    if (currentPage.value !== 1) {
      currentPage.value = 1
    }
  })

  return {
    // State
    currentPage,
    perPage,
    meta,
    links,

    // Computed
    totalPages,
    totalItems,
    from,
    to,
    hasNextPage,
    hasPrevPage,
    isFirstPage,
    isLastPage,
    paginationText,
    pageNumbers,

    // Actions
    setMeta,
    setLinks,
    goToPage,
    nextPage,
    prevPage,
    firstPage,
    lastPage,
    changePerPage,
    reset,
  }
}
