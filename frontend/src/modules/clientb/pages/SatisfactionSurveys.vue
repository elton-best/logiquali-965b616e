<template>
  <ClientBLayout current-page="/clientb/surveys">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center">
            <div>
              <h1 class="text-h4 font-weight-bold mb-2">
                📋 Enquêtes de Satisfaction
              </h1>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Partagez votre expérience et aidez-nous à améliorer nos services
              </p>
            </div>
            <div class="mt-4 mt-md-0">
              <v-btn
                color="primary"
                prepend-icon="mdi-plus-circle"
                size="large"
                @click="openCreateDialog"
              >
                Nouvelle Enquête
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Empty State (for now) -->
      <v-card color="grey-lighten-4" elevation="0">
        <v-card-text class="pa-12 text-center">
          <v-icon class="mb-4" color="grey-lighten-1" size="80">
            mdi-clipboard-text-outline
          </v-icon>
          <h2 class="text-h5 font-weight-bold mb-3">
            Interface refaite !
          </h2>
          <p class="text-body-1 text-medium-emphasis mb-6">
            La nouvelle interface des enquêtes de satisfaction a été créée avec succès.
          </p>
        </v-card-text>
      </v-card>
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import ClientBLayout from '../components/ClientBLayout.vue'

  function openCreateDialog () {
    alert('Nouvelle enquête - À implémenter')
  }
</script>
