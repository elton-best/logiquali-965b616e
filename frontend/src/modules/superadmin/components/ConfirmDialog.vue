<template>
  <v-dialog max-width="520" :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-3" :color="iconColor" size="28">{{ icon }}</v-icon>
        <span class="text-h6 font-weight-bold">{{ title }}</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-2">{{ message }}</p>
        <v-alert
          v-if="impact"
          class="mt-4"
          density="compact"
          type="error"
          variant="tonal"
        >
          {{ impact }}
        </v-alert>
      </v-card-text>
      <v-card-actions class="px-6 pb-4">
        <v-spacer />
        <v-btn variant="text" @click="$emit('cancel')">{{ cancelLabel }}</v-btn>
        <v-btn :color="confirmColor" :loading="loading" @click="$emit('confirm')">
          {{ confirmLabel }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  withDefaults(defineProps<{
    modelValue: boolean
    title: string
    message: string
    impact?: string
    confirmLabel?: string
    cancelLabel?: string
    confirmColor?: string
    icon?: string
    iconColor?: string
    loading?: boolean
  }>(), {
    impact: '',
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    confirmColor: 'error',
    icon: 'mdi-delete-alert-outline',
    iconColor: 'error',
    loading: false,
  })

  defineEmits(['update:modelValue', 'confirm', 'cancel'])
</script>
