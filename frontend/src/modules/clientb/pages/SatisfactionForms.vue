<template>
  <ClientBLayout current-page="/clientb/satisfaction-forms">
    <!-- Header -->
    <v-card class="mb-6" elevation="0">
      <v-card-text class="pa-6">
        <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">
              📊 Satisfaction Client
            </h1>
            <p class="text-body-1 text-medium-emphasis mb-0">
              Évaluez des services de vos entreprises partenaires
            </p>
          </div>
          <div class="mt-4 mt-md-0">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="large"
              :to="{ path: '/clientb/satisfaction-forms/create' }"
            >
              Nouvelle évaluation
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Statistiques -->
    <div class="mb-6">
      <h2 class="text-h5 font-weight-bold mb-4">Vue d'ensemble</h2>
      <v-row v-if="!statsLoading">
        <v-col cols="12" md="3" sm="6">
          <v-card color="primary" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="primary" size="32">mdi-file-document-outline</v-icon>
                <v-chip color="primary" size="small" variant="flat">Total</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.total }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Nombre totale d'évaluations
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="success" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="success" size="32">mdi-emoticon-happy-outline</v-icon>
                <v-chip color="success" size="small" variant="flat">{{ averageSatisfaction }}%</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.average_score?.toFixed(1) || '0' }}/24
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Score moyen
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="warning" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="warning" size="32">mdi-file-edit-outline</v-icon>
                <v-chip color="warning" size="small" variant="flat">Brouillons</v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.drafts || 0 }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                À finaliser
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card color="info" elevation="0" variant="tonal">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <v-icon color="info" size="32">mdi-chart-line</v-icon>
                <v-chip
                  :color="stats.trend >= 0 ? 'success' : 'error'"
                  size="small"
                  variant="flat"
                >
                  {{ stats.trend >= 0 ? '+' : '' }}{{ stats.trend }}%
                </v-chip>
              </div>
              <h3 class="text-h4 font-weight-bold mb-1">
                {{ stats.submitted || 0 }}
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Fiches soumises
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

    <!-- Filters -->
    <v-card class="mb-6" elevation="0">
      <v-card-text class="pa-4">
        <v-row align="center">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              clearable
              density="compact"
              hide-details
              placeholder="Rechercher une fiche..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              @update:model-value="handleSearch"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="statusFilter"
              clearable
              density="compact"
              hide-details
              :items="statusOptions"
              placeholder="Tous les statuts"
              prepend-inner-icon="mdi-filter"
              variant="outlined"
              @update:model-value="loadForms"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="satisfactionFilter"
              clearable
              density="compact"
              hide-details
              :items="satisfactionOptions"
              placeholder="Niveau de satisfaction"
              prepend-inner-icon="mdi-emoticon-outline"
              variant="outlined"
              @update:model-value="loadForms"
            />
          </v-col>
          <v-col class="text-right" cols="12" md="2">
            <v-chip color="primary" variant="tonal">
              {{ totalForms }} fiche(s)
            </v-chip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Loading -->
    <div v-if="loading" class="mb-6">
      <v-skeleton-loader
        v-for="i in 3"
        :key="i"
        class="mb-4"
        type="article"
      />
    </div>

    <!-- Forms List -->
    <div v-else-if="forms.length > 0">
      <v-row>
        <v-col
          v-for="form in forms"
          :key="form.id"
          cols="12"
        >
          <v-card
            class="satisfaction-card"
            elevation="0"
            hover
            @click="viewForm(form)"
          >
            <v-card-text class="pa-4">
              <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
                <div class="flex-grow-1 mb-3 mb-md-0">
                  <div class="d-flex align-center mb-2">
                    <v-chip
                      class="me-2"
                      :color="getStatusColor(form.status)"
                      size="small"
                    >
                      {{ getStatusLabel(form.status) }}
                    </v-chip>
                    <v-chip
                      class="me-2"
                      :color="getSatisfactionColor(form.satisfaction_level)"
                      size="small"
                      variant="tonal"
                    >
                      {{ getSatisfactionLabel(form.satisfaction_level) }}
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
                      :color="getSatisfactionColor(form.satisfaction_level)"
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

                <div class="d-flex align-center">
                  <v-btn
                    v-if="form.status === 'draft'"
                    class="me-2"
                    color="warning"
                    size="small"
                    variant="tonal"
                    @click.stop="editForm(form)"
                  >
                    <v-icon start>mdi-pencil</v-icon>
                    Modifier
                  </v-btn>
                  <v-btn
                    color="primary"
                    size="small"
                    variant="tonal"
                    @click.stop="viewForm(form)"
                  >
                    Voir détails
                  </v-btn>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-6 d-flex justify-center">
        <v-pagination
          v-model="currentPage"
          :length="totalPages"
          @update:model-value="loadForms"
        />
      </div>
    </div>

    <!-- Empty State -->
    <v-card v-else class="text-center pa-12" elevation="0">
      <v-icon color="grey-lighten-1" size="80">mdi-clipboard-text-outline</v-icon>
      <h3 class="text-h5 font-weight-bold mt-4 mb-2">Aucune fiche trouvée</h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        {{ search || statusFilter || satisfactionFilter ?
          'Aucun résultat pour vos critères de recherche.' :
          'Commencez par effectuer votre première évaluation de satisfaction.'
        }}
      </p>
      <v-btn
        v-if="!search && !statusFilter && !satisfactionFilter"
        color="primary"
        prepend-icon="mdi-plus"
        size="large"
        :to="{ path: '/clientb/satisfaction-forms/create' }"
      >
        Effectuer ma première évaluation
      </v-btn>
    </v-card>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientBLayout from '@/modules/clientb/components/ClientBLayout.vue'
  import clientSatisfactionFormService from '@/services/clientSatisfactionFormService'

  const router = useRouter()

  // State
  const forms = ref<any[]>([])
  const stats = ref<any>({})
  const loading = ref(false)
  const statsLoading = ref(false)
  const search = ref('')
  const statusFilter = ref(null)
  const satisfactionFilter = ref(null)
  const currentPage = ref(1)
  const totalForms = ref(0)
  const totalPages = ref(1)

  // Options
  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Soumise', value: 'submitted' },
    { title: 'Révisée', value: 'reviewed' },
  ]

  const satisfactionOptions = [
    { title: 'Satisfait', value: 'satisfied' },
    { title: 'Moyennement satisfait', value: 'moderately_satisfied' },
    { title: 'Insatisfait', value: 'dissatisfied' },
  ]

  // Computed
  const averageSatisfaction = computed(() => {
    if (!stats.value.average_percentage) return 0
    return Math.round(stats.value.average_percentage)
  })

  // Methods
  async function loadForms () {
    loading.value = true
    try {
      const params: any = {
        page: currentPage.value,
        per_page: 10,
      }

      if (search.value) params.search = search.value
      if (statusFilter.value) params.status = statusFilter.value
      if (satisfactionFilter.value) params.satisfaction_level = satisfactionFilter.value

      const response = await clientSatisfactionFormService.getAll(params)
      forms.value = response.data
      totalForms.value = response.meta?.total || (Math.max(response.data.length, 0))
      totalPages.value = response.meta?.last_page || 1
    } catch (error) {
      console.error('Erreur lors du chargement des fiches:', error)
    } finally {
      loading.value = false
    }
  }

  async function loadStats () {
    statsLoading.value = true
    try {
      const response = await clientSatisfactionFormService.getStatistics()
      stats.value = response
    } catch (error) {
      console.error('Erreur lors du chargement des statistiques:', error)
    } finally {
      statsLoading.value = false
    }
  }

  function handleSearch () {
    currentPage.value = 1
    loadForms()
  }

  function viewForm (form: any) {
    router.push(`/clientb/satisfaction-forms/${form.id}`)
  }

  function editForm (form: any) {
    router.push(`/clientb/satisfaction-forms/${form.id}/edit`)
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

  function formatDate (dateString: string) {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
  }

  // Lifecycle
  onMounted(() => {
    loadForms()
    loadStats()
  })
</script>

<style scoped>
.satisfaction-card {
  border: 1px solid rgba(0, 0, 0, 0.12);
  transition: all 0.3s ease;
  cursor: pointer;
}

.satisfaction-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
}
</style>
