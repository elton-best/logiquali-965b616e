<template>
  <div class="login-page">
    <!-- Left Side - Form -->
    <div class="login-form-section">
      <div class="form-container">
        <!-- Logo & Title -->
        <div class="brand-section">
          <AppLogo class="brand-logo" stacked variant="full" />
          <p class="brand-tagline">Votre solution de gestion qualité</p>
        </div>

        <!-- Login Card -->
        <v-card class="login-card login-card-loader-scope" elevation="0">
          <div v-if="loading" class="login-card-loader-overlay">
            <UnifiedLoader
              description="Vérification de vos accès..."
              title="Connexion en cours..."
              variant="local"
            />
          </div>
          <v-card-text class="pa-8">
            <h2 class="login-title">
              {{ mfaStep ? "Verification requise" : "Bienvenue" }}
            </h2>
            <p class="login-subtitle">
              {{
                mfaStep
                  ? "Saisissez le code envoye par email"
                  : "Connectez-vous a votre compte"
              }}
            </p>
            <p
              v-if="mfaStep && mfaCountdownLabel"
              class="text-caption text-medium-emphasis mb-4"
            >
              {{ mfaCountdownLabel }}
            </p>

            <!-- Error Alert -->
            <v-alert
              v-if="errorMessage"
              class="mb-4"
              closable
              color="error"
              type="error"
              variant="tonal"
              @click:close="errorMessage = ''"
            >
              {{ errorMessage }}
            </v-alert>
            <v-alert
              v-if="successMessage"
              class="mb-4"
              closable
              color="success"
              type="success"
              variant="tonal"
              @click:close="successMessage = ''"
            >
              {{ successMessage }}
            </v-alert>

            <!-- Login Form -->
            <v-form v-if="!mfaStep" ref="formRef" @submit.prevent="handleLogin">
              <div class="form-field">
                <label class="field-label">Email</label>
                <v-text-field
                  v-model="credentials.email"
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

              <div class="form-field">
                <div class="field-header">
                  <label class="field-label">Mot de passe</label>
                  <a
                    class="forgot-link"
                    @click="$router.push('/auth/forgot-password')"
                  >
                    Mot de passe oublié ?
                  </a>
                </div>
                <v-text-field
                  v-model="credentials.password"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  autocomplete="current-password"
                  class="modern-field"
                  density="comfortable"
                  hide-details="auto"
                  placeholder="••••••••"
                  prepend-inner-icon="mdi-lock-outline"
                  :rules="[rules.required]"
                  :type="showPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPassword = !showPassword"
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
                <v-icon class="mr-2">mdi-login</v-icon>
                Se connecter
              </v-btn>
            </v-form>

            <v-form v-else ref="mfaFormRef" @submit.prevent="handleMfaVerify">
              <div class="form-field">
                <label class="field-label">Code de verification</label>
                <v-text-field
                  v-model="mfaCode"
                  autocomplete="one-time-code"
                  class="modern-field"
                  density="comfortable"
                  hide-details="auto"
                  maxlength="6"
                  placeholder="Entrez le code recu"
                  prepend-inner-icon="mdi-shield-key-outline"
                  :rules="[rules.required, rules.otp]"
                  type="text"
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
                <v-icon class="mr-2">mdi-check</v-icon>
                Verifier
              </v-btn>

              <div class="d-flex justify-space-between align-center mt-4">
                <v-btn
                  class="text-none"
                  color="primary"
                  :disabled="mfaResendLoading || mfaResendCooldown > 0 || mfaExpired"
                  :loading="mfaResendLoading"
                  variant="text"
                  @click="handleMfaResend"
                >
                  {{ mfaResendLabel }}
                </v-btn>
                <v-btn
                  class="text-none"
                  color="grey"
                  variant="text"
                  @click="resetMfa"
                >
                  Retour
                </v-btn>
              </div>
            </v-form>

            <div v-if="!mfaStep" class="divider-section">
              <v-divider />
              <span class="divider-text">Nouveau sur BestQHSE ?</span>
              <v-divider />
            </div>

            <div v-if="!mfaStep" class="signup-section">
              <v-btn
                block
                class="signup-btn"
                prepend-icon="mdi-domain"
                size="large"
                variant="outlined"
                @click="$router.push('/auth/signup/company')"
              >
                Inscription Entreprise
              </v-btn>
              <v-btn
                block
                class="signup-btn mt-3"
                prepend-icon="mdi-account"
                size="large"
                variant="outlined"
                @click="$router.push('/auth/signup/clientb')"
              >
                Inscription Particulier
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <!-- Footer -->
        <div class="login-footer">
          <p>&copy; 2024 BestQHSE. Tous droits réservés.</p>
        </div>
      </div>
    </div>

    <!-- Right Side - Visual -->
    <div class="login-visual-section">
      <div class="visual-overlay">
        <div class="visual-content">
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-chart-line</v-icon>
            <h3>Pilotage qualité</h3>
            <p>Suivez vos indicateurs de performance en temps réel</p>
          </div>
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-shield-check</v-icon>
            <h3>Conformité assurée</h3>
            <p>Respectez les normes ISO avec facilité</p>
          </div>
          <div class="feature-card">
            <v-icon color="white" size="48">mdi-account-group</v-icon>
            <h3>Collaboration efficace</h3>
            <p>Travaillez en équipe sur vos processus qualité</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { LoginRequest } from '@/types/api'
  import { computed, onBeforeUnmount, ref } from 'vue'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useAuth } from '@/composables/useAuth'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import authService from '@/services/authService'

  const { login, verifyMfa, loading } = useAuth()

  const formRef = ref()
  const mfaFormRef = ref()
  const showPassword = ref(false)
  const errorMessage = ref('')
  const successMessage = ref('')
  const mfaStep = ref(false)
  const mfaToken = ref('')
  const mfaCode = ref('')
  const mfaExpiresAt = ref<number | null>(null)
  const mfaCountdownLabel = ref('')
  const mfaExpired = ref(false)
  const mfaResendCooldown = ref(0)
  const mfaResendLoading = ref(false)
  const mfaResendLabel = computed(() => {
    if (mfaResendCooldown.value > 0) {
      return `Renvoyer le code (${mfaResendCooldown.value}s)`
    }
    return 'Renvoyer le code'
  })
  let mfaCountdownTimer: number | null = null
  let mfaResendTimer: number | null = null

  const credentials = ref<LoginRequest>({
    email: '',
    password: '',
  })

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    otp: (v: string) => /^[0-9]{6}$/.test((v || '').trim()) || 'Code OTP invalide',
  }

  function startMfaCountdown (expiresAtIso?: string) {
    const expiresAt = expiresAtIso ? Date.parse(String(expiresAtIso)) : Number.NaN
    mfaExpiresAt.value = Number.isFinite(expiresAt) ? expiresAt : null

    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
      mfaCountdownTimer = null
    }

    const tick = () => {
      if (!mfaExpiresAt.value) {
        mfaCountdownLabel.value = ''
        mfaExpired.value = false
        return
      }
      const remainingMs = mfaExpiresAt.value - Date.now()
      if (remainingMs <= 0) {
        mfaCountdownLabel.value = 'Code expire. Relancez la connexion.'
        mfaExpired.value = true
        return
      }
      const totalSeconds = Math.floor(remainingMs / 1000)
      const minutes = Math.floor(totalSeconds / 60)
      const seconds = totalSeconds % 60
      mfaCountdownLabel.value = `Code valide encore ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
      mfaExpired.value = false
    }

    tick()
    mfaCountdownTimer = window.setInterval(tick, 1000)
  }

  function startResendCooldown (seconds = 30) {
    mfaResendCooldown.value = seconds
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
      mfaResendTimer = null
    }
    mfaResendTimer = window.setInterval(() => {
      mfaResendCooldown.value = Math.max(0, mfaResendCooldown.value - 1)
      if (mfaResendCooldown.value === 0 && mfaResendTimer) {
        window.clearInterval(mfaResendTimer)
        mfaResendTimer = null
      }
    }, 1000)
  }

  async function handleLogin () {
    if (loading) {
      return
    }

    const { valid } = await formRef.value.validate()
    if (!valid) return

    try {
      errorMessage.value = ''
      successMessage.value = ''
      const response = await login(credentials.value)
      if ((response as any)?.mfa_required) {
        mfaStep.value = true
        mfaToken.value = String((response as any).mfa_token || '')
        mfaCode.value = ''
        startMfaCountdown((response as any).mfa_expires_at)
        startResendCooldown()
      }
    } catch (error: any) {
      if (error?.response?.data?.message) {
        errorMessage.value = error.response.data.message
      } else if (error?.message) {
        errorMessage.value = error.message
      } else {
        errorMessage.value = 'Une erreur est survenue lors de la connexion'
      }
    }
  }

  async function handleMfaVerify () {
    if (loading || !mfaToken.value || mfaExpired.value) {
      if (mfaExpired.value) {
        errorMessage.value = 'Code expire. Relancez la connexion.'
      }
      return
    }

    const { valid } = await mfaFormRef.value.validate()
    if (!valid) return

    try {
      errorMessage.value = ''
      successMessage.value = ''
      await verifyMfa({ token: mfaToken.value, code: mfaCode.value.trim() })
    } catch (error: any) {
      if (error?.response?.data?.message) {
        errorMessage.value = error.response.data.message
      } else if (error?.message) {
        errorMessage.value = error.message
      } else {
        errorMessage.value = 'Une erreur est survenue lors de la verification'
      }
    }
  }

  async function handleMfaResend () {
    if (!mfaToken.value || mfaResendLoading.value || mfaResendCooldown.value > 0 || mfaExpired.value) {
      return
    }

    try {
      mfaResendLoading.value = true
      errorMessage.value = ''
      successMessage.value = ''
      const response = await authService.resendMfa({ token: mfaToken.value })
      mfaToken.value = response.mfa_token
      successMessage.value = response.message || 'Un nouveau code a ete envoye.'
      startMfaCountdown(response.mfa_expires_at)
      startResendCooldown()
    } catch (error: any) {
      errorMessage.value
        = error?.response?.data?.message || 'Impossible de renvoyer le code.'
    } finally {
      mfaResendLoading.value = false
    }
  }

  function resetMfa () {
    mfaStep.value = false
    mfaToken.value = ''
    mfaCode.value = ''
    mfaExpiresAt.value = null
    mfaCountdownLabel.value = ''
    mfaExpired.value = false
    successMessage.value = ''
    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
      mfaCountdownTimer = null
    }
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
      mfaResendTimer = null
    }
    mfaResendCooldown.value = 0
  }

  onBeforeUnmount(() => {
    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
    }
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
    }
  })
</script>

<style scoped lang="scss">
.login-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-secondary);
}

.login-form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-8);
  position: relative;
  z-index: 1;

  @media (max-width: 1024px) {
    flex: none;
    width: 100%;
  }
}

.form-container {
  width: 100%;
  max-width: 480px;
}

.brand-section {
  text-align: center;
  margin-bottom: var(--spacing-8);
}

.logo-circle {
  width: 64px;
  height: 64px;
  background: linear-gradient(
    135deg,
    var(--color-primary-500),
    var(--color-primary-700)
  );
  border-radius: var(--radius-xl);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--spacing-4);
  box-shadow: var(--shadow-primary);
}

.brand-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-2);
}

.brand-tagline {
  color: var(--text-secondary);
  font-size: var(--font-size-base);
}

.login-card {
  background: var(--bg-primary);
  border-radius: var(--radius-2xl);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border-color);
}

.login-card-loader-scope {
  position: relative;
}

.login-card-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-2xl);
  background: rgba(255, 255, 255, 0.74);
  backdrop-filter: blur(2px);
}

.login-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-2);
}

.login-subtitle {
  color: var(--text-secondary);
  margin-bottom: var(--spacing-8);
}

.form-field {
  margin-bottom: var(--spacing-6);
}

.field-label {
  display: block;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-2);
}

.field-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-2);
}

.forgot-link {
  font-size: var(--font-size-sm);
  color: var(--color-primary-500);
  text-decoration: none;
  cursor: pointer;
  font-weight: var(--font-weight-medium);
  transition: all var(--transition-base);

  &:hover {
    text-decoration: underline;
    color: var(--color-primary-600);
  }

  &:focus {
    outline: none;
    box-shadow: var(--shadow-focus);
    border-radius: var(--radius-sm);
  }
}

.modern-field {
  :deep(.v-field) {
    border-radius: var(--radius-lg);
    border: 2px solid var(--border-color);
    background: var(--bg-secondary);
    transition: all var(--transition-base);
    min-height: 48px;

    &:hover {
      border-color: var(--border-color-hover);
    }

    &.v-field--focused {
      border-color: var(--color-primary-500);
      background: var(--bg-primary);
      box-shadow: var(--shadow-focus);
    }
  }
}

.login-btn {
  margin-top: var(--spacing-4);
  margin-bottom: var(--spacing-6);
  border-radius: var(--radius-lg);
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-base);
  text-transform: none;
  letter-spacing: 0.3px;
  background: linear-gradient(
    135deg,
    var(--color-primary-500),
    var(--color-primary-700)
  );
  box-shadow: var(--shadow-primary);
  min-height: 48px;
  transition: all var(--transition-base);

  &:hover {
    box-shadow: 0 8px 20px rgba(68, 113, 196, 0.4);
    transform: translateY(-2px);
  }

  &:focus {
    outline: none;
    box-shadow: var(--shadow-focus), var(--shadow-primary);
  }

  &:active {
    transform: translateY(0);
  }
}

.divider-section {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  margin: var(--spacing-8) 0;
}

.divider-text {
  font-size: var(--font-size-sm);
  color: var(--text-tertiary);
  white-space: nowrap;
}

.signup-section {
  margin-bottom: var(--spacing-6);
}

.signup-btn {
  border-radius: var(--radius-lg);
  font-weight: var(--font-weight-semibold);
  text-transform: none;
  border: 2px solid var(--border-color);
  color: var(--text-primary);
  min-height: 48px;
  transition: all var(--transition-base);

  &:hover {
    background: var(--bg-secondary);
    border-color: var(--border-color-hover);
    transform: translateY(-1px);
  }

  &:focus {
    outline: none;
    box-shadow: var(--shadow-focus);
  }
}

.login-footer {
  text-align: center;
  margin-top: var(--spacing-8);
  color: var(--text-tertiary);
  font-size: var(--font-size-sm);
}

.login-visual-section {
  flex: 1;
  background: linear-gradient(
    135deg,
    var(--color-primary-500),
    var(--color-primary-700)
  );
  position: relative;
  overflow: hidden;

  @media (max-width: 1024px) {
    display: none;
  }

  &::before {
    content: "";
    position: absolute;
    width: 600px;
    height: 600px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    top: -200px;
    right: -200px;
  }

  &::after {
    content: "";
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    bottom: -100px;
    left: -100px;
  }
}

.visual-overlay {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-16);
}

.visual-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-8);
}

.feature-card {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-2xl);
  padding: var(--spacing-8);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: transform var(--transition-base);

  &:hover {
    transform: translateY(-5px);
  }

  h3 {
    color: white;
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    margin: var(--spacing-4) 0 var(--spacing-2);
  }

  p {
    color: rgba(255, 255, 255, 0.9);
    font-size: var(--font-size-base);
    line-height: var(--line-height-relaxed);
  }
}
</style>
