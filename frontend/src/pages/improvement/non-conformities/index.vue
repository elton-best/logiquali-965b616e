<template>
  <div class="nc-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Non-Conformités</h1>
        <p class="page-subtitle">
          Gestion et suivi des non-conformités QHSE
        </p>
      </div>
      <button class="btn-primary" @click="router.push('/improvement/non-conformities/create')">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Nouvelle NC
      </button>
    </div>

    <!-- Stats Cards -->
    <div v-if="statistics" class="stats-grid">
      <BaseCard class="stat-card">
        <p class="stat-label">Total</p>
        <p class="stat-value">{{ statistics.total }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">Ouvertes</p>
        <p class="stat-value stat-warning">{{ statistics.by_status.nouveau }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">En analyse</p>
        <p class="stat-value stat-primary">{{ statistics.by_status.en_analyse }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">En action</p>
        <p class="stat-value" style="color: #8B5CF6;">{{ statistics.by_status.action_en_cours }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">Closes</p>
        <p class="stat-value stat-success">{{ statistics.by_status.clos }}</p>
      </BaseCard>
    </div>

    <!-- Filters -->
    <NCFilters v-model="filters" :counts="filterCounts" @change="handleFilterChange" />

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="spinner" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
    </div>

    <!-- NC Grid -->
    <div v-else-if="nonConformities.length > 0" class="nc-grid">
      <NCCard
        v-for="nc in nonConformities"
        :key="nc.id"
        :nc="nc"
        @view="router.push(`/improvement/non-conformities/${nc.id}`)"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
      </svg>
      <h3 class="empty-title">Aucune non-conformité</h3>
      <p class="empty-text">Aucune non-conformité ne correspond aux filtres</p>
    </div>

    <!-- Pagination -->
    <div v-if="meta && links && meta.last_page > 1" class="pagination">
      <div class="pagination-info">
        Affichage de {{ meta.from ?? 0 }} à {{ meta.to ?? 0 }} sur {{ meta.total }} NC
      </div>
      <div class="pagination-buttons">
        <button class="btn-pagination" :disabled="!links.prev" @click="goToPage(meta.current_page - 1)">
          Précédent
        </button>
        <button class="btn-pagination" :disabled="!links.next" @click="goToPage(meta.current_page + 1)">
          Suivant
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { NCFilters as NCFiltersType } from '@/types/nonConformity'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import BaseCard from '@/components/common/BaseCard.vue'
  import NCCard from '@/components/nonconformities/NCCard.vue'
  import NCFilters from '@/components/nonconformities/NCFilters.vue'
  import { useNonConformityStore } from '@/stores/nonConformityStore'

  const router = useRouter()
  const ncStore = useNonConformityStore()

  const { nonConformities, loading, error, meta, links, statistics } = storeToRefs(ncStore)

  const filters = ref<NCFiltersType>({})

  const filterCounts = computed(() => ({
    total: statistics.value?.total || 0,
    nouveau: statistics.value?.by_status.nouveau || 0,
    en_analyse: statistics.value?.by_status.en_analyse || 0,
    action_en_cours: statistics.value?.by_status.action_en_cours || 0,
    overdue: statistics.value?.overdue_count || 0,
    major: statistics.value?.by_severity.majeur || 0,
  }))

  async function loadNC () {
    await ncStore.fetchNonConformities(filters.value)
    await ncStore.fetchStatistics()
  }

  function handleFilterChange (newFilters: NCFiltersType) {
    filters.value = newFilters
    loadNC()
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadNC()
  }

  onMounted(() => {
    loadNC()
  })
</script>

<style scoped>
.nc-page {
  padding: var(--spacing-6);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.page-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin: 0;
}

.page-subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  margin-top: var(--spacing-1);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-primary:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.stat-card {
  text-align: center;
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-1);
}

.stat-value {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.stat-warning { color: var(--color-warning); }
.stat-primary { color: var(--color-primary); }
.stat-success { color: var(--color-success); }

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 256px;
}

.error-state {
  background: var(--color-danger-light);
  border: 1px solid var(--color-danger);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-4);
  margin-bottom: var(--spacing-6);
  color: var(--color-danger-dark);
}

.nc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.empty-state {
  text-align: center;
  padding: var(--spacing-12) var(--spacing-4);
}

.empty-icon {
  width: 96px;
  height: 96px;
  margin: 0 auto var(--spacing-4);
  color: var(--color-text-tertiary);
}

.empty-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-2);
}

.empty-text {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: var(--spacing-6);
}

.pagination-info {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

.pagination-buttons {
  display: flex;
  gap: var(--spacing-2);
}

.btn-pagination {
  padding: var(--spacing-2) var(--spacing-3);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-md);
  background: white;
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-pagination:hover:not(:disabled) {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.btn-pagination:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .nc-grid {
    grid-template-columns: 1fr;
  }

  .pagination {
    flex-direction: column;
    gap: var(--spacing-3);
  }
}
</style>
