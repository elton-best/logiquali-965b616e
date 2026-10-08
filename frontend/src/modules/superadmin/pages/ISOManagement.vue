<template>
  <DashboardLayout current-page="iso" title="Gestion des normes ISO">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
      <p class="text-sm text-blue-700">
        Gérez les normes ISO disponibles sur la plateforme. Les normes actives peuvent être incluses dans les packs d'abonnement.
      </p>
    </div>

    <div class="flex justify-between items-center mb-6">
      <p class="text-gray-600">{{ norms.length }} normes enregistrées</p>
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
        <Plus class="w-5 h-5" />
        Ajouter une norme
      </button>
    </div>

    <div class="grid grid-cols-1 gap-6">
      <div v-for="norm in norms" :key="norm.id" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-start gap-4 flex-1">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <Shield class="w-6 h-6 text-blue-600" />
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="font-bold text-lg text-gray-900">{{ norm.code }}</h3>
                <span v-if="norm.active" class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium flex items-center gap-1">
                  <CheckCircle class="w-3 h-3" />
                  Active
                </span>
                <span v-else class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                  Inactive
                </span>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                  {{ norm.category }}
                </span>
              </div>
              <h4 class="font-semibold text-gray-900 mb-2">{{ norm.name }}</h4>
              <p class="text-sm text-gray-600 mb-4">{{ norm.description }}</p>
              <div class="flex items-center gap-6 text-sm">
                <div>
                  <span class="text-gray-500">Version: </span>
                  <span class="font-medium text-gray-900">{{ norm.version }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Entreprises: </span>
                  <span class="font-medium text-gray-900">{{ norm.companies }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 ml-4">
            <button class="p-2 text-gray-600 hover:text-blue-600 transition-colors rounded-lg hover:bg-blue-50">
              <Edit class="w-5 h-5" />
            </button>
            <button class="p-2 text-gray-600 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50">
              <Trash2 class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
  import { CheckCircle, Edit, Plus, Shield, Trash2 } from 'lucide-vue-next'
  import DashboardLayout from '@/modules/shared/components/DashboardLayout.vue'

  const norms = [
    {
      id: '1',
      code: 'ISO 9001:2015',
      name: 'Management de la qualité',
      description: 'Systèmes de management de la qualité - Exigences',
      category: 'Qualité',
      active: true,
      companies: 85,
      version: '2015',
    },
    {
      id: '2',
      code: 'ISO 14001:2015',
      name: 'Management environnemental',
      description: 'Systèmes de management environnemental - Exigences et lignes directrices',
      category: 'Environnement',
      active: true,
      companies: 52,
      version: '2015',
    },
    {
      id: '3',
      code: 'ISO 45001:2018',
      name: 'Santé et sécurité au travail',
      description: 'Systèmes de management de la santé et de la sécurité au travail',
      category: 'Sécurité',
      active: true,
      companies: 25,
      version: '2018',
    },
    {
      id: '4',
      code: 'ISO 27001:2013',
      name: 'Sécurité de l\'information',
      description: 'Systèmes de management de la sécurité de l\'information',
      category: 'IT',
      active: false,
      companies: 0,
      version: '2013',
    },
  ]
</script>
