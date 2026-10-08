<template>
  <div class="login-page">
    <div class="login-form-section">
      <div class="form-container">
        <ResetPasswordBrand />

        <v-card class="login-card login-card-loader-scope" elevation="0">
          <div v-if="loading" class="login-card-loader-overlay">
            <UnifiedLoader
              description="Mise à jour du mot de passe..."
              title="Sécurisation en cours"
              variant="local"
            />
          </div>
          <v-card-text class="pa-8">
            <h2 class="login-title">Nouveau mot de passe</h2>
            <p class="login-subtitle">
              Choisissez un mot de passe fort pour sécuriser votre compte.
            </p>

            <v-alert v-if="missingToken" class="mb-6" type="error" variant="tonal">
              Le lien de réinitialisation est invalide ou incomplet. Veuillez refaire une demande.
            </v-alert>

            <v-alert v-if="resetCompleted" class="mb-6" type="success" variant="tonal">
              Votre mot de passe a été réinitialisé avec succès. Redirection vers la connexion...
            </v-alert>

            <v-form v-if="!resetCompleted" ref="formRef" @submit.prevent="handleSubmit">
              <div class="form-field">
                <label class="field-label">Adresse email</label>
                <v-text-field
                  v-model="formData.email"
                  autocomplete="email"
                  class="modern-field"
                  density="comfortable"
                  hide-details="auto"
                  placeholder="exemple@entreprise.com"
                  prepend-inner-icon="mdi-email-outline"
                  :rules="[rules.required, rules.email]"
                  type="email"
                  variant="outlined"
                />
              </div>

              <div class="d-flex justify-end mb-4">
                <v-checkbox-btn
                  v-model="showPasswords"
                  color="primary"
                  hide-details
                  label="Afficher les mots de passe"
                />
              </div>

              <div class="form-field">
                <label class="field-label">Nouveau mot de passe</label>
                <v-text-field
                  v-model="formData.password"
                  :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                  autocomplete="new-password"
                  class="modern-field"
                  density="comfortable"
                  hide-details="auto"
                  placeholder="••••••••"
                  prepend-inner-icon="mdi-lock-outline"
                  :rules="[rules.required, rules.minLength]"
                  :type="showPasswords ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPasswords = !showPasswords"
                />
              </div>

              <div class="form-field">
                <label class="field-label">Confirmer le mot de passe</label>
                <v-text-field
                  v-model="formData.password_confirmation"
                  :append-inner-icon="showPasswords ? 'mdi-eye-off' : 'mdi-eye'"
                  autocomplete="new-password"
                  class="modern-field"
                  density="comfortable"
                  hide-details="auto"
                  placeholder="••••••••"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :rules="[rules.required, rules.match]"
                  :type="showPasswords ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPasswords = !showPasswords"
                />
              </div>

              <v-btn
                block
                class="login-btn"
                color="primary"
                :disabled="loading || missingToken"
                :loading="loading"
                size="x-large"
                type="submit"
              >
                <v-icon class="mr-2">mdi-lock-reset</v-icon>
                Réinitialiser le mot de passe
              </v-btn>
            </v-form>

            <v-btn
              v-else
              block
              class="login-btn"
              color="primary"
              prepend-icon="mdi-login"
              size="x-large"
              @click="redirectToLogin"
            >
              Aller à la connexion
            </v-btn>

            <div class="divider-section">
              <v-divider />
              <span class="divider-text">Besoin d’aide ?</span>
              <v-divider />
            </div>

            <v-btn
              block
              class="signup-btn"
              prepend-icon="mdi-arrow-left"
              variant="outlined"
              @click="goToForgot"
            >
              Retour à la demande de lien
            </v-btn>
          </v-card-text>
        </v-card>

        <div class="login-footer">
          <p>&copy; 2024 BestQHSE. Tous droits réservés.</p>
        </div>
      </div>
    </div>

    <ResetPasswordVisual />
  </div>
</template>

<script setup lang="ts">
  import type { ResetPasswordRequest } from '@/types/api'
  import { computed, onBeforeUnmount, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useAuth } from '@/composables/useAuth'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import ResetPasswordBrand from '@/pages/auth/components/reset-password/ResetPasswordBrand.vue'
  import ResetPasswordVisual from '@/pages/auth/components/reset-password/ResetPasswordVisual.vue'

  const { resetPassword, loading } = useAuth()
  const route = useRoute()
  const router = useRouter()

  const formRef = ref()
  const showPasswords = ref(false)
  const resetCompleted = ref(false)
  let redirectTimeout: number | null = null

  const token = ref(String(route.query.token || ''))
  const initialEmail = String(route.query.email || '')

  const formData = ref<ResetPasswordRequest>({
    email: initialEmail,
    token: token.value,
    password: '',
    password_confirmation: '',
  })

  const missingToken = computed(() => !formData.value.token)

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => v.length >= 8 || 'Minimum 8 caractères',
    match: (v: string) => v === formData.value.password || 'Les mots de passe ne correspondent pas',
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid || missingToken.value) return

    try {
      await resetPassword(formData.value)
      resetCompleted.value = true
      formData.value.password = ''
      formData.value.password_confirmation = ''
      formData.value.token = ''
      showPasswords.value = false
      redirectTimeout = window.setTimeout(() => {
        redirectToLogin()
      }, 1200)
    } catch {
      // errors handled by useAuth
    }
  }

  function redirectToLogin () {
    const loginUrl = `/auth/login?passwordReset=success&email=${encodeURIComponent(formData.value.email)}&t=${Date.now()}`
    window.location.assign(loginUrl)
  }

  function goToForgot () {
    router.push('/auth/forgot-password')
  }

  onBeforeUnmount(() => {
    if (redirectTimeout !== null) {
      window.clearTimeout(redirectTimeout)
    }
  })
</script>

<style scoped lang="scss">
@use './login-modern-shared.scss' as *;
</style>
