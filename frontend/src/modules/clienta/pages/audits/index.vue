<template>
  <ClientALayout current-page="audits">
    <v-container class="pa-6" fluid>
      <PageHeader
        subtitle="Planifiez et suivez vos audits qualité"
        title="Audits QHSE"
      >
        <template #actions>
          <v-btn
            v-if="canCreateAudit"
            color="primary"
            prepend-icon="mdi-plus"
            @click="router.push('/company/audits/create')"
          >
            Planifier un Audit
          </v-btn>
        </template>
      </PageHeader>

      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="ClipboardCheck"
            title="Total"
            :value="stats.total"
            variant="audit"
            @click="
              () => {
                filters.status = '';
                fetchAudits();
              }
            "
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Calendar"
            title="Planifiés"
            :value="stats.planned"
            variant="info"
            @click="
              () => {
                filters.status = 'planned';
                fetchAudits();
              }
            "
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Clock"
            title="En cours"
            :value="stats.in_progress"
            variant="warning"
            @click="
              () => {
                filters.status = 'in_progress';
                fetchAudits();
              }
            "
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="CheckCheck"
            title="Terminés"
            :value="stats.completed"
            variant="success"
            @click="
              () => {
                filters.status = 'closed';
                fetchAudits();
              }
            "
          />
        </v-col>
      </v-row>

      <!-- Filters -->
      <FilterCard
        v-model="filters"
        :filters="filterFields as any"
        @apply="fetchAudits()"
      />

      <!-- Table -->
      <v-card class="mt-6">
        <DataTable
          empty-message="Aucun audit trouvé"
          :headers="headers"
          :items="filteredItems"
          :loading="loading"
        >
          <template #item.type="{ item }">
            <StatusChip :label="item.type" size="small" />
          </template>

          <template #item.status="{ item }">
            <StatusChip
              :color="getStatusColor(item.status)"
              :label="getStatusLabel(item.status)"
              size="small"
            />
          </template>

          <template #item.planned_start_date="{ item }">
            {{ formatDate(item.planned_start_date) }}
          </template>

          <template #item.planned_end_date="{ item }">
            {{ formatDate(item.planned_end_date) }}
          </template>

          <template #item.actions="{ item }">
            <v-btn
              v-if="canReadAudit"
              icon="mdi-eye"
              size="small"
              variant="text"
              @click="viewItem(item)"
            />
            <v-btn
              v-if="canUpdateAudit"
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="editItem(item)"
            />
            <v-btn
              v-if="canDeleteAudit"
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteItem(item)"
            />
          </template>
        </DataTable>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { Calendar, CheckCheck, ClipboardCheck, Clock } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { AppWidget } from '@/components/common'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useAudits } from '@/modules/clienta/composables/useAudits'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { getErrorMessage } from '@/utils/errorMessage'
  import { expandPermissionAliases } from '@/utils/permissions'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const { audits, loading, stats, fetchAudits, deleteAudit } = useAudits()

  const { sites, fetchSites } = useSites()

  const _searchQuery = ref('')
  const _typeFilter = ref<string>('all')
  const _standardFilter = ref<string>('all')
  const _statusFilter = ref<string>('all')
  const _yearFilter = ref<string>(new Date().getFullYear().toString())

  const filters = ref({
    search: '',
    type: '',
    status: '',
    site_id: null,
  })

  const auditTypes = [
    { value: 'interne', label: 'Interne', color: 'blue' },
    { value: 'externe', label: 'Externe', color: 'purple' },
    { value: 'certification', label: 'Certification', color: 'green' },
  ]

  const _standards = [
    { value: 'ISO9001', label: 'ISO 9001' },
    { value: 'ISO14001', label: 'ISO 14001' },
    { value: 'ISO45001', label: 'ISO 45001' },
    { value: 'autre', label: 'Autre' },
  ]

  const statuses = [
    { value: 'planned', label: 'Planifié', color: 'gray' },
    { value: 'in_progress', label: 'En cours', color: 'blue' },
    { value: 'report_draft', label: 'Rapport en rédaction', color: 'yellow' },
    { value: 'report_approved', label: 'Rapport approuvé', color: 'green' },
    { value: 'closed', label: 'Clôturé', color: 'gray' },
  ]

  const typeFilters = auditTypes.map(t => ({ title: t.label, value: t.value }))
  const statusFilters = statuses.map(s => ({ title: s.label, value: s.value }))

  const filterFields = computed(() => [
    {
      type: 'select',
      model: filters.value.type,
      placeholder: 'Type',
      options: typeFilters,
      onChange: (val: string) => {
        filters.value.type = val
      },
    },
    {
      type: 'select',
      model: filters.value.status,
      placeholder: 'Statut',
      options: statusFilters,
      onChange: (val: string) => {
        filters.value.status = val
      },
    },
    {
      type: 'select',
      model: filters.value.site_id,
      placeholder: 'Site',
      options: (sites.value || []).map(s => ({ title: s.name, value: s.id })),
      onChange: (val: any) => {
        filters.value.site_id = val
      },
    },
  ])

  const headers = [
    { key: 'reference', label: 'Référence', sortable: false },
    { key: 'title', label: 'Titre', sortable: false },
    { key: 'type', label: 'Type', sortable: false },
    { key: 'status', label: 'Statut', sortable: false },
    { key: 'planned_start_date', label: 'Date début', sortable: false },
    { key: 'planned_end_date', label: 'Date fin', sortable: false },
    { key: 'actions', label: 'Actions', sortable: false },
  ]

  const _years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => (currentYear - i).toString())
  })

  const filteredItems = computed(() => {
    let filtered = audits.value

    if (filters.value.search) {
      const query = filters.value.search.toLowerCase()
      filtered = filtered.filter(
        a =>
          a.title.toLowerCase().includes(query)
          || a.reference?.toLowerCase().includes(query)
          || a.scope?.toLowerCase().includes(query)
          || a.lead_auditor?.name?.toLowerCase().includes(query),
      )
    }

    if (filters.value.type) {
      filtered = filtered.filter(a => a.type === filters.value.type)
    }

    if (filters.value.status) {
      filtered = filtered.filter(a => a.status === filters.value.status)
    }

    if (filters.value.site_id) {
      filtered = filtered.filter(a => a.site_id === filters.value.site_id)
    }

    return filtered
  })

  function getStatusColor (status: string) {
    const statusConfig = statuses.find(s => s.value === status)
    return statusConfig?.color || 'gray'
  }

  function getStatusLabel (status: string) {
    const statusConfig = statuses.find(s => s.value === status)
    return statusConfig?.label || status
  }

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (
      currentUser?.user_type === 'super_admin'
      || isEnterpriseAdminUser(currentUser)
    ) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canReadAudit = computed(() => canAccess(['evaluation.audits.read']))
  const canCreateAudit = computed(() => canAccess(['evaluation.audits.create']))
  const canUpdateAudit = computed(() => canAccess(['evaluation.audits.update']))
  const canDeleteAudit = computed(() => canAccess(['evaluation.audits.delete']))

  function viewItem (item: any) {
    if (!canReadAudit.value) return
    router.push(`/company/audits/${item.id}`)
  }

  function editItem (item: any) {
    if (!canUpdateAudit.value) return
    router.push(`/company/audits/${item.id}`)
  }

  async function deleteItem (item: any) {
    if (!canDeleteAudit.value) return
    if (confirm('Êtes-vous sûr de vouloir supprimer cet audit ?')) {
      try {
        await deleteAudit(item.id)
        toast.success('Audit supprimé.')
      } catch (error) {
        console.error('Failed to delete audit:', error)
        toast.error(getErrorMessage(error, 'Impossible de supprimer cet audit.'))
      }
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  onMounted(async () => {
    await Promise.all([fetchAudits(), fetchSites()])
  })
</script>
