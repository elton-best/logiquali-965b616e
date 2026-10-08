<template>
  <DashboardLayout current-page="company" title="Gestion de l'entreprise">
    <!-- Company Info -->
    <div class="bg-white rounded-xl border border-gray-200 p-8 mb-8">
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-start gap-4">
          <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center">
            <Building class="w-8 h-8 text-blue-600" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ company.name }}</h2>
            <p class="text-gray-600">{{ company.sector }}</p>
          </div>
        </div>
        <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium flex items-center gap-2">
          <Edit class="w-4 h-4" />
          Modifier
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
          <p class="text-sm text-gray-500 mb-1">SIRET</p>
          <p class="font-semibold text-gray-900">{{ company.siret }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Email</p>
          <p class="font-semibold text-gray-900">{{ company.email }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Téléphone</p>
          <p class="font-semibold text-gray-900">{{ company.phone }}</p>
        </div>
        <div class="md:col-span-2">
          <p class="text-sm text-gray-500 mb-1">Adresse</p>
          <p class="font-semibold text-gray-900">{{ company.address }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Effectif</p>
          <p class="font-semibold text-gray-900">{{ company.employees }} employés</p>
        </div>
      </div>
    </div>

    <!-- Sites -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-semibold text-lg">Sites</h3>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2">
          <Plus class="w-4 h-4" />
          Nouveau site
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div v-for="site in sites" :key="site.id" class="p-6 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors">
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <MapPin class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h4 class="font-semibold text-gray-900 mb-1">{{ site.name }}</h4>
                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                  {{ site.type }}
                </span>
              </div>
            </div>
            <button class="p-1 text-gray-400 hover:text-gray-600">
              <Edit class="w-4 h-4" />
            </button>
          </div>

          <p class="text-sm text-gray-600 mb-4">{{ site.address }}</p>

          <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Effectif</span>
              <span class="font-medium text-gray-900">{{ site.employees }} personnes</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Manager</span>
              <span class="font-medium text-gray-900">{{ site.manager }}</span>
            </div>
          </div>

          <div>
            <p class="text-xs text-gray-500 mb-2">Certifications</p>
            <div class="flex flex-wrap gap-2">
              <span v-for="(cert, index) in site.certifications" :key="index" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                {{ cert }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-semibold text-lg">Utilisateurs</h3>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2">
          <Plus class="w-4 h-4" />
          Inviter un utilisateur
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-4 py-3 text-sm font-semibold text-gray-900">Nom</th>
              <th class="text-left px-4 py-3 text-sm font-semibold text-gray-900">Rôle</th>
              <th class="text-left px-4 py-3 text-sm font-semibold text-gray-900">Email</th>
              <th class="text-left px-4 py-3 text-sm font-semibold text-gray-900">Site</th>
              <th class="text-left px-4 py-3 text-sm font-semibold text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(user, index) in users" :key="index" class="hover:bg-gray-50">
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 text-xs font-semibold">
                      {{ user.name.split(' ').map(n => n[0]).join('') }}
                    </span>
                  </div>
                  <span class="font-medium text-gray-900">{{ user.name }}</span>
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-gray-600">{{ user.role }}</td>
              <td class="px-4 py-4 text-sm text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-4 text-sm text-gray-600">{{ user.site }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
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
  </DashboardLayout>
</template>

<script setup lang="ts">
  import { Building, Edit, MapPin, Plus, Trash2 } from 'lucide-vue-next'
  import DashboardLayout from '@/modules/shared/components/DashboardLayout.vue'

  const company = {
    name: 'TechCorp SAS',
    siret: '123 456 789 00010',
    email: 'contact@techcorp.com',
    phone: '+33 1 23 45 67 89',
    address: '123 Rue de la Tech, 75001 Paris',
    sector: 'Technologies de l\'information',
    employees: 150,
  }

  const sites = [
    {
      id: '1',
      name: 'Siège social - Paris',
      address: '123 Rue de la Tech, 75001 Paris',
      type: 'Siège',
      employees: 80,
      certifications: ['ISO 9001', 'ISO 14001'],
      manager: 'Marie Dubois',
    },
    {
      id: '2',
      name: 'Site de production - Lyon',
      address: '456 Avenue Industrielle, 69000 Lyon',
      type: 'Production',
      employees: 45,
      certifications: ['ISO 9001'],
      manager: 'Jean Martin',
    },
    {
      id: '3',
      name: 'Centre logistique - Marseille',
      address: '789 Boulevard Logistique, 13000 Marseille',
      type: 'Logistique',
      employees: 25,
      certifications: ['ISO 9001'],
      manager: 'Sophie Laurent',
    },
  ]

  const users = [
    { name: 'Marie Dubois', role: 'Administrateur', email: 'marie.dubois@techcorp.com', site: 'Paris' },
    { name: 'Jean Martin', role: 'Manager Qualité', email: 'jean.martin@techcorp.com', site: 'Lyon' },
    { name: 'Sophie Laurent', role: 'Responsable site', email: 'sophie.laurent@techcorp.com', site: 'Marseille' },
    { name: 'Pierre Blanc', role: 'Auditeur interne', email: 'pierre.blanc@techcorp.com', site: 'Paris' },
  ]
</script>
