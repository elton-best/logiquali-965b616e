<template>
  <ClientALayout current-page="performance-revue">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-chart-arc" title="Synthèse du SM">
        <template #subtitle>
          Vue dynamique du site sélectionné, mise à jour automatiquement selon les données du système.
        </template>
        <template #actions>
          <v-btn
            :loading="loading"
            prepend-icon="mdi-refresh"
            rounded="lg"
            variant="outlined"
            @click="loadSynthesis"
          >
            Actualiser
          </v-btn>
          <v-btn
            class="ml-2"
            color="primary"
            prepend-icon="mdi-calendar-plus"
            rounded="lg"
            @click="$router.push('/company/management-reviews?openCreate=1')"
          >
            Planifier la revue
          </v-btn>
        </template>
      </PageHeader>

      <v-alert v-if="error" class="mt-4" type="error" variant="tonal">{{ error }}</v-alert>

      <v-card class="hero mt-6 mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8 d-flex align-center justify-space-between flex-wrap ga-4">
          <div>
            <div class="text-overline hero-kicker mb-2">Éléments d'entrée de revue</div>
            <h2 class="text-h5 font-weight-black mb-1">Synthèse continue du système de management</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Période analysée: {{ periodLabel }}
            </p>
          </div>
          <v-chip color="primary" variant="tonal">
            Dernière mise à jour: {{ generatedAtLabel }}
          </v-chip>
        </v-card-text>
      </v-card>

      <v-row class="mb-2" dense>
        <v-col
          v-for="item in isoFocusItems"
          :key="item.code"
          cols="12"
          lg="2"
          md="4"
          sm="6"
        >
          <v-card class="focus-card h-100" rounded="lg" variant="tonal">
            <v-card-text class="py-3">
              <div class="text-caption text-medium-emphasis mb-1">Point {{ item.code }}</div>
              <div class="font-weight-bold text-body-2">{{ item.label }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row dense>
        <v-col
          v-for="entry in entries"
          :key="`score-${entry.key}`"
          cols="12"
          lg="2"
          md="4"
          sm="6"
        >
          <v-card class="h-100 score-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex flex-column align-center justify-center text-center ga-2 py-4">
              <div class="text-caption font-weight-bold entry-title">{{ entry.title }}</div>
              <v-progress-circular
                :color="entry.scoreColor"
                :model-value="entry.scorePercent"
                size="76"
                width="8"
              >
                {{ entry.scorePercent }}%
              </v-progress-circular>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="mt-4 details-card" rounded="xl" variant="outlined">
        <v-card-title>Diagramme en bandes comparatif</v-card-title>
        <v-card-text>
          <div v-for="entry in entries" :key="`band-${entry.key}`" class="band-row">
            <div class="band-label">{{ entry.title }}</div>
            <div class="band-bar">
              <v-progress-linear
                :color="entry.scoreColor"
                height="10"
                :model-value="entry.scorePercent"
                rounded
              />
            </div>
            <div class="band-value">{{ entry.scorePercent }}%</div>
          </div>
        </v-card-text>
      </v-card>

      <v-row class="mt-1" dense>
        <v-col cols="12" lg="5">
          <v-card class="h-100 details-card chart-card" rounded="xl" variant="outlined">
            <v-card-title class="d-flex align-center justify-space-between">
              <span>Radar de maturité du système</span>
              <v-chip color="primary" size="small" variant="tonal">
                ISO 9.3.2
              </v-chip>
            </v-card-title>
            <v-card-subtitle>
              Lecture visuelle de la robustesse des entrées de revue pour cibler les déséquilibres avant audit.
            </v-card-subtitle>
            <v-card-text>
              <div class="chart-shell">
                <Radar v-if="entries.length > 0" :data="radarChartData" :options="radarChartOptions" />
                <v-alert
                  v-else
                  class="mt-2"
                  density="comfortable"
                  type="info"
                  variant="tonal"
                >
                  Données insuffisantes pour construire le radar.
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="7">
          <v-card class="h-100 details-card chart-card" rounded="xl" variant="outlined">
            <v-card-title class="d-flex align-center justify-space-between">
              <span>Priorités avant audit</span>
              <v-chip :color="auditReadiness.color" size="small" variant="tonal">
                {{ auditReadiness.label }}
              </v-chip>
            </v-card-title>
            <v-card-subtitle>
              Les axes les plus faibles demandent une sécurisation documentaire, terrain et décisionnelle avant l’audit.
            </v-card-subtitle>
            <v-card-text>
              <div class="priority-headline mb-4">
                <div class="priority-score">
                  <span class="text-caption text-medium-emphasis">Indice de préparation audit</span>
                  <strong>{{ auditReadiness.score }}%</strong>
                </div>
                <div class="priority-copy">
                  {{ auditReadiness.summary }}
                </div>
              </div>

              <div v-if="priorityFindings.length > 0" class="priority-list">
                <div
                  v-for="item in priorityFindings"
                  :key="item.key"
                  class="priority-item"
                >
                  <div class="d-flex align-start justify-space-between ga-3">
                    <div>
                      <div class="d-flex align-center ga-2 mb-1">
                        <v-chip :color="item.color" size="x-small" variant="flat">
                          {{ item.severity }}
                        </v-chip>
                        <span class="font-weight-bold">{{ item.title }}</span>
                      </div>
                      <p class="text-body-2 mb-2">{{ item.risk }}</p>
                      <div class="text-caption text-medium-emphasis">
                        {{ item.action }}
                      </div>
                    </div>
                    <div class="priority-percent">{{ item.scorePercent }}%</div>
                  </div>
                </div>
              </div>

              <v-alert
                v-else
                density="comfortable"
                type="success"
                variant="tonal"
              >
                Aucun axe critique détecté. Le système paraît globalement stabilisé pour une préparation d’audit.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row class="mt-1" dense>
        <v-col cols="12" lg="7">
          <v-card class="h-100 details-card chart-card" rounded="xl" variant="outlined">
            <v-card-title>Axes à corriger en premier</v-card-title>
            <v-card-subtitle>
              Classement du moins maîtrisé au plus maîtrisé pour orienter les plans d’action.
            </v-card-subtitle>
            <v-card-text>
              <div class="chart-shell chart-shell--compact">
                <Bar v-if="entries.length > 0" :data="weaknessChartData" :options="weaknessChartOptions" />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="5">
          <v-card class="h-100 details-card chart-card" rounded="xl" variant="outlined">
            <v-card-title>Répartition du niveau de maîtrise</v-card-title>
            <v-card-subtitle>
              Vision simple des zones robustes, fragiles et critiques du système.
            </v-card-subtitle>
            <v-card-text>
              <div class="chart-shell chart-shell--compact">
                <Doughnut v-if="entries.length > 0" :data="maturityChartData" :options="doughnutChartOptions" />
              </div>
              <div class="mt-4 maturity-legend">
                <div v-for="item in maturityLegend" :key="item.label" class="maturity-legend-item">
                  <span class="legend-dot" :style="`background:${item.color}`" />
                  <span>{{ item.label }}</span>
                  <strong>{{ item.value }}</strong>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="mt-4 details-card" rounded="xl" variant="outlined">
        <v-card-title>Niveau d’atteinte des objectifs qualité (c2)</v-card-title>
        <v-card-subtitle>
          Taux d’atteinte par objectif pour le site sélectionné
        </v-card-subtitle>
        <v-card-text>
          <v-alert
            v-if="objectiveAchievementBands.length === 0"
            density="comfortable"
            type="info"
            variant="tonal"
          >
            Aucun objectif exploitable trouvé pour le site sélectionné.
          </v-alert>
          <template v-else>
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="text-body-2">
                Taux moyen d’atteinte: <strong>{{ objectivesAverageRate }}%</strong>
              </div>
              <v-chip color="primary" size="small" variant="tonal">
                {{ objectiveAchievementBands.length }} objectif(s)
              </v-chip>
            </div>
            <div
              v-for="objective in objectiveAchievementBands"
              :key="objective.key"
              class="objective-band-row"
            >
              <div class="objective-title">
                <div class="font-weight-medium text-truncate">{{ objective.label }}</div>
                <div v-if="objective.processLabel" class="text-caption text-medium-emphasis text-truncate">
                  {{ objective.processLabel }}
                </div>
              </div>
              <div class="objective-bar">
                <v-progress-linear
                  :color="objective.color"
                  height="10"
                  :model-value="objective.rate"
                  rounded
                />
              </div>
              <div class="objective-value">{{ objective.rate }}%</div>
            </div>
          </template>
        </v-card-text>
      </v-card>

      <v-row class="mt-1" dense>
        <v-col v-for="entry in entries" :key="entry.key" cols="12" md="6">
          <v-card class="h-100 details-card" rounded="xl" variant="outlined">
            <v-card-title class="text-subtitle-1">{{ entry.title }}</v-card-title>
            <v-card-text>
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption text-medium-emphasis">Niveau de maîtrise</span>
                <span class="text-caption font-weight-bold">{{ entry.scorePercent }}%</span>
              </div>
              <v-progress-linear
                class="mb-3"
                :color="entry.scoreColor"
                height="8"
                :model-value="entry.scorePercent"
                rounded
              />
              <p class="text-body-2 mb-2">{{ entry.summary }}</p>
              <v-chip
                v-for="metric in entry.metrics"
                :key="`${entry.key}-${metric.label}`"
                class="mr-2 mb-2"
                color="primary"
                size="small"
                variant="tonal"
              >
                {{ metric.label }}: {{ metric.value }}
              </v-chip>
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
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    RadarController,
    RadialLinearScale,
    Tooltip,
  } from 'chart.js'
  import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
  import { Bar, Doughnut, Radar } from 'vue-chartjs'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  ChartJS.register(
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    RadarController,
    RadialLinearScale,
    Tooltip,
  )

  const authStore = useAuthStore()
  const loading = ref(false)
  const error = ref('')
  const synthesis = ref<any>(null)
  const latestReview = ref<any>(null)
  let refreshTimer: ReturnType<typeof setInterval> | null = null

  const isoFocusItems = [
    { code: 'b', label: 'Changements des enjeux internes et externes' },
    { code: 'c1', label: 'Satisfaction client et retours des parties intéressées' },
    { code: 'c2', label: 'Degré de réalisation des objectifs qualité' },
    { code: 'c4', label: 'Non-conformités et actions correctives' },
    { code: 'c7', label: 'Performance des prestataires externes' },
    { code: 'e', label: 'Efficacité des actions face aux risques et opportunités' },
  ] as const

  const generatedAtLabel = computed(() => {
    const value = synthesis.value?.generated_at
    if (!value) return '-'
    return new Date(value).toLocaleString('fr-FR')
  })

  const periodLabel = computed(() => {
    const from = synthesis.value?.period?.from
    const to = synthesis.value?.period?.to
    if (!from || !to) return '-'
    return `${new Date(from).toLocaleDateString('fr-FR')} → ${new Date(to).toLocaleDateString('fr-FR')}`
  })

  const entries = computed(() => {
    const rows = synthesis.value?.entries
    if (!rows || typeof rows !== 'object') return []
    const order = ['b', 'c1', 'c2', 'c4', 'c7', 'e']
    return order
      .filter(key => rows[key])
      .map((key: string) => {
        const value: any = rows[key]
        const scorePercent = Number(value?.score_percent ?? 0)
        const scoreColor = scorePercent >= 80 ? 'success' : scorePercent >= 60 ? 'info' : scorePercent >= 40 ? 'warning' : 'error'
        const metricsRaw = value?.metrics && typeof value.metrics === 'object' ? value.metrics : {}
        const metrics = Object.entries(metricsRaw).map(([metricKey, metricValue]) => ({
          label: String(metricKey).replaceAll('_', ' '),
          value: metricValue,
        }))
        const rawTitle = String(value?.title || key)
        const title = rawTitle.replace(/^[a-z]\d?\)\s*/i, '')
        return {
          key,
          title,
          summary: value?.summary || '—',
          scorePercent,
          scoreColor,
          metrics,
        }
      })
  })

  const objectiveAchievementBands = computed(() => {
    const rows = Array.isArray(latestReview.value?.objectives_data) ? latestReview.value.objectives_data : []

    const normalizeRate = (row: Record<string, any>) => {
      const directCandidates = [
        row.achievement_percentage,
        row.achievement_rate,
        row.completion_rate,
        row.progress_percentage,
        row.rate,
      ]
      for (const candidate of directCandidates) {
        const value = Number(candidate)
        if (Number.isFinite(value)) {
          return Math.max(0, Math.min(100, Math.round(value * 100) / 100))
        }
      }

      const current = Number(row.current_value)
      const target = Number(row.target_value)
      if (Number.isFinite(current) && Number.isFinite(target) && target > 0) {
        const computed = (current / target) * 100
        return Math.max(0, Math.min(100, Math.round(computed * 100) / 100))
      }

      const status = String(row.status || '').toLowerCase()
      if (['achieved', 'completed', 'done'].includes(status)) return 100
      if (['in_progress', 'ongoing', 'started'].includes(status)) return 50
      if (['cancelled', 'failed', 'not_started'].includes(status)) return 0
      return 0
    }

    const resolveColor = (rate: number) => {
      if (rate >= 90) return 'success'
      if (rate >= 70) return 'info'
      if (rate >= 40) return 'warning'
      return 'error'
    }

    return rows.map((row: Record<string, any>, index: number) => {
      const rate = normalizeRate(row)
      const label = String(
        row.objective_title
        || row.title
          || row.name
        || row.description
          || `Objectif ${index + 1}`,
      ).trim()
      const processLabel = String(
        row.process_name
        || row.process_label
          || row.process_title
        || row.process_code
          || '',
      ).trim()

      return {
        key: `objective-${row.id ?? index}`,
        label,
        processLabel,
        rate,
        color: resolveColor(rate),
      }
    })
  })

  const objectivesAverageRate = computed(() => {
    if (objectiveAchievementBands.value.length === 0) return 0
    const sum = objectiveAchievementBands.value.reduce((acc, item) => acc + item.rate, 0)
    return Math.round((sum / objectiveAchievementBands.value.length) * 100) / 100
  })

  function resolveEntryColor (scorePercent: number): string {
    if (scorePercent >= 80) return '#16a34a'
    if (scorePercent >= 60) return '#0284c7'
    if (scorePercent >= 40) return '#f59e0b'
    return '#dc2626'
  }

  const radarChartData = computed(() => ({
    labels: entries.value.map(entry => entry.title),
    datasets: [
      {
        label: 'Niveau de maîtrise (%)',
        data: entries.value.map(entry => entry.scorePercent),
        backgroundColor: 'rgba(14, 116, 144, 0.18)',
        borderColor: '#0f766e',
        borderWidth: 2,
        pointBackgroundColor: entries.value.map(entry => resolveEntryColor(entry.scorePercent)),
        pointBorderColor: '#ffffff',
        pointHoverBackgroundColor: '#ffffff',
        pointHoverBorderColor: '#0f766e',
      },
    ],
  }))

  const radarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => `${context.formattedValue}% de maîtrise`,
        },
      },
    },
    scales: {
      r: {
        min: 0,
        max: 100,
        ticks: {
          stepSize: 20,
          backdropColor: 'transparent',
          color: '#64748b',
        },
        grid: {
          color: 'rgba(148, 163, 184, 0.28)',
        },
        angleLines: {
          color: 'rgba(148, 163, 184, 0.22)',
        },
        pointLabels: {
          color: '#0f172a',
          font: {
            size: 11,
            weight: 600,
          },
        },
      },
    },
  }

  const weaknessRanking = computed(() => {
    return [...entries.value]
      .toSorted((a, b) => a.scorePercent - b.scorePercent)
  })

  const weaknessChartData = computed(() => ({
    labels: weaknessRanking.value.map(entry => entry.title),
    datasets: [
      {
        label: 'Maîtrise (%)',
        data: weaknessRanking.value.map(entry => entry.scorePercent),
        backgroundColor: weaknessRanking.value.map(entry => resolveEntryColor(entry.scorePercent)),
        borderRadius: 8,
        borderSkipped: false,
      },
    ],
  }))

  const weaknessChartOptions = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => `${context.formattedValue}%`,
        },
      },
    },
    scales: {
      x: {
        min: 0,
        max: 100,
        grid: {
          color: 'rgba(148, 163, 184, 0.18)',
        },
        ticks: {
          color: '#64748b',
        },
      },
      y: {
        grid: {
          display: false,
        },
        ticks: {
          color: '#0f172a',
        },
      },
    },
  }

  const maturityLegend = computed(() => {
    const strong = entries.value.filter(entry => entry.scorePercent >= 80).length
    const watch = entries.value.filter(entry => entry.scorePercent >= 60 && entry.scorePercent < 80).length
    const fragile = entries.value.filter(entry => entry.scorePercent < 60).length
    return [
      { label: 'Robuste', value: strong, color: '#16a34a' },
      { label: 'À surveiller', value: watch, color: '#0284c7' },
      { label: 'Fragile / critique', value: fragile, color: '#dc2626' },
    ]
  })

  const maturityChartData = computed(() => ({
    labels: maturityLegend.value.map(item => item.label),
    datasets: [
      {
        data: maturityLegend.value.map(item => item.value),
        backgroundColor: maturityLegend.value.map(item => item.color),
        borderWidth: 0,
      },
    ],
  }))

  const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => `${context.label}: ${context.formattedValue} axe(x)`,
        },
      },
    },
  }

  const priorityMeta: Record<string, { severity: string, risk: string, action: string }> = {
    b: {
      severity: 'Contexte',
      risk: 'Les changements internes ou externes ne sont pas suffisamment traduits en impacts système.',
      action: 'Actualiser le contexte, les enjeux, les parties intéressées et vérifier les impacts sur le périmètre, les risques et les objectifs.',
    },
    c1: {
      severity: 'Client',
      risk: 'Le retour client n’est pas assez maîtrisé pour démontrer la satisfaction et la réactivité du système.',
      action: 'Consolider les réclamations, enquêtes, retours terrain et preuves de traitement avant audit.',
    },
    c2: {
      severity: 'Objectifs',
      risk: 'Les objectifs qualité ne prouvent pas encore assez clairement l’efficacité du système.',
      action: 'Sécuriser les indicateurs, les résultats, les écarts versus cibles et les plans de rattrapage.',
    },
    c4: {
      severity: 'NC / AC',
      risk: 'Les non-conformités et actions correctives peuvent révéler un défaut de traitement ou d’efficacité.',
      action: 'Revoir les causes, délais, preuves d’efficacité et clôtures des actions correctives prioritaires.',
    },
    c7: {
      severity: 'Fournisseurs',
      risk: 'La maîtrise des prestataires externes peut apparaître insuffisante au regard des exigences applicables.',
      action: 'Préparer l’évaluation fournisseurs, les critères, résultats et décisions de surveillance ou requalification.',
    },
    e: {
      severity: 'Risques',
      risk: 'Les actions liées aux risques et opportunités ne démontrent pas encore une efficacité suffisante.',
      action: 'Mettre à jour le registre, les traitements, responsables, preuves et revues d’efficacité.',
    },
  }

  const priorityFindings = computed(() => {
    return weaknessRanking.value
      .filter(entry => entry.scorePercent < 80)
      .slice(0, 4)
      .map(entry => ({
        key: entry.key,
        title: entry.title,
        scorePercent: entry.scorePercent,
        color: entry.scoreColor,
        severity: priorityMeta[entry.key]?.severity || 'Priorité',
        risk: priorityMeta[entry.key]?.risk || entry.summary,
        action: priorityMeta[entry.key]?.action || 'Analyser l’écart, documenter les preuves et lancer les actions de sécurisation.',
      }))
  })

  const auditReadiness = computed(() => {
    if (entries.value.length === 0) {
      return {
        score: 0,
        color: 'grey',
        label: 'Aucune lecture',
        summary: 'Chargez une synthèse pour évaluer la préparation du système avant audit.',
      }
    }

    const total = entries.value.reduce((sum, entry) => sum + entry.scorePercent, 0)
    const score = Math.round((total / entries.value.length) * 100) / 100

    if (score >= 85) {
      return {
        score,
        color: 'success',
        label: 'Prêt avec surveillance',
        summary: 'Le système paraît globalement solide, avec un besoin de vérification ciblée sur les preuves et la cohérence terrain.',
      }
    }
    if (score >= 70) {
      return {
        score,
        color: 'info',
        label: 'Préparation intermédiaire',
        summary: 'Le système est exploitable mais plusieurs axes demandent un renforcement avant de se présenter sereinement à l’audit.',
      }
    }
    if (score >= 50) {
      return {
        score,
        color: 'warning',
        label: 'Préparation fragile',
        summary: 'Des écarts de pilotage sont visibles. Il faut stabiliser les entrées de revue et les preuves d’efficacité avant audit.',
      }
    }
    return {
      score,
      color: 'error',
      label: 'Risque élevé',
      summary: 'Le niveau de maîtrise est insuffisant pour une préparation audit confortable. Une action de remise à niveau est recommandée.',
    }
  })

  async function loadSynthesis () {
    if (!authStore.currentSiteId) {
      error.value = 'Sélectionnez un site pour afficher la synthèse du SM.'
      synthesis.value = null
      return
    }
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/management-reviews/sm-synthesis', {
        params: { site_id: authStore.currentSiteId },
      })
      synthesis.value = data?.data || null
      await loadLatestReviewForObjectives()
    } catch (error_) {
      error.value = getErrorMessage(error_, 'Impossible de charger la synthèse du SM.')
      synthesis.value = null
      latestReview.value = null
    } finally {
      loading.value = false
    }
  }

  async function loadLatestReviewForObjectives () {
    if (!authStore.currentSiteId) {
      latestReview.value = null
      return
    }
    try {
      const { data } = await api.get('/management-reviews', {
        params: { site_id: authStore.currentSiteId, per_page: 200 },
      })
      const rows = Array.isArray(data?.data) ? data.data : []
      const parsed = rows
        .map((item: any) => item?.attributes || item)
        .filter((item: any) => item && typeof item === 'object')
        .toSorted((a: any, b: any) => {
          const da = new Date(a?.planned_date || a?.scheduled_date || a?.created_at || 0).getTime()
          const db = new Date(b?.planned_date || b?.scheduled_date || b?.created_at || 0).getTime()
          return db - da
        })
      latestReview.value = parsed[0] || null
    } catch {
      latestReview.value = null
    }
  }

  function setupAutoRefresh () {
    if (refreshTimer) clearInterval(refreshTimer)
    refreshTimer = setInterval(() => {
      void loadSynthesis()
    }, 60_000)
  }

  onMounted(() => {
    void loadSynthesis()
    setupAutoRefresh()
  })

  onBeforeUnmount(() => {
    if (refreshTimer) clearInterval(refreshTimer)
  })

  watch(() => authStore.currentSiteId, () => {
    void loadSynthesis()
  })
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 62%),
    radial-gradient(900px 360px at 100% 0%, rgba(5, 150, 105, 0.17), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.8));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.score-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
}

.focus-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.85);
}

.details-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.chart-card :deep(canvas) {
  max-width: 100%;
}

.chart-shell {
  position: relative;
  min-height: 320px;
}

.chart-shell--compact {
  min-height: 280px;
}

.entry-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.band-row {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 2fr) auto;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.band-label {
  color: #334155;
  font-size: 0.86rem;
}

.band-value {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
}

.objective-band-row {
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(0, 2fr) auto;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.objective-title {
  min-width: 0;
}

.objective-value {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
}

.priority-headline {
  display: grid;
  grid-template-columns: minmax(0, 180px) minmax(0, 1fr);
  gap: 16px;
  padding: 16px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(16, 185, 129, 0.08));
  border: 1px solid rgba(14, 116, 144, 0.1);
}

.priority-score {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.priority-score strong {
  font-size: 2rem;
  line-height: 1;
  color: #0f172a;
}

.priority-copy {
  color: #334155;
  font-size: 0.95rem;
  line-height: 1.5;
}

.priority-list {
  display: grid;
  gap: 12px;
}

.priority-item {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.92);
}

.priority-percent {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  white-space: nowrap;
}

.maturity-legend {
  display: grid;
  gap: 8px;
}

.maturity-legend-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #334155;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

@media (max-width: 960px) {
  .priority-headline {
    grid-template-columns: 1fr;
  }
}
</style>
