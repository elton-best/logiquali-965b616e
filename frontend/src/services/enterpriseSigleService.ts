import api from '@/api/client'

/**
 * Sigle change preview response
 */
export interface SiglePreviewResponse {
  current_sigle: string
  new_sigle: string
  equipements_affected: number
  equipment_sample: Array<{
    id: number
    nom_commun: string
    current_code: string
    new_code: string
  }>
  warnings: string[]
  can_proceed: boolean
}

/**
 * Sigle update response
 */
export interface SigleUpdateResponse {
  success: boolean
  enterprise_id: number
  old_sigle: string
  new_sigle: string
  equipements_recoded: number
  sigle_history_id: number
  changed_by: number
  changed_at: string
}

/**
 * Sigle history entry
 */
export interface SigleHistoryEntry {
  id: number
  enterprise_id: number
  old_sigle: string | null
  new_sigle: string
  reason: string | null
  recoding_mode: 'auto' | 'none'
  equipements_affected: number
  changed_by: number
  is_reverted: boolean
  reverted_by: number | null
  created_at: string
  updated_at: string
}

const BASE_URL = '/enterprises'

/**
 * Enterprise sigle management service
 */
export const enterpriseSigleService = {
  /**
   * Preview sigle change without persisting
   */
  async previewSigleChange (
    enterpriseId: number,
    newSigle: string,
  ): Promise<SiglePreviewResponse> {
    const response = await api.post<SiglePreviewResponse>(
      `${BASE_URL}/${enterpriseId}/sigle/preview`,
      {
        new_sigle: newSigle,
      },
    )
    return response.data
  },

  /**
   * Update sigle with atomic transaction and history
   */
  async updateSigle (
    enterpriseId: number,
    newSigle: string,
    reason?: string,
    recodeEquipements = false,
  ): Promise<SigleUpdateResponse> {
    const response = await api.put<SigleUpdateResponse>(
      `${BASE_URL}/${enterpriseId}/sigle`,
      {
        new_sigle: newSigle,
        reason: reason || null,
        recode_equipements: recodeEquipements,
      },
    )
    return response.data
  },

  /**
   * Fetch sigle history for an enterprise
   */
  async getSigleHistory (enterpriseId: number): Promise<SigleHistoryEntry[]> {
    const response = await api.get<SigleHistoryEntry[]>(
      `${BASE_URL}/${enterpriseId}/sigle/history`,
    )
    return response.data || []
  },
}
