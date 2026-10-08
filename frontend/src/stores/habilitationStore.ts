import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useNotification } from '@/plugins/notification'
import { habilitationService } from '@/services/habilitationService'

export const useHabilitationStore = defineStore('habilitation', () => {
  // State
  const habilitations = ref([])
  const loading = ref(false)
  const error = ref(null)
  const lastFetch = ref(null)

  const notification = useNotification()

  // Getters
  const expiring = computed(() =>
    habilitations.value.filter(h => {
      if (!h.expiry_date) {
        return false
      }
      const days = Math.ceil((new Date(h.expiry_date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
      return days <= 30 && days > 0
    }),
  )

  const expired = computed(() =>
    habilitations.value.filter(h =>
      h.expiry_date && new Date(h.expiry_date) < new Date(),
    ),
  )

  const byUser = computed(() => userId =>
    habilitations.value.filter(h => h.user_id === userId),
  )

  // Actions
  const fetchAll = async (force = false) => {
    if (!force && habilitations.value.length > 0 && lastFetch.value
      && Date.now() - lastFetch.value < 300_000) {
      return
    } // 5min cache

    loading.value = true
    error.value = null
    try {
      habilitations.value = await habilitationService.getAll()
      lastFetch.value = Date.now()
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const create = async data => {
    loading.value = true
    try {
      const newHabilitation = await habilitationService.create(data)
      habilitations.value.push(newHabilitation)

      notification.success(`Habilitation créée pour ${newHabilitation.user?.name}`)

      return newHabilitation
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const update = async (id, data) => {
    loading.value = true
    try {
      const updated = await habilitationService.update(id, data)
      const index = habilitations.value.findIndex(h => h.id === id)
      if (index !== -1) {
        habilitations.value[index] = updated
      }
      return updated
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const remove = async id => {
    loading.value = true
    try {
      await habilitationService.delete(id)
      habilitations.value = habilitations.value.filter(h => h.id !== id)
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const renew = async (id, data) => {
    loading.value = true
    try {
      const renewed = await habilitationService.renew(id, data)
      const index = habilitations.value.findIndex(h => h.id === id)
      if (index !== -1) {
        habilitations.value[index] = renewed
      }
      return renewed
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    habilitations,
    loading,
    error,
    // Getters
    expiring,
    expired,
    byUser,
    // Actions
    fetchAll,
    create,
    update,
    remove,
    renew,
    fetchHabilitations: fetchAll,
    createHabilitation: create,
    updateHabilitation: update,
    deleteHabilitation: remove,
    invalidateUser: _userId => {
      lastFetch.value = null
    },
  }
})
