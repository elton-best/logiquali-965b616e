<template>
  <div class="actions-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Actions</h1>
        <p class="page-subtitle">
          Gestion des actions correctives, préventives et d'amélioration
        </p>
      </div>
      <button
        class="btn btn-primary"
        @click="showCreateModal = true"
      >
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Nouvelle action
      </button>
    </div>

    <!-- Stats Cards -->
    <div v-if="statistics" class="stats-grid">
      <div class="stat-card">
        <div class="stat-content">
          <div>
            <p class="stat-label">Total</p>
            <p class="stat-value">{{ statistics.total }}</p>
          </div>
          <div class="stat-icon stat-icon-primary">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-content">
          <div>
            <p class="stat-label">En cours</p>
            <p class="stat-value stat-warning">{{ statistics.by_status.in_progress }}</p>
          </div>
          <div class="stat-icon stat-icon-warning">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-content">
          <div>
            <p class="stat-label">Terminées</p>
            <p class="stat-value stat-success">{{ statistics.by_status.completed }}</p>
          </div>
          <div class="stat-icon stat-icon-success">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-content">
          <div>
            <p class="stat-label">En retard</p>
            <p class="stat-value stat-danger">{{ overdueCount }}</p>
          </div>
          <div class="stat-icon stat-icon-danger">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <ActionFilters
      v-model="filters"
      class="filters-section"
      :counts="filterCounts"
      @change="handleFilterChange"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-content">
        <div class="spinner" />
        <p class="loading-text">Chargement des actions...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p class="error-text">{{ error }}</p>
      <button class="error-retry" @click="loadActions">Réessayer</button>
    </div>

    <!-- Actions Grid -->
    <div v-else-if="actions.length > 0" class="actions-grid">
      <ActionCard
        v-for="action in actions"
        :key="action.id"
        :action="action"
        @delete="confirmDelete(action)"
        @edit="editAction(action.id)"
        @view="viewAction(action.id)"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
      </svg>
      <h3 class="empty-title">Aucune action</h3>
      <p class="empty-text">Commencez par créer votre première action</p>
      <button
        class="btn btn-primary"
        @click="showCreateModal = true"
      >
        Créer une action
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="meta && links && meta.last_page > 1" class="pagination">
      <div class="pagination-info">
        Affichage de {{ meta.from ?? 0 }} à {{ meta.to ?? 0 }} sur {{ meta.total }} actions
      </div>
      <div class="pagination-buttons">
        <button
          class="pagination-btn"
          :disabled="!links.prev"
          @click="goToPage(meta.current_page - 1)"
        >
          Précédent
        </button>
        <button
          class="pagination-btn"
          :disabled="!links.next"
          @click="goToPage(meta.current_page + 1)"
        >
          Suivant
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <BaseModal v-if="showCreateModal" :model-value="showCreateModal" @close="showCreateModal = false">
      <template #header>
        <h2 class="modal-title">{{ editingAction ? 'Modifier' : 'Créer' }} une action</h2>
      </template>
      <template #body>
        <ActionForm
          :action="editingAction"
          @cancel="showCreateModal = false"
          @submit="handleSubmit"
        />
      </template>
    </BaseModal>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-if="showDeleteModal" :model-value="showDeleteModal" @close="showDeleteModal = false">
      <template #header>
        <h2 class="modal-title modal-title-danger">Confirmer la suppression</h2>
      </template>
      <template #body>
        <p class="modal-text">
          Êtes-vous sûr de vouloir supprimer l'action "<strong>{{ actionToDelete?.title }}</strong>" ?
        </p>
        <p class="modal-hint">Cette action est irréversible.</p>
      </template>
      <template #footer>
        <div class="modal-actions">
          <button
            class="btn btn-secondary"
            @click="showDeleteModal = false"
          >
            Annuler
          </button>
          <button
            class="btn btn-danger"
            @click="handleDelete"
          >
            Supprimer
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup lang="ts">
  import type { Action, ActionFilters as Filters } from '@/types/action'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ActionCard from '@/components/actions/ActionCard.vue'
  import ActionFilters from '@/components/actions/ActionFilters.vue'
  import BaseModal from '@/components/common/BaseModal.vue'
  import { useActionStore } from '@/stores/actionStore'

  // Composant formulaire (à créer)
  const ActionForm = defineAsyncComponent(() => import('@/components/actions/ActionForm.vue'))

  const router = useRouter()
  const actionStore = useActionStore()

  const { actions, loading, error, meta, links, statistics } = storeToRefs(actionStore)

  const filters = ref<Filters>({})
  const showCreateModal = ref(false)
  const showDeleteModal = ref(false)
  const editingAction = ref<Action | null>(null)
  const actionToDelete = ref<Action | null>(null)

  const overdueCount = computed(() => actionStore.overdueActions.length)

  const filterCounts = computed(() => ({
    total: statistics.value?.total || 0,
    pending: statistics.value?.by_status.pending || 0,
    in_progress: statistics.value?.by_status.in_progress || 0,
    completed: statistics.value?.by_status.completed || 0,
    overdue: overdueCount.value,
  }))

  async function loadActions () {
    await actionStore.fetchActions(filters.value)
    await actionStore.fetchStatistics()
  }

  function handleFilterChange (newFilters: Filters) {
    filters.value = newFilters
    loadActions()
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadActions()
  }

  function viewAction (id: number) {
    router.push(`/improvement/actions/${id}`)
  }

  function editAction (id: number) {
    const action = actions.value.find(a => a.id === id)
    if (action) {
      editingAction.value = action
      showCreateModal.value = true
    }
  }

  function confirmDelete (action: Action) {
    actionToDelete.value = action
    showDeleteModal.value = true
  }

  async function handleDelete () {
    if (actionToDelete.value) {
      try {
        await actionStore.deleteAction(actionToDelete.value.id)
        showDeleteModal.value = false
        actionToDelete.value = null
      } catch (error_) {
        console.error('Delete error:', error_)
      }
    }
  }

  async function handleSubmit (payload: any) {
    try {
      await (editingAction.value ? actionStore.updateAction(editingAction.value.id, payload) : actionStore.createAction(payload))
      showCreateModal.value = false
      editingAction.value = null
      await loadActions()
    } catch (error_) {
      console.error('Submit error:', error_)
    }
  }

  onMounted(() => {
    loadActions()
  })
</script>

<style scoped>
.actions-page {
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

.btn-icon {
  width: 20px;
  height: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.stat-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-base);
  transition: all var(--transition-base);
}

.stat-card:hover {
  box-shadow: var(--shadow-md);
}

.stat-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.stat-value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.stat-warning {
  color: var(--color-warning-600);
}

.stat-success {
  color: var(--color-success-600);
}

.stat-danger {
  color: var(--color-danger-600);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon-primary {
  background: var(--color-primary-100);
}

.stat-icon-warning {
  background: var(--color-warning-100);
}

.stat-icon-success {
  background: var(--color-success-100);
}

.stat-icon-danger {
  background: var(--color-danger-100);
}

.icon {
  width: 24px;
  height: 24px;
}

.stat-icon-primary .icon {
  color: var(--color-primary-600);
}

.stat-icon-warning .icon {
  color: var(--color-warning-600);
}

.stat-icon-success .icon {
  color: var(--color-success-600);
}

.stat-icon-danger .icon {
  color: var(--color-danger-600);
}

.filters-section {
  margin-bottom: var(--spacing-6);
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 256px;
}

.loading-content {
  text-align: center;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid var(--color-primary-100);
  border-top-color: var(--color-primary-500);
  border-radius: var(--radius-full);
  animation: spin 0.8s linear infinite;
  margin: 0 auto var(--spacing-4);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  color: var(--text-secondary);
}

.error-state {
  background: var(--color-danger-50);
  border: 1px solid var(--color-danger-200);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.error-text {
  color: var(--color-danger-700);
}

.error-retry {
  margin-top: var(--spacing-2);
  font-size: var(--font-size-sm);
  color: var(--color-danger-600);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  transition: all var(--transition-base);
}

.error-retry:hover {
  color: var(--color-danger-700);
}

.error-retry:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
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
  align-items: center;
  justify-content: space-between;
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

.modal-title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.modal-title-danger {
  color: var(--color-danger-600);
}

.modal-text {
  color: var(--text-primary);
  margin-bottom: var(--spacing-4);
}

.modal-hint {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-3);
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .actions-page {
    padding: var(--spacing-4);
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
