<template>
  <ClientALayout current-page="processes">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold mb-2">Processus</h1>
          <p class="text-body-1 text-medium-emphasis">
            Gérez vos processus métier et leur documentation
          </p>
        </div>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          size="large"
          @click="router.push({ path: '/company/context/management-system', query: { openCreate: '1' } })"
        >
          Créer un processus
        </v-btn>
      </div>

      <!-- Stats Cards -->
      <ProcessStats
        class="mb-6"
        :stats="stats"
        @filter="handleStatsFilter"
      />

      <!-- Filters -->
      <v-card class="mb-6" variant="outlined">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="3" sm="6">
              <v-autocomplete
                v-model="filters.site_id"
                clearable
                density="compact"
                hide-details
                item-title="name"
                item-value="id"
                :items="sites"
                label="Site"
                placeholder="Tous les sites"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="3" sm="6">
              <v-select
                v-model="filters.category"
                clearable
                density="compact"
                hide-details
                :items="typeOptions"
                label="Type"
                placeholder="Tous les types"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="3" sm="6">
              <v-select
                v-model="filters.status"
                clearable
                density="compact"
                hide-details
                :items="statusOptions"
                label="Statut"
                placeholder="Tous les statuts"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="2" sm="6">
              <v-select
                v-model="filters.level"
                clearable
                density="compact"
                hide-details
                :items="levelOptions"
                label="Niveau"
                placeholder="Tous"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-text-field
                v-model="filters.search"
                clearable
                density="compact"
                hide-details
                label="Rechercher"
                placeholder="Code, titre..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
          </v-row>

          <div class="d-flex justify-end mt-3 gap-2">
            <v-btn
              prepend-icon="mdi-filter-off"
              variant="outlined"
              @click="resetFilters"
            >
              Réinitialiser
            </v-btn>
            <v-btn
              color="primary"
              prepend-icon="mdi-filter"
              @click="applyFilters"
            >
              Appliquer
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <!-- Actions Bar -->
      <div class="d-flex justify-space-between align-center mb-4">
        <div class="text-body-2 text-medium-emphasis">
          {{ pagination.total }} processus trouvés
        </div>
        <div class="d-flex gap-2">
          <v-btn
            prepend-icon="mdi-chart-sankey"
            variant="outlined"
            @click="router.push('/company/context/management-system')"
          >
            Cartographie
          </v-btn>
          <v-btn
            prepend-icon="mdi-download"
            variant="outlined"
            @click="exportProcesses"
          >
            Exporter
          </v-btn>
        </div>
      </div>

      <!-- Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="processes"
          :items-per-page="pagination.per_page"
          :loading="loading"
          loading-text="Chargement des processus..."
        >
          <!-- Code -->
          <template #item.code="{ item }">
            <span class="font-weight-bold">{{ item.code }}</span>
          </template>

          <!-- Title -->
          <template #item.title="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.title }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ truncate(item.finalite, 60) }}
              </div>
            </div>
          </template>

          <!-- Type -->
          <template #item.category="{ item }">
            <v-chip
              :color="getTypeColor(item.category)"
              size="small"
            >
              {{ getTypeLabel(item.category) }}
            </v-chip>
          </template>

          <!-- Pilot -->
          <template #item.pilot_id="{ item }">
            <div v-if="item.pilot" class="d-flex align-center gap-2">
              <v-avatar size="24">
                <v-icon size="16">mdi-account</v-icon>
              </v-avatar>
              <span class="text-body-2">{{ item.pilot.name }}</span>
            </div>
            <span v-else class="text-caption text-medium-emphasis">Non assigné</span>
          </template>

          <!-- Level -->
          <template #item.level="{ item }">
            <v-chip size="small" variant="outlined">
              N{{ item.level }}
            </v-chip>
          </template>

          <!-- Status -->
          <template #item.status="{ item }">
            <ProcessStatusChip :status="item.status" />
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <v-tooltip text="Voir les détails">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="viewProcess(item)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip v-if="canVerify(item)" text="Vérifier le processus">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="orange"
                    icon="mdi-check"
                    size="small"
                    variant="text"
                    @click="verifyProcess(item)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip v-if="canValidate(item)" text="Valider et activer">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="success"
                    icon="mdi-check-circle"
                    size="small"
                    variant="text"
                    @click="validateProcess(item)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip v-if="canEdit(item)" text="Modifier">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    @click="editProcess(item)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip v-if="item.status === 'draft'" text="Supprimer">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="error"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="deleteProcess(item)"
                  />
                </template>
              </v-tooltip>
            </div>
          </template>

          <!-- Empty State -->
          <template #no-data>
            <div class="text-center py-8">
              <v-icon color="grey" size="64">mdi-sitemap-outline</v-icon>
              <div class="text-h6 mt-4 text-medium-emphasis">
                Aucun processus trouvé
              </div>
              <v-btn
                class="mt-4"
                color="primary"
                variant="outlined"
                @click="router.push({ path: '/company/context/management-system', query: { openCreate: '1' } })"
              >
                Créer votre premier processus
              </v-btn>
            </div>
          </template>
        </v-data-table>

        <!-- Pagination -->
        <v-divider />
        <div class="d-flex justify-center pa-4">
          <v-pagination
            v-model="pagination.current_page"
            :length="pagination.last_page"
            :total-visible="7"
            @update:model-value="fetchProcesses"
          />
        </div>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { ProcessFilters } from '@/services/processService'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcessStats from '@/modules/clienta/components/processes/ProcessStats.vue'
  import ProcessStatusChip from '@/modules/clienta/components/processes/ProcessStatusChip.vue'
  import processWorkflowService from '@/services/processWorkflowService'
  import { useAuthStore } from '@/stores/auth'
  import { useProcessStore } from '@/stores/processStore'
  import { useSiteStore } from '@/stores/siteStore'

  const router = useRouter()
  const processStore = useProcessStore()
  const siteStore = useSiteStore()
  const authStore = useAuthStore()

  interface ProcessListFilters {
    site_id: number | null
    category: string | null
    status: string | null
    level: number | null
    search: string
  }

  const loading = ref(false)
  const filters = ref<ProcessListFilters>({
    site_id: null,
    category: null,
    status: null,
    level: null,
    search: '',
  })

  const typeOptions = [
    { title: 'Pilotage', value: 'pilotage' },
    { title: 'Support', value: 'support' },
    { title: 'Opérationnel', value: 'operationnel' },
  ]

  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'En révision', value: 'in_review' },
    { title: 'Validé', value: 'validated' },
    { title: 'Actif', value: 'active' },
    { title: 'Obsolète', value: 'obsolete' },
  ]

  const levelOptions = [
    { title: 'Niveau 1 - Principal', value: 1 },
    { title: 'Niveau 2 - Sous-processus', value: 2 },
    { title: 'Niveau 3 - Détaillé', value: 3 },
  ]

  const headers = [
    { title: 'Code', key: 'code', sortable: true },
    { title: 'Titre', key: 'title', sortable: true },
    { title: 'Type', key: 'category', sortable: true },
    { title: 'Pilote', key: 'pilot_id', sortable: false },
    { title: 'Niveau', key: 'level', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ] as const

  const processes = computed(() => processStore.processes)
  const pagination = computed(() => processStore.pagination)
  const sites = computed(() => siteStore.sites)
  const stats = computed(() => processStore.statistics || {})
  const currentUser = computed(() => authStore.user)

  onMounted(async () => {
    await Promise.all([
      fetchProcesses(),
      fetchStatistics(),
      siteStore.fetchAll(),
    ])
  })

  async function fetchProcesses (page = 1) {
    loading.value = true
    try {
      await processStore.fetchProcesses(page)
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    try {
      await processStore.fetchStatistics(filters.value.site_id || undefined)
    } catch (error) {
      console.error('Error fetching statistics:', error)
    }
  }

  function applyFilters () {
    processStore.setFilters(toProcessFilters(filters.value))
    fetchProcesses(1)
    fetchStatistics()
  }

  function resetFilters () {
    filters.value = {
      site_id: null,
      category: null,
      status: null,
      level: null,
      search: '',
    }
    processStore.resetFilters()
    fetchProcesses(1)
    fetchStatistics()
  }

  function handleStatsFilter (filterData: any) {
    // Merge the filter from stats click
    filters.value = { ...filters.value, ...filterData }
    applyFilters()
  }

  function toProcessFilters (value: ProcessListFilters): ProcessFilters {
    return {
      site_id: value.site_id ?? undefined,
      category: value.category ?? undefined,
      status: value.status ?? undefined,
      level: value.level ?? undefined,
      search: value.search || undefined,
    }
  }

  function viewProcess (process: any) {
    router.push(`/company/processes/${process.id}`)
  }

  function editProcess (process: any) {
    router.push(`/company/processes/${process.id}/edit`)
  }

  async function deleteProcess (process: any) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le processus "${process.title}" ?`)) {
      await processStore.deleteProcess(process.id)
      await fetchStatistics()
    }
  }

  function exportProcesses () {
    // TODO: Implement export
    console.log('Export processes')
  }

  function truncate (text: string | undefined, maxLength: number): string {
    if (!text) return ''
    return text.length > maxLength ? `${text.slice(0, Math.max(0, maxLength))}...` : text
  }

  function getTypeColor (category: string): string {
    switch (category) {
      case 'pilotage': { return 'purple'
      }
      case 'support': { return 'indigo'
      }
      case 'operationnel': { return 'teal'
      }
      case 'amelioration': { return 'blue'
      }
      default: { return 'grey'
      }
    }
  }

  function getTypeLabel (category: string): string {
    const option = typeOptions.find(opt => opt.value === category)
    return option?.title || category
  }

  function canEdit (process: any): boolean {
    return processWorkflowService.canEdit(process, currentUser.value)
  }

  function canVerify (process: any): boolean {
    return processWorkflowService.canVerify(process, currentUser.value)
  }

  function canValidate (process: any): boolean {
    return processWorkflowService.canValidate(process, currentUser.value)
  }

  async function verifyProcess (process: any) {
    if (confirm(`Vérifier le processus "${process.title}" et le passer en révision ?`)) {
      try {
        await processStore.verifyProcess(process.id)
        await fetchStatistics()
      } catch (error) {
        console.error('Error verifying process:', error)
      }
    }
  }

  async function validateProcess (process: any) {
    if (confirm(`Valider le processus "${process.title}" et l'activer ?`)) {
      try {
        await processStore.validateProcess(process.id)
        await fetchStatistics()
      } catch (error) {
        console.error('Error validating process:', error)
      }
    }
  }
</script>
