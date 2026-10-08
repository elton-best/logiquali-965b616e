<template>
  <ClientALayout current-page="support">
    <v-container class="pa-6" fluid>
      <v-card class="mx-auto" elevation="1" max-width="800" rounded="lg">
        <v-card-text class="pa-12 text-center">
          <v-avatar class="mb-6" color="purple" size="120">
            <v-icon color="white" size="64">mdi-bullhorn</v-icon>
          </v-avatar>

          <h1 class="text-h4 font-weight-bold mb-4">Communication et sensibilisation</h1>
          <p class="text-h6 text-medium-emphasis mb-8">Plan de communication & Sensibilisation</p>

          <v-chip class="mb-8" color="info" size="large" variant="tonal">
            <v-icon start>mdi-clock-outline</v-icon>
            Module en cours de développement
          </v-chip>

          <v-divider class="my-8" />

          <div class="text-left">
            <h3 class="text-h6 mb-4">Fonctionnalités prévues :</h3>
            <v-list class="bg-transparent">
              <v-list-item class="text-body-1" prepend-icon="mdi-check-circle">
                Plan de communication annuel
              </v-list-item>
              <v-list-item class="text-body-1" prepend-icon="mdi-check-circle">
                Campagnes de sensibilisation
              </v-list-item>
              <v-list-item class="text-body-1" prepend-icon="mdi-check-circle">
                Cibles et canaux de communication
              </v-list-item>
              <v-list-item class="text-body-1" prepend-icon="mdi-check-circle">
                Suivi et efficacité
              </v-list-item>
            </v-list>
          </div>

          <v-btn
            class="mt-8"
            color="primary"
            prepend-icon="mdi-arrow-left"
            size="large"
            to="/company/dashboard"
          >
            Retour au tableau de bord
          </v-btn>
        </v-card-text>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
</script>
