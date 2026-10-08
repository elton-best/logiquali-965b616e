<template>
  <ClientALayout current-page="security-audit">
    <v-container class="pa-6" fluid>
      <div class="mb-6">
        <h1 class="text-h4 font-weight-bold mb-2">
          <v-icon class="mr-2">mdi-shield-search</v-icon>
          Journal de sécurité
        </h1>
        <p class="text-body-2 text-medium-emphasis">
          Consultez les actions sensibles et événements d’accès sur votre périmètre.
        </p>
      </div>

      <v-row class="mb-4" dense>
        <v-col cols="12" md="2">
          <v-card rounded="lg" variant="tonal">
            <v-card-text>
              <div class="text-caption text-medium-emphasis">Total</div>
              <div class="text-h6 font-weight-bold">{{ stats.total_events }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="2">
          <v-card rounded="lg" variant="tonal">
            <v-card-text>
              <div class="text-caption text-medium-emphasis">Aujourd’hui</div>
              <div class="text-h6 font-weight-bold">{{ stats.events_today }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="2">
          <v-card rounded="lg" variant="tonal">
            <v-card-text>
              <div class="text-caption text-medium-emphasis">Risque élevé</div>
              <div class="text-h6 font-weight-bold">{{ stats.high_risk_events }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="2">
          <v-card rounded="lg" variant="tonal">
            <v-card-text>
              <div class="text-caption text-medium-emphasis">Critiques</div>
              <div class="text-h6 font-weight-bold">{{ stats.critical_events }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card rounded="lg" variant="tonal">
            <v-card-text>
              <div class="text-caption text-medium-emphasis">Échecs connexion (jour)</div>
              <div class="text-h6 font-weight-bold">{{ stats.failed_logins_today }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="mb-4" rounded="lg" variant="tonal">
        <v-card-text>
          <div class="d-flex align-center ga-2 mb-3">
            <div class="text-subtitle-2 font-weight-bold">
              Divergences RBAC (shadow)
            </div>
            <v-spacer />
            <v-select
              v-model="shadowWindowDays"
              density="compact"
              hide-details
              item-title="label"
              item-value="value"
              :items="shadowWindowDayOptions"
              label="Fenêtre"
              style="max-width: 180px"
              @update:model-value="loadShadowSummary"
            />
          </div>
          <v-row dense>
            <v-col cols="12" md="3">
              <v-sheet class="pa-3" rounded="lg">
                <div class="text-caption text-medium-emphasis">Total divergences</div>
                <div class="text-h6 font-weight-bold">{{ shadowSummary.total_divergences }}</div>
              </v-sheet>
            </v-col>
            <v-col cols="12" md="3">
              <v-sheet class="pa-3" rounded="lg">
                <div class="text-caption text-medium-emphasis">Permission</div>
                <div class="text-h6 font-weight-bold">{{ shadowSummary.by_reason.permission || 0 }}</div>
              </v-sheet>
            </v-col>
            <v-col cols="12" md="3">
              <v-sheet class="pa-3" rounded="lg">
                <div class="text-caption text-medium-emphasis">Scope</div>
                <div class="text-h6 font-weight-bold">{{ shadowSummary.by_reason.scope || 0 }}</div>
              </v-sheet>
            </v-col>
            <v-col cols="12" md="3">
              <v-sheet class="pa-3" rounded="lg">
                <div class="text-caption text-medium-emphasis">Subscription</div>
                <div class="text-h6 font-weight-bold">{{ shadowSummary.by_reason.subscription || 0 }}</div>
              </v-sheet>
            </v-col>
          </v-row>

          <div class="mt-4">
            <div class="text-caption text-medium-emphasis mb-2">Top permissions divergentes</div>
            <div class="d-flex flex-wrap ga-2">
              <v-chip
                v-for="item in shadowSummary.top_permissions"
                :key="item.permission"
                color="primary"
                size="small"
                variant="outlined"
              >
                {{ item.permission }} ({{ item.count }})
              </v-chip>
              <span v-if="shadowSummary.top_permissions.length === 0" class="text-caption text-medium-emphasis">
                Aucune divergence sur la fenêtre sélectionnée.
              </span>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="mb-4">
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.risk_level"
                clearable
                density="compact"
                :items="riskLevels"
                label="Risque"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="filters.event_type"
                clearable
                density="compact"
                label="Type événement"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="filters.action"
                clearable
                density="compact"
                label="Action"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.site_id"
                clearable
                density="compact"
                item-title="name"
                item-value="id"
                :items="siteOptions"
                label="Site"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="filters.date_from"
                density="compact"
                label="Du"
                type="date"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="filters.date_to"
                density="compact"
                label="Au"
                type="date"
              />
            </v-col>
          </v-row>
          <div class="d-flex ga-2">
            <v-btn color="primary" :loading="loading" @click="applyFilters">
              Appliquer
            </v-btn>
            <v-btn variant="text" @click="resetFilters">Réinitialiser</v-btn>
          </div>
        </v-card-text>
      </v-card>

      <v-card>
        <v-card-text class="pa-0">
          <v-table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Risque</th>
                <th>Action</th>
                <th>Événement</th>
                <th>Utilisateur</th>
                <th>Site</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs" :key="log.id">
                <td>{{ formatDate(log.created_at) }}</td>
                <td>
                  <v-chip :color="riskColor(log.risk_level)" size="small" variant="tonal">
                    {{ log.risk_level }}
                  </v-chip>
                </td>
                <td>{{ log.action }}</td>
                <td>{{ log.event_type }}</td>
                <td>{{ log.user?.name || 'Système' }}</td>
                <td>{{ log.site?.name || '-' }}</td>
                <td>{{ log.status }}</td>
              </tr>
              <tr v-if="!loading && logs.length === 0">
                <td class="text-center text-medium-emphasis py-6" colspan="7">
                  Aucun événement trouvé.
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-divider />
        <v-card-actions class="px-4">
          <span class="text-caption text-medium-emphasis">
            {{ pagination.total }} événement(s)
          </span>
          <v-spacer />
          <v-pagination
            v-model="pagination.current_page"
            :length="Math.max(1, pagination.last_page)"
            :total-visible="7"
            @update:model-value="loadLogs"
          />
        </v-card-actions>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import {
    type SecurityAuditLogEntry,
    securityAuditService,

    type SecurityAuditStats,
    type ShadowRbacSummary,
  } from '@/services/securityAuditService'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()
  const loading = ref(false)
  const logs = ref<SecurityAuditLogEntry[]>([])
  const stats = ref<SecurityAuditStats>({
    total_events: 0,
    high_risk_events: 0,
    critical_events: 0,
    events_today: 0,
    failed_logins_today: 0,
  })
  const shadowWindowDays = ref(7)
  const shadowWindowDayOptions = [
    { label: '24h', value: 1 },
    { label: '7 jours', value: 7 },
    { label: '14 jours', value: 14 },
    { label: '30 jours', value: 30 },
  ]
  const shadowSummary = ref<ShadowRbacSummary>({
    window_days: 7,
    total_divergences: 0,
    by_reason: {
      permission: 0,
      scope: 0,
      subscription: 0,
      other: 0,
    },
    top_permissions: [],
    timeline: [],
  })

  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 50,
  })

  const filters = reactive({
    event_type: '',
    action: '',
    risk_level: '',
    site_id: null as number | null,
    date_from: '',
    date_to: '',
  })

  const riskLevels = ['low', 'medium', 'high', 'critical']
  const siteOptions = computed(() => {
    return (authStore.availableSites || []).map((site: any) => ({
      id: Number(site?.id),
      name: String(site?.name || 'Site sans nom'),
    })).filter(site => Number.isFinite(site.id))
  })

  function buildParams (page = 1) {
    const params: Record<string, any> = { page }
    if (filters.event_type.trim()) params.event_type = filters.event_type.trim()
    if (filters.action.trim()) params.action = filters.action.trim()
    if (filters.risk_level) params.risk_level = filters.risk_level
    if (filters.site_id) params.site_id = filters.site_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    return params
  }

  async function loadStats () {
    try {
      stats.value = await securityAuditService.getStats(buildParams(pagination.current_page))
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur chargement statistiques audit')
    }
  }

  async function loadShadowSummary () {
    try {
      const params: Record<string, any> = {
        window_days: shadowWindowDays.value,
      }
      if (filters.site_id) params.site_id = filters.site_id
      shadowSummary.value = await securityAuditService.getShadowRbacSummary(params)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur chargement divergences RBAC')
    }
  }

  async function loadLogs (page = 1) {
    loading.value = true
    try {
      const payload = await securityAuditService.getLogs(buildParams(page))
      logs.value = Array.isArray(payload.data) ? payload.data : []
      pagination.current_page = Number(payload.current_page || 1)
      pagination.last_page = Number(payload.last_page || 1)
      pagination.total = Number(payload.total || 0)
      pagination.per_page = Number(payload.per_page || 50)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur chargement journal sécurité')
      logs.value = []
    } finally {
      loading.value = false
    }
  }

  async function applyFilters () {
    pagination.current_page = 1
    await Promise.all([loadLogs(1), loadStats(), loadShadowSummary()])
  }

  async function resetFilters () {
    filters.event_type = ''
    filters.action = ''
    filters.risk_level = ''
    filters.site_id = null
    filters.date_from = ''
    filters.date_to = ''
    await applyFilters()
  }

  function formatDate (value: string): string {
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
      return '-'
    }
    return date.toLocaleString('fr-FR')
  }

  function riskColor (risk: string): string {
    if (risk === 'critical') return 'error'
    if (risk === 'high') return 'deep-orange'
    if (risk === 'medium') return 'warning'
    return 'info'
  }

  onMounted(async () => {
    await Promise.all([loadLogs(1), loadStats(), loadShadowSummary()])
  })
</script>
