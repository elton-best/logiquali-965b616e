import type { Person } from '@/modules/clienta/pages/leadership/types'
import { computed, ref, type Ref } from 'vue'

type FilterState = {
  search: string
  role: string
  site: string
}

type FilterOption = { title: string, value: string }

export function usePersonnelFilters (personnel: Ref<Person[]>) {
  const listFilters = ref<FilterState>({
    search: '',
    role: 'all',
    site: 'all',
  })

  const roleFilterOptions = computed<FilterOption[]>(() => {
    const roles = new Set(
      personnel.value
        .map(person => String(person.poste || person.role || '').trim())
        .filter(Boolean),
    )

    return [
      { title: 'Tous les rôles', value: 'all' },
      ...Array.from(roles)
        .toSorted((a, b) => a.localeCompare(b))
        .map(role => ({
          title: role,
          value: role,
        })),
    ]
  })

  const siteFilterOptions = computed<FilterOption[]>(() => {
    const sites = new Set(
      personnel.value
        .map(person => String(person.siteName || '').trim())
        .filter(Boolean),
    )

    return [
      { title: 'Tous les sites', value: 'all' },
      ...Array.from(sites)
        .toSorted((a, b) => a.localeCompare(b))
        .map(site => ({
          title: site,
          value: site,
        })),
    ]
  })

  const filteredPersonnel = computed(() => {
    const query = listFilters.value.search.trim().toLowerCase()

    return personnel.value.filter(person => {
      const matchesSearch
        = !query
          || [
            person.fullName,
            person.email,
            person.poste,
            person.telephone,
            person.siteName,
          ]
            .map(value => String(value || '').toLowerCase())
            .some(value => value.includes(query))

      const matchesRole
        = listFilters.value.role === 'all'
          || String(person.poste || person.role || '').trim()
          === listFilters.value.role

      const matchesSite
        = listFilters.value.site === 'all'
          || String(person.siteName || '').trim() === listFilters.value.site

      return matchesSearch && matchesRole && matchesSite
    })
  })

  function resetListFilters () {
    listFilters.value = {
      search: '',
      role: 'all',
      site: 'all',
    }
  }

  return {
    listFilters,
    roleFilterOptions,
    siteFilterOptions,
    filteredPersonnel,
    resetListFilters,
  }
}
