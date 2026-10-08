import type { SubModule } from '@/modules/clienta/types/subscription.types'
import { ref } from 'vue'
import { hasReadAccess } from '@/modules/clienta/utils/accessPermissions'
import { canonicalizeCompanyRoute } from '@/modules/clienta/utils/routeCanonicalizer'
import { useAccessCatalog } from './useAccessCatalog'

const accessibleSubModules = ref<SubModule[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const loaded = ref(false)
let inflightPromise: Promise<void> | null = null

function normalizeSubModules (input: SubModule[]): SubModule[] {
  const normalized = input.map(subModule => {
    if (subModule.module_code === 'leadership') {
      if (subModule.code === 'politique' || subModule.code === 'politique_qhse') {
        return {
          ...subModule,
          code: 'politique',
          name: 'Politique QHSE',
          route: '/company/leadership/policy',
        }
      }

      if (subModule.code === 'roles_responsabilites') {
        return {
          ...subModule,
          route: '/company/leadership/roles',
        }
      }
    }

    if (subModule.module_code === 'support' && subModule.code === 'documents') {
      return {
        ...subModule,
        name: 'Information documentée',
        route: '/company/support/document-inventory',
      }
    }

    if (subModule.module_code === 'evaluation' && subModule.code === 'satisfaction_client') {
      return {
        ...subModule,
        name: 'Evaluations PIP',
        route: '/company/performance/surveillance',
      }
    }

    return {
      ...subModule,
      route: canonicalizeCompanyRoute(
        subModule.route,
        `/company/${subModule.module_code}/${subModule.code}`,
      ),
    }
  })

  const supportRows = normalized.filter(sm => sm.module_code === 'support')

  const improvementRows = normalized.filter(sm => sm.module_code === 'amelioration')
  const nonConformites = improvementRows.find(sm => sm.code === 'non_conformites')
  const actionsCorrectives = improvementRows.find(sm => sm.code === 'actions_correctives')
  const mergedImprovement = nonConformites || actionsCorrectives

  const deduped = normalized
    .reduce<Map<string, SubModule>>((acc, subModule) => {
      const dedupeKey = `${subModule.module_code}::${subModule.code}`
      const existing = acc.get(dedupeKey)
      if (!existing || subModule.order < existing.order) {
        acc.set(dedupeKey, subModule)
      }
      return acc
    }, new Map())
  const normalizedRows = [...deduped.values()]
    .filter(sm => {
      if (sm.module_code !== 'amelioration') {
        return true
      }
      if (sm.code === 'non_conformites_actions') {
        return true
      }
      if (sm.code !== 'non_conformites' && sm.code !== 'actions_correctives') {
        return true
      }
      if (!mergedImprovement) {
        return true
      }
      const primaryCode = nonConformites ? 'non_conformites' : 'actions_correctives'
      return sm.code === primaryCode
    })
    .map(sm => {
      if (sm.module_code === 'amelioration') {
        if (sm.code === 'non_conformites_actions') {
          return sm
        }

        if (sm.code === 'non_conformites' || sm.code === 'actions_correctives') {
          const baseOrder = Math.min(
            nonConformites?.order ?? Number.MAX_SAFE_INTEGER,
            actionsCorrectives?.order ?? Number.MAX_SAFE_INTEGER,
            sm.order,
          )

          return {
            ...sm,
            code: 'non_conformites_actions',
            name: 'Non-conformités et actions correctives',
            route: '/company/nonconformities',
            icon: 'mdi-alert-octagon-outline',
            order: baseOrder,
          }
        }
      }

      return sm
    })

  const hasEvaluationHub = normalizedRows.some(sm => sm.module_code === 'evaluation' && sm.code === 'satisfaction_client')
  const hasProcessReview = normalizedRows.some(sm => sm.module_code === 'evaluation' && sm.code === 'revue_processus')

  if (hasEvaluationHub && !hasProcessReview) {
    const base = normalizedRows.find(sm => sm.module_code === 'evaluation' && sm.code === 'satisfaction_client')
    if (base) {
      normalizedRows.push({
        ...base,
        id: -99_001,
        code: 'revue_processus',
        name: 'Revue Processus',
        route: '/company/performance/surveillance/process-review',
        icon: 'mdi-clipboard-text-search-outline',
        order: (base.order || 1) + 1,
      })
    }
  }

  return normalizedRows.toSorted((a: SubModule, b: SubModule) => a.order - b.order)
}

export function useSubModuleAccess () {
  const { fetchCatalog, subModules, clearCatalogCache } = useAccessCatalog()

  const fetchAccessibleSubModules = async (force = false) => {
    if (!force && loaded.value) {
      accessibleSubModules.value = normalizeSubModules(accessibleSubModules.value)
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
        const data = Array.isArray(subModules.value) ? subModules.value : []
        const normalized = normalizeSubModules(data)
        accessibleSubModules.value = normalized
        loaded.value = true

        // Cache dans localStorage
        localStorage.setItem('cached_sub_modules', JSON.stringify(normalized))
        localStorage.setItem('cached_sub_modules_timestamp', Date.now().toString())
      } catch (error_: any) {
        console.error('Erreur chargement sous-modules:', error_)
        error.value = error_.message
        loaded.value = false

        // Fallback sur cache
        const cached = localStorage.getItem('cached_sub_modules')
        if (cached) {
          accessibleSubModules.value = normalizeSubModules(JSON.parse(cached))
        }
      } finally {
        loading.value = false
        inflightPromise = null
      }
    })()

    return inflightPromise
  }

  const getSubModulesByModule = (moduleCode: string) => {
    return accessibleSubModules.value
      .filter(sm => sm.module_code === moduleCode)
      .toSorted((a: SubModule, b: SubModule) => a.order - b.order)
  }

  const canAccessSubModule = (code: string) => {
    return accessibleSubModules.value.some(sm => sm.code === code && hasReadAccess(sm.permissions))
  }

  const clearSubModulesCache = () => {
    clearCatalogCache()
    accessibleSubModules.value = []
    loaded.value = false
    inflightPromise = null
    localStorage.removeItem('cached_sub_modules')
    localStorage.removeItem('cached_sub_modules_timestamp')
  }

  return {
    accessibleSubModules,
    loading,
    error,
    fetchAccessibleSubModules,
    getSubModulesByModule,
    canAccessSubModule,
    clearSubModulesCache,
  }
}
