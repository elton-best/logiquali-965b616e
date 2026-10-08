<template>
  <ClientBLayout current-page="/clientb/satisfaction-forms">
    <v-container class="pa-6" fluid>
      <v-row v-if="loading" justify="center">
        <v-col class="text-center" cols="12">
          <v-progress-circular color="primary" indeterminate size="64" />
          <p class="mt-4">Chargement...</p>
        </v-col>
      </v-row>

      <template v-else-if="form">
        <!-- En-tête avec actions -->
        <v-row class="mb-4">
          <v-col cols="12" md="8">
            <v-btn
              class="mb-2"
              prepend-icon="mdi-arrow-left"
              variant="text"
              @click="goBack"
            >
              Retour
            </v-btn>
            <h1 class="text-h4 font-weight-bold">
              Fiche de Satisfaction {{ form.ref }}
            </h1>
            <div class="d-flex align-center mt-2">
              <v-chip
                class="mr-2"
                :color="form.satisfaction_color"
              >
                <v-icon start>{{ satisfactionIcon }}</v-icon>
                {{ form.satisfaction_level_label }}
              </v-chip>
              <v-chip
                class="mr-2"
                :color="getStatusColor(form.status)"
              >
                <v-icon size="small" start>{{ getStatusIcon(form.status) }}</v-icon>
                {{ form.status_label }}
              </v-chip>
              <span class="text-medium-emphasis">
                {{ form.survey_date_formatted }}
              </span>
            </div>
          </v-col>
          <v-col class="text-right" cols="12" md="4">
            <v-menu>
              <template #activator="{ props }">
                <v-btn
                  color="primary"
                  prepend-icon="mdi-dots-vertical"
                  v-bind="props"
                >
                  Actions
                </v-btn>
              </template>
              <v-list>
                <v-list-item
                  v-if="form.status === 'draft'"
                  prepend-icon="mdi-pencil"
                  @click="editForm"
                >
                  <v-list-item-title>Modifier</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="form.status === 'draft'"
                  prepend-icon="mdi-send"
                  @click="submitForm"
                >
                  <v-list-item-title>Soumettre</v-list-item-title>
                </v-list-item>
                <v-list-item
                  prepend-icon="mdi-download"
                  @click="downloadPDF"
                >
                  <v-list-item-title>Télécharger PDF</v-list-item-title>
                </v-list-item>
                <v-divider v-if="form.status === 'draft'" />
                <v-list-item
                  v-if="form.status === 'draft'"
                  prepend-icon="mdi-delete"
                  @click="deleteDialog = true"
                >
                  <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </v-col>
        </v-row>

        <!-- Contenu principal -->
        <v-row>
          <!-- Colonne gauche - Détails -->
          <v-col cols="12" md="8">
            <!-- Informations client -->
            <v-card class="mb-4" elevation="2">
              <v-card-title class="bg-primary text-white">
                <v-icon class="mr-2">mdi-clipboard-text</v-icon>
                Fiche de Satisfaction Client
              </v-card-title>
              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis">Nom de la structure</div>
                    <div class="text-h6">{{ form.client_name }}</div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis">Site</div>
                    <div class="text-h6">{{ form.site?.name || 'N/A' }}</div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis">Date de l'enquête</div>
                    <div class="text-h6">{{ form.survey_date_formatted }}</div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis">Référence</div>
                    <div class="text-h6">{{ form.ref }}</div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Critères d'évaluation -->
            <v-card class="mb-4" elevation="2">
              <v-card-title>
                <v-icon class="mr-2">mdi-star-check</v-icon>
                Critères d'évaluation
              </v-card-title>
              <v-card-text>
                <v-table>
                  <thead>
                    <tr>
                      <th>Critère</th>
                      <th class="text-center">Note</th>
                      <th class="text-center">Évaluation</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(criterion, key) in form.criteria" :key="key">
                      <td class="font-weight-medium">{{ criterion.label }}</td>
                      <td class="text-center">
                        <v-chip :color="criterion.color" size="small">
                          {{ criterion.score }}/4
                        </v-chip>
                      </td>
                      <td class="text-center">
                        <v-rating
                          :color="criterion.color"
                          density="compact"
                          :length="4"
                          :model-value="criterion.score"
                          readonly
                          size="small"
                        />
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card-text>
            </v-card>

            <!-- Recommandations -->
            <v-card v-if="form.recommendations" class="mb-4" elevation="2">
              <v-card-title>
                <v-icon class="mr-2">mdi-comment-text</v-icon>
                Recommandations pour amélioration
              </v-card-title>
              <v-card-text>
                <p class="text-body-1">{{ form.recommendations }}</p>
              </v-card-text>
            </v-card>

            <!-- Graphique radar -->
            <v-card elevation="2">
              <v-card-title>
                <v-icon class="mr-2">mdi-chart-radar</v-icon>
                Visualisation des critères
              </v-card-title>
              <v-card-text>
                <div class="text-center pa-4">
                  <canvas ref="radarChartRef" />
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Colonne droite - Score et métadonnées -->
          <v-col cols="12" md="4">
            <!-- Score global -->
            <v-card class="mb-4" :color="form.satisfaction_color" elevation="2" theme="dark">
              <v-card-text class="text-center pa-6">
                <v-icon class="mb-4" size="64">{{ satisfactionIcon }}</v-icon>
                <div class="text-h3 font-weight-bold mb-2">
                  {{ form.total_score }}/{{ form.max_score }}
                </div>
                <v-progress-linear
                  class="mb-4"
                  color="white"
                  height="20"
                  :model-value="form.satisfaction_percentage"
                  rounded
                >
                  <strong>{{ Math.round(form.satisfaction_percentage) }}%</strong>
                </v-progress-linear>
                <div class="text-h6">{{ form.satisfaction_level_label }}</div>
              </v-card-text>
            </v-card>

            <!-- Métadonnées -->
            <v-card class="mb-4" elevation="2">
              <v-card-title>Informations</v-card-title>
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title class="text-caption">Statut</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-chip :color="getStatusColor(form.status)" size="small">
                      {{ form.status_label }}
                    </v-chip>
                  </v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="form.submitted_at">
                  <v-list-item-title class="text-caption">Soumis le</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ formatDate(form.submitted_at) }}
                  </v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="form.reviewed_at">
                  <v-list-item-title class="text-caption">Examiné le</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ formatDate(form.reviewed_at) }}
                  </v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="form.reviewer">
                  <v-list-item-title class="text-caption">Examiné par</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ form.reviewer.name }}
                  </v-list-item-subtitle>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item>
                  <v-list-item-title class="text-caption">Créé le</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ formatDate(form.created_at) }}
                  </v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <v-list-item-title class="text-caption">Modifié le</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ formatDate(form.updated_at) }}
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>

            <!-- Répartition des scores -->
            <v-card elevation="2">
              <v-card-title>Répartition</v-card-title>
              <v-card-text>
                <div v-for="(criterion, key) in form.criteria" :key="key" class="mb-3">
                  <div class="d-flex justify-space-between mb-1">
                    <span class="text-caption">{{ criterion.label.substring(0, 25) }}...</span>
                    <span class="font-weight-bold">{{ criterion.score }}/4</span>
                  </div>
                  <v-progress-linear
                    :color="criterion.color"
                    height="8"
                    :model-value="(criterion.score / 4) * 100"
                    rounded
                  />
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </template>

      <!-- Dialogue de confirmation de suppression -->
      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card>
          <v-card-title class="text-h5">Confirmer la suppression</v-card-title>
          <v-card-text>
            Êtes-vous sûr de vouloir supprimer la fiche {{ form?.ref }} ?
            Cette action est irréversible.
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn @click="deleteDialog = false">Annuler</v-btn>
            <v-btn color="error" @click="confirmDelete">Supprimer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { Chart, registerables } from 'chart.js'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import ClientBLayout from '@/modules/clientb/components/ClientBLayout.vue'
  import clientSatisfactionFormService, { type ClientSatisfactionForm } from '@/services/clientSatisfactionFormService'

  Chart.register(...registerables)

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const loading = ref(true)
  const form = ref<ClientSatisfactionForm | null>(null)
  const deleteDialog = ref(false)
  const radarChartRef = ref<HTMLCanvasElement>()
  let radarChart: Chart | null = null

  const satisfactionIcon = computed(() => {
    if (!form.value) return 'mdi-emoticon'
    const percentage = form.value.satisfaction_percentage
    if (percentage >= 90) return 'mdi-emoticon-excited'
    if (percentage >= 70) return 'mdi-emoticon-happy'
    if (percentage >= 50) return 'mdi-emoticon-neutral'
    return 'mdi-emoticon-sad'
  })

  function getStatusColor (status: string): string {
    switch (status) {
      case 'draft': { return 'grey'
      }
      case 'submitted': { return 'warning'
      }
      case 'reviewed': { return 'success'
      }
      default: { return 'grey'
      }
    }
  }

  function getStatusIcon (status: string): string {
    switch (status) {
      case 'draft': { return 'mdi-file-document-edit'
      }
      case 'submitted': { return 'mdi-send'
      }
      case 'reviewed': { return 'mdi-check-circle'
      }
      default: { return 'mdi-file-document'
      }
    }
  }

  function formatDate (dateString: string): string {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  async function loadForm () {
    loading.value = true
    try {
      form.value = await clientSatisfactionFormService.getById(Number((route.params as Record<string, unknown>).id))
      // Attendre le prochain tick pour que le canvas soit rendu
      setTimeout(() => createRadarChart(), 100)
    } catch (error) {
      console.error('Erreur lors du chargement de la fiche:', error)
      toast.error('Erreur lors du chargement de la fiche')
      goBack()
    } finally {
      loading.value = false
    }
  }

  function createRadarChart () {
    if (!form.value || !radarChartRef.value) return

    if (radarChart) {
      radarChart.destroy()
    }

    const labels = Object.values(form.value.criteria).map(c => {
      const label = c.label
      // Couper les labels trop longs
      return label.length > 30 ? label.slice(0, 27) + '...' : label
    })

    const scores = Object.values(form.value.criteria).map(c => c.score)

    const ctx = radarChartRef.value.getContext('2d')
    if (!ctx) return

    radarChart = new Chart(ctx, {
      type: 'radar',
      data: {
        labels,
        datasets: [{
          label: 'Score',
          data: scores,
          backgroundColor: 'rgba(33, 150, 243, 0.2)',
          borderColor: 'rgb(33, 150, 243)',
          borderWidth: 2,
          pointBackgroundColor: 'rgb(33, 150, 243)',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: 'rgb(33, 150, 243)',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          r: {
            min: 0,
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

  function editForm () {
    router.push(`/clientb/satisfaction-forms/${form.value?.id}/edit`)
  }

  async function submitForm () {
    if (!form.value) return

    try {
      await clientSatisfactionFormService.submit(form.value.id)
      toast.success('Fiche soumise avec succès')
      loadForm()
    } catch (error) {
      console.error('Erreur lors de la soumission:', error)
      toast.error('Erreur lors de la soumission de la fiche')
    }
  }

  async function downloadPDF () {
    if (!form.value) return

    try {
      loading.value = true
      const blob = await clientSatisfactionFormService.exportPdf(form.value.id)

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `Fiche_Satisfaction_${form.value.ref}.pdf`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      toast.success('PDF téléchargé avec succès')
    } catch (error) {
      console.error('Erreur lors du téléchargement du PDF:', error)
      toast.error('Erreur lors du téléchargement du PDF')
    } finally {
      loading.value = false
    }
  }

  async function confirmDelete () {
    if (!form.value) return

    try {
      await clientSatisfactionFormService.delete(form.value.id)
      toast.success('Fiche supprimée avec succès')
      deleteDialog.value = false
      goBack()
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
      toast.error('Erreur lors de la suppression de la fiche')
    }
  }

  function goBack () {
    router.push('/clientb/satisfaction-forms')
  }

  onMounted(() => {
    loadForm()
  })
</script>

<style scoped>
  .chart-container {
    max-width: 600px;
    margin: 0 auto;
  }
</style>
