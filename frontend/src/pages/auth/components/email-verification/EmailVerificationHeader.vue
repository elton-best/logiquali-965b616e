<template>
  <div>
    <div class="flex items-center gap-2 mb-6">
      <Shield class="w-8 h-8 text-blue-600" />
      <span class="font-bold text-xl">Best Experts-Group</span>
    </div>

    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Mail class="w-8 h-8 text-blue-600" />
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Vérifiez votre email</h2>
      <p class="text-gray-600">
        {{ message }}
      </p>
      <div v-if="email && !isEditing" class="mt-2">
        <p class="font-medium text-gray-900">{{ email }}</p>
        <button class="text-sm text-blue-600 hover:text-blue-700 mt-1" @click="emit('update:isEditing', true)">
          Modifier l'adresse email
        </button>
      </div>
      <div v-else class="mt-4 space-y-3">
        <input
          v-model="emailValue"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="nouvelle.adresse@email.com"
          type="email"
        >
        <div class="flex gap-2">
          <button
            class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm"
            @click="emit('update-email')"
          >
            Mettre à jour
          </button>
          <button
            class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm"
            @click="emit('update:isEditing', false)"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Mail, Shield } from 'lucide-vue-next'
  import { computed } from 'vue'

  const props = defineProps({
    email: {
      type: String,
      required: true,
    },
    message: {
      type: String,
      required: true,
    },
    isEditing: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:email', value: string): void
    (event: 'update:isEditing', value: boolean): void
    (event: 'update-email'): void
  }>()

  const emailValue = computed({
    get: () => props.email,
    set: value => emit('update:email', value),
  })
</script>
