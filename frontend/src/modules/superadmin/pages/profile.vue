<template>
  <SuperAdminLayout current-page="profile">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-h4 font-weight-bold text-primary mb-2">Mon Profil</h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Gérez vos informations personnelles et votre mot de passe
        </p>
      </div>

      <v-row>
        <!-- Profile Information -->
        <v-col cols="12" lg="8">
          <v-card elevation="2">
            <v-card-title class="pa-6 pb-4">
              <span class="text-h6 font-weight-bold">Informations Personnelles</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-form ref="profileForm" @submit.prevent="handleUpdateProfile">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.name"
                      :error-messages="profileFieldErrors.name"
                      label="Nom complet"
                      prepend-inner-icon="mdi-account-outline"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.email"
                      :error-messages="profileFieldErrors.email"
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
                      :error-messages="profileFieldErrors.phone"
                      label="Téléphone"
                      prepend-inner-icon="mdi-phone-outline"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="profileData.username"
                      :error-messages="profileFieldErrors.username"
                      label="Nom d'utilisateur"
                      prepend-inner-icon="mdi-at"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                  <v-btn
                    color="primary"
                    :loading="updatingProfile"
                    style="border-radius: 12px"
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
          <v-card
            class="mt-6"
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <span class="text-h6 font-weight-bold">Changer le mot de passe</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-form ref="passwordForm" @submit.prevent="handleChangePassword">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.current_password"
                      :error-messages="passwordFieldErrors.current_password"
                      label="Mot de passe actuel"
                      prepend-inner-icon="mdi-lock-outline"
                      :rules="[rules.required]"
                      type="password"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6" />

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.new_password"
                      :error-messages="passwordFieldErrors.new_password"
                      label="Nouveau mot de passe"
                      prepend-inner-icon="mdi-lock-outline"
                      :rules="[rules.required, rules.minLength]"
                      type="password"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.confirm_password"
                      :error-messages="passwordFieldErrors.new_password_confirmation"
                      label="Confirmer le mot de passe"
                      prepend-inner-icon="mdi-lock-check-outline"
                      :rules="[rules.required, rules.passwordMatch]"
                      type="password"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                  <v-btn
                    color="primary"
                    :loading="changingPassword"
                    style="border-radius: 12px"
                    type="submit"
                    variant="flat"
                  >
                    Changer le mot de passe
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Account Info -->
          <v-card
            elevation="2"
          >
            <v-card-text class="pa-6 text-center">
              <v-avatar
                class="mb-4"
                color="primary"
                size="120"
              >
                <span class="text-h3 font-weight-bold">
                  {{ getInitials(user?.name || user?.username || '') }}
                </span>
              </v-avatar>

              <h3 class="text-h5 font-weight-bold mb-2">
                {{ user?.name || user?.username }}
              </h3>

              <v-chip class="mb-4" color="primary" size="x-small" variant="tonal">
                Super Administrateur
              </v-chip>

              <v-divider class="my-4" />

              <div class="text-left">
                <div class="d-flex align-center mb-3">
                  <v-icon class="mr-3" color="on-surface-variant" size="20">mdi-email-outline</v-icon>
                  <span class="text-body-2">{{ user?.email }}</span>
                </div>

                <div v-if="user?.phone" class="d-flex align-center mb-3">
                  <v-icon class="mr-3" color="on-surface-variant" size="20">mdi-phone-outline</v-icon>
                  <span class="text-body-2">{{ user?.phone }}</span>
                </div>

                <div class="d-flex align-center">
                  <v-icon class="mr-3" color="on-surface-variant" size="20">mdi-calendar-outline</v-icon>
                  <span class="text-body-2">Membre depuis {{ formatDate((user as any)?.created_at) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/composables/useToast'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const toast = useToast()

  const user = computed(() => authStore.user)
  const updatingProfile = ref(false)
  const changingPassword = ref(false)

  const profileForm = ref()
  const passwordForm = ref()

  const profileData = ref({
    name: '',
    email: '',
    phone: '',
    username: '',
  })
  const profileFieldErrors = ref<Record<string, string[]>>({})

  const passwordData = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
  })
  const passwordFieldErrors = ref<Record<string, string[]>>({})

  const rules = {
    required: (v: any) => !!v || 'Ce champ est obligatoire',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => v.length >= 8 || 'Minimum 8 caractères',
    passwordMatch: (v: string) => v === passwordData.value.new_password || 'Les mots de passe ne correspondent pas',
  }

  onMounted(() => {
    loadProfile()
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

  async function handleUpdateProfile () {
    const { valid } = await profileForm.value.validate()
    if (!valid) return

    updatingProfile.value = true
    try {
      profileFieldErrors.value = {}
      const { data } = await api.put('/auth/profile', {
        name: profileData.value.name,
        email: profileData.value.email,
        phone: profileData.value.phone,
      })

      authStore.updateUser(data.data)
      toast.success('Profil mis à jour')
    } catch (error: any) {
      if (error?.response?.status === 422 && error?.response?.data?.errors) {
        profileFieldErrors.value = error.response.data.errors
      }
    } finally {
      updatingProfile.value = false
    }
  }

  async function handleChangePassword () {
    const { valid } = await passwordForm.value.validate()
    if (!valid) return

    changingPassword.value = true
    try {
      passwordFieldErrors.value = {}
      await api.post('/auth/change-password', {
        current_password: passwordData.value.current_password,
        new_password: passwordData.value.new_password,
        new_password_confirmation: passwordData.value.confirm_password,
      })

      toast.success('Mot de passe modifié')
      passwordData.value = {
        current_password: '',
        new_password: '',
        confirm_password: '',
      }
      passwordForm.value.reset()
    } catch (error: any) {
      if (error?.response?.status === 422 && error?.response?.data?.errors) {
        passwordFieldErrors.value = error.response.data.errors
      }
    } finally {
      changingPassword.value = false
    }
  }

  function getInitials (name: string): string {
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
      month: 'long',
      year: 'numeric',
    })
  }
</script>
