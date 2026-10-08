import type { ModuleWithPermissions } from '../types/subscription.types'
import { computed, ref } from 'vue'
import { modulesApi } from '@/api/subscription'

export function useModuleAccess () {
  const modules = ref<ModuleWithPermissions[]>([])
  const loading = ref(false)

  const fetchAccessibleModules = async () => {
    loading.value = true
    try {
      const response = await modulesApi.getAccessible()
      modules.value = response.data.modules
    } catch (error) {
      console.error('Erreur lors de la récupération des modules', error)
      modules.value = []
    } finally {
      loading.value = false
    }
  }

  const canAccess = (moduleIdentifier: string, action: 'read' | 'create' | 'update' | 'delete' | 'validate' = 'read') => {
    const module = modules.value.find(m => m.identifier === moduleIdentifier)
    return module?.permissions[action] ?? false
  }

  const getModulePermissions = (moduleIdentifier: string) => {
    const module = modules.value.find(m => m.identifier === moduleIdentifier)
    return module?.permissions ?? { read: false, create: false, update: false, delete: false, validate: false }
  }

  const filterAccessibleRoutes = (routes: any[]) => {
    return routes.filter(route => {
      if (!route.meta?.moduleId) {
        return true
      }
      return canAccess(route.meta.moduleId, 'read')
    })
  }

  const accessibleModules = computed(() => modules.value.filter(m => m.permissions.read))

  return {
    modules,
    loading,
    accessibleModules,
    fetchAccessibleModules,
    canAccess,
    getModulePermissions,
    filterAccessibleRoutes,
  }
}
