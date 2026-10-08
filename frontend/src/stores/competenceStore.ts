import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { competenceService } from '@/services/competenceService'

export const useCompetenceStore = defineStore('competence', () => {
  // State
  const competencesRequises = ref([])
  const competencesAcquises = ref([])
  const matrixData = ref(null)
  const gapAnalyses = ref(new Map())
  const trainingPlans = ref(new Map())
  const loading = ref(false)
  const error = ref(null)
  const lastMatrixFetch = ref(null)

  // Getters
  const matrixByJob = computed(() => jobId => {
    if (!matrixData.value || matrixData.value.jobId !== jobId) {
      return null
    }
    return matrixData.value
  })

  const userGapAnalysis = computed(() => userId =>
    gapAnalyses.value.get(userId),
  )

  const userTrainingPlan = computed(() => userId =>
    trainingPlans.value.get(userId),
  )

  const competencesByType = computed(() => {
    const grouped = {}
    for (const comp of competencesRequises.value) {
      if (!grouped[comp.competence_type]) {
        grouped[comp.competence_type] = []
      }
      grouped[comp.competence_type].push(comp)
    }
    return grouped
  })

  // Actions
  const fetchCompetencesRequises = async () => {
    if (competencesRequises.value.length > 0) {
      return
    }

    loading.value = true
    try {
      competencesRequises.value = await competenceService.getCompetencesRequises()
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const fetchMatrix = async (jobId = null, force = false) => {
    const cacheKey = jobId || 'all'
    if (!force && matrixData.value?.jobId === cacheKey
      && lastMatrixFetch.value && Date.now() - lastMatrixFetch.value < 300_000) {
      return
    }

    loading.value = true
    try {
      const data = await competenceService.getMatrix(jobId)
      matrixData.value = { ...data, jobId: cacheKey }
      lastMatrixFetch.value = Date.now()
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const fetchGapAnalysis = async (userId, force = false) => {
    if (!force && gapAnalyses.value.has(userId)) {
      return gapAnalyses.value.get(userId)
    }

    loading.value = true
    try {
      const analysis = await competenceService.getGapAnalysis(userId)
      gapAnalyses.value.set(userId, analysis)
      return analysis
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const generateTrainingPlan = async (userId, force = false) => {
    if (!force && trainingPlans.value.has(userId)) {
      return trainingPlans.value.get(userId)
    }

    loading.value = true
    try {
      const plan = await competenceService.generateTrainingPlan(userId)
      trainingPlans.value.set(userId, plan)
      return plan
    } catch (error_) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const clearCache = () => {
    matrixData.value = null
    gapAnalyses.value.clear()
    trainingPlans.value.clear()
    lastMatrixFetch.value = null
  }

  const invalidateUser = userId => {
    gapAnalyses.value.delete(userId)
    trainingPlans.value.delete(userId)
    // Invalidate matrix if it contains this user
    if (matrixData.value?.users?.some(u => u.id === userId)) {
      matrixData.value = null
      lastMatrixFetch.value = null
    }
  }

  return {
    // State
    competencesRequises,
    competencesAcquises,
    matrixData,
    loading,
    error,
    // Getters
    matrixByJob,
    userGapAnalysis,
    userTrainingPlan,
    competencesByType,
    // Actions
    fetchCompetencesRequises,
    fetchMatrix,
    fetchGapAnalysis,
    generateTrainingPlan,
    clearCache,
    invalidateUser,
  }
})
