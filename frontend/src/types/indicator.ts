/**
 * Indicator Module Types
 * Indicateurs de performance QHSE (ISO 9001:2015 §9.1.1, ISO 14001:2015 §9.1, ISO 45001:2018 §9.1)
 */

import type {
  BaseEntity,
  BaseFilters,
  PaginationLinks,
  PaginationMeta, ProcessReference, UserReference,
} from './shared'

// ============================================
// ENUMS
// ============================================

export type IndicatorType = 'kpi' | 'objective' | 'target' | 'metric'
export type IndicatorCategory = 'quality' | 'environment' | 'health_safety' | 'performance' | 'financial'
export type IndicatorFrequency = 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly'
export type IndicatorStatus = 'active' | 'inactive' | 'archived' | 'draft'
export type IndicatorTrendDirection = 'up' | 'down' | 'stable'
export type IndicatorAlertLevel = 'green' | 'yellow' | 'red'
export type ChartType = 'line' | 'bar' | 'area' | 'pie' | 'donut' | 'radar' | 'gauge' | 'heatmap'

// ============================================
// MAIN ENTITIES
// ============================================

export interface Indicator extends BaseEntity {
  code: string
  name: string
  description?: string
  type: IndicatorType
  category: IndicatorCategory
  frequency: IndicatorFrequency
  status: IndicatorStatus

  // Measurement
  unit: string
  formula?: string

  // Targets & Thresholds
  target_value?: number
  min_acceptable?: number
  max_acceptable?: number
  warning_threshold_low?: number
  warning_threshold_high?: number
  critical_threshold_low?: number
  critical_threshold_high?: number

  // Display
  chart_type: ChartType
  display_order?: number
  color?: string
  icon?: string

  // Responsibility
  responsible_id?: number
  responsible?: UserReference

  // Relations
  processus_id?: number
  processus?: ProcessReference

  // Current value
  current_value?: number
  previous_value?: number
  trend?: IndicatorTrendDirection
  alert_level?: IndicatorAlertLevel
  last_measured_at?: string

  // Metadata
  is_cumulative: boolean
  is_reversed: boolean // Si true, valeur basse = bon
  data_source?: string
  calculation_method?: string

  // Timestamps
  active_from?: string
  active_until?: string
}

// ============================================
// INDICATOR VALUES (Historical data)
// ============================================

export interface IndicatorValue extends BaseEntity {
  indicator_id: number
  indicator?: IndicatorReference

  value: number
  period_start: string
  period_end: string

  // Calculated fields
  target_value?: number
  achievement_rate?: number
  alert_level?: IndicatorAlertLevel

  // Context
  note?: string
  measured_by_id?: number
  measured_by?: UserReference
  validated: boolean
  validated_by_id?: number
  validated_by?: UserReference
  validated_at?: string
}

// ============================================
// OBJECTIVES (SMART Goals)
// ============================================

export interface Objective extends BaseEntity {
  code: string
  title: string
  description?: string

  // SMART criteria
  is_specific: boolean
  is_measurable: boolean
  is_achievable: boolean
  is_relevant: boolean
  is_time_bound: boolean

  // Scope
  category: IndicatorCategory
  priority: 'low' | 'medium' | 'high' | 'critical'
  status: 'draft' | 'active' | 'achieved' | 'failed' | 'cancelled'

  // Targets
  target_value?: number
  unit?: string
  baseline_value?: number
  current_value?: number
  achievement_rate?: number

  // Responsibility
  responsible_id?: number
  responsible?: UserReference

  // Timeline
  start_date: string
  target_date: string
  achieved_date?: string

  // Relations
  indicator_ids?: number[]
  indicators?: IndicatorReference[]
  processus_id?: number
  processus?: ProcessReference

  // Progress tracking
  milestones?: ObjectiveMilestone[]
}

export interface ObjectiveMilestone {
  id: number
  title: string
  target_date: string
  target_value?: number
  achieved: boolean
  achieved_date?: string
  note?: string
}

// ============================================
// DASHBOARDS
// ============================================

export interface Dashboard extends BaseEntity {
  name: string
  description?: string
  is_default: boolean
  is_public: boolean

  layout: DashboardLayout
  widgets: DashboardWidget[]

  owner_id: number
  owner?: UserReference

  shared_with?: number[] // User IDs
}

export interface DashboardLayout {
  columns: number
  rows: number
  gap: number
}

export interface DashboardWidget {
  id: string
  type: 'indicator' | 'chart' | 'stats' | 'table' | 'objective'
  position: { x: number, y: number }
  size: { width: number, height: number }

  config: {
    indicator_id?: number
    indicator_ids?: number[]
    chart_type?: ChartType
    time_range?: string
    title?: string
    show_trend?: boolean
    show_target?: boolean
  }
}

// ============================================
// REFERENCES
// ============================================

export interface IndicatorReference {
  id: number
  code: string
  name: string
  type: IndicatorType
  category: IndicatorCategory
  unit: string
  current_value?: number
  alert_level?: IndicatorAlertLevel
}

export interface ObjectiveReference {
  id: number
  code: string
  title: string
  category: IndicatorCategory
  status: 'draft' | 'active' | 'achieved' | 'failed' | 'cancelled'
  achievement_rate?: number
}

// ============================================
// FILTERS & PAYLOADS
// ============================================

export interface IndicatorFilters extends BaseFilters {
  type?: IndicatorType
  category?: IndicatorCategory
  status?: IndicatorStatus
  frequency?: IndicatorFrequency
  alert_level?: IndicatorAlertLevel
  responsible_id?: number
  processus_id?: number
  active_only?: boolean
}

export interface ObjectiveFilters extends BaseFilters {
  category?: IndicatorCategory
  status?: 'draft' | 'active' | 'achieved' | 'failed' | 'cancelled'
  priority?: 'low' | 'medium' | 'high' | 'critical'
  responsible_id?: number
  overdue?: boolean
}

export interface IndicatorValueFilters {
  indicator_id?: number
  period_start?: string
  period_end?: string
  validated?: boolean
}

// ============================================
// FORM PAYLOADS
// ============================================

export interface CreateIndicatorPayload {
  code: string
  name: string
  description?: string
  type: IndicatorType
  category: IndicatorCategory
  frequency: IndicatorFrequency
  unit: string
  formula?: string

  target_value?: number
  min_acceptable?: number
  max_acceptable?: number
  warning_threshold_low?: number
  warning_threshold_high?: number
  critical_threshold_low?: number
  critical_threshold_high?: number

  chart_type: ChartType
  color?: string

  responsible_id?: number
  processus_id?: number

  is_cumulative?: boolean
  is_reversed?: boolean
  data_source?: string

  active_from?: string
  active_until?: string
}

export interface UpdateIndicatorPayload extends Partial<CreateIndicatorPayload> {
  status?: IndicatorStatus
}

export interface RecordIndicatorValuePayload {
  indicator_id: number
  value: number
  period_start: string
  period_end: string
  note?: string
}

export interface CreateObjectivePayload {
  code: string
  title: string
  description?: string
  category: IndicatorCategory
  priority: 'low' | 'medium' | 'high' | 'critical'

  target_value?: number
  unit?: string
  baseline_value?: number

  start_date: string
  target_date: string

  responsible_id?: number
  processus_id?: number
  indicator_ids?: number[]

  milestones?: Omit<ObjectiveMilestone, 'id'>[]
}

export interface UpdateObjectivePayload extends Partial<CreateObjectivePayload> {
  status?: 'draft' | 'active' | 'achieved' | 'failed' | 'cancelled'
  current_value?: number
  achieved_date?: string
}

// ============================================
// STATISTICS & ANALYTICS
// ============================================

export interface IndicatorStatistics {
  total: number
  by_type: Record<IndicatorType, number>
  by_category: Record<IndicatorCategory, number>
  by_status: Record<IndicatorStatus, number>
  by_alert_level: {
    green: number
    yellow: number
    red: number
    unknown: number
  }
  active_count: number
  achievement_rate_avg?: number
}

export interface ObjectiveStatistics {
  total: number
  by_status: {
    draft: number
    active: number
    achieved: number
    failed: number
    cancelled: number
  }
  by_category: Record<IndicatorCategory, number>
  achievement_rate_avg: number
  on_track: number
  at_risk: number
  overdue: number
}

export interface TrendData {
  indicator_id: number
  periods: string[]
  values: number[]
  targets?: number[]
  direction: IndicatorTrendDirection
  variance_percent: number
}

export interface PerformanceSnapshot {
  period: string
  indicators: Array<{
    indicator: IndicatorReference
    value: number
    target?: number
    achievement_rate?: number
    alert_level: IndicatorAlertLevel
  }>
  overall_performance: number
  green_count: number
  yellow_count: number
  red_count: number
}

// ============================================
// API RESPONSES
// ============================================

export interface IndicatorsResponse {
  meta: PaginationMeta
  links: PaginationLinks
  data: Indicator[]
  statistics?: IndicatorStatistics
}

export interface ObjectivesResponse {
  meta: PaginationMeta
  links: PaginationLinks
  data: Objective[]
  statistics?: ObjectiveStatistics
}

export interface IndicatorValuesResponse {
  data: IndicatorValue[]
  trend?: TrendData
}

export interface DashboardResponse {
  data: Dashboard
  snapshot?: PerformanceSnapshot
}
