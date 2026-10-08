<template>
  <v-card class="mb-4" variant="outlined">
    <v-card-title class="d-flex align-center pa-4">
      <v-icon class="mr-3" color="primary">mdi-timeline-clock</v-icon>
      <span>Timeline des Revues de Direction</span>
      <v-spacer />
      <v-chip :color="getYearColor(selectedYear)" variant="flat">
        {{ selectedYear }}
      </v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-6">
      <!-- Year Selector -->
      <v-row class="mb-4">
        <v-col cols="12" md="6">
          <v-select
            v-model="selectedYear"
            density="comfortable"
            :items="years"
            label="Année"
            prepend-inner-icon="mdi-calendar"
            variant="outlined"
          />
        </v-col>
        <v-col class="text-right" cols="12" md="6">
          <v-btn-toggle v-model="viewMode" divided mandatory variant="outlined">
            <v-btn value="quarters">
              <v-icon>mdi-calendar-range</v-icon>
              Trimestres
            </v-btn>
            <v-btn value="months">
              <v-icon>mdi-calendar-month</v-icon>
              Mois
            </v-btn>
          </v-btn-toggle>
        </v-col>
      </v-row>

      <!-- Quarterly View -->
      <div v-if="viewMode === 'quarters'">
        <v-row>
          <v-col v-for="quarter in quarters" :key="quarter.value" cols="12" md="6">
            <v-card class="pa-4" :color="getQuarterCardColor(quarter.value)" variant="tonal">
              <div class="d-flex align-center mb-3">
                <v-icon class="mr-3" :color="getQuarterIconColor(quarter.value)" size="large">
                  {{ getQuarterIcon(quarter.value) }}
                </v-icon>
                <div>
                  <div class="text-h6">{{ quarter.label }}</div>
                  <div class="text-caption">{{ quarter.months }}</div>
                </div>
              </div>

              <v-divider class="my-3" />

              <!-- Reviews for this quarter -->
              <div v-if="getReviewsForQuarter(quarter.value).length > 0">
                <v-list bg-color="transparent" density="compact">
                  <v-list-item
                    v-for="review in getReviewsForQuarter(quarter.value)"
                    :key="review.id"
                    class="mb-2"
                    rounded
                    @click="review.id && viewReview(review.id)"
                  >
                    <template #prepend>
                      <v-icon :color="getStatusColor(review.status)" size="small">
                        {{ getStatusIcon(review.status) }}
                      </v-icon>
                    </template>
                    <v-list-item-title class="text-body-2">
                      {{ review.title }}
                    </v-list-item-title>
                    <v-list-item-subtitle class="text-caption">
                      {{ formatDate(review.planned_date) }}
                    </v-list-item-subtitle>
                    <template #append>
                      <v-chip :color="getStatusColor(review.status)" size="x-small" variant="flat">
                        {{ getStatusLabel(review.status) }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </v-list>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon color="grey-lighten-1" size="48">mdi-calendar-blank</v-icon>
                <div class="text-caption mt-2">Aucune revue planifiée</div>
              </div>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- Monthly View -->
      <div v-else>
        <v-timeline density="compact" side="end">
          <v-timeline-item
            v-for="month in monthsWithReviews"
            :key="month.number"
            :dot-color="month.reviews.length > 0 ? 'primary' : 'grey'"
            size="small"
          >
            <template #opposite>
              <div class="text-caption text-grey">{{ month.label }}</div>
            </template>

            <v-card variant="outlined">
              <v-card-title class="text-subtitle-2 pa-3">
                {{ month.label }} {{ selectedYear }}
                <v-chip v-if="month.reviews.length > 0" class="ml-2" size="x-small">
                  {{ month.reviews.length }}
                </v-chip>
              </v-card-title>

              <v-divider />

              <v-card-text v-if="month.reviews.length > 0" class="pa-3">
                <v-list density="compact">
                  <v-list-item
                    v-for="review in month.reviews"
                    :key="review.id"
                    class="px-0"
                    @click="review.id && viewReview(review.id)"
                  >
                    <template #prepend>
                      <v-icon :color="getStatusColor(review.status)" size="small">
                        {{ getStatusIcon(review.status) }}
                      </v-icon>
                    </template>
                    <v-list-item-title class="text-body-2">
                      {{ review.title }}
                    </v-list-item-title>
                    <v-list-item-subtitle class="text-caption">
                      {{ formatDate(review.planned_date) }}
                    </v-list-item-subtitle>
                    <template #append>
                      <v-chip :color="getStatusColor(review.status)" size="x-small">
                        {{ getStatusLabel(review.status).substring(0, 4) }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card-text>
              <v-card-text v-else class="pa-3 text-center text-caption text-grey">
                Aucune revue ce mois-ci
              </v-card-text>
            </v-card>
          </v-timeline-item>
        </v-timeline>
      </div>

      <!-- Summary Stats -->
      <v-row class="mt-6">
        <v-col cols="12">
          <v-divider class="mb-4" />
          <div class="text-subtitle-2 mb-3">
            <v-icon class="mr-2">mdi-chart-bar</v-icon>
            Statistiques {{ selectedYear }}
          </div>
        </v-col>
        <v-col cols="6" md="3">
          <div class="text-center">
            <div class="text-h4 text-blue">{{ stats.total }}</div>
            <div class="text-caption">Total</div>
          </div>
        </v-col>
        <v-col cols="6" md="3">
          <div class="text-center">
            <div class="text-h4 text-green">{{ stats.completed }}</div>
            <div class="text-caption">Terminées</div>
          </div>
        </v-col>
        <v-col cols="6" md="3">
          <div class="text-center">
            <div class="text-h4 text-orange">{{ stats.in_progress }}</div>
            <div class="text-caption">En cours</div>
          </div>
        </v-col>
        <v-col cols="6" md="3">
          <div class="text-center">
            <div class="text-h4 text-grey">{{ stats.planned }}</div>
            <div class="text-caption">Planifiées</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'

  const router = useRouter()
  const store = useManagementReviewStore()

  const currentYear = new Date().getFullYear()
  const selectedYear = ref(currentYear)
  const viewMode = ref('quarters')

  const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)

  const quarters = [
    { value: 'Q1', label: 'Trimestre 1', months: 'Janvier - Mars', icon: 'mdi-numeric-1-circle' },
    { value: 'Q2', label: 'Trimestre 2', months: 'Avril - Juin', icon: 'mdi-numeric-2-circle' },
    { value: 'Q3', label: 'Trimestre 3', months: 'Juillet - Septembre', icon: 'mdi-numeric-3-circle' },
    { value: 'Q4', label: 'Trimestre 4', months: 'Octobre - Décembre', icon: 'mdi-numeric-4-circle' },
  ]

  const monthsWithReviews = computed(() => {
    const months = [
      'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
    ]

    return months.map((label, index) => ({
      number: index + 1,
      label,
      reviews: store.reviews.filter(r => {
        const date = new Date(r.planned_date)
        return date.getFullYear() === selectedYear.value && date.getMonth() === index
      }),
    }))
  })

  function getReviewsForQuarter (quarter: string) {
    return store.reviews.filter(r => {
      const date = new Date(r.planned_date)
      return r.quarter === quarter && date.getFullYear() === selectedYear.value
    })
  }

  const stats = computed(() => {
    const yearReviews = store.reviews.filter(r => {
      const date = new Date(r.planned_date)
      return date.getFullYear() === selectedYear.value
    })

    return {
      total: yearReviews.length,
      completed: yearReviews.filter(r => r.status === 'completed').length,
      in_progress: yearReviews.filter(r => r.status === 'in_progress').length,
      planned: yearReviews.filter(r => r.status === 'planned').length,
    }
  })

  function getQuarterCardColor (quarter: string) {
    const hasReviews = getReviewsForQuarter(quarter).length > 0
    return hasReviews ? 'blue-lighten-5' : 'grey-lighten-4'
  }

  function getQuarterIconColor (quarter: string) {
    const hasReviews = getReviewsForQuarter(quarter).length > 0
    return hasReviews ? 'primary' : 'grey'
  }

  function getQuarterIcon (quarter: string) {
    const icons: Record<string, string> = {
      Q1: 'mdi-numeric-1-circle',
      Q2: 'mdi-numeric-2-circle',
      Q3: 'mdi-numeric-3-circle',
      Q4: 'mdi-numeric-4-circle',
    }
    return icons[quarter] || 'mdi-calendar'
  }

  function getYearColor (year: number) {
    return year === currentYear ? 'primary' : 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      planned: 'Plan',
      in_progress: 'Cours',
      completed: 'Term',
      reported: 'Rep',
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

  function getStatusIcon (status: string) {
    const icons: Record<string, string> = {
      planned: 'mdi-calendar-clock',
      in_progress: 'mdi-progress-clock',
      completed: 'mdi-check-circle',
      reported: 'mdi-file-document',
    }
    return icons[status] || 'mdi-circle'
  }

  function formatDate (date: string) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
    })
  }

  function viewReview (id: number) {
    router.push(`/management-reviews/${id}`)
  }
</script>
