<template>
  <DataTable
    :headers="headers"
    :items="items"
    :items-per-page="15"
    :loading="loading"
  >
    <template #item.name="{ item }">
      <div class="d-flex align-center py-2">
        <v-avatar class="mr-3" color="primary" size="36">
          <span class="text-white text-body-2">{{ getInitials(item.name || item.username) }}</span>
        </v-avatar>
        <div>
          <div class="font-weight-medium">{{ item.name || item.username || 'Sans nom' }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
        </div>
      </div>
    </template>

    <template #item.role="{ item }">
      <v-chip v-if="item.role" color="blue-grey" size="small" variant="tonal">
        <v-icon size="small" start>mdi-briefcase</v-icon>
        {{ item.role }}
      </v-chip>
      <span v-else class="text-caption text-medium-emphasis">Non défini</span>
    </template>

    <template #item.site="{ item }">
      <v-chip v-if="getUserSiteLabel(item)" color="primary" size="small" variant="tonal">
        <v-icon size="small" start>mdi-map-marker</v-icon>
        {{ getUserSiteLabel(item) }}
      </v-chip>
      <span v-else class="text-medium-emphasis">-</span>
    </template>

    <template #item.is_active="{ item }">
      <StatusChip :status="item.is_active ? 'active' : 'inactive'" />
    </template>

    <template #item.permissions="{ item }">
      <v-chip color="info" size="small" variant="outlined">
        {{ item.active_scoped_permissions_count ?? item.effective_permissions_count ?? item.permissions?.length ?? 0 }} actives
      </v-chip>
    </template>

    <template #item.actions="{ item }">
      <v-btn
        color="info"
        icon="mdi-eye"
        size="small"
        variant="text"
        @click="emit('view', item)"
      >
        <v-icon>mdi-eye</v-icon>
        <v-tooltip activator="parent" location="top">Voir détails</v-tooltip>
      </v-btn>
      <v-btn
        color="primary"
        icon="mdi-pencil"
        size="small"
        variant="text"
        @click="emit('edit', item)"
      >
        <v-icon>mdi-pencil</v-icon>
        <v-tooltip activator="parent" location="top">Modifier</v-tooltip>
      </v-btn>
      <v-btn
        color="error"
        icon="mdi-delete"
        size="small"
        variant="text"
        @click="emit('delete', item)"
      >
        <v-icon>mdi-delete</v-icon>
        <v-tooltip activator="parent" location="top">Supprimer</v-tooltip>
      </v-btn>
    </template>

    <template #no-data>
      <div class="text-center pa-8">
        <v-icon color="grey-lighten-1" size="64">mdi-account-off-outline</v-icon>
        <div class="text-h6 mt-4 mb-2">Aucun collaborateur trouvé</div>
        <div class="text-body-2 text-medium-emphasis">Créez votre premier collaborateur</div>
      </div>
    </template>
  </DataTable>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'

  interface CollaboratorUser {
    id: number
    name: string
    username?: string
    email: string
    role?: string
    is_active: boolean
    permissions?: unknown[]
    active_scoped_permissions_count?: number
    effective_permissions_count?: number
  }

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, unknown>>>,
      required: true,
    },
    items: {
      type: Array as PropType<CollaboratorUser[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    getInitials: {
      type: Function as PropType<(value?: string) => string>,
      required: true,
    },
    getUserSiteLabel: {
      type: Function as PropType<(user: CollaboratorUser) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'view', value: CollaboratorUser): void
    (event: 'edit', value: CollaboratorUser): void
    (event: 'delete', value: CollaboratorUser): void
  }>()
</script>
