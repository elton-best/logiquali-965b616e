<template>
  <v-dialog :max-width="maxWidth" :model-value="modelValue" @update:model-value="emitModel">
    <v-card style="border-radius: 16px">
      <v-card-title class="pa-6 pb-4 d-flex align-center">
        <v-icon class="mr-3" :color="confirmColor" size="32">{{ icon }}</v-icon>
        <span class="text-h6 font-weight-bold">{{ title }}</span>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <p class="text-body-1 mb-4 dialog-message">{{ message }}</p>

        <v-textarea
          v-if="showReason"
          v-model="localReason"
          :error-messages="normalizedReasonError"
          :label="reasonLabel"
          :placeholder="reasonPlaceholder"
          rows="4"
          variant="outlined"
        />
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-6 pt-4">
        <v-spacer />
        <v-btn :disabled="loading" variant="text" @click="handleCancel">
          Annuler
        </v-btn>
        <v-btn :color="confirmColor" :loading="loading" variant="flat" @click="$emit('confirm')">
          {{ confirmLabel }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    modelValue: boolean
    title: string
    message: string
    confirmLabel: string
    confirmColor?: string
    icon?: string
    loading?: boolean
    showReason?: boolean
    reason?: string
    reasonLabel?: string
    reasonPlaceholder?: string
    reasonError?: string | string[]
    maxWidth?: number | string
  }

  const props = withDefaults(defineProps<Props>(), {
    confirmColor: 'primary',
    icon: 'mdi-alert-circle-outline',
    loading: false,
    showReason: false,
    reason: '',
    reasonLabel: 'Raison *',
    reasonPlaceholder: 'Indiquez la raison...',
    reasonError: '',
    maxWidth: 520,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:reason', value: string): void
    (e: 'confirm' | 'cancel'): void
  }>()

  const localReason = computed({
    get: () => props.reason,
    set: (value: string) => emit('update:reason', value),
  })

  const normalizedReasonError = computed(() => {
    if (!props.reasonError) return []
    return Array.isArray(props.reasonError) ? props.reasonError : [props.reasonError]
  })

  function emitModel (value: boolean) {
    emit('update:modelValue', value)
  }

  function handleCancel () {
    emit('update:modelValue', false)
    emit('cancel')
  }
</script>

<style scoped>
.dialog-message {
  white-space: pre-line;
}
</style>
