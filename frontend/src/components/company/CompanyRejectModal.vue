<template>
  <v-dialog v-model="isOpen" max-width="600" persistent>
    <v-card class="rounded-xl">
      <v-card-title class="d-flex align-center justify-space-between pa-6 border-b">
        <div class="d-flex align-center gap-3">
          <v-icon color="error" icon="mdi-alert-circle" size="28" />
          <span class="text-h6 font-weight-bold">Rejeter la demande</span>
        </div>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-card-text class="pa-6">
        <v-alert class="mb-4" type="warning" variant="tonal">
          Cette action est irréversible. L'entreprise sera notifiée par email.
        </v-alert>

        <div v-if="company" class="mb-4 pa-4 bg-grey-lighten-5 rounded-lg">
          <div class="font-weight-medium mb-1">{{ company.name }}</div>
          <div class="text-caption text-grey-darken-1">{{ company.email }}</div>
        </div>

        <v-textarea
          v-model="reason"
          counter
          :error-messages="errors.reason"
          label="Raison du rejet *"
          maxlength="500"
          placeholder="Expliquez pourquoi cette demande est rejetée..."
          required
          rows="4"
          variant="outlined"
        />
      </v-card-text>

      <v-card-actions class="pa-6 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="close">Annuler</v-btn>
        <v-btn
          color="error"
          :disabled="!reason || reason.length < 10"
          :loading="loading"
          variant="flat"
          @click="handleReject"
        >
          Confirmer le rejet
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { Enterprise } from '@/types/company.types'
  import { ref, watch } from 'vue'

  interface Props {
    modelValue: boolean
    company: Enterprise | null
    loading?: boolean
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'reject': [reason: string]
  }>()

  const isOpen = ref(props.modelValue)
  const reason = ref('')
  const errors = ref<{ reason?: string }>({})

  watch(() => props.modelValue, val => {
    isOpen.value = val
    if (val) {
      reason.value = ''
      errors.value = {}
    }
  })

  watch(isOpen, val => {
    emit('update:modelValue', val)
  })

  function close () {
    isOpen.value = false
  }

  function handleReject () {
    errors.value = {}

    if (!reason.value || reason.value.length < 10) {
      errors.value.reason = 'La raison doit contenir au moins 10 caractères'
      return
    }

    emit('reject', reason.value)
  }
</script>
