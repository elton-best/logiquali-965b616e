<template>
  <BaseCard padding="md">
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Filtres
        </h3>
        <button
          v-if="hasActiveFilters"
          class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
          @click="resetAllFilters"
        >
          Réinitialiser
        </button>
      </div>

      <!-- Search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Recherche
        </label>
        <input
          v-model="localFilters.search"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Titre, référence..."
          type="text"
        >
      </div>

      <!-- Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Statut
        </label>
        <select
          v-model="localFilters.status"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="undefined">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="in_progress">En cours</option>
          <option value="completed">Terminé</option>
          <option value="cancelled">Annulé</option>
          <option value="overdue">En retard</option>
        </select>
      </div>

      <!-- Priority -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Priorité
        </label>
        <select
          v-model="localFilters.priority"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="undefined">Toutes les priorités</option>
          <option value="low">Faible</option>
          <option value="medium">Moyenne</option>
          <option value="high">Haute</option>
          <option value="critical">Critique</option>
        </select>
      </div>

      <!-- Type -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Type
        </label>
        <select
          v-model="localFilters.type"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="undefined">Tous les types</option>
          <option value="corrective">Corrective</option>
          <option value="preventive">Préventive</option>
          <option value="improvement">Amélioration</option>
          <option value="routine">Routine</option>
        </select>
      </div>

      <!-- Responsible (if users list provided) -->
      <div v-if="users && users.length > 0">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Responsable
        </label>
        <select
          v-model="localFilters.responsible_id"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="undefined">Tous les responsables</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>

      <!-- Overdue Only -->
      <div class="flex items-center">
        <input
          id="overdue-filter"
          v-model="localFilters.overdue"
          class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          type="checkbox"
        >
        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300" for="overdue-filter">
          Afficher uniquement les actions en retard
        </label>
      </div>

      <!-- Date Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Échéance
        </label>
        <div class="grid grid-cols-2 gap-2">
          <input
            v-model="localFilters.due_date_from"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            type="date"
          >
          <input
            v-model="localFilters.due_date_to"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            type="date"
          >
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { ActionFilters } from '@/types/action'
  import type { UserReference } from '@/types/shared'
  import { computed, ref, watch } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'

  interface Props {
    modelValue: ActionFilters
    users?: UserReference[]
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [filters: ActionFilters]
  }>()

  const localFilters = ref<ActionFilters>({ ...props.modelValue })

  const hasActiveFilters = computed(() => {
    return !!(
      localFilters.value.search
      || localFilters.value.status
      || localFilters.value.priority
      || localFilters.value.type
      || localFilters.value.responsible_id
      || localFilters.value.overdue
      || localFilters.value.due_date_from
      || localFilters.value.due_date_to
    )
  })

  // Watch local filters and emit changes
  watch(
    localFilters,
    newFilters => {
      emit('update:modelValue', { ...newFilters })
    },
    { deep: true },
  )

  // Watch external changes
  watch(
    () => props.modelValue,
    newFilters => {
      localFilters.value = { ...newFilters }
    },
  )

  function resetAllFilters () {
    localFilters.value = {
      page: 1,
      per_page: 20,
    }
  }
</script>
