<template>
  <ClientALayout current-page="risks">
    <PageHeader
      subtitle="Identification, analyse et traitement des risques"
      title="Gestion des Risques QHSE"
    >
      <template #actions>
        <v-btn
          v-if="canCreateRisk"
          color="primary"
          prepend-icon="mdi-plus"
          @click="$router.push('/company/risks/create')"
        >
          Nouveau Risque
        </v-btn>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <AppWidget
        clickable
        :icon="Shield"
        title="Total"
        :value="stats.total"
        variant="risk"
        @click="applyFilters()"
      />
      <AppWidget
        clickable
        :icon="AlertTriangle"
        title="Critiques"
        :value="stats.high"
        variant="error"
        @click="filterByLevel('high')"
      />
      <AppWidget
        clickable
        :icon="AlertCircle"
        title="Élevés"
        :value="stats.high"
        variant="warning"
        @click="filterByLevel('high')"
      />
      <AppWidget
        clickable
        :icon="TrendingUp"
        title="Modérés"
        :value="stats.medium"
        variant="info"
        @click="filterByLevel('medium')"
      />
      <AppWidget
        clickable
        :icon="CheckCircle"
        title="Faibles"
        :value="stats.low"
        variant="success"
        @click="filterByLevel('low')"
      />
    </div>

    <!-- Risk Matrix Chart - Keep existing -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Matrice des Risques</h2>
      <div class="grid grid-cols-6 gap-1">
        <div class="col-span-1 row-span-5 flex items-center justify-center">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 -rotate-90">Probabilité</span>
        </div>
        <template v-for="prob in [5, 4, 3, 2, 1]" :key="'prob-' + prob">
          <div
            v-for="imp in [1, 2, 3, 4, 5]"
            :key="'cell-' + prob + '-' + imp"
            :class="[
              'aspect-square flex items-center justify-center text-sm font-medium rounded',
              getRiskMatrixColor(prob * imp),
            ]"
          >
            {{ getRiskCountInCell(prob, imp) }}
          </div>
        </template>
        <div class="col-span-1" />
        <div class="col-span-5 flex justify-around mt-2">
          <span class="text-xs text-gray-600 dark:text-gray-400">1</span>
          <span class="text-xs text-gray-600 dark:text-gray-400">2</span>
          <span class="text-xs text-gray-600 dark:text-gray-400">3</span>
          <span class="text-xs text-gray-600 dark:text-gray-400">4</span>
          <span class="text-xs text-gray-600 dark:text-gray-400">5</span>
        </div>
        <div class="col-span-6 text-center mt-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Impact</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <FilterCard
      v-model="filters"
      :filters="filterFields"
      @apply="applyFilters"
    />

    <!-- Risks Table -->
    <DataTable
      empty-message="Aucun risque trouvé"
      :headers="headers"
      :items="risks"
      :loading="loading"
      :pagination="pagination"
      @page-change="changePage"
    >
      <template #item.reference="{ item }">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ item.reference }}</span>
      </template>

      <template #item.category="{ item }">
        <StatusChip :color="getCategoryColor(item.category)" :label="getCategoryLabel(item.category)" />
      </template>

      <template #item.risk_score="{ item }">
        <StatusChip :color="getRiskLevelColor(item.risk_score)" :label="item.risk_score" />
      </template>

      <template #item.treatment="{ item }">
        <span class="text-sm text-gray-900 dark:text-white">{{ getTreatmentLabel(item.treatment) }}</span>
      </template>

      <template #item.status="{ item }">
        <StatusChip :color="getStatusColor(item.status)" :label="getStatusLabel(item.status)" />
      </template>

      <template #item.actions="{ item }">
        <div class="flex gap-2">
          <RouterLink
            class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
            :to="`/company/risks/${item.id}`"
          >
            Voir
          </RouterLink>
          <button
            v-if="canDeleteRisk"
            class="text-red-600 hover:text-red-900 dark:text-red-400"
            @click="handleDelete(item.id)"
          >
            Supprimer
          </button>
        </div>
      </template>
    </DataTable>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { RiskCategory, RiskStatus, RiskTreatment } from '@/api/services/risks.service'
  import { AlertCircle, AlertTriangle, CheckCircle, Shield, TrendingUp } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useRisks } from '@/modules/clienta/composables/useRisks'
  import { getRiskLevelColor } from '@/modules/clienta/utils/statusMappers'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const { risks, loading, pagination, stats, fetchRisks, deleteRisk } = useRisks()
  const authStore = useAuthStore()

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canCreateRisk = computed(() => canAccess(['planification.risques_opportunites.create']))
  const canDeleteRisk = computed(() => canAccess(['planification.risques_opportunites.delete']))

  const filters = ref({
    search: '',
    category: '' as RiskCategory | '',
    status: '' as RiskStatus | '',
  })

  interface RiskFilterField {
    key: 'category' | 'status'
    type: 'select'
    label: string
    items: Array<{ value: string, label: string }>
  }

  const filterFields = computed<RiskFilterField[]>(() => [
    {
      key: 'category',
      type: 'select',
      label: 'Toutes les catégories',
      items: [
        { value: 'qualite', label: 'Qualité' },
        { value: 'hygiene', label: 'Hygiène' },
        { value: 'securite', label: 'Sécurité' },
        { value: 'environnement', label: 'Environnement' },
      ],
    },
    {
      key: 'status',
      type: 'select',
      label: 'Tous les statuts',
      items: [
        { value: 'identified', label: 'Identifié' },
        { value: 'analyzed', label: 'Analysé' },
        { value: 'treated', label: 'Traité' },
        { value: 'monitored', label: 'Surveillé' },
        { value: 'closed', label: 'Clôturé' },
      ],
    },
  ])

  const headers = [
    { key: 'reference', label: 'Référence', sortable: false },
    { key: 'title', label: 'Titre', sortable: false },
    { key: 'category', label: 'Catégorie', sortable: false },
    { key: 'probability', label: 'Probabilité', sortable: false },
    { key: 'impact', label: 'Impact', sortable: false },
    { key: 'risk_score', label: 'Score', sortable: false },
    { key: 'treatment', label: 'Traitement', sortable: false },
    { key: 'status', label: 'Statut', sortable: false },
    { key: 'actions', label: 'Actions', sortable: false },
  ]

  onMounted(() => {
    fetchRisks()
  })

  function applyFilters () {
    fetchRisks({
      search: filters.value.search || undefined,
      category: filters.value.category || undefined,
      status: filters.value.status || undefined,
    })
  }

  function changePage (page: number) {
    fetchRisks({ page })
  }

  async function handleDelete (id: number) {
    if (!canDeleteRisk.value) return
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce risque ?')) return
    try {
      await deleteRisk(id)
    } catch {
      alert('Erreur lors de la suppression du risque')
    }
  }

  function getRiskCountInCell (probability: number, impact: number) {
    const count = risks.value.filter(
      r => r.probability === probability && r.impact === impact,
    ).length
    return count > 0 ? count : ''
  }

  function getRiskMatrixColor (score: number) {
    if (score <= 5) return 'bg-green-200 dark:bg-green-800 text-green-900 dark:text-green-100'
    if (score <= 10) return 'bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100'
    if (score <= 15) return 'bg-orange-200 dark:bg-orange-800 text-orange-900 dark:text-orange-100'
    return 'bg-red-200 dark:bg-red-800 text-red-900 dark:text-red-100'
  }

  function getCategoryColor (category: RiskCategory) {
    const colors = {
      qualite: 'info',
      hygiene: 'secondary',
      securite: 'error',
      environnement: 'success',
    }
    return colors[category]
  }

  function getCategoryLabel (category: RiskCategory) {
    const labels = {
      qualite: 'Qualité',
      hygiene: 'Hygiène',
      securite: 'Sécurité',
      environnement: 'Environnement',
    }
    return labels[category]
  }

  function getStatusColor (status: RiskStatus) {
    const colors = {
      identified: 'grey',
      analyzed: 'info',
      treated: 'warning',
      monitored: 'secondary',
      closed: 'success',
    }
    return colors[status]
  }

  function getStatusLabel (status: RiskStatus) {
    const labels = {
      identified: 'Identifié',
      analyzed: 'Analysé',
      treated: 'Traité',
      monitored: 'Surveillé',
      closed: 'Clôturé',
    }
    return labels[status]
  }

  function getTreatmentLabel (treatment?: RiskTreatment) {
    if (!treatment) return '-'
    const labels = {
      accept: 'Accepter',
      reduce: 'Réduire',
      transfer: 'Transférer',
      avoid: 'Éviter',
    }
    return labels[treatment]
  }

  function filterByLevel (level: string) {
    // Apply filter based on risk level
    const levelFilters: Record<string, { minScore: number, maxScore: number }> = {
      low: { minScore: 0, maxScore: 5 },
      medium: { minScore: 6, maxScore: 10 },
      high: { minScore: 11, maxScore: 25 },
    }
    // This would need backend support - for now just refresh
    fetchRisks()
  }
</script>
