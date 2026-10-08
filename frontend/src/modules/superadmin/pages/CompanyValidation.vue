<template>
  <DashboardLayout current-page="validation" title="Validation des entreprises">
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <AlertCircle class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
        <div>
          <h4 class="font-medium text-blue-900 mb-1">Processus de validation KYC</h4>
          <p class="text-sm text-blue-700">
            Vérifiez l'authenticité des documents légaux avant d'activer le compte entreprise.
            Les entreprises seront notifiées par email de votre décision.
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex gap-2">
      <button
        v-for="status in statuses"
        :key="status"
        :class="[
          'px-4 py-2 rounded-lg transition-colors',
          filter === status
            ? 'bg-blue-600 text-white'
            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
        ]"
        @click="filter = status"
      >
        {{ status === 'all' ? 'Toutes' :
          status === 'pending' ? 'En attente' :
          status === 'validated' ? 'Validées' :
          'Rejetées' }}
        <span v-if="status !== 'all'" class="ml-2 font-semibold">
          ({{ companies.filter(c => c.status === status).length }})
        </span>
      </button>
    </div>

    <!-- Companies List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="space-y-4">
        <h3 class="font-semibold text-lg mb-4">Liste des demandes</h3>
        <button
          v-for="company in filteredCompanies"
          :key="company.id"
          :class="[
            'w-full text-left p-6 rounded-xl border-2 transition-all',
            selectedCompany?.id === company.id
              ? 'border-blue-600 bg-blue-50'
              : 'border-gray-200 bg-white hover:border-gray-300'
          ]"
          @click="selectedCompany = company"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <Building class="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h4 class="font-semibold text-gray-900">{{ company.name }}</h4>
                <p class="text-sm text-gray-600">{{ company.pack }}</p>
              </div>
            </div>
            <component :is="getStatusBadge(company.status)" />
          </div>
          <div class="text-sm text-gray-600 space-y-1">
            <p>{{ company.email }}</p>
            <p class="text-xs">Soumis le {{ company.submittedDate }}</p>
          </div>
        </button>
      </div>

      <!-- Company Details -->
      <div class="lg:sticky lg:top-24">
        <div v-if="selectedCompany" class="bg-white rounded-xl border border-gray-200 p-6">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h3 class="font-bold text-xl text-gray-900 mb-2">{{ selectedCompany.name }}</h3>
              <component :is="getStatusBadge(selectedCompany.status)" />
            </div>
          </div>

          <!-- Company Info -->
          <div class="mb-6 p-4 bg-gray-50 rounded-lg space-y-2 text-sm">
            <div class="grid grid-cols-3 gap-2">
              <span class="text-gray-500">SIRET:</span>
              <span class="col-span-2 font-medium text-gray-900">{{ selectedCompany.siret }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="text-gray-500">Email:</span>
              <span class="col-span-2 font-medium text-gray-900">{{ selectedCompany.email }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="text-gray-500">Téléphone:</span>
              <span class="col-span-2 font-medium text-gray-900">{{ selectedCompany.phone }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="text-gray-500">Adresse:</span>
              <span class="col-span-2 font-medium text-gray-900">{{ selectedCompany.address }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="text-gray-500">Pack:</span>
              <span class="col-span-2 font-medium text-gray-900">{{ selectedCompany.pack }}</span>
            </div>
          </div>

          <!-- Documents -->
          <div class="mb-6">
            <h4 class="font-semibold mb-4">Documents légaux</h4>
            <div class="space-y-3">
              <div v-for="[key, label] in docEntries" :key="key" class="p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <FileText class="w-5 h-5 text-gray-400" />
                    <span class="font-medium text-gray-900">{{ label }}</span>
                  </div>
                  <component :is="getStatusBadge((selectedCompany.documents as Record<string, any>)[key].status)" />
                </div>
                <div class="flex gap-2">
                  <button class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition-colors">
                    <Eye class="w-4 h-4" />
                    Voir
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition-colors">
                    <Download class="w-4 h-4" />
                    Télécharger
                  </button>
                </div>
                <div v-if="(selectedCompany.documents as Record<string, any>)[key].status === 'pending'" class="flex gap-2 mt-3">
                  <button
                    class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors"
                    @click="handleDocumentAction(key, 'validate')"
                  >
                    ✓ Valider
                  </button>
                  <button
                    class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors"
                    @click="handleDocumentAction(key, 'reject')"
                  >
                    ✗ Rejeter
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Final Decision -->
          <div v-if="selectedCompany.status === 'pending'" class="pt-6 border-t border-gray-200">
            <h4 class="font-semibold mb-4">Décision finale</h4>
            <div class="flex gap-3">
              <button
                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                @click="handleFinalDecision('validate')"
              >
                <CheckCircle class="w-5 h-5" />
                Valider l'entreprise
              </button>
              <button
                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                @click="handleFinalDecision('reject')"
              >
                <XCircle class="w-5 h-5" />
                Rejeter la demande
              </button>
            </div>
            <p class="text-xs text-gray-500 text-center mt-3">
              L'entreprise sera notifiée par email de votre décision
            </p>
          </div>
        </div>
        <div v-else class="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <Building class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p class="text-gray-500">Sélectionnez une entreprise pour voir les détails</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
  import {
    AlertCircle,
    Building,
    CheckCircle,
    Clock,
    Download,
    Eye,
    FileText,
    XCircle,
  } from 'lucide-vue-next'
  import { computed, h, ref } from 'vue'
  import DashboardLayout from '@/modules/shared/components/DashboardLayout.vue'

  interface Company {
    id: string
    name: string
    email: string
    siret: string
    phone: string
    address: string
    submittedDate: string
    status: 'pending' | 'validated' | 'rejected'
    pack: string
    documents: {
      rccm: { status: 'pending' | 'validated' | 'rejected', url?: string }
      ifu: { status: 'pending' | 'validated' | 'rejected', url?: string }
      cniRecto: { status: 'pending' | 'validated' | 'rejected', url?: string }
      cniVerso: { status: 'pending' | 'validated' | 'rejected', url?: string }
    }
  }

  const selectedCompany = ref<Company | null>(null)
  const filter = ref<'all' | 'pending' | 'validated' | 'rejected'>('pending')

  const statuses = ['all', 'pending', 'validated', 'rejected'] as const

  const companies: Company[] = [
    {
      id: '1',
      name: 'TechCorp SAS',
      email: 'admin@techcorp.com',
      siret: '123 456 789 00010',
      phone: '+33 1 23 45 67 89',
      address: '123 Rue de la Tech, 75001 Paris',
      submittedDate: '19/01/2026 14:30',
      status: 'pending',
      pack: 'ISO 9001',
      documents: {
        rccm: { status: 'pending' },
        ifu: { status: 'pending' },
        cniRecto: { status: 'pending' },
        cniVerso: { status: 'pending' },
      },
    },
    {
      id: '2',
      name: 'Industrie Plus',
      email: 'contact@industrieplus.com',
      siret: '987 654 321 00020',
      phone: '+33 2 34 56 78 90',
      address: '456 Avenue Industrielle, 69000 Lyon',
      submittedDate: '19/01/2026 10:15',
      status: 'pending',
      pack: 'SMI Intégré',
      documents: {
        rccm: { status: 'pending' },
        ifu: { status: 'pending' },
        cniRecto: { status: 'pending' },
        cniVerso: { status: 'pending' },
      },
    },
    {
      id: '3',
      name: 'EcoServices',
      email: 'info@ecoservices.fr',
      siret: '456 789 123 00030',
      phone: '+33 3 45 67 89 01',
      address: '789 Boulevard Vert, 33000 Bordeaux',
      submittedDate: '18/01/2026 16:45',
      status: 'pending',
      pack: 'ISO 9001+14001',
      documents: {
        rccm: { status: 'pending' },
        ifu: { status: 'pending' },
        cniRecto: { status: 'pending' },
        cniVerso: { status: 'pending' },
      },
    },
    {
      id: '4',
      name: 'Manufacturing Co',
      email: 'admin@manufacturing.fr',
      siret: '321 654 987 00040',
      phone: '+33 4 56 78 90 12',
      address: '321 Rue Fabrique, 13000 Marseille',
      submittedDate: '17/01/2026 11:20',
      status: 'validated',
      pack: 'ISO 9001',
      documents: {
        rccm: { status: 'validated' },
        ifu: { status: 'validated' },
        cniRecto: { status: 'validated' },
        cniVerso: { status: 'validated' },
      },
    },
  ]

  const filteredCompanies = computed(() =>
    companies.filter(c => filter.value === 'all' || c.status === filter.value),
  )

  function getStatusBadge (status: 'pending' | 'validated' | 'rejected') {
    const config = {
      pending: { style: 'bg-orange-100 text-orange-700', label: 'En attente', icon: Clock },
      validated: { style: 'bg-green-100 text-green-700', label: 'Validé', icon: CheckCircle },
      rejected: { style: 'bg-red-100 text-red-700', label: 'Rejeté', icon: XCircle },
    }
    const { style, label, icon: Icon } = config[status]
    return () => h('span', { class: `inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${style}` }, [
      h(Icon, { class: 'w-3 h-3' }),
      label,
    ])
  }

  function handleDocumentAction (docType: string, action: 'validate' | 'reject') {
    console.log(`${action} document ${docType} for company ${selectedCompany.value?.id}`)
  }

  const docEntries = computed(() => {
    if (!selectedCompany.value) return []
    const documentLabels: Record<string, string> = {
      rccm: 'Registre du Commerce et du Crédit Mobilier',
      ifu: 'Identifiant Fiscal Unique',
      cniRecto: 'CNI - Recto (Gérant)',
      cniVerso: 'CNI - Verso (Gérant)',
    }
    return Object.entries(documentLabels)
  })

  function handleFinalDecision (action: 'validate' | 'reject') {
    console.log(`${action} company ${selectedCompany.value?.id}`)
    selectedCompany.value = null
  }
</script>
