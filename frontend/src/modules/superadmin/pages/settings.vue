<template>
  <SuperAdminLayout current-page="settings">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-h4 font-weight-bold text-primary mb-2">Paramètres</h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Configurez les paramètres de la plateforme
        </p>
      </div>

      <v-row>
        <v-col cols="12" lg="8">
          <!-- General Settings -->
          <v-card
            class="mb-6"
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <v-icon class="mr-3" color="primary">mdi-cog-outline</v-icon>
              <span class="text-h6 font-weight-bold">Paramètres Généraux</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="settings.platform_name"
                    label="Nom de la plateforme"
                    prepend-inner-icon="mdi-application-outline"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="settings.support_email"
                    label="Email de support"
                    prepend-inner-icon="mdi-email-outline"
                    type="email"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="settings.welcome_message"
                    label="Message de bienvenue"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Notifications -->
          <v-card
            class="mb-6"
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <v-icon class="mr-3" color="primary">mdi-bell-outline</v-icon>
              <span class="text-h6 font-weight-bold">Notifications</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-0">
              <v-list>
                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-email-outline</v-icon>
                  </template>
                  <v-list-item-title>Notifications par email</v-list-item-title>
                  <v-list-item-subtitle>
                    Recevoir des emails pour les événements importants
                  </v-list-item-subtitle>
                  <template #append>
                    <v-switch
                      v-model="settings.email_notifications"
                      color="primary"
                      hide-details
                    />
                  </template>
                </v-list-item>

                <v-divider />

                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-account-multiple-check-outline</v-icon>
                  </template>
                  <v-list-item-title>Nouvelles inscriptions</v-list-item-title>
                  <v-list-item-subtitle>
                    Notification lors de nouvelles inscriptions entreprises
                  </v-list-item-subtitle>
                  <template #append>
                    <v-switch
                      v-model="settings.notify_new_registrations"
                      color="primary"
                      hide-details
                    />
                  </template>
                </v-list-item>

                <v-divider />

                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-clock-alert-outline</v-icon>
                  </template>
                  <v-list-item-title>Abonnements expirés</v-list-item-title>
                  <v-list-item-subtitle>
                    Notification pour les abonnements proches de l'expiration
                  </v-list-item-subtitle>
                  <template #append>
                    <v-switch
                      v-model="settings.notify_expiring_subscriptions"
                      color="primary"
                      hide-details
                    />
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Appearance -->
          <v-card
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <v-icon class="mr-3" color="primary">mdi-palette-outline</v-icon>
              <span class="text-h6 font-weight-bold">Apparence</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="settings.theme"
                    :items="themeOptions"
                    label="Thème"
                    prepend-inner-icon="mdi-theme-light-dark"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="settings.language"
                    :items="languageOptions"
                    label="Langue"
                    prepend-inner-icon="mdi-translate"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Quick Actions -->
        <v-col cols="12" lg="4">
          <v-card
            class="mb-6"
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <span class="text-h6 font-weight-bold">Actions Rapides</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-4">
              <v-btn
                block
                class="mb-3"
                :loading="saving"
                prepend-icon="mdi-content-save-outline"
                style="border-radius: 12px"
                variant="outlined"
                @click="handleSave"
              >
                Enregistrer les paramètres
              </v-btn>

              <v-btn
                block
                class="mb-3"
                prepend-icon="mdi-restore"
                style="border-radius: 12px"
                variant="outlined"
                @click="handleReset"
              >
                Réinitialiser
              </v-btn>

              <v-btn
                block
                color="error"
                prepend-icon="mdi-database-refresh-outline"
                style="border-radius: 12px"
                variant="outlined"
                @click="clearCacheDialog = true"
              >
                Vider le cache
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- Info -->
          <v-card
            elevation="2"
          >
            <v-card-title class="pa-6 pb-4">
              <span class="text-h6 font-weight-bold">Informations Système</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <div class="mb-4">
                <p class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">Version</p>
                <p class="text-body-1 font-weight-medium">1.0.0</p>
              </div>

              <div class="mb-4">
                <p class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">Dernière mise à jour</p>
                <p class="text-body-1 font-weight-medium">23 Janvier 2026</p>
              </div>

              <div>
                <p class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">Environnement</p>
                <v-chip color="success" size="x-small" variant="tonal">Production</v-chip>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Clear Cache Dialog -->
    <v-dialog v-model="clearCacheDialog" max-width="500">
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4 d-flex align-center">
          <v-icon class="mr-3" color="warning" size="32">mdi-alert-outline</v-icon>
          <span class="text-h6 font-weight-bold">Vider le cache</span>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <p class="text-body-1">
            Êtes-vous sûr de vouloir vider le cache système ?
          </p>
          <p class="text-body-2 mt-2" style="color: rgb(var(--v-theme-on-surface-variant))">
            Cette action peut temporairement ralentir la plateforme.
          </p>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn variant="text" @click="clearCacheDialog = false">
            Annuler
          </v-btn>
          <v-btn color="warning" variant="flat" @click="handleClearCache">
            Vider le cache
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useToast } from '@/composables/useToast'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'

  const toast = useToast()

  const saving = ref(false)
  const clearCacheDialog = ref(false)

  const settings = ref({
    platform_name: 'BestQHSE',
    support_email: 'support@BestQHSE.com',
    welcome_message: 'Bienvenue sur la plateforme de gestion qualité BestQHSE',
    email_notifications: true,
    notify_new_registrations: true,
    notify_expiring_subscriptions: true,
    theme: 'system',
    language: 'fr',
  })

  const themeOptions = [
    { title: 'Clair', value: 'light' },
    { title: 'Sombre', value: 'dark' },
    { title: 'Système', value: 'system' },
  ]

  const languageOptions = [
    { title: 'Français', value: 'fr' },
    { title: 'English', value: 'en' },
  ]

  async function handleSave () {
    saving.value = true
    try {
      // API call to save settings
      await new Promise(resolve => setTimeout(resolve, 1000))
      toast.success('Paramètres enregistrés')
    } catch {
      toast.error('Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  function handleReset () {
    settings.value = {
      platform_name: 'BestQHSE',
      support_email: 'support@BestQHSE.com',
      welcome_message: 'Bienvenue sur la plateforme de gestion qualité BestQHSE',
      email_notifications: true,
      notify_new_registrations: true,
      notify_expiring_subscriptions: true,
      theme: 'system',
      language: 'fr',
    }
    toast.info('Paramètres réinitialisés')
  }

  async function handleClearCache () {
    try {
      // API call to clear cache
      await new Promise(resolve => setTimeout(resolve, 1000))
      clearCacheDialog.value = false
      toast.success('Cache vidé')
    } catch {
      toast.error('Erreur lors du vidage du cache')
    }
  }
</script>
