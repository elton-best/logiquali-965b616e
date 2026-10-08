<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="loading && !currentIndicator" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600" />
    </div>

    <template v-else-if="currentIndicator">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
          <button
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
            @click="$router.back()"
          >
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ currentIndicator.code }}</h1>
              <span class="px-2 py-1 text-xs rounded" :class="getCategoryColor(currentIndicator.category)">
                {{ getCategoryLabel(currentIndicator.category) }}
              </span>
              <span
                :class="[
                  'px-2 py-1 text-xs rounded',
                  currentIndicator.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                ]"
              >
                {{ currentIndicator.status === 'active' ? 'Actif' : 'Inactif' }}
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400">{{ currentIndicator.name }}</p>
          </div>
        </div>

        <button
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="showRecordModal = true"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Enregistrer une valeur
        </button>
      </div>

      <!-- KPI Card -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Valeur Actuelle</p>
            <div class="flex items-baseline justify-center gap-2">
              <span class="text-5xl font-bold text-gray-900 dark:text-white">
                {{ latestValue?.value ?? '--' }}
              </span>
              <span class="text-xl text-gray-600 dark:text-gray-400">{{ currentIndicator.unit || '' }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ latestValue?.period || 'Aucune donnée' }}</p>
          </div>

          <div class="text-center border-l border-r border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Objectif</p>
            <div class="flex items-baseline justify-center gap-2">
              <span class="text-5xl font-bold text-blue-600">
                {{ currentIndicator.target_value }}
              </span>
              <span class="text-xl text-gray-600 dark:text-gray-400">{{ currentIndicator.unit || '' }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-2">Cible à atteindre</p>
          </div>

          <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Taux d'atteinte</p>
            <div class="flex items-baseline justify-center gap-2">
              <span
                :class="[
                  'text-5xl font-bold',
                  achievementRate >= 100 ? 'text-green-600' : achievementRate >= 80 ? 'text-yellow-600' : 'text-red-600'
                ]"
              >
                {{ achievementRate }}
              </span>
              <span class="text-xl text-gray-600 dark:text-gray-400">%</span>
            </div>
            <div class="flex items-center justify-center gap-1 mt-2">
              <svg
                v-if="trend === 'up'"
                class="w-5 h-5 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
              <svg
                v-else-if="trend === 'down'"
                class="w-5 h-5 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
              <span class="text-xs text-gray-500">Tendance {{ trend === 'up' ? 'à la hausse' : trend === 'down' ? 'à la baisse' : 'stable' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gauge and Trend Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gauge Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
            Performance Actuelle
          </h2>
          <div class="flex items-center justify-center h-64">
            <div class="relative w-48 h-48">
              <svg class="w-full" viewBox="0 0 200 120">
                <path
                  class="dark:stroke-gray-700"
                  d="M 20 100 A 80 80 0 0 1 180 100"
                  fill="none"
                  stroke="#E5E7EB"
                  stroke-width="20"
                />
                <path
                  d="M 20 100 A 80 80 0 0 1 180 100"
                  fill="none"
                  :stroke="getGaugeColor(achievementRate)"
                  :stroke-dasharray="`${achievementRate * 2.51} 251`"
                  stroke-linecap="round"
                  stroke-width="20"
                />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ achievementRate }}%</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">de l'objectif</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
            Évolution (12 dernières périodes)
          </h2>
          <div class="h-64 flex items-end justify-between gap-1">
            <div
              v-for="(point, index) in chartData.slice(-12)"
              :key="index"
              class="flex-1 flex flex-col items-center gap-1"
            >
              <div class="relative w-full">
                <div
                  class="w-full bg-blue-500 rounded-t hover:bg-blue-600 transition-colors"
                  :style="{ height: `${(point.value / Math.max(...chartData.map(p => p.value), currentIndicator.target_value)) * 200}px` }"
                  :title="`${point.period}: ${point.value} ${currentIndicator.unit || ''}`"
                />
                <div
                  class="absolute top-0 w-full border-t-2 border-dashed border-red-400"
                  :style="{ transform: `translateY(-${(currentIndicator.target_value / Math.max(...chartData.map(p => p.value), currentIndicator.target_value)) * 200}px)` }"
                />
              </div>
              <span class="text-xs text-gray-600 dark:text-gray-400 rotate-45 origin-left">{{ point.period }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Details and Values History -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Indicator Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Détails</h2>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-gray-600 dark:text-gray-400">Description</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ currentIndicator.description }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-600 dark:text-gray-400">Type</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">
                {{ currentIndicator.type === 'quantitative' ? 'Quantitatif' : 'Qualitatif' }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-gray-600 dark:text-gray-400">Fréquence</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ getFrequencyLabel(currentIndicator.frequency) }}</dd>
            </div>
            <div v-if="currentIndicator.calculation_formula">
              <dt class="text-sm text-gray-600 dark:text-gray-400">Formule de calcul</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ currentIndicator.calculation_formula }}</dd>
            </div>
            <div v-if="currentIndicator.threshold_min || currentIndicator.threshold_max">
              <dt class="text-sm text-gray-600 dark:text-gray-400">Seuils</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">
                <span v-if="currentIndicator.threshold_min">Min: {{ currentIndicator.threshold_min }}</span>
                <span v-if="currentIndicator.threshold_min && currentIndicator.threshold_max"> / </span>
                <span v-if="currentIndicator.threshold_max">Max: {{ currentIndicator.threshold_max }}</span>
              </dd>
            </div>
            <div v-if="currentIndicator.responsible">
              <dt class="text-sm text-gray-600 dark:text-gray-400">Responsable</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ currentIndicator.responsible.name }}</dd>
            </div>
            <div v-if="currentIndicator.data_source">
              <dt class="text-sm text-gray-600 dark:text-gray-400">Source de données</dt>
              <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ currentIndicator.data_source }}</dd>
            </div>
          </dl>
        </div>

        <!-- Values History -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historique des valeurs</h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Période</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valeur</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Objectif</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Atteinte</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Commentaire</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="value in values" :key="value.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ value.period }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                    {{ value.value }} {{ currentIndicator.unit || '' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ value.target }} {{ currentIndicator.unit || '' }}
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <span
                      :class="[
                        'px-2 py-1 rounded text-xs font-medium',
                        getAchievementColor(value.value, value.target)
                      ]"
                    >
                      {{ Math.round((value.value / value.target) * 100) }}%
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ value.comment || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="values.length === 0" class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune valeur enregistrée</h3>
              <p class="mt-1 text-sm text-gray-500">Commencez par enregistrer une valeur.</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Record Value Modal -->
    <div
      v-if="showRecordModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showRecordModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enregistrer une valeur</h3>

        <form class="space-y-4" @submit.prevent="handleRecordValue">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Période <span class="text-red-500">*</span>
            </label>
            <input
              v-model="recordForm.period"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              required
              type="date"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Valeur <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="recordForm.value"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              :placeholder="currentIndicator?.unit || ''"
              required
              step="any"
              type="number"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Objectif
            </label>
            <input
              v-model.number="recordForm.target"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              :placeholder="currentIndicator?.target_value.toString()"
              step="any"
              type="number"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Commentaire
            </label>
            <textarea
              v-model="recordForm.comment"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Ajouter un commentaire..."
              rows="3"
            />
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
              type="button"
              @click="showRecordModal = false"
            >
              Annuler
            </button>
            <button
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
              :disabled="loading"
              type="submit"
            >
              {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { IndicatorCategory, IndicatorFrequency } from '@/api/services/indicators.service'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useIndicators } from '@/modules/clienta/composables/useIndicators'

  const route = useRoute()
  const _router = useRouter()
  const {
    currentIndicator,
    values,
    chartData,
    loading,
    fetchIndicator,
    fetchHistory,
    fetchChart,
    recordValue,
  } = useIndicators()

  const showRecordModal = ref(false)
  const recordForm = ref({
    period: new Date().toISOString().split('T')[0],
    value: 0,
    target: undefined as number | undefined,
    comment: '',
  })

  const latestValue = computed(() => values.value[0] || null)

  const achievementRate = computed(() => {
    if (!latestValue.value || !latestValue.value.target) return 0
    return Math.round((latestValue.value.value / latestValue.value.target) * 100)
  })

  const trend = computed(() => {
    if (values.value.length < 2) return 'stable'
    const latest = values.value[0]?.value ?? 0
    const previous = values.value[1]?.value ?? 0
    if (latest > previous) return 'up'
    if (latest < previous) return 'down'
    return 'stable'
  })

  function getCategoryLabel (category: IndicatorCategory): string {
    const labels: Record<IndicatorCategory, string> = {
      qualite: 'Qualité',
      hygiene: 'Hygiène',
      securite: 'Sécurité',
      environnement: 'Environnement',
      performance: 'Performance',
    }
    return labels[category]
  }

  function getCategoryColor (category: IndicatorCategory): string {
    const colors: Record<IndicatorCategory, string> = {
      qualite: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
      hygiene: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
      securite: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
      environnement: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      performance: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    }
    return colors[category]
  }

  function getFrequencyLabel (frequency: IndicatorFrequency): string {
    const labels: Record<IndicatorFrequency, string> = {
      daily: 'Quotidien',
      weekly: 'Hebdomadaire',
      monthly: 'Mensuel',
      quarterly: 'Trimestriel',
      yearly: 'Annuel',
    }
    return labels[frequency]
  }

  function getGaugeColor (rate: number): string {
    if (rate >= 100) return '#10B981'
    if (rate >= 80) return '#F59E0B'
    return '#EF4444'
  }

  function getAchievementColor (value: number, target: number): string {
    const rate = (value / target) * 100
    if (rate >= 100) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    if (rate >= 80) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }

  async function handleRecordValue () {
    if (!currentIndicator.value) return

    try {
      await recordValue(currentIndicator.value.id, {
        period: recordForm.value.period || '',
        value: recordForm.value.value,
        target: recordForm.value.target || currentIndicator.value.target_value || 0,
        comment: recordForm.value.comment,
      })

      showRecordModal.value = false
      recordForm.value = {
        period: new Date().toISOString().split('T')[0],
        value: 0,
        target: undefined,
        comment: '',
      }

      await Promise.all([
        fetchHistory(currentIndicator.value.id),
        fetchChart(currentIndicator.value.id, { periods: 12 }),
      ])
    } catch (error) {
      console.error('Failed to record value:', error)
    }
  }

  onMounted(async () => {
    const id = Number((route.params as { id: string }).id)
    if (id) {
      await fetchIndicator(id)
      await Promise.all([
        fetchHistory(id),
        fetchChart(id, { periods: 12 }),
      ])
    }
  })
</script>
