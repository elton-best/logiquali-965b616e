export type { Enterprise } from '@/types/api'

export interface ApprovalStats {
  pending: number
  approved: number
  rejected: number
  total: number
}

export type ApprovalFilter = 'all' | 'pending' | 'approved' | 'rejected'
