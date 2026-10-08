<template>
  <v-card>
    <DataTable
      class="sites-table"
      :empty-action-label="canCreateSite ? 'Nouveau site' : undefined"
      empty-description="Commencez par créer votre premier site"
      empty-icon="mdi-map-marker"
      empty-title="Aucun site"
      :headers="headers"
      :items="sites"
      :items-per-page="pagination.per_page"
      :loading="loading"
      :page="pagination.current_page"
      :total-items="pagination.total"
      @create="emit('create')"
      @update:items-per-page="emit('items-per-page', $event)"
      @update:page="emit('page', $event)"
    >
      <template #item.name="{ item }">
        <div class="site-name-cell">
          <v-avatar color="primary" size="30" variant="tonal">
            <v-icon size="16">mdi-office-building</v-icon>
          </v-avatar>
          <div class="site-name-content">
            <div class="site-name-title">{{ item.name }}</div>
            <div class="site-name-subtitle">
              {{ item.ref || `ID #${item.id}` }}
            </div>
          </div>
        </div>
      </template>

      <template #item.location="{ item }">
        <div class="site-location-cell">
          <v-icon color="medium-emphasis" size="16">mdi-map-marker-outline</v-icon>
          <span class="site-location-text">
            {{ item.location || item.city || "Non défini" }}
          </span>
        </div>
      </template>

      <template #item.is_active="{ item }">
        <v-chip :color="item.is_active ? 'success' : 'error'" size="small" variant="tonal">
          <v-icon start>
            {{ item.is_active ? "mdi-check-circle-outline" : "mdi-close-circle-outline" }}
          </v-icon>
          {{ item.is_active ? "Actif" : "Inactif" }}
        </v-chip>
      </template>

      <template #item.users_count="{ item }">
        <v-chip color="info" size="small" variant="tonal">
          <v-icon start>mdi-account-group-outline</v-icon>
          {{ item.users_count || 0 }}
        </v-chip>
      </template>

      <template #item.manager="{ item }">
        <div v-if="item.manager || item.manager_name" class="d-flex align-center gap-2">
          <v-avatar color="primary" size="32">
            <span class="text-caption">
              {{ getInitials(item.manager?.name || item.manager_name || "N/A") }}
            </span>
          </v-avatar>
          <div>
            <div class="text-body-2">
              {{ item.manager?.name || item.manager_name || "Non défini" }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{
                item.manager?.position ||
                  item.manager?.job_title ||
                  item.manager?.role ||
                  "Responsable de site"
              }}
            </div>
          </div>
        </div>
        <div v-else class="text-caption text-medium-emphasis">Non défini</div>
      </template>

      <template #item.subscription="{ item }">
        <div v-if="getActiveNormLabels(item).length > 0" class="d-flex flex-wrap ga-1">
          <v-chip
            v-for="normLabel in getActiveNormLabels(item)"
            :key="`${item.id}-${normLabel}`"
            color="success"
            size="small"
            variant="tonal"
          >
            <v-icon start>mdi-check</v-icon>
            {{ normLabel }}
          </v-chip>
        </div>
        <v-tooltip v-else-if="canManageSubscriptions" text="Cliquez pour souscrire">
          <template #activator="{ props }">
            <v-chip
              v-bind="props"
              color="error"
              size="small"
              variant="outlined"
              @click="emit('manage-subscription', item)"
            >
              <v-icon start>mdi-alert-circle</v-icon>
              Aucun
            </v-chip>
          </template>
        </v-tooltip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex ga-1 actions-cell">
          <v-tooltip v-if="canManageSubscriptions" text="Gérer l'abonnement">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                :color="hasAnyActiveSubscription(item) ? 'success' : 'warning'"
                icon="mdi-card-account-details"
                size="small"
                variant="tonal"
                @click="emit('manage-subscription', item)"
              />
            </template>
          </v-tooltip>

          <v-tooltip text="Voir détails">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                color="info"
                icon="mdi-eye"
                size="small"
                variant="tonal"
                @click="emit('details', item)"
              />
            </template>
          </v-tooltip>

          <v-tooltip v-if="canUpdateSite" text="Modifier">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                color="primary"
                icon="mdi-pencil"
                size="small"
                variant="tonal"
                @click="emit('edit', item)"
              />
            </template>
          </v-tooltip>

          <v-tooltip v-if="canDeleteSite" text="Supprimer">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                color="error"
                icon="mdi-delete"
                size="small"
                variant="tonal"
                @click="emit('delete', item)"
              />
            </template>
          </v-tooltip>
        </div>
      </template>
    </DataTable>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { Site } from '@/services/siteService'
  import DataTable from '@/modules/clienta/components/DataTable.vue'

  defineProps({
    sites: {
      type: Array as PropType<Site[]>,
      required: true,
    },
    headers: {
      type: Array as PropType<Array<{ title: string, key: string, sortable?: boolean }>>,
      required: true,
    },
    pagination: {
      type: Object as PropType<{ current_page: number, per_page: number, total: number }>,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    canCreateSite: {
      type: Boolean,
      required: true,
    },
    canUpdateSite: {
      type: Boolean,
      required: true,
    },
    canDeleteSite: {
      type: Boolean,
      required: true,
    },
    canManageSubscriptions: {
      type: Boolean,
      required: true,
    },
    getInitials: {
      type: Function as PropType<(name: string) => string>,
      required: true,
    },
    getActiveNormLabels: {
      type: Function as PropType<(site: Site) => string[]>,
      required: true,
    },
    hasAnyActiveSubscription: {
      type: Function as PropType<(site: Site) => boolean>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'create'): void
    (event: 'items-per-page', value: number): void
    (event: 'page', value: number): void
    (event: 'manage-subscription', site: Site): void
    (event: 'details', site: Site): void
    (event: 'edit', site: Site): void
    (event: 'delete', site: Site): void
  }>()
</script>

<style scoped>
.site-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.site-name-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.site-name-title {
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.site-name-subtitle {
  font-size: 12px;
  color: #64748b;
}

.site-location-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.site-location-text {
  color: #334155;
}

.actions-cell {
  justify-content: flex-end;
}

:deep(.app-table table) {
  table-layout: fixed;
}

:deep(.app-table th),
:deep(.app-table td) {
  vertical-align: middle;
}

:deep(.app-table th) {
  white-space: nowrap;
}

:deep(.app-table th:nth-child(1)),
:deep(.app-table td:nth-child(1)) {
  width: 20%;
}

:deep(.app-table th:nth-child(2)),
:deep(.app-table td:nth-child(2)) {
  width: 16%;
}

:deep(.app-table th:nth-child(3)),
:deep(.app-table td:nth-child(3)) {
  width: 20%;
}

:deep(.app-table th:nth-child(4)),
:deep(.app-table td:nth-child(4)) {
  width: 11%;
}

:deep(.app-table th:nth-child(5)),
:deep(.app-table td:nth-child(5)) {
  width: 15%;
}

:deep(.app-table th:nth-child(6)),
:deep(.app-table td:nth-child(6)) {
  width: 8%;
}

:deep(.app-table th:nth-child(7)),
:deep(.app-table td:nth-child(7)) {
  width: 10%;
}
</style>
