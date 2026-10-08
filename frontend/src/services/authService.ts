import type {
  ForgotPasswordRequest,
  LoginRequest,
  LoginResponse,
  RegisterClientRequest,
  RegisterClientResponse,
  RegisterEnterpriseRequest,
  RegisterEnterpriseResponse,
  ResetPasswordRequest,
  User,
} from '@/types/api'
import api from '@/api/client'

class AuthService {
  /**
   * Connexion utilisateur
   */
  async login (credentials: LoginRequest): Promise<LoginResponse> {
    const { data } = await api.post<LoginResponse>('/auth/login', credentials)
    return data
  }

  async verifyMfa (payload: { token: string, code: string }): Promise<LoginResponse> {
    const { data } = await api.post<LoginResponse>('/auth/mfa/verify', payload, {
      headers: { 'X-Skip-Error-Toast': 'true' },
    })
    return data
  }

  async resendMfa (payload: { token: string }): Promise<{ mfa_token: string, mfa_expires_at: string, message?: string }> {
    const { data } = await api.post<{ mfa_token: string, mfa_expires_at: string, message?: string }>('/auth/mfa/resend', payload, {
      headers: { 'X-Skip-Error-Toast': 'true' },
    })
    return data
  }

  /**
   * Déconnexion
   */
  async logout (): Promise<void> {
    await api.post('/auth/logout')
  }

  /**
   * Récupérer les informations de l'utilisateur connecté
   */
  async me (): Promise<{ user: User, role: string }> {
    const { data } = await api.get<{ user: User, role: string }>('/auth/me')
    return data
  }

  /**
   * Inscription entreprise
   */
  async registerEnterprise (formData: RegisterEnterpriseRequest): Promise<RegisterEnterpriseResponse> {
    // Créer FormData pour l'upload de fichiers
    const payload = new FormData()

    payload.append('enterprise_name', formData.enterprise_name)
    if (formData.sigle) {
      payload.append('sigle', formData.sigle)
    }
    payload.append('email', formData.email)
    if (formData.enterprise_email) {
      payload.append('enterprise_email', formData.enterprise_email)
    }
    payload.append('registration_number', formData.registration_number)
    payload.append('address', formData.address)
    payload.append('first_name', formData.first_name)
    payload.append('last_name', formData.last_name)
    if (formData.username) {
      payload.append('username', formData.username)
    }
    if (formData.job_title) {
      payload.append('job_title', formData.job_title)
    }
    payload.append('password', formData.password)
    payload.append('password_confirmation', formData.password_confirmation)

    // Numéros de documents (optionnels)
    if (formData.rccm_number) {
      payload.append('rccm_number', formData.rccm_number)
    }
    if (formData.ifu_number) {
      payload.append('ifu_number', formData.ifu_number)
    }
    if (formData.id_type) {
      payload.append('id_type', formData.id_type)
    }
    if (formData.id_number) {
      payload.append('id_number', formData.id_number)
    }

    if (formData.phone) {
      payload.append('phone', formData.phone)
    }

    if (formData.city) {
      payload.append('city', formData.city)
    }

    if (formData.country) {
      payload.append('country', formData.country)
    }

    payload.append('field', formData.field)

    // Ajouter les documents avec leurs noms spécifiques
    if (formData.logo) {
      payload.append('logo', formData.logo)
    }

    if (formData.rccm_document) {
      payload.append('rccm_document', formData.rccm_document)
    }

    if (formData.ifu_document) {
      payload.append('ifu_document', formData.ifu_document)
    }

    if (formData.id_document) {
      payload.append('id_document', formData.id_document)
    }

    const { data } = await api.post<RegisterEnterpriseResponse>(
      '/auth/register/enterprise',
      payload,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      },
    )

    return data
  }

  /**
   * Inscription client (particulier)
   */
  async registerClient (formData: RegisterClientRequest): Promise<RegisterClientResponse> {
    const { data } = await api.post<RegisterClientResponse>('/auth/register/client', formData)
    return data
  }

  /**
   * Mot de passe oublié
   */
  async forgotPassword (payload: ForgotPasswordRequest): Promise<{ message: string }> {
    const { data } = await api.post<{ message: string }>('/auth/forgot-password', payload)
    return data
  }

  /**
   * Réinitialiser le mot de passe
   */
  async resetPassword (payload: ResetPasswordRequest): Promise<{ message: string }> {
    const { data } = await api.post<{ message: string }>('/auth/reset-password', payload)
    return data
  }

  /**
   * Renvoyer l'email de vérification
   */
  async resendVerification (): Promise<{ message: string, verification_sent: boolean }> {
    const { data } = await api.post<{ message: string, verification_sent: boolean }>(
      '/auth/resend-verification',
    )
    return data
  }

  /**
   * Rafraîchir le token
   */
  async refreshToken (): Promise<{ token: string, token_type: string, expires_at: string }> {
    const { data } = await api.post<{ token: string, token_type: string, expires_at: string }>(
      '/auth/refresh-token',
    )
    return data
  }
}

export default new AuthService()

export { getErrorMessage, getValidationErrors } from '@/api/client'
