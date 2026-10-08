<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white p-4">
    <div class="max-w-4xl mx-auto py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
          <Shield class="w-8 h-8 text-blue-600" />
          <span class="font-bold text-xl">Best Experts-Group</span>
        </div>
        <button
          class="text-sm text-gray-600 hover:text-gray-900"
          @click="logout"
        >
          Se déconnecter
        </button>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
        <!-- Status Badge -->
        <div class="flex items-center justify-center mb-6">
          <div class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-6 py-3 rounded-full">
            <Clock class="w-5 h-5" />
            <span class="font-semibold">Compte en attente de validation</span>
          </div>
        </div>

        <!-- Welcome Message -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Merci {{ user?.name }} !
          </h1>
          <p class="text-xl text-gray-600">
            Votre demande d'inscription pour <strong>{{ enterpriseName }}</strong> est en cours de traitement
          </p>
        </div>

        <!-- Info Alert -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
          <div class="flex items-start gap-3">
            <AlertCircle class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 class="font-semibold text-blue-900 mb-2">Que se passe-t-il maintenant ?</h3>
              <ul class="text-sm text-blue-700 space-y-2">
                <li class="flex items-start gap-2">
                  <span class="text-blue-600 mt-0.5">•</span>
                  <span>Notre équipe examine vos documents légaux (RCCM, IFU, CNI)</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="text-blue-600 mt-0.5">•</span>
                  <span>La validation prend généralement entre 24 et 48 heures ouvrées</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="text-blue-600 mt-0.5">•</span>
                  <span>Vous recevrez un email à <strong>{{ user?.email }}</strong> dès que votre compte sera validé</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="text-blue-600 mt-0.5">•</span>
                  <span>Si des documents sont rejetés, nous vous indiquerons les corrections à apporter</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="mb-8">
          <h3 class="font-semibold text-lg mb-6">Suivi de votre demande</h3>
          <div class="space-y-6">
            <div v-for="(step, index) in timeline" :key="index" class="flex gap-4">
              <div class="flex flex-col items-center">
                <component :is="getStatusIcon(step.status)" />
                <div
                  v-if="index < timeline.length - 1"
                  :class="`w-0.5 h-12 mt-2 ${step.status === 'completed' ? 'bg-green-600' : 'bg-gray-300'}`"
                />
              </div>
              <div class="flex-1 pb-8">
                <h4
                  :class="`font-semibold mb-1 ${
                    step.status === 'completed' ? 'text-gray-900' :
                    step.status === 'in-progress' ? 'text-blue-600' :
                    'text-gray-400'
                  }`"
                >
                  {{ step.title }}
                </h4>
                <p class="text-sm text-gray-600 mb-1">{{ step.description }}</p>
                <p class="text-xs text-gray-500">{{ step.date }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents Status -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
          <h3 class="font-semibold text-lg mb-4">Documents soumis</h3>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="(doc, index) in documents"
              :key="index"
              class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200"
            >
              <span class="text-sm font-medium text-gray-700">{{ doc.name }}</span>
              <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
                En attente
              </span>
            </div>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-6 border border-blue-200">
          <div class="flex items-start gap-3">
            <Mail class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Besoin d'aide ?</h3>
              <p class="text-sm text-gray-600 mb-3">
                Si vous avez des questions concernant votre validation, notre équipe est là pour vous aider.
              </p>
              <div class="space-y-1 text-sm">
                <p class="text-gray-700">
                  <span class="font-medium">Email :</span> support@bestexperts.com
                </p>
                <p class="text-gray-700">
                  <span class="font-medium">Téléphone :</span> +33 1 23 45 67 89
                </p>
                <p class="text-gray-700">
                  <span class="font-medium">Horaires :</span> Lundi - Vendredi, 9h - 18h
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Access Restriction Notice -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <p class="text-sm text-yellow-800 text-center">
            <strong>Note :</strong> L'accès complet à la plateforme sera disponible après validation de votre compte.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, Clock, Mail, Shield } from 'lucide-vue-next'
  import { computed, h } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuth } from '@/modules/shared/composables/useAuth'

  const router = useRouter()
  const { user, clearAuth } = useAuth()
  const enterpriseName = computed(() => user?.enterprise?.name || 'votre entreprise')

  const timeline = [
    {
      status: 'completed',
      title: 'Inscription complétée',
      description: 'Votre compte a été créé avec succès',
      date: 'Aujourd\'hui, 10:30',
    },
    {
      status: 'completed',
      title: 'Documents soumis',
      description: 'Tous les documents requis ont été téléversés',
      date: 'Aujourd\'hui, 10:35',
    },
    {
      status: 'in-progress',
      title: 'Validation en cours',
      description: 'Notre équipe examine vos documents',
      date: 'En cours...',
    },
    {
      status: 'pending',
      title: 'Activation du compte',
      description: 'Votre compte sera activé après validation',
      date: 'En attente',
    },
  ]

  const documents = [
    { name: 'RCCM', status: 'pending' },
    { name: 'IFU', status: 'pending' },
    { name: 'CNI (Recto)', status: 'pending' },
    { name: 'CNI (Verso)', status: 'pending' },
  ]

  function getStatusIcon (status: string) {
    switch (status) {
      case 'completed': {
        return h(CheckCircle, { class: 'w-6 h-6 text-green-600' })
      }
      case 'in-progress': {
        return h(Clock, { class: 'w-6 h-6 text-blue-600 animate-pulse' })
      }
      case 'pending': {
        return h('div', { class: 'w-6 h-6 border-2 border-gray-300 rounded-full' })
      }
      default: {
        return null
      }
    }
  }

  function logout () {
    clearAuth()
    router.push('/auth/login')
  }
</script>
