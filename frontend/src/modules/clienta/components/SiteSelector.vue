<template>
  <v-autocomplete
    v-model="selectedSiteValue"
    clearable
    density="compact"
    hide-details
    item-title="title"
    item-value="value"
    :items="selectOptions"
    :loading="siteContextStore.loading"
    prepend-inner-icon="mdi-office-building"
    rounded="lg"
    style="min-width: 220px; max-width: 300px"
    variant="outlined"
    @update:model-value="handleSiteChange"
  >
    <template #item="{ item, props: itemProps }">
      <v-list-item v-bind="itemProps">
        <template #append>
          <v-chip
            v-if="item.raw.hasSubscription === false"
            color="warning"
            size="x-small"
            variant="flat"
          >
            Sans abonnement
          </v-chip>
          <v-chip
            v-else-if="item.raw.hasSubscription === true"
            color="success"
            size="x-small"
            variant="flat"
          >
            Actif
          </v-chip>
        </template>
      </v-list-item>
    </template>
  </v-autocomplete>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { useAuthStore } from '@/stores/auth'
  import { useSiteContextStore } from '@/stores/siteContext'
  import { normalizePermissionList } from '@/utils/permissions'

  const siteContextStore = useSiteContextStore()
  const authStore = useAuthStore()
  const router = useRouter()
  const toast = useToast()

  function getEffectivePermissionSet (): Set<string> {
    const user = authStore.user as any
    const fromDirect = Array.isArray(user?.permissions) ? user.permissions : []
    const fromEffective = Array.isArray(user?.effective_permissions) ? user.effective_permissions : []
    const fromActiveScoped = Array.isArray(user?.active_scoped_permissions) ? user.active_scoped_permissions : []
    const fromRolePermissions = Array.isArray(user?.roles)
      ? user.roles.flatMap((role: any) =>
        Array.isArray(role?.permissions)
          ? role.permissions
          : (Array.isArray(role?.relationships?.permissions)
            ? role.relationships.permissions
            : []),
      )
      : []

    const names = [
      ...fromDirect,
      ...fromEffective,
      ...fromActiveScoped,
      ...fromRolePermissions,
    ].map((permission: any) => {
      if (typeof permission === 'string') return permission
      if (typeof permission?.name === 'string') return permission.name
      if (typeof permission?.attributes?.name === 'string') return permission.attributes.name
      return null
    }).filter(Boolean) as string[]

    return new Set(normalizePermissionList(names))
  }

  function hasPermission (permissionName: string): boolean {
    const normalized = String(permissionName || '').trim().toLowerCase()
    if (!normalized) return false
    return getEffectivePermissionSet().has(normalized)
  }

  const isEnterpriseAdmin = computed(() => {
    const user = authStore.user as any
    if (!user || user.user_type !== 'company') {
      return false
    }

    const roleNames = Array.isArray(user.role_names) ? user.role_names : []
    if (roleNames.includes('admin_entreprise')) {
      return true
    }

    const roles = Array.isArray(user.roles) ? user.roles : []
    return roles.some(
      (role: any) =>
        role?.name === 'admin_entreprise'
        || role?.attributes?.name === 'admin_entreprise'
        || role === 'admin_entreprise',
    )
  })

  const canUseEnterpriseScope = computed(() =>
    isEnterpriseAdmin.value || hasPermission('sites.read'),
  )

  const selectedSiteValue = computed({
    get: () =>
      siteContextStore.activeScope === 'enterprise'
        ? 'all'
        : siteContextStore.activeSiteId,
    set: (value: string | number | null) => handleSiteChange(value),
  })

  const selectOptions = computed(() => {
    const base = siteContextStore.availableSites
    if (!canUseEnterpriseScope.value) {
      return base
    }

    return [
      {
        value: 'all',
        title: 'Tous les sites',
        hasSubscription: true,
      },
      ...base,
    ]
  })

  function handleSiteChange (siteId: string | number | null) {
    if (!siteId || siteId === 'all') {
      if (canUseEnterpriseScope.value) {
        siteContextStore.setEnterpriseScope()
        toast.success('Vue globale entreprise activée', { timeout: 2000 })
      }
      return
    }

    const selectedSite = selectOptions.value.find(
      s => String(s.value) === String(siteId),
    )

    if (selectedSite) {
      // Vérifier si le site a une souscription
      if (selectedSite.hasSubscription === false) {
        toast.warning(
          `Le site "${selectedSite.title}" nécessite un abonnement actif`,
          {
            timeout: 5000,
            onClick: () => router.push(`/company/subscription?site_id=${siteId}`),
          },
        )
      } else {
        toast.success(`Site "${selectedSite.title}" sélectionné`, {
          timeout: 2000,
        })
      }

      siteContextStore.setActiveSite(siteId)
    }
  }

  onMounted(() => {
    siteContextStore.loadAvailableSites()
  })
</script>

<style scoped>
:deep(.v-field) {
  background: rgba(var(--v-theme-surface), 0.8);
}
</style>
