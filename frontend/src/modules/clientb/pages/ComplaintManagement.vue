<template>
  <ClientBLayout current-page="/clientb/complaints">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-file-alert"
        primary-action="Nouvelle réclamation"
        primary-action-icon="mdi-plus"
        subtitle="Gestion et suivi des réclamations clients"
        title="Réclamations Clients"
        @primary-action="goToCreate"
      />

      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <StatCard color="primary" icon="mdi-file-alert" label="Total" :value="totalCount" />
        </v-col>
        <v-col cols="12" md="3">
          <StatCard color="warning" icon="mdi-alert-circle" label="En attente" :value="pendingCount" />
        </v-col>
        <v-col cols="12" md="3">
          <StatCard color="info" icon="mdi-progress-clock" label="En cours" :value="inProgressCount" />
        </v-col>
        <v-col cols="12" md="3">
          <StatCard color="success" icon="mdi-check-circle" label="Clôturées" :value="closedCount" />
        </v-col>
      </v-row>

      <FilterCard
        :filters="filterItems"
        :model-value="filterState"
        search-placeholder="Rechercher..."
        @reset="clearFilters"
        @update:model-value="updateFilters"
      />

      <v-row>
        <v-col cols="12">
          <v-card>
            <v-data-table
              class="elevation-1"
              :headers="headers"
              :items="complaints"
              :loading="loading"
            >
              <template #[`item.reference`]="{ item }">
                <span class="font-weight-bold text-primary">{{ item.reference }}</span>
              </template>
              <template #[`item.status`]="{ item }">
                <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                  {{ getStatusLabel(item.status) }}
                </v-chip>
              </template>
              <template #[`item.priority`]="{ item }">
                <v-chip :color="getPriorityColor(item.priority)" size="small">
                  {{ getPriorityLabel(item.priority) }}
                </v-chip>
              </template>
              <template #[`item.created_at`]="{ item }">
                {{ formatDate(item.created_at) }}
              </template>
              <template #[`item.actions`]="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewComplaint(item)"
                />
                <v-btn
                  v-if="canEdit(item)"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editComplaint(item)"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="confirmDelete(item)"
                />
              </template>
            </v-data-table>
          </v-card>
        </v-col>
      </v-row>

      <div v-if="totalPages > 1" class="mt-6 d-flex justify-center">
        <v-pagination
          v-model="currentPage"
          :length="totalPages"
          :total-visible="7"
          @update:model-value="loadComplaints"
        />
      </div>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card>
          <v-card-title class="text-h5 font-weight-bold pa-6">
            Confirmer la suppression
          </v-card-title>
          <v-card-text class="pa-6 pt-0">
            <p class="text-body-1">
              Êtes-vous sûr de vouloir supprimer cette réclamation ?
            </p>
            <p class="text-body-2 text-medium-emphasis mt-2">
              Cette action est irréversible.
            </p>
          </v-card-text>
          <v-card-actions class="pa-6 pt-0">
            <v-spacer />
            <v-btn
              :disabled="deleting"
              variant="text"
              @click="deleteDialog = false"
            >
              Annuler
            </v-btn>
            <v-btn
              color="error"
              :loading="deleting"
              variant="flat"
              @click="handleDelete"
            >
              Supprimer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Edit Complaint Modal -->
      <EditComplaintModal
        v-model="editDialog"
        :complaint="complaintToEdit as any"
        @updated="handleComplaintUpdated"
      />
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import type { Complaint } from '@/services/complaintService'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatCard from '@/modules/clienta/components/StatCard.vue'
  import { complaintService } from '@/services/complaintService'
  import ClientBLayout from '../components/ClientBLayout.vue'
  import EditComplaintModal from '../components/EditComplaintModal.vue'

  const router = useRouter()
  const toast = useToast()

  // State
  const loading = ref(false)
  const deleting = ref(false)
  const complaints = ref<Complaint[]>([])
  const currentPage = ref(1)
  const totalComplaints = ref(0)
  const perPage = ref(10)

  // Filters
  const search = ref('')
  const statusFilter = ref('')
  const sortBy = ref('created_at_desc')
  const deleteDialog = ref(false)
  const editDialog = ref(false)
  const complaintToDelete = ref<Complaint | null>(null)
  const complaintToEdit = ref<Complaint | null>(null)

  // Options
  const statusOptions = [
    { title: 'En attente', value: 'pending' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Résolue', value: 'resolved' },
    { title: 'Fermée', value: 'closed' },
  ]

  const sortOptions = [
    { title: 'Plus récentes', value: 'created_at_desc' },
    { title: 'Plus anciennes', value: 'created_at_asc' },
    { title: 'Titre A-Z', value: 'title_asc' },
    { title: 'Titre Z-A', value: 'title_desc' },
  ]

  const filterState = ref({
    search: '',
    status: '',
    sort: 'created_at_desc',
  })

  const filterItems = computed(() => [
    {
      key: 'status',
      type: 'select',
      label: 'Filtrer par statut',
      items: statusOptions,
      itemTitle: 'title',
      itemValue: 'value',
      md: 4,
    },
    {
      key: 'sort',
      type: 'select',
      label: 'Trier par',
      items: sortOptions,
      itemTitle: 'title',
      itemValue: 'value',
      md: 4,
    },
  ])

  // Computed
  const totalPages = computed(() => Math.ceil(totalComplaints.value / perPage.value))
  const totalCount = computed(() => totalComplaints.value || complaints.value.length)
  const pendingCount = computed(() => complaints.value.filter(c => c.status === 'pending').length)
  const inProgressCount = computed(() => complaints.value.filter(c => c.status === 'in_progress').length)
  const closedCount = computed(() => complaints.value.filter(c => ['resolved', 'closed'].includes(c.status)).length)

  const headers = [
    { title: 'Référence', value: 'reference' },
    { title: 'Titre', value: 'title' },
    { title: 'Statut', value: 'status' },
    { title: 'Priorité', value: 'priority' },
    { title: 'Date', value: 'created_at' },
    { title: 'Actions', value: 'actions', sortable: false },
  ]

  // Load complaints
  async function loadComplaints () {
    try {
      loading.value = true

      const [sortField, sortOrder] = sortBy.value.split('_')

      const response = await complaintService.getComplaints({
        page: currentPage.value,
        per_page: perPage.value,
        status: statusFilter.value || undefined,
        search: search.value || undefined,
        sort_by: sortField,
        sort_order: sortOrder as 'asc' | 'desc',
      })

      complaints.value = response.data || []
      totalComplaints.value = response.total || 0
    } catch (error) {
      console.error('Error loading complaints:', error)
      toast.error('Erreur lors du chargement des réclamations')
    } finally {
      loading.value = false
    }
  }

  // Search handler with debounce
  let searchTimeout: ReturnType<typeof setTimeout>
  function handleSearch () {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      currentPage.value = 1
      loadComplaints()
    }, 500)
  }

  function updateFilters (value: Record<string, any>) {
    filterState.value = { ...filterState.value, ...value }
  }

  // Clear filters
  function clearFilters () {
    filterState.value = { search: '', status: '', sort: 'created_at_desc' }
    search.value = ''
    statusFilter.value = ''
    sortBy.value = 'created_at_desc'
    currentPage.value = 1
    loadComplaints()
  }

  // Priority helpers
  function getPriorityColor (priority: string) {
    const colors: Record<string, string> = {
      low: 'success',
      medium: 'warning',
      high: 'error',
      urgent: 'error',
    }
    return colors[priority] || 'grey'
  }

  function getPriorityLabel (priority: string) {
    const labels: Record<string, string> = {
      low: 'Basse',
      medium: 'Moyenne',
      high: 'Haute',
      urgent: 'Urgente',
    }
    return labels[priority] || priority
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      pending: 'En attente',
      in_progress: 'En cours',
      resolved: 'Résolue',
      closed: 'Fermée',
    }
    return labels[status] || status
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      pending: 'warning',
      in_progress: 'info',
      resolved: 'success',
      closed: 'grey',
    }
    return colors[status] || 'grey'
  }

  // Format date
  function formatDate (dateString: string | undefined) {
    if (!dateString) return 'N/A'
    try {
      const date = new Date(dateString)
      if (Number.isNaN(date.getTime())) return 'N/A'
      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    } catch {
      return 'N/A'
    }
  }

  // Actions
  function viewComplaint (complaint: Complaint) {
    router.push(`/clientb/complaints/${complaint.id}`)
  }

  function goToCreate () {
    router.push('/clientb/complaints/create')
  }

  function editComplaint (complaint: Complaint) {
    complaintToEdit.value = complaint
    editDialog.value = true
  }

  function handleComplaintUpdated () {
    editDialog.value = false
    loadComplaints()
  }

  function confirmDelete (complaint: Complaint) {
    complaintToDelete.value = complaint
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!complaintToDelete.value) return

    deleting.value = true
    try {
      await complaintService.deleteComplaint(complaintToDelete.value.id)
      toast.success('Réclamation supprimée avec succès')
      deleteDialog.value = false
      loadComplaints()
    } catch (error: any) {
      console.error('Error deleting complaint:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
    } finally {
      deleting.value = false
    }
  }

  function canEdit (complaint: Complaint) {
    return complaint.status === 'pending'
  }

  watch(
    () => filterState.value.search,
    value => {
      search.value = value || ''
      handleSearch()
    },
  )

  watch(
    () => filterState.value.status,
    value => {
      statusFilter.value = value || ''
      currentPage.value = 1
      loadComplaints()
    },
  )

  watch(
    () => filterState.value.sort,
    value => {
      sortBy.value = value || 'created_at_desc'
      currentPage.value = 1
      loadComplaints()
    },
  )

  // Initialize
  onMounted(() => {
    filterState.value.sort = 'created_at_desc'
    loadComplaints()
  })
</script>

<style scoped>
</style>
