<template>
  <v-container class="pa-6" fluid>
    <h1 class="text-h4 font-weight-bold mb-6">Tableau de bord Company</h1>

    <v-row>
      <v-col cols="12" md="4">
        <v-card elevation="2" @click="$router.push('/company/documents')">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Documents</div>
                <div class="text-h4 font-weight-bold">0</div>
              </div>
              <v-avatar color="primary" size="56">
                <v-icon size="32">mdi-file-document</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">En attente</div>
                <div class="text-h4 font-weight-bold">0</div>
              </div>
              <v-avatar color="warning" size="56">
                <v-icon size="32">mdi-clock-outline</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Approuvés</div>
                <div class="text-h4 font-weight-bold">0</div>
              </div>
              <v-avatar color="success" size="56">
                <v-icon size="32">mdi-check-circle</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-6">
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title>Actions rapides</v-card-title>
          <v-card-text>
            <v-btn
              class="mr-2"
              color="primary"
              prepend-icon="mdi-plus"
              @click="$router.push('/company/documents/create')"
            >
              Nouveau document
            </v-btn>
            <v-btn
              prepend-icon="mdi-file-document"
              variant="outlined"
              @click="$router.push('/company/documents')"
            >
              Voir tous les documents
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
// Dashboard placeholder for company module
</script>
