<template>
  <div class="subscription-dashboard pa-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-h4 font-weight-bold mb-2">Tableau de bord des abonnements</h1>
      <p class="text-body-1 text-medium-emphasis">Gérez tous vos abonnements et normes actives</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <UnifiedLoader
        class="mx-auto"
        description="Chargement des abonnements et indicateurs..."
        title="Chargement du tableau de bord..."
        variant="local"
      />
    </div>

    <!-- Dashboard Content -->
    <div v-else-if="dashboard">
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Abonnements actifs"
          :value="dashboard.statistics.active_subscriptions_count"
          variant="primary"
        />
        <AppWidget
          clickable
          :icon="Award"
          title="Normes actives"
          :value="dashboard.statistics.active_norms_count"
          variant="success"
        />
        <AppWidget
          clickable
          :icon="FolderOpen"
          title="Modules accessibles"
          :value="dashboard.statistics.total_modules_accessible"
          variant="info"
        />
        <AppWidget
          clickable
          :icon="AlertCircle"
          title="Alertes urgentes"
          :value="dashboard.urgent_alerts.length"
          variant="warning"
        />
      </div>

      <!-- Urgent Alerts -->
      <v-alert
        v-if="dashboard.urgent_alerts.length > 0"
        class="mb-6"
        prominent
        type="warning"
        variant="tonal"
      >
        <template #prepend>
          <v-icon size="32">mdi-alert</v-icon>
        </template>
        <div class="text-h6 mb-2">Abonnements expirant bientôt</div>
        <div v-for="alert in dashboard.urgent_alerts" :key="alert.id" class="mb-2">
          <strong>{{ alert.norms.join(', ') }}</strong> -
          <span :class="getSeverityClass(alert.severity)">
            {{ alert.days_remaining }} jour(s) restant(s)
          </span>
          <v-btn
            class="ml-3"
            color="warning"
            size="small"
            variant="outlined"
            @click="renewSubscription(alert.id)"
          >
            Renouveler
          </v-btn>
        </div>
      </v-alert>

      <!-- Quick Actions -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card elevation="0" style="border: 1px solid #e2e8f0;">
            <v-card-title class="text-h6">Actions rapides</v-card-title>
            <v-card-text>
              <v-row>
                <v-col
                  v-for="action in quickActions.filter(a => a.enabled)"
                  :key="action.id"
                  cols="12"
                  md="4"
                >
                  <v-btn
                    block
                    :color="action.color"
                    :prepend-icon="action.icon"
                    size="large"
                    variant="tonal"
                    @click="handleQuickAction(action.id)"
                  >
                    {{ action.label }}
                  </v-btn>
                  <div class="text-caption text-medium-emphasis mt-2">{{ action.description }}</div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Active Subscriptions -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card elevation="0" style="border: 1px solid #e2e8f0;">
            <v-card-title class="d-flex align-center justify-space-between">
              <span class="text-h6">Abonnements actifs</span>
              <v-chip color="success" size="small">{{ dashboard.statistics.active_subscriptions_count }}</v-chip>
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col
                  v-for="sub in dashboard.active_subscriptions"
                  :key="sub.id"
                  cols="12"
                  lg="4"
                  md="6"
                >
                  <v-card
                    class="subscription-card"
                    :class="{ 'expiring-soon': sub.is_expiring_soon }"
                    elevation="0"
                    style="border: 1px solid #e2e8f0;"
                  >
                    <v-card-text>
                      <div class="d-flex align-center justify-space-between mb-3">
                        <v-chip
                          v-if="sub.is_trial"
                          color="warning"
                          size="small"
                          variant="tonal"
                        >
                          <v-icon size="16" start>mdi-star</v-icon>
                          Essai
                        </v-chip>
                        <v-chip
                          v-else
                          color="success"
                          size="small"
                          variant="tonal"
                        >
                          Actif
                        </v-chip>
                        <span class="text-caption text-medium-emphasis">{{ sub.ref }}</span>
                      </div>

                      <div class="mb-3">
                        <div
                          v-for="norm in sub.norms"
                          :key="norm.id"
                          class="mb-1"
                        >
                          <v-chip color="primary" size="small" variant="outlined">
                            {{ norm.name }}
                          </v-chip>
                        </div>
                      </div>

                      <div class="mb-3">
                        <div class="d-flex align-center justify-space-between text-body-2">
                          <span class="text-medium-emphasis">Modules</span>
                          <span class="font-weight-bold">{{ sub.modules_count }}</span>
                        </div>
                        <div class="d-flex align-center justify-space-between text-body-2">
                          <span class="text-medium-emphasis">Expire le</span>
                          <span class="font-weight-bold">{{ formatDate(sub.expiration_date) }}</span>
                        </div>
                      </div>

                      <!-- Days Remaining Progress -->
                      <div class="mb-3">
                        <div class="d-flex align-center justify-space-between mb-1">
                          <span class="text-body-2">Jours restants</span>
                          <span
                            class="text-body-2 font-weight-bold"
                            :class="getDaysRemainingColor(sub.days_remaining)"
                          >
                            {{ sub.days_remaining }} jours
                          </span>
                        </div>
                        <v-progress-linear
                          :color="getDaysRemainingProgressColor(sub.days_remaining)"
                          height="6"
                          :model-value="(sub.days_remaining / 30) * 100"
                          rounded
                        />
                      </div>

                      <v-btn
                        block
                        color="primary"
                        size="small"
                        variant="outlined"
                        @click="renewSubscription(sub.id)"
                      >
                        <v-icon start>mdi-refresh</v-icon>
                        Renouveler
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-alert
                v-if="dashboard.active_subscriptions.length === 0"
                class="mt-4"
                type="info"
                variant="tonal"
              >
                Aucun abonnement actif. Souscrivez à une offre pour commencer.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Timeline -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card elevation="0" style="border: 1px solid #e2e8f0;">
            <v-card-title class="text-h6">Timeline des expirations</v-card-title>
            <v-card-text>
              <div class="timeline-container">
                <div
                  v-for="(month, index) in dashboard.timeline"
                  :key="index"
                  class="timeline-item"
                >
                  <div class="timeline-month">{{ month.month }}</div>
                  <div class="timeline-count">
                    <v-chip
                      :color="month.count > 0 ? 'warning' : 'grey'"
                      size="small"
                    >
                      {{ month.count }} expiration(s)
                    </v-chip>
                  </div>
                  <div v-if="month.count > 0" class="timeline-details mt-2">
                    <div
                      v-for="sub in month.subscriptions"
                      :key="sub.id"
                      class="text-caption"
                    >
                      {{ sub.norms.join(', ') }}
                    </div>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Expired Subscriptions -->
      <v-row v-if="dashboard.expired_subscriptions.length > 0">
        <v-col cols="12">
          <v-card elevation="0" style="border: 1px solid #e2e8f0;">
            <v-card-title class="d-flex align-center justify-space-between">
              <span class="text-h6">Abonnements expirés</span>
              <v-chip color="error" size="small">{{ dashboard.expired_subscriptions.length }}</v-chip>
            </v-card-title>
            <v-card-text>
              <v-list>
                <v-list-item
                  v-for="sub in dashboard.expired_subscriptions"
                  :key="sub.id"
                >
                  <template #prepend>
                    <v-icon color="error">mdi-alert-circle</v-icon>
                  </template>
                  <v-list-item-title>
                    {{ sub.norms.join(', ') }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    Expiré depuis {{ sub.days_expired }} jours
                    <span v-if="sub.arrears_amount > 0" class="text-error">
                      - Arriérés: {{ sub.arrears_amount }}€
                    </span>
                  </v-list-item-subtitle>
                  <template #append>
                    <v-btn
                      color="error"
                      size="small"
                      variant="tonal"
                      @click="renewSubscription(sub.id)"
                    >
                      Renouveler maintenant
                    </v-btn>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Renew Dialog -->
    <v-dialog v-model="renewDialog" max-width="600">
      <v-card>
        <v-card-title class="text-h6">Renouveler l'abonnement</v-card-title>
        <v-card-text>
          <v-select
            v-model="renewDuration"
            density="comfortable"
            :items="[1, 3, 6, 12]"
            label="Durée (mois)"
            variant="outlined"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props" :subtitle="`${item.value} mois`" />
            </template>
          </v-select>
          <v-alert class="mt-4" type="info" variant="tonal">
            Le renouvellement commencera automatiquement à la fin de l'abonnement actuel.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="renewDialog = false">Annuler</v-btn>
          <v-btn color="primary" :loading="renewLoading" variant="flat" @click="confirmRenewal">
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
  import { AlertCircle, Award, CheckCircle, FolderOpen } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import AppWidget from '@/components/common/AppWidget.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const router = useRouter()
  const loading = ref(true)
  const dashboard = ref(null)
  const quickActions = ref([])
  const renewDialog = ref(false)
  const renewSubscriptionId = ref(null)
  const renewDuration = ref(3)
  const renewLoading = ref(false)

  async function loadDashboard () {
    try {
      loading.value = true
      const [dashboardRes, actionsRes] = await Promise.all([
        api.get('/subscription/dashboard'),
        api.get('/subscription/quick-actions'),
      ])
      dashboard.value = dashboardRes.data.data
      quickActions.value = actionsRes.data.actions
    } catch (error) {
      console.error('Erreur chargement dashboard:', error)
    } finally {
      loading.value = false
    }
  }

  function renewSubscription (id) {
    renewSubscriptionId.value = id
    renewDialog.value = true
  }

  async function confirmRenewal () {
    try {
      renewLoading.value = true
      await api.post(`/enterprise-subscriptions/${renewSubscriptionId.value}/renew`, {
        duration_months: renewDuration.value,
      })
      renewDialog.value = false
      loadDashboard()
    } catch (error) {
      console.error('Erreur renouvellement:', error)
    } finally {
      renewLoading.value = false
    }
  }

  function handleQuickAction (actionId) {
    if (actionId === 'add_norm') {
      router.push({ name: 'subscriptions-offers' })
    } else if (actionId === 'trial') {
      router.push({ name: 'subscriptions-trial' })
    }
  }

  function formatDate (date) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  function getSeverityClass (severity) {
    const classes = {
      critical: 'text-error font-weight-bold',
      high: 'text-warning font-weight-bold',
      medium: 'text-info',
    }
    return classes[severity] || ''
  }

  function getDaysRemainingColor (days) {
    if (days <= 3) return 'text-error'
    if (days <= 7) return 'text-warning'
    if (days <= 14) return 'text-info'
    return 'text-success'
  }

  function getDaysRemainingProgressColor (days) {
    if (days <= 3) return 'error'
    if (days <= 7) return 'warning'
    if (days <= 14) return 'info'
    return 'success'
  }

  onMounted(() => {
    loadDashboard()
  })
</script>

<style scoped>
.subscription-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.subscription-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.subscription-card.expiring-soon {
  border-color: #f59e0b !important;
  background: linear-gradient(to bottom, #fff 0%, #fffbeb 100%);
}

.timeline-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.timeline-item {
  padding: 16px;
  border-radius: 12px;
  background: #f8fafc;
  text-align: center;
}

.timeline-month {
  font-weight: 600;
  margin-bottom: 8px;
}

.timeline-count {
  margin-bottom: 8px;
}

.timeline-details {
  font-size: 12px;
  color: #64748b;
}
</style>
