import type { SubModuleSection } from '@/modules/clienta/types/subscription.types'
import { ref } from 'vue'
import { hasReadAccess } from '@/modules/clienta/utils/accessPermissions'
import { useAccessCatalog } from './useAccessCatalog'

const accessibleSections = ref<SubModuleSection[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const loaded = ref(false)
let inflightPromise: Promise<void> | null = null

export function useSectionAccess () {
  const { fetchCatalog, sections, clearCatalogCache } = useAccessCatalog()

  function dedupeSections (input: SubModuleSection[]): SubModuleSection[] {
    return [...input
      .reduce<Map<string, SubModuleSection>>((acc, section) => {
        const dedupeKey = `${section.module_code}::${section.sub_module_code}::${section.code}`
        const existing = acc.get(dedupeKey)
        if (!existing || section.order < existing.order) {
          acc.set(dedupeKey, section)
        }
        return acc
      }, new Map())
      .values()]
      .toSorted((a, b) => a.order - b.order)
  }

  const fetchAccessibleSections = async (force = false) => {
    if (!force && loaded.value) {
      return
    }

    if (inflightPromise) {
      return inflightPromise
    }

    inflightPromise = (async () => {
      loading.value = true
      error.value = null

      try {
        await fetchCatalog(force)
        const data = Array.isArray(sections.value) ? sections.value : []
        accessibleSections.value = dedupeSections(data)
        loaded.value = true

        // Cache dans localStorage
        localStorage.setItem('cached_sections', JSON.stringify(accessibleSections.value))
        localStorage.setItem('cached_sections_timestamp', Date.now().toString())
      } catch (error_: any) {
        console.error('Erreur chargement sections:', error_)
        error.value = error_.message
        loaded.value = false

        // Fallback sur cache
        const cached = localStorage.getItem('cached_sections')
        if (cached) {
          accessibleSections.value = dedupeSections(JSON.parse(cached))
        }
      } finally {
        loading.value = false
        inflightPromise = null
      }
    })()

    return inflightPromise
  }

  const getSectionsBySubModule = (subModuleCode: string) => {
    return accessibleSections.value
      .filter(s => s.sub_module_code === subModuleCode)
      .toSorted((a: SubModuleSection, b: SubModuleSection) => a.order - b.order)
  }

  const canAccessSection = (code: string) => {
    return accessibleSections.value.some(s => s.code === code && hasReadAccess(s.permissions))
  }

  const clearSectionsCache = () => {
    clearCatalogCache()
    accessibleSections.value = []
    loaded.value = false
    inflightPromise = null
    localStorage.removeItem('cached_sections')
    localStorage.removeItem('cached_sections_timestamp')
  }

  return {
    accessibleSections,
    loading,
    error,
    fetchAccessibleSections,
    getSectionsBySubModule,
    canAccessSection,
    clearSectionsCache,
  }
}
