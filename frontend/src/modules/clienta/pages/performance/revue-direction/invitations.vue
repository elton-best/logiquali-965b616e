<template>
  <ClientALayout current-page="performance-revue">
    <v-container class="pa-6 review-form-theme" fluid>
      <PageHeader icon="mdi-email-outline" title="Invitations revue de direction">
        <template #subtitle>
          M12-D3 - Invitation à la revue de direction
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            prepend-icon="mdi-view-dashboard-outline"
            rounded="lg"
            variant="outlined"
            @click="router.push('/company/management-reviews')"
          >
            Pilotage complet
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog">
            Nouvelle invitation
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Convocations</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Invitations et participants
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Assurez la bonne préparation des revues et la participation des parties intéressées.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Invitations</span>
                <strong>{{ filteredItems.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>Terminées</span>
                <strong>{{ completedCount }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row class="mb-4" dense>
        <v-col cols="12" md="4">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">Planifiées</div>
                <div class="kpi-value">{{ plannedCount }}</div>
              </div>
              <v-icon color="info">mdi-calendar-month-outline</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">En cours</div>
                <div class="kpi-value">{{ inProgressCount }}</div>
              </div>
              <v-icon color="warning">mdi-progress-clock</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="kpi-card" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between">
              <div>
                <div class="kpi-label">Terminées</div>
                <div class="kpi-value">{{ completedCount }}</div>
              </div>
              <v-icon color="success">mdi-check-decagram-outline</v-icon>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="filters-card" rounded="xl" variant="tonal">
        <v-card-text>
          <div class="filters-inline">
            <v-text-field
              v-model="filters.search"
              clearable
              hide-details
              label="Rechercher (participant, date)"
              prepend-inner-icon="mdi-magnify"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-model="filters.status"
              clearable
              hide-details
              :items="statusFilterItems"
              label="Statut"
              rounded="lg"
              variant="outlined"
            />
            <div class="filter-actions">
              <v-btn
                :disabled="!filters.search.trim() && !filters.status"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="list-toolbar mt-4">
        <div>
          <div class="list-toolbar-title">Vue tableau ou cartes</div>
          <div class="list-toolbar-subtitle">Retrouvez les invitations dans un format plus opérationnel selon votre besoin.</div>
        </div>
        <v-btn-toggle
          v-model="viewMode"
          color="primary"
          density="comfortable"
          divided
          mandatory
          variant="outlined"
        >
          <v-btn icon="mdi-table-large" value="table" />
          <v-btn icon="mdi-view-grid-outline" value="cards" />
        </v-btn-toggle>
      </div>

      <v-row>
        <v-col cols="12">
          <v-card class="mt-4 registry-shell" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
              <span>Invitations envoyées</span>
              <v-chip color="primary" variant="tonal">{{ filteredItems.length }} invitation(s)</v-chip>
            </v-card-title>
            <v-card-text>
              <v-data-table v-if="viewMode === 'table'" :headers="headers" :items="filteredItems" :loading="loading">
                <template #[`item.status`]="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                    {{ statusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-email-fast-outline" size="small" variant="text" @click="openReview(item.id)" />
                </template>
              </v-data-table>
              <div v-else class="card-grid">
                <v-card
                  v-for="item in filteredItems"
                  :key="item.id"
                  class="entity-card"
                  elevation="0"
                  rounded="xl"
                >
                  <v-card-text class="pa-5">
                    <div class="entity-card-top">
                      <div>
                        <div class="entity-card-ref">Réunion programmée</div>
                        <div class="entity-card-title">{{ item.meeting_date }}</div>
                      </div>
                      <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                        {{ statusLabel(item.status) }}
                      </v-chip>
                    </div>
                    <div class="entity-card-meta">
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-account-group-outline</v-icon><span>{{ item.participants }}</span></div>
                    </div>
                    <div class="entity-card-actions">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-email-fast-outline"
                        rounded="lg"
                        variant="tonal"
                        @click="openReview(item.id)"
                      >
                        Ouvrir
                      </v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </div>
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
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  interface InvitationRow {
    id: number
    meeting_date: string
    participants: string
    status: string
  }

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const loading = ref(false)
  const items = ref<InvitationRow[]>([])
  const viewMode = ref<'table' | 'cards'>('table')
  const filters = ref({
    search: '',
    status: null as string | null,
  })
  const headers = [
    { title: 'Date réunion', key: 'meeting_date' },
    { title: 'Participants', key: 'participants' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const filteredItems = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return items.value.filter(item => {
      if (filters.value.status && item.status !== filters.value.status) return false
      if (!query) return true
      const haystack = `${item.meeting_date} ${item.participants}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  const statusFilterItems = [
    { title: 'Planifiée', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminée', value: 'completed' },
    { title: 'Reportée', value: 'reported' },
  ]

  const plannedCount = computed(() => items.value.filter(item => item.status === 'planned').length)
  const inProgressCount = computed(() => items.value.filter(item => item.status === 'in_progress').length)
  const completedCount = computed(() => items.value.filter(item => item.status === 'completed').length)

  function formatDate (value?: string) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function statusLabel (status?: string) {
    const map: Record<string, string> = {
      planned: 'Planifiée',
      in_progress: 'En cours',
      completed: 'Terminée',
      reported: 'Reportée',
    }
    return map[status || ''] || (status || '—')
  }

  function statusColor (status?: string) {
    const map: Record<string, string> = {
      planned: 'info',
      in_progress: 'warning',
      completed: 'success',
      reported: 'secondary',
    }
    return map[status || ''] || 'default'
  }

  async function loadInvitations () {
    loading.value = true
    try {
      const { data } = await api.get('/management-reviews', {
        params: { per_page: 200, site_id: authStore.currentSiteId || undefined },
      })
      const rows = Array.isArray(data?.data) ? data.data : []

      items.value = rows.map((item: any) => {
        const attrs = item?.attributes || item
        const participants = Array.isArray(attrs?.participants) ? attrs.participants.length : 0
        return {
          id: Number(item?.id || attrs?.id),
          meeting_date: formatDate(attrs?.planned_date || attrs?.scheduled_date),
          participants: `${participants} participant(s)`,
          status: attrs?.status || 'planned',
        }
      })
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les invitations de revue.'))
      items.value = []
    } finally {
      loading.value = false
    }
  }

  function openDialog () {
    router.push('/company/management-reviews?openCreate=1')
  }

  function openReview (id: number) {
    router.push(`/company/management-reviews/${id}`)
  }

  function resetFilters () {
    filters.value.search = ''
    filters.value.status = null
  }

  onMounted(loadInvitations)
  watch(() => authStore.currentSiteId, loadInvitations)
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

.kpi-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
}

.kpi-label {
  color: #64748b;
  font-size: 0.8rem;
}

.kpi-value {
  color: #0f172a;
  font-weight: 800;
  font-size: 1.35rem;
}

.list-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.list-toolbar-title {
  font-weight: 800;
  color: #0f172a;
}

.list-toolbar-subtitle {
  color: #64748b;
  font-size: 0.92rem;
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

.card-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 16px;
}

.entity-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.entity-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.entity-card-ref {
  color: #64748b;
  font-size: 0.82rem;
  margin-bottom: 4px;
}

.entity-card-title {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 800;
}

.entity-card-meta {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.entity-card-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #475569;
}

.entity-card-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
}

.review-form-theme :deep(.input-container),
.review-form-theme :deep(.select-container),
.review-form-theme :deep(.textarea-container),
.review-form-theme :deep(.v-field.v-field--variant-outlined) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
  border-color: rgba(148, 163, 184, 0.55) !important;
}

.review-form-theme :deep(.input-field),
.review-form-theme :deep(.select-field),
.review-form-theme :deep(.textarea-field),
.review-form-theme :deep(.v-field__input) {
  color: #0f172a !important;
}

.review-form-theme :deep(.input-field::placeholder),
.review-form-theme :deep(.select-field::placeholder),
.review-form-theme :deep(.textarea-field::placeholder),
.review-form-theme :deep(.v-field__input::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.input-label),
.review-form-theme :deep(.select-label),
.review-form-theme :deep(.textarea-label),
.review-form-theme :deep(.date-label),
.review-form-theme :deep(.v-label),
.review-form-theme :deep(.field-label) {
  color: #334155 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.v-field-label),
.review-form-theme :deep(.v-field-label--floating) {
  color: #475569 !important;
  opacity: 1 !important;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }

  .filters-inline {
    grid-template-columns: minmax(0, 1fr) minmax(220px, 0.5fr) auto;
    align-items: center;
  }

  .card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
