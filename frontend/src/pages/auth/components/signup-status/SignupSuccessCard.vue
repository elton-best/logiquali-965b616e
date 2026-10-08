<template>
  <v-card class="signup-success-loader-scope" elevation="24" rounded="xl">
    <div v-if="isNavigating" class="signup-success-loader-overlay">
      <UnifiedLoader
        centered
        message="Redirection en cours..."
        size="sm"
        variant="spinner"
      />
    </div>
    <v-card-text class="pa-8 text-center">
      <v-avatar class="mb-6" color="success" size="100">
        <v-icon color="white" size="60">mdi-check-circle</v-icon>
      </v-avatar>

      <template v-if="type === 'enterprise'">
        <h2 class="text-h5 font-weight-bold mb-4">Demande soumise !</h2>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Votre dossier KYC a été transmis à notre équipe de validation.
        </p>

        <v-alert class="text-left mb-6" color="info" variant="tonal">
          <div class="text-body-2">
            <strong>Prochaines étapes :</strong>
            <ol class="mt-2 pl-4">
              <li>Vérification de vos documents (24-48h)</li>
              <li>Validation de votre compte</li>
              <li>Réception d'un email de confirmation</li>
              <li>Accès à votre espace entreprise</li>
            </ol>
          </div>
        </v-alert>

        <p class="text-body-2 text-medium-emphasis mb-6">
          Vous recevrez un email dès que votre compte sera activé.
        </p>
      </template>

      <template v-else>
        <h2 class="text-h5 font-weight-bold mb-4">Bienvenue sur BestQHSE !</h2>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Votre compte a été créé avec succès. Vous êtes maintenant connecté !
        </p>

        <v-alert class="text-left mb-6" color="success" variant="tonal">
          <div class="text-body-2">
            <strong>Vous pouvez maintenant :</strong>
            <ul class="mt-2 pl-4">
              <li>Déposer des plaintes et réclamations</li>
              <li>Suivre l'évolution de vos demandes</li>
              <li>Répondre aux enquêtes de satisfaction</li>
              <li>Utiliser le chatbot pour dialoguer avec les entreprises</li>
            </ul>
          </div>
        </v-alert>
      </template>

      <v-divider class="my-6" />

      <v-btn
        v-if="type === 'enterprise'"
        block
        color="primary"
        :disabled="isNavigating"
        :loading="isNavigating"
        prepend-icon="mdi-login"
        size="large"
        @click="emit('login')"
      >
        Aller à la page de connexion
      </v-btn>

      <v-btn
        v-else
        block
        color="success"
        :disabled="isNavigating"
        :loading="isNavigating"
        prepend-icon="mdi-view-dashboard"
        size="large"
        @click="emit('dashboard')"
      >
        Accéder à mon dashboard
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  defineProps({
    type: {
      type: String,
      required: true,
    },
    isNavigating: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'login'): void
    (event: 'dashboard'): void
  }>()
</script>
