<template>
  <div class="maintenance-alertes bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Alertes de maintenance
      </h3>
      <router-link class="text-sm text-blue-600 hover:text-blue-800" to="/support/ressources">
        Voir tout
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-4 text-gray-500">
      Chargement...
    </div>

    <div v-else-if="alertes.length === 0" class="text-center py-4 text-gray-500">
      Aucune alerte de maintenance
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="alerte in alertes"
        :key="alerte.id"
        :class="[
          'border-l-4 rounded p-3',
          getAlerteBorderClass(alerte.niveau_alerte)
        ]"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="getAlerteClass(alerte.niveau_alerte)">
                {{ getAlerteLabel(alerte.niveau_alerte) }}
              </span>
              <span class="text-sm font-medium">{{ alerte.equipement?.nom_commun }}</span>
            </div>
            <p class="text-xs text-gray-600">{{ alerte.equipement?.code_complet }}</p>
            <p class="text-xs text-gray-500 mt-1">
              {{ getTypeLabel(alerte.type) }} - {{ formatDate(alerte.date_prevue) }}
            </p>
          </div>
          <router-link
            class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap"
            to="/support/ressources"
          >
            Gérer →
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'

  const store = useSupportStore()
  const loading = ref(false)
  const alertes = ref<any[]>([])

  async function loadAlertes () {
    loading.value = true
    try {
      await store.fetchAlertes()
      alertes.value = store.alertes
    } catch (error) {
      console.error('Erreur lors du chargement des alertes:', error)
    } finally {
      loading.value = false
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getAlerteBorderClass (niveau: string) {
    const classes = {
      depassee: 'border-red-600',
      aujourd_hui: 'border-orange-600',
      veille: 'border-yellow-600',
      trois_jours: 'border-yellow-500',
      sept_jours: 'border-blue-500',
    }
    return classes[niveau as keyof typeof classes] || 'border-gray-300'
  }

  function getAlerteClass (niveau: string) {
    const classes = {
      depassee: 'bg-red-600 text-white',
      aujourd_hui: 'bg-orange-600 text-white',
      veille: 'bg-yellow-600 text-white',
      trois_jours: 'bg-yellow-500 text-white',
      sept_jours: 'bg-blue-500 text-white',
    }
    return classes[niveau as keyof typeof classes] || ''
  }

  function getAlerteLabel (niveau: string) {
    const labels = {
      depassee: 'DÉPASSÉE',
      aujourd_hui: 'AUJOURD\'HUI',
      veille: 'DEMAIN',
      trois_jours: '3 JOURS',
      sept_jours: '7 JOURS',
    }
    return labels[niveau as keyof typeof labels] || niveau
  }

  function getTypeLabel (type: string) {
    const labels = {
      preventive: 'Préventive',
      corrective: 'Corrective',
      etalonnage: 'Étalonnage',
    }
    return labels[type as keyof typeof labels] || type
  }

  onMounted(() => {
    loadAlertes()
    // Refresh every 5 minutes
    setInterval(loadAlertes, 5 * 60 * 1000)
  })
</script>
