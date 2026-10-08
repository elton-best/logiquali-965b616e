<template>
  <v-card flat>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-shield-check</v-icon>
      Sélectionner un rôle prédéfini
    </v-card-title>

    <v-card-text>
      <v-alert class="mb-4" type="info" variant="tonal">
        <div class="text-subtitle-2 mb-1">Rôles fixes</div>
        <div class="text-caption">
          Les rôles prédéfinis ont des permissions fixes. Choisissez le rôle qui correspond le mieux
          aux responsabilités du collaborateur.
        </div>
      </v-alert>

      <v-radio-group v-model="selectedRoleName" @update:model-value="emit('update:modelValue', $event || '')">
        <v-card
          v-for="role in roles"
          :key="role.id"
          class="mb-3 pa-3"
          :color="selectedRoleName === role.name ? 'primary' : undefined"
          style="cursor: pointer;"
          :variant="selectedRoleName === role.name ? 'tonal' : 'outlined'"
          @click="selectedRoleName = role.name"
        >
          <div class="d-flex align-center">
            <v-radio :value="role.name" />

            <div class="flex-grow-1 ml-2">
              <div class="text-subtitle-1 font-weight-bold">{{ role.name }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ role.permissions_count }} permission{{ role.permissions_count > 1 ? 's' : '' }}
              </div>
            </div>

            <v-btn
              icon="mdi-eye"
              size="small"
              variant="text"
              @click.stop="showPermissions(role)"
            />
          </div>

          <div v-if="selectedRoleName === role.name" class="mt-3 pt-3" style="border-top: 1px solid rgba(var(--v-theme-on-surface), 0.12)">
            <div class="text-caption text-medium-emphasis mb-2">Permissions incluses:</div>
            <v-chip-group>
              <v-chip
                v-for="perm in role.permissions.slice(0, 6)"
                :key="perm"
                color="primary"
                size="x-small"
                variant="flat"
              >
                {{ formatPermission(perm) }}
              </v-chip>
              <v-chip
                v-if="role.permissions.length > 6"
                size="x-small"
                variant="text"
              >
                +{{ role.permissions.length - 6 }} autres...
              </v-chip>
            </v-chip-group>
          </div>
        </v-card>
      </v-radio-group>
    </v-card-text>

    <v-dialog v-model="permissionsDialog" max-width="600">
      <v-card v-if="selectedRole">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-shield-check</v-icon>
          {{ selectedRole.name }}
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" @click="permissionsDialog = false" />
        </v-card-title>

        <v-card-text>
          <div class="text-subtitle-2 mb-3">
            {{ selectedRole.permissions_count }} permission{{ selectedRole.permissions_count > 1 ? 's' : '' }}
          </div>

          <v-list density="compact">
            <v-list-item
              v-for="(permGroup, index) in groupedPermissions"
              :key="index"
            >
              <template #prepend>
                <v-icon :icon="getModuleIcon(permGroup.module)" size="small" />
              </template>
              <v-list-item-title class="text-caption font-weight-medium">
                {{ permGroup.module }}
              </v-list-item-title>
              <v-list-item-subtitle>
                <v-chip-group>
                  <v-chip
                    v-for="perm in permGroup.permissions"
                    :key="perm"
                    color="primary"
                    size="x-small"
                    variant="flat"
                  >
                    {{ perm }}
                  </v-chip>
                </v-chip-group>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
  import type { Role } from '@/services/permissionService'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    roles: Role[]
    modelValue?: string
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string]
  }>()

  const selectedRoleName = ref<string>(props.modelValue || '')
  const permissionsDialog = ref(false)
  const selectedRole = ref<Role | null>(null)

  const groupedPermissions = computed(() => {
    if (!selectedRole.value) return []

    const groups: Record<string, string[]> = {}

    for (const perm of selectedRole.value.permissions) {
      const [module, action] = perm.split('.')
      if (!module || !action) continue
      if (!groups[module]) {
        groups[module] = []
      }
      groups[module].push(action)
    }

    return Object.entries(groups).map(([module, permissions]) => ({
      module: module.charAt(0).toUpperCase() + module.slice(1),
      permissions,
    }))
  })

  function formatPermission (perm: string): string {
    const action = perm.split('.')[1]
    return action || perm
  }

  function showPermissions (role: Role): void {
    selectedRole.value = role
    permissionsDialog.value = true
  }

  function getModuleIcon (module: string): string {
    const icons: Record<string, string> = {
      Process: 'mdi-sitemap',
      Audit: 'mdi-clipboard-check',
      Document: 'mdi-file-document',
      Nc: 'mdi-alert-circle',
      Action: 'mdi-lightning-bolt',
      User: 'mdi-account',
      Site: 'mdi-office-building',
      Indicator: 'mdi-chart-line',
      Complaint: 'mdi-message-alert',
      Settings: 'mdi-cog',
      Dashboard: 'mdi-view-dashboard',
    }
    return icons[module] || 'mdi-circle'
  }
</script>
