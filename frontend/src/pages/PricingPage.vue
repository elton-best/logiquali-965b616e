<template>
  <div class="min-h-screen bg-white">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full bg-white border-b border-gray-200 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <button class="flex items-center gap-2" @click="navigateTo('landing')">
            <Shield class="w-8 h-8 text-blue-600" />
            <span class="font-bold text-xl text-gray-900">Best Experts-Group</span>
          </button>
          <div class="flex items-center gap-4">
            <button class="text-gray-700 hover:text-blue-600" @click="navigateTo('login')">
              Connexion
            </button>
            <button
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              @click="navigateTo('signup')"
            >
              Commencer
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-16 px-4 bg-gradient-to-br from-blue-50 to-white">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-6">
          Tarifs transparents pour votre SMI
        </h1>
        <p class="text-xl text-gray-600 mb-8">
          Choisissez le pack adapté à vos besoins de certification ISO. Sans frais cachés.
        </p>
        <div class="flex items-center justify-center gap-8 text-sm text-gray-600">
          <div class="flex items-center gap-2">
            <Check class="w-5 h-5 text-green-500" />
            <span>Sans engagement</span>
          </div>
          <div class="flex items-center gap-2">
            <Check class="w-5 h-5 text-green-500" />
            <span>Changement de pack flexible</span>
          </div>
          <div class="flex items-center gap-2">
            <Check class="w-5 h-5 text-green-500" />
            <span>Support inclus</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="max-w-7xl mx-auto px-4 py-16">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div v-for="i in 3" :key="i" class="bg-gray-100 rounded-2xl h-96 animate-pulse" />
      </div>
    </div>

    <!-- Pricing Cards -->
    <section v-else class="py-16 px-4 -mt-8">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(offer, index) in offers"
            :key="offer.id"
            :class="[
              'bg-white rounded-2xl border-2 overflow-hidden relative',
              index === 1
                ? 'border-blue-500 shadow-2xl scale-105'
                : 'border-gray-200'
            ]"
          >
            <div
              v-if="index === 1"
              class="bg-blue-600 text-white text-center py-2 text-sm font-semibold"
            >
              ⭐ Plus populaire
            </div>
            <div class="p-8">
              <div
                v-if="offer.norms && offer.norms.length > 0"
                class="inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium mb-4"
              >
                {{ offer.norms.map(n => n.code).join(' + ') }}
              </div>
              <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ offer.name }}</h3>
              <p class="text-gray-600 mb-6 min-h-[48px]">{{ offer.description || 'Pack complet pour votre SMI' }}</p>

              <div class="mb-6">
                <div class="flex items-baseline gap-2">
                  <span class="text-5xl font-bold text-gray-900">{{ formatPrice(offer.price) }}</span>
                  <span class="text-gray-600">FCFA / {{ offer.duration_months }} mois</span>
                </div>
                <p class="text-sm text-gray-500 mt-2">Facturation selon durée</p>
              </div>

              <button
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors font-medium mb-6"
                @click="navigateTo('signup')"
              >
                Commencer maintenant
              </button>

              <div v-if="offer.norms && offer.norms.length > 0" class="space-y-3">
                <p class="text-sm font-semibold text-gray-900 mb-3">Normes incluses:</p>
                <div v-for="norm in offer.norms" :key="norm.id" class="flex items-start gap-3">
                  <Check class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                  <span class="text-sm text-gray-700">{{ norm.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Add-ons -->
    <section class="py-16 px-4 bg-gray-50">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Options complémentaires</h2>
          <p class="text-xl text-gray-600">Personnalisez votre abonnement selon vos besoins</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="(addon, index) in addons" :key="index" class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-lg text-gray-900 mb-2">{{ addon.name }}</h3>
            <div class="flex items-baseline gap-2 mb-3">
              <span class="text-3xl font-bold text-gray-900">{{ addon.price }}€</span>
              <span class="text-gray-600">/ {{ addon.period }}</span>
            </div>
            <p class="text-sm text-gray-600">{{ addon.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Comparison Table -->
    <section class="py-16 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Comparatif détaillé</h2>
          <p class="text-xl text-gray-600">Trouvez le pack qui correspond à vos besoins</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-6 py-4 text-sm font-semibold text-gray-900">Fonctionnalités</th>
                <th class="text-center px-6 py-4 text-sm font-semibold text-gray-900">ISO 9001</th>
                <th class="text-center px-6 py-4 text-sm font-semibold text-gray-900">9001 + 14001</th>
                <th class="text-center px-6 py-4 text-sm font-semibold text-gray-900">SMI Intégré</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 text-sm text-gray-700">Gestion des processus</td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-700">Gestion documentaire</td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
              </tr>
              <tr>
                <td class="px-6 py-4 text-sm text-gray-700">Audits internes</td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-700">Gestion environnementale</td>
                <td class="px-6 py-4 text-center text-gray-400">-</td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
              </tr>
              <tr>
                <td class="px-6 py-4 text-sm text-gray-700">Santé & Sécurité (ISO 45001)</td>
                <td class="px-6 py-4 text-center text-gray-400">-</td>
                <td class="px-6 py-4 text-center text-gray-400">-</td>
                <td class="px-6 py-4 text-center"><Check class="w-5 h-5 text-green-500 mx-auto" /></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-700">Nombre de sites</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">1</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">3</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">Illimité</td>
              </tr>
              <tr>
                <td class="px-6 py-4 text-sm text-gray-700">Support</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">Email</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">Prioritaire</td>
                <td class="px-6 py-4 text-center text-sm text-gray-700">Dédié 24/7</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="py-16 px-4 bg-gray-50">
      <div class="max-w-4xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Questions fréquentes</h2>
        </div>
        <div class="space-y-6">
          <div v-for="(faq, index) in faqs" :key="index" class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="font-semibold text-lg text-gray-900 mb-2">{{ faq.question }}</h3>
            <p class="text-gray-600">{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 px-4 bg-gradient-to-r from-blue-600 to-blue-800">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-bold text-white mb-6">
          Prêt à transformer votre gestion qualité ?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
          Démarrez votre essai gratuit de 90 jours. Aucune carte bancaire requise.
        </p>
        <button
          class="bg-white text-blue-600 px-8 py-4 rounded-lg hover:bg-gray-100 transition-colors text-lg font-medium inline-flex items-center gap-2"
          @click="navigateTo('signup')"
        >
          Commencer gratuitement
          <ChevronRight class="w-5 h-5" />
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
  import type { Offer } from '@/types/api'
  import { Check, ChevronRight, Shield } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import { useAuth } from '@/modules/shared/composables/useAuth'
  import publicService from '@/services/publicService'

  const { navigateTo } = useAuth()

  const loading = ref(true)
  const offers = ref<Offer[]>([])

  onMounted(async () => {
    try {
      offers.value = await publicService.getPublicOffers()
    } catch (error) {
      console.error('Error loading offers:', error)
      // Fallback to empty array
      offers.value = []
    } finally {
      loading.value = false
    }
  })

  function formatPrice (price: number): string {
    return new Intl.NumberFormat('fr-FR').format(price)
  }

  const addons = [
    {
      name: 'Sites supplémentaires',
      price: '99',
      period: 'mois/site',
      description: 'Ajoutez autant de sites que nécessaire',
    },
    {
      name: 'Formation sur site',
      price: '1,500',
      period: 'jour',
      description: 'Formation personnalisée dans vos locaux',
    },
    {
      name: 'Audit de certification',
      price: '2,500',
      period: 'audit',
      description: 'Audit blanc préparatoire à la certification',
    },
  ]

  const faqs = [
    {
      question: 'Puis-je changer de pack plus tard ?',
      answer: 'Oui, vous pouvez évoluer vers un pack supérieur à tout moment. Le changement est immédiat et la facturation est ajustée au prorata.',
    },
    {
      question: 'Y a-t-il un engagement minimum ?',
      answer: 'Non. Les offres sont mensuelles par défaut (1 mois). Vous pouvez renouveler, arrêter ou faire évoluer votre offre selon vos besoins.',
    },
    {
      question: 'Les données sont-elles sécurisées ?',
      answer: 'Oui, toutes vos données sont hébergées en Europe, chiffrées et sauvegardées quotidiennement. Nous sommes conformes RGPD.',
    },
    {
      question: 'L\'accompagnement est-il inclus ?',
      answer: 'Tous nos packs incluent un accompagnement. Le niveau varie selon le pack choisi : email, prioritaire ou dédié.',
    },
  ]

</script>
