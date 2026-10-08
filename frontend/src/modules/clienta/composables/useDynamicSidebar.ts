import type { AxiosError } from 'axios'
import axios from 'axios'
import { computed, ref } from 'vue'

interface SidebarItem {
  key: string
  label: string
  icon: string
  to: string
  permissions: string[]
  visible: boolean
}

interface SidebarData {
  sidebar: SidebarItem[]
  active_norms: number[]
  active_permissions: string[]
  user_id: number
  enterprise_id: number
  site_id: number
}

export function useDynamicSidebar () {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const sidebarData = ref<SidebarData | null>(null)

  const fetchSidebar = async (): Promise<SidebarData | null> => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.get<{ success: boolean, data: SidebarData }>('/api/v1/users/sidebar')
      sidebarData.value = response.data.data || null
      return sidebarData.value
    } catch (error_) {
      const axiosError = error_ as AxiosError
      error.value = axiosError.message || 'Erreur lors du chargement du menu'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const visibleItems = computed(() => {
    return sidebarData.value?.sidebar.filter(item => item.visible) || []
  })

  const hasPermission = computed(() => (permission: string) => {
    return sidebarData.value?.active_permissions.includes(permission) || false
  })

  const activeNorms = computed(() => sidebarData.value?.active_norms || [])

  return {
    loading,
    error,
    sidebarData,
    visibleItems,
    hasPermission,
    activeNorms,
    fetchSidebar,
  }
}
