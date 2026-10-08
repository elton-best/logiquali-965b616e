/**
 * Document Review Module Types
 */

export interface DocumentReview {
  id: number
  tenant_id: number
  document_id: number
  document?: {
    id: number
    title: string
    reference: string
    version: string
  }
  review_type: ReviewType
  status: ReviewStatus
  requested_by_id: number
  requested_by?: { id: number, name: string }
  assigned_to_id?: number
  assigned_to?: { id: number, name: string }
  due_date?: string
  priority: ReviewPriority
  comments?: string
  review_comments?: ReviewComment[]
  decision?: ReviewDecision
  decision_reason?: string
  completed_at?: string
  created_at: string
  updated_at: string
}

export type ReviewType
  = | 'periodic'
    | 'revision'
    | 'approval'
    | 'verification'
    | 'validation'

export type ReviewStatus
  = | 'pending'
    | 'in_progress'
    | 'completed'
    | 'cancelled'

export type ReviewPriority = 'low' | 'medium' | 'high' | 'urgent'

export type ReviewDecision
  = | 'approved'
    | 'approved_with_changes'
    | 'rejected'
    | 'needs_revision'

export interface ReviewComment {
  id: number
  review_id: number
  user_id: number
  user?: { id: number, name: string }
  comment: string
  type: 'general' | 'change_request' | 'question'
  resolved: boolean
  created_at: string
}

export interface CreateReviewPayload {
  document_id: number
  review_type: ReviewType
  assigned_to_id?: number
  due_date?: string
  priority: ReviewPriority
  comments?: string
}

export interface UpdateReviewPayload {
  status?: ReviewStatus
  assigned_to_id?: number
  due_date?: string
  priority?: ReviewPriority
  decision?: ReviewDecision
  decision_reason?: string
}

export interface AddReviewCommentPayload {
  comment: string
  type: 'general' | 'change_request' | 'question'
}
