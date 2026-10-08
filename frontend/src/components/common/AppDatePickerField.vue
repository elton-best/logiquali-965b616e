<template>
  <div class="app-date-field">
    <label
      v-if="label"
      class="date-label"
      :class="{ 'label-required': required }"
    >
      {{ label }}
      <span v-if="required" class="required-mark">*</span>
    </label>

    <div class="date-shell" :class="{ disabled, readonly }">
      <div aria-hidden="true" class="date-leading-icon">
        <v-icon v-if="mode === 'datetime'" size="18">mdi-calendar-clock-outline</v-icon>
        <v-icon v-else size="18">mdi-calendar-month-outline</v-icon>
      </div>
      <VueDatePicker
        v-model="pickerValue"
        :auto-apply="mode === 'date'"
        :clearable="clearable"
        :disabled="disabled"
        :enable-time-picker="mode === 'datetime'"
        :format="displayFormat"
        :hide-input-icon="true"
        :is-24="true"
        :minutes-increment="5"
        :placeholder="placeholder"
        :teleport="true"
        text-input
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { VueDatePicker } from '@vuepic/vue-datepicker'
  import { computed } from 'vue'

  interface Props {
    modelValue?: string | null
    label?: string
    placeholder?: string
    mode?: 'date' | 'datetime'
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    clearable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    label: '',
    placeholder: '',
    mode: 'date',
    disabled: false,
    readonly: false,
    required: false,
    clearable: true,
  })

  const emit = defineEmits<{
    'update:modelValue': [value: string | null]
  }>()

  function toDate (value: string | Date | null | undefined) {
    if (!value) return null
    if (value instanceof Date) return value
    const parsed = new Date(value)
    return Number.isNaN(parsed.getTime()) ? null : parsed
  }

  function two (value: number) {
    return String(value).padStart(2, '0')
  }

  function formatLocalDate (value: Date) {
    const year = value.getFullYear()
    const month = two(value.getMonth() + 1)
    const day = two(value.getDate())
    return `${year}-${month}-${day}`
  }

  function formatLocalDateTime (value: Date) {
    const year = value.getFullYear()
    const month = two(value.getMonth() + 1)
    const day = two(value.getDate())
    const hours = two(value.getHours())
    const minutes = two(value.getMinutes())
    return `${year}-${month}-${day}T${hours}:${minutes}`
  }

  const pickerValue = computed<Date | null>({
    get () {
      return toDate(props.modelValue)
    },
    set (value) {
      if (!value) {
        emit('update:modelValue', null)
        return
      }

      const output = props.mode === 'datetime'
        ? formatLocalDateTime(value)
        : formatLocalDate(value)

      emit('update:modelValue', output)
    },
  })

  const displayFormat = computed(() =>
    props.mode === 'datetime' ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
  )
</script>

<style scoped>
  .app-date-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
  }

  .date-label {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
  }

  .required-mark {
    color: #ef4444;
  }

  .date-shell {
    position: relative;
    border: 1px solid rgba(148, 163, 184, 0.55);
    border-radius: 12px;
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    transition: all 0.2s ease;
  }

  .date-leading-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    display: inline-flex;
    align-items: center;
    pointer-events: none;
    z-index: 2;
  }

  .date-shell:focus-within {
    border-color: rgba(37, 99, 235, 0.75);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 14px 28px rgba(37, 99, 235, 0.12);
  }

  .date-shell.disabled,
  .date-shell.readonly {
    opacity: 0.72;
    pointer-events: none;
  }

  .date-shell :deep(.dp__main) {
    width: 100%;
  }

  .date-shell :deep(.dp__input_wrap) {
    width: 100%;
  }

  .date-shell :deep(.dp__input) {
    border: 0;
    border-radius: 12px;
    min-height: 44px;
    padding: 10px 34px 10px 36px;
    background: transparent;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 500;
  }

  .date-shell :deep(.dp__input::placeholder) {
    color: #94a3b8;
  }

  .date-shell :deep(.dp__clear_icon) {
    right: 10px;
    color: #94a3b8;
  }
</style>
