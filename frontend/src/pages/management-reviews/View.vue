<template>
  <v-container class="pa-6">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-btn class="mr-3" icon @click="goBack">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        {{ review ? review.title : 'Chargement...' }}
        <v-spacer />
        <v-chip v-if="review" :color="getStatusColor(review.status)">
          {{ getStatusLabel(review.status) }}
        </v-chip>
      </v-card-title>

      <v-divider />

      <v-card-text v-if="review" class="pa-6">
        <!-- General Info -->
        <v-row>
          <v-col cols="12" md="4">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informations Générales
              </v-card-title>
              <v-divider />
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Période</v-list-item-title>
                  <v-list-item-subtitle>{{ review.year }} - {{ review.quarter }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Date planifiée</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate(review.scheduled_date) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item v-if="review.planned_date">
                  <v-list-item-title>Date prévue</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate(review.planned_date) }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>

          <!-- KPI Summary -->
          <v-col cols="12" md="8">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-chart-line</v-icon>
                Indicateurs Clés (KPI)
              </v-card-title>
              <v-divider />
              <v-card-text v-if="review.kpi_data">
                <v-row>
                  <v-col class="text-center" cols="6" md="3">
                    <div class="text-h4 text-green">{{ review.kpi_data.quality_rate || 0 }}%</div>
                    <div class="text-caption">Taux Qualité</div>
                  </v-col>
                  <v-col class="text-center" cols="6" md="3">
                    <div class="text-h4 text-blue">{{ review.kpi_data.customer_satisfaction || 0 }}%</div>
                    <div class="text-caption">Satisfaction Client</div>
                  </v-col>
                  <v-col class="text-center" cols="6" md="3">
                    <div class="text-h4 text-orange">{{ review.kpi_data.delivery_time || 0 }}j</div>
                    <div class="text-caption">Délai Livraison</div>
                  </v-col>
                  <v-col class="text-center" cols="6" md="3">
                    <div class="text-h4 text-purple">{{ review.kpi_data.nc_count || 0 }}</div>
                    <div class="text-caption">Non-Conformités</div>
                  </v-col>
                </v-row>
              </v-card-text>
              <v-card-text v-else class="text-center text-grey">
                Aucune donnée KPI disponible
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Actions Data -->
        <v-row v-if="review.actions_data && review.actions_data.length > 0" class="mt-4">
          <v-col cols="12">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-check-circle</v-icon>
                Actions ({{ review.actions_data.length }})
              </v-card-title>
              <v-divider />
              <v-list>
                <v-list-item v-for="(action, index) in review.actions_data.slice(0, 5)" :key="index">
                  <template #prepend>
                    <v-icon>mdi-chevron-right</v-icon>
                  </template>
                  <v-list-item-title>{{ action.title || action.description }}</v-list-item-title>
                  <v-list-item-subtitle>{{ action.status }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>
        </v-row>

        <!-- Risks Data -->
        <v-row v-if="review.risks_data && review.risks_data.length > 0" class="mt-4">
          <v-col cols="12">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-alert</v-icon>
                Risques Identifiés ({{ review.risks_data.length }})
              </v-card-title>
              <v-divider />
              <v-list>
                <v-list-item v-for="(risk, index) in review.risks_data.slice(0, 5)" :key="index">
                  <template #prepend>
                    <v-chip :color="getRiskColor(risk.criticality)" size="small">
                      {{ risk.criticality }}
                    </v-chip>
                  </template>
                  <v-list-item-title>{{ risk.description }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>
        </v-row>

        <!-- Objectives Data -->
        <v-row v-if="review.objectives_data && review.objectives_data.length > 0" class="mt-4">
          <v-col cols="12">
            <v-card variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-target</v-icon>
                Objectifs ({{ review.objectives_data.length }})
              </v-card-title>
              <v-divider />
              <v-list>
                <v-list-item v-for="(obj, index) in review.objectives_data.slice(0, 5)" :key="index">
                  <template #prepend>
                    <v-icon>mdi-bullseye</v-icon>
                  </template>
                  <v-list-item-title>{{ obj.title || obj.description }}</v-list-item-title>
                  <v-list-item-subtitle>{{ obj.status }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn variant="text" @click="goBack">
          <v-icon left>mdi-arrow-left</v-icon>
          Retour
        </v-btn>
        <v-spacer />
        <v-btn
          v-if="review && review.status === 'planned'"
          color="success"
          @click="startReview"
        >
          <v-icon left>mdi-play</v-icon>
          Démarrer
        </v-btn>
        <v-btn
          v-if="review && review.status === 'in_progress'"
          color="primary"
          @click="generateData"
        >
          <v-icon left>mdi-refresh</v-icon>
          Générer Données
        </v-btn>
        <v-btn
          v-if="review && review.status === 'completed'"
          color="info"
          @click="exportDocx"
        >
          <v-icon left>mdi-file-word</v-icon>
          Export DOCX
        </v-btn>
        <v-btn color="primary" @click="editReview">
          <v-icon left>mdi-pencil</v-icon>
          Éditer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'

  const router = useRouter()
  const route = useRoute()
  const store = useManagementReviewStore()
  const reviewId = computed<number | null>(() => {
    const params = route.params as Record<string, unknown>
    const rawParam = params.id
    const rawId = Array.isArray(rawParam) ? rawParam[0] : rawParam
    const id = Number(rawId)
    return Number.isFinite(id) && id > 0 ? id : null
  })

  const review = computed(() =>
    store.reviews.find(r => r.id === reviewId.value),
  )

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      planned: 'Planifiée',
      in_progress: 'En cours',
      completed: 'Terminée',
      reported: 'Reportée',
    }
    return labels[status] || status
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      planned: 'blue',
      in_progress: 'orange',
      completed: 'green',
      reported: 'red',
    }
    return colors[status] || 'grey'
  }

  function getRiskColor (criticality: number) {
    if (criticality >= 15) return 'red'
    if (criticality >= 10) return 'orange'
    return 'green'
  }

  function formatDate (date?: string) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  const goBack = () => router.push('/management-reviews')
  function editReview () {
    if (reviewId.value === null) return
    router.push(`/management-reviews/${reviewId.value}/edit`)
  }

  async function startReview () {
    if (reviewId.value === null) return
    await store.startReview(reviewId.value)
  }

  async function generateData () {
    if (reviewId.value === null) return
    await store.generateInputData(reviewId.value)
  }

  async function exportDocx () {
    if (reviewId.value === null) return
    await store.exportReportDocx(reviewId.value)
  }

  onMounted(async () => {
    if (!review.value) {
      await store.fetchReviews()
    }
  })
</script>
