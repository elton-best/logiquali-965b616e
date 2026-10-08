import type { Axe } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

export const useAxeStore = defineStore('axe', () => {
  // State
  const axes = ref<Axe[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const activeAxes = computed(() => axes.value.filter(axe => axe.is_active))
  const inactiveAxes = computed(() => axes.value.filter(axe => !axe.is_active))

  const isQualityActive = computed(() =>
    axes.value.find(a => a.code === 'Q')?.is_active || false,
  )
  const isHSActive = computed(() =>
    axes.value.find(a => a.code === 'HS')?.is_active || false,
  )
  const isEnvironmentActive = computed(() =>
    axes.value.find(a => a.code === 'E')?.is_active || false,
  )

  // Mode système
  const isSMQ = computed(() =>
    isQualityActive.value && !isHSActive.value && !isEnvironmentActive.value,
  )
  const isSMI = computed(() =>
    isQualityActive.value && (isHSActive.value || isEnvironmentActive.value),
  )

  const systemMode = computed(() => {
    if (isSMI.value) {
      return 'SMI (Système de Management Intégré)'
    }
    if (isSMQ.value) {
      return 'SMQ (Système de Management Qualité)'
    }
    return 'Non configuré'
  })

  // Actions
  async function fetchAxes (activeOnly = false) {
    loading.value = true
    error.value = null

    try {
      const params = activeOnly ? { active: true } : {}
      const { data } = await api.get('/axes', { params })
      axes.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement des axes'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function toggleAxe (axeId: number) {
    const axe = axes.value.find(a => a.id === axeId)
    if (!axe) {
      return
    }

    try {
      const { data } = await api.put(`/axes/${axeId}`, {
        is_active: !axe.is_active,
      })

      // Mettre à jour localement
      const index = axes.value.findIndex(a => a.id === axeId)
      if (index !== -1) {
        axes.value[index] = data
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la mise à jour'
      throw error_
    }
  }

  function getAxeByCode (code: 'Q' | 'HS' | 'E'): Axe | undefined {
    return axes.value.find(a => a.code === code)
  }

  function getAxeById (id: number): Axe | undefined {
    return axes.value.find(a => a.id === id)
  }

  return {
    // State
    axes,
    loading,
    error,

    // Getters
    activeAxes,
    inactiveAxes,
    isQualityActive,
    isHSActive,
    isEnvironmentActive,
    isSMQ,
    isSMI,
    systemMode,

    // Actions
    fetchAxes,
    toggleAxe,
    getAxeByCode,
    getAxeById,
  }
})
