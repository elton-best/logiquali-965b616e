<template>
  <SuperAdminLayout current-page="enterprises">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold text-primary mb-2">Gestion des Entreprises</h1>
          <p class="text-subtitle-1 text-grey-darken-1">
            Gérez toutes les entreprises inscrites sur la plateforme
          </p>
        </div>
        <div class="d-flex align-center ga-3">
          <v-btn-toggle v-model="viewMode" density="compact" mandatory>
            <v-btn value="table" variant="outlined">
              <v-icon start>mdi-table</v-icon>
              Table
            </v-btn>
            <v-btn value="cards" variant="outlined">
              <v-icon start>mdi-view-grid</v-icon>
              Cartes
            </v-btn>
          </v-btn-toggle>
        </div>
      </div>

      <!-- Stats -->
      <v-row class="mb-6">
        <v-col
          v-for="stat in stats"
          :key="stat.label"
          cols="12"
          lg="3"
          sm="6"
        >
          <StatCard
            :color="stat.color"
            :icon="stat.icon"
            :label="stat.label"
            :value="stat.value"
          />
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6 sa-card sa-filters" elevation="2">
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                clearable
                hide-details
                label="Rechercher"
                placeholder="Nom, email, secteur..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                hide-details
                :items="statusOptions"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.field"
                clearable
                hide-details
                :items="fieldOptions"
                label="Secteur d'activité"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-btn
                block
                color="primary"
                height="56"
                style="border-radius: 12px"
                variant="flat"
                @click="loadEnterprises"
              >
                Filtrer
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Table -->
      <v-card v-if="viewMode === 'table'" class="sa-card sa-table-card" elevation="2">
        <v-data-table
          class="elevation-0 sa-table"
          density="compact"
          :headers="headers"
          :items="enterprises"
          :items-per-page="15"
          :loading="loading"
        >
          <template #no-data>
            <EmptyState
              description="Aucun resultat ne correspond a vos filtres."
              icon="mdi-office-building-outline"
              title="Aucune entreprise trouvee"
            />
          </template>
          <template #item.name="{ item }">
            <div class="d-flex align-center py-2">
              <v-avatar class="mr-3" color="primary" size="40">
                <span class="text-body-2 font-weight-bold">
                  {{ getInitials(item.name) }}
                </span>
              </v-avatar>
              <div>
                <div class="font-weight-medium">{{ item.name }}</div>
                <div class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">
                  {{ item.email }}
                </div>
              </div>
            </div>
          </template>

          <template #item.field="{ item }">
            <span class="text-body-2">{{ item.field || 'N/A' }}</span>
          </template>

          <template #item.status="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              size="x-small"
              variant="tonal"
            >
              {{ getStatusLabel(item.status) }}
            </v-chip>
          </template>

          <template #item.created_at="{ item }">
            <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
          </template>

          <template #item.actions="{ item }">
            <v-menu>
              <template #activator="{ props }">
                <v-tooltip location="top" text="Actions">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      size="small"
                      variant="text"
                      v-bind="{ ...props, ...tooltipProps }"
                    />
                  </template>
                </v-tooltip>
              </template>
              <v-list density="compact">
                <v-list-item @click="$router.push(`/superadmin/kyc/${item.id}`)">
                  <template #prepend>
                    <v-icon size="20">mdi-eye-outline</v-icon>
                  </template>
                  <v-list-item-title>Voir détails</v-list-item-title>
                </v-list-item>

                <v-divider v-if="item.status !== 'suspended'" />

                <v-list-item v-if="item.status === 'active'" @click="openSuspendDialog(item)">
                  <template #prepend>
                    <v-icon color="warning" size="20">mdi-pause-circle-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-warning">Suspendre</v-list-item-title>
                </v-list-item>

                <v-list-item v-if="item.status === 'suspended'" @click="openReactivateDialog(item)">
                  <template #prepend>
                    <v-icon color="success" size="20">mdi-play-circle-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-success">Réactiver</v-list-item-title>
                </v-list-item>

                <v-divider />

                <v-list-item @click="openDeleteDialog(item)">
                  <template #prepend>
                    <v-icon color="error" size="20">mdi-delete-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>

          <template #loading>
            <v-skeleton-loader type="table-row@5" />
          </template>

        </v-data-table>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="enterprises.length === 0" cols="12">
          <EmptyState
            description="Aucun resultat ne correspond a vos filtres."
            icon="mdi-office-building-outline"
            title="Aucune entreprise trouvee"
          />
        </v-col>
        <v-col
          v-for="enterprise in enterprises"
          :key="enterprise.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div class="font-weight-bold">{{ enterprise.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ enterprise.email }}</div>
              </div>
              <v-chip :color="getStatusColor(enterprise.status)" size="x-small" variant="tonal">
                {{ getStatusLabel(enterprise.status) }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-1">Secteur: {{ enterprise.field || 'N/A' }}</div>
              <div class="text-caption text-medium-emphasis">Inscrite le {{ formatDate(enterprise.created_at) }}</div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Voir détails">
                <template #activator="{ props: tooltipProps }">
                  <v-btn v-bind="tooltipProps" icon="mdi-eye-outline" variant="text" @click="$router.push(`/superadmin/kyc/${enterprise.id}`)" />
                </template>
              </v-tooltip>
              <v-menu>
                <template #activator="{ props }">
                  <v-tooltip location="top" text="Actions">
                    <template #activator="{ props: tooltipProps }">
                      <v-btn v-bind="{ ...props, ...tooltipProps }" icon="mdi-dots-vertical" variant="text" />
                    </template>
                  </v-tooltip>
                </template>
                <v-list density="compact">
                  <v-list-item @click="$router.push(`/superadmin/kyc/${enterprise.id}`)">
                    <template #prepend>
                      <v-icon size="20">mdi-eye-outline</v-icon>
                    </template>
                    <v-list-item-title>Voir détails</v-list-item-title>
                  </v-list-item>
                  <v-divider v-if="enterprise.status !== 'suspended'" />
                  <v-list-item v-if="enterprise.status === 'active'" @click="openSuspendDialog(enterprise)">
                    <template #prepend>
                      <v-icon color="warning" size="20">mdi-pause-circle-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-warning">Suspendre</v-list-item-title>
                  </v-list-item>
                  <v-list-item v-if="enterprise.status === 'suspended'" @click="openReactivateDialog(enterprise)">
                    <template #prepend>
                      <v-icon color="success" size="20">mdi-play-circle-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-success">Réactiver</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item @click="openDeleteDialog(enterprise)">
                    <template #prepend>
                      <v-icon color="error" size="20">mdi-delete-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <StatusDialog
      v-model="suspendDialog"
      v-model:reason="suspendReason"
      confirm-color="warning"
      confirm-label="Suspendre"
      icon="mdi-pause-circle-outline"
      :loading="suspending"
      :message="`Merci d’indiquer la raison de suspension de <strong>${selectedEnterprise?.name ?? ''}</strong>.`"
      :reason-error="suspendReasonError"
      reason-label="Raison de la suspension *"
      reason-placeholder="Expliquez pourquoi cette entreprise est suspendue..."
      show-reason
      title="Suspendre l'entreprise"
      @cancel="suspendDialog = false"
      @confirm="handleSuspend"
    />

    <StatusDialog
      v-model="reactivateDialog"
      confirm-color="success"
      confirm-label="Réactiver"
      icon="mdi-check-circle-outline"
      :loading="reactivating"
      :message="`Confirmez la réactivation de <strong>${selectedEnterprise?.name ?? ''}</strong>.`"
      title="Réactiver l'entreprise"
      @cancel="reactivateDialog = false"
      @confirm="handleReactivate"
    />

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: suppression definitive de l'entreprise, de ses sites et de ses documents dans l'espace super admin."
      :loading="deleting"
      :message="`Etes-vous sur de vouloir supprimer ${selectedEnterprise?.name} ?`"
      title="Supprimer l'entreprise"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise } from '@/types/api'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import StatusDialog from '@/modules/superadmin/components/StatusDialog.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const toast = useToast()

  const loading = ref(true)
  const enterprises = ref<Enterprise[]>([])
  const viewMode = ref<'table' | 'cards'>(localStorage.getItem('sa_view_enterprises') === 'cards' ? 'cards' : 'table')
  const suspendDialog = ref(false)
  const reactivateDialog = ref(false)
  const deleteDialog = ref(false)
  const selectedEnterprise = ref<Enterprise | null>(null)
  const suspendReason = ref('')
  const suspendReasonError = ref('')
  const suspending = ref(false)
  const reactivating = ref(false)
  const deleting = ref(false)
  const { run: runLocked } = useActionLock()

  const filters = ref({
    search: '',
    status: 'all' as 'all' | 'pending' | 'active' | 'rejected' | 'suspended',
    field: null as string | null,
  })

  const statusOptions = [
    { title: 'Tous', value: 'all' },
    { title: 'En attente', value: 'pending' },
    { title: 'Approuvée', value: 'active' },
    { title: 'Rejetée', value: 'rejected' },
    { title: 'Suspendue', value: 'suspended' },
  ]

  const fieldOptions = [
    'Industrie',
    'Services',
    'Commerce',
    'Agriculture',
    'Technologie',
    'Santé',
    'Éducation',
    'Autre',
  ]

  const headers = [
    { title: 'Entreprise', key: 'name', sortable: true },
    { title: 'Secteur', key: 'field', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Date d\'inscription', key: 'created_at', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const stats = computed(() => {
    const all = enterprises.value
    return [
      {
        label: 'Total',
        value: all.length,
        icon: 'mdi-office-building',
        color: 'primary',
      },
      {
        label: 'Actives',
        value: all.filter(e => e.status === 'active').length,
        icon: 'mdi-check-circle',
        color: 'success',
      },
      {
        label: 'Suspendues',
        value: all.filter(e => e.status === 'suspended').length,
        icon: 'mdi-pause-circle',
        color: 'warning',
      },
      {
        label: 'En attente',
        value: all.filter(e => e.status === 'pending').length,
        icon: 'mdi-clock',
        color: 'info',
      },
    ]
  })

  onMounted(async () => {
    await loadEnterprises()
    if (typeof window !== 'undefined') {
      window.addEventListener('superadmin-kyc-updated', loadEnterprises)
    }
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('superadmin-kyc-updated', loadEnterprises)
    }
  })

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_enterprises', mode)
  })

  async function loadEnterprises () {
    loading.value = true
    try {
      const response = await superAdminService.getEnterprises({
        status: filters.value.status,
        search: filters.value.search,
      })
      enterprises.value = response.data || []
    } catch {} finally {
      loading.value = false
    }
  }

  function openSuspendDialog (enterprise: Enterprise) {
    selectedEnterprise.value = enterprise
    suspendReason.value = ''
    suspendReasonError.value = ''
    suspendDialog.value = true
  }

  function openReactivateDialog (enterprise: Enterprise) {
    selectedEnterprise.value = enterprise
    reactivateDialog.value = true
  }

  async function handleSuspend () {
    if (!selectedEnterprise.value) return

    if (!suspendReason.value || suspendReason.value.length < 10) {
      suspendReasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    await runLocked('enterprise-suspend', async () => {
      suspending.value = true
      try {
        await superAdminService.suspendEnterprise(selectedEnterprise.value!.id, suspendReason.value)
        toast.success('Entreprise suspendue')
        suspendDialog.value = false
        await loadEnterprises()
      } finally {
        suspending.value = false
      }
    })
  }

  async function handleReactivate () {
    if (!selectedEnterprise.value) return
    await runLocked('enterprise-reactivate', async () => {
      reactivating.value = true
      try {
        await superAdminService.reactivateEnterprise(selectedEnterprise.value!.id)
        toast.success('Entreprise réactivée')
        reactivateDialog.value = false
        await loadEnterprises()
      } finally {
        reactivating.value = false
      }
    })
  }

  function openDeleteDialog (enterprise: Enterprise) {
    selectedEnterprise.value = enterprise
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!selectedEnterprise.value) return

    await runLocked('enterprise-delete', async () => {
      deleting.value = true
      try {
        await superAdminService.deleteEnterprise(selectedEnterprise.value!.id)
        toast.success('Entreprise supprimée')
        deleteDialog.value = false
        await loadEnterprises()
      } finally {
        deleting.value = false
      }
    })
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'warning',
      approved: 'success',
      rejected: 'error',
      suspended: 'error',
    }
    return colors[status] || 'default'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      approved: 'Approuvée',
      rejected: 'Rejetée',
      suspended: 'Suspendue',
    }
    return labels[status] || status
  }

  function getInitials (name: string): string {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>
