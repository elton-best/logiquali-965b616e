<template>
  <v-card flat>
    <v-card-text>
      <!-- Summary Card -->
      <v-card class="mb-6" color="primary" variant="tonal">
        <v-card-text class="d-flex align-center justify-space-between">
          <div>
            <div class="text-h6">Permissions sélectionnées</div>
            <div class="text-subtitle-2 text-medium-emphasis">
              {{ selectedCount }} / {{ totalPermissions }} permissions attribuées
            </div>
          </div>
          <v-progress-circular
            :color="percentage === 100 ? 'success' : 'primary'"
            :model-value="percentage"
            :size="60"
            :width="6"
          >
            {{ percentage }}%
          </v-progress-circular>
        </v-card-text>
      </v-card>

      <!-- Quick Actions -->
      <div class="d-flex gap-2 mb-4">
        <v-btn
          color="primary"
          prepend-icon="mdi-check-all"
          variant="outlined"
          @click="selectAll"
        >
          Tout sélectionner
        </v-btn>
        <v-btn
          color="error"
          prepend-icon="mdi-close-box-multiple"
          variant="outlined"
          @click="deselectAll"
        >
          Tout désélectionner
        </v-btn>
      </div>

      <!-- Permission Groups -->
      <v-skeleton-loader
        v-if="loading"
        class="mb-4"
        type="article, article, article"
      />

      <v-expansion-panels
        v-else
        v-model="openedPanels"
        multiple
      >
        <v-expansion-panel
          v-for="group in permissionGroups"
          :key="group.key"
          :value="group.key"
        >
          <v-expansion-panel-title>
            <div class="d-flex align-center justify-space-between flex-grow-1 mr-4">
              <div class="d-flex align-center gap-3">
                <v-icon :color="group.color">{{ group.icon }}</v-icon>
                <span class="font-weight-medium">{{ group.label }}</span>
              </div>
              <v-badge
                :color="getGroupSelectedCount(group.key) === group.permissions.length ? 'success' : 'grey'"
                :content="`${getGroupSelectedCount(group.key)}/${group.permissions.length}`"
                inline
              />
            </div>
          </v-expansion-panel-title>

          <v-expansion-panel-text>
            <div class="d-flex gap-2 mb-3">
              <v-btn
                color="success"
                prepend-icon="mdi-check-all"
                size="small"
                variant="text"
                @click="selectGroup(group.key)"
              >
                Tout sélectionner
              </v-btn>
              <v-btn
                color="error"
                prepend-icon="mdi-close"
                size="small"
                variant="text"
                @click="deselectGroup(group.key)"
              >
                Tout désélectionner
              </v-btn>
            </div>

            <v-divider class="mb-3" />

            <div class="d-flex flex-column gap-2">
              <v-checkbox
                v-for="permission in group.permissions"
                :key="permission.key"
                v-model="selectedPermissions"
                color="primary"
                density="compact"
                hide-details
                :label="permission.label"
                :value="permission.value"
              >
                <template #label>
                  <div>
                    <div class="font-weight-medium">{{ permission.label }}</div>
                    <div class="text-caption text-medium-emphasis">{{ permission.description }}</div>
                  </div>
                </template>
              </v-checkbox>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <v-alert
        v-if="selectedCount === 0"
        class="mt-4"
        type="warning"
        variant="tonal"
      >
        Attention: Aucune permission n'a été sélectionnée. L'admin entreprise n'aura aucun accès.
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import permissionService from '@/services/permissionService'

  interface Permission {
    key: string
    label: string
    description: string
    value: string
  }

  interface PermissionGroup {
    key: string
    label: string
    icon: string
    color: string
    permissions: Permission[]
  }

  interface Props {
    modelValue: string[]
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void
  }>()

  const openedPanels = ref<string[]>([])
  const loading = ref(false)
  const permissionGroups = ref<PermissionGroup[]>([])

  const selectedPermissions = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const MODULE_ICONS: Record<string, string> = {
    site: 'mdi-map-marker',
    sites: 'mdi-map-marker',
    user: 'mdi-account-group',
    users: 'mdi-account-group',
    personnel: 'mdi-account-group',
    subscription: 'mdi-credit-card',
    subscriptions: 'mdi-credit-card',
    document: 'mdi-file-document',
    documents: 'mdi-file-document',
    process: 'mdi-sitemap',
    processes: 'mdi-sitemap',
    risk: 'mdi-alert',
    risks: 'mdi-alert',
    audit: 'mdi-clipboard-check',
    audits: 'mdi-clipboard-check',
    nc: 'mdi-alert-circle',
    action: 'mdi-check-circle',
    actions: 'mdi-check-circle',
    indicator: 'mdi-chart-line',
    indicators: 'mdi-chart-line',
  }

  const MODULE_COLORS = [
    'blue',
    'purple',
    'orange',
    'red',
    'green',
    'indigo',
    'teal',
    'primary',
  ]

  function toLabel (value: string): string {
    return value
      .replace(/[_-]+/g, ' ')
      .replace(/\b\w/g, c => c.toUpperCase())
  }

  function toActionLabel (action: string): string {
    const labels: Record<string, string> = {
      read: 'Consulter',
      create: 'Créer',
      update: 'Modifier',
      delete: 'Supprimer',
      assign: 'Assigner',
      validate: 'Valider',
      approve: 'Approuver',
      publish: 'Publier',
      export: 'Exporter',
    }
    return labels[action] || toLabel(action)
  }

  async function loadPermissionGroups () {
    loading.value = true
    try {
      const modules = await permissionService.getAvailablePermissions()
      permissionGroups.value = modules.map((module, index) => ({
        key: module.module,
        label: toLabel(module.module),
        icon: MODULE_ICONS[module.module] || 'mdi-shield-key',
        color: MODULE_COLORS[index % MODULE_COLORS.length] || 'primary',
        permissions: module.permissions.map(permission => ({
          key: permission.action,
          label: toActionLabel(permission.action),
          description: `${toActionLabel(permission.action)} ${toLabel(module.module).toLowerCase()}`,
          value: permission.name,
        })),
      }))
      openedPanels.value = permissionGroups.value.map(group => group.key)
    } finally {
      loading.value = false
    }
  }

  const totalPermissions = computed(() => {
    return permissionGroups.value.reduce((total, group) => total + group.permissions.length, 0)
  })

  const selectedCount = computed(() => selectedPermissions.value.length)

  const percentage = computed(() => {
    return totalPermissions.value > 0
      ? Math.round((selectedCount.value / totalPermissions.value) * 100)
      : 0
  })

  function getGroupSelectedCount (groupKey: string) {
    const group = permissionGroups.value.find(g => g.key === groupKey)
    if (!group) {
      return 0
    }
    const groupValues = new Set(group.permissions.map(permission => permission.value))
    return selectedPermissions.value.filter(permission => groupValues.has(permission)).length
  }

  function selectAll () {
    selectedPermissions.value = permissionGroups.value.flatMap(group =>
      group.permissions.map(permission => permission.value),
    )
  }

  function deselectAll () {
    selectedPermissions.value = []
  }

  function selectGroup (groupKey: string) {
    const group = permissionGroups.value.find(g => g.key === groupKey)
    if (!group) return

    const groupPermissions = new Set(group.permissions.map(permission => permission.value))
    const merged = new Set(selectedPermissions.value)
    for (const permission of groupPermissions) merged.add(permission)
    selectedPermissions.value = Array.from(merged)
  }

  function deselectGroup (groupKey: string) {
    const group = permissionGroups.value.find(g => g.key === groupKey)
    if (!group) {
      return
    }
    const groupPermissions = new Set(group.permissions.map(permission => permission.value))
    selectedPermissions.value = selectedPermissions.value.filter(permission => !groupPermissions.has(permission))
  }

  onMounted(async () => {
    await loadPermissionGroups()
  })
</script>
