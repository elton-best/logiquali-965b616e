<template>
  <v-card class="email-verified-loader-scope" elevation="24" rounded="xl">
    <div v-if="isNavigating" class="email-verified-loader-overlay">
      <UnifiedLoader
        centered
        message="Redirection en cours..."
        size="sm"
        variant="spinner"
      />
    </div>
    <v-card-text class="pa-8 text-center">
      <v-avatar class="mb-6" color="success" size="100">
        <v-icon color="white" size="60">mdi-shield-check</v-icon>
      </v-avatar>

      <h2 class="text-h5 font-weight-bold mb-4">
        {{ alreadyVerified ? 'Email déjà vérifié' : 'Bienvenue sur BestQHSE !' }}
      </h2>

      <p class="text-body-1 text-medium-emphasis mb-6">
        {{ alreadyVerified
          ? 'Votre email était déjà vérifié. Vous pouvez vous connecter dès maintenant.'
          : 'Votre email a été vérifié avec succès. Vous pouvez maintenant vous connecter et profiter de toutes les fonctionnalités de la plateforme.' }}
      </p>

      <v-chip
        v-if="countdown > 0"
        class="mb-4"
        color="success"
        size="small"
        variant="tonal"
      >
        <v-icon start>mdi-clock-outline</v-icon>
        Redirection automatique dans {{ countdown }}s
      </v-chip>

      <v-alert class="text-left mb-6" color="success" variant="tonal">
        <div class="text-body-2">
          <strong>Vous pouvez maintenant :</strong>
          <ul class="mt-2 pl-4">
            <li>Déposer des plaintes et réclamations</li>
            <li>Suivre l'évolution de vos demandes en temps réel</li>
            <li>Répondre aux enquêtes de satisfaction</li>
            <li>Utiliser le chatbot pour dialoguer avec les entreprises</li>
            <li>Accéder à votre tableau de bord personnalisé</li>
          </ul>
        </div>
      </v-alert>

      <v-divider class="my-6" />

      <v-btn
        block
        color="success"
        :disabled="isNavigating"
        :loading="isNavigating"
        prepend-icon="mdi-login"
        size="large"
        @click="emit('login')"
      >
        Se connecter maintenant
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  defineProps({
    alreadyVerified: {
      type: Boolean,
      required: true,
    },
    countdown: {
      type: Number,
      required: true,
    },
    isNavigating: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'login'): void
  }>()
</script>
