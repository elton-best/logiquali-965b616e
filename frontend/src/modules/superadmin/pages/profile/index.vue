<template>
  <SuperAdminLayout current-page="profile">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center">
            <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
              <v-icon size="32">mdi-account-circle</v-icon>
            </v-avatar>
            <div>
              <h1 class="text-h4 font-weight-bold text-primary mb-1">
                Mon Profil
              </h1>
              <p class="text-subtitle-1 text-grey-darken-1">
                Gérez vos informations personnelles et vos préférences
              </p>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <!-- Main Content -->
        <v-col cols="12" lg="8">
          <!-- Avatar Upload -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-camera</v-icon>
                <h2 class="text-h6 font-weight-bold">Photo de profil</h2>
              </div>

              <div class="d-flex align-center flex-wrap ga-4">
                <v-avatar color="primary" size="120" variant="tonal">
                  <v-img v-if="profile.avatar" cover :src="profile.avatar" />
                  <v-icon v-else size="64">mdi-account</v-icon>
                </v-avatar>

                <div>
                  <v-btn
                    class="mb-2"
                    color="primary"
                    prepend-icon="mdi-upload"
                    variant="flat"
                    @click="uploadAvatar"
                  >
                    Télécharger une photo
                  </v-btn>
                  <p class="text-caption text-medium-emphasis">
                    JPG, PNG ou GIF. Taille maximale de 2 Mo.
                  </p>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Personal Information -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-account-edit</v-icon>
                <h2 class="text-h6 font-weight-bold">
                  Informations personnelles
                </h2>
              </div>

              <v-form>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profile.firstName"
                      density="comfortable"
                      label="Prénom"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profile.lastName"
                      density="comfortable"
                      label="Nom"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="profile.email"
                      density="comfortable"
                      hint="L'email ne peut pas être modifié"
                      label="Email"
                      persistent-hint
                      prepend-inner-icon="mdi-email"
                      readonly
                      type="email"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="profile.phone"
                      density="comfortable"
                      label="Téléphone"
                      prepend-inner-icon="mdi-phone"
                      type="tel"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="profile.role"
                      density="comfortable"
                      label="Rôle"
                      prepend-inner-icon="mdi-shield-account"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex align-center justify-end ga-2">
                  <v-btn variant="outlined" @click="resetForm">Annuler</v-btn>
                  <v-btn color="primary" variant="flat" @click="saveProfile">
                    <v-icon start>mdi-content-save</v-icon>
                    Enregistrer
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Security Section -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-shield-lock</v-icon>
                <h2 class="text-h6 font-weight-bold">Sécurité</h2>
              </div>

              <v-alert
                v-if="isPasswordChangeRequired"
                class="mb-4"
                color="warning"
                icon="mdi-alert-circle-outline"
                variant="tonal"
              >
                Vous devez changer votre mot de passe avant de continuer.
              </v-alert>

              <v-form>
                <v-text-field
                  v-model="security.currentPassword"
                  :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  class="mb-4"
                  density="comfortable"
                  label="Mot de passe actuel"
                  prepend-inner-icon="mdi-lock"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showCurrentPassword = !showCurrentPassword"
                />
                <v-text-field
                  v-model="security.newPassword"
                  :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  class="mb-4"
                  density="comfortable"
                  :error="passwordRuleErrors.length > 0 && security.newPassword.length > 0"
                  :error-messages="passwordRuleErrors"
                  label="Nouveau mot de passe"
                  prepend-inner-icon="mdi-lock-plus"
                  :type="showNewPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showNewPassword = !showNewPassword"
                />
                <v-text-field
                  v-model="security.confirmPassword"
                  :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  class="mb-4"
                  density="comfortable"
                  :error="passwordMismatch"
                  :error-messages="passwordMismatch ? ['Les mots de passe ne correspondent pas'] : []"
                  :hint="passwordMatchHint"
                  label="Confirmer le nouveau mot de passe"
                  :persistent-hint="Boolean(passwordMatchHint)"
                  prepend-inner-icon="mdi-lock-check"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showConfirmPassword = !showConfirmPassword"
                />

                <EmptyState
                  description="Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre."
                  icon="mdi-lock-check-outline"
                  title="Règles de sécurité"
                />

                <div class="d-flex align-center justify-end">
                  <v-btn
                    color="primary"
                    :disabled="!canSubmitPassword"
                    :loading="passwordSubmitting"
                    variant="flat"
                    @click="changePassword"
                  >
                    <v-icon start>mdi-shield-check</v-icon>
                    Changer le mot de passe
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Preferences Section -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-cog</v-icon>
                <h2 class="text-h6 font-weight-bold">Préférences</h2>
              </div>

              <v-form>
                <v-select
                  v-model="preferences.language"
                  class="mb-4"
                  density="comfortable"
                  :items="languages"
                  label="Langue"
                  prepend-inner-icon="mdi-translate"
                  variant="outlined"
                />

                <v-select
                  v-model="preferences.timezone"
                  class="mb-4"
                  density="comfortable"
                  :items="timezones"
                  label="Fuseau horaire"
                  prepend-inner-icon="mdi-clock-outline"
                  variant="outlined"
                />

                <v-divider class="my-6" />

                <h3 class="text-subtitle-1 font-weight-bold mb-4">
                  Notifications
                </h3>

                <v-switch
                  v-model="preferences.emailNotifications"
                  class="mb-2"
                  color="primary"
                  label="Notifications par email"
                />

                <v-switch
                  v-model="preferences.newSubscription"
                  class="mb-2"
                  color="primary"
                  label="Nouveaux abonnements"
                />

                <v-switch
                  v-model="preferences.kycPending"
                  class="mb-2"
                  color="primary"
                  label="KYC en attente"
                />

                <v-switch
                  v-model="preferences.paymentReceived"
                  class="mb-2"
                  color="primary"
                  label="Paiements reçus"
                />

      <div class="d-flex align-center mb-4">
        <v-switch
          v-model="preferences.systemAlerts"
          color="primary"
          label="Alertes système"
        />
        <v-tooltip location="top">
          <template #activator="{ props }">
            <v-icon v-bind="props" class="ml-2" size="18" color="primary">mdi-information</v-icon>
          </template>
          <span>Recevoir les notifications critiques de plate-forme; stocké localement</span>
        </v-tooltip>
      </div>

                <div class="d-flex align-center justify-end">
                  <v-btn
                    color="primary"
                    variant="flat"
                    @click="savePreferences"
                  >
                    <v-icon start>mdi-content-save</v-icon>
                    Enregistrer les préférences
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Account Status -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="success"
                  size="32"
                >mdi-check-circle</v-icon>
                <h2 class="text-h6 font-weight-bold">Statut du compte</h2>
              </div>

              <v-list class="bg-transparent" elevation="0">
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-icon color="success">mdi-shield-check</v-icon>
                  </template>
                  <v-list-item-title>Compte vérifié</v-list-item-title>
                </v-list-item>
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-icon color="success">mdi-email-check</v-icon>
                  </template>
                  <v-list-item-title>Email confirmé</v-list-item-title>
                </v-list-item>
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-icon color="success">mdi-lock-check</v-icon>
                  </template>
                  <v-list-item-title>2FA activé</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Activity Stats -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-chart-box</v-icon>
                <h2 class="text-h6 font-weight-bold">Activité</h2>
              </div>

              <EmptyState
                description="Statistiques d'activité indisponibles pour le moment."
                icon="mdi-chart-box-outline"
                title="Aucune statistique"
              />
            </v-card-text>
          </v-card>

          <!-- Active Sessions -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-devices</v-icon>
                <h2 class="text-h6 font-weight-bold">Sessions actives</h2>
              </div>

              <v-list class="bg-transparent" density="compact">
                <v-list-item
                  v-for="session in sessions"
                  :key="session.id"
                  class="px-0"
                >
                  <v-list-item-title class="font-weight-medium">
                    {{
                      session.is_current
                        ? "Session actuelle"
                        : session.name || "Session"
                    }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    Dernière activité:
                    {{ session.last_used_at || session.created_at || "-" }}
                  </v-list-item-subtitle>
                  <template #append>
                    <v-btn
                      v-if="!session.is_current"
                      color="error"
                      size="small"
                      variant="outlined"
                      @click="revokeSession(session.id)"
                    >
                      Déconnecter
                    </v-btn>
                  </template>
                </v-list-item>

                <div v-if="sessions.length === 0" class="pt-2">
                  <EmptyState
                    description="Aucune session n'est actuellement connectée."
                    icon="mdi-lan-disconnect"
                    title="Aucune session active"
                  />
                </div>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Quick Actions -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-lightning-bolt</v-icon>
                <h2 class="text-h6 font-weight-bold">Actions rapides</h2>
              </div>

              <v-btn
                block
                class="mb-3 text-none"
                prepend-icon="mdi-history"
                variant="outlined"
                @click="viewActivityLog"
              >
                Historique d'activité
              </v-btn>

              <v-btn
                block
                class="mb-3 text-none"
                prepend-icon="mdi-download"
                variant="outlined"
                @click="exportData"
              >
                Exporter mes données
              </v-btn>

              <v-btn
                block
                class="text-none"
                color="error"
                prepend-icon="mdi-account-remove"
                variant="outlined"
                @click="deleteAccount"
              >
                Supprimer le compte
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
      {{ snackbarMessage }}
    </v-snackbar>

    <!-- Avatar Upload Input -->
    <input
      ref="avatarInput"
      accept="image/*"
      style="display: none"
      type="file"
      @change="handleAvatarUpload"
    >
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/api/client'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import superAdminService from '@/services/superAdminService'
  import { useAuthStore } from '@/stores/auth'
  import SuperAdminLayout from '../../components/SuperAdminLayout.vue'

  const snackbar = ref(false)
  const snackbarMessage = ref('')
  const snackbarColor = ref('success')
  const avatarInput = ref<HTMLInputElement>()
  const authStore = useAuthStore()
  const route = useRoute()

  const profile = ref({
    firstName: 'Admin',
    lastName: 'Super',
    email: 'admin@BestQHSE.com',
    phone: '+237 690 000 000',
    role: 'Super Administrateur',
    avatar: '',
  })

  const security = ref({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  })

  const showCurrentPassword = ref(false)
  const showNewPassword = ref(false)
  const showConfirmPassword = ref(false)
  const passwordSubmitting = ref(false)

  const isPasswordChangeRequired = computed(
    () => route.query.blocking === 'PASSWORD_CHANGE_REQUIRED',
  )

  const passwordRuleErrors = computed(() => {
    const errors: string[] = []
    const value = security.value.newPassword || ''
    if (!value) return errors
    if (value.length < 8) errors.push('Au moins 8 caractères.')
    if (!/[A-Z]/.test(value)) errors.push('Au moins une majuscule.')
    if (!/[a-z]/.test(value)) errors.push('Au moins une minuscule.')
    if (!/[0-9]/.test(value)) errors.push('Au moins un chiffre.')
    return errors
  })

  const passwordMismatch = computed(() => {
    return (
      Boolean(security.value.confirmPassword)
      && security.value.newPassword !== security.value.confirmPassword
    )
  })

  const passwordMatchHint = computed(() => {
    if (!security.value.confirmPassword) return ''
    return passwordMismatch.value
      ? ''
      : 'Les mots de passe correspondent.'
  })

  const canSubmitPassword = computed(() => {
    return (
      Boolean(security.value.currentPassword)
      && Boolean(security.value.newPassword)
      && Boolean(security.value.confirmPassword)
      && passwordRuleErrors.value.length === 0
      && !passwordMismatch.value
    )
  })

  const preferences = ref({
    language: 'Français',
    timezone: 'Africa/Douala',
    emailNotifications: true,
    newSubscription: true,
    kycPending: true,
    paymentReceived: true,
    systemAlerts: false,
  })

  const languages = ['Français', 'English', 'Español']
  const timezones = [
    'Africa/Douala',
    'Africa/Lagos',
    'Africa/Accra',
    'Europe/Paris',
    'America/New_York',
  ]

  const sessions = ref<
    Array<{
      id: number
      name: string
      created_at: string | null
      last_used_at: string | null
      mfa_verified_at: string | null
      is_current: boolean
    }>
  >([])

  function uploadAvatar () {
    avatarInput.value?.click()
  }

  async function handleAvatarUpload (event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
      if (!authStore.user?.id) {
        showSnackbar('Utilisateur non authentifié', 'error')
        return
      }

      const form = new FormData()
      form.append('photo', file)
      try {
        const res = await api.upload(`/users/${authStore.user.id}/photo`, form)
        const data = (res as any)?.data?.data || (res as any).data
        if (data?.photo_url) {
          profile.value.avatar = data.photo_url
          authStore.updateUser({ photo_url: data.photo_url, photo_path: data.photo_path })
          showSnackbar('Photo de profil enregistrée', 'success')
        } else {
          showSnackbar('Photo mise à jour localement', 'warning')
        }
      } catch (error: any) {
        showSnackbar(error?.response?.data?.message || 'Impossible d\'uploader la photo', 'error')
      } finally {
        if (avatarInput.value) avatarInput.value.value = ''
      }
    }
  }

  async function loadProfile () {
    try {
      const response = await api.get<{ user: any }>('/auth/me')
      const user = response?.data?.user || response?.data
      const name = String(user?.name || '')
      const parts = name.split(' ')
      profile.value.firstName = parts[0] || profile.value.firstName
      profile.value.lastName = parts.slice(1).join(' ') || profile.value.lastName
      profile.value.email = user?.email || profile.value.email
      profile.value.phone = user?.phone || profile.value.phone
      profile.value.role
        = user?.user_type === 'super_admin'
          ? 'Super Administrateur'
          : profile.value.role
      authStore.updateUser(user)
      profile.value.avatar = user?.photo_url || user?.photo_path || profile.value.avatar
    } catch {}
  }

  function loadPreferencesFromStorage () {
    try {
      const raw = localStorage.getItem('user_preferences')
      if (raw) {
        const parsed = JSON.parse(raw)
        preferences.value = { ...preferences.value, ...parsed }
      }
    } catch (e) {
      // ignore parse errors
    }
  }

  async function saveProfile () {
    try {
      const name = `${profile.value.firstName} ${profile.value.lastName}`.trim()
      const response = await api.put<{ data: any }>('/auth/profile', {
        name,
        phone: profile.value.phone,
      })
      if (response?.data) {
        authStore.updateUser((response.data as any)?.data || response.data)
      }
      showSnackbar('Profil enregistré', 'success')
    } catch {}
  }

  async function loadSessions () {
    try {
      sessions.value = await superAdminService.getSessions()
    } catch {}
  }

  function resetForm () {
    showSnackbar('Modifications annulées', 'info')
  }

  async function changePassword () {
    if (!canSubmitPassword.value) {
      showSnackbar('Veuillez vérifier les champs du mot de passe', 'error')
      return
    }

    passwordSubmitting.value = true
    try {
      const response = await api.put<{ data: any }>('/auth/profile', {
        current_password: security.value.currentPassword,
        password: security.value.newPassword,
        password_confirmation: security.value.confirmPassword,
      })
      if (response?.data) {
        authStore.updateUser((response.data as any)?.data || response.data)
      }
      showSnackbar('Mot de passe modifié', 'success')
      security.value = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      }
    } catch (error: any) {
      showSnackbar(
        error?.response?.data?.message || 'Impossible de modifier le mot de passe',
        'error',
      )
    } finally {
      passwordSubmitting.value = false
    }
  }

  function savePreferences () {
    try {
      localStorage.setItem('user_preferences', JSON.stringify(preferences.value))
      showSnackbar('Préférences enregistrées', 'success')
    } catch (e) {
      showSnackbar('Impossible de sauvegarder les préférences localement', 'error')
    }
  }

  function viewActivityLog () {
    console.log('Voir l\'historique d\'activité')
  }

  function exportData () {
    showSnackbar('Export des données en cours...', 'info')
  }

  function deleteAccount () {
    showSnackbar('Cette action nécessite une confirmation', 'warning')
  }

  async function revokeSession (sessionId: number) {
    try {
      await superAdminService.revokeSession(sessionId)
      sessions.value = sessions.value.filter(
        session => session.id !== sessionId,
      )
      showSnackbar('Session revoquee', 'success')
    } catch {}
  }

  function showSnackbar (message: string, color: string) {
    snackbarMessage.value = message
    snackbarColor.value = color
    snackbar.value = true
  }

  onMounted(() => {
    loadProfile()
    loadSessions()
    loadPreferencesFromStorage()
  })

  watch(
    preferences,
    () => {
      try {
        localStorage.setItem('user_preferences', JSON.stringify(preferences.value))
      } catch {
        // noop
      }
    },
    { deep: true },
  )
</script>

<style scoped>
.v-card {
  transition: all 0.3s ease;
}

.v-btn {
  text-transform: none;
}
</style>
