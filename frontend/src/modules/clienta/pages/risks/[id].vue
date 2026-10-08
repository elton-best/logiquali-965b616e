<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <RouterLink
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
          to="/company/risks"
        >
          <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </RouterLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ currentRisk?.reference || 'Détails du Risque' }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400">{{ currentRisk?.title }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <button
          v-if="canUpdateStatus"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          @click="showStatusModal = true"
        >
          Changer le statut
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
    </div>

    <div v-else-if="!currentRisk" class="text-center py-12">
      <p class="text-gray-600 dark:text-gray-400">Risque non trouvé</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Risk Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Détails du Risque</h2>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Référence</p>
              <p class="text-gray-900 dark:text-white font-medium">{{ currentRisk.reference }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Catégorie</p>
              <span :class="getCategoryBadgeClass(currentRisk.category)">
                {{ getCategoryLabel(currentRisk.category) }}
              </span>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Processus</p>
              <p class="text-gray-900 dark:text-white font-medium">{{ currentRisk.process || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Site</p>
              <p class="text-gray-900 dark:text-white font-medium">{{ currentRisk.site?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Identifié par</p>
              <p class="text-gray-900 dark:text-white font-medium">{{ currentRisk.identified_by }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Date d'identification</p>
              <p class="text-gray-900 dark:text-white font-medium">{{ formatDate(currentRisk.identified_date) }}</p>
            </div>
          </div>

          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Description</p>
            <p class="text-gray-900 dark:text-white">{{ currentRisk.description }}</p>
          </div>
        </div>

        <!-- Risk Matrix Visualization -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Évaluation du Risque</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Probabilité</p>
              <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ currentRisk.probability }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">sur 5</p>
            </div>
            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Impact</p>
              <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ currentRisk.impact }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">sur 5</p>
            </div>
            <div class="text-center p-4 rounded-lg" :class="getScoreBackgroundClass(currentRisk.risk_score)">
              <p class="text-sm mb-1">Score Initial</p>
              <p class="text-3xl font-bold">{{ currentRisk.risk_score }}</p>
              <p class="text-xs">{{ getScoreLevel(currentRisk.risk_score) }}</p>
            </div>
          </div>

          <!-- Mini Risk Matrix -->
          <div class="grid grid-cols-6 gap-1 max-w-md mx-auto">
            <div class="col-span-1 row-span-5 flex items-center justify-center">
              <span class="text-xs font-medium text-gray-700 dark:text-gray-300 -rotate-90">Probabilité</span>
            </div>
            <template v-for="prob in [5, 4, 3, 2, 1]" :key="'prob-' + prob">
              <div
                v-for="imp in [1, 2, 3, 4, 5]"
                :key="'cell-' + prob + '-' + imp"
                :class="[
                  'aspect-square flex items-center justify-center text-xs font-medium rounded',
                  getRiskMatrixColor(prob * imp),
                  prob === currentRisk.probability && imp === currentRisk.impact ? 'ring-4 ring-blue-500' : ''
                ]"
              >
                <svg v-if="prob === currentRisk.probability && imp === currentRisk.impact" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
            </template>
            <div class="col-span-1" />
            <div class="col-span-5 flex justify-around mt-1">
              <span class="text-xs text-gray-600 dark:text-gray-400">1</span>
              <span class="text-xs text-gray-600 dark:text-gray-400">2</span>
              <span class="text-xs text-gray-600 dark:text-gray-400">3</span>
              <span class="text-xs text-gray-600 dark:text-gray-400">4</span>
              <span class="text-xs text-gray-600 dark:text-gray-400">5</span>
            </div>
            <div class="col-span-6 text-center mt-1">
              <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Impact</span>
            </div>
          </div>
        </div>

        <!-- Treatment Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Plan de Traitement</h2>
            <button
              v-if="!showTreatmentForm"
              class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
              @click="showTreatmentForm = true"
            >
              {{ currentRisk.treatment ? 'Modifier' : 'Ajouter' }}
            </button>
          </div>

          <div v-if="showTreatmentForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type de traitement
              </label>
              <select
                v-model="treatmentForm.treatment"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="accept">Accepter</option>
                <option value="reduce">Réduire</option>
                <option value="transfer">Transférer</option>
                <option value="avoid">Éviter</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Plan d'action
              </label>
              <textarea
                v-model="treatmentForm.action_plan"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Décrivez les actions à mettre en place..."
                rows="4"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Probabilité résiduelle
                </label>
                <input
                  v-model.number="treatmentForm.residual_probability"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  max="5"
                  min="1"
                  type="number"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Impact résiduel
                </label>
                <input
                  v-model.number="treatmentForm.residual_impact"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  max="5"
                  min="1"
                  type="number"
                >
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date de révision
              </label>
              <input
                v-model="treatmentForm.review_date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                type="date"
              >
            </div>

            <div class="flex gap-2">
              <button
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                :disabled="loading"
                @click="handleUpdateTreatment"
              >
                Enregistrer
              </button>
              <button
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                @click="showTreatmentForm = false"
              >
                Annuler
              </button>
            </div>
          </div>

          <div v-else-if="currentRisk.treatment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Type de traitement</p>
                <p class="text-gray-900 dark:text-white font-medium">{{ getTreatmentLabel(currentRisk.treatment) }}</p>
              </div>
              <div v-if="currentRisk.review_date">
                <p class="text-sm text-gray-600 dark:text-gray-400">Date de révision</p>
                <p class="text-gray-900 dark:text-white font-medium">{{ formatDate(currentRisk.review_date) }}</p>
              </div>
            </div>
            <div v-if="currentRisk.action_plan">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Plan d'action</p>
              <p class="text-gray-900 dark:text-white">{{ currentRisk.action_plan }}</p>
            </div>
          </div>

          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucun plan de traitement défini
          </div>
        </div>

        <!-- Current Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contrôles Existants</h2>
          <div v-if="currentRisk.current_controls" class="text-gray-900 dark:text-white">
            {{ currentRisk.current_controls }}
          </div>
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucun contrôle défini
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Status Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Statut</h3>
          <span :class="getStatusBadgeClass(currentRisk.status)">
            {{ getStatusLabel(currentRisk.status) }}
          </span>
        </div>

        <!-- Residual Risk -->
        <div v-if="currentRisk.residual_probability && currentRisk.residual_impact" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Risque Résiduel</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Probabilité</span>
              <span class="font-semibold text-gray-900 dark:text-white">{{ currentRisk.residual_probability }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Impact</span>
              <span class="font-semibold text-gray-900 dark:text-white">{{ currentRisk.residual_impact }}</span>
            </div>
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">Score</span>
                <span :class="getScoreBadgeClass(currentRisk.residual_score || 0)">
                  {{ currentRisk.residual_score }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Responsible -->
        <div v-if="currentRisk.responsible" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Responsable</h3>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
            </div>
            <div>
              <p class="text-gray-900 dark:text-white font-medium">{{ currentRisk.responsible.name }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ currentRisk.responsible.email }}</p>
            </div>
          </div>
        </div>

        <!-- Metadata -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">Informations</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Créé le</span>
              <span class="text-gray-900 dark:text-white">{{ formatDate(currentRisk.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Mis à jour</span>
              <span class="text-gray-900 dark:text-white">{{ formatDate(currentRisk.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Modal -->
    <div v-if="showStatusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Changer le statut</h3>
        <select
          v-model="newStatus"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white mb-4"
        >
          <option value="identified">Identifié</option>
          <option value="analyzed">Analysé</option>
          <option value="treated">Traité</option>
          <option value="monitored">Surveillé</option>
          <option value="closed">Clôturé</option>
        </select>
        <div class="flex gap-2">
          <button
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            @click="handleUpdateStatus"
          >
            Enregistrer
          </button>
          <button
            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
            @click="showStatusModal = false"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { RiskCategory, RiskStatus, RiskTreatment } from '@/api/services/risks.service'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { useRisks } from '@/modules/clienta/composables/useRisks'

  const route = useRoute()
  const { currentRisk, loading, fetchRisk, updateRisk, updateTreatment } = useRisks()

  const riskId = computed(() => Number((route.params as { id: string }).id))
  const showTreatmentForm = ref(false)
  const showStatusModal = ref(false)
  const newStatus = ref<RiskStatus>('identified')

  const treatmentForm = ref({
    treatment: 'reduce' as RiskTreatment,
    action_plan: '',
    residual_probability: 1,
    residual_impact: 1,
    review_date: '',
  })

  const canUpdateStatus = computed(() => {
    return currentRisk.value && currentRisk.value.status !== 'closed'
  })

  onMounted(async () => {
    await fetchRisk(riskId.value)
    if (currentRisk.value) {
      newStatus.value = currentRisk.value.status
      if (currentRisk.value.treatment) {
        treatmentForm.value = {
          treatment: currentRisk.value.treatment,
          action_plan: currentRisk.value.action_plan || '',
          residual_probability: currentRisk.value.residual_probability || 1,
          residual_impact: currentRisk.value.residual_impact || 1,
          review_date: currentRisk.value.review_date || '',
        }
      }
    }
  })

  async function handleUpdateTreatment () {
    try {
      await updateTreatment(riskId.value, treatmentForm.value)
      showTreatmentForm.value = false
    } catch {
      alert('Erreur lors de la mise à jour du traitement')
    }
  }

  async function handleUpdateStatus () {
    try {
      await updateRisk(riskId.value, { status: newStatus.value })
      showStatusModal.value = false
    } catch {
      alert('Erreur lors de la mise à jour du statut')
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getRiskMatrixColor (score: number) {
    if (score <= 5) return 'bg-green-200 dark:bg-green-800 text-green-900 dark:text-green-100'
    if (score <= 10) return 'bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100'
    if (score <= 15) return 'bg-orange-200 dark:bg-orange-800 text-orange-900 dark:text-orange-100'
    return 'bg-red-200 dark:bg-red-800 text-red-900 dark:text-red-100'
  }

  function getScoreBadgeClass (score: number) {
    if (score <= 5) return 'px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
    if (score <= 10) return 'px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
    if (score <= 15) return 'px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100'
    return 'px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
  }

  function getScoreBackgroundClass (score: number) {
    if (score <= 5) return 'bg-green-50 dark:bg-green-900 text-green-800 dark:text-green-100'
    if (score <= 10) return 'bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-100'
    if (score <= 15) return 'bg-orange-50 dark:bg-orange-900 text-orange-800 dark:text-orange-100'
    return 'bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-100'
  }

  function getScoreLevel (score: number) {
    if (score <= 5) return 'Faible'
    if (score <= 10) return 'Moyen'
    if (score <= 15) return 'Élevé'
    return 'Très Élevé'
  }

  function getCategoryBadgeClass (category: RiskCategory) {
    const classes = {
      qualite: 'inline-block px-2 py-1 rounded text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
      hygiene: 'inline-block px-2 py-1 rounded text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100',
      securite: 'inline-block px-2 py-1 rounded text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
      environnement: 'inline-block px-2 py-1 rounded text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    }
    return classes[category]
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

  function getStatusBadgeClass (status: RiskStatus) {
    const classes = {
      identified: 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
      analyzed: 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
      treated: 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
      monitored: 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100',
      closed: 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    }
    return classes[status]
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

  function getTreatmentLabel (treatment: RiskTreatment) {
    const labels = {
      accept: 'Accepter',
      reduce: 'Réduire',
      transfer: 'Transférer',
      avoid: 'Éviter',
    }
    return labels[treatment]
  }
</script>
