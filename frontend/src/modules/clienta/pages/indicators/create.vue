<template>
  <div class="space-y-6">
    <!-- Header -->
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvel Indicateur QHSE</h1>
        <p class="text-gray-600 dark:text-gray-400">Créer un nouvel indicateur de performance</p>
      </div>
    </div>

    <!-- Form -->
    <form class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6" @submit.prevent="handleSubmit">
      <!-- Basic Information -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de base</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Code <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.code"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Ex: IND-Q-001"
              required
              type="text"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Nom <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.name"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Nom de l'indicateur"
              required
              type="text"
            >
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="formData.description"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Description détaillée de l'indicateur"
              required
              rows="3"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Catégorie <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.category"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              required
            >
              <option value="">Sélectionner...</option>
              <option value="qualite">Qualité</option>
              <option value="hygiene">Hygiène</option>
              <option value="securite">Sécurité</option>
              <option value="environnement">Environnement</option>
              <option value="performance">Performance</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Type <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.type"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              required
            >
              <option value="">Sélectionner...</option>
              <option value="quantitative">Quantitatif</option>
              <option value="qualitative">Qualitatif</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Unité
            </label>
            <input
              v-model="formData.unit"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Ex: %, nb, kg, etc."
              type="text"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Fréquence <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.frequency"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              required
            >
              <option value="">Sélectionner...</option>
              <option value="daily">Quotidien</option>
              <option value="weekly">Hebdomadaire</option>
              <option value="monthly">Mensuel</option>
              <option value="quarterly">Trimestriel</option>
              <option value="yearly">Annuel</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Formule de calcul
            </label>
            <textarea
              v-model="formData.calculation_formula"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
              placeholder="Ex: (nombre_conformités / total_contrôles) * 100"
              rows="2"
            />
          </div>
        </div>
      </div>

      <!-- Targets and Thresholds -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Objectifs et Seuils</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Valeur cible <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="formData.target_value"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="100"
              required
              step="any"
              type="number"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Seuil minimum
            </label>
            <input
              v-model.number="formData.threshold_min"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="80"
              step="any"
              type="number"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Seuil maximum
            </label>
            <input
              v-model.number="formData.threshold_max"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="120"
              step="any"
              type="number"
            >
          </div>
        </div>
      </div>

      <!-- Additional Information -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations complémentaires</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Processus lié
            </label>
            <input
              v-model.number="formData.process_id"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="ID du processus"
              type="number"
            >
            <p class="text-xs text-gray-500 mt-1">ID du processus associé</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Responsable
            </label>
            <input
              v-model.number="formData.responsible_id"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="ID du responsable"
              type="number"
            >
            <p class="text-xs text-gray-500 mt-1">ID de l'utilisateur responsable</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Source de données
            </label>
            <input
              v-model="formData.data_source"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Ex: Système ERP, Saisie manuelle, etc."
              type="text"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Statut <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.status"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              required
            >
              <option value="active">Actif</option>
              <option value="inactive">Inactif</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button
          class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          type="button"
          @click="$router.back()"
        >
          Annuler
        </button>
        <button
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          :disabled="loading"
          type="submit"
        >
          {{ loading ? 'Création...' : 'Créer l\'indicateur' }}
        </button>
      </div>
    </form>

    <!-- Error Message -->
    <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <div>
          <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Erreur</h3>
          <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { CreateIndicatorDTO } from '@/api/services/indicators.service'
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useIndicators } from '@/modules/clienta/composables/useIndicators'

  const router = useRouter()
  const { createIndicator, loading, error } = useIndicators()

  const formData = ref<CreateIndicatorDTO>({
    code: '',
    name: '',
    description: '',
    category: '' as any,
    type: '' as any,
    unit: '',
    calculation_formula: '',
    frequency: '' as any,
    target_value: 0,
    threshold_min: undefined,
    threshold_max: undefined,
    process_id: undefined,
    responsible_id: undefined,
    data_source: '',
    status: 'active',
  })

  async function handleSubmit () {
    try {
      await createIndicator(formData.value)
      router.push('/company/indicators')
    } catch (error_) {
      console.error('Failed to create indicator:', error_)
    }
  }
</script>
