<template>
  <div>
    <div v-if="!role">Rôle non sélectionné</div>
    <div v-else>
      <h3 class="text-h6 mb-2">{{ role.title || role.name }}</h3>
      <div v-if="role.description" class="mb-4">{{ role.description }}</div>
      <div>
        <h4 class="text-subtitle-2 mb-2">Permissions</h4>
        <div v-if="role.permissions && role.permissions.length">
          <v-chip
            v-for="p in role.permissions"
            :key="p"
            class="mr-2 mb-2"
            :color="currentPermissions?.includes(p) ? 'primary' : 'default'"
            variant="tonal"
          >
            {{ mapPermissionLabel(p) }}
          </v-chip>
        </div>
        <div v-else class="text-medium-emphasis">Aucune permission déclarée pour ce rôle</div>
      </div>
      <div class="mt-4">
        <v-btn color="primary" @click="$emit('compare', role.permissions || [])">Comparer avec l'utilisateur</v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { mapPermissionLabel } from '@/config/permissionLabels'

type RoleDetail = {
  name?: string
  title?: string
  description?: string
  permissions?: string[]
}

defineProps<{
  role: RoleDetail | null
  currentPermissions?: string[]
}>()

defineEmits<{
  (e: 'compare', permissions: string[]): void
}>()
</script>
