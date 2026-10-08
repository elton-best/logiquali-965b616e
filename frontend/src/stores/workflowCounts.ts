/**
 * Store Pinia — Compteurs de workflow documentaire.
 *
 * Centralise les compteurs "En attente de vérification" et "En attente d'approbation"
 * pour les afficher comme badges dans la sidebar sans re-fetch par composant.
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/client'

export const useWorkflowCountsStore = defineStore('workflowCounts', () => {
  const pendingVerification = ref(0)
  const pendingApproval = ref(0)
  const myTasksCount = ref(0)
  const loading = ref(false)
  const lastFetchedAt = ref<number | null>(null)

  /** TTL du cache : 90 secondes */
  const CACHE_TTL_MS = 90_000

  function isCacheValid (): boolean {
    return lastFetchedAt.value !== null
      && (Date.now() - lastFetchedAt.value) < CACHE_TTL_MS
  }

  /**
   * Charge les compteurs depuis l'API.
   * @param force — ignore le cache si true
   */
  async function fetchCounts (force = false): Promise<void> {
    if (!force && isCacheValid()) return

    loading.value = true
    try {
      const [verifyRes, approveRes, tasksRes] = await Promise.allSettled([
        api.get('/document-code-workflow/pending-verification'),
        api.get('/document-code-workflow/pending-approval'),
        api.get('/my-tasks', { params: { month: new Date().toISOString().slice(0, 7), status: 'all' } }),
      ])

      if (verifyRes.status === 'fulfilled') {
        const data = verifyRes.value.data
        pendingVerification.value = Array.isArray(data?.data)
          ? data.data.length
          : (Array.isArray(data) ? data.length : (data?.total ?? 0))
      }

      if (approveRes.status === 'fulfilled') {
        const data = approveRes.value.data
        pendingApproval.value = Array.isArray(data?.data)
          ? data.data.length
          : (Array.isArray(data) ? data.length : (data?.total ?? 0))
      }

      if (tasksRes.status === 'fulfilled') {
        const tasks = Array.isArray(tasksRes.value?.data?.data)
          ? tasksRes.value.data.data
          : []
        myTasksCount.value = tasks.filter((t: any) => t?.status !== 'termine').length
      }

      lastFetchedAt.value = Date.now()
    } catch {
      // Silencieux — les badges ne sont pas critiques
    } finally {
      loading.value = false
    }
  }

  /** Incrémente/décrémente les compteurs localement après une action (évite un re-fetch) */
  function decrementVerification () {
    pendingVerification.value = Math.max(0, pendingVerification.value - 1)
  }

  function decrementApproval () {
    pendingApproval.value = Math.max(0, pendingApproval.value - 1)
  }

  function invalidate () {
    lastFetchedAt.value = null
  }

  return {
    pendingVerification,
    pendingApproval,
    myTasksCount,
    loading,
    fetchCounts,
    decrementVerification,
    decrementApproval,
    invalidate,
  }
})
