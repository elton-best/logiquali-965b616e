<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestion des processus</h1>
        <p class="text-gray-500 mt-1">Pilotez vos processus métier</p>
      </div>
      <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <Plus class="w-5 h-5" />
        Nouveau processus
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Rechercher un processus..."
            type="text"
          >
        </div>
        <div class="flex gap-2">
          <button
            v-for="category in categories"
            :key="category"
            :class="`px-4 py-2 rounded-lg transition-colors ${
              selectedCategory === category
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`"
            @click="selectedCategory = category"
          >
            {{ category === 'all' ? 'Tous' : category }}
          </button>
        </div>
      </div>
    </div>

    <!-- Process List -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Code</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Nom du processus</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Pilote</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Catégorie</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Statut</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">KPI</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Prochaine revue</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="process in filteredProcesses" :key="process.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <span class="font-mono text-sm text-gray-900">{{ process.code }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="font-medium text-gray-900">{{ process.name }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ process.owner }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ process.category }}</span>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusBadgeClass(process.status)">
                  {{ getStatusLabel(process.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span v-if="process.kpi > 0" :class="`font-semibold ${getKPIColor(process.kpi)}`">
                  {{ process.kpi }}%
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ process.nextReview }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="p-1 text-gray-600 hover:text-blue-600 transition-colors">
                    <Eye class="w-4 h-4" />
                  </button>
                  <button class="p-1 text-gray-600 hover:text-blue-600 transition-colors">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="p-1 text-gray-600 hover:text-red-600 transition-colors">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="filteredProcesses.length === 0" class="text-center py-12">
      <p class="text-gray-500">Aucun processus trouvé</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Edit, Eye, Plus, Search, Trash2 } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Process {
    id: string
    code: string
    name: string
    owner: string
    category: string
    status: 'active' | 'draft' | 'archived'
    lastReview: string
    nextReview: string
    kpi: number
  }

  const searchTerm = ref('')
  const selectedCategory = ref('all')

  const processes = ref<Process[]>([
    {
      id: '1',
      code: 'P-001',
      name: 'Gestion des achats',
      owner: 'Marie Dubois',
      category: 'Support',
      status: 'active',
      lastReview: '15/12/2025',
      nextReview: '15/06/2026',
      kpi: 92,
    },
    {
      id: '2',
      code: 'P-002',
      name: 'Production et fabrication',
      owner: 'Jean Martin',
      category: 'Réalisation',
      status: 'active',
      lastReview: '10/12/2025',
      nextReview: '10/06/2026',
      kpi: 88,
    },
    {
      id: '3',
      code: 'P-003',
      name: 'Contrôle qualité',
      owner: 'Sophie Laurent',
      category: 'Support',
      status: 'active',
      lastReview: '20/12/2025',
      nextReview: '20/06/2026',
      kpi: 95,
    },
    {
      id: '4',
      code: 'P-004',
      name: 'Gestion des ressources humaines',
      owner: 'Pierre Blanc',
      category: 'Management',
      status: 'active',
      lastReview: '05/01/2026',
      nextReview: '05/07/2026',
      kpi: 87,
    },
    {
      id: '5',
      code: 'P-005',
      name: 'Maintenance préventive',
      owner: 'Luc Bernard',
      category: 'Support',
      status: 'active',
      lastReview: '12/01/2026',
      nextReview: '12/07/2026',
      kpi: 90,
    },
    {
      id: '6',
      code: 'P-006',
      name: 'Amélioration continue',
      owner: 'Marie Dubois',
      category: 'Management',
      status: 'draft',
      lastReview: '-',
      nextReview: '01/03/2026',
      kpi: 0,
    },
  ])

  const categories = ['all', 'Management', 'Réalisation', 'Support']

  const filteredProcesses = computed(() => {
    return processes.value.filter(process => {
      const matchesSearch = process.name.toLowerCase().includes(searchTerm.value.toLowerCase())
        || process.code.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesCategory = selectedCategory.value === 'all' || process.category === selectedCategory.value
      return matchesSearch && matchesCategory
    })
  })

  function getStatusBadgeClass (status: string) {
    const styles: Record<string, string> = {
      active: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700',
      draft: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
      archived: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700',
    }
    return styles[status] || styles.active
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      active: 'Actif',
      draft: 'Brouillon',
      archived: 'Archivé',
    }
    return labels[status] || status
  }

  function getKPIColor (kpi: number) {
    if (kpi >= 90) return 'text-green-600'
    if (kpi >= 75) return 'text-orange-600'
    return 'text-red-600'
  }
</script>
