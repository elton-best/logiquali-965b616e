<template>
  <ClientBLayout current-page="/clientb/satisfaction-forms">
    <!-- Loading -->
    <div v-if="loading" class="pa-4">
      <v-skeleton-loader type="article, article" />
    </div>

    <!-- Content -->
    <div v-else-if="form">
      <!-- Header -->
      <v-card class="mb-6" elevation="0">
        <v-card-text class="pa-6">
          <div class="d-flex align-center mb-4">
            <v-btn
              class="me-2"
              icon
              variant="text"
              @click="goBack"
            >
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <div class="flex-grow-1">
              <div class="d-flex align-center gap-2 mb-2">
                <v-chip
                  :color="getSatisfactionColor(form.satisfaction_level)"
                  variant="tonal"
                >
                  <v-icon size="16" start>
                    {{ getSatisfactionIcon(form.satisfaction_level) }}
                  </v-icon>
                  {{ getSatisfactionLabel(form.satisfaction_level) }}
                </v-chip>
                <v-chip
                  :color="getStatusColor(form.status)"
                  size="small"
                  variant="flat"
                >
                  {{ getStatusLabel(form.status) }}
                </v-chip>
                <v-spacer />
                <div v-if="canEdit" class="d-flex gap-2">
                  <v-btn
                    color="warning"
                    prepend-icon="mdi-pencil"
                    variant="tonal"
                    @click="editForm"
                  >
                    Modifier
                  </v-btn>
                  <v-btn
                    v-if="form.status === 'draft'"
                    color="success"
                    prepend-icon="mdi-send"
                    variant="flat"
                    @click="submitForm"
                  >
                    Soumettre
                  </v-btn>
                  <v-btn
                    color="error"
                    prepend-icon="mdi-delete"
                    variant="outlined"
                    @click="confirmDelete"
                  >
                    Supprimer
                  </v-btn>
                </div>
              </div>
              <h1 class="text-h4 font-weight-bold mb-1">
                {{ form.client_name }}
              </h1>
              <div class="d-flex align-center gap-3 text-body-2 text-medium-emphasis">
                <span>
                  <v-icon class="me-1" size="16">mdi-tag</v-icon>
                  {{ form.ref }}
                </span>
                <span>
                  <v-icon class="me-1" size="16">mdi-calendar</v-icon>
                  {{ formatDate(form.survey_date) }}
                </span>
                <span>
                  <v-icon class="me-1" size="16">mdi-chart-box</v-icon>
                  Score: {{ form.total_score }}/24
                </span>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row>
        <!-- Main Content -->
        <v-col cols="12" md="8">
          <!-- Score Overview Card -->
          <v-card class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-chart-line</v-icon>
              Vue d'ensemble de la satisfaction
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <div class="text-center mb-6">
                <div class="d-flex align-center justify-center mb-4">
                  <v-progress-circular
                    :color="getSatisfactionColor(form.satisfaction_level)"
                    :model-value="form.satisfaction_percentage"
                    :size="120"
                    :width="12"
                  >
                    <div class="text-center">
                      <div class="text-h4 font-weight-bold">{{ Math.round(form.satisfaction_percentage) }}%</div>
                      <div class="text-caption text-medium-emphasis">Satisfaction</div>
                    </div>
                  </v-progress-circular>
                </div>
                <v-chip
                  :color="getSatisfactionColor(form.satisfaction_level)"
                  size="large"
                  variant="tonal"
                >
                  {{ getSatisfactionLabel(form.satisfaction_level) }}
                </v-chip>
              </div>

              <v-divider class="my-4" />

              <div class="text-center">
                <div class="text-h3 font-weight-bold mb-1">{{ form.total_score }}/24</div>
                <div class="text-body-2 text-medium-emphasis">Score total</div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Criteria Details -->
          <v-card class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-format-list-checks</v-icon>
              Détail des critères
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <v-list bg-color="transparent" lines="two">
                <v-list-item
                  v-for="(criterion, index) in criteriaList"
                  :key="index"
                  class="px-0 mb-2"
                >
                  <template #prepend>
                    <v-avatar
                      class="me-3"
                      :color="getCriterionColor(criterion.score)"
                      size="40"
                    >
                      <span class="text-h6 font-weight-bold">{{ criterion.score }}</span>
                    </v-avatar>
                  </template>

                  <v-list-item-title class="font-weight-medium mb-1">
                    {{ criterion.label }}
                  </v-list-item-title>

                  <v-list-item-subtitle>
                    <v-progress-linear
                      class="mt-1"
                      :color="getCriterionColor(criterion.score)"
                      height="8"
                      :model-value="(criterion.score / 4) * 100"
                      rounded
                    />
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Recommendations -->
          <v-card v-if="form.recommendations" class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-lightbulb-outline</v-icon>
              Recommandations
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <p class="text-body-1" style="white-space: pre-wrap;">
                {{ form.recommendations }}
              </p>
            </v-card-text>
          </v-card>

          <!-- Chart -->
          <v-card elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-chart-radar</v-icon>
              Visualisation radar
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <div class="chart-container">
                <canvas ref="chartCanvas" />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Info Card -->
          <v-card class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-information-outline</v-icon>
              Informations
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <div class="info-item mb-4">
                <div class="text-caption text-medium-emphasis mb-1">Référence</div>
                <div class="font-weight-medium">{{ form.ref }}</div>
              </div>

              <div class="info-item mb-4">
                <div class="text-caption text-medium-emphasis mb-1">Client</div>
                <div class="font-weight-medium">{{ form.client_name }}</div>
              </div>

              <div class="info-item mb-4">
                <div class="text-caption text-medium-emphasis mb-1">Date d'enquête</div>
                <div class="font-weight-medium">{{ formatDate(form.survey_date) }}</div>
              </div>

              <div class="info-item mb-4">
                <div class="text-caption text-medium-emphasis mb-1">Statut</div>
                <v-chip
                  :color="getStatusColor(form.status)"
                  size="small"
                >
                  {{ getStatusLabel(form.status) }}
                </v-chip>
              </div>

              <div class="info-item mb-4">
                <div class="text-caption text-medium-emphasis mb-1">Score total</div>
                <div class="font-weight-medium">{{ form.total_score }}/24 ({{ Math.round(form.satisfaction_percentage) }}%)</div>
              </div>

              <div class="info-item">
                <div class="text-caption text-medium-emphasis mb-1">Niveau de satisfaction</div>
                <v-chip
                  :color="getSatisfactionColor(form.satisfaction_level)"
                  size="small"
                  variant="tonal"
                >
                  {{ getSatisfactionLabel(form.satisfaction_level) }}
                </v-chip>
              </div>
            </v-card-text>
          </v-card>

          <!-- Timeline Card -->
          <v-card elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-timeline-clock</v-icon>
              Historique
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <v-timeline align="start" density="compact" side="end">
                <v-timeline-item
                  dot-color="primary"
                  icon="mdi-plus"
                  size="small"
                >
                  <div class="mb-1 font-weight-medium">Création</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(form.created_at) }}
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  v-if="form.submitted_at"
                  dot-color="info"
                  icon="mdi-send"
                  size="small"
                >
                  <div class="mb-1 font-weight-medium">Soumission</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(form.submitted_at) }}
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  v-if="form.reviewed_at"
                  dot-color="success"
                  icon="mdi-check-circle"
                  size="small"
                >
                  <div class="mb-1 font-weight-medium">Révision</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(form.reviewed_at) }}
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Error State -->
    <v-card v-else class="text-center pa-12" elevation="0">
      <v-icon color="error" size="80">mdi-alert-circle-outline</v-icon>
      <h3 class="text-h5 font-weight-bold mt-4 mb-2">Fiche introuvable</h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        La fiche de satisfaction demandée n'existe pas ou a été supprimée.
      </p>
      <v-btn color="primary" prepend-icon="mdi-arrow-left" @click="goBack">
        Retour à la liste
      </v-btn>
    </v-card>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title class="pa-6">
          <v-icon class="me-2" color="error">mdi-delete-alert</v-icon>
          Confirmer la suppression
        </v-card-title>
        <v-card-text class="pa-6">
          Êtes-vous sûr de vouloir supprimer cette fiche de satisfaction ? Cette action est irréversible.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">
            Annuler
          </v-btn>
          <v-btn color="error" variant="flat" @click="deleteForm">
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { Chart, registerables } from 'chart.js'
  import { computed, nextTick, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ClientBLayout from '@/modules/clientb/components/ClientBLayout.vue'
  import clientSatisfactionFormService from '@/services/clientSatisfactionFormService'

  Chart.register(...registerables)

  const router = useRouter()
  const route = useRoute()

  const form = ref<any>(null)
  const loading = ref(true)
  const deleteDialog = ref(false)
  const chartCanvas = ref<HTMLCanvasElement | null>(null)
  let chartInstance: Chart | null = null

  const canEdit = computed(() => {
    return form.value && (form.value.status === 'draft' || form.value.status === 'submitted')
  })

  const criteriaList = computed(() => {
    if (!form.value) return []

    return [
      { label: 'Amabilité et écoute du client', score: form.value.amabilite_ecoute || 0 },
      { label: 'Disponibilité et spontanéité', score: form.value.disponibilite_spontaneite || 0 },
      { label: 'Rapidité dans le traitement des requêtes', score: form.value.rapidite_traitement || 0 },
      { label: 'Respect des délais de livraison', score: form.value.respect_delais || 0 },
      { label: 'Conformité des produits livrés', score: form.value.conformite_produits || 0 },
      { label: 'Traitement des réclamations et plaintes', score: form.value.traitement_reclamations || 0 },
    ]
  })

  async function loadForm () {
    try {
      loading.value = true
      const id = String((route.params as Record<string, unknown>).id ?? '')
      const response = await clientSatisfactionFormService.getById(Number.parseInt(id))
      form.value = response

      // Wait for DOM update before creating chart
      await nextTick()
      createChart()
    } catch (error) {
      console.error('Erreur lors du chargement de la fiche:', error)
    } finally {
      loading.value = false
    }
  }

  function createChart () {
    if (!chartCanvas.value || !form.value) return

    // Destroy existing chart
    if (chartInstance) {
      chartInstance.destroy()
    }

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    chartInstance = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: [
          'Amabilité',
          'Disponibilité',
          'Rapidité',
          'Délais',
          'Conformité',
          'Réclamations',
        ],
        datasets: [{
          label: 'Score',
          data: [
            form.value.amabilite_ecoute || 0,
            form.value.disponibilite_spontaneite || 0,
            form.value.rapidite_traitement || 0,
            form.value.respect_delais || 0,
            form.value.conformite_produits || 0,
            form.value.traitement_reclamations || 0,
          ],
          backgroundColor: 'rgba(66, 165, 245, 0.2)',
          borderColor: 'rgb(66, 165, 245)',
          pointBackgroundColor: 'rgb(66, 165, 245)',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: 'rgb(66, 165, 245)',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          r: {
            beginAtZero: true,
            max: 4,
            ticks: {
              stepSize: 1,
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    })
  }

  function goBack () {
    router.push('/clientb/satisfaction-forms')
  }

  function editForm () {
    router.push(`/clientb/satisfaction-forms/${form.value.id}/edit`)
  }

  async function submitForm () {
    try {
      await clientSatisfactionFormService.submit(form.value.id)
      await loadForm()
    } catch (error) {
      console.error('Erreur lors de la soumission:', error)
    }
  }

  function confirmDelete () {
    deleteDialog.value = true
  }

  async function deleteForm () {
    try {
      await clientSatisfactionFormService.delete(form.value.id)
      deleteDialog.value = false
      router.push('/clientb/satisfaction-forms')
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }

  function formatDate (dateString: string) {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'warning',
      submitted: 'info',
      reviewed: 'success',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      submitted: 'Soumise',
      reviewed: 'Révisée',
    }
    return labels[status] || status
  }

  function getSatisfactionColor (level: string) {
    const colors: Record<string, string> = {
      satisfied: 'success',
      moderately_satisfied: 'warning',
      dissatisfied: 'error',
    }
    return colors[level] || 'grey'
  }

  function getSatisfactionLabel (level: string) {
    const labels: Record<string, string> = {
      satisfied: 'Satisfait',
      moderately_satisfied: 'Moyennement satisfait',
      dissatisfied: 'Insatisfait',
    }
    return labels[level] || level
  }

  function getSatisfactionIcon (level: string) {
    const icons: Record<string, string> = {
      satisfied: 'mdi-emoticon-happy',
      moderately_satisfied: 'mdi-emoticon-neutral',
      dissatisfied: 'mdi-emoticon-sad',
    }
    return icons[level] || 'mdi-emoticon'
  }

  function getCriterionColor (score: number) {
    if (score >= 3.5) return 'success'
    if (score >= 2.5) return 'info'
    if (score >= 1.5) return 'warning'
    return 'error'
  }

  onMounted(() => {
    loadForm()
  })
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 400px;
  width: 100%;
}

.info-item {
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.info-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
</style>
