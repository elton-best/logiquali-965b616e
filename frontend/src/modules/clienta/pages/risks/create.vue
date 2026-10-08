<template>
  <div class="space-y-6">
    <!-- Header -->
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau Risque QHSE</h1>
        <p class="text-gray-600 dark:text-gray-400">Identifier un nouveau risque</p>
      </div>
    </div>

    <!-- Form -->
    <form class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6" @submit.prevent="handleSubmit">
      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Titre du risque <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.title"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Ex: Risque de contamination alimentaire"
          required
          type="text"
        >
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Description <span class="text-red-500">*</span>
        </label>
        <textarea
          v-model="form.description"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Décrivez le risque en détail..."
          required
          rows="4"
        />
      </div>

      <!-- Category and Process -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Catégorie QHSE <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.category"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            required
          >
            <option value="">Sélectionner une catégorie</option>
            <option value="qualite">Qualité</option>
            <option value="hygiene">Hygiène</option>
            <option value="securite">Sécurité</option>
            <option value="environnement">Environnement</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Processus
          </label>
          <input
            v-model="form.process"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            placeholder="Ex: Production, Stockage, Distribution"
            type="text"
          >
        </div>
      </div>

      <!-- Site and Identified By -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Site <span class="text-red-500">*</span>
          </label>
          <select
            v-model.number="form.site_id"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            required
          >
            <option value="">Sélectionner un site</option>
            <option v-for="site in sites" :key="site.id" :value="site.id">
              {{ site.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Identifié par <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.identified_by"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            placeholder="Nom de la personne"
            required
            type="text"
          >
        </div>
      </div>

      <!-- Identified Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Date d'identification <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.identified_date"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          required
          type="date"
        >
      </div>

      <!-- Probability and Impact -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Probabilité (1-5) <span class="text-red-500">*</span>
          </label>
          <div class="space-y-2">
            <input
              v-model.number="form.probability"
              class="w-full"
              max="5"
              min="1"
              step="1"
              type="range"
            >
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
              <span>Très faible</span>
              <span>Faible</span>
              <span>Moyenne</span>
              <span>Élevée</span>
              <span>Très élevée</span>
            </div>
            <div class="text-center">
              <span class="inline-block px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 rounded-lg font-semibold">
                {{ form.probability }}
              </span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Impact (1-) <span class="text-red-500">*</span>
          </label>
          <div class="space-y-2">
            <input
              v-model.number="form.impact"
              class="w-full"
              max="5"
              min="1"
              step="1"
              type="range"
            >
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
              <span>Très faible</span>
              <span>Faible</span>
              <span>Moyen</span>
              <span>Élevé</span>
              <span>Très élevé</span>
            </div>
            <div class="text-center">
              <span class="inline-block px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 rounded-lg font-semibold">
                {{ form.impact }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Risk Score Display -->
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Score de risque calculé</p>
        <div class="flex items-center justify-center gap-4">
          <span class="text-3xl font-bold px-6 py-3 rounded-lg" :class="getScoreBadgeClass(calculatedScore)">
            {{ calculatedScore }}
          </span>
          <div class="text-left">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ getScoreLevel(calculatedScore) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Probabilité × Impact
            </p>
          </div>
        </div>
      </div>

      <!-- Current Controls -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Contrôles existants
        </label>
        <textarea
          v-model="form.current_controls"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Décrivez les mesures de contrôle déjà en place..."
          rows="4"
        />
      </div>

      <!-- Actions -->
      <div class="flex gap-4">
        <button
          class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
          :disabled="loading"
          type="submit"
        >
          {{ loading ? 'Création en cours...' : 'Créer le risque' }}
        </button>
        <RouterLink
          class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-semibold text-center"
          to="/company/risks"
        >
          Annuler
        </RouterLink>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
        <p class="text-red-800 dark:text-red-200 text-sm">{{ error }}</p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import type { RiskCategory } from '@/api/services/risks.service'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useRisks } from '@/modules/clienta/composables/useRisks'
  import { useSites } from '@/modules/clienta/composables/useSites'

  const router = useRouter()
  const { createRisk, loading, error } = useRisks()
  const { sites, fetchSites } = useSites()

  const form = ref({
    title: '',
    description: '',
    category: '' as RiskCategory | '',
    process: '',
    site_id: 0,
    identified_by: '',
    identified_date: new Date().toISOString().split('T')[0],
    probability: 3,
    impact: 3,
    current_controls: '',
  })

  const calculatedScore = computed(() => form.value.probability * form.value.impact)

  onMounted(async () => {
    await fetchSites()
  })

  async function handleSubmit () {
    if (!form.value.category || !form.value.site_id) {
      alert('Veuillez remplir tous les champs obligatoires')
      return
    }

    try {
      await createRisk({
        title: form.value.title,
        description: form.value.description,
        category: form.value.category as RiskCategory,
        process: form.value.process || undefined,
        site_id: form.value.site_id,
        identified_by: form.value.identified_by,
        identified_date: form.value.identified_date || new Date().toISOString().split('T')[0] || '',
        probability: form.value.probability,
        impact: form.value.impact,
        current_controls: form.value.current_controls || undefined,
      })
      router.push('/company/risks')
    } catch (error_) {
      console.error('Error creating risk:', error_)
    }
  }

  function getScoreBadgeClass (score: number) {
    if (score <= 5) return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
    if (score <= 10) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
    if (score <= 15) return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100'
    return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
  }

  function getScoreLevel (score: number) {
    if (score <= 5) return 'Risque Faible'
    if (score <= 10) return 'Risque Moyen'
    if (score <= 15) return 'Risque Élevé'
    return 'Risque Très Élevé'
  }
</script>
