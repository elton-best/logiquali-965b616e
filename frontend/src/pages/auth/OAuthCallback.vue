/**
 * OAuth2 Google Callback Page
 * Handles the redirect from Google after user authorizes
 */

<script setup lang="ts">
  import { AlertCircle, Loader2 } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { oauth2Service } from '@/api/services/oauth2.service'
  import { STORAGE_KEYS } from '@/constants'
  import { useAuthStore } from '@/stores/auth'
  import { storage } from '@/utils/storage'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()

  const loading = ref(true)
  const error = ref('')

  onMounted(async () => {
    const code = route.query.code as string
    const errorParam = route.query.error as string

    // User denied access
    if (errorParam) {
      error.value = 'Vous avez refusé l\'accès à Google. Veuillez réessayer.'
      loading.value = false
      setTimeout(() => router.push('/login'), 3000)
      return
    }

    // No code in URL
    if (!code) {
      error.value = 'Code d\'autorisation manquant'
      loading.value = false
      setTimeout(() => router.push('/login'), 3000)
      return
    }

    try {
      // Exchange code for tokens
      const response = await oauth2Service.googleCallback(code)
      const { access_token, refresh_token, user, tenant_id } = response.data

      // Store tokens and user data
      storage.set(STORAGE_KEYS.ACCESS_TOKEN, access_token)
      storage.set(STORAGE_KEYS.REFRESH_TOKEN, refresh_token)
      storage.set(STORAGE_KEYS.USER_DATA, user)

      if (tenant_id) {
        storage.set(STORAGE_KEYS.TENANT_ID, tenant_id)
      }

      // Update auth store
      authStore.setAuth(user as any, access_token)

      // Redirect based on user type
      switch (user.user_type) {
        case 'super_admin': {
          router.push('/superadmin/dashboard')

          break
        }
        case 'company': {
          router.push('/company/dashboard')

          break
        }
        case 'clientb': {
          router.push('/clientb/dashboard')

          break
        }
        default: {
          router.push('/')
        }
      }
    } catch (error_: any) {
      console.error('OAuth callback error:', error_)
      error.value = error_.message || 'Une erreur est survenue lors de la connexion avec Google'
      loading.value = false
      setTimeout(() => router.push('/login'), 3000)
    }
  })
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-neutral-50 dark:bg-neutral-950">
    <div class="card p-8 max-w-md w-full text-center">
      <!-- Loading -->
      <div v-if="loading">
        <Loader2 class="w-12 h-12 animate-spin text-primary-500 mx-auto mb-4" />
        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-50 mb-2">
          Connexion avec Google...
        </h2>
        <p class="text-sm text-neutral-600 dark:text-neutral-400">
          Veuillez patienter pendant que nous finalisons votre connexion
        </p>
      </div>

      <!-- Error -->
      <div v-else>
        <AlertCircle class="w-12 h-12 text-red-500 mx-auto mb-4" />
        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-50 mb-2">
          Échec de la connexion
        </h2>
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">
          {{ error }}
        </p>
        <p class="text-xs text-neutral-500">
          Redirection vers la page de connexion...
        </p>
      </div>
    </div>
  </div>
</template>
  import type { User as ApiUser } from '@/types/api'
