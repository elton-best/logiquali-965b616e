// Storage keys
export const STORAGE_KEYS = {
  ACCESS_TOKEN: 'accessToken',
  REFRESH_TOKEN: 'refreshToken',
  USER: 'user',
  TOKEN: 'token',
  THEME: 'theme',
  LANGUAGE: 'language',
  SIDEBAR_STATE: 'sidebarState',
} as const

// API endpoints
export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/api/v1/auth/login',
    LOGOUT: '/api/v1/auth/logout',
    REFRESH: '/api/v1/auth/refresh',
    ME: '/api/v1/auth/me',
  },
  USERS: '/api/v1/users',
  SITES: '/api/v1/sites',
  PROCESSES: '/api/v1/processes',
  DOCUMENTS: '/api/v1/documents',
  NON_CONFORMITIES: '/api/v1/non-conformities',
  AUDITS: '/api/v1/audits',
  RISKS: '/api/v1/risks',
  NORMS: '/api/v1/norms',
  DASHBOARD: '/api/v1/dashboard',
} as const

// User types (3 uniquement)
export const USER_TYPES = {
  SUPER_ADMIN: 'super_admin',
  COMPANY: 'company',
  CLIENTB: 'clientb',
} as const

// User roles (pour user_type = 'company')
export const USER_ROLES = {
  ADMIN_ENTREPRISE: 'admin_entreprise',
  SITE_MANAGER: 'site_manager',
  VIEWER: 'lecteur',
} as const

// Pagination
export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_PER_PAGE: 15,
  PER_PAGE_OPTIONS: [10, 15, 25, 50, 100],
} as const

// Date formats
export const DATE_FORMATS = {
  DISPLAY: 'dd/MM/yyyy',
  DISPLAY_TIME: 'dd/MM/yyyy HH:mm',
  API: 'yyyy-MM-dd',
  API_TIME: 'yyyy-MM-dd HH:mm:ss',
} as const

// File upload
export const FILE_UPLOAD = {
  MAX_SIZE: 100 * 1024 * 1024, // 100MB
  ALLOWED_TYPES: [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  ],
} as const

// Status
export const STATUS = {
  ACTIVE: 'active',
  INACTIVE: 'inactive',
  PENDING: 'pending',
  ARCHIVED: 'archived',
} as const

// NC Status
export const NC_STATUS = {
  OPEN: 'open',
  IN_PROGRESS: 'in_progress',
  RESOLVED: 'resolved',
  CLOSED: 'closed',
} as const

// NC Severity
export const NC_SEVERITY = {
  LOW: 'low',
  MEDIUM: 'medium',
  HIGH: 'high',
  CRITICAL: 'critical',
} as const

// Audit Status
export const AUDIT_STATUS = {
  PLANNED: 'planned',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
} as const

// Risk Status
export const RISK_STATUS = {
  IDENTIFIED: 'identified',
  EVALUATING: 'evaluating',
  CONTROLLED: 'controlled',
  ACCEPTED: 'accepted',
} as const

export default {
  STORAGE_KEYS,
  API_ENDPOINTS,
  USER_ROLES,
  PAGINATION,
  DATE_FORMATS,
  FILE_UPLOAD,
  STATUS,
  NC_STATUS,
  NC_SEVERITY,
  AUDIT_STATUS,
  RISK_STATUS,
}
