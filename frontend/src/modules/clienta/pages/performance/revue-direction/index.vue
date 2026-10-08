<template>
  <ClientALayout current-page="performance-revue">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-account-group" title="Revue de direction">
        <template #subtitle>
          Pilotage des revues, invitations, décisions et synthèses ISO 9001 §9.3.2.
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-view-dashboard-outline" rounded="lg" @click="$router.push('/company/management-reviews')">
            Pilotage complet
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">ISO 9001 • Clause 9.3</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Revue de direction orientée décision
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Accédez rapidement aux rapports, invitations et synthèses du système de management pour le site sélectionné.
              </p>
            </div>
            <div class="hero-side">
              <div class="hero-side-card">
                <span>Total revues</span>
                <strong>{{ stats.total }}</strong>
              </div>
              <div class="hero-side-card">
                <span>Terminées</span>
                <strong>{{ stats.completed }}</strong>
              </div>
              <div class="hero-side-card">
                <span>Participants convoqués</span>
                <strong>{{ stats.participants }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row class="mb-6" dense>
        <v-col cols="12" md="3" sm="6">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">Planifiées</div>
                <div class="kpi-value">{{ stats.planned }}</div>
              </div>
              <v-icon color="info" size="28">mdi-calendar-month-outline</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">En cours</div>
                <div class="kpi-value">{{ stats.in_progress }}</div>
              </div>
              <v-icon color="warning" size="28">mdi-progress-clock</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">Terminées</div>
                <div class="kpi-value">{{ stats.completed }}</div>
              </div>
              <v-icon color="success" size="28">mdi-check-decagram-outline</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">Invitations</div>
                <div class="kpi-value">{{ stats.invitations }}</div>
              </div>
              <v-icon color="primary" size="28">mdi-email-fast-outline</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row class="mb-6" dense>
        <v-col cols="12" lg="4">
          <v-card class="chart-card h-100" rounded="xl" variant="outlined">
            <v-card-title class="chart-title">
              <span>Répartition des statuts</span>
              <v-chip color="primary" size="small" variant="tonal">{{ stats.total }} revue(s)</v-chip>
            </v-card-title>
            <v-card-text>
              <div class="chart-shell">
                <Doughnut v-if="reviews.length > 0" :data="statusChartData" :options="doughnutChartOptions" />
                <v-alert v-else density="comfortable" type="info" variant="tonal">
                  Aucune revue enregistrée pour le site sélectionné.
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card class="chart-card h-100" rounded="xl" variant="outlined">
            <v-card-title class="chart-title">
              <span>Taux de remplissage</span>
              <v-chip :color="completionColor" size="small" variant="tonal">{{ completionRate }}%</v-chip>
            </v-card-title>
            <v-card-text>
              <div class="chart-shell">
                <Bar v-if="reviews.length > 0" :data="completionChartData" :options="completionChartOptions" />
                <v-alert v-else density="comfortable" type="info" variant="tonal">
                  Créez une revue pour suivre les données renseignées.
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card class="chart-card h-100" rounded="xl" variant="outlined">
            <v-card-title class="chart-title">
              <span>Évolution des revues</span>
              <v-chip color="success" size="small" variant="tonal">{{ stats.completed }} terminée(s)</v-chip>
            </v-card-title>
            <v-card-text>
              <div class="chart-shell">
                <Bar v-if="timelineChartData.labels.length > 0" :data="timelineChartData" :options="timelineChartOptions" />
                <v-alert v-else density="comfortable" type="info" variant="tonal">
                  Pas encore assez de données datées pour construire l’évolution.
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="synthesis-shell mb-6" rounded="xl" variant="outlined">
        <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
          <div>
            <div class="section-title mb-1">
              <v-icon color="success" size="18">mdi-chart-arc</v-icon>
              <span>Synthèse du SM en direct (site sélectionné)</span>
            </div>
            <div class="text-caption text-medium-emphasis">
              ISO 9001 Chapitre 9.3.2 - b, c1, c2, c4, c7, e
            </div>
          </div>
          <div class="d-flex align-center ga-2">
            <v-btn :loading="synthesisLoading" size="small" variant="outlined" @click="loadSmSynthesis">
              Actualiser
            </v-btn>
            <v-btn color="primary" size="small" @click="$router.push('/company/performance/revue-direction/synthese')">
              Vue détaillée
            </v-btn>
          </div>
        </v-card-title>
        <v-card-text>
          <v-alert v-if="synthesisError" density="comfortable" type="warning" variant="tonal">
            {{ synthesisError }}
          </v-alert>

          <template v-else-if="synthesisEntries.length > 0">
            <div class="text-caption text-medium-emphasis mb-3">
              Mise à jour: {{ synthesisGeneratedAtLabel }}
            </div>
            <v-row dense>
              <v-col
                v-for="entry in synthesisEntries"
                :key="entry.key"
                cols="12"
                lg="2"
                md="4"
                sm="6"
              >
                <v-card class="h-100 mini-synthesis-card" rounded="lg" variant="tonal">
                  <v-card-text class="py-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <span class="text-caption font-weight-bold mini-title">{{ entry.title }}</span>
                      <span class="text-caption font-weight-bold">{{ entry.scorePercent }}%</span>
                    </div>
                    <v-progress-linear
                      :color="entry.scoreColor"
                      height="7"
                      :model-value="entry.scorePercent"
                      rounded
                    />
                    <div class="text-caption mt-2 text-medium-emphasis text-truncate">
                      {{ entry.summary }}
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </template>

          <v-alert v-else density="comfortable" type="info" variant="tonal">
            Aucune donnée de synthèse disponible pour ce site.
          </v-alert>
        </v-card-text>
      </v-card>

      <div class="section-title mb-3">
        <v-icon color="primary" size="18">mdi-compass-outline</v-icon>
        <span>Actions essentielles</span>
      </div>

      <v-row class="cards-grid">
        <v-col class="d-flex" cols="12" lg="3" md="6">
          <v-card class="nav-card h-100" hover rounded="xl" @click="$router.push('/company/management-reviews')">
            <v-card-text class="pa-5">
              <div class="nav-icon"><v-icon color="primary" size="24">mdi-clipboard-list-outline</v-icon></div>
              <div class="nav-title">Suivi des revues</div>
              <div class="nav-subtitle">Registre complet</div>
              <div class="nav-copy">Consultez toutes les revues, leur statut et leur avancement.</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col class="d-flex" cols="12" lg="3" md="6">
          <v-card class="nav-card h-100" hover rounded="xl" @click="$router.push('/company/management-reviews?openCreate=1')">
            <v-card-text class="pa-5">
              <div class="nav-icon"><v-icon color="warning" size="24">mdi-calendar-plus</v-icon></div>
              <div class="nav-title">Planifier une revue</div>
              <div class="nav-subtitle">Préparation</div>
              <div class="nav-copy">Créez une nouvelle revue, définissez participants, données d’entrée et planning.</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col class="d-flex" cols="12" lg="3" md="6">
          <v-card class="nav-card h-100" hover rounded="xl" @click="$router.push('/company/performance/revue-direction/synthese')">
            <v-card-text class="pa-5">
              <div class="nav-icon"><v-icon color="success" size="24">mdi-chart-arc</v-icon></div>
              <div class="nav-title">Synthèse du SM</div>
              <div class="nav-subtitle">ISO 9.3.2</div>
              <div class="nav-copy">Consultez les synthèses dynamiques avant la planification de la revue.</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col class="d-flex" cols="12" lg="3" md="6">
          <v-card class="nav-card h-100" hover rounded="xl" @click="$router.push('/company/management-reviews')">
            <v-card-text class="pa-5">
              <div class="nav-icon"><v-icon color="info" size="24">mdi-email-outline</v-icon></div>
              <div class="nav-title">Exécution</div>
              <div class="nav-subtitle">Invitations & rapports</div>
              <div class="nav-copy">Gérez les convocations, les exports et les comptes rendus directement depuis le registre ou la fiche revue.</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
  } from 'chart.js'
  import { computed, onMounted, ref, watch } from 'vue'
  import { Bar, Doughnut } from 'vue-chartjs'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  ChartJS.register(ArcElement, BarElement, CategoryScale, Legend, LinearScale, Tooltip)

  const toast = useToast()
  const authStore = useAuthStore()
  const reviews = ref<any[]>([])
  const stats = ref({
    total: 0,
    planned: 0,
    in_progress: 0,
    completed: 0,
    invitations: 0,
    participants: 0,
  })
  const synthesisLoading = ref(false)
  const synthesisError = ref('')
  const synthesisPayload = ref<any>(null)
  const inputFieldKeys = [
    'previous_actions_status',
    'context_changes',
    'performance_indicators',
    'customer_satisfaction',
    'audit_results',
    'nc_complaints_status',
    'resources_adequacy',
    'improvement_opportunities',
    'decisions',
    'action_items',
    'kpi_data',
    'objectives_data',
    'actions_data',
    'risks_data',
    'nc_data',
    'audit_data',
  ]

  function normalizeReviewRow (row: any) {
    return row?.attributes || row || {}
  }

  function hasFilledValue (value: unknown) {
    if (Array.isArray(value)) return value.length > 0
    if (value && typeof value === 'object') return Object.keys(value).length > 0
    return String(value ?? '').trim().length > 0
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      planned: 'Planifiées',
      in_progress: 'En cours',
      completed: 'Terminées',
      reported: 'Reportées',
    }
    return labels[status] || status || 'Sans statut'
  }

  function resolveChartColor (status: string) {
    const colors: Record<string, string> = {
      planned: '#2563eb',
      in_progress: '#f59e0b',
      completed: '#16a34a',
      reported: '#64748b',
    }
    return colors[status] || '#94a3b8'
  }

  function getCompletionRateForReview (review: any) {
    const filled = inputFieldKeys.filter(key => hasFilledValue(review?.[key])).length
    return Math.round((filled / inputFieldKeys.length) * 100)
  }

  const normalizedReviews = computed(() => reviews.value.map(normalizeReviewRow))

  const completionRate = computed(() => {
    if (normalizedReviews.value.length === 0) return 0
    const total = normalizedReviews.value.reduce((sum, review) => sum + getCompletionRateForReview(review), 0)
    return Math.round(total / normalizedReviews.value.length)
  })

  const completionColor = computed(() => {
    if (completionRate.value >= 80) return 'success'
    if (completionRate.value >= 55) return 'warning'
    return 'error'
  })

  const statusChartData = computed(() => {
    const statuses = ['planned', 'in_progress', 'completed', 'reported']
    const values = statuses.map(status => normalizedReviews.value.filter(review => review.status === status).length)
    return {
      labels: statuses.map(getStatusLabel),
      datasets: [
        {
          data: values,
          backgroundColor: statuses.map(resolveChartColor),
          borderWidth: 0,
        },
      ],
    }
  })

  const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '66%',
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: {
          color: '#334155',
          boxWidth: 10,
        },
      },
    },
  }

  const completionChartData = computed(() => {
    const buckets = [
      { label: '0-25%', min: 0, max: 25, color: '#dc2626' },
      { label: '26-50%', min: 26, max: 50, color: '#f59e0b' },
      { label: '51-75%', min: 51, max: 75, color: '#0284c7' },
      { label: '76-100%', min: 76, max: 100, color: '#16a34a' },
    ]
    return {
      labels: buckets.map(bucket => bucket.label),
      datasets: [
        {
          label: 'Revues',
          data: buckets.map(bucket => normalizedReviews.value.filter(review => {
            const rate = getCompletionRateForReview(review)
            return rate >= bucket.min && rate <= bucket.max
          }).length),
          backgroundColor: buckets.map(bucket => bucket.color),
          borderRadius: 8,
          borderSkipped: false,
        },
      ],
    }
  })

  const completionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#334155' } },
      y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' } },
    },
  }

  const timelineChartData = computed(() => {
    const grouped = new Map<string, { planned: number, completed: number }>()
    for (const review of normalizedReviews.value) {
      const dateValue = review.actual_date || review.planned_date || review.scheduled_date || review.created_at
      if (!dateValue) continue
      const date = new Date(dateValue)
      if (Number.isNaN(date.getTime())) continue
      const label = date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' })
      const current = grouped.get(label) || { planned: 0, completed: 0 }
      current.planned += 1
      if (review.status === 'completed') current.completed += 1
      grouped.set(label, current)
    }

    const labels = Array.from(grouped.keys()).slice(-8)
    return {
      labels,
      datasets: [
        {
          label: 'Revues',
          data: labels.map(label => grouped.get(label)?.planned || 0),
          backgroundColor: '#2563eb',
          borderRadius: 8,
        },
        {
          label: 'Terminées',
          data: labels.map(label => grouped.get(label)?.completed || 0),
          backgroundColor: '#16a34a',
          borderRadius: 8,
        },
      ],
    }
  })

  const timelineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: { color: '#334155', boxWidth: 10 },
      },
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#334155' } },
      y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' } },
    },
  }

  const synthesisGeneratedAtLabel = computed(() => {
    const value = synthesisPayload.value?.generated_at
    if (!value) return '-'
    return new Date(value).toLocaleString('fr-FR')
  })

  const synthesisEntries = computed(() => {
    const entries = synthesisPayload.value?.entries
    if (!entries || typeof entries !== 'object') return []
    return Object.entries(entries).map(([key, value]: [string, any]) => {
      const scorePercent = Number(value?.score_percent ?? 0)
      const scoreColor = scorePercent >= 80 ? 'success' : scorePercent >= 60 ? 'info' : scorePercent >= 40 ? 'warning' : 'error'
      const rawTitle = String(value?.title || key)
      const title = rawTitle.replace(/^[a-z]\d?\)\s*/i, '')
      return {
        key,
        title,
        summary: value?.summary || '—',
        scorePercent,
        scoreColor,
      }
    })
  })

  async function loadSummary () {
    try {
      const { data } = await api.get('/management-reviews', {
        params: { per_page: 200, site_id: authStore.currentSiteId || undefined },
      })
      const rows = Array.isArray(data?.data) ? data.data : []
      reviews.value = rows

      const extracted = rows.map(normalizeReviewRow)
      stats.value.total = extracted.length
      stats.value.planned = extracted.filter((x: any) => x?.status === 'planned').length
      stats.value.in_progress = extracted.filter((x: any) => x?.status === 'in_progress').length
      stats.value.completed = extracted.filter((x: any) => x?.status === 'completed').length
      stats.value.invitations = extracted.reduce((acc: number, x: any) => acc + (Array.isArray(x?.participants) ? x.participants.length : 0), 0)
      stats.value.participants = stats.value.invitations
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger la vue Revue de direction.'))
      reviews.value = []
    }
  }

  async function loadSmSynthesis () {
    if (!authStore.currentSiteId) {
      synthesisPayload.value = null
      synthesisError.value = 'Sélectionnez un site pour afficher la synthèse du SM.'
      return
    }
    synthesisLoading.value = true
    synthesisError.value = ''
    try {
      const { data } = await api.get('/management-reviews/sm-synthesis', {
        params: { site_id: authStore.currentSiteId },
      })
      synthesisPayload.value = data?.data || null
    } catch (error) {
      synthesisPayload.value = null
      synthesisError.value = getErrorMessage(error, 'Impossible de charger la synthèse du SM.')
    } finally {
      synthesisLoading.value = false
    }
  }

  onMounted(async () => {
    await loadSummary()
    await loadSmSynthesis()
  })

  watch(() => authStore.currentSiteId, async () => {
    await loadSummary()
    await loadSmSynthesis()
  })
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.2), transparent 62%),
    radial-gradient(900px 360px at 100% 0%, rgba(5, 150, 105, 0.18), transparent 60%),
    linear-gradient(135deg, rgba(15, 23, 42, 0.03), rgba(255, 255, 255, 0.85));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
}

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.hero-side {
  display: grid;
  gap: 12px;
}

.hero-side-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.86);
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.hero-side-card span {
  color: #64748b;
  font-size: 0.85rem;
}

.hero-side-card strong {
  color: #0f172a;
  font-size: 1.1rem;
}

.kpi-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
}

.kpi-label {
  font-size: 0.8rem;
  color: #64748b;
}

.kpi-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
}

.section-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  color: #0f172a;
}

.chart-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.chart-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #0f172a;
  font-size: 0.98rem;
  font-weight: 800;
}

.chart-shell {
  min-height: 230px;
  position: relative;
}

.nav-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.nav-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.nav-title {
  color: #0f172a;
  font-weight: 800;
  font-size: 1rem;
}

.nav-subtitle {
  color: #64748b;
  font-size: 0.85rem;
  margin-top: 2px;
}

.nav-copy {
  color: #475569;
  margin-top: 10px;
  font-size: 0.9rem;
}

.synthesis-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.mini-synthesis-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.92);
}

.mini-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }
}
</style>
