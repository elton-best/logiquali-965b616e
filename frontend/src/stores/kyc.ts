/**
 * KYC Store (Pinia)
 * Manage KYC state for Client A registration
 */

import type {
  CreateKYCPayload,
  KYCRequest,
  KYCStatistics,
  ReviewKYCPayload,
} from '@/types/models/kyc'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { kycService } from '@/api/services/kyc.service'

export const useKYCStore = defineStore('kyc', () => {
  // State
  const requests = ref<KYCRequest[]>([])
  const currentRequest = ref<KYCRequest | null>(null)
  const statistics = ref<KYCStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const currentPage = ref(1)
  const perPage = ref(10)
  const total = ref(0)

  // Computed
  const pendingRequests = computed(() =>
    requests.value.filter(r => r.status === 'pending'),
  )

  const underReviewRequests = computed(() =>
    requests.value.filter(r => r.status === 'under_review'),
  )

  // Actions
  async function submitKYC (payload: CreateKYCPayload): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await kycService.submitKYC(payload)
      currentRequest.value = response.data
      return true
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la soumission'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchKYCRequests (filters?: { status?: string }) {
    loading.value = true
    error.value = null

    try {
      const response = await kycService.getKYCRequests({
        ...filters,
        page: currentPage.value,
        per_page: perPage.value,
      })

      requests.value = response.data.data
      total.value = response.data.meta?.total || 0
      currentPage.value = response.data.meta?.current_page || 1
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchKYCRequest (id: number) {
    loading.value = true
    error.value = null

    try {
      const response = await kycService.getKYCRequest(id)
      currentRequest.value = response.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function reviewKYC (id: number, payload: ReviewKYCPayload): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await kycService.reviewKYC(id, payload)
      currentRequest.value = response.data

      // Update in list
      const index = requests.value.findIndex(r => r.id === id)
      if (index !== -1) {
        requests.value[index] = response.data
      }

      return true
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la révision'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    try {
      const response = await kycService.getKYCStatistics()
      statistics.value = response.data
    } catch (error_: any) {
      console.error('Failed to fetch KYC statistics:', error_)
    }
  }

  async function downloadDocument (requestId: number, documentId: number) {
    try {
      await kycService.downloadDocument(requestId, documentId)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du téléchargement'
      throw error_
    }
  }

  function setPage (page: number) {
    currentPage.value = page
  }

  function clearError () {
    error.value = null
  }

  return {
    // State
    requests,
    currentRequest,
    statistics,
    loading,
    error,
    currentPage,
    perPage,
    total,

    // Computed
    pendingRequests,
    underReviewRequests,

    // Actions
    submitKYC,
    fetchKYCRequests,
    fetchKYCRequest,
    reviewKYC,
    fetchStatistics,
    downloadDocument,
    setPage,
    clearError,
  }
})
