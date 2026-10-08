<template>
  <div class="lock-screen-container">
    <!-- Animated Background -->
    <div class="animated-bg">
      <div class="shape shape-1" />
      <div class="shape shape-2" />
      <div class="shape shape-3" />
    </div>

    <v-container class="fill-height d-flex align-center justify-center">
      <v-card
        class="lock-screen-card"
        elevation="12"
        max-width="450"
        width="100%"
      >
        <v-card-text class="pa-8">
          <!-- Lock Icon -->
          <div class="text-center mb-6">
            <div class="lock-icon-wrapper">
              <v-icon color="primary" size="64">mdi-lock</v-icon>
            </div>
          </div>

          <!-- User Info -->
          <div class="text-center mb-6">
            <v-avatar class="mb-3" color="primary" size="80">
              <span class="text-h5 font-weight-bold">{{ userInitials }}</span>
            </v-avatar>
            <h2 class="text-h5 font-weight-bold mb-2">Session verrouillée</h2>
            <p class="text-body-1 text-medium-emphasis mb-1">{{ userName }}</p>
            <p class="text-caption text-medium-emphasis">{{ userEmail }}</p>

            <v-chip
              v-if="lockedAt"
              class="mt-3"
              color="primary"
              size="small"
              variant="tonal"
            >
              <v-icon size="16" start>mdi-clock-outline</v-icon>
              Verrouillée {{ formattedLockTime }}
            </v-chip>
          </div>

          <!-- Error Message -->
          <v-scroll-y-transition>
            <v-alert
              v-if="error"
              class="mb-4"
              closable
              color="error"
              icon="mdi-alert-circle"
              variant="tonal"
              @click:close="error = ''"
            >
              {{ error }}
            </v-alert>
          </v-scroll-y-transition>

          <!-- Password Form -->
          <v-form @submit.prevent="handleUnlock">
            <div class="mb-4">
              <label class="form-label mb-2 d-block">Mot de passe</label>
              <v-text-field
                v-model="password"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                autofocus
                density="comfortable"
                :disabled="loading"
                hide-details="auto"
                placeholder="Entrez votre mot de passe"
                prepend-inner-icon="mdi-lock-outline"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                @click:append-inner="showPassword = !showPassword"
              />
            </div>

            <v-btn
              block
              class="mb-4"
              color="primary"
              :loading="loading"
              size="x-large"
              type="submit"
            >
              <v-icon class="mr-2">mdi-lock-open</v-icon>
              Déverrouiller
            </v-btn>
          </v-form>

          <!-- Alternative Actions -->
          <v-divider class="my-4" />

          <div class="text-center">
            <v-btn
              color="grey-darken-1"
              variant="text"
              @click="handleLogout"
            >
              <v-icon class="mr-2" size="20">mdi-account-switch</v-icon>
              Se connecter avec un autre compte
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuth } from '@/composables/useAuth'
  import { useLockScreenStore } from '@/stores/lockScreen'

  const router = useRouter()
  const { user: authUser, logout } = useAuth()
  const lockScreenStore = useLockScreenStore()

  const password = ref('')
  const error = ref('')
  const loading = ref(false)
  const showPassword = ref(false)

  const userName = computed(() => authUser?.name || authUser?.username || 'Utilisateur')
  const userEmail = computed(() => authUser?.email || '')
  const userInitials = computed(() => {
    const names = userName.value.split(' ')
    return names.map((n: string) => n[0]).join('').toUpperCase().slice(0, 2)
  })

  const lockedAt = computed(() => lockScreenStore.lockedAt)

  const formattedLockTime = computed(() => {
    if (!lockedAt.value) return ''

    const now = new Date()
    const locked = new Date(lockedAt.value)
    const diffMs = now.getTime() - locked.getTime()
    const diffMins = Math.floor(diffMs / 60_000)

    if (diffMins < 1) return 'à l\'instant'
    if (diffMins === 1) return 'il y a 1 minute'
    if (diffMins < 60) return `il y a ${diffMins} minutes`

    const diffHours = Math.floor(diffMins / 60)
    if (diffHours === 1) return 'il y a 1 heure'
    if (diffHours < 24) return `il y a ${diffHours} heures`

    const diffDays = Math.floor(diffHours / 24)
    if (diffDays === 1) return 'il y a 1 jour'
    return `il y a ${diffDays} jours`
  })

  onMounted(() => {
    // Vérifier si la session est bien verrouillée
    if (!lockScreenStore.isLocked && !lockScreenStore.checkLockStatus()) {
      // Si non verrouillée, rediriger vers le dashboard
      router.push('/')
    }

    // Empêcher le retour arrière
    const preventBack = () => {
      window.history.pushState(null, '', window.location.href)
    }

    // Ajouter un état dans l'historique pour bloquer le retour
    window.history.pushState(null, '', window.location.href)
    window.addEventListener('popstate', preventBack)

    // Nettoyer au démontage
    return () => {
      window.removeEventListener('popstate', preventBack)
    }
  })

  async function handleUnlock () {
    error.value = ''

    if (!password.value) {
      error.value = 'Veuillez entrer votre mot de passe'
      return
    }

    loading.value = true

    try {
      // Récupérer l'email depuis le store (priorité) ou localStorage
      const emailToUse = lockScreenStore.userEmail || userEmail.value

      if (!emailToUse) {
        error.value = 'Email introuvable. Veuillez vous reconnecter.'
        loading.value = false
        return
      }

      // Utiliser authService directement pour éviter la redirection automatique
      const authService = (await import('@/services/authService')).default
      await authService.login({
        email: emailToUse,
        password: password.value,
      })

      // Si la connexion réussit, déverrouiller
      const returnPath = lockScreenStore.unlockSession()

      // Rediriger vers la page d'origine
      router.push(returnPath || '/')
    } catch (error_: any) {
      // Si erreur, afficher le message et rester verrouillé
      error.value = error_?.response?.data?.message || error_?.message || 'Mot de passe incorrect'
    } finally {
      loading.value = false
    }
  }

  function handleLogout () {
    lockScreenStore.unlockSession()
    logout()
  }
</script>

<style scoped lang="scss">
.lock-screen-container {
  position: relative;
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  overflow: hidden;
}

// Animated Background
.animated-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.shape {
  position: absolute;
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.1), rgba(42, 74, 143, 0.1));
  border-radius: 50%;
  animation: float 20s ease-in-out infinite;

  &.shape-1 {
    width: 400px;
    height: 400px;
    top: -100px;
    left: -100px;
    animation-delay: 0s;
  }

  &.shape-2 {
    width: 300px;
    height: 300px;
    top: 50%;
    right: -50px;
    animation-delay: 2s;
  }

  &.shape-3 {
    width: 250px;
    height: 250px;
    bottom: -50px;
    left: 50%;
    animation-delay: 4s;
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(180deg);
  }
}

.lock-screen-card {
  position: relative;
  z-index: 1;
  border-radius: 24px !important;
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
}

.lock-icon-wrapper {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.1), rgba(42, 74, 143, 0.15));
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(68, 113, 196, 0.4);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 10px rgba(68, 113, 196, 0);
  }
}

.form-label {
  color: #334155;
  font-size: 0.875rem;
  font-weight: 600;
}

:deep(.v-field) {
  border-radius: 12px;
  background: white;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;

  &:hover {
    border-color: #cbd5e1;
  }

  &.v-field--focused {
    border-color: #4471c4;
    box-shadow: 0 0 0 4px rgba(68, 113, 196, 0.1);
  }
}

:deep(.v-btn) {
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.3px;
}
</style>
