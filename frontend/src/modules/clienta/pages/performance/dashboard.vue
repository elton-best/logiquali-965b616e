<template>
  <ClientALayout current-page="performance-dashboard">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-chart-timeline-variant" title="Évaluation des Performances">
        <template #subtitle>
          Vue d'ensemble de la surveillance, des audits et des revues de direction
        </template>
      </PageHeader>

      <!-- Stats Overview -->
      <v-row class="mt-6">
        <v-col cols="12" md="3" sm="6">
          <v-card class="stat-card" rounded="xl" @click="router.push('/company/performance/surveillance')">
            <v-card-text class="text-center pa-6">
              <v-icon color="primary" size="48">mdi-chart-box-outline</v-icon>
              <div class="text-h4 mt-3 font-weight-bold">{{ stats.indicators }}</div>
              <div class="text-body-2 text-grey mt-1">Indicateurs suivis</div>
              <v-chip class="mt-3" color="success" size="small" variant="tonal">
                {{ stats.indicatorsOnTarget }} atteints
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card class="stat-card" rounded="xl" @click="router.push('/company/performance/audits')">
            <v-card-text class="text-center pa-6">
              <v-icon color="info" size="48">mdi-clipboard-check-outline</v-icon>
              <div class="text-h4 mt-3 font-weight-bold">{{ stats.audits }}</div>
              <div class="text-body-2 text-grey mt-1">Audits planifiés</div>
              <v-chip class="mt-3" color="warning" size="small" variant="tonal">
                {{ stats.auditsInProgress }} en cours
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card class="stat-card" rounded="xl" @click="router.push('/company/performance/revue-direction')">
            <v-card-text class="text-center pa-6">
              <v-icon color="purple" size="48">mdi-account-group</v-icon>
              <div class="text-h4 mt-3 font-weight-bold">{{ stats.reviews }}</div>
              <div class="text-body-2 text-grey mt-1">Revues de direction</div>
              <v-chip class="mt-3" color="primary" size="small" variant="tonal">
                {{ stats.reviewsScheduled }} planifiées
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card class="stat-card" rounded="xl">
            <v-card-text class="text-center pa-6">
              <v-icon color="success" size="48">mdi-star</v-icon>
              <div class="text-h4 mt-3 font-weight-bold">{{ stats.satisfaction }}/5</div>
              <div class="text-body-2 text-grey mt-1">Satisfaction client</div>
              <v-chip class="mt-3" color="success" size="small" variant="tonal">
                +{{ stats.satisfactionTrend }}% ce mois
              </v-chip>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Quick Actions -->
      <v-row class="mt-6">
        <v-col cols="12" md="8">
          <v-card rounded="xl">
            <v-card-title class="pa-6">Activités récentes</v-card-title>
            <v-divider />
            <v-card-text class="pa-0">
              <v-list>
                <v-list-item
                  v-for="activity in recentActivities"
                  :key="activity.id"
                  :prepend-icon="activity.icon"
                  :subtitle="activity.date"
                  :title="activity.title"
                >
                  <template #prepend>
                    <v-avatar :color="activity.color">
                      <v-icon>{{ activity.icon }}</v-icon>
                    </v-avatar>
                  </template>
                  <template #append>
                    <v-chip :color="activity.statusColor" size="small" variant="tonal">
                      {{ activity.status }}
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-title class="pa-6">Actions rapides</v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <v-list density="compact">
                <v-list-item
                  prepend-icon="mdi-plus-circle"
                  title="Nouveau suivi"
                  @click="router.push('/company/performance/surveillance')"
                />
                <v-list-item
                  prepend-icon="mdi-calendar-plus"
                  title="Planifier un audit"
                  @click="router.push('/company/performance/audits')"
                />
                <v-list-item
                  prepend-icon="mdi-account-multiple-plus"
                  title="Créer une revue"
                  @click="router.push('/company/performance/revue-direction')"
                />
                <v-list-item
                  prepend-icon="mdi-file-chart"
                  title="Voir les rapports"
                  @click="router.push('/company/performance/revue-direction?tab=reports')"
                />
              </v-list>
            </v-card-text>
          </v-card>

          <v-card class="mt-4" rounded="xl">
            <v-card-title class="pa-6">Conformité</v-card-title>
            <v-divider />
            <v-card-text class="text-center pa-6">
              <v-progress-circular
                color="success"
                :model-value="stats.conformityRate"
                :size="120"
                :width="12"
              >
                <span class="text-h4 font-weight-bold">{{ stats.conformityRate }}%</span>
              </v-progress-circular>
              <div class="text-body-2 text-grey mt-4">Taux de conformité global</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Upcoming Events -->
      <v-row class="mt-6">
        <v-col cols="12">
          <v-card rounded="xl">
            <v-card-title class="pa-6">Événements à venir</v-card-title>
            <v-divider />
            <v-card-text>
              <v-timeline density="compact" side="end">
                <v-timeline-item
                  v-for="event in upcomingEvents"
                  :key="event.id"
                  :dot-color="event.color"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ event.date }}</div>
                  </template>
                  <div>
                    <div class="font-weight-medium">{{ event.title }}</div>
                    <div class="text-caption text-grey">{{ event.description }}</div>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'

  const router = useRouter()

  const stats = ref({
    indicators: 12,
    indicatorsOnTarget: 8,
    audits: 5,
    auditsInProgress: 2,
    reviews: 3,
    reviewsScheduled: 1,
    satisfaction: 4.2,
    satisfactionTrend: 5,
    conformityRate: 92,
  })

  const recentActivities = ref([
    {
      id: 1,
      title: 'Audit interne ISO 9001 complété',
      date: 'Il y a 2 heures',
      icon: 'mdi-clipboard-check',
      color: 'success',
      status: 'Terminé',
      statusColor: 'success',
    },
    {
      id: 2,
      title: 'Nouveau rapport de satisfaction client',
      date: 'Il y a 5 heures',
      icon: 'mdi-star',
      color: 'warning',
      status: 'Nouveau',
      statusColor: 'info',
    },
    {
      id: 3,
      title: 'Revue de direction planifiée',
      date: 'Hier',
      icon: 'mdi-calendar',
      color: 'primary',
      status: 'Planifié',
      statusColor: 'primary',
    },
    {
      id: 4,
      title: 'Indicateur KPI mis à jour',
      date: 'Il y a 2 jours',
      icon: 'mdi-chart-line',
      color: 'info',
      status: 'Mis à jour',
      statusColor: 'success',
    },
  ])

  const upcomingEvents = ref([
    {
      id: 1,
      title: 'Audit processus production',
      description: 'Audit interne du processus de production',
      date: '15 Mars 2026',
      color: 'primary',
    },
    {
      id: 2,
      title: 'Revue de direction Q1',
      description: 'Revue trimestrielle avec la direction',
      date: '20 Mars 2026',
      color: 'purple',
    },
    {
      id: 3,
      title: 'Évaluation satisfaction client',
      description: 'Enquête de satisfaction trimestrielle',
      date: '25 Mars 2026',
      color: 'warning',
    },
  ])

  onMounted(() => {
    // Load dashboard data
  })
</script>

<style scoped>
.stat-card {
  cursor: pointer;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
</style>
