<template>
  <SuperAdminLayout current-page="settings">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center">
            <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
              <v-icon size="32">mdi-cog</v-icon>
            </v-avatar>
            <div>
              <h1 class="text-h4 font-weight-bold text-primary mb-1">Paramètres</h1>
              <p class="text-subtitle-1 text-grey-darken-1">
                Configuration de la plateforme logiquali
              </p>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Tabs Content -->
      <v-card class="rounded-lg" elevation="2">
        <v-tabs
          v-model="currentTab"
          align-tabs="start"
          color="primary"
        >
          <v-tab value="general">
            <v-icon start>mdi-cog-outline</v-icon>
            Général
          </v-tab>
          <v-tab value="email">
            <v-icon start>mdi-email-outline</v-icon>
            Email
          </v-tab>
          <v-tab value="security">
            <v-icon start>mdi-shield-lock-outline</v-icon>
            Sécurité
          </v-tab>
          <v-tab value="system">
            <v-icon start>mdi-server</v-icon>
            Système
          </v-tab>
        </v-tabs>

        <v-divider />

        <v-window v-model="currentTab">
          <!-- General Tab -->
          <v-window-item value="general">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card class="mb-6 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Informations de la plateforme</h3>

                    <v-text-field
                      v-model="general.platformName"
                      class="mb-4"
                      density="comfortable"
                      label="Nom de la plateforme"
                      prepend-inner-icon="mdi-application"
                      variant="outlined"
                    />

                    <v-textarea
                      v-model="general.platformDescription"
                      class="mb-4"
                      density="comfortable"
                      label="Description"
                      rows="3"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="general.supportEmail"
                      class="mb-4"
                      density="comfortable"
                      label="Email de support"
                      prepend-inner-icon="mdi-email"
                      type="email"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="general.supportPhone"
                      class="mb-4"
                      density="comfortable"
                      label="Téléphone de support"
                      prepend-inner-icon="mdi-phone"
                      variant="outlined"
                    />

                    <div class="d-flex justify-end">
                      <v-btn color="primary" variant="flat" @click="saveGeneral">
                        <v-icon start>mdi-content-save</v-icon>
                        Enregistrer
                      </v-btn>
                    </div>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Paramètres régionaux</h3>

                    <v-select
                      v-model="general.defaultLanguage"
                      class="mb-4"
                      density="comfortable"
                      :items="languages"
                      label="Langue par défaut"
                      prepend-inner-icon="mdi-translate"
                      variant="outlined"
                    />

                    <v-select
                      v-model="general.defaultCurrency"
                      class="mb-4"
                      density="comfortable"
                      :items="currencies"
                      label="Devise par défaut"
                      prepend-inner-icon="mdi-currency-usd"
                      variant="outlined"
                    />

                    <v-select
                      v-model="general.timezone"
                      class="mb-4"
                      density="comfortable"
                      :items="timezones"
                      label="Fuseau horaire"
                      prepend-inner-icon="mdi-clock-outline"
                      variant="outlined"
                    />

                    <div class="d-flex justify-end">
                      <v-btn color="primary" variant="flat" @click="saveGeneral">
                        <v-icon start>mdi-content-save</v-icon>
                        Enregistrer
                      </v-btn>
                    </div>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card class="bg-primary pa-6 rounded-lg" elevation="0">
                    <v-icon class="mb-4" color="white" size="48">mdi-information-outline</v-icon>
                    <h3 class="text-h6 font-weight-bold text-white mb-3">Information</h3>
                    <p class="text-body-2 text-white" style="opacity: 0.9">
                      Ces paramètres affectent l'ensemble de la plateforme. Assurez-vous de vérifier les changements avant de les enregistrer.
                    </p>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Email Tab -->
          <v-window-item value="email">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card class="mb-6 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Configuration SMTP</h3>

                    <v-text-field
                      v-model="email.smtpHost"
                      class="mb-4"
                      density="comfortable"
                      label="Serveur SMTP"
                      prepend-inner-icon="mdi-server"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="email.smtpPort"
                      class="mb-4"
                      density="comfortable"
                      label="Port SMTP"
                      prepend-inner-icon="mdi-network"
                      type="number"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="email.smtpUser"
                      class="mb-4"
                      density="comfortable"
                      label="Nom d'utilisateur"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="email.smtpPassword"
                      class="mb-4"
                      density="comfortable"
                      label="Mot de passe"
                      prepend-inner-icon="mdi-lock"
                      type="password"
                      variant="outlined"
                    />

                    <v-select
                      v-model="email.encryption"
                      class="mb-4"
                      density="comfortable"
                      :items="['TLS', 'SSL', 'None']"
                      label="Chiffrement"
                      prepend-inner-icon="mdi-shield-lock"
                      variant="outlined"
                    />

                    <div class="d-flex align-center justify-end ga-2">
                      <v-btn color="primary" variant="outlined" @click="testEmail">
                        <v-icon start>mdi-send</v-icon>
                        Tester la connexion
                      </v-btn>
                      <v-btn color="primary" variant="flat" @click="saveEmail">
                        <v-icon start>mdi-content-save</v-icon>
                        Enregistrer
                      </v-btn>
                    </div>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Templates d'emails</h3>

                    <v-list class="bg-transparent" elevation="0">
                      <v-list-item
                        v-for="template in emailTemplates"
                        :key="template.id"
                        class="px-0 mb-3"
                      >
                        <template #prepend>
                          <v-icon color="primary">{{ template.icon }}</v-icon>
                        </template>
                        <v-list-item-title class="font-weight-medium">
                          {{ template.name }}
                        </v-list-item-title>
                        <v-list-item-subtitle>
                          {{ template.description }}
                        </v-list-item-subtitle>
                        <template #append>
                          <v-tooltip location="top" text="Modifier le template">
                            <template #activator="{ props: tooltipProps }">
                              <v-btn
                                v-bind="tooltipProps"
                                icon
                                size="small"
                                variant="text"
                                @click="editTemplate(template)"
                              >
                                <v-icon>mdi-pencil</v-icon>
                              </v-btn>
                            </template>
                          </v-tooltip>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card class="mb-4 bg-success pa-6 rounded-lg" elevation="0">
                    <v-icon class="mb-4" color="white" size="48">mdi-check-circle</v-icon>
                    <h3 class="text-h6 font-weight-bold text-white mb-3">Statut de connexion</h3>
                    <p class="text-body-2 text-white" style="opacity: 0.9">
                      SMTP configuré et opérationnel
                    </p>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-subtitle-1 font-weight-bold mb-4">Statistiques d'envoi</h3>
                    <div class="mb-3">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">Aujourd'hui</span>
                        <span class="text-body-2 font-weight-bold">147</span>
                      </div>
                      <v-divider />
                    </div>
                    <div class="mb-3">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">Ce mois</span>
                        <span class="text-body-2 font-weight-bold">3,421</span>
                      </div>
                      <v-divider />
                    </div>
                    <div>
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">Échecs</span>
                        <span class="text-body-2 font-weight-bold text-error">12</span>
                      </div>
                    </div>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Security Tab -->
          <v-window-item value="security">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card class="mb-6 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Politique de sécurité</h3>

                    <v-switch
                      v-model="security.enforceStrongPassword"
                      class="mb-2"
                      color="primary"
                      label="Exiger des mots de passe forts"
                    />

                    <v-switch
                      v-model="security.enable2FA"
                      class="mb-2"
                      color="primary"
                      label="Activer l'authentification à deux facteurs (2FA)"
                    />

                    <v-switch
                      v-model="security.sessionTimeout"
                      class="mb-4"
                      color="primary"
                      label="Expiration automatique des sessions"
                    />

                    <v-text-field
                      v-if="security.sessionTimeout"
                      v-model="security.sessionTimeoutMinutes"
                      class="mb-4"
                      density="comfortable"
                      label="Durée d'inactivité (minutes)"
                      type="number"
                      variant="outlined"
                    />

                    <v-text-field
                      v-model="security.maxLoginAttempts"
                      class="mb-4"
                      density="comfortable"
                      label="Nombre maximum de tentatives de connexion"
                      type="number"
                      variant="outlined"
                    />

                    <div class="d-flex justify-end">
                      <v-btn color="primary" variant="flat" @click="saveSecurity">
                        <v-icon start>mdi-content-save</v-icon>
                        Enregistrer
                      </v-btn>
                    </div>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Gestion des accès</h3>

                    <v-list class="bg-transparent" elevation="0">
                      <v-list-item
                        v-for="permission in permissions"
                        :key="permission.id"
                        class="px-0"
                      >
                        <template #prepend>
                          <v-checkbox
                            v-model="permission.enabled"
                            color="primary"
                            hide-details
                          />
                        </template>
                        <v-list-item-title class="font-weight-medium">
                          {{ permission.name }}
                        </v-list-item-title>
                        <v-list-item-subtitle>
                          {{ permission.description }}
                        </v-list-item-subtitle>
                      </v-list-item>
                    </v-list>

                    <div class="d-flex justify-end">
                      <v-btn class="mt-4" color="primary" variant="flat" @click="savePermissions">
                        <v-icon start>mdi-content-save</v-icon>
                        Enregistrer les permissions
                      </v-btn>
                    </div>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card class="mb-4 bg-warning pa-6 rounded-lg" elevation="0">
                    <v-icon class="mb-4" color="white" size="48">mdi-shield-alert</v-icon>
                    <h3 class="text-h6 font-weight-bold text-white mb-3">Alertes de sécurité</h3>
                    <p class="text-body-2 text-white" style="opacity: 0.9">
                      3 tentatives de connexion échouées détectées aujourd'hui
                    </p>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-subtitle-1 font-weight-bold mb-4">Dernières activités suspectes</h3>
                    <v-timeline density="compact" side="end">
                      <v-timeline-item
                        v-for="activity in suspiciousActivities"
                        :key="activity.id"
                        dot-color="error"
                        size="small"
                      >
                        <div class="mb-3">
                          <p class="text-caption text-medium-emphasis mb-1">{{ activity.time }}</p>
                          <p class="text-body-2 font-weight-medium">{{ activity.description }}</p>
                        </div>
                      </v-timeline-item>
                    </v-timeline>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- System Tab -->
          <v-window-item value="system">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card class="mb-6 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Informations système</h3>

                    <v-list class="bg-transparent" elevation="0">
                      <v-list-item class="px-0">
                        <v-list-item-title class="font-weight-medium">Version de la plateforme</v-list-item-title>
                        <v-list-item-subtitle>{{ systemInfo.version }}</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item class="px-0">
                        <v-list-item-title class="font-weight-medium">Base de données</v-list-item-title>
                        <v-list-item-subtitle>{{ systemInfo.database }}</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item class="px-0">
                        <v-list-item-title class="font-weight-medium">Serveur</v-list-item-title>
                        <v-list-item-subtitle>{{ systemInfo.server }}</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item class="px-0">
                        <v-list-item-title class="font-weight-medium">Dernière sauvegarde</v-list-item-title>
                        <v-list-item-subtitle>{{ systemInfo.lastBackup }}</v-list-item-subtitle>
                      </v-list-item>
                    </v-list>
                  </v-card>

                  <v-card class="mb-6 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-h6 font-weight-bold mb-4">Maintenance</h3>

                    <v-switch
                      v-model="system.maintenanceMode"
                      class="mb-4"
                      color="warning"
                      label="Mode maintenance"
                    />

                    <v-textarea
                      v-if="system.maintenanceMode"
                      v-model="system.maintenanceMessage"
                      class="mb-4"
                      density="comfortable"
                      label="Message de maintenance"
                      rows="3"
                      variant="outlined"
                    />

                    <div class="d-flex align-center justify-end ga-2">
                      <v-btn color="primary" variant="outlined" @click="clearCache">
                        <v-icon start>mdi-cached</v-icon>
                        Vider le cache
                      </v-btn>
                      <v-btn color="success" variant="outlined" @click="backupDatabase">
                        <v-icon start>mdi-database-export</v-icon>
                        Sauvegarder la base
                      </v-btn>
                    </div>
                  </v-card>

                  <!-- System Logs -->
                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <div class="d-flex align-center justify-space-between mb-4">
                      <h3 class="text-h6 font-weight-bold">Logs système</h3>
                      <v-tooltip location="top" text="Actualiser">
                        <template #activator="{ props: tooltipProps }">
                          <v-btn
                            v-bind="tooltipProps"
                            prepend-icon="mdi-refresh"
                            size="small"
                            variant="outlined"
                            @click="refreshLogs"
                          >
                            Actualiser
                          </v-btn>
                        </template>
                      </v-tooltip>
                    </div>

                    <v-data-table
                      class="rounded-lg"
                      elevation="0"
                      :headers="logHeaders"
                      :items="systemLogs"
                      :items-per-page="5"
                    >
                      <template #item.type="{ item }">
                        <v-chip
                          :color="getLogColor(item.type)"
                          size="x-small"
                          variant="tonal"
                        >
                          {{ item.type }}
                        </v-chip>
                      </template>
                      <template #item.timestamp="{ item }">
                        <span class="text-caption">{{ item.timestamp }}</span>
                      </template>
                    </v-data-table>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card class="mb-4 bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-subtitle-1 font-weight-bold mb-4">Performance</h3>

                    <div class="mb-4">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">CPU</span>
                        <span class="text-body-2 font-weight-bold">24%</span>
                      </div>
                      <v-progress-linear
                        color="success"
                        height="8"
                        model-value="24"
                        rounded
                      />
                    </div>

                    <div class="mb-4">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">Mémoire</span>
                        <span class="text-body-2 font-weight-bold">67%</span>
                      </div>
                      <v-progress-linear
                        color="warning"
                        height="8"
                        model-value="67"
                        rounded
                      />
                    </div>

                    <div>
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-body-2 text-medium-emphasis">Disque</span>
                        <span class="text-body-2 font-weight-bold">45%</span>
                      </div>
                      <v-progress-linear
                        color="primary"
                        height="8"
                        model-value="45"
                        rounded
                      />
                    </div>
                  </v-card>

                  <v-card class="bg-surface-variant pa-6 rounded-lg" elevation="0">
                    <h3 class="text-subtitle-1 font-weight-bold mb-4">Utilisateurs actifs</h3>
                    <div class="text-center">
                      <p class="text-h2 font-weight-bold text-primary mb-2">42</p>
                      <p class="text-caption text-medium-emphasis">En ce moment</p>
                    </div>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>
    </v-container>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
      {{ snackbarMessage }}
    </v-snackbar>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import superAdminService from '@/services/superAdminService'
  import SuperAdminLayout from '../../components/SuperAdminLayout.vue'

  const currentTab = ref('general')
  const snackbar = ref(false)
  const snackbarMessage = ref('')
  const snackbarColor = ref('success')
  const toast = useToast()

  const general = ref({
    platformName: 'logiquali',
    platformDescription: 'Plateforme de gestion de la qualité et des normes ISO',
    supportEmail: 'support@logiquali.com',
    supportPhone: '+237 690 000 000',
    defaultLanguage: 'Français',
    defaultCurrency: 'FCFA',
    timezone: 'Africa/Douala',
  })

  const email = ref({
    smtpHost: 'smtp.gmail.com',
    smtpPort: 587,
    smtpUser: 'noreply@logiquali.com',
    smtpPassword: '••••••••',
    encryption: 'TLS',
  })

  const security = ref({
    enforceStrongPassword: true,
    enable2FA: true,
    sessionTimeout: true,
    sessionTimeoutMinutes: 30,
    maxLoginAttempts: 5,
  })

  const system = ref({
    maintenanceMode: false,
    maintenanceMessage: 'Le système est en maintenance. Veuillez réessayer plus tard.',
  })

  const systemInfo = ref({
    version: 'v2.4.1',
    database: 'PostgreSQL 14.5',
    server: 'Ubuntu 22.04 LTS',
    lastBackup: '15 Déc 2024, 02:00',
  })

  const languages = ['Français', 'English', 'Español']
  const currencies = ['FCFA', 'EUR', 'USD']
  const timezones = ['Africa/Douala', 'Africa/Lagos', 'Europe/Paris', 'America/New_York']

  const emailTemplates = [
    { id: 1, name: 'Bienvenue', description: 'Email envoyé aux nouveaux utilisateurs', icon: 'mdi-email-plus' },
    { id: 2, name: 'Réinitialisation mot de passe', description: 'Email de réinitialisation', icon: 'mdi-lock-reset' },
    { id: 3, name: 'Nouveau KYC', description: 'Notification de nouveau KYC', icon: 'mdi-account-check' },
    { id: 4, name: 'Paiement confirmé', description: 'Confirmation de paiement', icon: 'mdi-cash-check' },
  ]

  const permissions = ref([
    { id: 1, name: 'Gestion des utilisateurs', description: 'Créer, modifier et supprimer des utilisateurs', enabled: true },
    { id: 2, name: 'Gestion des entreprises', description: 'Gérer les entreprises et leurs abonnements', enabled: true },
    { id: 3, name: 'Gestion des offres', description: 'Créer et modifier les offres', enabled: true },
    { id: 4, name: 'Validation KYC', description: 'Valider les demandes KYC', enabled: true },
    { id: 5, name: 'Accès aux logs', description: 'Consulter les logs système', enabled: false },
  ])

  const suspiciousActivities = ref([
    { id: 1, time: 'Il y a 2h', description: 'Tentative de connexion échouée depuis 41.202.xxx.xxx' },
    { id: 2, time: 'Il y a 5h', description: 'Accès non autorisé bloqué' },
    { id: 3, time: 'Hier', description: 'Tentative de force brute détectée' },
  ])

  const logHeaders = [
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Message', key: 'message', sortable: false },
    { title: 'Date', key: 'timestamp', sortable: true },
  ]

  const systemLogs = ref([
    { type: 'INFO', message: 'Nouvelle entreprise enregistrée: SOMDIAA', timestamp: '2024-12-15 14:32' },
    { type: 'SUCCESS', message: 'Sauvegarde de la base de données terminée', timestamp: '2024-12-15 02:00' },
    { type: 'WARNING', message: 'Espace disque faible (25% restant)', timestamp: '2024-12-14 18:45' },
    { type: 'ERROR', message: 'Échec de l\'envoi d\'email à client@example.com', timestamp: '2024-12-14 12:15' },
    { type: 'INFO', message: 'Nouveau paiement reçu: 450,000 FCFA', timestamp: '2024-12-14 10:20' },
  ])

  async function loadSettings () {
    try {
      const data = await superAdminService.getSettings()
      general.value = { ...general.value, ...data.general }
      email.value = { ...email.value, ...data.email }
      security.value = { ...security.value, ...data.security }
      system.value = { ...system.value, ...data.system }
      if (data.system_info) {
        systemInfo.value = { ...systemInfo.value, ...data.system_info }
      }
    } catch {}
  }

  async function saveGeneral () {
    try {
      await superAdminService.updateSettings({ general: general.value })
      showSnackbar('Paramètres généraux enregistrés', 'success')
    } catch {}
  }

  async function saveEmail () {
    try {
      await superAdminService.updateSettings({ email: email.value })
      showSnackbar('Configuration email enregistrée', 'success')
    } catch {}
  }

  async function testEmail () {
    try {
      const response = await superAdminService.testEmailSettings()
      showSnackbar(response.message || 'Email de test envoyé', 'success')
    } catch {}
  }

  function editTemplate (template: any) {
    console.log('Éditer template:', template.name)
  }

  async function saveSecurity () {
    try {
      await superAdminService.updateSettings({ security: security.value })
      showSnackbar('Paramètres de sécurité enregistrés', 'success')
    } catch {}
  }

  function savePermissions () {
    showSnackbar('Permissions enregistrées', 'success')
  }

  function clearCache () {
    showSnackbar('Cache vidé', 'success')
  }

  function backupDatabase () {
    showSnackbar('Sauvegarde de la base de données en cours...', 'info')
  }

  function refreshLogs () {
    showSnackbar('Logs actualisés', 'success')
  }

  function getLogColor (type: string) {
    const colors: Record<string, string> = {
      INFO: 'info',
      SUCCESS: 'success',
      WARNING: 'warning',
      ERROR: 'error',
    }
    return colors[type] || 'grey'
  }

  function showSnackbar (message: string, color: string) {
    snackbarMessage.value = message
    snackbarColor.value = color
    snackbar.value = true
  }

  onMounted(() => {
    loadSettings()
  })
</script>

<style scoped>
.v-card {
  transition: all 0.3s ease;
}

.v-btn {
  text-transform: none;
}
</style>
