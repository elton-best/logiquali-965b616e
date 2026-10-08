<template>
  <div>
    <h3 class="text-h6 mb-6">Sécurité du compte</h3>

    <v-card class="mb-6" variant="outlined">
      <v-card-text class="py-6">
        <div class="d-flex align-start gap-3">
          <v-icon class="mt-1" color="primary" icon="mdi-lock-reset" />
          <div>
            <div class="text-subtitle-1 font-weight-medium">Mot de passe</div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              Le changement de mot de passe est disponible dans l'onglet Profil.
            </div>
            <v-btn color="primary" variant="outlined" @click="emit('go-profile')">
              Aller au profil
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-alert color="info" icon="mdi-information-outline" variant="tonal">
      Les options avancées de sécurité (sessions actives, clés API, 2FA) seront activées
      dès la mise en service backend dédiée.
    </v-alert>
  </div>
</template>

<script setup lang="ts">
  const emit = defineEmits<{
    (event: 'go-profile'): void
  }>()
</script>
