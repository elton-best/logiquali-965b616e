<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-file-chart-outline" title="Rapport de satisfaction des PIP">
        <template #subtitle>
          M13-D6 - Rapport de Satisfaction des Parties Intéressées Pertinentes
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-file-document" @click="generateReport">
            Générer un rapport
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Consolidation</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Rapports de satisfaction
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Synthétisez les retours clients, personnel et prestataires.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Rapports</span>
                <strong>{{ filteredItems.length }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="filters-card" rounded="xl" variant="tonal">
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="filters.search"
                clearable
                label="Rechercher (type, période)"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.type"
                clearable
                :items="typeOptions"
                label="Type"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center justify-end" cols="12" md="3">
              <v-btn
                :disabled="!hasActiveFilters"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <v-row>
        <v-col cols="12">
          <v-card class="mt-4" rounded="xl">
            <v-card-title>Rapports générés</v-card-title>
            <v-card-text>
              <v-data-table :headers="headers" :items="filteredItems" :loading="loading">
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openSurvey(item.id)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import satisfactionSurveyService from '@/services/satisfactionSurveyService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  interface SatisfactionReportRow {
    id: number
    date: string
    period: string
    type: string
    type_key: 'client' | 'employee' | 'supplier' | 'unknown'
  }

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const loading = ref(false)
  const items = ref<SatisfactionReportRow[]>([])
  const filters = ref({
    search: '',
    type: null as string | null,
  })
  const typeOptions = [
    { title: 'Satisfaction client', value: 'client' },
    { title: 'Satisfaction personnel', value: 'employee' },
    { title: 'Satisfaction prestataire', value: 'supplier' },
  ]
  const hasActiveFilters = computed(() => Boolean(filters.value.search.trim() || filters.value.type))
  const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Période', key: 'period' },
    { title: 'Type', key: 'type' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const filteredItems = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return items.value.filter(item => {
      if (filters.value.type && item.type_key !== filters.value.type) return false
      if (!query) return true
      const haystack = `${item.type} ${item.period}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  function formatDate (value?: string) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function typeLabel (value?: string) {
    const map: Record<string, string> = {
      client: 'Satisfaction client',
      employee: 'Satisfaction personnel',
      supplier: 'Satisfaction prestataire',
    }
    return map[value || ''] || (value || '—')
  }

  async function loadReports () {
    loading.value = true
    try {
      const { data } = await satisfactionSurveyService.getSurveys({
        per_page: 200,
        site_id: authStore.currentSiteId || undefined,
      })

      items.value = data.map(row => ({
        id: Number(row.id),
        date: formatDate(row.created_at),
        period: row.period || String(row.year || '—'),
        type: typeLabel(row.type),
        type_key: (row.type === 'client' || row.type === 'employee' || row.type === 'supplier') ? row.type : 'unknown',
      }))
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les rapports de satisfaction.'))
      items.value = []
    } finally {
      loading.value = false
    }
  }

  function generateReport () {
    router.push('/company/performance/surveillance')
  }

  function resetFilters () {
    filters.value = {
      search: '',
      type: null,
    }
  }

  function openSurvey (id: number) {
    const row = items.value.find(item => item.id === id)
    if (!row) return

    if (row.type_key === 'client') {
      router.push(`/company/performance/surveillance/client/${id}`)
      return
    }

    if (row.type_key === 'employee') {
      router.push(`/company/performance/surveillance/personnel/${id}`)
      return
    }

    if (row.type_key === 'supplier') {
      router.push(`/company/performance/surveillance/prestataires/${id}`)
      return
    }

    router.push('/company/performance/surveillance')
  }

  onMounted(loadReports)
  watch(() => authStore.currentSiteId, loadReports)
</script>

<style scoped>
.hero {
  background: linear-gradient(135deg, #0f172a 0%, #8b5cf6 55%, #f472b6 100%);
  color: #fff;
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
}

.hero-badges {
  display: grid;
  gap: 12px;
}

.hero-badge {
  background: rgba(255, 255, 255, 0.14);
  border-radius: 16px;
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  font-weight: 600;
}

.hero-kicker {
  color: rgba(255, 255, 255, 0.7);
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }
}
</style>
