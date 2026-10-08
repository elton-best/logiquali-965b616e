<template>
  <div class="nc-cause-analysis bg-white dark:bg-gray-800 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Analyse des causes - Méthode 5M
    </h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
      Identifiez les causes racines selon les 5M (Matière, Machine, Méthode, Main d'œuvre, Milieu)
    </p>

    <!-- 5M Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <!-- Matière -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Matière</h4>
        </div>
        <textarea
          v-model="localAnalysis.main_material"
          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Matières premières, composants..."
          rows="3"
          @input="emitUpdate"
        />
      </div>

      <!-- Machine -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Machine</h4>
        </div>
        <textarea
          v-model="localAnalysis.machine"
          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Équipements, outils, logiciels..."
          rows="3"
          @input="emitUpdate"
        />
      </div>

      <!-- Méthode -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Méthode</h4>
        </div>
        <textarea
          v-model="localAnalysis.method"
          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Procédures, processus, instructions..."
          rows="3"
          @input="emitUpdate"
        />
      </div>

      <!-- Main d'œuvre -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Main d'œuvre</h4>
        </div>
        <textarea
          v-model="localAnalysis.workforce"
          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Compétences, formation, fatigue..."
          rows="3"
          @input="emitUpdate"
        />
      </div>

      <!-- Milieu -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Milieu</h4>
        </div>
        <textarea
          v-model="localAnalysis.environment"
          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Environnement, climat, éclairage..."
          rows="3"
          @input="emitUpdate"
        />
      </div>

      <!-- Conclusion / Root Cause -->
      <div class="border-2 border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-900/10 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white">Cause racine</h4>
        </div>
        <textarea
          v-model="localAnalysis.root_cause"
          class="w-full px-3 py-2 text-sm border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
          :disabled="disabled"
          placeholder="Quelle est la cause racine identifiée ?"
          rows="3"
          @input="emitUpdate"
        />
      </div>
    </div>

    <!-- Additional Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Remarques complémentaires
      </label>
      <textarea
        v-model="localAnalysis.notes"
        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none disabled:opacity-50"
        :disabled="disabled"
        placeholder="Toute information complémentaire utile pour l'analyse..."
        rows="4"
        @input="emitUpdate"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { CauseAnalysis } from '@/types/nonConformity'
  import { ref, watch } from 'vue'

  interface Props {
    modelValue: CauseAnalysis
    disabled?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    disabled: false,
  })

  const emit = defineEmits<{
    'update:modelValue': [analysis: CauseAnalysis]
  }>()

  const localAnalysis = ref<CauseAnalysis>({ ...props.modelValue })

  function emitUpdate () {
    emit('update:modelValue', localAnalysis.value)
  }

  watch(() => props.modelValue, newVal => {
    localAnalysis.value = { ...newVal }
  }, { deep: true })
</script>
