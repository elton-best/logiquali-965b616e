<template>
  <v-card flat>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-shield-lock</v-icon>
      Permissions personnalisées
    </v-card-title>

    <v-card-text>
      <v-alert class="mb-4" type="warning" variant="tonal">
        <div class="text-subtitle-2 mb-1">Mode avancé</div>
        <div class="text-caption">
          Sélectionnez manuellement les permissions individuelles. Le collaborateur aura uniquement
          les permissions cochées ci-dessous.
        </div>
      </v-alert>

      <div class="d-flex align-center mb-4">
        <v-text-field
          v-model="search"
          clearable
          density="compact"
          hide-details
          label="Rechercher une permission..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
        />

        <v-spacer />

        <v-btn
          class="ml-2"
          color="primary"
          variant="text"
          @click="selectAll"
        >
          Tout sélectionner
        </v-btn>

        <v-btn
          class="ml-2"
          color="error"
          variant="text"
          @click="deselectAll"
        >
          Tout désélectionner
        </v-btn>
      </div>

      <div class="text-caption text-medium-emphasis mb-2">
        {{ selectedPermissions.length }} permission(s) sélectionnée(s)
      </div>

      <v-expansion-panels multiple variant="accordion">
        <v-expansion-panel
          v-for="module in filteredModules"
          :key="module.module"
        >
          <v-expansion-panel-title>
            <div class="d-flex align-center">
              <v-icon class="mr-2" :icon="getModuleIcon(module.module)" size="small" />
              <span class="font-weight-medium">{{ module.module }}</span>
              <v-chip
                class="ml-2"
                :color="getModuleSelectionColor(module)"
                size="x-small"
              >
                {{ getSelectedCount(module) }}/{{ module.permissions.length }}
              </v-chip>
            </div>
          </v-expansion-panel-title>

          <v-expansion-panel-text>
            <v-row>
              <v-col
                v-for="perm in module.permissions"
                :key="perm.name"
                cols="12"
                md="4"
                sm="6"
              >
                <v-checkbox
                  v-model="selectedPermissions"
                  density="compact"
                  hide-details
                  :label="formatPermissionLabel(perm)"
                  :value="perm.name"
                  @update:model-value="emit('update:modelValue', selectedPermissions)"
                />
              </v-col>
            </v-row>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PermissionModule } from '@/services/permissionService'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    permissionModules: PermissionModule[]
    modelValue: string[]
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string[]]
  }>()

  const search = ref('')
  const selectedPermissions = ref<string[]>([...props.modelValue])

  const filteredModules = computed(() => {
    if (!search.value) return props.permissionModules

    const searchLower = search.value.toLowerCase()
    return props.permissionModules
      .map(module => ({
        ...module,
        permissions: module.permissions.filter(perm =>
          perm.name.toLowerCase().includes(searchLower)
          || perm.action.toLowerCase().includes(searchLower)
          || module.module.toLowerCase().includes(searchLower),
        ),
      }))
      .filter(module => module.permissions.length > 0)
  })

  function getSelectedCount (module: PermissionModule): number {
    return module.permissions.filter(perm =>
      selectedPermissions.value.includes(perm.name),
    ).length
  }

  function getModuleSelectionColor (module: PermissionModule): string {
    const count = getSelectedCount(module)
    if (count === 0) return 'grey'
    if (count === module.permissions.length) return 'success'
    return 'primary'
  }

  function formatPermissionLabel (perm: { name: string, action: string }): string {
    const actionLabels: Record<string, string> = {
      read: 'Consulter',
      create: 'Créer',
      update: 'Modifier',
      delete: 'Supprimer',
      approve: 'Approuver',
      validate: 'Valider',
      verify: 'Vérifier',
      reject: 'Rejeter',
      export: 'Exporter',
      publish: 'Publier',
      archive: 'Archiver',
      close: 'Clôturer',
      manage_permissions: 'Gérer permissions',
    }
    return actionLabels[perm.action] || perm.action
  }

  function selectAll (): void {
    selectedPermissions.value = props.permissionModules.flatMap(module =>
      module.permissions.map(perm => perm.name),
    )
    emit('update:modelValue', selectedPermissions.value)
  }

  function deselectAll (): void {
    selectedPermissions.value = []
    emit('update:modelValue', selectedPermissions.value)
  }

  function getModuleIcon (module: string): string {
    const icons: Record<string, string> = {
      process: 'mdi-sitemap',
      audit: 'mdi-clipboard-check',
      document: 'mdi-file-document',
      nc: 'mdi-alert-circle',
      action: 'mdi-lightning-bolt',
      user: 'mdi-account',
      site: 'mdi-office-building',
      indicator: 'mdi-chart-line',
      complaint: 'mdi-message-alert',
      settings: 'mdi-cog',
      dashboard: 'mdi-view-dashboard',
    }
    return icons[module.toLowerCase()] || 'mdi-circle'
  }
</script>
