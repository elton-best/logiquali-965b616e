<template>
  <v-snackbar
    v-model="state.show"
    :color="state.color"
    :location="state.position === 'top' ? 'top' : 'bottom'"
    :timeout="state.timeout"
  >
    {{ state.message }}

    <template #actions>
      <v-btn
        variant="text"
        @click="state.show = false"
      >
        Fermer
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script setup lang="ts">
  import { useNotification } from '@/plugins/notification'

  const notification = useNotification()
  const state = notification.getState()
</script>
