import type {
  ForgotPasswordRequest,
  LoginRequest,
  LoginResponse,
  RegisterClientRequest,
  RegisterEnterpriseRequest,
  ResetPasswordRequest,
} from '@/types/api'
import { useRouter } from 'vue-router'
import { useToast } from '@/modules/shared/composables/useToast'
import authService, { getErrorMessage, getValidationErrors } from '@/services/authService'
import { useAuthStore } from '@/stores/auth'

export function useAuth () {
  const authStore = useAuthStore()
  const router = useRouter()
  const toast = useToast()

  const handleApprovalStatusRedirect = async (error: any): Promise<boolean> => {
    if (error?.response?.status !== 403) {
      return false
    }

    const data = error.response?.data
    if (
      data?.status === 'pending'
      || data?.status === 'rejected'
      || data?.status === 'suspended'
      || data?.status === 'pending_admin_approval'
    ) {
      await router.push({
        path: '/auth/pending-approval',
        query: {
          status: data.status,
          message: data.message,
        },
      })
      return true
    }

    return false
  }

  const finalizeLogin = async (response: LoginResponse) => {
    if (!response?.user || !response?.token) {
      throw new Error('Reponse de connexion incomplete')
    }

    authStore.setAuth(response.user, response.token)

    await new Promise(resolve => setTimeout(resolve, 100))

    toast.success('Connexion reussie !')

    if (typeof response.redirect_to === 'string' && response.redirect_to.startsWith('/')) {
      await router.push(response.redirect_to)
      return response
    }

    if (response.redirect_to === 'subscription' || response.requires_subscription) {
      await router.push('/company/subscription')
      return response
    }

    const dashboardRoute = authStore.getDashboardRoute()
    await router.push(dashboardRoute)

    return response
  }

  const login = async (credentials: LoginRequest) => {
    try {
      authStore.loading = true

      const response = await authService.login(credentials)

      if (response?.mfa_required) {
        return response
      }

      return await finalizeLogin(response)
    } catch (error: any) {
      // Nettoyer le store en cas d'erreur
      authStore.clearAuth()

      // Gérer les cas spécifiques d'erreur 403 (compte non validé)
      const redirectedToPendingApproval = await handleApprovalStatusRedirect(error)
      if (redirectedToPendingApproval) {
        // Ne pas lancer l'erreur pour éviter l'affichage d'erreur dans le formulaire
        return
      }

      // Gérer les erreurs réseau (timeout, connexion perdue)
      if (error.code === 'ECONNABORTED' || error.code === 'ERR_NETWORK' || !error.response) {
        error.message = 'Erreur de connexion au serveur. Vérifiez votre connexion internet.'
      }

      // Pour les autres erreurs, laisser la page les gérer
      throw error
    } finally {
      authStore.loading = false
    }
  }

  const verifyMfa = async (payload: { token: string, code: string }) => {
    try {
      authStore.loading = true
      const response = await authService.verifyMfa(payload)
      return await finalizeLogin(response)
    } catch (error: any) {
      authStore.clearAuth()
      const redirectedToPendingApproval = await handleApprovalStatusRedirect(error)
      if (redirectedToPendingApproval) {
        return
      }
      throw error
    } finally {
      authStore.loading = false
    }
  }

  const logout = async () => {
    try {
      await authService.logout()
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error)
    } finally {
      authStore.clearAuth()
      toast.success('Déconnexion réussie')
      // Use replace instead of push to avoid navigation issues
      await router.replace('/auth/login')
    }
  }

  const registerEnterprise = async (formData: RegisterEnterpriseRequest) => {
    try {
      authStore.loading = true
      const response = await authService.registerEnterprise(formData)

      toast.success(response.message)

      await router.push('/auth/signup-loading?type=enterprise')

      return response
    } catch (error: any) {
      const validationErrors = getValidationErrors(error)
      if (Object.keys(validationErrors).length > 0) {
        throw { validationErrors, error }
      } else {
        const message = getErrorMessage(error)
        toast.error(message)
        throw error
      }
    } finally {
      authStore.loading = false
    }
  }

  const registerClient = async (formData: RegisterClientRequest) => {
    try {
      authStore.loading = true
      const response = await authService.registerClient(formData)

      // Ne plus auto-connecter - rediriger vers page de vérification email
      toast.success('Inscription réussie ! Vérifiez votre email pour activer votre compte.')

      await router.push(`/auth/signup-loading?type=client&email=${encodeURIComponent(formData.email)}`)

      return response
    } catch (error: any) {
      const validationErrors = getValidationErrors(error)
      if (Object.keys(validationErrors).length > 0) {
        throw { validationErrors, error }
      } else {
        const message = getErrorMessage(error)
        toast.error(message)
        throw error
      }
    } finally {
      authStore.loading = false
    }
  }

  const forgotPassword = async (payload: ForgotPasswordRequest) => {
    try {
      authStore.loading = true
      const response = await authService.forgotPassword(payload)
      toast.success('Email de réinitialisation envoyé !')
      return response
    } catch (error: any) {
      const message = getErrorMessage(error)
      toast.error(message)
      throw error
    } finally {
      authStore.loading = false
    }
  }

  const resetPassword = async (payload: ResetPasswordRequest) => {
    try {
      authStore.loading = true
      const response = await authService.resetPassword(payload)
      toast.success('Mot de passe réinitialisé avec succès !')
      return response
    } catch (error: any) {
      const message = getErrorMessage(error)
      toast.error(message)
      throw error
    } finally {
      authStore.loading = false
    }
  }

  const fetchUser = async () => {
    try {
      const response = await authService.me()
      authStore.updateUser(response.user)
      return response.user
    } catch (error: any) {
      authStore.clearAuth()
      throw error
    }
  }

  const initAuth = () => {
    authStore.initAuth()
  }

  return {
    user: authStore.user,
    loading: authStore.loading,
    isAuthenticated: authStore.isAuthenticated,
    userType: authStore.userType,
    isAdmin: authStore.isAdmin,
    isClientA: authStore.isClientA,
    isClientB: authStore.isClientB,
    userName: authStore.userName,
    userEmail: authStore.userEmail,

    login,
    logout,
    registerEnterprise,
    registerClient,
    forgotPassword,
    resetPassword,
    verifyMfa,
    fetchUser,
    initAuth,
    getDashboardRoute: authStore.getDashboardRoute,
  }
}
