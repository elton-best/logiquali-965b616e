<template>
  <SuperAdminLayout current-page="companies">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row v-if="!loading && company" class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center justify-space-between flex-wrap ga-4">
            <div class="d-flex align-center flex-grow-1">
              <v-tooltip location="top" text="Retour">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    class="mr-4"
                    icon
                    variant="text"
                    @click="$router.back()"
                  >
                    <v-icon>mdi-arrow-left</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
                <v-icon size="32">mdi-office-building-outline</v-icon>
              </v-avatar>

              <div class="flex-grow-1">
                <h1 class="text-h4 font-weight-bold text-primary mb-1">{{ company.name }}</h1>
                <p class="text-subtitle-1 text-grey-darken-1">{{ company.rccm }} • Créé le {{ formatDate(company.createdAt) }}</p>
              </div>

              <v-chip
                class="mr-4"
                :color="getEnterpriseStatusColor(company.status)"
                size="x-small"
                variant="tonal"
              >
                <v-icon start>{{ getEnterpriseStatusIcon(company.status) }}</v-icon>
                {{ getEnterpriseStatusLabel(company.status) }}
              </v-chip>
            </div>

            <div class="d-flex align-center ga-2 flex-wrap">
              <v-btn
                color="primary"
                prepend-icon="mdi-shield-account-outline"
                variant="outlined"
                @click="goToKycDetail"
              >
                Dossier KYC
              </v-btn>
              <v-btn
                :color="company.status === 'active' ? 'error' : 'success'"
                :prepend-icon="company.status === 'active' ? 'mdi-pause-circle-outline' : 'mdi-check-circle-outline'"
                variant="outlined"
                @click="toggleStatus"
              >
                {{ company.status === 'active' ? 'Suspendre' : 'Réactiver' }}
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-history"
                variant="outlined"
                @click="currentTab = 'history'"
              >
                Voir historique
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row v-else-if="loading">
        <v-col cols="12">
          <v-card class="rounded-lg pa-6" elevation="2">
            <div class="d-flex align-center mb-4">
              <v-progress-circular class="mr-3" color="primary" indeterminate />
              <span>Chargement des données entreprise...</span>
            </div>
            <v-skeleton-loader type="article, table, list-item-three-line@3" />
          </v-card>
        </v-col>
      </v-row>

      <v-row v-else-if="error">
        <v-col cols="12">
          <EmptyState
            :description="error"
            icon="mdi-alert-circle-outline"
            title="Erreur de chargement"
          />
        </v-col>
      </v-row>

      <v-row v-else-if="company">
        <!-- Main Content -->
        <v-col cols="12" lg="9">
          <v-card class="rounded-lg" elevation="2">
            <v-tabs
              v-model="currentTab"
              align-tabs="start"
              color="primary"
            >
              <v-tab value="overview">
                <v-icon start>mdi-view-dashboard-outline</v-icon>
                Vue d'ensemble
              </v-tab>
              <v-tab value="subscriptions">
                <v-icon start>mdi-cash-multiple</v-icon>
                Abonnements
              </v-tab>
              <v-tab value="users">
                <v-icon start>mdi-account-group-outline</v-icon>
                Utilisateurs
              </v-tab>
              <v-tab value="history">
                <v-icon start>mdi-timeline-clock-outline</v-icon>
                Historique
              </v-tab>
            </v-tabs>

            <v-window v-model="currentTab">
              <!-- Vue d'ensemble -->
              <v-window-item value="overview">
                <v-card-text class="pa-6">
                  <!-- Company Info -->
                  <v-card class="mb-6 rounded-lg border" elevation="2">
                    <v-card-title class="d-flex align-center pa-4">
                      <v-icon class="mr-2" color="primary">mdi-domain</v-icon>
                      Informations Entreprise
                    </v-card-title>
                    <v-divider />
                    <v-card-text class="pa-6">
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-1">Raison sociale</p>
                            <p class="text-body-1 font-weight-medium">{{ company.name }}</p>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-1">RCCM</p>
                            <p class="text-body-1 font-weight-medium">{{ company.rccm }}</p>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-1">Secteur d'activité</p>
                            <p class="text-body-1 font-weight-medium">{{ company.sector }}</p>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-1">Pays</p>
                            <p class="text-body-1 font-weight-medium">{{ company.country }}</p>
                          </div>
                        </v-col>
                        <v-col cols="12">
                          <div>
                            <p class="text-caption text-medium-emphasis mb-1">Adresse</p>
                            <p class="text-body-1 font-weight-medium">{{ company.address }}</p>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>

                  <!-- Sites -->
                  <v-card class="mb-6 rounded-lg border" elevation="2">
                    <v-card-title class="d-flex align-center justify-space-between pa-4">
                      <div class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-map-marker-multiple-outline</v-icon>
                        Sites
                      </div>
                      <v-chip color="primary" size="x-small" variant="tonal">
                        {{ company.sites.length }} site(s)
                      </v-chip>
                    </v-card-title>
                    <v-divider />
                    <v-card-text class="pa-6">
                      <v-row>
                        <v-col
                          v-for="site in company.sites"
                          :key="site.id"
                          cols="12"
                          md="4"
                        >
                          <v-card class="border rounded-lg" elevation="2">
                            <v-card-text class="pa-4">
                              <div class="d-flex align-center mb-3">
                                <v-icon class="mr-2" color="primary">mdi-factory</v-icon>
                                <span class="font-weight-bold">{{ site.name }}</span>
                              </div>
                              <div class="mb-2">
                                <v-icon class="mr-1" size="small">mdi-map-marker-outline</v-icon>
                                <span class="text-body-2">{{ site.location }}</span>
                              </div>
                              <div class="mb-2">
                                <v-icon class="mr-1" size="small">mdi-account-multiple-outline</v-icon>
                                <span class="text-body-2">{{ site.users }} utilisateurs</span>
                              </div>
                              <div>
                                <v-chip :color="site.status === 'active' ? 'success' : 'grey'" size="x-small" variant="tonal">
                                  {{ site.status === 'active' ? 'Actif' : 'Inactif' }}
                                </v-chip>
                              </div>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>

                  <!-- Statistics -->
                  <v-card class="rounded-lg border" elevation="2">
                    <v-card-title class="d-flex align-center pa-4">
                      <v-icon class="mr-2" color="primary">mdi-chart-box-outline</v-icon>
                      Statistiques
                    </v-card-title>
                    <v-divider />
                    <v-card-text class="pa-6">
                      <v-row>
                        <v-col
                          v-for="stat in statistics"
                          :key="stat.label"
                          cols="12"
                          md="3"
                          sm="6"
                        >
                          <v-card class="rounded-lg" :color="stat.color" elevation="2">
                            <v-card-text class="pa-4 text-center">
                              <v-icon class="mb-2" :color="stat.iconColor" size="32">
                                {{ stat.icon }}
                              </v-icon>
                              <h3 class="text-h4 font-weight-bold mb-1">{{ stat.value }}</h3>
                              <p class="text-body-2 text-medium-emphasis">{{ stat.label }}</p>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-card-text>
              </v-window-item>

              <!-- Abonnements -->
              <v-window-item value="subscriptions">
                <v-card-text class="pa-6">
                  <v-card
                    v-for="site in company.sites"
                    :key="site.id"
                    class="mb-4 rounded-lg border"
                    elevation="2"
                  >
                    <v-card-title class="d-flex align-center pa-4">
                      <v-icon class="mr-2" color="primary">mdi-factory</v-icon>
                      {{ site.name }}
                    </v-card-title>
                    <v-divider />
                    <v-card-text class="pa-0">
                      <v-table class="sa-table" hover>
                        <thead>
                          <tr>
                            <th>Module</th>
                            <th>Statut</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Montant</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="sub in getSubscriptionsBySite(site.id)" :key="sub.id">
                            <td>
                              <div class="d-flex align-center">
                                <v-icon class="mr-2" size="small">{{ sub.icon }}</v-icon>
                                <span class="font-weight-medium">{{ sub.module }}</span>
                              </div>
                            </td>
                            <td>
                              <v-chip
                                :color="getSubscriptionStatusColor(sub.status)"
                                size="x-small"
                                variant="tonal"
                              >
                                {{ sub.status }}
                              </v-chip>
                            </td>
                            <td>{{ formatDate(sub.startDate) }}</td>
                            <td>{{ formatDate(sub.endDate) }}</td>
                            <td class="font-weight-bold">{{ formatCurrency(sub.amount) }}</td>
                            <td>
                              <v-tooltip location="top" text="Voir l'abonnement">
                                <template #activator="{ props: tooltipProps }">
                                  <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    @click="viewSubscription(sub)"
                                  >
                                    <v-icon size="small">mdi-eye-outline</v-icon>
                                  </v-btn>
                                </template>
                              </v-tooltip>
                            </td>
                          </tr>
                          <tr v-if="getSubscriptionsBySite(site.id).length === 0">
                            <td colspan="6">
                              <EmptyState
                                description="Aucun abonnement pour ce site."
                                icon="mdi-credit-card-outline"
                                title="Aucun abonnement"
                              />
                            </td>
                          </tr>
                        </tbody>
                      </v-table>
                    </v-card-text>
                  </v-card>
                </v-card-text>
              </v-window-item>

              <!-- Utilisateurs -->
              <v-window-item value="users">
                <v-card-text class="pa-6">
                  <v-card class="rounded-lg border" elevation="2">
                    <v-card-text class="pa-0">
                      <v-table class="sa-table" hover>
                        <thead>
                          <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Site</th>
                            <th>Statut</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="user in users" :key="user.id">
                            <td>
                              <div class="d-flex align-center">
                                <v-avatar class="mr-2" color="primary" size="32" variant="tonal">
                                  <span class="text-caption">{{ user.initials }}</span>
                                </v-avatar>
                                <span class="font-weight-medium">{{ user.name }}</span>
                              </div>
                            </td>
                            <td>{{ user.email }}</td>
                            <td>
                              <v-chip color="primary" size="x-small" variant="tonal">
                                {{ user.role }}
                              </v-chip>
                            </td>
                            <td>{{ user.site }}</td>
                            <td>
                              <v-chip
                                :color="user.status === 'active' ? 'success' : 'grey'"
                                size="x-small"
                                variant="tonal"
                              >
                                {{ user.status === 'active' ? 'Actif' : 'Inactif' }}
                              </v-chip>
                            </td>
                            <td>{{ formatDateTime(user.lastLogin) }}</td>
                            <td>
                              <v-tooltip location="top" text="Voir l'utilisateur">
                                <template #activator="{ props: tooltipProps }">
                                  <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    @click="viewUser(user)"
                                  >
                                    <v-icon size="small">mdi-eye-outline</v-icon>
                                  </v-btn>
                                </template>
                              </v-tooltip>
                            </td>
                          </tr>
                          <tr v-if="users.length === 0">
                            <td colspan="7">
                              <EmptyState
                                description="Aucun utilisateur trouvé."
                                icon="mdi-account-outline"
                                title="Aucun utilisateur"
                              />
                            </td>
                          </tr>
                        </tbody>
                      </v-table>
                    </v-card-text>
                  </v-card>
                </v-card-text>
              </v-window-item>

              <!-- Historique -->
              <v-window-item value="history">
                <v-card-text class="pa-6">
                  <v-timeline density="compact" side="end">
                    <v-timeline-item v-for="event in history" :key="event.id" :dot-color="event.color" size="small">
                      <template #icon>
                        <v-icon size="small">{{ event.icon }}</v-icon>
                      </template>
                      <v-card class="rounded-lg border" elevation="2">
                        <v-card-text class="pa-4">
                          <div class="d-flex justify-space-between align-start mb-2">
                            <h4 class="font-weight-medium">{{ event.title }}</h4>
                            <span class="text-caption text-medium-emphasis">
                              {{ formatDateTime(event.date) }}
                            </span>
                          </div>
                          <p class="text-body-2 text-medium-emphasis mb-2">
                            {{ event.description }}
                          </p>
                          <div class="d-flex align-center">
                            <v-avatar class="mr-2" color="grey-lighten-2" size="24">
                              <span class="text-caption">{{ event.userInitials }}</span>
                            </v-avatar>
                            <span class="text-caption">{{ event.userName }}</span>
                          </div>
                        </v-card-text>
                      </v-card>
                    </v-timeline-item>
                    <div v-if="history.length === 0">
                      <EmptyState
                        description="Aucun événement disponible."
                        icon="mdi-calendar-outline"
                        title="Aucun événement"
                      />
                    </div>
                  </v-timeline>
                </v-card-text>
              </v-window-item>
            </v-window>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="3">
          <!-- Quick Info -->
          <v-card class="mb-4 rounded-lg border" elevation="2">
            <v-card-title class="pa-4">
              <v-icon class="mr-2" color="primary" size="small">mdi-information-outline</v-icon>
              Informations rapides
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <div class="mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Sites actifs</p>
                <p class="text-h6 font-weight-bold">{{ company.sites.length }}</p>
              </div>
              <div class="mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Abonnements actifs</p>
                <p class="text-h6 font-weight-bold">{{ activeSubscriptionsCount }}</p>
              </div>
              <div class="mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Utilisateurs</p>
                <p class="text-h6 font-weight-bold">{{ users.length }}</p>
              </div>
              <div>
                <p class="text-caption text-medium-emphasis mb-1">CA annuel</p>
                <p class="text-h6 font-weight-bold">{{ formatCurrency(totalRevenue) }}</p>
              </div>
            </v-card-text>
          </v-card>

          <!-- Contact -->
          <v-card class="mb-4 rounded-lg border" elevation="2">
            <v-card-title class="pa-4">
              <v-icon class="mr-2" color="primary" size="small">mdi-card-account-phone-outline</v-icon>
              Contact
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <div class="mb-3">
                <p class="text-caption text-medium-emphasis mb-1">Administrateur</p>
                <p class="text-body-2 font-weight-medium">{{ company.adminName }}</p>
              </div>
              <div class="mb-3">
                <div class="d-flex align-center">
                  <v-icon class="mr-2" size="small">mdi-email-outline</v-icon>
                  <a class="text-body-2" :href="`mailto:${company.email}`">
                    {{ company.email }}
                  </a>
                </div>
              </div>
              <div>
                <div class="d-flex align-center">
                  <v-icon class="mr-2" size="small">mdi-phone-outline</v-icon>
                  <a class="text-body-2" :href="`tel:${company.phone}`">
                    {{ company.phone }}
                  </a>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Important Dates -->
          <v-card class="rounded-lg border" elevation="2">
            <v-card-title class="pa-4">
              <v-icon class="mr-2" color="primary" size="small">mdi-calendar-clock-outline</v-icon>
              Dates importantes
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <div class="mb-3">
                <p class="text-caption text-medium-emphasis mb-1">Inscription</p>
                <p class="text-body-2 font-weight-medium">{{ formatDate(company.createdAt) }}</p>
              </div>
              <div class="mb-3">
                <p class="text-caption text-medium-emphasis mb-1">Dernier paiement</p>
                <p class="text-body-2 font-weight-medium">{{ formatDate(company.lastPayment) }}</p>
              </div>
              <div>
                <p class="text-caption text-medium-emphasis mb-1">Prochain renouvellement</p>
                <p class="text-body-2 font-weight-medium text-warning">
                  {{ formatDate(company.nextRenewal) }}
                </p>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row v-else>
        <v-col cols="12">
          <EmptyState
            description="L'entreprise demandée est introuvable ou a été supprimée."
            icon="mdi-office-building-outline"
            title="Entreprise introuvable"
          />
        </v-col>
      </v-row>

      <StatusDialog
        v-model="suspendDialog"
        v-model:reason="suspendReason"
        confirm-color="warning"
        confirm-label="Suspendre"
        icon="mdi-pause-circle-outline"
        :loading="togglingStatus"
        :message="`Merci d’indiquer la raison de suspension de <strong>${enterprise?.name ?? ''}</strong>.`"
        :reason-error="suspendReasonError"
        reason-label="Raison de la suspension *"
        reason-placeholder="Indiquez la raison de la suspension..."
        show-reason
        title="Suspendre l'entreprise"
        @cancel="suspendDialog = false"
        @confirm="confirmSuspend"
      />

      <StatusDialog
        v-model="reactivateDialog"
        confirm-color="success"
        confirm-label="Réactiver"
        icon="mdi-check-circle-outline"
        :loading="togglingStatus"
        :message="`Confirmez la réactivation de <strong>${enterprise?.name ?? ''}</strong>.`"
        title="Réactiver l'entreprise"
        @cancel="reactivateDialog = false"
        @confirm="confirmReactivate"
      />
    </v-container>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise, Subscription } from '@/types/api'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import StatusDialog from '@/modules/superadmin/components/StatusDialog.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const currentTab = ref('overview')
  const loading = ref(true)
  const error = ref<string | null>(null)
  const togglingStatus = ref(false)
  const suspendDialog = ref(false)
  const reactivateDialog = ref(false)
  const suspendReason = ref('')
  const suspendReasonError = ref('')
  const { run: runLocked } = useActionLock()

  type DisplaySubscription = {
    id: number
    siteId: number
    module: string
    icon: string
    status: 'Actif' | 'Inactif' | 'Essai' | 'Expiré'
    startDate: string
    endDate: string
    amount: number
  }

  type DisplaySite = {
    id: number
    name: string
    location: string
    users: number
    status: 'active' | 'inactive'
  }

  const enterprise = ref<Enterprise | null>(null)
  const subscriptions = ref<DisplaySubscription[]>([])

  const statistics = computed(() => [
    {
      label: 'Abonnements actifs',
      value: activeSubscriptionsCount.value,
      icon: 'mdi-cash-multiple',
      color: 'success',
      iconColor: 'success',
    },
    {
      label: 'Utilisateurs',
      value: users.value.length,
      icon: 'mdi-account-group-outline',
      color: 'info',
      iconColor: 'info',
    },
    {
      label: 'Sites',
      value: company.value?.sites.length ?? 0,
      icon: 'mdi-map-marker-multiple-outline',
      color: 'primary',
      iconColor: 'primary',
    },
    {
      label: 'CA Annuel',
      value: `${(totalRevenue.value / 1_000_000).toFixed(1)}M`,
      icon: 'mdi-chart-line',
      color: 'warning',
      iconColor: 'warning',
    },
  ])

  const activeSubscriptionsCount = computed(() => {
    return subscriptions.value.filter(sub => sub.status === 'Actif').length
  })

  const totalRevenue = computed(() => {
    return subscriptions.value.reduce((sum, sub) => sum + sub.amount, 0)
  })

  const users = computed(() => {
    const companyUsers = (enterprise.value?.users ?? []) as any[]
    const sitesById = new Map((enterprise.value?.sites ?? []).map(site => [site.id, site.name]))

    return companyUsers.map(user => {
      const fullName = user.name || [user.first_name, user.last_name].filter(Boolean).join(' ') || 'Utilisateur'
      const initials = fullName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part: string) => part[0]?.toUpperCase() || '')
        .join('')

      const roleName = Array.isArray(user.role_names) && user.role_names.length > 0
        ? String(user.role_names[0])
        : String(user.user_type || 'Utilisateur')

      return {
        id: user.id,
        name: fullName,
        initials,
        email: user.email || '-',
        role: roleName.replace(/_/g, ' '),
        site: sitesById.get(user.site_id) || user.site?.name || '-',
        status: user.is_active ? 'active' : 'inactive',
        lastLogin: user.last_login_at || null,
      }
    })
  })

  const company = computed(() => {
    if (!enterprise.value) {
      return null
    }

    const enterpriseUsers = users.value
    const displaySites: DisplaySite[] = (enterprise.value.sites ?? []).map((site: any) => ({
      id: site.id,
      name: site.name,
      location: site.location || '-',
      users: enterpriseUsers.filter(user => user.site === site.name).length,
      status: site.is_active ? 'active' : 'inactive',
    }))

    const enterpriseAdmin = enterpriseUsers.find(user =>
      user.role.toLowerCase().includes('admin_entreprise')
      || user.role.toLowerCase().includes('admin entreprise')
      || user.role.toLowerCase().includes('company'),
    ) || enterpriseUsers[0]

    const lastPaymentSub = subscriptions.value.reduce<DisplaySubscription | null>((latest, current) => {
      if (!latest) return current
      return new Date(current.startDate).getTime() > new Date(latest.startDate).getTime() ? current : latest
    }, null)
    const lastPayment = lastPaymentSub?.startDate || enterprise.value.updated_at || enterprise.value.created_at

    const nextRenewalSub = subscriptions.value
      .filter(sub => sub.status === 'Actif' || sub.status === 'Essai')
      .reduce<DisplaySubscription | null>((nearest, current) => {
        if (!nearest) return current
        return new Date(current.endDate).getTime() < new Date(nearest.endDate).getTime() ? current : nearest
      }, null)
    const nextRenewal = nextRenewalSub?.endDate

    return {
      id: String(enterprise.value.id),
      name: enterprise.value.name,
      rccm: enterprise.value.registration_number || '-',
      sector: enterprise.value.field || 'Non renseigné',
      country: (enterprise.value as any).country || '-',
      address: (enterprise.value as any).address || '-',
      status: enterprise.value.status,
      adminName: enterpriseAdmin?.name || '-',
      email: enterprise.value.email || '-',
      phone: (enterprise.value as any).phone || '-',
      createdAt: enterprise.value.created_at || '',
      lastPayment: lastPayment || '',
      nextRenewal: nextRenewal || '',
      sites: displaySites,
    }
  })

  const history = computed(() => {
    if (!enterprise.value) {
      return []
    }

    const items = [
      {
        id: 1,
        title: 'Entreprise créée',
        description: `Inscription de ${enterprise.value.name}`,
        date: enterprise.value.created_at,
        icon: 'mdi-office-building-plus-outline',
        color: 'primary',
        userName: 'Système',
        userInitials: 'SY',
      },
      {
        id: 2,
        title: 'Mise à jour du statut',
        description: `Statut actuel: ${getEnterpriseStatusLabel(enterprise.value.status)}`,
        date: enterprise.value.updated_at,
        icon: 'mdi-shield-check-outline',
        color: 'success',
        userName: 'Système',
        userInitials: 'SY',
      },
      {
        id: 3,
        title: 'Synchronisation abonnements',
        description: `${subscriptions.value.length} abonnement(s) chargé(s)`,
        date: enterprise.value.updated_at,
        icon: 'mdi-cash-multiple',
        color: 'info',
        userName: 'Système',
        userInitials: 'SY',
      },
    ]

    return items.filter(item => !!item.date)
  })

  onMounted(async () => {
    await loadData()
  })

  async function loadData () {
    loading.value = true
    error.value = null
    try {
      const id = Number((route.params as Record<string, unknown>).id)
      if (!id || Number.isNaN(id)) {
        throw new Error('Identifiant entreprise invalide')
      }

      const [enterpriseData, subscriptionsResponse] = await Promise.all([
        superAdminService.getEnterprise(id),
        superAdminService.getSubscriptions({ enterprise_id: id, per_page: 100 }),
      ])

      enterprise.value = enterpriseData
      subscriptions.value = (subscriptionsResponse.data || []).map((sub: Subscription & any) => {
        const isTrial = sub.is_trial === true || sub.status === 'trial'
        const isExpired = !!sub.expiration_date && new Date(sub.expiration_date).getTime() < Date.now()
        let status: DisplaySubscription['status'] = 'Inactif'
        if (isTrial) {
          status = 'Essai'
        } else if (sub.is_active) {
          status = 'Actif'
        } else if (isExpired) {
          status = 'Expiré'
        }

        return {
          id: sub.id,
          siteId: Number(sub.site_id),
          module: sub.offer?.name || 'Offre',
          icon: 'mdi-cube-outline',
          status,
          startDate: sub.start_date || '',
          endDate: sub.expiration_date || '',
          amount: Number(sub.offer?.price || 0),
        } as DisplaySubscription
      })
    } catch (error_: any) {
      error.value = error_?.response?.data?.message || error_?.message || 'Erreur de chargement'
    } finally {
      loading.value = false
    }
  }

  function getSubscriptionsBySite (siteId: number) {
    return subscriptions.value.filter(sub => sub.siteId === siteId)
  }

  function getSubscriptionStatusColor (status: string) {
    switch (status) {
      case 'Actif': {
        return 'success'
      }
      case 'Essai': {
        return 'info'
      }
      case 'Expiré': {
        return 'error'
      }
      default: {
        return 'grey'
      }
    }
  }

  function formatDate (date?: string | null) {
    if (!date) {
      return '-'
    }
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
  }

  function formatDateTime (date?: string | null) {
    if (!date) {
      return '-'
    }
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatCurrency (amount: number) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      minimumFractionDigits: 0,
    }).format(amount)
  }

  function getEnterpriseStatusColor (status: string) {
    const colors: Record<string, string> = {
      active: 'success',
      pending: 'warning',
      suspended: 'error',
      rejected: 'error',
    }
    return colors[status] || 'grey'
  }

  function getEnterpriseStatusIcon (status: string) {
    const icons: Record<string, string> = {
      active: 'mdi-check-circle-outline',
      pending: 'mdi-clock-outline',
      suspended: 'mdi-pause-circle-outline',
      rejected: 'mdi-close-circle-outline',
    }
    return icons[status] || 'mdi-help-circle-outline'
  }

  function getEnterpriseStatusLabel (status: string) {
    const labels: Record<string, string> = {
      active: 'Active',
      pending: 'En attente',
      suspended: 'Suspendue',
      rejected: 'Rejetée',
    }
    return labels[status] || status
  }

  function toggleStatus () {
    if (!enterprise.value) return

    if (enterprise.value.status === 'active') {
      suspendDialog.value = true
      suspendReason.value = ''
      suspendReasonError.value = ''
      return
    }

    reactivateDialog.value = true
  }

  async function confirmSuspend () {
    if (!enterprise.value) return
    if (!suspendReason.value || suspendReason.value.trim().length < 10) {
      suspendReasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    await runLocked('company-detail-suspend', async () => {
      togglingStatus.value = true
      try {
        await superAdminService.suspendEnterprise(enterprise.value!.id, suspendReason.value.trim())
        toast.success('Entreprise suspendue')
        suspendDialog.value = false
        await loadData()
      } finally {
        togglingStatus.value = false
      }
    })
  }

  async function confirmReactivate () {
    if (!enterprise.value) return
    await runLocked('company-detail-reactivate', async () => {
      togglingStatus.value = true
      try {
        await superAdminService.reactivateEnterprise(enterprise.value!.id)
        toast.success('Entreprise réactivée')
        reactivateDialog.value = false
        await loadData()
      } finally {
        togglingStatus.value = false
      }
    })
  }

  function viewSubscription (subscription: DisplaySubscription) {
    router.push(`/superadmin/subscriptions/${subscription.id}`)
  }

  function viewUser (user: any) {
    router.push({
      path: '/superadmin/users',
      query: { search: user.email },
    })
  }

  function goToKycDetail () {
    if (!enterprise.value) return
    router.push(`/superadmin/kyc/${enterprise.value.id}`)
  }
</script>

<style scoped>
.border {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

  .v-timeline-item {
    padding-bottom: 24px;
  }
</style>
