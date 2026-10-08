import { computed, reactive, ref, watch } from 'vue'
import api from '@/api/client'

export interface CollaboratorOption {
  id: number
  full_name: string
  email: string
  site_id?: number | null
  site_name?: string | null
  department?: string | null
  job_title?: string | null
}

export interface CollaboratorFilters {
  search: string
  siteId: number | null
  department: string
  jobTitle: string
}

export function useCollaborators () {
  const collaboratorCatalog = ref<CollaboratorOption[]>([])
  const collaborators = ref<CollaboratorOption[]>([])
  const loadingCollaborators = ref(false)
  const collaboratorFilters = reactive<CollaboratorFilters>({
    search: '',
    siteId: null,
    department: '',
    jobTitle: '',
  })

  const siteOptions = computed(() => {
    const seen = new Map<number, string>()
    for (const collaborator of collaboratorCatalog.value) {
      if (collaborator.site_id && collaborator.site_name) {
        seen.set(collaborator.site_id, collaborator.site_name)
      }
    }
    return Array.from(seen.entries()).map(([value, title]) => ({ title, value }))
  })

  const departmentOptions = computed(() => {
    const seen = new Set<string>()
    for (const collaborator of collaboratorCatalog.value) {
      if (collaborator.department) {
        seen.add(collaborator.department)
      }
    }
    return Array.from(seen.values()).map(title => ({ title, value: title }))
  })

  async function loadCollaborators (catalog = false) {
    loadingCollaborators.value = true
    try {
      const params = catalog
        ? undefined
        : {
            search: collaboratorFilters.search || undefined,
            site_id: collaboratorFilters.siteId || undefined,
            department: collaboratorFilters.department || undefined,
            job_title: collaboratorFilters.jobTitle || undefined,
          }

      const { data } = await api.get('/users/job-description-collaborators', { params })
      const rows = Array.isArray(data?.data) ? data.data : []
      const mapped = rows.map((row: any) => ({
        id: Number(row.id),
        full_name: String(row.full_name || row.name || row.email || `Collaborateur ${row.id}`),
        email: String(row.email || ''),
        site_id: row.site_id !== undefined && row.site_id !== null ? Number(row.site_id) : null,
        site_name: row.site_name ? String(row.site_name) : null,
        department: row.department ? String(row.department) : null,
        job_title: row.job_title ? String(row.job_title) : null,
      }))

      if (catalog) {
        collaboratorCatalog.value = mapped
      } else {
        collaborators.value = mapped
      }
    } finally {
      loadingCollaborators.value = false
    }
  }

  function resetCollaboratorFilters () {
    collaboratorFilters.search = ''
    collaboratorFilters.siteId = null
    collaboratorFilters.department = ''
    collaboratorFilters.jobTitle = ''
  }

  function selectVisibleCollaborators (currentSelection: number[] = []) {
    const selected = new Set(currentSelection)
    for (const collaborator of collaborators.value) {
      selected.add(collaborator.id)
    }
    return Array.from(selected)
  }

  watch(
    () => [
      collaboratorFilters.search,
      collaboratorFilters.siteId,
      collaboratorFilters.department,
      collaboratorFilters.jobTitle,
    ],
    () => {
      void loadCollaborators()
    },
    { immediate: true },
  )

  void loadCollaborators(true)

  return {
    collaborators,
    loadingCollaborators,
    collaboratorFilters,
    siteOptions,
    departmentOptions,
    loadCollaborators,
    resetCollaboratorFilters,
    selectVisibleCollaborators,
  }
}
