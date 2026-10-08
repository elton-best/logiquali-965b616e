<template>
  <BaseModal
    v-model="isOpen"
    size="md"
    title="Mettre à jour la progression"
    @close="handleClose"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <!-- Progress Slider -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Progression : {{ formData.progress }}%
        </label>
        <input
          v-model.number="formData.progress"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          max="100"
          min="0"
          step="5"
          type="range"
        >
        <div class="flex justify-between text-xs text-gray-500 mt-1">
          <span>0%</span>
          <span>25%</span>
          <span>50%</span>
          <span>75%</span>
          <span>100%</span>
        </div>
      </div>

      <!-- Visual Progress Bar -->
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
        <div
          :class="[
            'h-3 rounded-full transition-all duration-300',
            progressColor
          ]"
          :style="{ width: `${formData.progress}%` }"
        />
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Notes (optionnel)
        </label>
        <textarea
          v-model="formData.notes"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Décrivez l'avancement, les obstacles rencontrés..."
          rows="4"
        />
      </div>

      <!-- Error Display -->
      <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
      </div>
    </form>

    <template #footer>
      <button
        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
        :disabled="loading"
        type="button"
        @click="handleClose"
      >
        Annuler
      </button>
      <button
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="loading"
        type="button"
        @click="handleSubmit"
      >
        <span v-if="loading">Mise à jour...</span>
        <span v-else>Mettre à jour</span>
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
  import type { Action } from '@/types/action'
  import { computed, ref, watch } from 'vue'
  import BaseModal from '@/components/common/BaseModal.vue'

  interface Props {
    modelValue: boolean
    action: Action | null
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'submit': [data: { progress: number, notes?: string }]
  }>()

  const isOpen = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const formData = ref({
    progress: 0,
    notes: '',
  })

  const loading = ref(false)
  const error = ref<string | null>(null)

  const progressColor = computed(() => {
    const progress = formData.value.progress
    if (progress >= 75) return 'bg-green-500'
    if (progress >= 50) return 'bg-blue-500'
    if (progress >= 25) return 'bg-yellow-500'
    return 'bg-gray-400'
  })

  // Watch action changes to update form
  watch(() => props.action, newAction => {
    if (newAction) {
      formData.value.progress = newAction.progress
      formData.value.notes = ''
    }
  }, { immediate: true })

  async function handleSubmit () {
    if (!props.action) return

    error.value = null
    loading.value = true

    try {
      emit('submit', {
        progress: formData.value.progress,
        notes: formData.value.notes || undefined,
      })
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour'
    } finally {
      loading.value = false
    }
  }

  function handleClose () {
    formData.value = {
      progress: props.action?.progress || 0,
      notes: '',
    }
    error.value = null
    emit('update:modelValue', false)
  }
</script>
