<template>
  <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
    <div class="flex items-center gap-2 mb-6">
      <Shield class="w-8 h-8 text-blue-600" />
      <span class="font-bold text-xl">Best Experts-Group</span>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 mb-2">Mot de passe oublié ?</h2>
    <p class="text-gray-600 mb-6">
      Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
    </p>

    <form class="space-y-4" @submit.prevent="emit('submit')">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Adresse email
        </label>
        <div class="relative">
          <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            v-model="modelValue"
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="exemple@entreprise.com"
            required
            type="email"
          >
        </div>
      </div>

      <button
        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
        type="submit"
      >
        Envoyer le lien de réinitialisation
      </button>
    </form>

    <div class="mt-6">
      <button
        class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        @click="emit('login')"
      >
        <ArrowLeft class="w-4 h-4" />
        Retour à la connexion
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ArrowLeft, Mail, Shield } from 'lucide-vue-next'
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'submit'): void
    (event: 'login'): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>
