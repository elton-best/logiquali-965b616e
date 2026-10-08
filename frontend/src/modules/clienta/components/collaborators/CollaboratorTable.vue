<template>
  <v-card>
    <v-card-title class="d-flex align-center pa-4">
      <v-icon class="mr-2">mdi-account-multiple</v-icon>
      <span>Liste des collaborateurs</span>
      <v-spacer />
      <v-text-field
        v-model="search"
        class="ml-4"
        clearable
        density="compact"
        hide-details
        placeholder="Rechercher..."
        prepend-inner-icon="mdi-magnify"
        single-line
        style="max-width: 300px"
        variant="outlined"
      />
    </v-card-title>

    <v-data-table
      class="elevation-0"
      :headers="headers as any"
      :items="users"
      :items-per-page="10"
      :loading="loading"
      :search="search"
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
        <v-chip v-if="item.site" color="primary" size="small" variant="tonal">
          <v-icon size="small" start>mdi-map-marker</v-icon>
          {{ item.site.name }}
        </v-chip>
        <span v-else class="text-caption text-medium-emphasis">Non assigné</span>
      </template>

      <template #item.is_active="{ item }">
        <StatusChip :active="item.is_active" />
      </template>

      <template #item.permissions="{ item }">
        <v-chip
          color="info"
          size="small"
          variant="tonal"
        >
          {{ item.permissions?.length || 0 }} permissions
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <v-btn
          color="info"
          icon="mdi-eye"
          size="small"
          variant="text"
          @click="$emit('view', item)"
        >
          <v-icon>mdi-eye</v-icon>
          <v-tooltip activator="parent" location="top">Voir détails</v-tooltip>
        </v-btn>
        <v-btn
          color="primary"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="$emit('edit', item)"
        >
          <v-icon>mdi-pencil</v-icon>
          <v-tooltip activator="parent" location="top">Modifier</v-tooltip>
        </v-btn>
        <v-btn
          color="error"
          icon="mdi-delete"
          size="small"
          variant="text"
          @click="$emit('delete', item)"
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
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'

  const search = ref('')

  interface User {
    id: number
    name?: string
    username?: string
    email: string
    role?: string
    is_active: boolean
    site?: {
      id: number
      name: string
    }
    permissions?: Array<{ name: string }>
  }

  interface Props {
    users: User[]
    loading: boolean
  }

  defineProps<Props>()

  defineEmits<{
    view: [user: User]
    edit: [user: User]
    delete: [user: User]
  }>()

  const headers = [
    { title: 'Utilisateur', value: 'name', sortable: true },
    { title: 'Poste', value: 'role', sortable: true },
    { title: 'Site', value: 'site', sortable: false },
    { title: 'Statut', value: 'is_active', sortable: true },
    { title: 'Permissions', value: 'permissions', sortable: false },
    { title: 'Actions', value: 'actions', sortable: false, align: 'end' },
  ]

  function getInitials (name: string | undefined | null): string {
    if (!name) return '??'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
  }
</script>
