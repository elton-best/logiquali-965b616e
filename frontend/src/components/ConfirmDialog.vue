<template>
  <v-dialog v-model="dialog" max-width="500" persistent>
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-3" :color="iconColor" size="32">{{ icon }}</v-icon>
        <span>{{ title }}</span>
      </v-card-title>

      <v-card-text class="text-body-1">
        {{ message }}
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          :disabled="loading"
          variant="text"
          @click="handleCancel"
        >
          {{ cancelText }}
        </v-btn>
        <v-btn
          :color="confirmColor"
          :loading="loading"
          variant="flat"
          @click="handleConfirm"
        >
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  interface Props {
    modelValue: boolean
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    type?: 'danger' | 'warning' | 'info' | 'success'
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Confirmation',
    message: 'Êtes-vous sûr de vouloir effectuer cette action ?',
    confirmText: 'Confirmer',
    cancelText: 'Annuler',
    type: 'danger',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'confirm': []
    'cancel': []
  }>()

  const loading = ref(false)

  const dialog = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  type ConfirmType = NonNullable<Props['type']>
  const typeConfig: Record<ConfirmType, { color: string, icon: string }> = {
    danger: { color: 'error', icon: 'mdi-alert-circle' },
    warning: { color: 'warning', icon: 'mdi-alert' },
    info: { color: 'info', icon: 'mdi-information' },
    success: { color: 'success', icon: 'mdi-check-circle' },
  }

  const config = computed<{ color: string, icon: string }>(() => {
    const key: ConfirmType = props.type || 'danger'
    return typeConfig[key]
  })
  const confirmColor = computed(() => config.value.color)
  const iconColor = computed(() => config.value.color)
  const icon = computed(() => config.value.icon)

  function handleCancel () {
    emit('cancel')
    emit('update:modelValue', false)
  }

  function handleConfirm () {
    emit('confirm')
  // Don't close dialog automatically - let parent handle it after async operation
  }
</script>
