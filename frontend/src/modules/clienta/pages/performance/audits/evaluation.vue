<template>
  <ClientALayout current-page="performance-audits">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-clipboard-text-outline" title="Évaluation des auditeurs">
        <template #subtitle>
          M9-D5 - Fiche d'évaluation des auditeurs internes
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            color="secondary"
            prepend-icon="mdi-tune-vertical"
            variant="outlined"
            @click="goToCriteria"
          >
            Définir critères
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog">
            Nouvelle évaluation
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Compétences</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Suivi des auditeurs internes
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Mesurez la performance, détectez les besoins de formation et progressez.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Évaluations</span>
                <strong>{{ filteredItems.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>Score moyen</span>
                <strong>{{ averageScore }}%</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="section-title">
        <v-icon size="18">mdi-filter-cog-outline</v-icon>
        <span>Filtres & recherche</span>
      </div>
      <v-card class="filters-card registry-shell" rounded="xl" variant="tonal">
        <v-card-text>
          <div class="filters-inline">
            <v-text-field
              v-model="filters.search"
              clearable
              hide-details
              label="Rechercher (auditeur, audit, période)"
              prepend-inner-icon="mdi-magnify"
              rounded="lg"
              variant="outlined"
            />
            <div class="filter-actions">
              <v-btn
                :disabled="!filters.search.trim()"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="filters.search = ''"
              >
                Réinitialiser
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="section-title">
        <v-icon size="18">mdi-format-list-bulleted</v-icon>
        <span>Évaluations</span>
      </div>
      <v-row>
        <v-col cols="12">
          <v-card class="mt-4 registry-shell" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between">
              <span>Évaluations des auditeurs</span>
              <v-chip color="primary" size="small" variant="tonal">
                {{ filteredItems.length }} évaluations
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="table-guide">
                <v-chip size="small" variant="outlined">Score = niveau de maîtrise</v-chip>
                <v-chip size="small" variant="outlined">Audit = période d'évaluation</v-chip>
                <v-chip size="small" variant="outlined">Actions = consultation rapide</v-chip>
              </div>
              <div class="table-scroll-shell">
                <v-data-table class="custom-table" :headers="headers" :items="filteredItems" :loading="loading">
                  <template #[`item.score`]="{ item }">
                    <v-chip :color="scoreColor(item.score)" size="small" variant="tonal">
                      {{ item.score }}
                    </v-chip>
                  </template>
                  <template #[`item.actions`]="{ item: _item }">
                    <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openDialog" />
                  </template>
                </v-data-table>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'

  interface EvaluationRow {
    id: number
    auditor: string
    date: string
    audit: string
    score: string
  }

  const router = useRouter()
  const toast = useToast()
  const loading = ref(false)
  const items = ref<EvaluationRow[]>([])
  const filters = ref({
    search: '',
  })
  const headers = [
    { title: 'Auditeur', key: 'auditor' },
    { title: 'Date', key: 'date' },
    { title: 'Audit', key: 'audit' },
    { title: 'Score', key: 'score' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const filteredItems = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return items.value.filter(item => {
      if (!query) return true
      const haystack = `${item.auditor} ${item.audit} ${item.date}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  const averageScore = computed(() => {
    const scores = filteredItems.value
      .map(item => Number(item.score.replace('%', '')))
      .filter(value => Number.isFinite(value))
    if (scores.length === 0) return 0
    return Math.round(scores.reduce((a, b) => a + b, 0) / scores.length)
  })

  function formatDate (value?: string) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function scoreColor (score: string) {
    if (score === '—') return 'default'
    const value = Number(score.replace('%', ''))
    if (!Number.isFinite(value)) return 'default'
    if (value >= 80) return 'success'
    if (value >= 60) return 'warning'
    return 'error'
  }

  async function loadEvaluations () {
    loading.value = true
    try {
      const { data } = await api.get('/employee-evaluations', { params: { per_page: 200 } })
      const rows = Array.isArray(data?.data) ? data.data : []

      items.value = rows.map((item: any) => {
        const attrs = item?.attributes || item
        const evaluator = item?.relationships?.evaluator?.attributes
        const user = item?.relationships?.user?.attributes
        const totalScore = attrs?.total_score
        return {
          id: Number(item?.id || attrs?.id),
          auditor: evaluator?.name || user?.name || '—',
          date: formatDate(attrs?.created_at),
          audit: `${attrs?.period || 'Période non définie'} ${attrs?.year || ''}`.trim(),
          score: Number.isFinite(Number(totalScore)) ? `${Math.round(Number(totalScore))}%` : '—',
        }
      })
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les évaluations d’auditeurs.'))
      items.value = []
    } finally {
      loading.value = false
    }
  }

  function openDialog () {
    router.push('/company/performance/evaluations')
  }

  function goToCriteria () {
    router.push('/company/performance/criteria?form_type=evaluation_auditeur')
  }

  onMounted(loadEvaluations)
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 62%),
    radial-gradient(900px 360px at 100% 0%, rgba(5, 150, 105, 0.17), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.8));
  border: 1px solid rgba(15, 23, 42, 0.08);
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
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.84);
  border-radius: 12px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.filters-inline {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 12px;
}

.filter-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.table-guide {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 12px;
  flex-wrap: wrap;
}

.table-scroll-shell {
  overflow-x: auto;
}

.custom-table :deep(th:last-child),
.custom-table :deep(td:last-child) {
  white-space: nowrap;
  min-width: 110px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: rgb(15 23 42);
  margin: 10px 0 14px;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }

  .filters-inline {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
  }
}
</style>
