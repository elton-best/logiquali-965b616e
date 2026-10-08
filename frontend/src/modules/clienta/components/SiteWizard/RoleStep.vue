<template>
  <v-card flat>
    <v-card-text>
      <PermissionsRoleEditor
        v-model="selectedRole"
        v-model:permissions="customPermissions"
        :permission-modules="permissionModules"
        :roles="roleCatalog"
      />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import PermissionsRoleEditor from '@/modules/clienta/components/permissions/PermissionsRoleEditor.vue'
  import permissionService, { type PermissionModule, type Role } from '@/services/permissionService'

  interface Props {
    modelValue: string
    permissions?: string[]
    siteId?: number | null
  }

  const props = withDefaults(defineProps<Props>(), {
    permissions: () => [],
    siteId: null,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'update:permissions', value: string[]): void
  }>()

  const selectedRole = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const customPermissions = computed({
    get: () => props.permissions,
    set: value => emit('update:permissions', value),
  })

  const roleCatalog = ref<Role[]>([])
  const permissionModules = ref<PermissionModule[]>([])

  async function loadRoleAndPermissionCatalog () {
    try {
      const [availableRoles, availablePermissionModules] = await Promise.all([
        permissionService.getAvailableRoles(props.siteId),
        permissionService.getAvailablePermissions(),
      ])

      roleCatalog.value = availableRoles
      permissionModules.value = availablePermissionModules

      if (!selectedRole.value && roleCatalog.value.length > 0) {
        const preferred = roleCatalog.value.find(role => role.name === 'site_manager')
        const fallback = roleCatalog.value[0]
        if (preferred?.name) {
          selectedRole.value = preferred.name
        } else if (fallback?.name) {
          selectedRole.value = fallback.name
        }
      }
    } catch (error) {
      console.error('Erreur chargement rôles/permissions:', error)
      roleCatalog.value = []
      permissionModules.value = []
    }
  }

  onMounted(() => {
    loadRoleAndPermissionCatalog()
  })

  watch(
    () => props.siteId,
    () => {
      loadRoleAndPermissionCatalog()
    },
  )
</script>
