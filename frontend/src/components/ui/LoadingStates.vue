<template>
  <!-- Button Loading -->
  <button
    v-if="type === 'button'"
    :class="[
      'inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200',
      loading ? 'opacity-75 cursor-not-allowed' : '',
      variant === 'primary' ? 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500' : '',
      variant === 'secondary' ? 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500' : '',
      variant === 'danger' ? 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500' : '',
      customClass
    ]"
    :disabled="loading"
    v-bind="$attrs"
  >
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor" />
    </svg>

    <slot>{{ loading ? loadingText : text }}</slot>
  </button>

  <!-- Inline Loading -->
  <div
    v-else-if="type === 'inline'"
    class="flex items-center space-x-2"
  >
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4 text-blue-600"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor" />
    </svg>
    <span class="text-sm text-gray-600">
      <slot>{{ loading ? loadingText : text }}</slot>
    </span>
  </div>

  <!-- Overlay Loading -->
  <div
    v-else-if="type === 'overlay'"
    class="relative"
  >
    <div :class="{ 'opacity-50 pointer-events-none': loading }">
      <slot />
    </div>

    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg"
    >
      <div class="flex flex-col items-center space-y-2">
        <svg
          class="animate-spin h-8 w-8 text-blue-600"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor" />
        </svg>
        <span class="text-sm text-gray-600">{{ loadingText }}</span>
      </div>
    </div>
  </div>

  <!-- Spinner Only -->
  <div
    v-else-if="type === 'spinner'"
    class="flex items-center justify-center"
    :class="customClass"
  >
    <svg
      class="animate-spin text-blue-600"
      :class="size === 'sm' ? 'h-4 w-4' : size === 'lg' ? 'h-8 w-8' : 'h-6 w-6'"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor" />
    </svg>
  </div>

  <!-- Dots Loading -->
  <div
    v-else-if="type === 'dots'"
    class="flex items-center justify-center space-x-1"
    :class="customClass"
  >
    <div
      v-for="i in 3"
      :key="i"
      class="rounded-full bg-blue-600 animate-pulse"
      :class="size === 'sm' ? 'h-2 w-2' : size === 'lg' ? 'h-4 w-4' : 'h-3 w-3'"
      :style="{ animationDelay: `${(i - 1) * 0.2}s` }"
    />
  </div>

  <!-- Progress Bar -->
  <div
    v-else-if="type === 'progress'"
    class="w-full bg-gray-200 rounded-full h-2"
    :class="customClass"
  >
    <div
      class="bg-blue-600 h-2 rounded-full transition-all duration-300"
      :style="{ width: `${progress}%` }"
    />
    <div v-if="showProgressText" class="text-xs text-gray-600 mt-1 text-center">
      {{ progress }}%
    </div>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    type?: 'button' | 'inline' | 'overlay' | 'spinner' | 'dots' | 'progress'
    loading?: boolean
    variant?: 'primary' | 'secondary' | 'danger'
    size?: 'sm' | 'md' | 'lg'
    text?: string
    loadingText?: string
    progress?: number
    showProgressText?: boolean
    customClass?: string
  }

  withDefaults(defineProps<Props>(), {
    type: 'spinner',
    loading: true,
    variant: 'primary',
    size: 'md',
    text: '',
    loadingText: 'Chargement...',
    progress: 0,
    showProgressText: false,
    customClass: '',
  })
</script>
