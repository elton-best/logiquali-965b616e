import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { userService } from '@/services/userService'

export const useUserStore = defineStore('user', () => {
  // State
  const users = ref([])
  const jobDescriptions = ref([])
  const loading = ref(false)
  const error = ref(null)
  const lastFetch = ref(null)

  // Getters
  const usersByJob = computed(() => jobId =>
    users.value.filter(user => user.job_description_id === jobId),
  )

  const activeUsers = computed(() =>
    users.value.filter(user => user.status === 'active'),
  )

  const getUserById = computed(() => id =>
    users.value.find(user => user.id === id),
  )

  const usersWithExpiredHabilitations = computed(() =>
    users.value.filter(user =>
      user.habilitations?.some(h =>
        h.expiry_date && new Date(h.expiry_date) < new Date(),
      ),
    ),
  )

  // Actions
  const fetchUsers = async (force = false) => {
    if (!force && users.value.length > 0 && lastFetch.value
      && Date.now() - lastFetch.value < 300_000) {
      return
    }

    loading.value = true
    error.value = null
    try {
      users.value = await userService.getAll()
      lastFetch.value = Date.now()
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const fetchJobDescriptions = async () => {
    if (jobDescriptions.value.length > 0) {
      return
    }

    loading.value = true
    try {
      jobDescriptions.value = await userService.getJobDescriptions()
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateUser = (userId, updates) => {
    const index = users.value.findIndex(u => u.id === userId)
    if (index !== -1) {
      users.value[index] = { ...users.value[index], ...updates }
    }
  }

  const addHabilitation = (userId, habilitation) => {
    const user = users.value.find(u => u.id === userId)
    if (user) {
      if (!user.habilitations) {
        user.habilitations = []
      }
      user.habilitations.push(habilitation)
    }
  }

  const updateHabilitation = (userId, habilitationId, updates) => {
    const user = users.value.find(u => u.id === userId)
    if (user?.habilitations) {
      const index = user.habilitations.findIndex(h => h.id === habilitationId)
      if (index !== -1) {
        user.habilitations[index] = { ...user.habilitations[index], ...updates }
      }
    }
  }

  const removeHabilitation = (userId, habilitationId) => {
    const user = users.value.find(u => u.id === userId)
    if (user?.habilitations) {
      user.habilitations = user.habilitations.filter(h => h.id !== habilitationId)
    }
  }

  return {
    // State
    users,
    jobDescriptions,
    loading,
    error,
    // Getters
    usersByJob,
    activeUsers,
    getUserById,
    usersWithExpiredHabilitations,
    // Actions
    fetchUsers,
    fetchJobDescriptions,
    updateUser,
    addHabilitation,
    updateHabilitation,
    removeHabilitation,
  }
})
