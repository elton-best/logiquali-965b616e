<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestion des audits</h1>
        <p class="text-gray-500 mt-1">Planifiez et suivez vos audits</p>
      </div>
      <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <Plus class="w-5 h-5" />
        Planifier un audit
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
            placeholder="Rechercher un audit..."
            type="text"
          >
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="status in statuses"
            :key="status"
            :class="`px-4 py-2 rounded-lg transition-colors whitespace-nowrap ${
              selectedStatus === status
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`"
            @click="selectedStatus = status"
          >
            {{ getStatusFilterLabel(status) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Audit List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="audit in filteredAudits" :key="audit.id" class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div>
            <span class="font-mono text-sm text-gray-500">{{ audit.code }}</span>
            <h3 class="font-semibold text-lg mt-1">{{ audit.title }}</h3>
          </div>
          <div :class="getStatusBadgeClass(audit.status).class">
            <component :is="getStatusBadgeClass(audit.status).icon" class="w-3 h-3" />
            {{ getStatusBadgeClass(audit.status).label }}
          </div>
        </div>

        <div class="space-y-2 mb-4">
          <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 w-24">Type :</span>
            <span class="font-medium">{{ audit.type }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 w-24">Périmètre :</span>
            <span class="font-medium">{{ audit.scope }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 w-24">Auditeur :</span>
            <span class="font-medium">{{ audit.auditor }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 w-24">Date :</span>
            <span class="font-medium">{{ audit.date }}</span>
          </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center gap-4">
            <div>
              <p class="text-xs text-gray-500">Constats</p>
              <p class="text-lg font-semibold">{{ audit.findings }}</p>
            </div>
            <div v-if="audit.score !== undefined">
              <p class="text-xs text-gray-500">Score</p>
              <p
                :class="`text-lg font-semibold ${
                  audit.score >= 90 ? 'text-green-600' :
                  audit.score >= 75 ? 'text-orange-600' :
                  'text-red-600'
                }`"
              >{{ audit.score }}%</p>
            </div>
          </div>
          <button class="text-blue-600 hover:text-blue-700 font-medium text-sm">
            Voir détails →
          </button>
        </div>
      </div>
    </div>

    <div v-if="filteredAudits.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <p class="text-gray-500">Aucun audit trouvé</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, Calendar, CheckCircle2, Clock, Plus, Search } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Audit {
    id: string
    code: string
    title: string
    type: string
    scope: string
    auditor: string
    date: string
    status: 'planned' | 'in-progress' | 'completed' | 'delayed'
    findings: number
    score?: number
  }

  const searchTerm = ref('')
  const selectedStatus = ref('all')

  const audits = ref<Audit[]>([
    {
      id: '1',
      code: 'AUD-2026-001',
      title: 'Audit ISO 9001:2015',
      type: 'Certification',
      scope: 'Système qualité complet',
      auditor: 'Bureau Veritas',
      date: '15/02/2026',
      status: 'planned',
      findings: 0,
    },
    {
      id: '2',
      code: 'AUD-2026-002',
      title: 'Audit interne processus achats',
      type: 'Interne',
      scope: 'Processus P-001',
      auditor: 'Marie Dubois',
      date: '10/01/2026',
      status: 'completed',
      findings: 3,
      score: 85,
    },
    {
      id: '3',
      code: 'AUD-2025-045',
      title: 'Audit sécurité atelier',
      type: 'Sécurité',
      scope: 'Zone production',
      auditor: 'Luc Bernard',
      date: '18/01/2026',
      status: 'in-progress',
      findings: 5,
    },
    {
      id: '4',
      code: 'AUD-2025-042',
      title: 'Audit environnemental',
      type: 'Environnement',
      scope: 'Gestion des déchets',
      auditor: 'Sophie Laurent',
      date: '20/12/2025',
      status: 'completed',
      findings: 2,
      score: 92,
    },
    {
      id: '5',
      code: 'AUD-2026-003',
      title: 'Audit fournisseur',
      type: 'Fournisseur',
      scope: 'Fournisseur XYZ',
      auditor: 'Jean Martin',
      date: '25/02/2026',
      status: 'planned',
      findings: 0,
    },
    {
      id: '6',
      code: 'AUD-2025-040',
      title: 'Audit processus maintenance',
      type: 'Interne',
      scope: 'Processus P-015',
      auditor: 'Pierre Blanc',
      date: '15/12/2025',
      status: 'delayed',
      findings: 0,
    },
  ])

  const statuses = ['all', 'planned', 'in-progress', 'completed', 'delayed']

  const filteredAudits = computed(() => {
    return audits.value.filter(audit => {
      const matchesSearch = audit.title.toLowerCase().includes(searchTerm.value.toLowerCase())
        || audit.code.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesStatus = selectedStatus.value === 'all' || audit.status === selectedStatus.value
      return matchesSearch && matchesStatus
    })
  })

  const stats = computed(() => [
    {
      label: 'Audits planifiés',
      value: audits.value.filter(a => a.status === 'planned').length,
      color: 'blue',
      icon: Calendar,
    },
    {
      label: 'En cours',
      value: audits.value.filter(a => a.status === 'in-progress').length,
      color: 'orange',
      icon: Clock,
    },
    {
      label: 'Terminés',
      value: audits.value.filter(a => a.status === 'completed').length,
      color: 'green',
      icon: CheckCircle2,
    },
    {
      label: 'Retardés',
      value: audits.value.filter(a => a.status === 'delayed').length,
      color: 'red',
      icon: AlertCircle,
    },
  ])

  function getColorClasses (color: string) {
    const colors: Record<string, string> = {
      blue: 'bg-blue-50 text-blue-600',
      orange: 'bg-orange-50 text-orange-600',
      green: 'bg-green-50 text-green-600',
      red: 'bg-red-50 text-red-600',
    }
    return colors[color] || colors.blue
  }

  function getStatusBadgeClass (status: string): { class: string, label: string, icon: any } {
    const defaultConfig = {
      class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-blue-100 text-blue-700',
      label: 'Planifié',
      icon: Calendar,
    }
    const config: Record<string, { class: string, label: string, icon: any }> = {
      'planned': defaultConfig,
      'in-progress': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-orange-100 text-orange-700',
        label: 'En cours',
        icon: Clock,
      },
      'completed': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-green-100 text-green-700',
        label: 'Terminé',
        icon: CheckCircle2,
      },
      'delayed': {
        class: 'px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit bg-red-100 text-red-700',
        label: 'Retardé',
        icon: AlertCircle,
      },
    }
    return config[status] ?? defaultConfig
  }

  function getStatusFilterLabel (status: string) {
    const labels: Record<string, string> = {
      'all': 'Tous',
      'planned': 'Planifiés',
      'in-progress': 'En cours',
      'completed': 'Terminés',
      'delayed': 'Retardés',
    }
    return labels[status] || status
  }
</script>
