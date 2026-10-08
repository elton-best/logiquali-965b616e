<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestion des risques</h1>
        <p class="text-gray-500 mt-1">Identifiez et maîtrisez vos risques</p>
      </div>
      <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <Plus class="w-5 h-5" />
        Nouveau risque
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div v-for="(stat, index) in stats" :key="index" class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3 mb-2">
          <div :class="`w-10 h-10 rounded-lg ${getColorClasses(stat.color)} flex items-center justify-center`">
            <component :is="stat.icon" class="w-5 h-5" />
          </div>
        </div>
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
            placeholder="Rechercher un risque..."
            type="text"
          >
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="criticality in criticalities"
            :key="criticality"
            :class="`px-4 py-2 rounded-lg transition-colors whitespace-nowrap ${
              selectedCriticality === criticality
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`"
            @click="selectedCriticality = criticality"
          >
            {{ getCriticalityFilterLabel(criticality) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Risk Matrix -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
      <h3 class="font-semibold mb-4">Matrice des risques</h3>
      <div class="grid grid-cols-6 gap-2">
        <!-- Header row -->
        <div class="col-span-1" />
        <div v-for="prob in [1, 2, 3, 4, 5]" :key="`header-${prob}`" class="text-center text-xs font-medium text-gray-600">{{ prob }}</div>

        <!-- Rows -->
        <template v-for="impact in [5, 4, 3, 2, 1]" :key="`row-${impact}`">
          <div class="flex items-center justify-center text-xs font-medium text-gray-600">
            {{ impact }}
          </div>
          <div
            v-for="probability in [1, 2, 3, 4, 5]"
            :key="`${probability}-${impact}`"
            :class="`${getMatrixCellColor(probability * impact)} rounded p-2 text-center min-h-[60px] flex items-center justify-center`"
          >
            <span v-if="getRiskCountForCell(probability, impact) > 0" class="font-semibold text-gray-700">
              {{ getRiskCountForCell(probability, impact) }}
            </span>
          </div>
        </template>

        <!-- Footer labels -->
        <div class="col-span-1" />
        <div class="col-span-5 text-center text-xs font-medium text-gray-600 mt-2">
          Probabilité →
        </div>
      </div>
      <div class="relative mt-4">
        <div class="text-xs font-medium text-gray-600 text-center">
          Impact ↑
        </div>
      </div>
    </div>

    <!-- Risk List -->
    <div class="space-y-4">
      <div v-for="risk in filteredRisks" :key="risk.id" class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="font-mono text-sm text-gray-500">{{ risk.code }}</span>
              <span :class="getCriticalityBadgeClass(risk.criticality)">
                {{ getCriticalityLabel(risk.criticality) }}
              </span>
              <span :class="getStatusBadgeClass(risk.status)">
                {{ getStatusLabel(risk.status) }}
              </span>
            </div>
            <h3 class="font-semibold text-lg">{{ risk.title }}</h3>
            <p class="text-gray-600 text-sm mt-1">{{ risk.description }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 pt-4 border-t border-gray-200">
          <div>
            <p class="text-xs text-gray-500 mb-1">Catégorie</p>
            <p class="text-sm font-medium">{{ risk.category }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Probabilité</p>
            <p class="text-sm font-medium">{{ risk.probability }}/5</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Impact</p>
            <p class="text-sm font-medium">{{ risk.impact }}/5</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Responsable</p>
            <p class="text-sm font-medium">{{ risk.owner }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Prochaine revue</p>
            <p class="text-sm font-medium">{{ risk.reviewDate }}</p>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200">
          <p class="text-xs text-gray-500 mb-1">Mesures de mitigation</p>
          <p class="text-sm text-gray-700">{{ risk.mitigation }}</p>
        </div>
      </div>
    </div>

    <div v-if="filteredRisks.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <p class="text-gray-500">Aucun risque trouvé</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertTriangle, Plus, Search, Shield, TrendingUp } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  type Criticality = 'low' | 'medium' | 'high' | 'critical'

  interface Risk {
    id: string
    code: string
    title: string
    description: string
    category: string
    probability: 1 | 2 | 3 | 4 | 5
    impact: 1 | 2 | 3 | 4 | 5
    criticality: Criticality
    status: 'identified' | 'analyzed' | 'treated' | 'monitored'
    owner: string
    mitigation: string
    reviewDate: string
  }

  const searchTerm = ref('')
  const selectedCriticality = ref('all')

  function calculateCriticality (probability: number, impact: number): Criticality {
    const score = probability * impact
    if (score <= 4) return 'low'
    if (score <= 9) return 'medium'
    if (score <= 16) return 'high'
    return 'critical'
  }

  const risks = ref<Risk[]>([
    {
      id: '1',
      code: 'R-001',
      title: 'Panne équipement critique',
      description: 'Arrêt de la ligne de production principal',
      category: 'Opérationnel',
      probability: 3,
      impact: 5,
      criticality: calculateCriticality(3, 5),
      status: 'treated',
      owner: 'Luc Bernard',
      mitigation: 'Maintenance préventive renforcée + équipement de secours',
      reviewDate: '01/03/2026',
    },
    {
      id: '2',
      code: 'R-002',
      title: 'Perte de certification ISO 9001',
      description: 'Non-conformités majeures lors de l\'audit',
      category: 'Qualité',
      probability: 2,
      impact: 5,
      criticality: calculateCriticality(2, 5),
      status: 'monitored',
      owner: 'Marie Dubois',
      mitigation: 'Audits internes mensuels + formation équipe',
      reviewDate: '15/02/2026',
    },
    {
      id: '3',
      code: 'R-003',
      title: 'Rupture d\'approvisionnement',
      description: 'Défaillance fournisseur unique matière première',
      category: 'Supply Chain',
      probability: 4,
      impact: 4,
      criticality: calculateCriticality(4, 4),
      status: 'treated',
      owner: 'Jean Martin',
      mitigation: 'Diversification fournisseurs + stock de sécurité',
      reviewDate: '20/02/2026',
    },
    {
      id: '4',
      code: 'R-004',
      title: 'Cyberattaque système informatique',
      description: 'Compromission données et systèmes',
      category: 'Sécurité IT',
      probability: 3,
      impact: 4,
      criticality: calculateCriticality(3, 4),
      status: 'analyzed',
      owner: 'Pierre Blanc',
      mitigation: 'Plan de sécurité IT en cours de déploiement',
      reviewDate: '10/02/2026',
    },
    {
      id: '5',
      code: 'R-005',
      title: 'Non-conformité environnementale',
      description: 'Dépassement seuils réglementaires émissions',
      category: 'Environnement',
      probability: 2,
      impact: 4,
      criticality: calculateCriticality(2, 4),
      status: 'treated',
      owner: 'Sophie Laurent',
      mitigation: 'Système de filtration amélioré + monitoring continu',
      reviewDate: '25/02/2026',
    },
    {
      id: '6',
      code: 'R-006',
      title: 'Départ personnel clé',
      description: 'Perte de compétences critiques',
      category: 'RH',
      probability: 3,
      impact: 3,
      criticality: calculateCriticality(3, 3),
      status: 'identified',
      owner: 'Pierre Blanc',
      mitigation: 'Plan de succession à définir',
      reviewDate: '05/03/2026',
    },
    {
      id: '7',
      code: 'R-007',
      title: 'Variation qualité matière première',
      description: 'Impact sur qualité produit fini',
      category: 'Qualité',
      probability: 4,
      impact: 3,
      criticality: calculateCriticality(4, 3),
      status: 'monitored',
      owner: 'Sophie Laurent',
      mitigation: 'Contrôle réception renforcé + qualification fournisseurs',
      reviewDate: '18/02/2026',
    },
  ])

  const criticalities = ['all', 'low', 'medium', 'high', 'critical']

  const filteredRisks = computed(() => {
    return risks.value.filter(risk => {
      const matchesSearch = risk.title.toLowerCase().includes(searchTerm.value.toLowerCase())
        || risk.code.toLowerCase().includes(searchTerm.value.toLowerCase())
        || risk.description.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesCriticality = selectedCriticality.value === 'all' || risk.criticality === selectedCriticality.value
      return matchesSearch && matchesCriticality
    })
  })

  const stats = computed(() => [
    {
      label: 'Risques critiques',
      value: risks.value.filter(r => r.criticality === 'critical').length,
      color: 'red',
      icon: AlertTriangle,
    },
    {
      label: 'Risques élevés',
      value: risks.value.filter(r => r.criticality === 'high').length,
      color: 'orange',
      icon: TrendingUp,
    },
    {
      label: 'En traitement',
      value: risks.value.filter(r => r.status === 'treated' || r.status === 'analyzed').length,
      color: 'blue',
      icon: Shield,
    },
    {
      label: 'Total risques',
      value: risks.value.length,
      color: 'gray',
      icon: Shield,
    },
  ])

  function getColorClasses (color: string) {
    const colors: Record<string, string> = {
      red: 'bg-red-50 text-red-600',
      orange: 'bg-orange-50 text-orange-600',
      blue: 'bg-blue-50 text-blue-600',
      gray: 'bg-gray-50 text-gray-600',
    }
    return colors[color] || colors.gray
  }

  function getCriticalityBadgeClass (criticality: string) {
    const config: Record<string, string> = {
      low: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700',
      medium: 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700',
      high: 'px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700',
      critical: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700',
    }
    return config[criticality] || config.low
  }

  function getCriticalityLabel (criticality: string) {
    const labels: Record<string, string> = {
      low: 'Faible',
      medium: 'Moyen',
      high: 'Élevé',
      critical: 'Critique',
    }
    return labels[criticality] || criticality
  }

  function getCriticalityFilterLabel (criticality: string) {
    const labels: Record<string, string> = {
      all: 'Tous',
      low: 'Faibles',
      medium: 'Moyens',
      high: 'Élevés',
      critical: 'Critiques',
    }
    return labels[criticality] || criticality
  }

  function getStatusBadgeClass (status: string) {
    const config: Record<string, string> = {
      identified: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
      analyzed: 'px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
      treated: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700',
      monitored: 'px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700',
    }
    return config[status] || config.identified
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      identified: 'Identifié',
      analyzed: 'Analysé',
      treated: 'Traité',
      monitored: 'Surveillé',
    }
    return labels[status] || status
  }

  function getMatrixCellColor (score: number) {
    if (score <= 4) return 'bg-green-100'
    if (score <= 9) return 'bg-yellow-100'
    if (score <= 16) return 'bg-orange-100'
    return 'bg-red-100'
  }

  function getRiskCountForCell (probability: number, impact: number) {
    return risks.value.filter(r => r.probability === probability && r.impact === impact).length
  }
</script>
