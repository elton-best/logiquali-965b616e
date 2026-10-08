<template>
  <LeadershipLayout>
    <div class="hero-progress-row">
      <HeroCard
        :badges="[
          {
            icon: 'mdi-account',
            text: `${personnel.length} collaborateurs`,
            variant: 'primary',
          },
          {
            icon: 'mdi-shield-check',
            text: 'Permissions configurables',
            variant: 'secondary',
          },
        ]"
        icon="mdi-account-group"
        icon-color="primary"
        subtitle="Créez des collaborateurs et gérez leurs permissions"
        title="Liste du personnel"
      >
        <template #actions>
          <button class="btn-secondary" @click="previewPersonnel">
            <v-icon size="20">mdi-eye</v-icon>
            Aperçu
          </button>
          <button class="btn-secondary" @click="exportPersonnelCsv">
            <v-icon size="20">mdi-microsoft-excel</v-icon>
            Excel
          </button>
          <button class="btn-secondary" @click="exportPersonnelDocx">
            <v-icon size="20">mdi-file-word</v-icon>
            Word
          </button>
          <button
            class="btn-secondary"
            :disabled="exportingPdf"
            @click="exportPersonnelPdf()"
          >
            <v-icon size="20">mdi-file-pdf-box</v-icon>
            PDF
          </button>
          <button
            class="btn-secondary"
            :disabled="submittingForVerification"
            @click="submitPersonnelForVerification"
          >
            <v-icon size="20">mdi-shield-check</v-icon>
            Vérifier document
          </button>
          <button class="btn-primary" @click="openCreateDialog">
            <v-icon size="20">mdi-plus</v-icon>
            Nouveau
          </button>
        </template>
      </HeroCard>
    </div>

    <TabsContainer v-model="activeTab" :tabs="tabs" />

    <transition mode="out-in" name="fade-slide">
      <!-- Liste -->
      <GlassCard v-if="activeTab === 'list'" key="list">
        <PersonnelFilters
          :role="listFilters.role"
          :role-options="roleFilterOptions"
          :search="listFilters.search"
          :site="listFilters.site"
          :site-options="siteFilterOptions"
          @reset="resetListFilters"
          @update:role="(value) => (listFilters.role = value)"
          @update:search="(value) => (listFilters.search = value)"
          @update:site="(value) => (listFilters.site = value)"
        />

        <PersonnelTable
          :filtered-personnel="filteredPersonnel"
          :get-initials="getInitials"
          :personnel="personnel"
          @delete="deletePerson"
          @edit="editPerson"
          @view="openDetailsDialog"
        />
      </GlassCard>

      <GlassCard v-else-if="activeTab === 'pending'" key="pending">
        <div class="pending-approvals-wrap">
          <div class="pending-approvals-header">
            <h3>Demandes en attente de validation</h3>
            <span class="pending-badge">{{ pendingApprovals.length }}</span>
          </div>

          <p v-if="!canApproveCollaborators" class="pending-info">
            Seul l’admin d’entreprise peut approuver ou rejeter les demandes.
          </p>

          <div v-if="pendingApprovals.length === 0" class="pending-empty">
            Aucune demande en attente.
          </div>

          <div v-else class="pending-grid">
            <article
              v-for="request in pendingApprovals"
              :key="request.id"
              class="pending-card"
            >
              <header class="pending-card-head">
                <strong>{{ request.fullName }}</strong>
                <span class="pending-card-status">En attente</span>
              </header>

              <div class="pending-card-body">
                <div><b>Email:</b> {{ request.email }}</div>
                <div><b>Site:</b> {{ request.siteName || 'Non defini' }}</div>
                <div><b>Poste:</b> {{ request.poste }}</div>
                <div>
                  <b>Demandeur:</b>
                  {{ request.approvalRequestedByName || 'Non renseigne' }}
                </div>
                <div>
                  <b>Demandé le:</b>
                  {{ formatDateTime(request.approvalRequestedAt) }}
                </div>
              </div>

              <footer v-if="canApproveCollaborators" class="pending-card-actions">
                <button
                  class="btn-primary"
                  :disabled="loading || approvalActionLoadingId === request.id"
                  @click="approvePendingRequest(request)"
                >
                  <v-icon size="18">mdi-check</v-icon>
                  Approuver
                </button>
                <button
                  class="btn-secondary"
                  :disabled="loading || approvalActionLoadingId === request.id"
                  @click="rejectPendingRequest(request)"
                >
                  <v-icon size="18">mdi-close</v-icon>
                  Rejeter
                </button>
              </footer>
            </article>
          </div>
        </div>
      </GlassCard>

      <!-- Import Excel -->
      <GlassCard v-else key="import">
        <BulkImportPanel
          :import-result="importResult"
          :loading="loading"
          @download-template="downloadTemplate"
          @file-selected="handleExcelUpload"
        />
      </GlassCard>
    </transition>

    <PersonnelCreateDialog
      v-model="showDialog"
      :active-scoped-permissions-count="activeScopedPermissionsCount"
      :available-site-options="availableSiteOptions"
      :dialog-tab="dialogTab"
      :dialog-tabs="dialogTabs"
      :direct-permissions-count="directPermissionsCount"
      :edit-mode="editMode"
      :effective-permissions-count="effectivePermissionsCount"
      :form="form"
      :format-permission-label="formatPermissionLabel"
      :inherited-role-permissions="inheritedRolePermissions"
      :is-custom-role="isCustomRoleSelected"
      :is-default-read-permission="isDefaultReadPermission"
      :is-inherited-permission="isInheritedPermission"
      :is-permission-checked="isPermissionChecked"
      :is-permission-locked="isPermissionLocked"
      :is-role-step-valid="isRoleStepValid"
      :is-step-valid="isStepValid"
      :loading="loading"
      :permission-groups="permissionGroups"
      :roles="roles"
      :toggle-permission="togglePermission"
      @close="closeDialog"
      @save="saveCollaborator"
      @update:dialog-tab="(value) => (dialogTab = value)"
    />

    <PersonnelDetailsDialog
      v-model="showDetailsDialog"
      :selected-person-details="selectedPersonDetails"
      @close="closeDetailsDialog"
      @edit="editFromDetails"
    />
  </LeadershipLayout>
</template>

<script setup lang="ts">
  import type { Person } from '@/modules/clienta/pages/leadership/types'
  import type { PermissionModule, Role } from '@/services/permissionService'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import * as XLSX from 'xlsx'
  import api from '@/api/client'
  import { usersService } from '@/api/services/users.service'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import HeroCard from '@/components/leadership/HeroCard.vue'
  import LeadershipLayout from '@/components/leadership/LeadershipLayout.vue'
  import TabsContainer from '@/components/leadership/TabsContainer.vue'
  import BulkImportPanel from '@/modules/clienta/pages/leadership/components/BulkImportPanel.vue'
  import PersonnelCreateDialog from '@/modules/clienta/pages/leadership/components/PersonnelCreateDialog.vue'
  import PersonnelDetailsDialog from '@/modules/clienta/pages/leadership/components/PersonnelDetailsDialog.vue'
  import PersonnelFilters from '@/modules/clienta/pages/leadership/components/PersonnelFilters.vue'
  import PersonnelTable from '@/modules/clienta/pages/leadership/components/PersonnelTable.vue'
  import { usePersonnelExports } from '@/modules/clienta/pages/leadership/composables/usePersonnelExports'
  import { usePersonnelFilters } from '@/modules/clienta/pages/leadership/composables/usePersonnelFilters'
  import {
    canManageCustomRoleForActor,
    CUSTOM_ROLE_PLACEHOLDER,
    sanitizeSelectedRole,
  } from '@/modules/clienta/pages/leadership/personnelRoleGuards'
  import { pdfExportService } from '@/services/pdfExportService'
  import permissionService from '@/services/permissionService'
  import { useAuthStore } from '@/stores/auth'
  import { useSiteContextStore } from '@/stores/siteContext'

  const authStore = useAuthStore()
  const siteContextStore = useSiteContextStore()

  type JsonApiEntity = {
    id: number | string
    attributes?: Record<string, any>
    relationships?: Record<string, any>
  }

  const personnel = ref<Person[]>([])
  const showDialog = ref(false)
  const editMode = ref(false)
  const editingId = ref<number | null>(null)
  const activeTab = ref('list')
  const dialogTab = ref('info')
  const lastSavedAt = ref<Date | null>(null)
  const importResult = ref<{
    type: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
  } | null>(null)
  const showDetailsDialog = ref(false)
  const selectedPersonDetails = ref<Person | null>(null)
  const loading = ref(false)
  const exportingPdf = ref(false)
  const siteOptions = ref<Array<{ id: number, name: string }>>([])
  const {
    listFilters,
    roleFilterOptions,
    siteFilterOptions,
    filteredPersonnel,
    resetListFilters,
  } = usePersonnelFilters(personnel)

  const form = ref({
    nom: '',
    prenoms: '',
    telephone: '',
    email: '',
    poste: '',
    role: '',
    site_id: null as number | null,
    adresse: '',
    datePriseService: '',
    custom_role_name: '',
    permissions: [] as string[],
  })

  function isCustomEnterpriseRoleName (roleName: string): boolean {
    return /^custom_enterprise_\d+_/i.test(String(roleName || ''))
  }

  function resolveActorEnterpriseId (): number | null {
    const actor: any = authStore.user || {}
    const nestedEnterpriseId = Number(actor?.enterprise?.id || 0)
    if (Number.isFinite(nestedEnterpriseId) && nestedEnterpriseId > 0) {
      return nestedEnterpriseId
    }

    const flatEnterpriseId = Number(actor?.enterprise_id || 0)
    if (Number.isFinite(flatEnterpriseId) && flatEnterpriseId > 0) {
      return flatEnterpriseId
    }

    return null
  }

  function sanitizeRoleSlug (value: string): string {
    const slug = String(value || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '')

    return slug || 'personnalise'
  }

  function actorHasRole (roleName: string): boolean {
    const user: any = authStore.user
    if (!user) {
      return false
    }

    const roleNames = Array.isArray(user.role_names) ? user.role_names : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = Array.isArray(user.roles) ? user.roles : []
    return roles.some((role: any) => {
      const name = String(role?.name || role?.attributes?.name || role || '')
      return name === roleName
    })
  }

  const canManageCustomRole = computed(() => {
    return canManageCustomRoleForActor(authStore.user)
  })

  const canApproveCollaborators = computed(() => {
    const user: any = authStore.user
    return user?.user_type === 'company' && actorHasRole('admin_entreprise')
  })

  const tabs = computed(() => {
    const baseTabs = [
      { value: 'list', label: 'Liste', icon: 'mdi-view-list' },
      { value: 'import', label: 'Import Excel', icon: 'mdi-file-excel' },
    ]
    if (canApproveCollaborators.value) {
      baseTabs.splice(1, 0, { value: 'pending', label: 'Demandes', icon: 'mdi-timer-sand' })
    }
    return baseTabs
  })

  const dialogTabs = [
    { value: 'info', label: 'Informations', icon: 'mdi-account' },
    { value: 'role', label: 'Rôle', icon: 'mdi-shield-account' },
    { value: 'permissions', label: 'Permissions', icon: 'mdi-shield-check' },
  ]

  const roleCatalog = ref<Role[]>([])
  const permissionModules = ref<PermissionModule[]>([])
  const scopedPermissionNames = ref<string[]>([])
  let loadPersonnelPromise: Promise<void> | null = null
  let loadAccessCatalogPromise: Promise<void> | null = null
  let loadSiteOptionsPromise: Promise<void> | null = null
  let siteContextChangeTimer: ReturnType<typeof setTimeout> | null = null
  const approvalActionLoadingId = ref<number | null>(null)

  function getRolePermissionNames (roleName: string): string[] {
    if (!roleName) {
      return []
    }

    const role = roleCatalog.value.find(item => item.name === roleName)
    return Array.isArray(role?.permissions) ? role.permissions : []
  }

  type NormalizedRole = {
    value: string
    label: string
    description: string
    icon: string
    color: string
  }

  const roleVisuals: Record<
    string,
    { icon: string, color: string, description: string }
  > = {
    admin_entreprise: {
      icon: 'mdi-shield-crown',
      color: 'purple',
      description: 'Administration complete de l entreprise',
    },
    site_manager: {
      icon: 'mdi-office-building',
      color: 'primary',
      description: 'Gestion complete du site',
    },
    lecteur: {
      icon: 'mdi-eye',
      color: 'grey',
      description: 'Consultation uniquement',
    },
  }

  const roles = computed<NormalizedRole[]>(() => {
    const normalized = roleCatalog.value.map(role => {
      const visual = roleVisuals[role.name] || {
        icon: 'mdi-shield-account',
        color: 'primary',
        description: 'Role metier',
      }

      return {
        value: role.name,
        label: role.label || role.name,
        description: visual.description,
        icon: visual.icon,
        color: visual.color,
      }
    })

    const hasCustomEntry = normalized.some(
      role => role.value === CUSTOM_ROLE_PLACEHOLDER,
    )

    if (!hasCustomEntry && canManageCustomRole.value) {
      normalized.push({
        value: CUSTOM_ROLE_PLACEHOLDER,
        label: 'Role personnalise',
        description: 'Creez un role unique pour votre entreprise',
        icon: 'mdi-shield-edit',
        color: 'deep-orange',
      })
    }

    const currentRole = String(form.value.role || '').trim()
    if (
      currentRole
      && currentRole !== CUSTOM_ROLE_PLACEHOLDER
      && !normalized.some(role => role.value === currentRole)
    ) {
      normalized.push({
        value: currentRole,
        label: form.value.custom_role_name || currentRole,
        description: 'Role personnalise actif',
        icon: 'mdi-shield-star',
        color: 'deep-orange',
      })
    }

    return normalized
  })

  const availableSiteOptions = computed(() => {
    if (siteOptions.value.length > 0) {
      return siteOptions.value
    }

    return (authStore.availableSites || [])
      .map((site: any) => ({
        id: Number(site?.id),
        name: String(site?.name || 'Site sans nom'),
      }))
      .filter((site: { id: number }) => Number.isFinite(site.id))
  })
  const siteNameById = computed(() => {
    const map = new Map<number, string>()
    for (const site of availableSiteOptions.value) {
      const id = Number(site.id)
      const name = String(site.name || '').trim()
      if (Number.isFinite(id) && name) {
        map.set(id, name)
      }
    }
    return map
  })

  const inheritedRolePermissions = computed(() => {
    return Array.from(new Set(getRolePermissionNames(form.value.role)))
  })

  const directPermissionsCount = computed(() => {
    if (isCustomRoleSelected.value) {
      return form.value.permissions.length
    }

    return form.value.permissions.filter(
      permission => !inheritedRolePermissions.value.includes(permission),
    ).length
  })

  const effectivePermissionsCount = computed(() => {
    if (isCustomRoleSelected.value) {
      return Array.from(new Set(form.value.permissions)).length
    }

    return Array.from(
      new Set([...inheritedRolePermissions.value, ...form.value.permissions]),
    ).length
  })

  const activeScopedPermissionsCount = computed(() => {
    if (scopedPermissionNames.value.length === 0) {
      return 0
    }

    const effective = new Set(
      isCustomRoleSelected.value
        ? [...form.value.permissions]
        : [...inheritedRolePermissions.value, ...form.value.permissions],
    )
    const scopedSet = new Set(scopedPermissionNames.value)
    return Array.from(effective).filter(permission => scopedSet.has(permission)).length
  })

  const isCustomRoleSelected = computed(() => {
    return (
      form.value.role === CUSTOM_ROLE_PLACEHOLDER
      || isCustomEnterpriseRoleName(form.value.role)
    )
  })

  const moduleStyle: Record<string, { icon: string, color: string }> = {
    nc: { icon: 'mdi-alert-octagon', color: 'error' },
    audits: { icon: 'mdi-clipboard-check', color: 'info' },
    risks: { icon: 'mdi-alert-circle', color: 'warning' },
    actions: { icon: 'mdi-check-circle', color: 'success' },
    reclamations: { icon: 'mdi-comment-alert', color: 'deep-orange' },
    documents: { icon: 'mdi-file-document', color: 'primary' },
    processes: { icon: 'mdi-sitemap', color: 'indigo' },
    indicators: { icon: 'mdi-chart-line', color: 'teal' },
    objectives: { icon: 'mdi-bullseye-arrow', color: 'pink' },
    plans: { icon: 'mdi-file-document-edit', color: 'purple' },
    dashboard: { icon: 'mdi-view-dashboard', color: 'cyan' },
    leadership: { icon: 'mdi-account-tie', color: 'amber' },
    personnel: { icon: 'mdi-account-group', color: 'amber-darken-2' },
    sites: { icon: 'mdi-map-marker', color: 'green-darken-1' },
  }

  const permissionGroups = computed(() => {
    return permissionModules.value.map(module => {
      const style = moduleStyle[module.module] || {
        icon: 'mdi-shield-key',
        color: 'grey',
      }
      return {
        id: module.module,
        name: module.module,
        icon: style.icon,
        color: style.color,
        permissions: module.permissions.map(permission => permission.name),
      }
    })
  })

  function isInheritedPermission (permission: string): boolean {
    return inheritedRolePermissions.value.includes(permission)
  }

  function isDefaultReadPermission (permission: string): boolean {
    return false
  }

  function isPermissionLocked (permission: string): boolean {
    if (isCustomRoleSelected.value) {
      return false
    }
    return (
      isInheritedPermission(permission) || isDefaultReadPermission(permission)
    )
  }

  function isPermissionChecked (permission: string): boolean {
    if (isCustomRoleSelected.value) {
      return form.value.permissions.includes(permission)
    }

    if (
      isInheritedPermission(permission)
      || isDefaultReadPermission(permission)
    ) {
      return true
    }

    return form.value.permissions.includes(permission)
  }

  function togglePermission (permission: string, checked: boolean) {
    if (isPermissionLocked(permission)) {
      return
    }

    if (checked && !form.value.permissions.includes(permission)) {
      form.value.permissions = [...form.value.permissions, permission]
      return
    }

    if (!checked) {
      form.value.permissions = form.value.permissions.filter(
        current => current !== permission,
      )
    }
  }

  function formatPermissionLabel (permission: string): string {
    const segments = permission.split('.').filter(Boolean)
    const actionKey = segments.length > 1 ? segments.at(-1) || '' : ''
    const scopeKeys = segments.length > 1 ? segments.slice(0, -1) : segments
    const moduleKey = scopeKeys[0] || ''
    const resourceKey = scopeKeys.length > 1 ? scopeKeys.slice(1).join('.') : ''

    const humanize = (value: string) =>
      value.replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

    const normalizeKey = (value: string) => value.toLowerCase().replace(/-/g, '_')

    const moduleLabels: Record<string, string> = {
      dashboard: 'Tableau de bord',
      users: 'Utilisateurs',
      personnel: 'Personnel',
      roles: 'Roles',
      permissions: 'Permissions',
      documents: 'Documents',
      actions: 'Actions',
      audits: 'Audits',
      risks: 'Risques',
      reclamations: 'Reclamations',
      non_conformities: 'Non-conformites',
      indicateurs: 'Indicateurs',
      management_reviews: 'Revues de direction',
      objectives: 'Objectifs',
      contexts: 'Contexte',
      stakeholders: 'Parties prenantes',
      process: 'Processus',
      processes: 'Processus',
      subscriptions: 'Abonnements',
      offers: 'Offres',
      sites: 'Sites',
      settings: 'Parametres',
      reports: 'Rapports',
      norm_library: 'Bibliotheque des normes',
      equipements: 'Equipements',
      habilitations: 'Habilitations',
      competence_matrix: 'Matrice de competences',
      leadership: 'Leadership',
      securite: 'Securite',
    }

    const resourceLabels: Record<string, string> = {
      'roles_responsabilites': 'Roles et responsabilites',
      'roles_responsabilites.organigramme': 'Organigramme',
      'roles_responsabilites.personnel': 'Liste du personnel',
      'roles_responsabilites.fiche_poste': 'Fiches de poste',
      'roles_responsabilites.fiche_responsabilite': 'Fiches de responsabilite',
      'politique': 'Politique',
      'epi': 'EPI',
    }

    const moduleLabel = moduleLabels[normalizeKey(moduleKey)] || humanize(moduleKey)
    const resourceLabel = resourceKey
      ? (resourceLabels[normalizeKey(resourceKey)] || humanize(resourceKey))
      : ''

    const actionLabels: Record<string, string> = {
      read: 'Lecture',
      create: 'Creation',
      update: 'Modification',
      delete: 'Suppression',
      reset_password: 'Reinitialisation du mot de passe',
      validate: 'Validation',
      approve: 'Approbation',
      assign: 'Assignation',
      assign_roles: 'Assignation des roles',
      verify: 'Verification',
      analyze: 'Analyse',
      close: 'Cloture',
      track: 'Suivi',
      conduct: 'Conduite',
      export: 'Export',
      publish: 'Publication',
      archive: 'Archivage',
      assess: 'Evaluation',
      treat: 'Traitement',
      monitor: 'Surveillance',
      respond: 'Reponse',
      plan: 'Planification',
      manage: 'Gestion',
      configure: 'Configuration',
      download: 'Telechargement',
    }

    const actionLabel = actionLabels[normalizeKey(actionKey)] || humanize(actionKey)
    const scopeLabel = resourceLabel
      ? `${moduleLabel} / ${resourceLabel}`
      : moduleLabel

    if (!actionKey) {
      return scopeLabel
    }

    return `${scopeLabel} - ${actionLabel}`
  }

  const {
    previewPersonnel,
    exportPersonnelCsv,
    exportPersonnelDocx,
    exportPersonnelPdf,
    submitPersonnelForVerification,
    lastExportedDocumentId,
    submittingForVerification,
  } = usePersonnelExports(personnel, authStore, pdfExportService, exportingPdf)

  function isValidEmailFormat (value: string): boolean {
    const email = String(value || '').trim()
    if (!email) {
      return false
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)
  }

  const isStepValid = computed(() => {
    return !!(
      form.value.nom.trim()
      && form.value.prenoms.trim()
      && isValidEmailFormat(form.value.email)
      && form.value.poste.trim()
      && form.value.site_id
    )
  })

  const isRoleStepValid = computed(() => {
    if (!form.value.role) {
      return false
    }

    if (form.value.role === CUSTOM_ROLE_PLACEHOLDER) {
      return Boolean(form.value.custom_role_name.trim())
    }

    return true
  })

  function resolveCustomRoleName (): string {
    const enterpriseId = Number(authStore.user?.enterprise?.id || 0)
    const prefix = `custom_enterprise_${enterpriseId}_`
    const slug = sanitizeRoleSlug(form.value.custom_role_name)
    return `${prefix}${slug}`
  }

  function findEnterpriseCustomRoleByName (roleName: string): Role | null {
    const normalized = String(roleName || '').trim()
    if (!normalized) {
      return null
    }

    return (
      roleCatalog.value.find(
        role => String(role.name || '').trim() === normalized,
      ) || null
    )
  }

  async function ensureEnterpriseCustomRole (): Promise<Role> {
    const enterpriseId = Number(resolveActorEnterpriseId() || 0)
    const selectedSiteId = Number(form.value.site_id || 0) || null
    if (!enterpriseId) {
      throw new Error(
        'Impossible de creer un role personnalise sans entreprise.',
      )
    }

    const requestedName = resolveCustomRoleName()
    const permissions = Array.from(new Set(form.value.permissions))
    const existing = findEnterpriseCustomRoleByName(requestedName)

    if (existing) {
      const response = await api.put(`/roles/${existing.id}`, {
        name: requestedName,
        description: `Role personnalise entreprise #${enterpriseId}`,
        permissions,
        site_id: selectedSiteId,
      })
      const updated = response.data?.data || response.data
      const normalized: Role = {
        id: Number(updated?.id || existing.id),
        name: String(updated?.attributes?.name || updated?.name || requestedName),
        label: form.value.custom_role_name || 'Role personnalise',
        permissions_count: permissions.length,
        permissions,
      }
      roleCatalog.value = roleCatalog.value.map(role =>
        role.id === existing.id ? normalized : role,
      )
      return normalized
    }

    const response = await api.post('/roles', {
      name: requestedName,
      enterprise_id: enterpriseId,
      description: `Role personnalise entreprise #${enterpriseId}`,
      permissions,
      site_id: selectedSiteId,
    })
    const created = response.data?.data || response.data
    const normalized: Role = {
      id: Number(created?.id),
      name: String(created?.attributes?.name || created?.name || requestedName),
      label: form.value.custom_role_name || 'Role personnalise',
      permissions_count: permissions.length,
      permissions,
    }
    roleCatalog.value = [...roleCatalog.value, normalized]
    return normalized
  }

  function getInitials (nom: string, prenoms: string): string {
    const a = nom?.charAt(0) || ''
    const b = prenoms?.charAt(0) || ''
    return `${a}${b}`.toUpperCase() || 'NA'
  }

  function openCreateDialog () {
    editMode.value = false
    editingId.value = null
    resetForm()
    // Initialize form.site_id with current user site
    form.value.site_id = authStore.currentSiteId ? Number(authStore.currentSiteId) : null
    // Contrat unifié: rôle hérité = baseline, permissions directes = exceptions explicites.
    form.value.permissions = []
    showDialog.value = true
    dialogTab.value = 'info'
    // Load permissions AFTER dialog is shown
    if (form.value.site_id) {
      void loadAccessCatalog()
    }
  }

  function editPerson (person: Person) {
    editMode.value = true
    editingId.value = person.id
    form.value = {
      nom: person.nom,
      prenoms: person.prenoms,
      telephone: person.telephone,
      email: person.email,
      poste: person.poste === '—' ? '' : person.poste,
      role: person.role || '',
      site_id: person.siteId || authStore.currentSiteId,
      adresse: person.adresse,
      datePriseService: person.datePriseService,
      custom_role_name: '',
      permissions: [...(person.permissions || [])],
    }
    showDialog.value = true
    dialogTab.value = 'info'
  }

  function closeDialog () {
    showDialog.value = false
    resetForm()
  }

  function openDetailsDialog (person: Person) {
    selectedPersonDetails.value = person
    showDetailsDialog.value = true
  }

  function closeDetailsDialog () {
    showDetailsDialog.value = false
    selectedPersonDetails.value = null
  }

  function editFromDetails () {
    if (!selectedPersonDetails.value) {
      return
    }

    const person = selectedPersonDetails.value
    closeDetailsDialog()
    editPerson(person)
  }

  function resetForm () {
    form.value = {
      nom: '',
      prenoms: '',
      telephone: '',
      email: '',
      poste: '',
      role: '',
      site_id: null,
      adresse: '',
      datePriseService: '',
      custom_role_name: '',
      permissions: [],
    }
  }

  function normalizePersonNames (attributes: any) {
    const firstName = attributes.first_name || ''
    const lastName = attributes.last_name || ''
    const fallbackName = attributes.name || attributes.email || 'Utilisateur'
    const fullName = `${lastName} ${firstName}`.trim() || fallbackName
    return { firstName, lastName, fullName }
  }

  function resolvePermissionsFromEntity (
    attributes: any,
    relationships: any,
  ): string[] {
    const permissionResources = Array.isArray(relationships.permissions)
      ? relationships.permissions
      : []

    return permissionResources
      .map((perm: any) => perm?.attributes?.name || perm?.name)
      .filter((name: any) => typeof name === 'string')
  }

  function resolveRolePermissionsCountFromEntity (
    attributes: any,
    relationships: any,
  ): number {
    const fromAttributes = Number(attributes.role_permissions_count)
    if (Number.isFinite(fromAttributes) && fromAttributes >= 0) {
      return fromAttributes
    }

    const roles = Array.isArray(relationships.roles) ? relationships.roles : []
    const rolePermissionNames = new Set<string>()

    for (const role of roles) {
      const rolePermissions = Array.isArray(role?.relationships?.permissions)
        ? role.relationships.permissions
        : []

      for (const permission of rolePermissions) {
        const permissionName = permission?.attributes?.name || permission?.name
        if (typeof permissionName === 'string' && permissionName.trim()) {
          rolePermissionNames.add(permissionName)
        }
      }
    }

    return rolePermissionNames.size
  }

  function resolveDirectPermissionsCountFromEntity (
    attributes: any,
    directPermissions: string[],
  ): number {
    const fromAttributes = Number(attributes.direct_permissions_count)
    if (Number.isFinite(fromAttributes) && fromAttributes >= 0) {
      return fromAttributes
    }
    return directPermissions.length
  }

  function resolveEffectivePermissionsCountFromEntity (
    attributes: any,
    directPermissions: string[],
    rolePermissionsCount: number,
  ): number {
    const fromAttributes = Number(attributes.effective_permissions_count)
    if (Number.isFinite(fromAttributes) && fromAttributes >= 0) {
      return fromAttributes
    }
    return directPermissions.length + rolePermissionsCount
  }

  function resolveActiveScopedPermissionsCountFromEntity (
    attributes: any,
    effectivePermissionsCount: number,
  ): number {
    const fromAttributes = Number(attributes.active_scoped_permissions_count)
    if (Number.isFinite(fromAttributes) && fromAttributes >= 0) {
      return fromAttributes
    }
    // Fallback for payloads that do not expose active scoped metrics yet.
    return effectivePermissionsCount
  }

  function resolveRoleFromAttributes (attributes: any, roleNames: string[]) {
    let poste
      = attributes.job_title || attributes.position || attributes.role || ''
    if (!poste && roleNames.includes('admin_entreprise')) {
      poste = 'Admin entreprise'
    } else if (!poste && roleNames.includes('site_manager')) {
      poste = 'Responsable de site'
    }
    return poste || '—'
  }

  function resolveSiteNameFromEntity (
    attributes: any,
    siteResource: any,
    siteId: number | null,
  ): string | undefined {
    const fromRelationship = String(siteResource?.attributes?.name || '').trim()
    if (fromRelationship) {
      return fromRelationship
    }

    const fromAttributes = String(
      attributes.site_name || attributes.site?.name || '',
    ).trim()
    if (fromAttributes) {
      return fromAttributes
    }

    if (siteId && siteNameById.value.has(siteId)) {
      return siteNameById.value.get(siteId)
    }

    return undefined
  }

  function mapUserToPerson (entity: JsonApiEntity): Person {
    const attributes: any = entity.attributes || entity
    const relationships: any = entity.relationships || {}
    const siteResource: any = relationships.site || null

    const permissions = resolvePermissionsFromEntity(attributes, relationships)
    const rolePermissionsCount = resolveRolePermissionsCountFromEntity(
      attributes,
      relationships,
    )
    const directPermissionsCount = resolveDirectPermissionsCountFromEntity(
      attributes,
      permissions,
    )
    const effectivePermissionsCount = resolveEffectivePermissionsCountFromEntity(
      attributes,
      permissions,
      rolePermissionsCount,
    )
    const activeScopedPermissionsCount
      = resolveActiveScopedPermissionsCountFromEntity(
        attributes,
        effectivePermissionsCount,
      )

    const roleNames: string[] = Array.isArray(attributes.role_names)
      ? attributes.role_names
      : []
    const { firstName, lastName, fullName } = normalizePersonNames(attributes)
    const poste = resolveRoleFromAttributes(attributes, roleNames)

    const siteId = Number(attributes.site_id ?? siteResource?.id ?? null) || null

    return {
      id: Number(entity.id),
      nom: lastName,
      prenoms: firstName,
      fullName,
      telephone: attributes.phone || '',
      email: attributes.email || '',
      poste,
      adresse: attributes.address || '',
      datePriseService: attributes.start_date || '',
      role: roleNames[0] || '',
      siteId,
      siteName: resolveSiteNameFromEntity(attributes, siteResource, siteId),
      permissions,
      directPermissionsCount,
      rolePermissionsCount,
      activeScopedPermissionsCount,
      permissionsCount: effectivePermissionsCount,
      approvalStatus: String(attributes.collaborator_approval_status || '').trim(),
      approvalRequestedAt: String(attributes.collaborator_requested_at || '').trim(),
      approvalRequestedByName: String(attributes.collaborator_requested_by_name || '').trim(),
      approvalRejectedAt: String(attributes.collaborator_rejected_at || '').trim(),
      approvalRejectedByName: String(attributes.collaborator_rejected_by_name || '').trim(),
      approvalRejectionReason: String(attributes.collaborator_rejection_reason || '').trim(),
    }
  }

  const pendingApprovals = computed(() => {
    return personnel.value.filter(
      person => person.approvalStatus === 'pending_admin_approval',
    )
  })

  function formatDateTime (value?: string): string {
    if (!value) {
      return 'Non defini'
    }
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
      return 'Non defini'
    }
    return date.toLocaleString('fr-FR')
  }

  async function approvePendingRequest (person: Person) {
    approvalActionLoadingId.value = person.id
    try {
      await api.post(`/users/${person.id}/approve`)
      await loadPersonnel()
      alert(` Demande approuvée pour ${person.email}.`)
    } catch (error: any) {
      const message = error.response?.data?.message || 'Erreur lors de l’approbation.'
      alert(` ${message}`)
    } finally {
      approvalActionLoadingId.value = null
    }
  }

  async function rejectPendingRequest (person: Person) {
    const reason = prompt(
      `Motif de rejet pour ${person.email} (obligatoire):`,
      person.approvalRejectionReason || '',
    )
    if (reason === null) {
      return
    }
    if (reason.trim().length < 5) {
      alert(' Le motif de rejet est obligatoire (minimum 5 caractères).')
      return
    }

    approvalActionLoadingId.value = person.id
    try {
      await api.post(`/users/${person.id}/reject`, { reason: reason.trim() })
      await loadPersonnel()
      alert(` Demande rejetée pour ${person.email}.`)
    } catch (error: any) {
      const message = error.response?.data?.message || 'Erreur lors du rejet.'
      alert(` ${message}`)
    } finally {
      approvalActionLoadingId.value = null
    }
  }

  async function loadPersonnel () {
    if (loadPersonnelPromise) {
      return loadPersonnelPromise
    }

    loadPersonnelPromise = (async () => {
      try {
        const params
          = siteContextStore.activeScope === 'site' && siteContextStore.activeSiteId
            ? { site_id: Number(siteContextStore.activeSiteId) }
            : undefined
        const response = await api.get('/users', { params })
        const rawUsers = Array.isArray(response.data?.data)
          ? response.data.data
          : []
        personnel.value = rawUsers
          .filter(
            (user: JsonApiEntity) =>
              (user.attributes?.user_type || (user as any).user_type)
              === 'company',
          )
          .map((user: JsonApiEntity) => mapUserToPerson(user))
          .filter((user: Person) => user.email && user.id)
      } catch (error) {
        console.error('Erreur chargement:', error)
      } finally {
        loadPersonnelPromise = null
      }
    })()

    return loadPersonnelPromise
  }

  async function saveCollaborator () {
    loading.value = true
    try {
      if (!form.value.site_id) {
        alert(' Sélectionnez explicitement un site d’affectation.')
        return
      }

      if (!isValidEmailFormat(form.value.email)) {
        alert(' Le format de l’email est invalide. Utilisez Nom_utilisateur@domaine.extension.')
        return
      }

      let selectedRole = form.value.role
      if (selectedRole === CUSTOM_ROLE_PLACEHOLDER) {
        if (!canManageCustomRole.value) {
          throw new Error(
            'Vous n\'etes pas autorise a gerer un role personnalise.',
          )
        }
        const customRole = await ensureEnterpriseCustomRole()
        selectedRole = customRole.name
      }

      const data: Record<string, any> = {
        first_name: form.value.prenoms,
        last_name: form.value.nom,
        email: form.value.email,
        user_type: 'company',
        phone: form.value.telephone,
        address: form.value.adresse,
        job_title: form.value.poste,
        start_date: form.value.datePriseService || null,
        enterprise_id: resolveActorEnterpriseId(),
        site_id: form.value.site_id,
        access_role: selectedRole,
        is_active: true,
        send_welcome_email: true,
      }

      // RBAC roles-only: ne plus écrire de permissions directes utilisateur.

      if (editMode.value && editingId.value) {
        await api.put(`/users/${editingId.value}`, data)
        await loadPersonnel()
        alert(' Collaborateur modifié avec succès !')
      } else {
        const response = await api.post('/users', data)
        const payload = response?.data || {}
        const notifications = payload.notifications || {}
        const approvalRequired = notifications.approval_required === true
        const activationEmailSent = notifications.activation_email_sent !== false
        const verificationEmailSent = notifications.verification_email_sent !== false

        await loadPersonnel()

        if (approvalRequired) {
          alert(
            ` Demande créée avec succès !\n\nLe compte de ${form.value.email} reste bloqué jusqu'à validation par l'admin d'entreprise.`,
          )
        } else if (activationEmailSent && verificationEmailSent) {
          alert(
            ` Collaborateur créé avec succès !\n\nUne invitation d'activation a été envoyée à ${form.value.email}`,
          )
        } else {
          const warningLines = [
            'Collaborateur créé avec succès, mais un ou plusieurs emails automatiques n’ont pas pu être envoyés.',
            `Email du collaborateur: ${form.value.email}`,
            'Action recommandée: vérifiez l’adresse email, puis utilisez le renvoi d’invitation/vérification.',
          ]
          alert(` ${warningLines.join('\n')}`)
        }
      }

      lastSavedAt.value = new Date()
      closeDialog()
    } catch (error: any) {
      console.error('Erreur sauvegarde:', error)
      const message
        = error.response?.data?.message || 'Erreur lors de la sauvegarde'
      alert(` ${message}`)
    } finally {
      loading.value = false
    }
  }

  async function deletePerson (id: number) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce collaborateur ?')) {
      try {
        await api.delete(`/users/${id}`)
        personnel.value = personnel.value.filter(p => p.id !== id)
        lastSavedAt.value = new Date()
        alert(' Collaborateur supprimé')
      } catch {
        alert(' Erreur lors de la suppression')
      }
    }
  }

  async function handleExcelUpload (file: File) {
    loading.value = true
    importResult.value = null

    try {
      if (!authStore.currentSiteId) {
        importResult.value = {
          type: 'error',
          title: 'Site requis',
          message:
            'Aucun site actif sélectionné. Sélectionnez un site puis relancez l\'import.',
        }
        return
      }

      // Appel API pour importer les utilisateurs
      const result = await usersService.importUsers(file, {
        default_role: 'lecteur',
        send_welcome_email: true,
        site_id: authStore.currentSiteId,
      })

      const approvalRequired = result.notifications?.approval_required === true

      importResult.value = {
        type: approvalRequired ? 'info' : 'success',
        title: approvalRequired
          ? 'Import lancé (validation requise)'
          : 'Import lancé avec succès',
        message: approvalRequired
          ? `${result.message} Ouvrez l'onglet "Demandes" pour valider les comptes avant activation.`
          : result.message
            + ' Rechargez la page dans quelques instants pour voir les nouveaux collaborateurs.',
      }

      // Recharger la liste après 5 secondes
      setTimeout(() => {
        loadPersonnel()
      }, 5000)
    } catch (error: any) {
      console.error('Erreur import:', error)
      importResult.value = {
        type: 'error',
        title: 'Erreur d\'import',
        message:
          error.response?.data?.message
          || 'Impossible d\'importer le fichier. Vérifiez le format.',
      }
    } finally {
      loading.value = false
    }
  }

  function downloadTemplate () {
    const workbook = XLSX.utils.book_new()

    const templateRows = [
      [
        'Prénoms',
        'Nom',
        'Email',
        'Téléphone',
        'Poste',
        'Adresse',
        'Date de prise de service',
        'Rôle',
      ],
      [
        'Jean',
        'Dupont',
        'jean.dupont@example.com',
        '+2250102030405',
        'Responsable Qualité',
        '123 Rue de la Paix, Abidjan',
        '2026-01-15',
        'lecteur',
      ],
    ]

    const legendRows = [
      ['Champ', 'Obligatoire', 'Format attendu', 'Exemple', 'Description'],
      ['Prénoms', 'Oui', 'Texte', 'Jean', 'Prénom(s) du collaborateur'],
      ['Nom', 'Oui', 'Texte', 'Dupont', 'Nom du collaborateur'],
      ['Email', 'Oui', 'Nom_utilisateur@domaine.extension', 'jean.dupont@example.com', 'Adresse e-mail valide pour les notifications'],
      ['Téléphone', 'Non', 'Texte ou numérique', '+2250102030405', 'Téléphone du collaborateur'],
      ['Poste', 'Oui', 'Texte', 'Responsable Qualité', 'Intitulé du poste'],
      ['Adresse', 'Non', 'Texte', 'Abidjan', 'Adresse postale'],
      ['Date de prise de service', 'Non', 'YYYY-MM-DD', '2026-01-15', 'Date d\'embauche ou de prise de poste'],
      ['Rôle', 'Oui', 'Valeur rôle', 'lecteur', 'Valeurs recommandées: admin_entreprise, site_manager, lecteur'],
      ['', '', '', '', 'Pour un rôle personnalisé: custom_enterprise_<id>_<slug>'],
    ]

    const templateSheet = XLSX.utils.aoa_to_sheet(templateRows)
    const legendSheet = XLSX.utils.aoa_to_sheet(legendRows)

    XLSX.utils.book_append_sheet(workbook, templateSheet, 'Template')
    XLSX.utils.book_append_sheet(workbook, legendSheet, 'Légende')

    XLSX.writeFile(workbook, 'modele_import_collaborateurs.xlsx')
  }

  async function loadAccessCatalog () {
    if (loadAccessCatalogPromise) {
      return loadAccessCatalogPromise
    }

    loadAccessCatalogPromise = (async () => {
      try {
        const effectiveSiteId = Number(
          form.value.site_id
          || siteContextStore.activeSiteId
          || authStore.currentSiteId
          || siteOptions.value[0]?.id
          || 0,
        ) || undefined
        if (!form.value.site_id && effectiveSiteId) {
          form.value.site_id = effectiveSiteId
        }

        const [rolesResponse, permissionsResponse] = await Promise.all([
          permissionService.getAvailableRoles(effectiveSiteId),
          permissionService.getActivePermissions(effectiveSiteId),
        ])
        roleCatalog.value = rolesResponse
        permissionModules.value = permissionsResponse

        if (
          form.value.role
          && !roleCatalog.value.some(role => role.name === form.value.role)
        ) {
          form.value.role = roleCatalog.value[0]?.name || ''
        }

        scopedPermissionNames.value = permissionModules.value
          .flatMap(module =>
            module.permissions.map(permission =>
              String(permission.name || '')
                .trim()
                .toLowerCase(),
            ),
          )
          .filter(Boolean)
        scopedPermissionNames.value = Array.from(new Set(scopedPermissionNames.value))
      } catch (error) {
        console.error('Erreur chargement catalogue des acces:', error)
        roleCatalog.value = []
        permissionModules.value = []
        scopedPermissionNames.value = []
      } finally {
        loadAccessCatalogPromise = null
      }
    })()

    return loadAccessCatalogPromise
  }

  async function loadSiteOptions () {
    if (loadSiteOptionsPromise) {
      return loadSiteOptionsPromise
    }

    loadSiteOptionsPromise = (async () => {
      const authSites = (authStore.availableSites || [])
        .map((site: any) => ({
          id: Number(site?.id),
          name: String(site?.name || 'Site sans nom'),
        }))
        .filter((site: { id: number }) => Number.isFinite(site.id))

      try {
        const response = await api.get('/sites', { params: { per_page: 100 } })
        const rawSites = Array.isArray(response.data?.data)
          ? response.data.data
          : []
        const apiSites = rawSites
          .map((site: any) => ({
            id: Number(site?.id),
            name: String(site?.attributes?.name || site?.name || 'Site sans nom'),
          }))
          .filter((site: { id: number }) => Number.isFinite(site.id))

        const merged = [...authSites, ...apiSites]
        const deduped = new Map<number, { id: number, name: string }>()

        const isPlaceholderName = (name: string) => {
          const normalized = String(name || '')
            .trim()
            .toLowerCase()
          return (
            normalized === ''
            || normalized === 'site sans nom'
            || normalized.startsWith('site #')
          )
        }

        for (const site of merged) {
          const existing = deduped.get(site.id)
          if (!existing) {
            deduped.set(site.id, site)
            continue
          }

          if (isPlaceholderName(existing.name) && !isPlaceholderName(site.name)) {
            deduped.set(site.id, site)
          }
        }
        siteOptions.value = [...deduped.values()]
      } catch (error) {
        console.error('Erreur chargement sites (personnel):', error)
        siteOptions.value = authSites
      } finally {
        loadSiteOptionsPromise = null
      }
    })()

    return loadSiteOptionsPromise
  }

  function handleSiteContextChanged () {
    if (siteContextChangeTimer) {
      clearTimeout(siteContextChangeTimer)
    }

    siteContextChangeTimer = setTimeout(() => {
      Promise.all([loadAccessCatalog(), loadPersonnel()])
    }, 250)
  }

  watch(
    () => form.value.site_id,
    () => {
      loadAccessCatalogPromise = null
      void loadAccessCatalog()
    },
    { flush: 'post' },
  )

  onMounted(async () => {
    await loadSiteOptions()
    // Initialize site_id with current user's site if available
    if (!form.value.site_id && authStore.currentSiteId) {
      form.value.site_id = Number(authStore.currentSiteId)
    }
    await loadAccessCatalog()
    await loadPersonnel()
    window.addEventListener('site-context-changed', handleSiteContextChanged)
  })

  onUnmounted(() => {
    if (siteContextChangeTimer) {
      clearTimeout(siteContextChangeTimer)
      siteContextChangeTimer = null
    }
    window.removeEventListener('site-context-changed', handleSiteContextChanged)
  })

  watch(
    () => form.value.site_id,
    async (siteId, previousSiteId) => {
      if (siteId === previousSiteId) {
        return
      }

      await loadAccessCatalog()

      if (
        form.value.role
        && !roleCatalog.value.some(role => role.name === form.value.role)
      ) {
        form.value.role = roleCatalog.value[0]?.name || ''
      }
    },
  )

  watch(
    () => form.value.role,
    () => {
      const sanitizedRole = sanitizeSelectedRole({
        selectedRole: form.value.role,
        canManageCustomRole: canManageCustomRole.value,
        fallbackRole: roleCatalog.value[0]?.name || '',
      })
      if (sanitizedRole !== form.value.role) {
        form.value.role = sanitizedRole
        return
      }

      if (form.value.role === CUSTOM_ROLE_PLACEHOLDER) {
        return
      }

      if (isCustomEnterpriseRoleName(form.value.role)) {
        const inherited = getRolePermissionNames(form.value.role)
        const baseLabel = String(form.value.role)
          .replace(/^custom_enterprise_\d+_/, '')
          .replace(/_/g, ' ')
          .trim()
        if (!form.value.custom_role_name) {
          form.value.custom_role_name
            = baseLabel.charAt(0).toUpperCase() + baseLabel.slice(1)
        }
        form.value.permissions = Array.from(
          new Set([...form.value.permissions, ...inherited]),
        )
        return
      }

      // Empêche de stocker en direct une permission déjà héritée du rôle sélectionné.
      form.value.permissions = form.value.permissions.filter(
        permission => !isInheritedPermission(permission),
      )
    },
  )
</script>

<style scoped>
.hero-progress-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  align-items: stretch;
  margin-bottom: 20px;
}

:deep(.meta-badges) {
  flex-wrap: nowrap;
  gap: 10px;
}

:deep(.hero-card) {
  padding: 16px 20px;
}

:deep(.hero-icon) {
  width: 52px;
  height: 52px;
  border-radius: 14px;
}

:deep(.hero-title) {
  font-size: 1.5rem;
  margin-bottom: 4px;
}

:deep(.hero-subtitle) {
  font-size: 0.875rem;
  margin-bottom: 8px;
}

.panel-loader-overlay {
  position: sticky;
  top: 0;
  z-index: 9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0 14px;
  margin-bottom: 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(2px);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
  border: none;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}

.btn-primary:hover {
  box-shadow: 0 6px 20px rgba(91, 141, 217, 0.4);
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.permissions-tech-details {
  width: 100%;
}

.permissions-tech-details > summary {
  cursor: pointer;
  color: #1d4ed8;
  font-weight: 600;
  font-size: 0.88rem;
}

.permissions-tech-content {
  margin-top: 8px;
  color: #334155;
  font-size: 0.86rem;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.pending-approvals-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pending-approvals-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pending-approvals-header h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.pending-badge {
  background: #fef3c7;
  color: #92400e;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.8rem;
  font-weight: 700;
}

.pending-empty,
.pending-info {
  padding: 12px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.pending-grid {
  display: grid;
  gap: 12px;
}

.pending-card {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px;
  background: white;
}

.pending-card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.pending-card-status {
  border-radius: 999px;
  background: #ffedd5;
  color: #9a3412;
  font-size: 0.75rem;
  padding: 3px 8px;
  font-weight: 700;
}

.pending-card-body {
  display: grid;
  gap: 6px;
  color: #334155;
  font-size: 0.9rem;
}

.pending-card-actions {
  display: flex;
  gap: 10px;
  margin-top: 12px;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

@media (max-width: 768px) {
  .hero-progress-row {
    grid-template-columns: 1fr;
  }
}
</style>
