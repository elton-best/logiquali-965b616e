<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { config } from '@/config'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import LoginBrandingPanel from '@/pages/auth/components/LoginBrandingPanel.vue'
  import LoginDemoAccounts from '@/pages/auth/components/LoginDemoAccounts.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()

  const forceLogin = computed(
    () => String(route.query.force_login || '') === '1',
  )

  // Redirect if already authenticated (unless explicit force-login flow)
  onMounted(() => {
    if (authStore.isAuthenticated && !forceLogin.value) {
      const role = authStore.user?.role || authStore.user?.user_type
      switch (role) {
        case 'super_admin':
        case 'superadmin': {
          router.replace('/superadmin/dashboard')

          break
        }
        case 'company':
        case 'clienta':
        case 'entreprise': {
          router.replace('/company/dashboard')

          break
        }
        case 'client_b':
        case 'clientb':
        case 'client': {
          router.replace('/clientb/dashboard')

          break
        }
        default: {
          router.replace('/')
        }
      }
    }
  })

  const email = ref('')
  const password = ref('')
  const error = ref('')
  const showPassword = ref(false)
  const rememberMe = ref(false)
  const showDemo = ref(false)
  const successMessage = ref((route.query.message as string) || '')
  const loading = computed(() => Boolean((authStore as any).loading))

  function hasEmptyCredentials () {
    return !email.value || !password.value
  }

  function resolveDashboardRoute (role?: string): string {
    switch (role) {
      case 'super_admin':
      case 'superadmin': {
        return '/superadmin/dashboard'
      }
      case 'clienta': {
        return '/company/dashboard'
      }
      case 'client_b':
      case 'clientb': {
        return '/clientb/dashboard'
      }
      default: {
        return '/'
      }
    }
  }

  function isVerificationRequired (errorData: any): boolean {
    return (
      errorData?.action_required === 'verify_email'
      || errorData?.email_verified === false
    )
  }

  function redirectToEmailVerification (errorData: any) {
    localStorage.setItem('pendingVerificationEmail', email.value)
    router.push({
      path: '/auth/email-verification',
      query: {
        message: errorData?.message || 'Veuillez vérifier votre email',
        email: email.value,
      },
    })
  }

  function applyLoginError (error_: any) {
    const errorData = error_?.response?.data
    if (isVerificationRequired(errorData)) {
      redirectToEmailVerification(errorData)
      return
    }
    error.value
      = errorData?.message || 'Erreur de connexion. Vérifiez vos identifiants.'
  }

  async function handleSubmit () {
    if (loading.value) {
      return
    }

    error.value = ''
    successMessage.value = ''

    if (hasEmptyCredentials()) {
      error.value = 'Veuillez remplir tous les champs'
      return
    }

    try {
      await authStore.login({ email: email.value, password: password.value })
      router.push(resolveDashboardRoute(authStore.user?.role))
    } catch (error_: any) {
      applyLoginError(error_)
    }
  }

  function navigateToSignup () {
    router.push('/auth/signup')
  }

  function navigateToForgotPassword () {
    router.push('/auth/forgot-password')
  }

  function navigateToLanding () {
    router.push('/landing')
  }

  function handleGoogleLogin () {
    const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${config.oauth.google.clientId}&redirect_uri=${config.oauth.google.redirectUri}&response_type=code&scope=openid%20email%20profile&access_type=offline`
    window.location.href = googleAuthUrl
  }

  const demoAccounts = [
    { role: 'Super Admin', email: 'admin@bestexperts.com', password: 'demo' },
    { role: 'Client A (Approuvé)', email: 'clienta@demo.com', password: 'demo' },
    {
      role: 'Client A (En attente)',
      email: 'clienta-pending@demo.com',
      password: 'demo',
    },
    { role: 'Client B', email: 'clientb@demo.com', password: 'demo' },
  ]

  function fillDemoAccount (account: { email: string, password: string }) {
    email.value = account.email
    password.value = account.password
  }
</script>

<template>
  <div class="login-container">
    <!-- Animated Background Patterns -->
    <div class="animated-bg">
      <div class="shape shape-1" />
      <div class="shape shape-2" />
      <div class="shape shape-3" />
    </div>

    <v-container class="fill-height pa-0" fluid>
      <v-row class="ma-0 fill-height" no-gutters>
        <!-- Left Panel - Branding -->
        <LoginBrandingPanel />

        <!-- Right Panel - Login Form -->
        <v-col
          class="d-flex flex-column justify-center login-panel"
          cols="12"
          lg="6"
        >
          <div class="login-content">
            <!-- Mobile Logo -->
            <div class="mobile-logo d-lg-none" data-aos="zoom-in">
              <div class="mobile-logo-icon">
                <v-icon color="white" size="40">mdi-shield-check</v-icon>
              </div>
              <h1>Best Experts-Group</h1>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section" data-aos="fade-down">
              <div class="welcome-badge">
                <v-icon size="20">mdi-hand-wave</v-icon>
                <span>Bienvenue !</span>
              </div>
              <h2 class="welcome-title">Connectez-vous à votre SMI</h2>
              <p class="welcome-text">
                Gérez votre conformité ISO en temps réel
              </p>
            </div>

            <!-- Alerts -->
            <v-scroll-y-transition>
              <v-alert
                v-if="successMessage"
                class="alert-custom success-alert"
                closable
                icon="mdi-check-circle"
                @click:close="successMessage = ''"
              >
                {{ successMessage }}
              </v-alert>
            </v-scroll-y-transition>

            <v-scroll-y-transition>
              <v-alert
                v-if="error"
                class="alert-custom error-alert"
                closable
                icon="mdi-alert-circle"
                @click:close="error = ''"
              >
                {{ error }}
              </v-alert>
            </v-scroll-y-transition>

            <!-- Login Form -->
            <v-form
              class="login-form"
              data-aos="fade-up"
              @submit.prevent="handleSubmit"
            >
              <div v-if="loading" class="form-loader-overlay">
                <UnifiedLoader
                  description="Vérification des informations de connexion"
                  :show-skeleton="false"
                  title="Connexion en cours..."
                  variant="local"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Adresse email</label>
                <v-text-field
                  v-model="email"
                  class="custom-input"
                  density="comfortable"
                  :disabled="loading"
                  hide-details="auto"
                  placeholder="exemple@entreprise.com"
                  prepend-inner-icon="mdi-email-outline"
                  type="email"
                  variant="outlined"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <v-text-field
                  v-model="password"
                  :append-inner-icon="
                    showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
                  "
                  class="custom-input"
                  density="comfortable"
                  :disabled="loading"
                  hide-details="auto"
                  placeholder="••••••••"
                  prepend-inner-icon="mdi-lock-outline"
                  :type="showPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPassword = !showPassword"
                />
              </div>

              <div class="form-options">
                <v-checkbox
                  v-model="rememberMe"
                  class="custom-checkbox"
                  color="#4471c4"
                  density="compact"
                  :disabled="loading"
                  hide-details
                  label="Se souvenir de moi"
                />
                <a
                  class="forgot-link"
                  @click="navigateToForgotPassword"
                >Mot de passe oublié ?</a>
              </div>

              <v-btn
                block
                class="login-btn"
                :disabled="loading"
                :loading="loading"
                size="x-large"
                type="submit"
              >
                <v-icon class="mr-2">mdi-login-variant</v-icon>
                Se connecter
              </v-btn>
            </v-form>

            <!-- Divider -->
            <div class="divider-section">
              <span class="divider-text">ou continuez avec</span>
            </div>

            <!-- Social Login -->
            <v-btn
              block
              class="google-btn"
              :disabled="loading"
              size="large"
              variant="outlined"
              @click="handleGoogleLogin"
            >
              <v-icon class="mr-2" color="#DB4437">mdi-google</v-icon>
              Google
            </v-btn>

            <!-- Signup Link -->
            <div class="signup-section">
              <span>Pas encore de compte ?</span>
              <a
                class="signup-link"
                @click="navigateToSignup"
              >Créer un compte</a>
            </div>

            <LoginDemoAccounts
              :accounts="demoAccounts"
              :open="showDemo"
              @select="fillDemoAccount"
              @toggle="showDemo = !showDemo"
            />

            <!-- Back Link -->
            <div class="back-section">
              <a class="back-link" @click="navigateToLanding">
                <v-icon size="18">mdi-arrow-left</v-icon>
                <span>Retour à l'accueil</span>
              </a>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<style scoped lang="scss">
.login-container {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: #f8f9fa;
}

// Animated Background
.animated-bg {
  position: absolute;
  top: 0;
  right: 0;
  width: 50%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;

  @media (max-width: 1023px) {
    display: none;
  }
}

.shape {
  position: absolute;
  background: linear-gradient(
    135deg,
    rgba(68, 113, 196, 0.1),
    rgba(42, 74, 143, 0.1)
  );
  border-radius: 50%;
  animation: float 20s ease-in-out infinite;

  &.shape-1 {
    width: 300px;
    height: 300px;
    top: -100px;
    right: -50px;
    animation-delay: 0s;
  }

  &.shape-2 {
    width: 200px;
    height: 200px;
    top: 40%;
    right: 10%;
    animation-delay: 2s;
  }

  &.shape-3 {
    width: 150px;
    height: 150px;
    bottom: 10%;
    right: 30%;
    animation-delay: 4s;
  }
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(180deg);
  }
}

// Branding Panel
:deep(.branding-panel) {
  background: linear-gradient(135deg, #4471c4 0%, #2a4a8f 100%);
  position: relative;
  overflow: hidden;

  &::before {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -200px;
    left: -200px;
  }

  &::after {
    content: "";
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
    bottom: -150px;
    right: -150px;
  }
}

:deep(.branding-content) {
  position: relative;
  z-index: 1;
  padding: 4rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 100%;
}

:deep(.logo-card) {
  text-align: center;
  margin-bottom: 3rem;
  animation: fadeInDown 0.8s ease-out;
}

:deep(.logo-wrapper) {
  width: 100px;
  height: 100px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;

  &:hover {
    transform: scale(1.05);
  }
}

:deep(.brand-title) {
  color: white;
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  letter-spacing: -0.5px;
}

:deep(.brand-subtitle) {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  font-weight: 400;
}

:deep(.features-grid) {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  width: 100%;
  max-width: 500px;
  margin-bottom: 2rem;
}

:deep(.feature-card) {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;

  &:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
  }
}

:deep(.feature-icon) {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

:deep(.feature-title) {
  color: white;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

:deep(.feature-text) {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  line-height: 1.4;
  margin: 0;
}

:deep(.trust-badge) {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50px;
  padding: 0.75rem 1.5rem;
  color: white;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  margin-bottom: 2rem;
}

:deep(.brand-badges) {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 1rem;
}

:deep(.mini-badge) {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

:deep(.stats-row) {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2rem;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  padding: 1.5rem;
  max-width: 500px;
  width: 100%;
}

:deep(.stat-item) {
  text-align: center;
  flex: 1;
}

:deep(.stat-number) {
  color: white;
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

:deep(.stat-label) {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.stat-divider) {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
}

// Login Panel
.login-panel {
  padding: 2rem;
  position: relative;
  z-index: 1;
}

.login-content {
  max-width: 480px;
  width: 100%;
  margin: 0 auto;
}

.mobile-logo {
  text-align: center;
  margin-bottom: 3rem;

  h1 {
    color: #1a1a1a;
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 1rem;
  }
}

.mobile-logo-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #4471c4, #2a4a8f);
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(68, 113, 196, 0.3);
}

.welcome-section {
  margin-bottom: 2rem;
  animation: fadeInUp 0.8s ease-out;
}

.welcome-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(
    135deg,
    rgba(68, 113, 196, 0.1),
    rgba(42, 74, 143, 0.1)
  );
  color: #4471c4;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.welcome-title {
  color: #1a1a1a;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  letter-spacing: -0.5px;
}

.welcome-text {
  color: #64748b;
  font-size: 1rem;
  line-height: 1.6;
}

// Alerts
.alert-custom {
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);

  &.success-alert {
    background: linear-gradient(
      135deg,
      rgba(76, 175, 80, 0.1),
      rgba(56, 142, 60, 0.1)
    );
    color: #2e7d32;
  }

  &.error-alert {
    background: linear-gradient(
      135deg,
      rgba(244, 67, 54, 0.1),
      rgba(211, 47, 47, 0.1)
    );
    color: #c62828;
  }
}

// Form
.login-form {
  margin-bottom: 2rem;
  position: relative;
}

.form-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(2px);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  color: #334155;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.custom-input {
  :deep(.v-field) {
    border-radius: 12px;
    background: white;
    border: 2px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;

    &:hover {
      border-color: #cbd5e1;
    }

    &.v-field--focused {
      border-color: #4471c4;
      box-shadow: 0 0 0 4px rgba(68, 113, 196, 0.1);
    }
  }

  :deep(.v-field__input) {
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
  }
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.custom-checkbox {
  :deep(.v-label) {
    font-size: 0.875rem;
    color: #64748b;
  }
}

.forgot-link {
  color: #4471c4;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: color 0.2s ease;

  &:hover {
    color: #2a4a8f;
  }
}

.login-btn {
  background: linear-gradient(135deg, #4471c4 0%, #2a4a8f 100%);
  color: white;
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.3px;
  box-shadow: 0 4px 14px rgba(68, 113, 196, 0.4);
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 6px 20px rgba(68, 113, 196, 0.5);
    transform: translateY(-2px);
  }

  &:active {
    transform: translateY(0);
  }
}

.divider-section {
  position: relative;
  text-align: center;
  margin: 2rem 0;

  &::before,
  &::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background: #e2e8f0;
  }

  &::before {
    left: 0;
  }

  &::after {
    right: 0;
  }
}

.divider-text {
  color: #94a3b8;
  font-size: 0.875rem;
  background: #f8f9fa;
  padding: 0 1rem;
  position: relative;
  z-index: 1;
}

.google-btn {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  text-transform: none;
  font-weight: 500;
  color: #475569;
  margin-bottom: 2rem;
  transition: all 0.3s ease;

  &:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
}

.signup-section {
  text-align: center;
  color: #64748b;
  font-size: 0.9375rem;
  margin-bottom: 1.5rem;

  span {
    margin-right: 0.5rem;
  }
}

.signup-link {
  color: #4471c4;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: color 0.2s ease;

  &:hover {
    color: #2a4a8f;
    text-decoration: underline;
  }
}

:deep(.demo-section) {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

:deep(.demo-toggle) {
  width: 100%;
  padding: 1rem;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #475569;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: #f1f5f9;
  }

  .v-icon:last-child {
    margin-left: auto;
    transition: transform 0.3s ease;

    &.rotated {
      transform: rotate(180deg);
    }
  }
}

:deep(.demo-accounts) {
  padding: 0 1rem 1rem;
}

:deep(.demo-account) {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;

  &:last-child {
    margin-bottom: 0;
  }

  &:hover {
    background: #f8fafc;
    border-color: #4471c4;
    transform: translateX(4px);
  }
}

:deep(.demo-account-icon) {
  color: #4471c4;
}

:deep(.demo-account-info) {
  flex: 1;
}

:deep(.demo-account-role) {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.125rem;
}

:deep(.demo-account-email) {
  font-size: 0.75rem;
  color: #64748b;
}

:deep(.demo-account-arrow) {
  color: #cbd5e1;
  transition: all 0.2s ease;

  .demo-account:hover & {
    color: #4471c4;
    transform: translateX(4px);
  }
}

.back-section {
  text-align: center;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.875rem;
  text-decoration: none;
  cursor: pointer;
  transition: color 0.2s ease;

  &:hover {
    color: #475569;
  }
}

// Animations
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

// Responsive
@media (max-width: 1023px) {
  .login-panel {
    padding: 2rem 1rem;
  }

  .welcome-title {
    font-size: 1.75rem;
  }
}
</style>
