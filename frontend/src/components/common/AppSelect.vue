<template>
  <div class="app-select-wrapper">
    <!-- Label -->
    <label
      v-if="label"
      class="select-label"
      :class="{ 'label-required': required }"
      :for="selectId"
    >
      {{ label }}
      <span v-if="required" aria-label="requis" class="required-mark">*</span>
    </label>

    <!-- Select container -->
    <div class="select-container" :class="containerClasses">
      <!-- Prefix icon -->
      <div v-if="prefixIcon" class="select-icon prefix-icon">
        <component :is="prefixIcon" :size="20" />
      </div>

      <!-- Native select (simple mode) -->
      <select
        v-if="!searchable"
        :id="selectId"
        ref="selectRef"
        :aria-describedby="
          error ? `${selectId}-error` : hint ? `${selectId}-hint` : undefined
        "
        :aria-invalid="error ? 'true' : 'false'"
        class="select-field"
        :class="{ 'select-placeholder': isPlaceholderActive }"
        :disabled="disabled"
        :required="required"
        :value="modelValue"
        @blur="handleBlur"
        @change="handleChange"
        @focus="handleFocus"
      >
        <option v-if="placeholder" disabled value="">{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="getOptionValue(option)"
          :value="getOptionValue(option)"
        >
          {{ getOptionLabel(option) }}
        </option>
      </select>

      <!-- Custom select (searchable mode) -->
      <div v-else class="custom-select">
        <input
          :id="selectId"
          ref="inputRef"
          class="select-field"
          :disabled="disabled"
          :placeholder="placeholder"
          :readonly="!searchable"
          type="text"
          :value="displayValue"
          @blur="handleBlur"
          @focus="handleFocus"
          @input="handleSearch"
          @keydown.down.prevent="navigateOptions(1)"
          @keydown.enter.prevent="selectHighlighted"
          @keydown.escape="closeDropdown"
          @keydown.up.prevent="navigateOptions(-1)"
        >
      </div>

      <!-- Chevron icon -->
      <div class="select-icon suffix-icon">
        <ChevronDown :class="{ 'rotate-180': isOpen }" :size="20" />
      </div>
    </div>

    <!-- Dropdown (searchable mode) -->
    <Transition name="dropdown">
      <div v-if="searchable && isOpen" class="select-dropdown">
        <div v-if="filteredOptions.length === 0" class="dropdown-empty">
          Aucun résultat
        </div>
        <div
          v-for="(option, index) in filteredOptions"
          :key="getOptionValue(option)"
          class="dropdown-option"
          :class="{
            'option-selected': isSelected(option),
            'option-highlighted': index === highlightedIndex,
          }"
          @click="selectOption(option)"
          @mouseenter="highlightedIndex = index"
        >
          {{ getOptionLabel(option) }}
        </div>
      </div>
    </Transition>

    <!-- Hint text -->
    <p v-if="hint && !error" :id="`${selectId}-hint`" class="select-hint">
      {{ hint }}
    </p>

    <!-- Error message -->
    <p v-if="error" :id="`${selectId}-error`" class="select-error" role="alert">
      <AlertCircle :size="14" />
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, ChevronDown } from 'lucide-vue-next'
  import { computed, ref, useId, watch } from 'vue'

  type Option
    = | string
      | number
      | { [key: string]: any, label: string, value: any }

  interface Props {
    modelValue?: any
    options: Option[]
    label?: string
    placeholder?: string
    hint?: string
    error?: string
    disabled?: boolean
    required?: boolean
    searchable?: boolean
    prefixIcon?: any
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    searchable: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: any]
    'blur': [event: FocusEvent]
    'focus': [event: FocusEvent]
  }>()

  const selectRef = ref<HTMLSelectElement>()
  const inputRef = ref<HTMLInputElement>()
  const selectId = `select-${useId()}`
  const isOpen = ref(false)
  const searchQuery = ref('')
  const highlightedIndex = ref(0)

  const containerClasses = computed(() => ({
    'select-disabled': props.disabled,
    'select-error': props.error,
    'select-open': isOpen.value,
    [`select-${props.size}`]: true,
    'has-prefix': props.prefixIcon,
  }))

  function getOptionValue (option: Option) {
    if (typeof option === 'object' && option !== null) {
      return option.value
    }
    return option
  }

  function getOptionLabel (option: Option) {
    if (typeof option === 'object' && option !== null) {
      return option.label
    }
    return String(option)
  }

  const displayValue = computed(() => {
    if (!props.modelValue) return ''
    const selected = props.options.find(
      opt => getOptionValue(opt) === props.modelValue,
    )
    return selected ? getOptionLabel(selected) : ''
  })

  const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options

    const query = searchQuery.value.toLowerCase()
    return props.options.filter(option =>
      getOptionLabel(option).toLowerCase().includes(query),
    )
  })

  const isPlaceholderActive = computed(() => {
    return (
      props.modelValue === ''
      || props.modelValue === null
      || props.modelValue === undefined
    )
  })

  function isSelected (option: Option) {
    return getOptionValue(option) === props.modelValue
  }

  function handleChange (event: Event) {
    const target = event.target as HTMLSelectElement
    emit('update:modelValue', target.value)
  }

  function handleSearch (event: Event) {
    const target = event.target as HTMLInputElement
    searchQuery.value = target.value
    isOpen.value = true
    highlightedIndex.value = 0
  }

  function handleFocus (event: FocusEvent) {
    if (props.searchable) {
      isOpen.value = true
    }
    emit('focus', event)
  }

  function handleBlur (event: FocusEvent) {
    setTimeout(() => {
      isOpen.value = false
      searchQuery.value = ''
    }, 200)
    emit('blur', event)
  }

  function selectOption (option: Option) {
    emit('update:modelValue', getOptionValue(option))
    isOpen.value = false
    searchQuery.value = ''
  }

  function navigateOptions (direction: number) {
    if (!isOpen.value) {
      isOpen.value = true
      return
    }

    highlightedIndex.value = Math.max(
      0,
      Math.min(
        filteredOptions.value.length - 1,
        highlightedIndex.value + direction,
      ),
    )
  }

  function selectHighlighted () {
    if (filteredOptions.value[highlightedIndex.value]) {
      selectOption(filteredOptions.value[highlightedIndex.value])
    }
  }

  function closeDropdown () {
    isOpen.value = false
    searchQuery.value = ''
  }

  watch(isOpen, open => {
    if (!open) {
      highlightedIndex.value = 0
    }
  })

  defineExpose({
    focus: () => {
      if (props.searchable) {
        inputRef.value?.focus()
      } else {
        selectRef.value?.focus()
      }
    },
  })
</script>

<style scoped>
.app-select-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
  position: relative;
}

/* Label */
.select-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 4px;
}

.required-mark {
  color: #ef4444;
  font-weight: 700;
}

/* Select container */
.select-container {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.select-container:hover:not(.select-disabled) {
  border-color: #9ca3af;
}

.select-container:focus-within:not(.select-disabled),
.select-container.select-open {
  border-color: #4471c4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
  outline: none;
}

.select-container.select-error {
  border-color: #ef4444;
}

.select-container.select-error:focus-within {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.select-container.select-disabled {
  background: #f3f4f6;
  border-color: #e5e7eb;
  cursor: not-allowed;
}

/* Sizes */
.select-container.select-sm {
  min-height: 36px;
}

.select-container.select-md {
  min-height: 44px;
}

.select-container.select-lg {
  min-height: 52px;
}

/* Select field */
.select-field {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #1f2937;
  outline: none;
  padding: 0 12px;
  min-width: 0;
  cursor: pointer;
  appearance: none;
}

.select-sm .select-field {
  padding: 0 10px;
  font-size: 13px;
}

.select-lg .select-field {
  padding: 0 14px;
  font-size: 16px;
}

.select-container.has-prefix .select-field {
  padding-left: 0;
}

.select-field:disabled {
  cursor: not-allowed;
  color: #6b7280;
}

.custom-select {
  flex: 1;
  min-width: 0;
}

/* Icons */
.select-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  flex-shrink: 0;
  transition: transform 150ms;
}

.prefix-icon {
  padding-left: 12px;
  padding-right: 8px;
}

.suffix-icon {
  padding-right: 12px;
  padding-left: 8px;
}

.suffix-icon svg {
  transition: transform 150ms;
}

.suffix-icon svg.rotate-180 {
  transform: rotate(180deg);
}

/* Dropdown */
.select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow:
    0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -4px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  z-index: 50;
}

.dropdown-empty {
  padding: 12px;
  text-align: center;
  color: #9ca3af;
  font-size: 14px;
}

.dropdown-option {
  padding: 10px 12px;
  cursor: pointer;
  font-size: 14px;
  color: #1f2937;
  transition: background 100ms;
}

.dropdown-option:hover,
.dropdown-option.option-highlighted {
  background: #f3f4f6;
}

.dropdown-option.option-selected {
  background: #eef2fb;
  color: #4471c4;
  font-weight: 600;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Hint */
.select-hint {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

/* Error */
.select-error {
  font-size: 13px;
  color: #ef4444;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Scrollbar */
.select-dropdown::-webkit-scrollbar {
  width: 8px;
}

.select-dropdown::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.select-dropdown::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.select-dropdown::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .select-container,
  .select-icon svg,
  .dropdown-option {
    transition: none !important;
  }
}
</style>
