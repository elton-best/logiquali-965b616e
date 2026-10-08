import type { PaginatedResponse } from '@/types/shared'
import api from '@/api/client'

/**
 * Equipment transfer history entry
 */
export interface TransferHistoryEntry {
  id: number
  equipement_id: number
  enterprise_id: number
  previous_site_id: number | null
  previous_localisation_id: number | null
  new_site_id: number
  new_localisation_id: number
  transfer_reason_code: string
  transfer_notes: string | null
  is_verified: boolean
  transferred_by: number
  transferred_at: string
  verified_by: number | null
  verified_at: string | null
  verification_notes: string | null
  created_at: string
  updated_at: string
  // Relations (lazy loaded)
  equipement?: {
    id: number
    code_complet: string
    nom_commun: string
  }
  previousSite?: { id: number, name: string }
  newSite?: { id: number, name: string }
  previousLocalisation?: { id: number, code: string, name: string }
  newLocalisation?: { id: number, code: string, name: string }
  transferReason?: { id: number, code: string, name: string }
  transferredByUser?: { id: number, name: string, email: string }
  verifiedByUser?: { id: number, name: string, email: string }
}

/**
 * Transfer reason code
 */
export interface TransferReasonCode {
  id: number
  code: string
  name: string
  description?: string
}

/**
 * Equipment transfer service
 */
export const equipmentTransferService = {
  /**
   * Get transfer history for specific equipment
   */
  async getEquipmentTransfers (
    equipementId: number,
  ): Promise<TransferHistoryEntry[]> {
    const response = await api.get<TransferHistoryEntry[]>(
      `/equipements/${equipementId}/transfers`,
    )
    return response.data || []
  },

  /**
   * Register an equipment transfer
   */
  async registerTransfer (
    equipementId: number,
    newSiteId: number,
    newLocalisationId: number,
    reasonCode: string,
    notes?: string,
  ): Promise<{
    success: boolean
    transfer_id: number
    equipement_id: number
  }> {
    const response = await api.post(`/equipements/${equipementId}/transfer`, {
      new_site_id: newSiteId,
      new_localisation_id: newLocalisationId,
      transfer_reason_code: reasonCode,
      transfer_notes: notes || null,
    })
    return response.data
  },

  /**
   * Get transfer history with filters
   */
  async listTransfers (
    enterpriseId: number,
    filters?: {
      equipement_id?: number
      status?: 'pending' | 'verified' | 'all'
      reason_code?: string
      page?: number
      per_page?: number
    },
  ): Promise<PaginatedResponse<TransferHistoryEntry>> {
    const response = await api.get<PaginatedResponse<TransferHistoryEntry>>(
      '/transfers',
      {
        params: {
          enterprise_id: enterpriseId,
          ...filters,
        },
      },
    )
    return response.data
  },

  /**
   * Get transfer details
   */
  async getTransfer (transferId: number): Promise<TransferHistoryEntry> {
    const response = await api.get<TransferHistoryEntry>(
      `/transfers/${transferId}`,
    )
    return response.data
  },

  /**
   * Verify/approve a transfer
   */
  async verifyTransfer (
    transferId: number,
    verificationNotes?: string,
  ): Promise<{
    success: boolean
    transfer_id: number
    is_verified: boolean
  }> {
    const response = await api.put(`/transfers/${transferId}/verify`, {
      verification_notes: verificationNotes || null,
    })
    return response.data
  },

  /**
   * Get available transfer reason codes for an enterprise
   */
  async getTransferReasons (
    enterpriseId: number,
  ): Promise<TransferReasonCode[]> {
    const response = await api.get<TransferReasonCode[]>(
      `/enterprises/${enterpriseId}/transfer-reasons`,
    )
    return response.data || []
  },
}
