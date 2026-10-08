<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h1 class="text-h4 mb-2">Traçabilité des mesures</h1>
          <p class="text-subtitle-1 text-grey">M7-D4 - Traçabilité métrologique</p>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" lg="8">
          <v-card rounded="lg">
            <v-card-title>Finalité</v-card-title>
            <v-card-text>
              S'assurer que les équipements de surveillance et de mesure sont identifiés, vérifiés et raccordés à des
              étalons reconnus afin de garantir la fiabilité des résultats.
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" lg="4">
          <v-card rounded="lg">
            <v-card-title>Accès rapide</v-card-title>
            <v-card-text class="d-flex flex-column ga-2">
              <v-btn color="primary" prepend-icon="mdi-file-document-outline" @click="$router.push('/company/performance/surveillance/procedure-evaluation')">
                Procédure d'évaluation
              </v-btn>
              <v-btn prepend-icon="mdi-chart-line" variant="outlined" @click="$router.push('/company/performance/surveillance')">
                Retour au suivi global
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row class="mt-4">
        <v-col cols="12">
          <v-card rounded="lg">
            <v-card-title>Registre de traçabilité (minimum)</v-card-title>
            <v-card-text>
              <v-data-table :headers="headers" :items="items" items-per-page="5" />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'

  const headers = [
    { title: 'Exigence', key: 'requirement' },
    { title: 'Contrôle attendu', key: 'control' },
  ]

  const items = [
    { requirement: 'Identification équipement', control: 'Code unique, type, localisation et responsable définis.' },
    { requirement: 'Étalonnage / Vérification', control: 'Dates de contrôle, résultats et décision de conformité.' },
    { requirement: 'Raccordement', control: 'Référence à l’étalon ou au laboratoire utilisé.' },
    { requirement: 'Gestion des écarts', control: 'Actions en cas d’équipement non conforme ou hors tolérance.' },
  ]
</script>
