<template>
  <ClientBLayout current-page="/clientb/profile">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center">
            <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
              <v-icon size="32">mdi-account-circle</v-icon>
            </v-avatar>
            <div>
              <h1 class="text-h4 font-weight-bold mb-1">Mon Profil</h1>
              <p class="text-body-2 text-medium-emphasis">
                Gérez vos informations personnelles et vos préférences
              </p>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <!-- Main Content -->
        <v-col cols="12" lg="8">
          <!-- Personal Information -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-account-edit</v-icon>
                <h2 class="text-h6 font-weight-bold">Informations personnelles</h2>
              </div>

              <v-form ref="profileForm" @submit.prevent="handleUpdateProfile">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.name"
                      density="comfortable"
                      label="Nom complet"
                      prepend-inner-icon="mdi-account-outline"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.username"
                      density="comfortable"
                      hint="Le nom d'utilisateur ne peut pas être modifié"
                      label="Nom d'utilisateur"
                      persistent-hint
                      prepend-inner-icon="mdi-at"
                      readonly
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.email"
                      density="comfortable"
                      label="Email"
                      prepend-inner-icon="mdi-email-outline"
                      :rules="[rules.required, rules.email]"
                      type="email"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.phone"
                      density="comfortable"
                      label="Téléphone"
                      prepend-inner-icon="mdi-phone-outline"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                  <v-btn
                    color="primary"
                    :loading="updatingProfile"
                    prepend-icon="mdi-content-save"
                    size="large"
                    type="submit"
                    variant="flat"
                  >
                    Enregistrer les modifications
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Change Password -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-lock-reset</v-icon>
                <h2 class="text-h6 font-weight-bold">Changer le mot de passe</h2>
              </div>

              <v-form ref="passwordForm" @submit.prevent="handleChangePassword">
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      v-model="passwordData.current_password"
                      :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      density="comfortable"
                      label="Mot de passe actuel"
                      prepend-inner-icon="mdi-lock-outline"
                      :rules="[rules.required]"
                      :type="showCurrentPassword ? 'text' : 'password'"
                      variant="outlined"
                      @click:append-inner="showCurrentPassword = !showCurrentPassword"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.new_password"
                      :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      density="comfortable"
                      hint="Minimum 8 caractères"
                      label="Nouveau mot de passe"
                      persistent-hint
                      prepend-inner-icon="mdi-lock-outline"
                      :rules="[rules.required, rules.minLength]"
                      :type="showNewPassword ? 'text' : 'password'"
                      variant="outlined"
                      @click:append-inner="showNewPassword = !showNewPassword"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.confirm_password"
                      :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      density="comfortable"
                      label="Confirmer le mot de passe"
                      prepend-inner-icon="mdi-lock-check-outline"
                      :rules="[rules.required, rules.passwordMatch]"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      variant="outlined"
                      @click:append-inner="showConfirmPassword = !showConfirmPassword"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                  <v-btn
                    color="primary"
                    :loading="changingPassword"
                    prepend-icon="mdi-lock-reset"
                    size="large"
                    type="submit"
                    variant="flat"
                  >
                    Changer le mot de passe
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Account Security -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-shield-account</v-icon>
                <h2 class="text-h6 font-weight-bold">Sécurité du compte</h2>
              </div>

              <v-list class="bg-transparent">
                <v-list-item>
                  <template #prepend>
                    <v-icon color="primary">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title>Email vérifié</v-list-item-title>
                  <v-list-item-subtitle>{{ user?.email }}</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon color="info">mdi-clock-outline</v-icon>
                  </template>
                  <v-list-item-title>Dernière connexion</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate(user?.last_login_at) }}</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon color="warning">mdi-account-clock</v-icon>
                  </template>
                  <v-list-item-title>Compte créé</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate((user as any)?.created_at) }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Account Info -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6 text-center">
              <v-avatar
                class="mb-4"
                color="primary"
                size="120"
                variant="tonal"
              >
                <span class="text-h3 font-weight-bold text-primary">
                  {{ getInitials(user?.name || user?.username || 'C') }}
                </span>
              </v-avatar>

              <h3 class="text-h5 font-weight-bold mb-2">
                {{ user?.name || user?.username || 'Client' }}
              </h3>

              <v-chip class="mb-4" color="primary" size="small" variant="flat">
                <v-icon size="small" start>mdi-account</v-icon>
                Client
              </v-chip>

              <v-divider class="my-4" />

              <div class="text-left">
                <div class="d-flex align-center mb-3">
                  <v-icon class="mr-3" color="primary" size="20">mdi-email-outline</v-icon>
                  <span class="text-body-2">{{ user?.email || 'N/A' }}</span>
                </div>

                <div v-if="user?.phone" class="d-flex align-center mb-3">
                  <v-icon class="mr-3" color="primary" size="20">mdi-phone-outline</v-icon>
                  <span class="text-body-2">{{ user?.phone }}</span>
                </div>

                <div class="d-flex align-center mb-3">
                  <v-icon class="mr-3" color="primary" size="20">mdi-calendar-outline</v-icon>
                  <span class="text-body-2">Membre depuis {{ formatDate((user as any)?.created_at) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Quick Stats -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="28">mdi-chart-box</v-icon>
                <h2 class="text-h6 font-weight-bold">Statistiques</h2>
              </div>

              <v-progress-linear v-if="loadingStats" class="mb-4" color="primary" indeterminate />

              <v-list class="bg-transparent">
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="info" size="40" variant="tonal">
                      <v-icon size="20">mdi-file-document</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Plaintes</v-list-item-title>
                  <v-list-item-subtitle>Total</v-list-item-subtitle>
                  <template #append>
                    <div class="text-h6 font-weight-bold text-info">{{ stats.complaints.total }}</div>
                  </template>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="warning" size="40" variant="tonal">
                      <v-icon size="20">mdi-clock-outline</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">En attente</v-list-item-title>
                  <v-list-item-subtitle>Plaintes</v-list-item-subtitle>
                  <template #append>
                    <div class="text-h6 font-weight-bold text-warning">{{ stats.complaints.pending }}</div>
                  </template>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="primary" size="40" variant="tonal">
                      <v-icon size="20">mdi-check-circle</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Résolues</v-list-item-title>
                  <v-list-item-subtitle>Plaintes</v-list-item-subtitle>
                  <template #append>
                    <div class="text-h6 font-weight-bold text-primary">{{ stats.complaints.resolved }}</div>
                  </template>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="purple" size="40" variant="tonal">
                      <v-icon size="20">mdi-clipboard-text</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Enquêtes</v-list-item-title>
                  <v-list-item-subtitle>Complétées</v-list-item-subtitle>
                  <template #append>
                    <div class="text-h6 font-weight-bold text-purple">{{ stats.satisfaction_forms.completed }}</div>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import ClientBLayout from '../components/ClientBLayout.vue'

  const authStore = useAuthStore()
  const toast = useToast()

  const user = computed(() => authStore.user)
  const updatingProfile = ref(false)
  const changingPassword = ref(false)
  const loadingStats = ref(false)

  const showCurrentPassword = ref(false)
  const showNewPassword = ref(false)
  const showConfirmPassword = ref(false)

  const profileForm = ref()
  const passwordForm = ref()

  const profileData = ref({
    name: '',
    email: '',
    phone: '',
    username: '',
  })

  const passwordData = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
  })

  // Statistics data
  const stats = ref({
    complaints: {
      total: 0,
      pending: 0,
      in_progress: 0,
      resolved: 0,
      closed: 0,
    },
    satisfaction_forms: {
      total: 0,
      completed: 0,
      draft: 0,
    },
  })

  const rules = {
    required: (v: any) => !!v || 'Ce champ est obligatoire',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => v.length >= 8 || 'Minimum 8 caractères',
    passwordMatch: (v: string) => v === passwordData.value.new_password || 'Les mots de passe ne correspondent pas',
  }

  onMounted(() => {
    loadProfile()
    loadStats()
  })

  function loadProfile () {
    if (user.value) {
      profileData.value = {
        name: user.value.name || '',
        email: user.value.email || '',
        phone: user.value.phone || '',
        username: user.value.username || '',
      }
    }
  }

  async function loadStats () {
    loadingStats.value = true
    try {
      const { data } = await api.get('/clientb/profile/stats')
      if (data.success) {
        stats.value = data.data
      }
    } catch (error: any) {
      console.error('Error loading stats:', error)
    } finally {
      loadingStats.value = false
    }
  }

  async function handleUpdateProfile () {
    const { valid } = await profileForm.value.validate()
    if (!valid) return

    updatingProfile.value = true
    try {
      const { data } = await api.put('/clientb/profile', {
        name: profileData.value.name,
        email: profileData.value.email,
        phone: profileData.value.phone,
      })

      // Update store with new user data
      if (data.success && data.data) {
        authStore.updateUser(data.data)
        toast.success('Profil mis à jour avec succès')
        loadProfile()
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour')
    } finally {
      updatingProfile.value = false
    }
  }

  async function handleChangePassword () {
    const { valid } = await passwordForm.value.validate()
    if (!valid) return

    changingPassword.value = true
    try {
      await api.post('/clientb/profile/change-password', {
        current_password: passwordData.value.current_password,
        new_password: passwordData.value.new_password,
        new_password_confirmation: passwordData.value.confirm_password,
      })

      toast.success('Mot de passe modifié avec succès')
      passwordData.value = {
        current_password: '',
        new_password: '',
        confirm_password: '',
      }
      passwordForm.value.reset()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors du changement de mot de passe')
    } finally {
      changingPassword.value = false
    }
  }

  function getInitials (name: string): string {
    if (!name) return 'C'
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
</script>
