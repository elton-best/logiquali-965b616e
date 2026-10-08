<template>
  <v-card class="pa-8 text-center" elevation="4" max-width="600">
    <v-icon class="mb-4" color="warning" size="80">mdi-clock-alert-outline</v-icon>

    <h1 class="text-h4 font-weight-bold mb-4">
      {{ statusMessage.title }}
    </h1>

    <p class="text-body-1 text-medium-emphasis mb-6">
      {{ statusMessage.message }}
    </p>

    <v-alert
      v-if="enterprise?.rejection_reason"
      class="mb-6 text-left"
      color="error"
      variant="tonal"
    >
      <div class="font-weight-bold mb-2">Raison du rejet :</div>
      <div>{{ enterprise.rejection_reason }}</div>
    </v-alert>

    <v-alert
      v-if="enterprise?.suspension_reason"
      class="mb-6 text-left"
      color="warning"
      variant="tonal"
    >
      <div class="font-weight-bold mb-2">Raison de la suspension :</div>
      <div>{{ enterprise.suspension_reason }}</div>
    </v-alert>

    <div class="d-flex gap-4 justify-center">
      <v-btn
        color="primary"
        prepend-icon="mdi-logout"
        variant="outlined"
        @click="emit('logout')"
      >
        Se déconnecter
      </v-btn>

      <v-btn
        v-if="enterprise?.status === 'rejected'"
        color="primary"
        prepend-icon="mdi-pencil"
        @click="emit('reapply')"
      >
        Modifier mon inscription
      </v-btn>
    </div>

    <v-divider class="my-6" />

    <div class="text-caption text-medium-emphasis">
      <div class="mb-2">Besoin d'aide ?</div>
      <div>
        Contactez-nous à
        <a class="text-primary" href="mailto:support@BestQHSE.com">support@BestQHSE.com</a>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    statusMessage: {
      type: Object as PropType<{ title: string, message: string }>,
      required: true,
    },
    enterprise: {
      type: Object as PropType<Record<string, any> | null>,
      default: null,
    },
  })

  const emit = defineEmits<{
    (event: 'logout'): void
    (event: 'reapply'): void
  }>()
</script>
