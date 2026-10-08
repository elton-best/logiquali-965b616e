<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestion documentaire</h1>
        <p class="text-gray-500 mt-1">Centralisez et gérez vos documents SMI</p>
      </div>
      <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <Plus class="w-5 h-5" />
        Nouveau document
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div v-for="(stat, index) in stats" :key="index" class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-500">{{ stat.label }}</p>
        <p class="text-2xl font-bold mt-1">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Rechercher un document..."
            type="text"
          >
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="type in documentTypes"
            :key="type"
            :class="`px-4 py-2 rounded-lg transition-colors whitespace-nowrap ${
              selectedType === type
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`"
            @click="selectedType = type"
          >
            {{ type === 'all' ? 'Tous' : type }}
          </button>
        </div>
      </div>
    </div>

    <!-- Document List -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Code</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Titre</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Type</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Version</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Statut</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Propriétaire</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Valide jusqu'au</th>
              <th class="text-left px-6 py-3 text-sm font-semibold text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="doc in filteredDocuments" :key="doc.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <FileText class="w-4 h-4 text-gray-400" />
                  <span class="font-mono text-sm text-gray-900">{{ doc.code }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="font-medium text-gray-900">{{ doc.title }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ doc.type }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm font-mono text-gray-600">{{ doc.version }}</span>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusBadgeClass(doc.status)">
                  {{ getStatusLabel(doc.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ doc.owner }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600">{{ doc.validUntil }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="p-1 text-gray-600 hover:text-blue-600 transition-colors" title="Voir">
                    <Eye class="w-4 h-4" />
                  </button>
                  <button class="p-1 text-gray-600 hover:text-blue-600 transition-colors" title="Télécharger">
                    <Download class="w-4 h-4" />
                  </button>
                  <button class="p-1 text-gray-600 hover:text-blue-600 transition-colors" title="Modifier">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="p-1 text-gray-600 hover:text-red-600 transition-colors" title="Supprimer">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="filteredDocuments.length === 0" class="text-center py-12">
      <p class="text-gray-500">Aucun document trouvé</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Download, Edit, Eye, FileText, Plus, Search, Trash2 } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Document {
    id: string
    code: string
    title: string
    type: string
    version: string
    status: 'valid' | 'draft' | 'obsolete' | 'review'
    owner: string
    createdDate: string
    validUntil: string
  }

  const searchTerm = ref('')
  const selectedType = ref('all')

  const documents = ref<Document[]>([
    {
      id: '1',
      code: 'PRO-001',
      title: 'Procédure d\'achat',
      type: 'Procédure',
      version: 'v2.1',
      status: 'valid',
      owner: 'Marie Dubois',
      createdDate: '10/01/2025',
      validUntil: '10/01/2027',
    },
    {
      id: '2',
      code: 'INS-012',
      title: 'Instruction de fabrication',
      type: 'Instruction',
      version: 'v1.5',
      status: 'valid',
      owner: 'Jean Martin',
      createdDate: '15/12/2024',
      validUntil: '15/12/2026',
    },
    {
      id: '3',
      code: 'FOR-008',
      title: 'Formulaire de contrôle qualité',
      type: 'Formulaire',
      version: 'v3.0',
      status: 'valid',
      owner: 'Sophie Laurent',
      createdDate: '20/11/2025',
      validUntil: '20/11/2027',
    },
    {
      id: '4',
      code: 'POL-002',
      title: 'Politique environnementale',
      type: 'Politique',
      version: 'v1.0',
      status: 'review',
      owner: 'Pierre Blanc',
      createdDate: '05/01/2026',
      validUntil: '05/01/2027',
    },
    {
      id: '5',
      code: 'PRO-015',
      title: 'Procédure de maintenance',
      type: 'Procédure',
      version: 'v2.0',
      status: 'valid',
      owner: 'Luc Bernard',
      createdDate: '12/10/2025',
      validUntil: '12/10/2027',
    },
    {
      id: '6',
      code: 'MAN-001',
      title: 'Manuel qualité',
      type: 'Manuel',
      version: 'v4.2',
      status: 'draft',
      owner: 'Marie Dubois',
      createdDate: '18/01/2026',
      validUntil: '-',
    },
    {
      id: '7',
      code: 'INS-025',
      title: 'Instruction sécurité machines',
      type: 'Instruction',
      version: 'v1.2',
      status: 'valid',
      owner: 'Jean Martin',
      createdDate: '03/09/2025',
      validUntil: '03/09/2027',
    },
    {
      id: '8',
      code: 'FOR-015',
      title: 'Fiche de non-conformité',
      type: 'Formulaire',
      version: 'v2.1',
      status: 'valid',
      owner: 'Sophie Laurent',
      createdDate: '28/08/2025',
      validUntil: '28/08/2027',
    },
  ])

  const documentTypes = ['all', 'Procédure', 'Instruction', 'Formulaire', 'Politique', 'Manuel']

  const filteredDocuments = computed(() => {
    return documents.value.filter(doc => {
      const matchesSearch = doc.title.toLowerCase().includes(searchTerm.value.toLowerCase())
        || doc.code.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesType = selectedType.value === 'all' || doc.type === selectedType.value
      return matchesSearch && matchesType
    })
  })

  const stats = computed(() => [
    { label: 'Total documents', value: documents.value.length, color: 'blue' },
    { label: 'Valides', value: documents.value.filter(d => d.status === 'valid').length, color: 'green' },
    { label: 'En révision', value: documents.value.filter(d => d.status === 'review').length, color: 'orange' },
    { label: 'Brouillons', value: documents.value.filter(d => d.status === 'draft').length, color: 'gray' },
  ])

  function getStatusBadgeClass (status: string) {
    const styles: Record<string, string> = {
      valid: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700',
      draft: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
      obsolete: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700',
      review: 'px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700',
    }
    return styles[status] || styles.valid
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      valid: 'Valide',
      draft: 'Brouillon',
      obsolete: 'Obsolète',
      review: 'En révision',
    }
    return labels[status] || status
  }
</script>
