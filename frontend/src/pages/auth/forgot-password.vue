<template>
  <div class="login-page">
    <div class="login-form-section">
      <div class="form-container">
        <div class="brand-section">
          <AppLogo class="brand-logo" stacked variant="full" />
          <p class="brand-tagline">Votre solution de gestion qualité</p>
        </div>

        <v-card class="login-card login-card-loader-scope" elevation="0">
          <div v-if="loading" class="login-card-loader-overlay">
            <UnifiedLoader
              description="Envoi du lien..."
              title="Veuillez patienter"
              variant="local"
            />
          </div>
          <v-card-text class="pa-8">
            <h2 class="login-title">Mot de passe oublié</h2>
            <p class="login-subtitle">
              Récupérez l’accès à votre compte en recevant un lien sécurisé.
            </p>

            <template v-if="emailSent">
              <div class="success-panel">
                <div class="success-icon">
                  <v-icon color="white" size="28">mdi-email-check-outline</v-icon>
                </div>
                <h3>Consultez votre boîte mail</h3>
                <p>
                  Un email contenant un lien de réinitialisation a été envoyé à
                  <strong>{{ formData.email }}</strong>.
                </p>
                <v-alert class="mt-4" type="info" variant="tonal">
                  Vérifiez vos spams si vous ne recevez rien dans quelques minutes.
                </v-alert>
                <v-btn
                  block
                  class="login-btn mt-6"
                  color="primary"
                  prepend-icon="mdi-arrow-left"
                  @click="goToLogin"
                >
                  Retour à la connexion
                </v-btn>
              </div>
            </template>

            <template v-else>
              <v-form ref="formRef" @submit.prevent="handleSubmit">
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

                <v-btn
                  block
                  class="login-btn"
                  color="primary"
                  :disabled="loading"
                  :loading="loading"
                  size="x-large"
                  type="submit"
                >
                  <v-icon class="mr-2">mdi-email-send-outline</v-icon>
                  Envoyer le lien
                </v-btn>
              </v-form>

              <div class="divider-section">
                <v-divider />
                <span class="divider-text">Retour à l’accès</span>
                <v-divider />
              </div>

              <v-btn
                block
                class="signup-btn"
                prepend-icon="mdi-arrow-left"
                variant="outlined"
                @click="goToLogin"
              >
                Revenir à la connexion
              </v-btn>
            </template>
          </v-card-text>
        </v-card>

        <div class="login-footer">
          <p>&copy; 2024 BestQHSE. Tous droits réservés.</p>
        </div>
      </div>
    </div>

    <div class="login-visual-section">
      <div class="visual-overlay">
        <div class="visual-content">
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-lock-reset</v-icon>
            <h3>Réinitialisation sécurisée</h3>
            <p>Recevez un lien temporaire pour retrouver l’accès à votre compte.</p>
          </div>
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-email-fast</v-icon>
            <h3>Envoi instantané</h3>
            <p>Nous envoyons l’email en quelques secondes vers votre boîte.</p>
          </div>
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-shield-check</v-icon>
            <h3>Protection BestQHSE</h3>
            <p>Chaque demande est vérifiée et sécurisée par notre système.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { ForgotPasswordRequest } from '@/types/api'
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useAuth } from '@/composables/useAuth'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const { forgotPassword, loading } = useAuth()
  const router = useRouter()

  const formRef = ref()
  const emailSent = ref(false)

  const formData = ref<ForgotPasswordRequest>({
    email: '',
  })

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    try {
      await forgotPassword(formData.value)
      emailSent.value = true
    } catch {
      // errors handled by useAuth
    }
  }

  function goToLogin () {
    router.push('/auth/login')
  }
</script>

<style scoped lang="scss">
@use './login-modern-shared.scss' as *;

.success-panel {
  text-align: center;

  h3 {
    margin-top: var(--spacing-4);
    margin-bottom: var(--spacing-2);
    font-size: var(--font-size-xl);
    color: var(--text-primary);
    font-weight: var(--font-weight-bold);
  }

  p {
    color: var(--text-secondary);
  }
}

.success-icon {
  width: 56px;
  height: 56px;
  border-radius: 18px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-primary-500), var(--color-primary-700));
  box-shadow: var(--shadow-primary);
}
</style>
