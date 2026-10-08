<template>
  <ClientBLayout current-page="/clientb/dashboard">
    <!-- Header Section -->
    <v-card class="mb-6 overflow-hidden" elevation="0">
      <v-card-text class="pa-6">
        <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">
              Bienvenue, {{ userName }} 👋
            </h1>
            <p class="text-body-1 text-medium-emphasis mb-0">
              Nous sommes ravis de vous revoir. Comment pouvons-nous vous aider aujourd'hui ?
            </p>
          </div>
          <div class="mt-4 mt-md-0">
            <v-chip
              color="primary"
              prepend-icon="mdi-calendar"
              size="large"
              variant="tonal"
            >
              {{ currentDate }}
            </v-chip>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Actions Rapides -->
    <div class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Actions rapides</h2>
      <v-row>
        <v-col cols="12" md="6">
          <v-card
            class="action-card h-100"
            elevation="0"
            hover
            :to="{ path: '/clientb/complaints/create' }"
          >
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-avatar
                  color="primary"
                  size="64"
                  variant="tonal"
                >
                  <v-icon size="36">mdi-message-alert-outline</v-icon>
                </v-avatar>
                <div class="ms-4 flex-grow-1">
                  <h3 class="text-h6 font-weight-bold mb-1">
                    💬 Déposer une plainte/réclamation
                  </h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Besoin d'aide ? Déposez votre réclamation en quelques clics
                  </p>
                </div>
              </div>
              <v-btn
                append-icon="mdi-arrow-right"
                block
                color="primary"
                size="large"
                variant="tonal"
              >
                Déposer une réclamation
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- <v-col cols="12" md="6">
          <v-card
            class="action-card h-100"
            elevation="0"
            hover
            :to="{ path: '/clientb/satisfaction-forms/create' }"
          >
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-avatar
                  color="success"
                  size="64"
                  variant="tonal"
                >
                  <v-icon size="36">mdi-star-check-outline</v-icon>
                </v-avatar>
                <div class="ms-4 flex-grow-1">
                  <h3 class="text-h6 font-weight-bold mb-1">
                    ⭐ Fiche de satisfaction
                  </h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Évaluez nos services
                  </p>
                </div>
              </div>
              <v-btn
                append-icon="mdi-arrow-right"
                block
                color="success"
                size="large"
                variant="tonal"
              >
                Remplir une fiche
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col> -->

        <v-col cols="12" md="6">
          <v-card
            class="action-card h-100"
            elevation="0"
            hover
            :to="{ path: '/clientb/satisfaction-forms/create' }"
          >
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-avatar
                  color="info"
                  size="64"
                  variant="tonal"
                >
                  <v-icon size="36">mdi-clipboard-text-outline</v-icon>
                </v-avatar>
                <div class="ms-4 flex-grow-1">
                  <h3 class="text-h6 font-weight-bold mb-1">
                    📋 Enquêtes de satisfaction
                  </h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Partagez votre avis pour améliorer les services des entreprises partenaires
                  </p>
                </div>
              </div>
              <v-btn
                append-icon="mdi-arrow-right"
                block
                color="info"
                size="large"
                variant="tonal"
              >
                Répondre aux enquêtes
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Statistiques -->
    <div class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Vue d'ensemble</h2>
      <v-row v-if="!statsLoading">
        <v-col cols="12" md="3" sm="6">
          <v-card color="primary" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="primary" size="32">mdi-message-text-outline</v-icon>
                <v-chip color="primary" size="small" variant="flat">Total</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.total_complaints }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Réclamations totales
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="warning" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="warning" size="32">mdi-clock-outline</v-icon>
                <v-chip color="warning" size="small" variant="flat">En attente</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.pending_complaints }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                En attente de traitement
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="info" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="info" size="32">mdi-progress-clock</v-icon>
                <v-chip color="info" size="small" variant="flat">En cours</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.in_progress_complaints }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                En cours de traitement
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="primary" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="primary" size="32">mdi-check-circle-outline</v-icon>
                <v-chip color="primary" size="small" variant="flat">{{ resolvedPercentage }}%</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.resolved_complaints }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Réclamations résolues
              </p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Loading skeleton for stats -->
      <v-row v-else>
        <v-col
          v-for="i in 4"
          :key="i"
          cols="12"
          md="3"
          sm="6"
        >
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>
    </div>

    <!-- Mes Plaintes -->
    <div v-if="!loading && recentComplaints.length > 0" class="mb-6">
      <div class="d-flex justify-space-between align-center mb-4">
        <h2 class="text-h5 font-weight-bold">Mes plaintes récentes</h2>
        <v-btn
          append-icon="mdi-arrow-right"
          color="primary"
          :to="{ path: '/clientb/complaints' }"
          variant="text"
        >
          Voir toutes mes plaintes
        </v-btn>
      </div>

      <v-row>
        <v-col
          v-for="complaint in recentComplaints"
          :key="complaint.id"
          cols="12"
        >
          <v-card class="cursor-pointer" elevation="0" hover @click="viewComplaint(complaint)">
            <v-card-text class="pa-4">
              <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
                <div class="flex-grow-1 mb-3 mb-md-0">
                  <div class="d-flex align-center mb-2">
                    <v-chip
                      class="me-2"
                      :color="complaintService.getStatusColor(complaint.status)"
                      size="small"
                    >
                      {{ complaintService.getStatusLabel(complaint.status) }}
                    </v-chip>
                    <span class="text-caption text-medium-emphasis">
                      <v-icon class="me-1" size="14">mdi-calendar</v-icon>
                      {{ formatDate(complaint.created_at) }}
                    </span>
                  </div>
                  <h4 class="text-subtitle-1 font-weight-medium mb-1">
                    {{ complaint.title }}
                  </h4>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Référence: {{ complaint.reference }}
                  </p>
                </div>
                <v-btn
                  color="primary"
                  variant="tonal"
                  @click.stop="viewComplaint(complaint)"
                >
                  Voir détails
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Loading state for complaints -->
    <div v-if="loading" class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Mes plaintes récentes</h2>
      <v-skeleton-loader type="article" />
    </div>

    <!-- Empty state -->
    <div v-if="!loading && recentComplaints.length === 0" class="mb-6">
      <v-card color="grey-lighten-4" elevation="0">
        <v-card-text class="pa-8 text-center">
          <v-icon class="mb-4" color="grey-lighten-1" size="64">
            mdi-message-alert-outline
          </v-icon>
          <h3 class="text-h6 font-weight-bold mb-2">
            Aucune plainte pour le moment
          </h3>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Vous n'avez pas encore déposé de réclamation.
          </p>
          <v-btn
            color="primary"
            :to="{ path: '/clientb/complaints/create' }"
            variant="tonal"
          >
            Déposer ma première réclamation
          </v-btn>
        </v-card-text>
      </v-card>
    </div>

    <!-- Mes Fiches de Satisfaction -->
    <div v-if="!satisfactionLoading && recentSatisfactionForms.length > 0" class="mb-6">
      <div class="d-flex justify-space-between align-center mb-4">
        <h2 class="text-h5 font-weight-bold">Mes fiches de satisfaction</h2>
        <v-btn
          append-icon="mdi-arrow-right"
          color="success"
          :to="{ path: '/clientb/satisfaction-forms' }"
          variant="text"
        >
          Voir toutes mes fiches
        </v-btn>
      </div>

      <v-row>
        <v-col
          v-for="form in recentSatisfactionForms"
          :key="form.id"
          cols="12"
        >
          <v-card class="cursor-pointer" elevation="0" hover @click="viewSatisfactionForm(form)">
            <v-card-text class="pa-4">
              <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
                <div class="flex-grow-1 mb-3 mb-md-0">
                  <div class="d-flex align-center mb-2">
                    <v-chip
                      class="me-2"
                      :color="getSatisfactionStatusColor(form.status)"
                      size="small"
                    >
                      {{ getSatisfactionStatusLabel(form.status) }}
                    </v-chip>
                    <v-chip
                      class="me-2"
                      :color="getSatisfactionLevelColor(form.satisfaction_level)"
                      size="small"
                      variant="tonal"
                    >
                      {{ getSatisfactionLevelLabel(form.satisfaction_level) }}
                    </v-chip>
                    <span class="text-caption text-medium-emphasis">
                      <v-icon class="me-1" size="14">mdi-calendar</v-icon>
                      {{ formatDate(form.survey_date) }}
                    </span>
                  </div>
                  <h4 class="text-subtitle-1 font-weight-medium mb-1">
                    {{ form.client_name }}
                  </h4>
                  <p class="text-body-2 text-medium-emphasis mb-2">
                    Référence: {{ form.ref }}
                  </p>

                  <!-- Score Progress -->
                  <div class="d-flex align-center">
                    <v-progress-linear
                      class="me-3"
                      :color="getSatisfactionLevelColor(form.satisfaction_level)"
                      height="8"
                      :model-value="form.satisfaction_percentage"
                      rounded
                      style="max-width: 200px;"
                    />
                    <span class="text-body-2 font-weight-bold">
                      {{ form.total_score }}/24 ({{ Math.round(form.satisfaction_percentage) }}%)
                    </span>
                  </div>
                </div>
                <v-btn
                  color="success"
                  variant="tonal"
                  @click.stop="viewSatisfactionForm(form)"
                >
                  Voir détails
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Enquêtes en attente -->
    <!-- Temporairement désactivé - À implémenter avec un système de notification -->
    <!--
    <div v-if="pendingSurveys.length > 0" class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Enquêtes en attente</h2>
      <v-card color="info" elevation="0" variant="tonal">
        <v-card-text class="pa-6">
          <div class="d-flex align-start">
            <v-icon class="me-4" color="info" size="48">
              mdi-clipboard-text-outline
            </v-icon>
            <div class="flex-grow-1">
              <h3 class="text-h6 font-weight-bold mb-2">
                {{ pendingSurveys.length }} enquête(s) à compléter
              </h3>
              <p class="text-body-2 mb-4">
                Votre avis compte ! Aidez-nous à améliorer nos services en répondant aux enquêtes suivantes :
              </p>
              <v-list bg-color="transparent" class="mb-4">
                <v-list-item
                  v-for="survey in pendingSurveys"
                  :key="survey.id"
                  class="px-0"
                >
                  <template #prepend>
                    <v-icon color="info">mdi-checkbox-blank-circle</v-icon>
                  </template>
                  <v-list-item-title>{{ survey.title }}</v-list-item-title>
                </v-list-item>
              </v-list>
              <v-btn
                append-icon="mdi-arrow-right"
                color="info"
                size="large"
                :to="{ path: '/clientb/surveys' }"
                variant="flat"
              >
                Répondre maintenant
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>
    -->

    <!-- Statistiques de satisfaction client -->
    <div v-if="!satisfactionStatsLoading && satisfactionStats" class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Statistiques de satisfaction client</h2>
      <v-row>
        <v-col cols="12" md="3">
          <v-card color="success" elevation="0" variant="tonal">
            <v-card-text class="pa-6 text-center">
              <v-icon class="mb-3" color="success" size="48">
                mdi-clipboard-check-outline
              </v-icon>
              <div class="text-h3 font-weight-bold mb-2">
                {{ satisfactionStats.total_forms }}
              </div>
              <div class="text-body-1 text-medium-emphasis">
                Fiches de satisfaction
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card color="info" elevation="0" variant="tonal">
            <v-card-text class="pa-6 text-center">
              <v-icon class="mb-3" color="info" size="48">
                mdi-star-outline
              </v-icon>
              <div class="text-h3 font-weight-bold mb-2">
                {{ satisfactionStats.avg_score }}
              </div>
              <div class="text-body-1 text-medium-emphasis">
                Score moyen /24
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card color="warning" elevation="0" variant="tonal">
            <v-card-text class="pa-6 text-center">
              <v-icon class="mb-3" color="warning" size="48">
                mdi-percent-outline
              </v-icon>
              <div class="text-h3 font-weight-bold mb-2">
                {{ satisfactionStats.avg_percentage }}%
              </div>
              <div class="text-body-1 text-medium-emphasis">
                Taux de satisfaction
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card color="primary" elevation="0" variant="tonal">
            <v-card-text class="pa-6 text-center">
              <v-icon class="mb-3" color="primary" size="48">
                mdi-chart-line
              </v-icon>
              <div class="text-h3 font-weight-bold mb-2">
                {{ satisfactionStats.satisfaction_level_label }}
              </div>
              <div class="text-body-1 text-medium-emphasis">
                Niveau global
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Loading skeleton for satisfaction stats -->
    <div v-if="satisfactionStatsLoading" class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Statistiques de satisfaction client</h2>
      <v-row>
        <v-col
          v-for="i in 4"
          :key="i"
          cols="12"
          md="3"
        >
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>
    </div>

    <!-- Aide rapide -->
    <div class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Aide rapide</h2>
      <v-row>
        <v-col
          v-for="faq in faqs"
          :key="faq.id"
          cols="12"
          md="4"
        >
          <v-card class="h-100" elevation="0" hover>
            <v-card-text class="pa-5">
              <div class="d-flex align-center mb-3">
                <v-avatar
                  class="me-3"
                  :color="faq.color"
                  size="40"
                  variant="tonal"
                >
                  <v-icon>{{ faq.icon }}</v-icon>
                </v-avatar>
                <h4 class="text-subtitle-1 font-weight-bold">
                  {{ faq.question }}
                </h4>
              </div>
              <p class="text-body-2 text-medium-emphasis mb-3">
                {{ faq.answer }}
              </p>
              <v-btn
                :color="faq.color"
                size="small"
                variant="text"
                @click="openFaqDetail(faq)"
              >
                En savoir plus
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <div class="text-center mt-6">
        <v-btn
          color="primary"
          prepend-icon="mdi-help-circle-outline"
          size="large"
          :to="{ path: '/clientb/support' }"
          variant="outlined"
        >
          Centre d'aide et support
        </v-btn>
      </div>
    </div>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import type { ClientBDashboardStats, RecentActivity } from '@/services/clientBDashboardService'
  import type { Complaint } from '@/services/complaintService'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { clientBDashboardService } from '@/services/clientBDashboardService'
  import clientSatisfactionFormService from '@/services/clientSatisfactionFormService'
  import { complaintService } from '@/services/complaintService'
  import { useAuthStore } from '@/stores/auth'
  import ClientBLayout from '../components/ClientBLayout.vue'

  const authStore = useAuthStore()
  const router = useRouter()

  // User data from auth store
  const userName = computed(() => authStore.userName || 'Utilisateur')

  // Current date
  const currentDate = computed(() => {
    const date = new Date()
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  })

  // Loading states
  const loading = ref(true)
  const statsLoading = ref(true)
  const activityLoading = ref(true)
  const satisfactionLoading = ref(true)
  const satisfactionStatsLoading = ref(true)

  // Dashboard data
  const stats = ref<ClientBDashboardStats>({
    total_complaints: 0,
    pending_complaints: 0,
    in_progress_complaints: 0,
    resolved_complaints: 0,
    closed_complaints: 0,
    avg_response_time_days: 0,
    resolution_rate: 0,
  })

  const recentActivity = ref<RecentActivity[]>([])
  const recentComplaints = ref<Complaint[]>([])
  const recentSatisfactionForms = ref<any[]>([])
  const satisfactionStats = ref<{
    total_forms: number
    avg_score: number
    avg_percentage: number
    satisfaction_level: string
    satisfaction_level_label: string
  } | null>(null)

  // Mock surveys data (à activer plus tard avec un système de notification)
  // const pendingSurveys = ref([
  //   {
  //     id: 1,
  //     title: 'Enquête de satisfaction service client',
  //   },
  // ])

  // Load dashboard data
  async function loadDashboard () {
    try {
      loading.value = true

      // Load stats
      statsLoading.value = true
      const statsData = await clientBDashboardService.getStats()
      stats.value = statsData
      statsLoading.value = false

      // Load recent activity
      activityLoading.value = true
      const activityData = await clientBDashboardService.getRecentActivity()
      recentActivity.value = activityData
      activityLoading.value = false

      // Load recent complaints
      const complaintsData = await complaintService.getComplaints({ per_page: 3 })
      recentComplaints.value = complaintsData.data || []

      // Load recent satisfaction forms
      satisfactionLoading.value = true
      const satisfactionData = await clientSatisfactionFormService.getAll({ per_page: 3 })
      recentSatisfactionForms.value = satisfactionData.data || []
      satisfactionLoading.value = false

      // Load satisfaction statistics
      await loadSatisfactionStats()
    } catch (error) {
      console.error('Error loading dashboard:', error)
    } finally {
      loading.value = false
    }
  }

  // Load satisfaction statistics
  async function loadSatisfactionStats () {
    try {
      satisfactionStatsLoading.value = true
      const allForms = await clientSatisfactionFormService.getAll()
      const forms = allForms.data || []

      if (forms.length === 0) {
        satisfactionStats.value = {
          total_forms: 0,
          avg_score: 0,
          avg_percentage: 0,
          satisfaction_level: 'none',
          satisfaction_level_label: 'Aucune donnée',
        }
        return
      }

      // Calculate statistics
      const totalScore = forms.reduce((sum: number, form: any) => sum + (form.total_score || 0), 0)
      const totalPercentage = forms.reduce((sum: number, form: any) => sum + (form.satisfaction_percentage || 0), 0)
      const avgScore = totalScore / forms.length
      const avgPercentage = totalPercentage / forms.length

      // Determine satisfaction level based on average percentage
      let satisfactionLevel = 'dissatisfied'
      let satisfactionLevelLabel = 'Insatisfait'

      if (avgPercentage >= 67) {
        satisfactionLevel = 'satisfied'
        satisfactionLevelLabel = 'Satisfait'
      } else if (avgPercentage >= 34) {
        satisfactionLevel = 'moderately_satisfied'
        satisfactionLevelLabel = 'Moyennement satisfait'
      } else {
        satisfactionLevel = 'dissatisfied'
        satisfactionLevelLabel = 'Insatisfait'
      }

      satisfactionStats.value = {
        total_forms: forms.length,
        avg_score: Math.round(avgScore * 10) / 10,
        avg_percentage: Math.round(avgPercentage),
        satisfaction_level: satisfactionLevel,
        satisfaction_level_label: satisfactionLevelLabel,
      }
    } catch (error) {
      console.error('Error loading satisfaction stats:', error)
      satisfactionStats.value = {
        total_forms: 0,
        avg_score: 0,
        avg_percentage: 0,
        satisfaction_level: 'none',
        satisfaction_level_label: 'Erreur',
      }
    } finally {
      satisfactionStatsLoading.value = false
    }
  }

  // Computed values
  const resolvedPercentage = computed(() => {
    if (stats.value.total_complaints === 0) return 0
    return Math.round((stats.value.resolved_complaints / stats.value.total_complaints) * 100)
  })

  // FAQs
  const faqs = ref([
    {
      id: 1,
      question: 'Comment déposer une plainte ?',
      answer: 'Cliquez sur "Déposer une plainte" et remplissez le formulaire en quelques minutes.',
      icon: 'mdi-help-circle',
      color: 'primary',
    },
    {
      id: 2,
      question: 'Combien de temps pour une réponse ?',
      answer: 'Nous nous engageons à répondre sous 48h maximum à toute réclamation.',
      icon: 'mdi-clock-outline',
      color: 'info',
    },
    {
      id: 3,
      question: 'Comment suivre ma plainte ?',
      answer: 'Consultez la section "Mes Plaintes" pour suivre l\'évolution en temps réel.',
      icon: 'mdi-chart-timeline-variant',
      color: 'success',
    },
  ])

  // Format date
  function formatDate (dateString: string) {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }

  // Navigate to complaint details
  function viewComplaint (complaint: Complaint) {
    router.push(`/clientb/complaints/${complaint.id}`)
  }

  // Navigate to satisfaction form details
  function viewSatisfactionForm (form: any) {
    router.push(`/clientb/satisfaction-forms/${form.id}`)
  }

  // Satisfaction helpers
  function getSatisfactionStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'warning',
      submitted: 'info',
      reviewed: 'success',
    }
    return colors[status] || 'grey'
  }

  function getSatisfactionStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      submitted: 'Soumise',
      reviewed: 'Révisée',
    }
    return labels[status] || status
  }

  function getSatisfactionLevelColor (level: string) {
    const colors: Record<string, string> = {
      satisfied: 'success',
      moderately_satisfied: 'warning',
      dissatisfied: 'error',
    }
    return colors[level] || 'grey'
  }

  function getSatisfactionLevelLabel (level: string) {
    const labels: Record<string, string> = {
      satisfied: 'Satisfait',
      moderately_satisfied: 'Moyennement satisfait',
      dissatisfied: 'Insatisfait',
    }
    return labels[level] || level
  }

  // FAQ detail handler
  function openFaqDetail (faq: any) {
    console.log('Opening FAQ:', faq)
  // TODO: Implement FAQ detail view or modal
  }

  // Initialize dashboard
  onMounted(() => {
    loadDashboard()
  })
</script>

<style scoped>
.action-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.action-card:hover {
  transform: translateY(-4px);
}

.h-100 {
  height: 100%;
}
</style>
