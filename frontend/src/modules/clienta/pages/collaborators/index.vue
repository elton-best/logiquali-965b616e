<template>
  <ClientALayout current-page="collaborators">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <PageHeader
        icon="mdi-account-group"
        subtitle="Gérez les utilisateurs et leurs rôles d'accès par site"
        title="Collaborateurs"
      >
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-account-plus" size="large" @click="openCreateDialog">
            Nouveau collaborateur
          </v-btn>
        </template>
      </PageHeader>

      <!-- Stats Cards -->
      <CollaboratorsStats
        :sites-count="sites.length"
        :stats="stats"
      />

      <!-- Table -->
      <CollaboratorsTable
        :get-initials="getInitials"
        :get-user-site-label="getUserSiteLabel"
        :headers="headers"
        :items="users"
        :loading="loading"
        @delete="confirmDelete"
        @edit="editUser"
        @view="viewUser"
      />

      <!-- Create/Edit Dialog -->
      <FormDialog
        v-model="dialog"
        :edit-mode="editMode"
        :loading="saving"
        max-width="1000"
        :title="`${editMode ? 'Modifier' : 'Nouveau'} collaborateur`"
        @cancel="closeDialog"
        @submit="saveUser"
      >
        <div v-if="saving" class="form-loader-overlay">
          <UnifiedLoader
            description="Validation du profil et des permissions"
            :show-skeleton="true"
            title="Enregistrement du collaborateur en cours..."
            variant="local"
          />
        </div>

        <!-- Informations personnelles -->
        <div class="mb-6">
          <h3 class="text-h6 mb-4 d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-account</v-icon>
            Informations personnelles
          </h3>
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.first_name"
                density="comfortable"
                label="Prénom *"
                prepend-inner-icon="mdi-account"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.last_name"
                density="comfortable"
                label="Nom *"
                prepend-inner-icon="mdi-account"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.email"
                density="comfortable"
                label="Email *"
                prepend-inner-icon="mdi-email"
                :rules="[rules.required, rules.email]"
                type="email"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.phone"
                density="comfortable"
                label="Téléphone (optionnel)"
                prepend-inner-icon="mdi-phone"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.role"
                density="comfortable"
                label="Poste *"
                placeholder="Ex: Responsable Qualité, Auditeur Interne..."
                prepend-inner-icon="mdi-briefcase"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>
          </v-row>

          <v-alert v-if="!editMode" class="mt-4" type="info" variant="tonal">
            <v-icon class="mr-2">mdi-information</v-icon>
            Un mot de passe sera généré automatiquement et envoyé par email au collaborateur.
          </v-alert>
        </div>

        <!-- Affectation Site -->
        <div class="mb-6">
          <h3 class="text-h6 mb-4 d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
            Affectation Site
          </h3>
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.site_id"
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="filteredSites"
                label="Site *"
                prepend-inner-icon="mdi-map-marker"
                :rules="[rules.required]"
                variant="outlined"
                @update:model-value="onSiteChange"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.access_roles"
                chips
                closable-chips
                density="comfortable"
                item-title="label"
                item-value="name"
                :items="availableRoles"
                label="Rôle(s) d'accès *"
                multiple
                prepend-inner-icon="mdi-shield-account"
                :rules="[v => !!v.length || 'Champ requis']"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-switch
                v-model="form.is_active"
                color="success"
                hide-details
                label="Compte actif"
              />
            </v-col>
          </v-row>
        </div>

        <!-- Permissions par Site -->
        <div class="mb-6">
          <h3 class="text-h6 mb-4 d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-shield-key</v-icon>
            Permissions (aperçu local, non persisté) ({{ selectedPermissions?.length || 0 }} sélectionnées)
          </h3>

          <div class="d-flex justify-end mb-3 ga-2">
            <v-btn color="primary" size="small" variant="text" @click="selectAllPermissions">
              <v-icon start>mdi-checkbox-multiple-marked</v-icon>
              Tout sélectionner
            </v-btn>
            <v-btn color="error" size="small" variant="text" @click="deselectAllPermissions">
              <v-icon start>mdi-checkbox-multiple-blank-outline</v-icon>
              Tout désélectionner
            </v-btn>
          </div>

          <!-- Alert avec profil suggéré -->
          <v-alert
            v-if="form.access_roles?.length > 0 && suggestedPermissions.length > 0"
            class="mb-4"
            color="info"
            icon="mdi-lightbulb"
            variant="tonal"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="font-weight-medium mb-1">
                  Rôle(s) sélectionné(s) : {{ selectedRoleLabel }}
                </div>
                <div class="text-caption">
                  Ces rôles attribuent automatiquement leurs permissions.
                  <br>
                  <strong>{{ inheritedPermissions.length }} permissions de rôle</strong>
                </div>
              </div>
              <v-btn
                color="primary"
                size="small"
                variant="elevated"
                @click="applySuggestedPermissions"
              >
                <v-icon start>mdi-account-check-outline</v-icon>
                Pré-remplir l’aperçu
              </v-btn>
            </div>
          </v-alert>

          <v-alert
            v-if="form.site_id && form.access_roles?.length > 0"
            class="mb-4"
            color="primary"
            icon="mdi-shield-account"
            variant="tonal"
          >
            <div class="text-body-2">
              Permissions actives (site/souscription):
              <strong>{{ activePermissionsDisplayCount }}</strong>
            </div>
          </v-alert>

          <v-expansion-panels
            v-if="form.site_id && form.access_roles?.length > 0"
            class="mb-4"
            variant="accordion"
          >
            <v-expansion-panel>
              <v-expansion-panel-title>
                Détails techniques (avancé)
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <div class="text-caption text-medium-emphasis">
                  Permissions de rôle: <strong>{{ inheritedPermissions.length }}</strong> ·
                  Permissions directes: <strong>{{ selectedPermissions.length }}</strong> ·
                  Permissions effectives (avant contexte site/souscription): <strong>{{ effectivePermissions.length }}</strong>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>

          <v-alert
            v-if="form.site_id"
            class="mb-4"
            color="success"
            icon="mdi-information"
            variant="tonal"
          >
            Permissions attribuées pour le site: <strong>{{ getSiteName(form.site_id) }}</strong>
          </v-alert>

          <v-alert
            v-else
            class="mb-4"
            color="warning"
            icon="mdi-alert"
            variant="tonal"
          >
            Veuillez d'abord sélectionner un site
          </v-alert>

          <v-expansion-panels v-if="form.site_id" v-model="expandedPanels" multiple variant="accordion">
            <v-expansion-panel
              v-for="group in permissionGroups"
              :key="group.name"
            >
              <v-expansion-panel-title>
                <div class="d-flex align-center w-100">
                  <v-icon class="mr-2" :color="getGroupColor(group.name)">{{ group.icon }}</v-icon>
                  <span class="font-weight-medium">{{ group.label }}</span>
                  <v-spacer />
                  <v-chip class="mr-2" :color="getGroupColor(group.name)" size="small" variant="tonal">
                    {{ getGroupSelectedCount(group.name) }} / {{ group.permissions.length }}
                  </v-chip>
                </div>
              </v-expansion-panel-title>

              <v-expansion-panel-text>
                <div class="d-flex justify-end mb-3 gap-2">
                  <v-btn
                    color="primary"
                    size="small"
                    variant="text"
                    @click.stop="selectAllGroup(group.name)"
                  >
                    <v-icon start>mdi-checkbox-multiple-marked</v-icon>
                    Tout sélectionner
                  </v-btn>
                  <v-btn
                    color="error"
                    size="small"
                    variant="text"
                    @click.stop="deselectAllGroup(group.name)"
                  >
                    <v-icon start>mdi-checkbox-multiple-blank-outline</v-icon>
                    Tout désélectionner
                  </v-btn>
                </div>

                <v-row>
                  <v-col
                    v-for="permission in group.permissions"
                    :key="permission.value"
                    cols="12"
                    md="6"
                  >
                    <v-checkbox
                      v-model="selectedPermissions"
                      color="primary"
                      density="compact"
                      hide-details
                      :label="permission.label"
                      :value="permission.value"
                    />
                  </v-col>
                </v-row>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </div>
      </FormDialog>

      <!-- Delete Dialog -->
      <v-dialog v-model="deleteDialog" max-width="400">
        <v-card>
          <v-card-title class="text-h6">Confirmer la suppression</v-card-title>
          <v-card-text>
            Êtes-vous sûr de vouloir supprimer <strong>{{ userToDelete?.name }}</strong> ?
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
            <v-btn color="error" :loading="deleting" variant="flat" @click="deleteUser">Supprimer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- View Details Dialog -->
      <v-dialog v-model="viewDialog" max-width="800">
        <v-card v-if="selectedUser">
          <v-card-title class="bg-primary text-white">
            <v-icon class="mr-2">mdi-account-details</v-icon>
            Détails du collaborateur
          </v-card-title>

          <v-card-text class="pa-6">
            <v-row>
              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Nom complet</div>
                  <div class="text-h6">{{ selectedUser.name }}</div>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Email</div>
                  <div class="text-body-1">{{ selectedUser.email }}</div>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Nom d'utilisateur</div>
                  <div class="text-body-1">{{ selectedUser.username }}</div>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Téléphone</div>
                  <div class="text-body-1">{{ selectedUser.phone || 'Non renseigné' }}</div>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Site</div>
                  <div class="text-body-1">{{ getSiteName(selectedUser.site_id) }}</div>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Statut</div>
                  <v-chip :color="selectedUser.is_active ? 'success' : 'error'" size="small">
                    {{ selectedUser.is_active ? 'Actif' : 'Inactif' }}
                  </v-chip>
                </div>
              </v-col>

              <v-col cols="12">
                <v-alert class="mb-4" color="primary" icon="mdi-shield-check" variant="tonal">
                  Permissions actives (site/souscription):
                  <strong>{{ getSelectedUserActivePermissionsCount(selectedUser) }}</strong>
                </v-alert>

                <v-expansion-panels variant="accordion">
                  <v-expansion-panel>
                    <v-expansion-panel-title>
                      Détails techniques (avancé)
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <div class="text-caption text-medium-emphasis mb-2">
                        Permissions directes personnalisées:
                        <strong>{{ selectedUser.permissions?.length ?? 0 }}</strong> ·
                        Permissions effectives:
                        <strong>{{ getSelectedUserEffectivePermissionsCount(selectedUser) }}</strong>
                      </div>
                      <v-chip-group v-if="selectedUser.permissions && selectedUser.permissions.length > 0">
                        <v-chip
                          v-for="(perm, idx) in selectedUser.permissions"
                          :key="idx"
                          color="primary"
                          size="small"
                          variant="outlined"
                        >
                          {{ typeof perm === 'string' ? perm : (perm.name || perm.label || perm.slug || 'Permission') }}
                        </v-chip>
                      </v-chip-group>
                      <div v-else class="text-body-2 text-medium-emphasis">Aucune permission attribuée</div>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="viewDialog = false">Fermer</v-btn>
            <v-btn color="primary" variant="flat" @click="editUser(selectedUser); viewDialog = false">
              <v-icon class="mr-2">mdi-pencil</v-icon>
              Modifier
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { User } from '@/types/api'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useToast } from 'vue-toastification'
  import apiClient from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import FormDialog from '@/modules/clienta/components/FormDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import permissionService, { type Role as AccessRole, type PermissionModule } from '@/services/permissionService'
  import { useAuthStore } from '@/stores/auth'
  import { useSiteContextStore } from '@/stores/siteContext'
  import CollaboratorsStats from './components/CollaboratorsStats.vue'
  import CollaboratorsTable from './components/CollaboratorsTable.vue'

  interface AuthUserExtended extends User {
    site_id?: number | null
    role_names?: string[]
    enterprise_id?: number | null
  }

  type PermissionItem = string | { name?: string, label?: string, slug?: string }

  interface SiteOption {
    id: number
    name: string
    location?: string
    is_headquarter?: boolean
  }

  interface CollaboratorUser {
    id: number
    name: string
    first_name?: string
    last_name?: string
    email: string
    username?: string
    phone?: string
    user_type?: string
    is_active: boolean
    site_id?: number | null
    site?: { name?: string } | null
    role?: string
    access_roles?: string[]
    role_names?: string[]
    permissions?: PermissionItem[]
    effective_permissions_count?: number
    active_scoped_permissions_count?: number
    created_at?: string
  }

  interface CollaboratorForm {
    id: number | null
    first_name: string
    last_name: string
    email: string
    username: string
    phone: string
    role: string
    password: string
    password_confirmation: string
    site_id: number | null
    user_type: 'company'
    access_roles: string[]
    is_active: boolean
    generate_password: boolean
    send_welcome_email: boolean
  }

  const toast = useToast()
  const authStore = useAuthStore()
  const siteContextStore = useSiteContextStore()

  // State
  const loading = ref(false)
  const saving = ref(false)
  const deleting = ref(false)
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const viewDialog = ref(false)
  const expandedPanels = ref([0, 1, 2, 3, 4, 5, 6, 7, 8, 9])
  const editMode = ref(false)

  const users = ref<CollaboratorUser[]>([])
  const sites = ref<SiteOption[]>([])
  const availableRoles = ref<Array<AccessRole & { label?: string }>>([])
  const permissionModules = ref<PermissionModule[]>([])
  const selectedPermissions = ref<string[]>([])
  const directPermissionsFromApi = ref<string[]>([])
  const effectivePermissionsFromApi = ref<string[]>([])
  const activePermissionsCountFromApi = ref<number | null>(null)
  const userToDelete = ref<CollaboratorUser | null>(null)
  const selectedUser = ref<CollaboratorUser | null>(null)

  const form = ref<CollaboratorForm>({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    username: '',
    phone: '',
    role: '', // Poste du collaborateur
    password: '',
    password_confirmation: '',
    site_id: null,
    user_type: 'company',
    access_roles: ['lecteur'],
    is_active: true,
    generate_password: true,
    send_welcome_email: true,
  })

  // Table Headers
  const headers = [
    { title: 'Utilisateur', value: 'name', sortable: true },
    { title: 'Poste', value: 'role', sortable: true },
    { title: 'Site', value: 'site', sortable: false },
    { title: 'Statut', value: 'is_active', sortable: true },
    { title: 'Permissions actives', value: 'permissions', sortable: false },
    { title: 'Actions', value: 'actions', sortable: false, align: 'end' },
  ]

  const moduleLabels: Record<string, string> = {
    sites: 'Sites',
    personnel: 'Personnel',
    subscriptions: 'Abonnements',
    documents: 'Documents QHSE',
    processes: 'Processus',
    risks: 'Risques et opportunités',
    audits: 'Audits',
    nc: 'Non-conformités',
    actions: 'Actions',
    indicators: 'Indicateurs',
    dashboard: 'Tableaux de bord',
    objectives: 'Objectifs',
    plans: 'Plans',
    leadership: 'Leadership',
    organigramme: 'Organigramme',
    fiche_poste: 'Fiche de poste',
    fiche_responsabilite: 'Fiche de responsabilité',
    politique: 'Politique',
    politique_qhse: 'Politique QHSE',
    roles_responsabilites: 'Rôles et responsabilités',
    reclamations: 'Réclamations',
  }

  const moduleIcons: Record<string, string> = {
    sites: 'mdi-map-marker',
    personnel: 'mdi-account-group',
    subscriptions: 'mdi-credit-card',
    documents: 'mdi-file-document',
    processes: 'mdi-sitemap',
    risks: 'mdi-alert',
    audits: 'mdi-clipboard-check',
    nc: 'mdi-alert-circle',
    actions: 'mdi-check-circle',
    indicators: 'mdi-chart-line',
    dashboard: 'mdi-view-dashboard',
    objectives: 'mdi-target',
    plans: 'mdi-clipboard-list',
    leadership: 'mdi-tie',
    organigramme: 'mdi-sitemap-outline',
    fiche_poste: 'mdi-file-account-outline',
    fiche_responsabilite: 'mdi-file-account',
    politique: 'mdi-file-document-edit',
    politique_qhse: 'mdi-file-document-edit',
    roles_responsabilites: 'mdi-account-tie',
    reclamations: 'mdi-message-alert',
  }

  const actionLabels: Record<string, string> = {
    read: 'Lecture',
    create: 'Création',
    update: 'Modification',
    delete: 'Suppression',
    validate: 'Validation',
    approve: 'Approbation',
    assign: 'Assignation',
    verify: 'Vérification',
    analyze: 'Analyse',
    close: 'Clôture',
    track: 'Suivi',
    conduct: 'Conduite',
    export: 'Export',
    publish: 'Publication',
    archive: 'Archivage',
    review: 'Révision',
    assess: 'Évaluation',
    treat: 'Traitement',
    monitor: 'Surveillance',
    respond: 'Réponse',
    plan: 'Planification',
    add_finding: 'Constat',
    manage: 'Gestion',
    configure: 'Configuration',
    download: 'Téléchargement',
  }

  const selectedRoleLabel = computed(() => {
    const selected = form.value.access_roles || []
    if (selected.length === 0) return 'Aucun rôle'
    return availableRoles.value
      .filter(r => selected.includes(r.name))
      .map(r => r.label || r.name)
      .join(', ')
  })

  const suggestedPermissions = computed(() => {
    const selected = form.value.access_roles || []
    if (selected.length === 0) return []
    
    const permissions = new Set<string>()
    for (const roleName of selected) {
      const role = availableRoles.value.find(r => r.name === roleName)
      if (role?.permissions) {
        for (const p of role.permissions) {
          permissions.add(p)
        }
      }
    }
    return Array.from(permissions)
  })

  const inheritedPermissions = computed(() => {
    if (effectivePermissionsFromApi.value.length > 0 && directPermissionsFromApi.value.length > 0 && editMode.value) {
      const direct = new Set(directPermissionsFromApi.value)
      return effectivePermissionsFromApi.value.filter(permission => !direct.has(permission))
    }
    return suggestedPermissions.value
  })

  const effectivePermissions = computed(() => {
    if (effectivePermissionsFromApi.value.length > 0 && editMode.value) {
      return effectivePermissionsFromApi.value
    }
    return Array.from(new Set([
      ...inheritedPermissions.value,
      ...selectedPermissions.value,
    ]))
  })

  const activePermissionsDisplayCount = computed(() => {
    if (editMode.value && activePermissionsCountFromApi.value !== null) {
      return activePermissionsCountFromApi.value
    }
    return effectivePermissions.value.length
  })

  const permissionGroups = computed(() => {
    return permissionModules.value.map(group => {
      const seen = new Set<string>()
      const permissions = group.permissions
        .filter(perm => {
          if (seen.has(perm.name)) return false
          seen.add(perm.name)
          return true
        })
        .map(perm => ({
          label: `${moduleLabels[group.module] || group.module} - ${actionLabels[perm.action] || perm.action}`,
          value: perm.name,
        }))
      return {
        name: group.module,
        label: moduleLabels[group.module] || group.module,
        icon: moduleIcons[group.module] || 'mdi-shield-key',
        permissions,
      }
    })
  })

  // Validation Rules
  const rules = {
    required: (v: any) => !!v || 'Champ requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (min: number) => (v: string) => (v && v.length >= min) || `Min ${min} caractères`,
    match: (target: string) => (v: string) => v === target || 'Les mots de passe ne correspondent pas',
  }

  // Computed
  const stats = computed(() => ({
    total: users.value.length,
    active: users.value.filter(u => u.is_active).length,
    inactive: users.value.filter(u => !u.is_active).length,
  }))

  // Filtrer les sites selon les permissions de l'utilisateur connecté
  const filteredSites = computed(() => {
    const currentUser = authStore.user as AuthUserExtended | null

    // Si admin entreprise → voir tous les sites
    const roleNames = Array.isArray(currentUser?.role_names)
      ? currentUser.role_names
      : []
    if (currentUser?.user_type === 'company' && roleNames.includes('admin_entreprise')) {
      return sites.value
    }

    // Si Responsable de Site → voir SEULEMENT son site
    // Détection : a la permission 'leadership.roles_responsabilites.personnel.create' ET user_type='company'
    const hasPersonnelCreate = (currentUser?.permissions as PermissionItem[] | undefined)?.some(p => {
      if (typeof p === 'string') return p === 'leadership.roles_responsabilites.personnel.create'
      return p?.name === 'leadership.roles_responsabilites.personnel.create'
    })
    if (currentUser?.user_type === 'company' && hasPersonnelCreate && currentUser?.site_id) {
      const currentSiteId = Number(currentUser.site_id)
      return sites.value.filter(s => Number(s.id) === currentSiteId)
    }

    // Par défaut : voir tous les sites (fallback)
    return sites.value
  })

  // Methods
  function extractUserPermissions (attrs: any, rels: any): string[] {
    if (rels.permissions && Array.isArray(rels.permissions)) {
      return rels.permissions.map((perm: any) => {
        if (perm.attributes) {
          return perm.attributes.name || perm.attributes.slug
        }
        return perm.name || perm.slug || perm
      })
    }

    if (attrs.permissions && Array.isArray(attrs.permissions)) {
      return attrs.permissions.map((perm: any) => {
        if (typeof perm === 'object' && perm !== null) {
          return perm.name || perm.slug || perm.label || String(perm)
        }
        return String(perm)
      })
    }

    return []
  }

  function extractRoleNames (attrs: any, rels: any): string[] {
    const roleNamesFromRels = Array.isArray(rels.roles)
      ? rels.roles
        .map((r: any) => r?.attributes?.name || r?.name)
        .filter(Boolean)
      : []

    return (Array.isArray(attrs.role_names) ? attrs.role_names : roleNamesFromRels) as string[]
  }

  function mapApiUserToCollaborator (user: any) {
    const attrs = user.attributes || user
    const rels = user.relationships || {}
    const userPermissions = Array.isArray(attrs.active_scoped_permissions)
      ? attrs.active_scoped_permissions
      : extractUserPermissions(attrs, rels)
    const roleNames = extractRoleNames(attrs, rels)
    const permissionCounts = resolvePermissionCounts(attrs, userPermissions.length)

    return {
      id: user.id,
      name: resolveUserDisplayName(attrs),
      first_name: attrs.first_name,
      last_name: attrs.last_name,
      email: attrs.email,
      username: attrs.username,
      phone: attrs.phone,
      user_type: attrs.user_type,
      is_active: attrs.is_active ?? true,
      site_id: Number(rels.site?.id || attrs.site_id || 0) || null,
      site: rels.site?.attributes || attrs.site,
      role: attrs.job_title || attrs.position || attrs.role || '',
      access_roles: roleNames,
      role_names: roleNames,
      permissions: userPermissions,
      effective_permissions_count: permissionCounts.effective,
      active_scoped_permissions_count: permissionCounts.active,
      created_at: attrs.created_at,
    }
  }

  function resolveUserDisplayName (attrs: any): string {
    return attrs.name || `${attrs.first_name || ''} ${attrs.last_name || ''}`.trim() || 'Sans nom'
  }

  function resolvePrimaryRole (roleNames: string[]): string | null {
    return roleNames[0] || null
  }

  function resolvePermissionCounts (attrs: any, fallbackLength: number): { effective: number, active: number } {
    const effective = Number(attrs.effective_permissions_count ?? fallbackLength ?? 0)
    const active = Number(attrs.active_scoped_permissions_count ?? attrs.effective_permissions_count ?? fallbackLength ?? 0)
    return { effective, active }
  }

  async function loadUsers () {
    const currentUser = authStore.user as AuthUserExtended | null
    loading.value = true
    try {
      const params: Record<string, unknown> = {
        enterprise_id: currentUser?.enterprise_id ?? currentUser?.enterprise?.id,
      }

      if (siteContextStore.activeScope === 'site' && siteContextStore.activeSiteId) {
        params.site_id = Number(siteContextStore.activeSiteId)
      }

      const response = await apiClient.get('/users', {
        params,
      })

      // Transformer le format JSON:API vers format simple
      let rawUsers: any[] = []
      if (Array.isArray(response.data)) {
        rawUsers = response.data
      } else if (response.data.data && Array.isArray(response.data.data)) {
        rawUsers = response.data.data
      }

      users.value = rawUsers.map((user: any) => mapApiUserToCollaborator(user))
    } catch (error: any) {
      console.error('[Collaborators] Error loading users:', error)
      console.error('[Collaborators] Error response:', error.response)
      toast.error(error.response?.data?.message || 'Erreur chargement utilisateurs')
    } finally {
      loading.value = false
    }
  }

  async function loadSites () {
    const currentUser = authStore.user as AuthUserExtended | null
    try {
      const response = await apiClient.get('/sites', {
        params: { enterprise_id: currentUser?.enterprise_id ?? currentUser?.enterprise?.id },
      })

      // Transformer le format JSON:API vers format simple
      let rawSites: any[] = []
      if (Array.isArray(response.data)) {
        rawSites = response.data
      } else if (response.data.data && Array.isArray(response.data.data)) {
        rawSites = response.data.data
      }

      // Extraire id et name depuis attributes
      sites.value = rawSites.map((site: any) => ({
        id: Number(site.id),
        name: site.attributes?.name || site.name || 'Site sans nom',
        location: site.attributes?.location || site.location,
        is_headquarter: site.attributes?.is_headquarter || site.is_headquarter,
      })).filter((site: SiteOption) => Number.isFinite(site.id))
    } catch (error: any) {
      console.error('[Collaborators] Error loading sites:', error)
      toast.error('Erreur chargement sites')
    }
  }

  function openCreateDialog () {
    editMode.value = false
    form.value = {
      id: null,
      first_name: '',
      last_name: '',
      email: '',
      username: '',
      phone: '',
      role: '',
      password: '',
      password_confirmation: '',
      access_roles: ['lecteur'],
      site_id: null,
      user_type: 'company',
      is_active: true,
      generate_password: true,
      send_welcome_email: true,
    }
    selectedPermissions.value = []
    directPermissionsFromApi.value = []
    effectivePermissionsFromApi.value = []
    activePermissionsCountFromApi.value = null
    dialog.value = true
  }

  function viewUser (user: CollaboratorUser) {
    selectedUser.value = user
    viewDialog.value = true
  }

  function applyPermissionDetailsState (permissionDetails: any) {
    directPermissionsFromApi.value = Array.isArray(permissionDetails?.direct_permissions)
      ? permissionDetails.direct_permissions
      : []

    const activeCount = Number(permissionDetails?.active_scoped_permissions_count)
    if (Number.isFinite(activeCount) && activeCount >= 0) {
      activePermissionsCountFromApi.value = activeCount
    }

    if (Array.isArray(permissionDetails?.active_scoped_permissions)) {
      effectivePermissionsFromApi.value = permissionDetails.active_scoped_permissions
      if (activePermissionsCountFromApi.value === null) {
        activePermissionsCountFromApi.value = permissionDetails.active_scoped_permissions.length
      }
    } else if (Array.isArray(permissionDetails?.effective_permissions)) {
      effectivePermissionsFromApi.value = permissionDetails.effective_permissions
      if (activePermissionsCountFromApi.value === null) {
        activePermissionsCountFromApi.value = permissionDetails.effective_permissions.length
      }
    } else {
      effectivePermissionsFromApi.value = []
    }

    selectedPermissions.value = [...directPermissionsFromApi.value]
  }

  async function editUser (user: CollaboratorUser) {
    editMode.value = true

    form.value = {
      id: user.id,
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      email: user.email,
      username: user.username || '',
      phone: user.phone || '',
      role: user.role || '',
      password: '',
      password_confirmation: '',
      access_roles: user.access_roles?.length ? user.access_roles : ['lecteur'],
      site_id: user.site_id ?? null,
      user_type: 'company',
      is_active: user.is_active,
      generate_password: false,
      send_welcome_email: false,
    }
    selectedPermissions.value = []
    directPermissionsFromApi.value = []
    effectivePermissionsFromApi.value = []
    activePermissionsCountFromApi.value = null

    try {
      const permissionDetails = await permissionService.getUserPermissions(user.id)
      applyPermissionDetailsState(permissionDetails)
    } catch (error) {
      console.error('[Collaborators] Error loading user permission details:', error)
      selectedPermissions.value = Array.isArray(user.permissions)
        ? user.permissions
          .map(permission => {
            if (typeof permission === 'string') return permission
            return permission.name || permission.slug || ''
          })
          .filter(Boolean)
        : []
    }
    dialog.value = true
  }

  function closeDialog () {
    dialog.value = false
    directPermissionsFromApi.value = []
    effectivePermissionsFromApi.value = []
    activePermissionsCountFromApi.value = null
  }

  async function saveUser () {
    const currentUser = authStore.user as AuthUserExtended | null
    const emailValue = String(form.value.email || '').trim()
    const strictEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!strictEmailPattern.test(emailValue)) {
      toast.error('Adresse email invalide. Vérifiez le format (ex: nom@domaine.com).')
      return
    }

    saving.value = true
    try {
      // Générer le username automatiquement : prenom.nom
      const username = `${form.value.first_name.toLowerCase()}.${form.value.last_name.toLowerCase()}`.replace(/\s+/g, '')

      if (selectedPermissions.value.length > 0) {
        toast.info('Les permissions directes ne sont plus persistées (mode roles-only).')
      }

      const payload: Record<string, unknown> = {
        ...form.value,
        email: emailValue,
        username,
        enterprise_id: currentUser?.enterprise_id ?? currentUser?.enterprise?.id,
        role: form.value.role,
        job_title: form.value.role,
        access_roles: form.value.access_roles,
        user_type: 'company',
      }

      // Tous les collaborateurs sont user_type='company'

      if (editMode.value) {
        const { password, password_confirmation, ...updatePayload } = payload
        void password
        void password_confirmation
        await apiClient.put(`/users/${form.value.id}`, updatePayload)
        toast.success('Collaborateur mis à jour avec succès')
      } else {
        await apiClient.post('/users', payload)
        toast.success('Collaborateur créé avec succès')
      }

      await loadUsers()
      closeDialog()
    } catch (error: any) {
      console.error('[Collaborators] Error saving user:', error)
      console.error('[Collaborators] Error response:', error.response)
      toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  function confirmDelete (user: CollaboratorUser) {
    userToDelete.value = user
    deleteDialog.value = true
  }

  async function deleteUser () {
    if (!userToDelete.value) return

    deleting.value = true
    try {
      await apiClient.delete(`/users/${userToDelete.value.id}`)
      toast.success('Collaborateur supprimé')
      deleteDialog.value = false
      userToDelete.value = null
      await loadUsers()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur suppression')
    } finally {
      deleting.value = false
    }
  }

  function getInitials (name: string | undefined | null): string {
    if (!name) return '??'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
  }

  function getSiteName (siteId: number | null | undefined): string {
    const normalizedSiteId = Number(siteId)
    const site = sites.value.find(s => Number(s.id) === normalizedSiteId)
    return site?.name || 'Non assigné'
  }

  function getUserSiteLabel (user: CollaboratorUser): string {
    if (user.site_id) {
      const fromCatalog = getSiteName(user.site_id)
      if (fromCatalog && fromCatalog !== 'Non assigné') {
        return fromCatalog
      }
    }

    const relationName = user.site?.name
    if (typeof relationName === 'string' && relationName.trim()) {
      return relationName.trim()
    }

    return ''
  }

  function getSelectedUserActivePermissionsCount (user: CollaboratorUser): number {
    const activeCount = Number(user.active_scoped_permissions_count)
    if (Number.isFinite(activeCount) && activeCount >= 0) {
      return activeCount
    }

    const effectiveCount = Number(user.effective_permissions_count)
    if (Number.isFinite(effectiveCount) && effectiveCount >= 0) {
      return effectiveCount
    }

    return Array.isArray(user.permissions) ? user.permissions.length : 0
  }

  function getSelectedUserEffectivePermissionsCount (user: CollaboratorUser): number {
    const effectiveCount = Number(user.effective_permissions_count)
    if (Number.isFinite(effectiveCount) && effectiveCount >= 0) {
      return effectiveCount
    }

    return Array.isArray(user.permissions) ? user.permissions.length : 0
  }

  function onSiteChange () {
    // Reset permissions when site changes
    selectedPermissions.value = []
    void loadAccessCatalog()
  }

  function getGroupColor (groupName: string): string {
    const colors: Record<string, string> = {
      sites: 'purple',
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

  function getGroupSelectedCount (groupName: string): number {
    const group = permissionGroups.value.find(g => g.name === groupName)
    if (!group) return 0
    return group.permissions.filter(p => selectedPermissions.value.includes(p.value)).length
  }

  // Appliquer les permissions suggérées selon le profil
  function applySuggestedPermissions () {
    selectedPermissions.value = [...suggestedPermissions.value]
    toast.success(`${suggestedPermissions.value.length} permissions pré-remplies (aperçu local)`)

    // Ouvrir tous les panels pour voir les permissions sélectionnées
    expandedPanels.value = Array.from({ length: permissionGroups.value.length }, (_, i) => i)
  }

  function selectAllGroup (groupName: string) {
    const group = permissionGroups.value.find(g => g.name === groupName)
    if (!group) return

    for (const p of group.permissions) {
      if (!selectedPermissions.value.includes(p.value)) {
        selectedPermissions.value.push(p.value)
      }
    }
  }

  function deselectAllGroup (groupName: string) {
    const group = permissionGroups.value.find(g => g.name === groupName)
    if (!group) return

    selectedPermissions.value = selectedPermissions.value.filter(
      p => !group.permissions.some(gp => gp.value === p),
    )
  }

  function selectAllPermissions () {
    const all = permissionGroups.value.flatMap(group => group.permissions.map(permission => permission.value))
    selectedPermissions.value = Array.from(new Set(all))
  }

  function deselectAllPermissions () {
    selectedPermissions.value = []
  }

  async function loadAccessCatalog () {
    try {
      const [roles, permissions] = await Promise.all([
        permissionService.getAvailableRoles(form.value.site_id || undefined),
        permissionService.getAvailablePermissions(),
      ])

      availableRoles.value = roles
      permissionModules.value = permissions

      if (!form.value.access_roles || form.value.access_roles.length === 0) {
        form.value.access_roles = [availableRoles.value[0]?.name || 'lecteur']
      }
    } catch (error) {
      console.error('[Collaborators] Error loading access catalog:', error)
      toast.error('Impossible de charger les rôles et permissions')
    }
  }

  watch(() => form.value.access_roles, (newRoles, oldRoles) => {
    if (!newRoles || newRoles === oldRoles) return
    if (editMode.value) {
      effectivePermissionsFromApi.value = []
      directPermissionsFromApi.value = []
      activePermissionsCountFromApi.value = null
    }
    if (!editMode.value) {
      toast.info(`Le rôle "${selectedRoleLabel.value}" met à disposition ${inheritedPermissions.value.length} permissions de rôle`)
    }
  })

  watch(
    () => [siteContextStore.activeScope, siteContextStore.activeSiteId],
    () => {
      void Promise.all([loadUsers(), loadAccessCatalog()])
    },
  )

  onMounted(async () => {
    await loadAccessCatalog()
    await Promise.all([
      loadUsers(),
      loadSites(),
    ])
  })
</script>

<style scoped>
.form-loader-overlay {
  position: sticky;
  top: 0;
  z-index: 8;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0 14px;
  margin-bottom: 8px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(2px);
}
</style>
