<template>
  <div class="text-center py-12" :class="containerClass">
    <!-- Icon -->
    <div class="mx-auto mb-4" :class="iconContainerClass">
      <svg
        v-if="!customIcon"
        class="mx-auto text-gray-400"
        :class="iconSizeClass"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path :d="iconPath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
      </svg>
      <component :is="customIcon" v-else class="mx-auto text-gray-400" :class="iconSizeClass" />
    </div>

    <!-- Title -->
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ title }}
    </h3>

    <!-- Description -->
    <p class="text-gray-500 mb-6 max-w-sm mx-auto">
      {{ description }}
    </p>

    <!-- Actions -->
    <div v-if="$slots.actions || primaryAction" class="flex flex-col sm:flex-row gap-3 justify-center">
      <slot name="actions">
        <button
          v-if="primaryAction"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          @click="$emit('primary-action')"
        >
          <svg
            v-if="primaryAction.icon"
            class="w-4 h-4 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path :d="primaryAction.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          {{ primaryAction.text }}
        </button>

        <button
          v-if="secondaryAction"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          @click="$emit('secondary-action')"
        >
          <svg
            v-if="secondaryAction.icon"
            class="w-4 h-4 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path :d="secondaryAction.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          {{ secondaryAction.text }}
        </button>
      </slot>
    </div>

    <!-- Help Text -->
    <div v-if="helpText" class="mt-6 text-xs text-gray-400">
      {{ helpText }}
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface ActionButton {
    text: string
    icon?: string
  }

  interface Props {
    type?: 'default' | 'search' | 'filter' | 'error' | 'maintenance' | 'permission'
    title?: string
    description?: string
    primaryAction?: ActionButton
    secondaryAction?: ActionButton
    helpText?: string
    size?: 'sm' | 'md' | 'lg'
    customIcon?: any
    containerClass?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    type: 'default',
    size: 'md',
  })

  defineEmits<{
    'primary-action': []
    'secondary-action': []
  }>()

  const iconSizeClass = computed(() => {
    const sizes = {
      sm: 'h-12 w-12',
      md: 'h-16 w-16',
      lg: 'h-20 w-20',
    }
    return sizes[props.size]
  })

  const iconContainerClass = computed(() => {
    const sizes = {
      sm: 'w-12 h-12',
      md: 'w-16 h-16',
      lg: 'w-20 h-20',
    }
    return sizes[props.size]
  })

  const iconPath = computed(() => {
    const paths = {
      default: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
      filter: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z',
      error: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z',
      maintenance: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
      permission: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    }
    return paths[props.type]
  })

  const title = computed(() => {
    if (props.title) return props.title

    const titles = {
      default: 'Aucune donnée',
      search: 'Aucun résultat',
      filter: 'Aucun résultat pour ces filtres',
      error: 'Une erreur est survenue',
      maintenance: 'Maintenance en cours',
      permission: 'Accès non autorisé',
    }
    return titles[props.type]
  })

  const description = computed(() => {
    if (props.description) return props.description

    const descriptions = {
      default: 'Il n\'y a aucune donnée à afficher pour le moment.',
      search: 'Essayez de modifier votre recherche ou vos filtres.',
      filter: 'Aucun élément ne correspond aux critères sélectionnés.',
      error: 'Nous rencontrons des difficultés techniques. Veuillez réessayer.',
      maintenance: 'Cette fonctionnalité est temporairement indisponible.',
      permission: 'Vous n\'avez pas les permissions nécessaires pour accéder à cette section.',
    }
    return descriptions[props.type]
  })
</script>
