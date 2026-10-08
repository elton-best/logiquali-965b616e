<template>
  <DashboardLayout current-page="packs" title="Gestion des packs & offres">
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <p class="text-sm text-gray-500 mb-1">Total packs</p>
          <p class="text-3xl font-bold text-gray-900">{{ packs.length }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Abonnés actifs</p>
          <p class="text-3xl font-bold text-gray-900">
            {{ packs.reduce((sum, pack) => sum + pack.subscribers, 0) }}
          </p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Revenus mensuels</p>
          <p class="text-3xl font-bold text-gray-900">
            {{ getTotalRevenue().toLocaleString() }}€
          </p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Pack le plus populaire</p>
          <p class="text-lg font-bold text-gray-900">ISO 9001</p>
        </div>
      </div>
    </div>

    <div class="flex justify-between items-center mb-6">
      <p class="text-gray-600">Gérez vos offres d'abonnement</p>
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
        <Plus class="w-5 h-5" />
        Nouveau pack
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div
        v-for="pack in packs"
        :key="pack.id"
        :class="[
          'bg-white rounded-xl border-2 p-6 hover:shadow-lg transition-all',
          pack.popular ? 'border-blue-600' : 'border-gray-200'
        ]"
      >
        <div v-if="pack.popular" class="inline-block px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-semibold mb-4">
          ⭐ Plus populaire
        </div>

        <div class="flex items-start justify-between mb-4">
          <div class="flex items-start gap-3 flex-1">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <Package class="w-6 h-6 text-blue-600" />
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-lg text-gray-900 mb-1">{{ pack.name }}</h3>
              <p class="text-sm text-gray-600 mb-3">{{ pack.subtitle }}</p>
              <div class="flex flex-wrap gap-2 mb-3">
                <span v-for="(norm, index) in pack.norms" :key="index" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                  {{ norm }}
                </span>
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

        <div class="flex items-baseline gap-2 mb-4">
          <span class="text-3xl font-bold text-gray-900">{{ pack.price }}€</span>
          <span class="text-gray-600">/mois</span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
          <div>
            <p class="text-xs text-gray-500 mb-1">Abonnés</p>
            <p class="text-lg font-semibold text-gray-900">{{ pack.subscribers }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Revenus/mois</p>
            <p class="text-lg font-semibold text-gray-900">
              {{ (pack.price * pack.subscribers).toLocaleString() }}€
            </p>
          </div>
        </div>

        <div class="space-y-2 mb-4">
          <p class="text-sm font-medium text-gray-700">Fonctionnalités incluses:</p>
          <div class="max-h-48 overflow-y-auto">
            <div v-for="(feature, index) in pack.features" :key="index" class="flex items-start gap-2 py-1">
              <Check class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
              <span class="text-sm text-gray-700">{{ feature }}</span>
            </div>
          </div>
        </div>

        <div class="flex gap-2 pt-4 border-t border-gray-200">
          <button class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            Modifier
          </button>
          <button class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
            {{ pack.active ? 'Désactiver' : 'Activer' }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
  import { Check, Edit, Package, Plus, Trash2 } from 'lucide-vue-next'
  import DashboardLayout from '@/modules/shared/components/DashboardLayout.vue'

  const packs = [
    {
      id: '1',
      name: 'Pack ISO 9001',
      subtitle: 'Qualité & Management',
      price: 299,
      active: true,
      subscribers: 65,
      features: [
        'Gestion des processus qualité',
        'Gestion documentaire complète',
        'Audits internes illimités',
        'Gestion des non-conformités',
        'Tableau de bord KPI',
        'Support par email',
        '1 site inclus',
        'Formation en ligne',
      ],
      norms: ['ISO 9001:2015'],
    },
    {
      id: '2',
      name: 'Pack ISO 9001 + 14001',
      subtitle: 'Qualité & Environnement',
      price: 499,
      active: true,
      subscribers: 52,
      popular: true,
      features: [
        'Tout du Pack ISO 9001',
        'Gestion environnementale ISO 14001',
        'Aspects et impacts environnementaux',
        'Conformité réglementaire',
        'Veille réglementaire',
        'Support prioritaire',
        'Jusqu\'à 3 sites',
        'Accompagnement personnalisé',
      ],
      norms: ['ISO 9001:2015', 'ISO 14001:2015'],
    },
    {
      id: '3',
      name: 'Pack SMI Intégré',
      subtitle: 'Solution Complète',
      price: 799,
      active: true,
      subscribers: 25,
      features: [
        'ISO 9001 + ISO 14001 + ISO 45001',
        'Gestion de la santé et sécurité',
        'Gestion des risques avancée',
        'Tableaux de bord personnalisés',
        'API & Intégrations',
        'Support dédié 24/7',
        'Sites illimités',
        'Auditeur dédié',
      ],
      norms: ['ISO 9001:2015', 'ISO 14001:2015', 'ISO 45001:2018'],
    },
  ]

  function getTotalRevenue () {
    return packs.reduce((sum, pack) => sum + (pack.price * pack.subscribers), 0)
  }
</script>
