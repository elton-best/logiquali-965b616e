<template>
  <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-200 text-center">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <CheckCircle class="w-8 h-8 text-green-600" />
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Email envoyé !</h2>
    <p class="text-gray-600 mb-6">
      Si un compte existe avec l'adresse <strong>{{ email }}</strong>, vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.
    </p>
    <p class="text-sm text-gray-500 mb-6">
      Vérifiez également votre dossier spam/courrier indésirable.
    </p>
    <button
      class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
      @click="emit('login')"
    >
      Retour à la connexion
    </button>
  </div>
</template>

<script setup lang="ts">
  import { CheckCircle } from 'lucide-vue-next'

  defineProps({
    email: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'login'): void
  }>()
</script>
