<template>
  <div class="axe-selector">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="flex gap-3">
      <div
        v-for="axe in availableAxes"
        :key="axe.code"
        class="axe-option"
        :class="{ 'axe-selected': isSelected(axe.code), 'axe-disabled': axe.is_active === false }"
        @click="toggleAxe(axe.code)"
      >
        <div class="axe-icon" :style="{ backgroundColor: axe.color }">
          {{ axe.code }}
        </div>
        <div class="axe-info">
          <div class="axe-name">{{ axe.name }}</div>
          <div class="axe-description">{{ axe.description }}</div>
        </div>
        <div v-if="isSelected(axe.code)" class="axe-check">
          <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd" />
          </svg>
        </div>
      </div>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <p v-if="hint" class="mt-2 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import { useAxeStore } from '@/stores/improvement/axeStore'

  interface Props {
    modelValue: string[]
    label?: string
    required?: boolean
    multiple?: boolean
    onlyActive?: boolean
    error?: string
    hint?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    multiple: true,
    onlyActive: true,
  })

  const emit = defineEmits<{
    'update:modelValue': [value: string[]]
  }>()

  const axeStore = useAxeStore()

  // Load axes on mount
  onMounted(() => {
    if (axeStore.axes.length === 0) {
      axeStore.fetchAxes()
    }
  })

  // Available axes (filter by active if needed)
  const availableAxes = computed(() => {
    if (props.onlyActive) {
      return axeStore.activeAxes
    }
    return axeStore.axes
  })

  // Check if axe is selected
  function isSelected (code: string) {
    return props.modelValue.includes(code)
  }

  // Toggle axe selection
  function toggleAxe (code: string) {
    const axe = availableAxes.value.find(a => a.code === code)
    if (!axe || axe.is_active === false) return

    let newValue: string[]

    if (props.multiple) {
      newValue = isSelected(code) ? props.modelValue.filter(c => c !== code) : [...props.modelValue, code]
    } else {
      newValue = isSelected(code) ? [] : [code]
    }

    emit('update:modelValue', newValue)
  }
</script>

<style scoped>
.axe-selector {
  @apply w-full;
}

.axe-option {
  @apply relative flex items-center gap-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-blue-400 hover:shadow-md;
}

.axe-option.axe-selected {
  @apply border-blue-500 bg-blue-50 shadow-md;
}

.axe-option.axe-disabled {
  @apply opacity-50 cursor-not-allowed hover:border-gray-200 hover:shadow-none;
}

.axe-icon {
  @apply flex items-center justify-center w-12 h-12 text-white font-bold text-lg rounded-full shrink-0;
}

.axe-info {
  @apply flex-1;
}

.axe-name {
  @apply text-sm font-semibold text-gray-900;
}

.axe-description {
  @apply text-xs text-gray-600 mt-1;
}

.axe-check {
  @apply absolute top-2 right-2;
}
</style>
