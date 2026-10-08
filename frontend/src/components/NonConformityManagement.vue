<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestion des non-conformités</h1>
        <p class="text-gray-500 mt-1">Identifiez et résolvez les non-conformités</p>
      </div>
      <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <Plus class="w-5 h-5" />
        Déclarer une NC
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
            placeholder="Rechercher une non-conformité..."
            type="text"
          >
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="severity in severities"
            :key="severity"
            :class="`px-4 py-2 rounded-lg transition-colors whitespace-nowrap ${
              selectedSeverity === severity
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`"
            @click="selectedSeverity = severity"
          >
            {{ getSeverityFilterLabel(severity) }}
          </button>
        </div>
      </div>
    </div>

    <!-- NC List -->
    <div class="space-y-4">
      <div v-for="nc in filteredNCs" :key="nc.id" class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="font-mono text-sm text-gray-500">{{ nc.code }}</span>
              <span :class="getSeverityBadgeClass(nc.severity)">
                {{ getSeverityLabel(nc.severity) }}
              </span>
              <div :class="getStatusBadgeClass(nc.status).class">
                <component :is="getStatusBadgeClass(nc.status).icon" class="w-3 h-3" />
                {{ getStatusBadgeClass(nc.status).label }}
              </div>
            </div>
            <h3 class="font-semibold text-lg">{{ nc.title }}</h3>
            <p class="text-gray-600 text-sm mt-1">{{ nc.description }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
          <div>
            <p class="text-xs text-gray-500 mb-1">Source</p>
            <p class="text-sm font-medium">{{ nc.source }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Responsable</p>
            <p class="text-sm font-medium">{{ nc.responsible }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Date de création</p>
            <p class="text-sm font-medium">{{ nc.createdDate }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Date limite</p>
            <p
              :class="`text-sm font-medium ${
                nc.status === 'open' ? 'text-red-600' : ''
              }`"
            >{{ nc.dueDate }}</p>
          </div>
        </div>

        <div v-if="nc.resolvedDate" class="mt-3 pt-3 border-t border-gray-200">
          <p class="text-xs text-gray-500">
            Résolu le <span class="font-medium text-green-600">{{ nc.resolvedDate }}</span>
          </p>
        </div>
      </div>
    </div>

    <div v-if="filteredNCs.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <p class="text-gray-500">Aucune non-conformité trouvée</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertTriangle, CheckCircle, Clock, Plus, Search, XCircle } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface NonConformity {
    id: string
    code: string
    title: string
    description: string
    severity: 'minor' | 'major' | 'critical'
    status: 'open' | 'in-progress' | 'resolved' | 'closed'
    source: string
    responsible: string
    createdDate: string
    dueDate: string
    resolvedDate?: string
  }

  const searchTerm = ref('')
  const selectedSeverity = ref('all')

  const nonConformities = ref<NonConformity[]>([
    {
      id: '1',
      code: 'NC-2026-001',
      title: 'Défaut d\'étiquetage produit',
      description: 'Produits expédiés sans étiquette de traçabilité conforme',
      severity: 'major',
      status: 'in-progress',
      source: 'Audit interne',
      responsible: 'Jean Martin',
      createdDate: '10/01/2026',
      dueDate: '24/01/2026',
    },
    {
      id: '2',
      code: 'NC-2026-002',
      title: 'Température de stockage non conforme',
      description: 'Zone de stockage hors plage de température spécifiée',
      severity: 'critical',
      status: 'open',
      source: 'Contrôle qualité',
      responsible: 'Sophie Laurent',
      createdDate: '15/01/2026',
      dueDate: '17/01/2026',
    },
    {
      id: '3',
      code: 'NC-2025-089',
      title: 'Documentation formation manquante',
      description: 'Enregistrements de formation non archivés',
      severity: 'minor',
      status: 'resolved',
      source: 'Audit ISO 9001',
      responsible: 'Pierre Blanc',
      createdDate: '20/12/2025',
      dueDate: '10/01/2026',
      resolvedDate: '08/01/2026',
    },
    {
      id: '4',
      code: 'NC-2025-085',
      title: 'Calibration équipement échue',
      description: 'Équipement de mesure utilisé hors période de calibration',
      severity: 'major',
      status: 'closed',
      source: 'Contrôle métrologie',
      responsible: 'Luc Bernard',
      createdDate: '05/12/2025',
      dueDate: '19/12/2025',
      resolvedDate: '18/12/2025',
    },
    {
      id: '5',
      code: 'NC-2026-003',
      title: 'Plan de nettoyage non respecté',
      description: 'Zone de production non nettoyée selon planning',
      severity: 'minor',
      status: 'in-progress',
      source: 'Inspection hygiène',
      responsible: 'Marie Dubois',
      createdDate: '12/01/2026',
      dueDate: '19/01/2026',
    },
    {
      id: '6',
      code: 'NC-2025-092',
      title: 'Procédure non suivie',
      description: 'Processus d\'achat non conforme à PRO-001',
      severity: 'major',
      status: 'open',
      source: 'Réclamation client',
      responsible: 'Marie Dubois',
      createdDate: '18/01/2026',
      dueDate: '25/01/2026',
    },
  ])

  const severities = ['all', 'minor', 'major', 'critical']

  const filteredNCs = computed(() => {
    return nonConformities.value.filter(nc => {
      const matchesSearch = nc.title.toLowerCase().includes(searchTerm.value.toLowerCase())
        || nc.code.toLowerCase().includes(searchTerm.value.toLowerCase())
        || nc.description.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesSeverity = selectedSeverity.value === 'all' || nc.severity === selectedSeverity.value
      return matchesSearch && matchesSeverity
    })
  })

  const stats = computed(() => [
    {
      label: 'NC ouvertes',
      value: nonConformities.value.filter(nc => nc.status === 'open').length,
      color: 'red',
      icon: AlertTriangle,
    },
    {
      label: 'En cours de traitement',
      value: nonConformities.value.filter(nc => nc.status === 'in-progress').length,
      color: 'blue',
      icon: Clock,
    },
    {
      label: 'NC critiques',
      value: nonConformities.value.filter(nc => nc.severity === 'critical').length,
      color: 'orange',
      icon: AlertTriangle,
    },
    {
      label: 'Résolues ce mois',
      value: nonConformities.value.filter(nc => nc.status === 'resolved' || nc.status === 'closed').length,
      color: 'green',
      icon: CheckCircle,
    },
  ])

  function getColorClasses (color: string) {
    const colors: Record<string, string> = {
      red: 'bg-red-50 text-red-600',
      blue: 'bg-blue-50 text-blue-600',
      orange: 'bg-orange-50 text-orange-600',
      green: 'bg-green-50 text-green-600',
    }
    return colors[color] || colors.blue
  }

  function getSeverityBadgeClass (severity: string) {
    const config: Record<string, string> = {
      minor: 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700',
      major: 'px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700',
      critical: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700',
    }
    return config[severity] || config.minor
  }

  function getSeverityLabel (severity: string) {
    const labels: Record<string, string> = {
      minor: 'Mineure',
      major: 'Majeure',
      critical: 'Critique',
    }
    return labels[severity] || severity
  }

  function getSeverityFilterLabel (severity: string) {
    const labels: Record<string, string> = {
      all: 'Toutes',
      minor: 'Mineures',
      major: 'Majeures',
      critical: 'Critiques',
    }
    return labels[severity] || severity
  }

  function getStatusBadgeClass (status: string): { class: string, label: string, icon: any } {
    const defaultConfig = {
      class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-red-100 text-red-700',
      label: 'Ouverte',
      icon: AlertTriangle,
    }
    const config: Record<string, { class: string, label: string, icon: any }> = {
      'open': defaultConfig,
      'in-progress': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-blue-100 text-blue-700',
        label: 'En cours',
        icon: Clock,
      },
      'resolved': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-green-100 text-green-700',
        label: 'Résolue',
        icon: CheckCircle,
      },
      'closed': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-gray-100 text-gray-700',
        label: 'Clôturée',
        icon: XCircle,
      },
    }
    return config[status] ?? defaultConfig
  }
</script>
