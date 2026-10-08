<template>
  <div class="documents-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Documents</h1>
        <p class="page-subtitle">
          Gestion documentaire ISO 9001:2015 §7.5
        </p>
      </div>
      <div class="header-actions">
        <button
          :class="['view-btn', { 'view-btn-active': viewMode === 'grid' }]"
          @click="viewMode = 'grid'"
        >
          Grille
        </button>
        <button
          :class="['view-btn', { 'view-btn-active': viewMode === 'tree' }]"
          @click="viewMode = 'tree'"
        >
          Arborescence
        </button>
        <button
          class="btn btn-primary"
          @click="router.push('/documents/upload')"
        >
          <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Upload
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="statistics" class="stats-grid">
      <BaseCard class="stat-card">
        <p class="stat-label">Total</p>
        <p class="stat-value">{{ statistics.total }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">Brouillons</p>
        <p class="stat-value stat-neutral">{{ statistics.by_status?.draft || 0 }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">En révision</p>
        <p class="stat-value stat-info">{{ statistics.by_status?.under_review || 0 }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">Validés</p>
        <p class="stat-value stat-success">{{ statistics.by_status?.approved || 0 }}</p>
      </BaseCard>
      <BaseCard class="stat-card">
        <p class="stat-label">Révision échue</p>
        <p class="stat-value stat-danger">{{ overdueCount }}</p>
      </BaseCard>
    </div>

    <!-- Filters -->
    <DocumentFilters
      v-model="filters"
      :categories="categories"
      class="filters-section"
      :counts="filterCounts"
      @change="handleFilterChange"
    />

    <!-- Grid View -->
    <div v-if="viewMode === 'grid'">
      <div v-if="loading" class="loading-state">
        <div class="spinner" />
      </div>

      <div v-else-if="documents.length > 0" class="documents-grid">
        <DocumentCard
          v-for="doc in documents"
          :key="doc.id"
          :document="doc"
          @view="router.push(`/documents/${doc.id}`)"
        />
      </div>

      <div v-else class="empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <h3 class="empty-title">Aucun document</h3>
        <p class="empty-text">Commencez par uploader des documents</p>
      </div>
    </div>

    <!-- Tree View -->
    <BaseCard v-else-if="viewMode === 'tree'">
      <div v-if="documents.length > 0" class="tree-fallback">
        <button
          v-for="doc in documents"
          :key="doc.id"
          class="tree-item"
          @click="router.push(`/documents/${doc.id}`)"
        >
          {{ doc.code || doc.ref }} - {{ doc.title }}
        </button>
      </div>
      <div v-else class="empty-state-inline">
        Aucun document à afficher
      </div>
    </BaseCard>

    <!-- Pagination -->
    <div v-if="meta && links && meta.last_page > 1" class="pagination">
      <span class="pagination-info">{{ meta.from ?? 0 }} - {{ meta.to ?? 0 }} sur {{ meta.total }}</span>
      <div class="pagination-buttons">
        <button class="pagination-btn" :disabled="!links.prev" @click="goToPage(meta.current_page - 1)">Précédent</button>
        <button class="pagination-btn" :disabled="!links.next" @click="goToPage(meta.current_page + 1)">Suivant</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentFilters as Filters } from '@/types/document'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import BaseCard from '@/components/common/BaseCard.vue'
  import DocumentCard from '@/components/documents/DocumentCard.vue'
  import DocumentFilters from '@/components/documents/DocumentFilters.vue'
  import { useDocumentStore } from '@/stores/documentStore'

  const router = useRouter()
  const documentStore = useDocumentStore()

  const { documents, loading, meta, links, statistics, categories } = storeToRefs(documentStore)

  const viewMode = ref<'grid' | 'tree'>('grid')
  const filters = ref<Filters>({})
  const overdueCount = computed(() => documentStore.reviewOverdueDocuments.length)

  const filterCounts = computed(() => ({
    total: statistics.value?.total || 0,
    draft: statistics.value?.by_status?.draft || 0,
    review: statistics.value?.by_status?.under_review || 0,
    approved: statistics.value?.by_status?.approved || 0,
    archived: statistics.value?.by_status?.archived || 0,
    review_overdue: overdueCount.value,
  }))

  async function loadDocuments () {
    await documentStore.fetchDocuments(filters.value)
    await documentStore.fetchStatistics()
  }

  async function loadCategories () {
    await documentStore.fetchCategories()
  }

  function handleFilterChange (newFilters: Filters) {
    filters.value = newFilters
    loadDocuments()
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadDocuments()
  }

  onMounted(() => {
    loadDocuments()
    loadCategories()
  })
</script>

<style scoped>
.documents-page {
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
}

.page-subtitle {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-1);
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.stat-card {
  text-align: center;
  padding: var(--spacing-4);
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-1);
}

.stat-value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.stat-neutral {
  color: var(--text-secondary);
}

.stat-info {
  color: var(--color-primary-600);
}

.stat-success {
  color: var(--color-success-600);
}

.stat-danger {
  color: var(--color-danger-600);
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

.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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

.empty-state-inline {
  text-align: center;
  padding: var(--spacing-12) 0;
  color: var(--text-secondary);
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
  .documents-page {
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

  .documents-grid {
    grid-template-columns: 1fr;
  }
}
</style>
