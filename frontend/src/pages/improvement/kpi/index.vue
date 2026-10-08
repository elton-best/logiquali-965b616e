<template>
  <div class="indicators-page p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold">Indicateurs de Performance</h1>
        <p class="text-gray-600 mt-1">Gestion des KPIs et objectifs QHSE</p>
      </div>
      <div class="flex gap-3">
        <button
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          @click="router.push('/improvement/kpi/dashboard')"
        >
          Tableau de bord
        </button>
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
          @click="showCreateModal = true"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Nouvel indicateur
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <BaseCard class="text-center">
        <p class="text-sm text-gray-600 mb-1">Total indicateurs</p>
        <p class="text-3xl font-bold">{{ statistics.total }}</p>
      </BaseCard>
      <BaseCard class="text-center">
        <p class="text-sm text-gray-600 mb-1">Conformes</p>
        <p class="text-3xl font-bold text-green-600">{{ statistics.by_alert_level.green }}</p>
      </BaseCard>
      <BaseCard class="text-center">
        <p class="text-sm text-gray-600 mb-1">Attention</p>
        <p class="text-3xl font-bold text-yellow-600">{{ statistics.by_alert_level.yellow }}</p>
      </BaseCard>
      <BaseCard class="text-center">
        <p class="text-sm text-gray-600 mb-1">Critiques</p>
        <p class="text-3xl font-bold text-red-600">{{ statistics.by_alert_level.red }}</p>
      </BaseCard>
    </div>

    <!-- Filters -->
    <IndicatorFilters
      v-model="filters"
      class="mb-6"
      :counts="alertCounts"
      @change="handleFilterChange"
    />

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <!-- Indicators Grid -->
    <div v-else-if="indicators.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <IndicatorCard
        v-for="indicator in indicators"
        :key="indicator.id"
        :indicator="indicator"
        @record-value="openRecordValueModal(indicator)"
        @view="router.push(`/improvement/kpi/${indicator.id}`)"
      />
    </div>

    <!-- Empty -->
    <div v-else class="text-center py-12">
      <h3 class="text-lg font-medium mb-2">Aucun indicateur</h3>
      <p class="text-gray-600 mb-4">Créez votre premier indicateur</p>
      <button
        class="px-4 py-2 bg-blue-600 text-white rounded-lg"
        @click="showCreateModal = true"
      >
        Créer un indicateur
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex justify-between items-center mt-6">
      <span class="text-sm text-gray-600">{{ meta.from }} - {{ meta.to }} sur {{ meta.total }}</span>
      <div class="flex gap-2">
        <button class="px-3 py-2 border rounded-lg disabled:opacity-50" :disabled="!links.prev" @click="goToPage(meta.current_page - 1)">Précédent</button>
        <button class="px-3 py-2 border rounded-lg disabled:opacity-50" :disabled="!links.next" @click="goToPage(meta.current_page + 1)">Suivant</button>
      </div>
    </div>

    <!-- Modals would go here -->
  </div>
</template>

<script setup lang="ts">
  import type { IndicatorFilters as Filters } from '@/types/indicator'
  import { storeToRefs } from 'pinia'
  import { onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import BaseCard from '@/components/common/BaseCard.vue'
  import IndicatorCard from '@/components/indicators/IndicatorCard.vue'
  import IndicatorFilters from '@/components/indicators/IndicatorFilters.vue'
  import { useIndicatorStore } from '@/stores/indicatorStore'

  const router = useRouter()
  const indicatorStore = useIndicatorStore()

  const { indicators, loading, meta, links, statistics, alertCounts } = storeToRefs(indicatorStore)

  const filters = ref<Filters>({})
  const showCreateModal = ref(false)

  async function loadIndicators () {
    await indicatorStore.fetchIndicators(filters.value)
    await indicatorStore.fetchStatistics()
  }

  function handleFilterChange (newFilters: Filters) {
    filters.value = newFilters
    loadIndicators()
  }

  function goToPage (page: number) {
    filters.value.page = page
    loadIndicators()
  }

  function openRecordValueModal (indicator: any) {
    router.push(`/improvement/kpi/${indicator.id}?record=true`)
  }

  onMounted(() => {
    loadIndicators()
  })
</script>
