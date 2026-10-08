/**
 * Error handler utility
 */

import { HTTP_STATUS } from '@/constants'

export class AppError extends Error {
  constructor (
    public message: string,
    public code?: string,
    public status?: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message)
    this.name = 'AppError'
  }
}

export function handleApiError (error: any): AppError {
  if (error.response) {
    const { data, status } = error.response

    // Laravel validation errors
    if (status === HTTP_STATUS.UNPROCESSABLE_ENTITY && data.errors) {
      return new AppError(
        data.message || 'Erreur de validation',
        'VALIDATION_ERROR',
        status,
        data.errors,
      )
    }

    // Authentication errors
    if (status === HTTP_STATUS.UNAUTHORIZED) {
      return new AppError(
        data.message || 'Non autorisé',
        'UNAUTHORIZED',
        status,
      )
    }

    // Permission errors
    if (status === HTTP_STATUS.FORBIDDEN) {
      return new AppError(
        data.message || 'Accès interdit',
        'FORBIDDEN',
        status,
      )
    }

    // Not found
    if (status === HTTP_STATUS.NOT_FOUND) {
      return new AppError(
        data.message || 'Ressource non trouvée',
        'NOT_FOUND',
        status,
      )
    }

    // Server errors
    if (status >= 500) {
      return new AppError(
        data.message || 'Erreur serveur',
        'SERVER_ERROR',
        status,
      )
    }

    return new AppError(
      data.message || 'Une erreur est survenue',
      'API_ERROR',
      status,
    )
  }

  // Network errors
  if (error.request) {
    return new AppError(
      'Erreur de connexion au serveur',
      'NETWORK_ERROR',
    )
  }

  // Other errors
  return new AppError(
    error.message || 'Une erreur inattendue est survenue',
    'UNKNOWN_ERROR',
  )
}

export function getErrorMessage (error: any): string {
  if (error instanceof AppError) {
    return error.message
  }

  if (typeof error === 'string') {
    return error
  }

  if (error?.message) {
    return error.message
  }

  return 'Une erreur est survenue'
}

export function getValidationErrors (error: any): Record<string, string[]> | null {
  if (error instanceof AppError && error.errors) {
    return error.errors
  }

  if (error?.response?.data?.errors) {
    return error.response.data.errors
  }

  return null
}
