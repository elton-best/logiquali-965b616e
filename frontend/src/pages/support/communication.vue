<template>
  <div class="communication-page p-6">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Communication et sensibilisation</h1>
      <p class="text-gray-600 mt-2">Plans de communication et sensibilisation</p>
    </div>

    <!-- Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Communications</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-green-100 text-green-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Réalisées</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.realisees }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Planifiées</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.planifiees }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-purple-100 text-purple-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Taux Réalisation</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.tauxRealisation }}%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center mb-6">
      <div class="flex space-x-4">
        <button
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center"
          @click="showForm = true"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Nouvelle Communication
        </button>

        <button
          class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
          @click="exportData"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Exporter
        </button>
      </div>

      <div class="flex space-x-4">
        <select v-model="filters.type" class="border rounded-lg px-3 py-2">
          <option value="">Tous les types</option>
          <option value="communication">Communication</option>
          <option value="sensibilisation">Sensibilisation</option>
        </select>

        <select v-model="filters.status" class="border rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option value="planifiee">Planifiée</option>
          <option value="realisee">Réalisée</option>
          <option value="annulee">Annulée</option>
        </select>
      </div>
    </div>

    <!-- Communications List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <div class="grid gap-6">
          <div
            v-for="communication in filteredCommunications"
            :key="communication.id"
            class="border rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ communication.designation }}</h3>
                  <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getStatusClass(communication.status)">
                    {{ getStatusLabel(communication.status) }}
                  </span>
                  <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getTypeClass(communication.type)">
                    {{ getTypeLabel(communication.type) }}
                  </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                  <div>
                    <span class="font-medium">Responsable:</span> {{ communication.responsable }}
                  </div>
                  <div>
                    <span class="font-medium">Période:</span>
                    {{ formatDate(communication.dateDebut) }} - {{ formatDate(communication.dateFin) }}
                  </div>
                  <div>
                    <span class="font-medium">Fréquence:</span> {{ getFrequencyLabel(communication.frequency) }}
                  </div>
                </div>

                <div class="mt-2 text-sm text-gray-600">
                  <span class="font-medium">Cibles:</span>
                  <span v-for="(cible, index) in communication.cibles" :key="index" class="inline-block bg-gray-100 px-2 py-1 rounded mr-1 mb-1">
                    {{ cible }}
                  </span>
                </div>
              </div>

              <div class="flex space-x-2">
                <button
                  class="text-blue-600 hover:text-blue-800"
                  @click="editCommunication(communication)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>

                <button
                  class="text-red-600 hover:text-red-800"
                  @click="deleteCommunication(communication.id)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">{{ editingCommunication ? 'Modifier' : 'Nouvelle' }} Communication</h2>
          <button class="text-gray-500 hover:text-gray-700" @click="closeForm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="saveCommunication">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select v-model="form.type" class="w-full border rounded-lg px-3 py-2" required>
                <option value="communication">Communication</option>
                <option value="sensibilisation">Sensibilisation</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Fréquence</label>
              <select v-model="form.frequency" class="w-full border rounded-lg px-3 py-2" required>
                <option value="ponctuelle">Ponctuelle</option>
                <option value="annuelle">Annuelle</option>
                <option value="semestrielle">Semestrielle</option>
                <option value="trimestrielle">Trimestrielle</option>
                <option value="mensuelle">Mensuelle</option>
                <option value="biennale">Biennale</option>
                <option value="sur_demande">Sur demande</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Désignation</label>
            <input v-model="form.designation" class="w-full border rounded-lg px-3 py-2" required type="text">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
            <input v-model="form.responsable" class="w-full border rounded-lg px-3 py-2" required type="text">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
              <input v-model="form.dateDebut" class="w-full border rounded-lg px-3 py-2" required type="date">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
              <input v-model="form.dateFin" class="w-full border rounded-lg px-3 py-2" required type="date">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Coût (€)</label>
            <input v-model="form.cout" class="w-full border rounded-lg px-3 py-2" step="0.01" type="number">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observations</label>
            <textarea v-model="form.observations" class="w-full border rounded-lg px-3 py-2" rows="3" />
          </div>

          <div class="flex justify-end space-x-4">
            <button class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50" type="button" @click="closeForm">
              Annuler
            </button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" type="submit">
              {{ editingCommunication ? 'Modifier' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Communication, CommunicationFormData } from '@/modules/clienta/types/communication.types'
  import { computed, onMounted, ref } from 'vue'
  import { useCommunications } from '@/modules/clienta/composables/useCommunications'

  const {
    communications,
    stats,
    fetchCommunications,
    createCommunication,
    updateCommunication,
    deleteCommunication: deleteCommunicationApi,
  } = useCommunications()

  const showForm = ref(false)
  const editingCommunication = ref<Communication | null>(null)
  const filters = ref({
    type: '',
    status: '',
  })

  const form = ref<CommunicationFormData>({
    type: 'communication',
    designation: '',
    responsable: '',
    dateDebut: '',
    dateFin: '',
    periodMode: 'custom',
    frequency: 'ponctuelle',
    cout: undefined,
    observations: '',
    cibles: [],
    moyens: [],
    chronogramme: Array.from({ length: 12 }).fill(false) as boolean[],
  })

  const filteredCommunications = computed(() => {
    return communications.value.filter(comm => {
      if (filters.value.type && comm.type !== filters.value.type) return false
      if (filters.value.status && comm.status !== filters.value.status) return false
      return true
    })
  })

  function getStatusClass (status: string) {
    const classes = {
      planifiee: 'bg-yellow-100 text-yellow-800',
      realisee: 'bg-green-100 text-green-800',
      annulee: 'bg-red-100 text-red-800',
      replanifiee: 'bg-blue-100 text-blue-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
  }

  function getStatusLabel (status: string) {
    const labels = {
      planifiee: 'Planifiée',
      realisee: 'Réalisée',
      annulee: 'Annulée',
      replanifiee: 'Replanifiée',
    }
    return labels[status] || status
  }

  function getTypeClass (type: string) {
    return type === 'communication' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
  }

  function getTypeLabel (type: string) {
    return type === 'communication' ? 'Communication' : 'Sensibilisation'
  }

  function getFrequencyLabel (frequency: string) {
    const labels = {
      ponctuelle: 'Ponctuelle',
      annuelle: 'Annuelle',
      semestrielle: 'Semestrielle',
      trimestrielle: 'Trimestrielle',
      mensuelle: 'Mensuelle',
      biennale: 'Biennale',
      sur_demande: 'Sur demande',
    }
    return labels[frequency] || frequency
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function editCommunication (communication: Communication) {
    editingCommunication.value = communication
    form.value = {
      type: communication.type,
      designation: communication.designation,
      responsable: communication.responsable,
      dateDebut: communication.dateDebut,
      dateFin: communication.dateFin,
      periodMode: communication.periodMode || 'custom',
      frequency: communication.frequency,
      cout: communication.cout,
      observations: communication.observations,
      cibles: communication.cibles || [],
      moyens: communication.moyens || [],
      chronogramme: communication.chronogramme || (Array.from({ length: 12 }).fill(false) as boolean[]),
    }
    showForm.value = true
  }

  function closeForm () {
    showForm.value = false
    editingCommunication.value = null
    form.value = {
      type: 'communication',
      designation: '',
      responsable: '',
      dateDebut: '',
      dateFin: '',
      periodMode: 'custom',
      frequency: 'ponctuelle',
      cout: undefined,
      observations: '',
      cibles: [],
      moyens: [],
      chronogramme: Array.from({ length: 12 }).fill(false) as boolean[],
    }
  }

  async function saveCommunication () {
    try {
      await (editingCommunication.value ? updateCommunication(editingCommunication.value.id, form.value) : createCommunication(form.value))
      closeForm()
      await fetchCommunications()
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error)
    }
  }

  async function deleteCommunication (id: string) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette communication ?')) {
      try {
        await deleteCommunicationApi(id)
        await fetchCommunications()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
      }
    }
  }

  async function exportData () {
    try {
      await fetchCommunications(filters.value)
    } catch (error) {
      console.error('Erreur lors de l\'export:', error)
    }
  }

  onMounted(async () => {
    await fetchCommunications()
  })
</script>
