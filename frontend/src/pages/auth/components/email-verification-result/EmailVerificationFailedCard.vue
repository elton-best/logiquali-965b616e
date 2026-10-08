<template>
  <v-card class="email-failed-loader-scope" elevation="24" rounded="xl">
    <div v-if="isNavigating" class="email-failed-loader-overlay">
      <UnifiedLoader
        centered
        message="Redirection en cours..."
        size="sm"
        variant="spinner"
      />
    </div>
    <v-card-text class="pa-8 text-center">
      <v-avatar class="mb-6" color="error" size="100">
        <v-icon color="white" size="60">mdi-close-circle</v-icon>
      </v-avatar>

      <h2 class="text-h5 font-weight-bold mb-4">Une erreur est survenue</h2>

      <template v-if="errorType === 'invalid_link'">
        <p class="text-body-1 text-medium-emphasis mb-6">
          Le lien de vérification est invalide ou a été corrompu. Veuillez demander un nouveau lien.
        </p>
      </template>

      <template v-else-if="errorType === 'expired_link'">
        <p class="text-body-1 text-medium-emphasis mb-6">
          Le lien de vérification a expiré. Pour des raisons de sécurité, les liens sont valables 24 heures. Veuillez demander un nouveau lien.
        </p>
      </template>

      <template v-else>
        <p class="text-body-1 text-medium-emphasis mb-6">
          Une erreur technique est survenue. Veuillez réessayer ou contacter le support.
        </p>

        <v-alert
          v-if="errorMessage"
          class="text-left mb-4"
          color="error"
          density="compact"
          variant="outlined"
        >
          <div class="text-caption">
            <strong>Détails techniques :</strong> {{ errorMessage }}
          </div>
        </v-alert>
      </template>

      <v-alert class="text-left mb-6" color="info" variant="tonal">
        <div class="text-body-2">
          <strong>Solutions :</strong>
          <ul class="mt-2 pl-4">
            <li>Retournez à la page de connexion</li>
            <li>Demandez un nouveau lien de vérification</li>
            <li>Contactez le support si le problème persiste</li>
          </ul>
        </div>
      </v-alert>

      <v-card class="mb-6 text-left" variant="outlined">
        <v-card-text>
          <div class="text-subtitle-2 font-weight-bold mb-2">
            Demander un nouveau lien de vérification
          </div>
          <v-text-field
            v-model="emailValue"
            :disabled="resendLoading || isNavigating"
            hide-details="auto"
            label="Adresse email"
            prepend-inner-icon="mdi-email"
            type="email"
            variant="outlined"
          />
          <v-btn
            block
            class="mt-3"
            color="primary"
            :disabled="isNavigating || resendLoading"
            :loading="resendLoading"
            prepend-icon="mdi-email-fast"
            @click="emit('resend')"
          >
            Renvoyer un lien
          </v-btn>
          <div v-if="resendSuccess" class="text-success text-caption mt-2">
            {{ resendSuccess }}
          </div>
        </v-card-text>
      </v-card>

      <v-divider class="my-6" />

      <div class="d-flex flex-column gap-3">
        <v-btn
          block
          color="primary"
          :disabled="isNavigating"
          :loading="isNavigating"
          prepend-icon="mdi-login"
          size="large"
          @click="emit('login')"
        >
          Retour à la connexion
        </v-btn>

        <v-btn
          block
          color="info"
          :disabled="isNavigating"
          prepend-icon="mdi-help-circle"
          size="large"
          variant="outlined"
          @click="emit('support')"
        >
          Contacter le support
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const props = defineProps({
    errorType: {
      type: String,
      required: true,
    },
    errorMessage: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
    },
    resendLoading: {
      type: Boolean,
      required: true,
    },
    resendSuccess: {
      type: String,
      required: true,
    },
    isNavigating: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:email', value: string): void
    (event: 'resend'): void
    (event: 'login'): void
    (event: 'support'): void
  }>()

  const emailValue = computed({
    get: () => props.email,
    set: value => emit('update:email', value),
  })
</script>
