<template>
  <div class="management-reviews pa-4">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2" size="large">mdi-calendar-check</v-icon>
        <span class="text-h5">Revues de Direction</span>
        <v-spacer />
        <v-btn color="primary" @click="createNew">
          <v-icon left>mdi-plus</v-icon>
          Nouvelle Revue
        </v-btn>
      </v-card-title>

      <v-card-text>
        <!-- Filters -->
        <v-row dense>
          <v-col cols="12" md="3">
            <v-select
              v-model="statusFilter"
              clearable
              density="compact"
              :items="statusOptions"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="yearFilter"
              clearable
              density="compact"
              :items="yearOptions"
              label="Année"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="quarterFilter"
              clearable
              density="compact"
              :items="quarterOptions"
              label="Trimestre"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <!-- Timeline View -->
      <v-container>
        <v-row>
          <v-col
            v-for="review in filteredReviews"
            :key="review.id"
            cols="12"
            md="6"
          >
            <v-card
              :color="getStatusColor(review.status) + '-lighten-5'"
              hover
              @click="viewReview(review)"
            >
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-2" :color="getStatusColor(review.status)">
                  {{ getStatusIcon(review.status) }}
                </v-icon>
                {{ review.title }}
              </v-card-title>

              <v-card-subtitle class="d-flex align-center mt-2">
                <v-chip class="mr-2" :color="getStatusColor(review.status)" size="small">
                  {{ getStatusLabel(review.status) }}
                </v-chip>
                <v-chip class="mr-2" size="small">
                  <v-icon left size="small">mdi-calendar</v-icon>
                  {{ formatDate(review.scheduled_date) }}
                </v-chip>
                <v-chip size="small">{{ review.year }} - {{ review.quarter }}</v-chip>
              </v-card-subtitle>

              <v-card-text v-if="review.kpi_data">
                <v-row dense>
                  <v-col class="text-center" cols="4">
                    <div class="text-h6">{{ review.kpi_data.quality_rate || 0 }}%</div>
                    <div class="text-caption">Qualité</div>
                  </v-col>
                  <v-col class="text-center" cols="4">
                    <div class="text-h6">{{ review.kpi_data.customer_satisfaction || 0 }}%</div>
                    <div class="text-caption">Satisfaction</div>
                  </v-col>
                  <v-col class="text-center" cols="4">
                    <div class="text-h6">{{ review.kpi_data.delivery_time || 0 }}j</div>
                    <div class="text-caption">Délais</div>
                  </v-col>
                </v-row>
              </v-card-text>

              <v-card-actions>
                <v-btn size="small" @click.stop="viewReview(review)">
                  <v-icon left>mdi-eye</v-icon>
                  Voir
                </v-btn>
                <v-btn size="small" @click.stop="editReview(review)">
                  <v-icon left>mdi-pencil</v-icon>
                  Éditer
                </v-btn>
                <v-btn
                  v-if="review.status === 'planned'"
                  color="success"
                  size="small"
                  @click.stop="startReview(review)"
                >
                  <v-icon left>mdi-play</v-icon>
                  Démarrer
                </v-btn>
                <v-btn
                  v-if="review.status === 'completed'"
                  color="info"
                  size="small"
                  @click.stop="exportReview(review)"
                >
                  <v-icon left>mdi-file-word</v-icon>
                  Export
                </v-btn>
                <v-spacer />
                <v-btn color="error" icon size="small" @click.stop="deleteReview(review)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-if="filteredReviews.length === 0">
          <v-col class="text-center py-8" cols="12">
            <v-icon color="grey-lighten-2" size="64">mdi-calendar-check</v-icon>
            <p class="text-h6 text-grey mt-4">Aucune revue trouvée</p>
          </v-col>
        </v-row>
      </v-container>
    </v-card>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'

  const router = useRouter()
  const store = useManagementReviewStore()

  const statusFilter = ref(null)
  const yearFilter = ref(null)
  const quarterFilter = ref(null)

  const statusOptions = [
    { value: 'planned', title: 'Planifiée' },
    { value: 'in_progress', title: 'En cours' },
    { value: 'completed', title: 'Terminée' },
    { value: 'reported', title: 'Reportée' },
  ]

  const quarterOptions = ['Q1', 'Q2', 'Q3', 'Q4']

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - i)
  })

  const filteredReviews = computed(() => {
    let items = store.reviews

    if (statusFilter.value) {
      items = items.filter(item => item.status === statusFilter.value)
    }

    if (yearFilter.value) {
      items = items.filter(item => item.year === yearFilter.value)
    }

    if (quarterFilter.value) {
      items = items.filter(item => item.quarter === quarterFilter.value)
    }

    return items
  })

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      planned: 'blue',
      in_progress: 'orange',
      completed: 'green',
      reported: 'red',
    }
    return colors[status] || 'grey'
  }

  function getStatusIcon (status: string) {
    const icons: Record<string, string> = {
      planned: 'mdi-calendar-clock',
      in_progress: 'mdi-progress-clock',
      completed: 'mdi-check-circle',
      reported: 'mdi-file-document',
    }
    return icons[status] || 'mdi-help-circle'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      planned: 'Planifiée',
      in_progress: 'En cours',
      completed: 'Terminée',
      reported: 'Reportée',
    }
    return labels[status] || status
  }

  function formatDate (date?: string) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  const createNew = () => router.push('/management-reviews/create')
  const viewReview = (item: any) => router.push(`/management-reviews/${item.id}`)
  const editReview = (item: any) => router.push(`/management-reviews/${item.id}/edit`)
  async function startReview (item: any) {
    await store.startReview(item.id)
  }
  async function exportReview (item: any) {
    await store.exportReportDocx(item.id)
  }
  async function deleteReview (item: any) {
    if (confirm(`Supprimer la revue "${item.title}"?`)) {
      await store.deleteReview(item.id)
    }
  }

  onMounted(() => {
    store.fetchReviews()
  })
</script>
