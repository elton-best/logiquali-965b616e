<template>
  <SuperAdminLayout current-page="norms">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center flex-wrap ga-4">
            <div>
              <div class="d-flex align-center mb-2">
                <v-icon class="mr-3" color="primary" size="32">mdi-certificate-outline</v-icon>
                <h1 class="text-h4 font-weight-bold text-primary">Gestion des Normes ISO</h1>
              </div>
              <p class="text-subtitle-1 text-grey-darken-1">
                Gérez toutes les normes ISO disponibles sur la plateforme
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
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                variant="flat"
                @click="openDialog(null)"
              >
                Ajouter une Norme
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col
          v-for="stat in stats"
          :key="stat.id"
          cols="12"
          lg="4"
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

      <!-- Norms Table -->
      <v-card v-if="viewMode === 'table'" class="sa-card sa-table-card" elevation="2">
        <v-card-text class="pa-0">
          <!-- Loading State -->
          <v-skeleton-loader v-if="loading" type="table" />

          <!-- Data Table -->
          <v-data-table
            v-else
            class="norms-table sa-table"
            density="compact"
            :headers="headers"
            hover
            :items="norms"
            :items-per-page="10"
            :items-per-page-options="[5, 10, 25, 50]"
          >
            <template #no-data>
              <EmptyState
                description="Aucune norme ne correspond a vos filtres."
                icon="mdi-certificate-outline"
                title="Aucune norme trouvee"
              />
            </template>
            <!-- Code ISO Column -->
            <template #item.code="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar
                  class="mr-3"
                  :color="getNormColor(item.code)"
                  size="40"
                  variant="tonal"
                >
                  <v-icon :color="getNormColor(item.code)" size="20">mdi-certificate</v-icon>
                </v-avatar>
                <div>
                  <div class="font-weight-bold text-body-1">{{ item.code }}</div>
                  <div class="text-caption text-medium-emphasis">Version {{ getNormVersionLabel(item) }}</div>
                </div>
              </div>
            </template>

            <!-- Nom Column -->
            <template #item.name="{ item }">
              <div class="text-body-1 font-weight-medium">{{ item.name }}</div>
            </template>

            <!-- Description Column -->
            <template #item.description="{ item }">
              <div class="text-body-2 text-medium-emphasis" style="max-width: 400px">
                {{ item.description }}
              </div>
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

            <!-- Entreprises Column -->
            <template #item.enterprises_count="{ item }">
              <v-chip
                color="primary"
                size="x-small"
                variant="tonal"
              >
                <v-icon size="14" start>mdi-office-building</v-icon>
                {{ getEnterpriseCount(item) }} {{ getEnterpriseCount(item) > 1 ? 'entreprises' : 'entreprise' }}
              </v-chip>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
              <div class="d-flex ga-2">
                <v-tooltip location="top" text="Voir">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="primary"
                      icon="mdi-eye-outline"
                      size="small"
                      variant="tonal"
                      @click="viewNorm(item)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Modifier">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="info"
                      icon="mdi-pencil-outline"
                      :loading="loadingNormDetails"
                      size="small"
                      variant="tonal"
                      @click="openDialog(item)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Supprimer">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="error"
                      icon="mdi-delete-outline"
                      size="small"
                      variant="tonal"
                      @click="confirmDelete(item)"
                    />
                  </template>
                </v-tooltip>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="norms.length === 0" cols="12">
          <EmptyState
            description="Aucune norme ne correspond a vos filtres."
            icon="mdi-certificate-outline"
            title="Aucune norme trouvee"
          />
        </v-col>
        <v-col
          v-for="norm in norms"
          :key="norm.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div class="font-weight-bold">{{ norm.code }}</div>
                <div class="text-caption text-medium-emphasis">Version {{ getNormVersionLabel(norm) }}</div>
              </div>
              <v-chip :color="getStatusColor(norm.status)" size="x-small" variant="tonal">
                {{ getStatusLabel(norm.status) }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-2">{{ norm.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ norm.description }}</div>
              <div class="mt-3">
                <v-chip color="primary" size="x-small" variant="tonal">
                  <v-icon size="14" start>mdi-office-building</v-icon>
                  {{ getEnterpriseCount(norm) }} {{ getEnterpriseCount(norm) > 1 ? 'entreprises' : 'entreprise' }}
                </v-chip>
              </div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Voir">
                <template #activator="{ props: tooltipProps }">
                  <v-btn v-bind="tooltipProps" icon="mdi-eye-outline" variant="text" @click="viewNorm(norm)" />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Modifier">
                <template #activator="{ props: tooltipProps }">
                  <v-btn v-bind="tooltipProps" icon="mdi-pencil-outline" variant="text" @click="openDialog(norm)" />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Supprimer">
                <template #activator="{ props: tooltipProps }">
                  <v-btn v-bind="tooltipProps" icon="mdi-delete-outline" variant="text" @click="confirmDelete(norm)" />
                </template>
              </v-tooltip>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Add/Edit Dialog - NEW NormEditor -->
    <NormEditor
      v-model="dialog"
      :norm-data="editedNorm.id ? editedNorm : null"
      @save="handleSaveNorm"
    />

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: toutes les versions et sections associees seront definitivement supprimees."
      :loading="deleting"
      :message="`Etes-vous sur de vouloir supprimer ${normToDelete?.code} - ${normToDelete?.name} ?`"
      title="Supprimer la norme"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Norm, NormSection, NormVersion } from '@/types/api'
  import type { NormStructure } from '@/types/norms'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import superAdminService from '@/services/superAdminService'
  import NormEditor from '../../components/NormEditor.vue'
  import SuperAdminLayout from '../../components/SuperAdminLayout.vue'

  interface NormWithCompat extends Norm {
    enterprises_count?: number
    current_version?: NormVersion
  }

  const router = useRouter()
  const toast = useToast()

  // Loading states
  const loading = ref(true)
  const submitting = ref(false)
  const deleting = ref(false)
  const loadingNormDetails = ref(false)

  // Data
  const norms = ref<NormWithCompat[]>([])
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const normToDelete = ref<NormWithCompat | null>(null)
  const viewMode = ref<'table' | 'cards'>(localStorage.getItem('sa_view_norms') === 'cards' ? 'cards' : 'table')
  const { run: runLocked } = useActionLock()

  const _formRef = ref()
  const editedNorm = ref<NormStructure & { id?: number }>({
    code: '',
    version: '',
    name: '',
    description: '',
    domain: 'quality',
    nodes: [],
  })

  // Stats
  const stats = computed(() => [
    {
      id: 1,
      label: 'Total Normes',
      value: norms.value.length,
      icon: 'mdi-certificate-outline',
      color: 'primary',
    },
    {
      id: 2,
      label: 'Normes Actives',
      value: norms.value.length,
      icon: 'mdi-check-circle-outline',
      color: 'success',
    },
    {
      id: 3,
      label: 'Entreprises Certifiées',
      value: norms.value.reduce((acc, norm) => acc + getEnterpriseCount(norm), 0),
      icon: 'mdi-office-building-outline',
      color: 'info',
    },
  ])

  // Table headers
  const headers = [
    { title: 'Code ISO', key: 'code', sortable: true },
    { title: 'Nom', key: 'name', sortable: true },
    { title: 'Description', key: 'description', sortable: false },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Entreprises', key: 'enterprises_count', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' as const },
  ] as const

  // Validation rules
  const _rules = {
    required: (v: any) => !!v || 'Ce champ est obligatoire',
  }

  // Load data on mount
  onMounted(async () => {
    await loadNorms()
  })

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_norms', mode)
  })

  // API calls
  async function loadNorms () {
    loading.value = true
    try {
      norms.value = await superAdminService.getNorms()
    } catch {
      toast.error('Erreur lors du chargement des normes')
    } finally {
      loading.value = false
    }
  }

  // Helper functions
  function getNormColor (code: string) {
    const colors: Record<string, string> = {
      'ISO 9001': 'blue',
      'ISO 14001': 'green',
      'ISO 45001': 'orange',
      'ISO 27001': 'purple',
      'ISO 22000': 'teal',
      'ISO 50001': 'cyan',
      'ISO 13485': 'pink',
      'ISO 20000': 'indigo',
      'ISO 26000': 'lime',
      'ISO 31000': 'amber',
      'ISO 37001': 'deep-orange',
      'ISO 55001': 'brown',
    }
    return colors[code] || 'grey'
  }

  function getEnterpriseCount (norm: NormWithCompat): number {
    return norm.enterprises_count ?? 0
  }

  function getNormVersionLabel (norm: NormWithCompat): string {
    return norm.currentVersion?.version_code
      || norm.current_version?.version_code
      || norm.versions?.[0]?.version_code
      || 'N/A'
  }

  function getStatusColor (status?: string): string {
    const colors: Record<string, string> = {
      draft: 'warning',
      published: 'success',
      archived: 'grey',
    }
    return status ? (colors[status] || 'grey') : 'grey'
  }

  function getStatusLabel (status?: string): string {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      published: 'Publiée',
      archived: 'Archivée',
    }
    return status ? (labels[status] || status) : 'N/A'
  }

  // Dialog actions
  async function openDialog (norm?: NormWithCompat | null) {
    if (norm) {
      // Charger la norme complète avec sa structure
      loadingNormDetails.value = true
      try {
        const fullNorm = await superAdminService.getNorm(norm.id) as NormWithCompat

        // Extraire les sections de la version actuelle
        const currentVersion = fullNorm.currentVersion || fullNorm.current_version || fullNorm.versions?.[0]
        const sections = currentVersion?.sections || []

        // Convertir les sections en nodes pour le NormEditor
        const nodes = convertSectionsToNodes(sections)

        editedNorm.value = {
          id: fullNorm.id,
          code: fullNorm.code,
          version: currentVersion?.version_code || '',
          name: fullNorm.name,
          description: fullNorm.description,
          domain: fullNorm.domain,
          nodes: nodes,
        }

        dialog.value = true
      } catch {
        toast.error('Erreur lors du chargement de la norme')
      } finally {
        loadingNormDetails.value = false
      }
    } else {
      // Nouvelle norme
      editedNorm.value = {
        code: '',
        version: '',
        name: '',
        description: '',
        domain: 'quality',
        nodes: [],
      }
      dialog.value = true
    }
  }

  // Convertir les NormSection en NormNode pour l'éditeur
  function convertSectionsToNodes (sections: NormSection[]): NormStructure['nodes'] {
    return sections.map(section => ({
      id: `section-${section.id}`,
      type: section.type,
      code: section.number,
      title: section.title || '',
      content: section.content || '',
      parentId: section.parent_id ? `section-${section.parent_id}` : undefined,
      order: section.order_index || 0,
      isObligatory: false,
      children: section.children ? convertSectionsToNodes(section.children) : [],
    }))
  }

  // Handle save from NormEditor
  async function handleSaveNorm (normData: NormStructure) {
    await runLocked('norm-save', async () => {
      submitting.value = true
      try {
        // Validation des champs obligatoires
        if (!normData.code || !normData.name || !normData.version) {
          toast.error('Veuillez remplir tous les champs obligatoires')
          return
        }

        const payload = {
          name: normData.name,
          version_code: normData.version, // Backend expects version_code
          domain: normData.domain || 'quality', // Requis par le backend
          description: normData.description || '',
          structure: normData.nodes || [], // Send the hierarchical structure
          publish: normData.publish ?? true,
        }

        if (editedNorm.value?.id) {
          await superAdminService.updateNorm(editedNorm.value.id, payload)
          toast.success('Norme modifiée')
        } else {
          await superAdminService.createNorm({
            ...payload,
            code: normData.code,
          })
          toast.success('Norme créée')
        }

        dialog.value = false
        await loadNorms()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Impossible d’enregistrer la norme.')
      } finally {
        submitting.value = false
      }
    })
  }

  // Other actions
  function viewNorm (norm: NormWithCompat) {
    // Naviguer vers la page de détail
    router.push(`/superadmin/norms/${norm.id}`)
  }

  function confirmDelete (norm: NormWithCompat) {
    normToDelete.value = norm
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!normToDelete.value) return

    await runLocked('norm-delete', async () => {
      deleting.value = true
      try {
        await superAdminService.deleteNorm(normToDelete.value!.id)
        toast.success('Norme supprimée')
        deleteDialog.value = false
        normToDelete.value = null
        await loadNorms()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suppression impossible pour cette norme.')
      } finally {
        deleting.value = false
      }
    })
  }
</script>

<style scoped>
.stat-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.norms-table :deep(tbody tr) {
  transition: background-color 0.2s ease;
}

.norms-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}

:deep(.v-card) {
  background-color: rgb(var(--v-theme-surface));
}

:deep(.v-data-table) {
  background-color: transparent;
}

* {
  transition: background-color 0.2s ease, color 0.2s ease;
}
</style>
