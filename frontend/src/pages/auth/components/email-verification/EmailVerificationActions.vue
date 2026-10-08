<template>
  <div>
    <div v-if="resent" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 flex items-center gap-3">
      <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0" />
      <span class="text-sm text-green-700">Email de vérification renvoyé avec succès !</span>
    </div>

    <div class="space-y-3">
      <button
        class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="resent || loading"
        @click="emit('resend')"
      >
        <RefreshCw class="w-5 h-5" :class="{ 'animate-spin': loading }" />
        {{ loading ? 'Envoi...' : resent ? 'Email renvoyé' : 'Renvoyer l\'email' }}
      </button>

      <div class="pt-4 border-t border-gray-200">
        <p class="text-sm text-gray-600 text-center mb-3">
          Vous avez déjà vérifié votre email ?
        </p>
        <button
          class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium"
          @click="emit('verify')"
        >
          Retour à la connexion
        </button>
      </div>
    </div>

    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 mb-2">
        Vous n'avez pas reçu l'email ? Vérifiez vos spams.
      </p>
      <button class="text-sm text-gray-600 hover:text-gray-900" @click="emit('logout')">
        Se déconnecter
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { CheckCircle, RefreshCw } from 'lucide-vue-next'

  defineProps({
    resent: {
      type: Boolean,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'resend'): void
    (event: 'verify'): void
    (event: 'logout'): void
  }>()
</script>
