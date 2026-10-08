<template>
  <v-container class="audits-page" fluid>
    <!-- Header avec actions -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <v-icon class="title-icon" icon="mdi-clipboard-check-outline" size="36" />
          Audits QHSE
        </h1>
        <p class="page-subtitle">
          Planification et réalisation des audits ISO 9001/14001/45001
        </p>
      </div>
      <div class="header-actions">
        <button
          :class="['view-btn', { 'view-btn-active': viewMode === 'calendar' }]"
          @click="viewMode = 'calendar'"
        >
          Calendrier
        </button>
        <button
          :class="['view-btn', { 'view-btn-active': viewMode === 'list' }]"
          @click="viewMode = 'list'"
        >
          Liste
        </button>
        <button
          class="btn btn-primary"
          @click="router.push('/improvement/audits/create')"
        >
          <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Planifier un audit
        </button>
      </div>
    </div>

    <!-- Stats Widget -->
    <AuditStatsWidget v-if="statistics" class="stats-widget" :statistics="statistics" />

    <!-- Calendar View -->
    <BaseCard v-if="viewMode === 'calendar' && !loading">
      <AuditCalendar
        :events="calendarEvents"
        @date-click="handleDateClick"
        @event-click="handleEventClick"
      />
    </BaseCard>

    <!-- List View -->
    <div v-else-if="viewMode === 'list'">
      <!-- Filters -->
      <AuditFilters
        v-model="filters"
        class="filters-section"
        :counts="filterCounts"
        @change="handleFilterChange"
      />

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner" />
      </div>

      <!-- Audits Grid -->
      <div v-else-if="audits.length > 0" class="audits-grid">
        <AuditCard
          v-for="audit in audits"
          :key="audit.id"
          :audit="audit"
          @view="router.push(`/improvement/audits/${audit.id}`)"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <h3 class="empty-title">Aucun audit</h3>
        <p class="empty-text">Planifiez votre premier audit</p>
      </div>

      <!-- Pagination -->
      <div v-if="paginationMeta.last_page > 1" class="pagination">
        <span class="pagination-info">
          {{ paginationMeta.from }} - {{ paginationMeta.to }} sur {{ paginationMeta.total }}
        </span>
        <div class="pagination-buttons">
          <button
            class="pagination-btn"
            :disabled="!paginationLinks.prev"
            @click="goToPage(paginationMeta.current_page - 1)"
          >
            Précédent
          </button>
          <button
            class="pagination-btn"
            :disabled="!paginationLinks.next"
            @click="goToPage(paginationMeta.current_page + 1)"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>
  </v-container>
</template>

<script setup lang="ts">
  import type { AuditFilters as Filters } from '@/types/audit'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AuditCalendar from '@/components/audits/AuditCalendar.vue'
  import AuditCard from '@/components/audits/AuditCard.vue'
  import AuditFilters from '@/components/audits/AuditFilters.vue'
  import AuditStatsWidget from '@/components/audits/AuditStatsWidget.vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import { useAuditStore } from '@/stores/auditStore'

  const router = useRouter()
  const auditStore = useAuditStore()

  const { audits, loading, meta, links, statistics, calendarEvents } = storeToRefs(auditStore)

  const viewMode = ref<'calendar' | 'list'>('calendar')
  const filters = ref<Filters>({})

  const filterCounts = computed(() => ({
    total: statistics.value?.total || 0,
    planned: statistics.value?.by_status.planned || 0,
    in_progress: statistics.value?.by_status.in_progress || 0,
    overdue: 0,
    completed: statistics.value?.by_status.completed || 0,
  }))

  const paginationMeta = computed(() => meta.value ?? {
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 0,
    to: 0,
    total: 0,
  })

  const paginationLinks = computed(() => links.value ?? {
    first: null,
    last: null,
    prev: null,
    next: null,
  })

  async function loadAudits () {
    await auditStore.fetchAudits(filters.value)
    await auditStore.fetchStatistics()
  }

  function handleFilterChange (newFilters: Filters) {
    filters.value = newFilters
    loadAudits()
  }

  function handleEventClick (event: any) {
    router.push(`/improvement/audits/${event.id}`)
  }

  function handleDateClick (date: Date) {
    router.push(`/improvement/audits/create?date=${date.toISOString()}`)
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadAudits()
  }

  onMounted(() => {
    loadAudits()
  })
</script>

<style scoped>
.audits-page {
  padding: var(--spacing-6);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
  gap: var(--spacing-4);
  flex-wrap: wrap;
}

.page-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-2);
}

.title-icon {
  color: var(--color-primary-500);
}

.page-subtitle {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
}

.header-actions {
  display: flex;
  gap: var(--spacing-3);
}

.view-btn {
  padding: var(--spacing-2) var(--spacing-4);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-base);
  min-height: 44px;
}

.view-btn:hover {
  background: var(--bg-secondary);
  border-color: var(--border-color-hover);
}

.view-btn:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.view-btn-active {
  background: var(--color-primary-500);
  color: white;
  border-color: var(--color-primary-500);
}

.btn-icon {
  width: 20px;
  height: 20px;
}

.stats-widget {
  margin-bottom: var(--spacing-6);
}

.filters-section {
  margin-bottom: var(--spacing-6);
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: var(--spacing-12) 0;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid var(--color-primary-100);
  border-top-color: var(--color-primary-500);
  border-radius: var(--radius-full);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.audits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--spacing-4);
}

.empty-state {
  text-align: center;
  padding: var(--spacing-12) 0;
}

.empty-icon {
  width: 96px;
  height: 96px;
  margin: 0 auto var(--spacing-4);
  color: var(--text-tertiary);
}

.empty-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
  margin-bottom: var(--spacing-2);
}

.empty-text {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-4);
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: var(--spacing-6);
  gap: var(--spacing-4);
  flex-wrap: wrap;
}

.pagination-info {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.pagination-buttons {
  display: flex;
  gap: var(--spacing-2);
}

.pagination-btn {
  padding: var(--spacing-2) var(--spacing-3);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: var(--font-size-sm);
  cursor: pointer;
  transition: all var(--transition-base);
  min-height: 40px;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--bg-secondary);
  border-color: var(--border-color-hover);
}

.pagination-btn:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .audits-page {
    padding: var(--spacing-4);
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .audits-grid {
    grid-template-columns: 1fr;
  }
}
</style>
