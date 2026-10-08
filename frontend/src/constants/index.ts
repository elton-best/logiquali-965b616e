/**
 * Application constants
 */

export const STORAGE_KEYS = {
  ACCESS_TOKEN: 'access_token',
  REFRESH_TOKEN: 'refresh_token',
  TENANT_ID: 'tenant_id',
  USER_DATA: 'user_data',
  THEME: 'theme',
  LANGUAGE: 'language',
} as const

export const AUTH_TYPES = {
  JWT: 'jwt',
  OAUTH2: 'oauth2',
} as const

export const USER_ROLES = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  MANAGER: 'manager',
  USER: 'user',
  CLIENT: 'client',
} as const

export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  INTERNAL_SERVER_ERROR: 500,
} as const

export const ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  DASHBOARD: '/dashboard',
  PROFILE: '/profile',
  LOGOUT: '/logout',
  OAUTH_CALLBACK: '/auth/callback',
} as const

export const API_ENDPOINTS = {
  // Auth
  LOGIN: '/auth/login',
  REGISTER: '/auth/register',
  LOGOUT: '/auth/logout',
  REFRESH_TOKEN: '/auth/refresh',
  ME: '/auth/me',

  // OAuth2
  OAUTH_LOGIN: '/auth/oauth/login',
  OAUTH_CALLBACK: '/auth/oauth/callback',

  // Tenants
  TENANTS: '/tenants',
  SWITCH_TENANT: '/tenants/switch',

  // Users
  USERS: '/users',

  // QHSE Modules (à compléter selon vos besoins)
  DOCUMENTS: '/documents',
  INCIDENTS: '/incidents',
  AUDITS: '/audits',
  RISKS: '/risks',
  ACTIONS: '/actions',
} as const
