<template>
  <div class="modern-login-container">
    <!-- Left Side - Branding & Image -->
    <div class="left-panel" :class="{ 'forgot-mode': isForgotPassword }">
      <div class="brand-section">
        <div class="logo-wrapper">
          <v-icon color="white" size="48">mdi-quality-high</v-icon>
          <h1 class="brand-name">BestQHSE</h1>
        </div>
        <p class="brand-tagline">Votre solution complète de gestion qualité</p>
      </div>

      <!-- Hero Illustration -->
      <div class="illustration-wrapper">
        <div class="floating-card card-1">
          <v-icon color="#4471c4" size="32">mdi-shield-check</v-icon>
          <div class="card-text">
            <div class="card-title">Sécurité Maximale</div>
            <div class="card-subtitle">Données cryptées</div>
          </div>
        </div>

        <div class="floating-card card-2">
          <v-icon color="#10b981" size="32">mdi-chart-line</v-icon>
          <div class="card-text">
            <div class="card-title">Suivi en Temps Réel</div>
            <div class="card-subtitle">Analytics avancés</div>
          </div>
        </div>

        <div class="floating-card card-3">
          <v-icon color="#f59e0b" size="32">mdi-account-group</v-icon>
          <div class="card-text">
            <div class="card-title">Collaboration</div>
            <div class="card-subtitle">Équipe connectée</div>
          </div>
        </div>

        <!-- Central illustration -->
        <div class="central-circle">
          <div class="circle-content">
            <v-icon color="white" size="80">mdi-rocket-launch</v-icon>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="stats-section">
        <div class="stat-item">
          <div class="stat-number">500+</div>
          <div class="stat-label">Entreprises</div>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <div class="stat-number">50K+</div>
          <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <div class="stat-number">99.9%</div>
          <div class="stat-label">Disponibilité</div>
        </div>
      </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="right-panel">
      <div class="login-wrapper">
        <!-- Back Button -->
        <!-- <v-btn
          class="back-btn"
          icon
          size="small"
          variant="text"
          @click="$router.push('/')"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn> -->

        <!-- Login Header -->
        <div class="login-header">
          <h2 class="login-title">
            {{ isForgotPassword ? "Mot de passe oublié ?" : "Bienvenue !" }}
          </h2>
          <p class="login-subtitle">
            {{
              isForgotPassword
                ? "Entrez votre email pour recevoir un lien de réinitialisation"
                : "Connectez-vous à votre compte pour continuer"
            }}
          </p>
        </div>

        <!-- Login Form -->
        <v-form ref="formRef" class="login-form" @submit.prevent="handleSubmit">
          <div v-if="!isForgotPassword">
            <!-- Email Field -->
            <div class="form-group">
              <label class="input-label">Adresse email</label>
              <v-text-field
                v-model="formData.email"
                autofocus
                class="modern-input"
                density="comfortable"
                hide-details="auto"
                placeholder="vous@exemple.com"
                prepend-inner-icon="mdi-email-outline"
                :rules="[rules.required, rules.email]"
                type="email"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
                    v-if="
                      formData.email && rules.email(formData.email) === true
                    "
                    color="success"
                    size="20"
                  >
                    mdi-check-circle
                  </v-icon>
                </template>
              </v-text-field>
            </div>

            <!-- Password Field -->
            <div class="form-group">
              <div class="d-flex justify-space-between align-center mb-2">
                <label class="input-label">Mot de passe</label>
                <a class="forgot-link" @click.prevent="isForgotPassword = true">
                  Mot de passe oublié ?
                </a>
              </div>
              <v-text-field
                v-model="formData.password"
                :append-inner-icon="
                  showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
                "
                class="modern-input"
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

            <!-- Remember Me -->
            <div class="form-group">
              <v-checkbox
                v-model="rememberMe"
                class="remember-checkbox"
                color="#4471c4"
                hide-details
                label="Se souvenir de moi"
              />
            </div>
          </div>

          <!-- Forgot Password Form -->
          <div v-else>
            <!-- Email Field -->
            <div class="form-group">
              <label class="input-label">Adresse email</label>
              <v-text-field
                v-model="forgotEmail"
                autofocus
                class="modern-input"
                density="comfortable"
                hide-details="auto"
                placeholder="vous@exemple.com"
                prepend-inner-icon="mdi-email-outline"
                :rules="[rules.required, rules.email]"
                type="email"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
                    v-if="forgotEmail && rules.email(forgotEmail) === true"
                    color="success"
                    size="20"
                  >
                    mdi-check-circle
                  </v-icon>
                </template>
              </v-text-field>
            </div>

            <v-alert
              class="mb-4"
              color="info"
              density="compact"
              icon="mdi-information-outline"
              variant="tonal"
            >
              Un lien de réinitialisation sera envoyé à cette adresse email.
            </v-alert>
          </div>

          <!-- Error Alert -->
          <v-alert
            v-if="errorMessage"
            class="mb-4"
            closable
            density="compact"
            type="error"
            variant="tonal"
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </v-alert>

          <!-- Success Alert -->
          <v-alert
            v-if="successMessage"
            class="mb-4"
            closable
            density="compact"
            icon="mdi-check-circle"
            type="success"
            variant="tonal"
            @click:close="successMessage = ''"
          >
            {{ successMessage }}
          </v-alert>

          <!-- Submit Button -->
          <v-btn
            block
            class="login-btn"
            :loading="loading"
            size="x-large"
            type="submit"
          >
            <v-icon class="mr-2">{{
              isForgotPassword ? "mdi-email-fast" : "mdi-login"
            }}</v-icon>
            {{ isForgotPassword ? "Envoyer le lien" : "Se connecter" }}
          </v-btn>

          <!-- Back to Login -->
          <div v-if="isForgotPassword" class="text-center mt-4">
            <v-btn
              color="primary"
              prepend-icon="mdi-arrow-left"
              variant="text"
              @click="resetToLogin"
            >
              Retour à la connexion
            </v-btn>
          </div>

          <!-- Divider -->
          <div v-if="!isForgotPassword" class="divider-section">
            <!-- <div class="divider-line" />
            <span class="divider-text">OU</span> -->
            <!-- <div class="divider-line" /> -->
          </div>

          <!-- OAuth Buttons -->
          <!-- <div v-if="!isForgotPassword" class="oauth-buttons">
            <v-btn
              block
              class="oauth-btn"
              size="large"
              variant="outlined"
            >
              <v-icon class="mr-2" color="#4285F4">mdi-google</v-icon>
              Continuer avec Google
            </v-btn>

            <v-btn
              block
              class="oauth-btn"
              size="large"
              variant="outlined"
            >
              <v-icon class="mr-2" color="#0078D4">mdi-microsoft</v-icon>
              Continuer avec Microsoft
            </v-btn>
          </div> -->

          <!-- Register Links -->
          <div v-if="!isForgotPassword" class="register-section">
            <p class="register-text">Vous n'avez pas de compte ?</p>
            <div class="register-buttons">
              <v-btn
                class="register-btn"
                color="#4471c4"
                prepend-icon="mdi-office-building"
                size="large"
                variant="outlined"
                @click="$router.push('/auth/signup/company')"
              >
                Entreprise
              </v-btn>
              <v-btn
                class="register-btn"
                color="#10b981"
                prepend-icon="mdi-account-plus"
                size="large"
                variant="outlined"
                @click="$router.push('/auth/signup/clientb')"
              >
                Particulier
              </v-btn>
            </div>
          </div>
        </v-form>

        <!-- Trust Badges -->
        <div class="trust-badges">
          <div class="badge-item">
            <v-icon color="#10b981" size="18">mdi-shield-check</v-icon>
            <span>Sécurisé SSL</span>
          </div>
          <div class="badge-item">
            <v-icon color="#4471c4" size="18">mdi-lock</v-icon>
            <span>RGPD Conforme</span>
          </div>
          <div class="badge-item">
            <v-icon color="#f59e0b" size="18">mdi-check-decagram</v-icon>
            <span>ISO Certifié</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { LoginRequest } from '@/types/api'
  import { ref } from 'vue'
  import { useAuth } from '@/composables/useAuth'

  const { login, forgotPassword, loading } = useAuth()

  const formRef = ref()
  const showPassword = ref(false)
  const rememberMe = ref(false)
  const errorMessage = ref('')
  const isForgotPassword = ref(false)
  const forgotEmail = ref('')
  const successMessage = ref('')

  const formData = ref<LoginRequest>({
    email: '',
    password: '',
  })

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
  }

  function resetToLogin () {
    isForgotPassword.value = false
    forgotEmail.value = ''
    errorMessage.value = ''
    successMessage.value = ''
  }

  async function handleSubmit () {
    if (loading) {
      return
    }
    errorMessage.value = ''
    successMessage.value = ''

    const { valid } = await formRef.value.validate()
    if (!valid) return

    try {
      if (isForgotPassword.value) {
        // Forgot password flow
        await forgotPassword({ email: forgotEmail.value })
        successMessage.value
          = 'Email de réinitialisation envoyé ! Vérifiez votre boîte de réception.'
        forgotEmail.value = ''
      } else {
        // Login flow
        await login(formData.value)
      }
    } catch (error: any) {
      if (error.response?.data?.message) {
        errorMessage.value = error.response.data.message
      } else if (error.message) {
        errorMessage.value = error.message
      } else {
        errorMessage.value = isForgotPassword.value
          ? 'Une erreur est survenue lors de l\'envoi de l\'email'
          : 'Une erreur est survenue lors de la connexion'
      }
    }
  }
</script>

<style scoped lang="scss">
.modern-login-container {
  display: flex;
  min-height: 100vh;
  background: #ffffff;
}

// ============= LEFT PANEL =============
.left-panel {
  flex: 1;
  background: linear-gradient(135deg, #4471c4 0%, #2a4a8f 100%);
  padding: 3rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  transition: background 0.5s ease;

  &.forgot-mode {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  }

  &::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(
      circle,
      rgba(255, 255, 255, 0.1) 1px,
      transparent 1px
    );
    background-size: 30px 30px;
    animation: grid-move 20s linear infinite;
  }
}

@keyframes grid-move {
  0% {
    transform: translate(0, 0);
  }
  100% {
    transform: translate(30px, 30px);
  }
}

.brand-section {
  position: relative;
  z-index: 1;
}

.logo-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.brand-name {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin: 0;
}

.brand-tagline {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.125rem;
  margin: 0;
  max-width: 400px;
}

// Illustration
.illustration-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.floating-card {
  position: absolute;
  background: white;
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  animation: float 3s ease-in-out infinite;
}

.card-1 {
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.card-2 {
  top: 50%;
  right: 10%;
  animation-delay: 1s;
}

.card-3 {
  bottom: 15%;
  left: 15%;
  animation-delay: 2s;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

.card-text {
  flex: 1;
}

.card-title {
  font-weight: 600;
  font-size: 0.9375rem;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.card-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
}

.central-circle {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.2),
    rgba(255, 255, 255, 0.1)
  );
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
  }
}

.circle-content {
  animation: rotate 20s linear infinite;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

// Stats
.stats-section {
  display: flex;
  gap: 2rem;
  position: relative;
  z-index: 1;
}

.stat-item {
  flex: 1;
}

.stat-number {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.stat-divider {
  width: 1px;
  background: rgba(255, 255, 255, 0.2);
}

// ============= RIGHT PANEL =============
.right-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #fafafa;
}

.login-wrapper {
  width: 100%;
  max-width: 480px;
  background: white;
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  position: relative;
}

.back-btn {
  position: absolute;
  top: 1.5rem;
  left: 1.5rem;
}

// Login Header
.login-header {
  margin-bottom: 1.5rem;
  text-align: center;
}

.login-title {
  color: #1a1a1a;
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.375rem;
  letter-spacing: -0.5px;
}

.login-subtitle {
  color: #64748b;
  font-size: 0.9375rem;
  margin: 0;
}

// Form
.login-form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.input-label {
  display: block;
  color: #334155;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.forgot-link {
  color: #4471c4;
  font-size: 0.8125rem;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s;

  &:hover {
    color: #2a4a8f;
    text-decoration: underline;
  }
}

.modern-input {
  :deep(.v-field) {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #fafafa;
    transition: all 0.3s;

    &:hover {
      border-color: #cbd5e1;
      background: white;
    }

    &.v-field--focused {
      border-color: #4471c4;
      background: white;
      box-shadow: 0 0 0 4px rgba(68, 113, 196, 0.1);
    }
  }

  :deep(.v-field__input) {
    padding: 0.875rem 1rem;
    font-size: 0.9375rem;
  }
}

.remember-checkbox {
  :deep(.v-label) {
    color: #64748b;
    font-size: 0.875rem;
  }
}

.login-btn {
  background: linear-gradient(135deg, #4471c4, #2a4a8f);
  color: white;
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.2px;
  box-shadow: 0 4px 14px rgba(68, 113, 196, 0.4);
  transition: all 0.3s;

  &:hover:not(:disabled) {
    box-shadow: 0 6px 20px rgba(68, 113, 196, 0.5);
    transform: translateY(-2px);
  }

  &:active {
    transform: translateY(0);
  }
}

// Divider
.divider-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 1.5rem 0;
}

.divider-line {
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider-text {
  color: #94a3b8;
  font-size: 0.8125rem;
  font-weight: 600;
}

// OAuth
.oauth-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.oauth-btn {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  color: #475569;
  transition: all 0.3s;

  &:hover {
    border-color: #cbd5e1;
    background: #fafafa;
  }
}

// Register
.register-section {
  text-align: center;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.register-text {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 0.875rem;
}

.register-buttons {
  display: flex;
  gap: 0.75rem;
}

.register-btn {
  flex: 1;
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  border-width: 2px;
  transition: all 0.3s;

  &:hover {
    transform: translateY(-2px);
  }
}

// Trust Badges
.trust-badges {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.badge-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 600;
}

// Responsive
@media (max-width: 1024px) {
  .left-panel {
    display: none;
  }

  .right-panel {
    flex: 1;
  }
}

@media (max-width: 640px) {
  .right-panel {
    padding: 1rem;
  }

  .login-wrapper {
    padding: 2rem 1.5rem;
  }

  .login-title {
    font-size: 1.75rem;
  }

  .register-buttons {
    flex-direction: column;
  }

  .trust-badges {
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
  }
}
</style>
