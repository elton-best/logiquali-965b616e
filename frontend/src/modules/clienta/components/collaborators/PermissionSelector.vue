<template>
  <div>
    <!-- Alert profil suggéré -->
    <v-alert
      v-if="suggestedPermissions.length > 0"
      class="mb-4"
      type="info"
      variant="tonal"
    >
      <div class="d-flex align-center justify-space-between">
        <div>
          <div class="font-weight-medium">{{ profileLabel }}</div>
          <div class="text-caption">{{ profileDescription }}</div>
        </div>
        <v-btn
          color="primary"
          size="small"
          variant="elevated"
          @click="$emit('apply-suggested')"
        >
          <v-icon start>mdi-check</v-icon>
          Appliquer
        </v-btn>
      </div>
    </v-alert>

    <!-- Actions globales -->
    <div class="d-flex justify-space-between align-center mb-4">
      <div class="text-subtitle-2">
        {{ selectedPermissions.length }} permission(s) sélectionnée(s)
      </div>
      <div>
        <v-btn
          size="small"
          variant="text"
          @click="$emit('select-all')"
        >
          <v-icon start>mdi-checkbox-multiple-marked</v-icon>
          Tout sélectionner
        </v-btn>
        <v-btn
          size="small"
          variant="text"
          @click="$emit('deselect-all')"
        >
          <v-icon start>mdi-checkbox-multiple-blank-outline</v-icon>
          Tout désélectionner
        </v-btn>
      </div>
    </div>

    <!-- Expansion panels par groupe -->
    <v-expansion-panels
      v-model="expandedPanels"
      multiple
    >
      <v-expansion-panel
        v-for="group in permissionGroups"
        :key="group.name"
      >
        <v-expansion-panel-title>
          <div class="d-flex align-center">
            <v-icon class="mr-3" :color="getGroupColor(group.name)">{{ group.icon }}</v-icon>
            <span class="font-weight-medium">{{ group.label }}</span>
            <v-chip
              class="ml-2"
              :color="getGroupColor(group.name)"
              size="small"
              variant="tonal"
            >
              {{ getSelectedCountInGroup(group) }}/{{ group.permissions.length }}
            </v-chip>
          </div>
        </v-expansion-panel-title>

        <v-expansion-panel-text>
          <div class="d-flex justify-end mb-3">
            <v-btn
              size="x-small"
              variant="text"
              @click="$emit('select-group', group.name)"
            >
              Tout sélectionner
            </v-btn>
            <v-btn
              size="x-small"
              variant="text"
              @click="$emit('deselect-group', group.name)"
            >
              Tout désélectionner
            </v-btn>
          </div>

          <v-row dense>
            <v-col
              v-for="permission in group.permissions"
              :key="permission.value"
              cols="12"
              sm="6"
            >
              <v-checkbox
                density="compact"
                hide-details
                :label="permission.label"
                :model-value="selectedPermissions.includes(permission.value)"
                @update:model-value="(val) => $emit('toggle-permission', permission.value, Boolean(val))"
              />
            </v-col>
          </v-row>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  interface Permission {
    label: string
    value: string
  }

  interface PermissionGroup {
    name: string
    label: string
    icon: string
    permissions: Permission[]
  }

  interface Props {
    selectedPermissions: string[]
    suggestedPermissions: string[]
    profileLabel: string
    profileDescription: string
    permissionGroups: PermissionGroup[]
  }

  const props = defineProps<Props>()

  defineEmits<{
    'apply-suggested': []
    'select-all': []
    'deselect-all': []
    'select-group': [groupName: string]
    'deselect-group': [groupName: string]
    'toggle-permission': [permission: string, value: boolean]
  }>()

  const expandedPanels = ref(Array.from({ length: props.permissionGroups.length }, (_, i) => i))

  function getGroupColor (groupName: string): string {
    const colors: Record<string, string> = {
      sites: 'purple',
      users: 'cyan',
      subscriptions: 'amber',
      documents: 'blue',
      processes: 'deep-purple',
      risks: 'orange',
      audits: 'green',
      nc: 'red',
      actions: 'teal',
      indicators: 'indigo',
    }
    return colors[groupName] || 'grey'
  }

  function getSelectedCountInGroup (group: PermissionGroup): number {
    return group.permissions.filter(p => props.selectedPermissions.includes(p.value)).length
  }
</script>
