<template>
  <div class="advanced-filters">
    <div class="filters-header">
      <h3>Filtres avancés</h3>
      <button class="btn btn-sm btn-secondary" @click="resetFilters">Réinitialiser</button>
    </div>

    <div class="filters-grid">
      <!-- Scope -->
      <div class="filter-group">
        <label>Portée</label>
        <select v-model="filters.scope" class="form-control">
          <option :value="null">Toutes</option>
          <option value="global">Globales</option>
          <option value="enterprise">Entreprise</option>
          <option value="site">Site</option>
        </select>
      </div>

      <!-- Site -->
      <div v-if="sites.length > 0" class="filter-group">
        <label>Site</label>
        <select v-model="filters.site_id" class="form-control">
          <option :value="null">Tous les sites</option>
          <option v-for="site in sites" :key="site.id" :value="site.id">
            {{ site.name }}
          </option>
        </select>
      </div>

      <!-- Type de document -->
      <div v-if="documentTypes.length > 0" class="filter-group">
        <label>Type de document</label>
        <select v-model="filters.document_type_id" class="form-control">
          <option :value="null">Tous les types</option>
          <option v-for="type in documentTypes" :key="type.id" :value="type.id">
            {{ type.name }}
          </option>
        </select>
      </div>

      <!-- Statut -->
      <div class="filter-group">
        <label>Statut</label>
        <select v-model="filters.is_active" class="form-control">
          <option :value="null">Tous</option>
          <option :value="true">Actives</option>
          <option :value="false">Inactives</option>
        </select>
      </div>

      <!-- Recherche -->
      <div class="filter-group full-width">
        <label>Recherche</label>
        <input
          v-model="filters.search"
          class="form-control"
          placeholder="Code ou libellé..."
          type="text"
        >
      </div>

      <!-- Date de création (de) -->
      <div class="filter-group">
        <label>Créée après le</label>
        <input
          v-model="filters.created_from"
          class="form-control"
          type="date"
        >
      </div>

      <!-- Date de création (à) -->
      <div class="filter-group">
        <label>Créée avant le</label>
        <input
          v-model="filters.created_to"
          class="form-control"
          type="date"
        >
      </div>

      <!-- Nombre minimum de documents -->
      <div class="filter-group">
        <label>Min. documents</label>
        <input
          v-model.number="filters.min_documents"
          class="form-control"
          min="0"
          placeholder="0"
          type="number"
        >
      </div>

      <!-- Tri -->
      <div class="filter-group">
        <label>Trier par</label>
        <select v-model="filters.sort_by" class="form-control">
          <option value="created_at">Date de création</option>
          <option value="type_label">Libellé</option>
          <option value="type_code">Code</option>
          <option value="is_active">Statut</option>
        </select>
      </div>

      <!-- Direction du tri -->
      <div class="filter-group">
        <label>Direction</label>
        <select v-model="filters.sort_direction" class="form-control">
          <option value="asc">Croissant</option>
          <option value="desc">Décroissant</option>
        </select>
      </div>
    </div>

    <div class="filters-actions">
      <button class="btn btn-primary" @click="applyFilters">
        Appliquer les filtres
      </button>
      <span v-if="activeFiltersCount > 0" class="active-filters-badge">
        {{ activeFiltersCount }} filtre(s) actif(s)
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  const props = defineProps<{
    sites?: any[]
    documentTypes?: any[]
  }>()

  const emit = defineEmits<{
    (e: 'apply', filters: any): void
  }>()

  const filters = ref({
    scope: null as string | null,
    site_id: null as number | null,
    document_type_id: null as number | null,
    is_active: null as boolean | null,
    search: '',
    created_from: null as string | null,
    created_to: null as string | null,
    min_documents: null as number | null,
    sort_by: 'created_at',
    sort_direction: 'desc',
  })

  const sites = computed(() => props.sites || [])
  const documentTypes = computed(() => props.documentTypes || [])

  const activeFiltersCount = computed(() => {
    let count = 0
    if (filters.value.scope) count++
    if (filters.value.site_id) count++
    if (filters.value.document_type_id) count++
    if (filters.value.is_active !== null) count++
    if (filters.value.search) count++
    if (filters.value.created_from) count++
    if (filters.value.created_to) count++
    if (filters.value.min_documents) count++
    return count
  })

  function applyFilters () {
    const cleanFilters = Object.fromEntries(
      Object.entries(filters.value).filter(([_, v]) => v !== null && v !== ''),
    )
    emit('apply', cleanFilters)
  }

  function resetFilters () {
    filters.value = {
      scope: null,
      site_id: null,
      document_type_id: null,
      is_active: null,
      search: '',
      created_from: null,
      created_to: null,
      min_documents: null,
      sort_by: 'created_at',
      sort_direction: 'desc',
    }
    applyFilters()
  }

  // Auto-apply on certain changes
  watch(() => filters.value.is_active, () => {
    if (filters.value.is_active !== null) {
      applyFilters()
    }
  })

  watch(() => filters.value.scope, () => {
    if (filters.value.scope) {
      applyFilters()
    }
  })
</script>

<style scoped>
.advanced-filters {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 1.5rem;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.filters-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group.full-width {
  grid-column: 1 / -1;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
}

.form-control {
  padding: 0.5rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.875rem;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.filters-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #dee2e6;
}

.active-filters-badge {
  background: #007bff;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}
</style>
