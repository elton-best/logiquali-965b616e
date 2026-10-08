<template>
  <ClientALayout current-page="performance-dashboard">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h1 class="text-h3 mb-6">Évaluation des Performances</h1>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="3">
          <v-card class="h-100" hover @click="$router.push('/company/performance/surveillance')">
            <v-card-title class="text-h5">
              <v-icon color="primary" start>mdi-chart-line</v-icon>
              Surveillance & Mesure
            </v-card-title>
            <v-card-subtitle>M13-D3/4/6</v-card-subtitle>
            <v-card-text>
              Surveillance, mesure, analyse et évaluation de la satisfaction
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card class="h-100" hover @click="$router.push('/company/performance/audits')">
            <v-card-title class="text-h5">
              <v-icon color="primary" start>mdi-clipboard-check</v-icon>
              Audit Interne
            </v-card-title>
            <v-card-subtitle>M9-D1/5</v-card-subtitle>
            <v-card-text>
              Gestion des audits, planification et évaluation des auditeurs
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card class="h-100" hover @click="$router.push('/company/performance/surveillance/process-review')">
            <v-card-title class="text-h5">
              <v-icon color="primary" start>mdi-chart-timeline-variant</v-icon>
              Revue Processus
            </v-card-title>
            <v-card-subtitle>Nouveau sous-module</v-card-subtitle>
            <v-card-text>
              Revue détaillée par processus avec sections métiers et clôture définitive
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3">
          <v-card class="h-100" hover @click="$router.push('/company/performance/revue-direction')">
            <v-card-title class="text-h5">
              <v-icon color="primary" start>mdi-account-tie</v-icon>
              Revue de Direction
            </v-card-title>
            <v-card-subtitle>M12-D2/3</v-card-subtitle>
            <v-card-text>
              Rapports et invitations pour les revues de direction
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
</script>
