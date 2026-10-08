<template>
  <ViewerLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Consultation</div>
          <h1 class="role-dashboard__title">Votre espace de lecture</h1>
          <p class="role-dashboard__subtitle">Retrouvez rapidement les processus, documents et indicateurs autorisés.</p>
        </div>
      </div>

    <v-row>
      <v-col cols="12">
        <v-alert type="info" variant="tonal">
          Vous avez un accès en lecture seule. Vous pouvez consulter les processus, documents et indicateurs.
        </v-alert>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card prepend-icon="mdi-sync-circle" title="Processus">
          <v-card-text>
            <v-list>
              <v-list-item v-for="process in processes" :key="process.id" :to="`/viewer/processes/${process.id}`">
                <v-list-item-title>{{ process.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ process.category }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card prepend-icon="mdi-folder" title="Documents">
          <v-card-text>
            <v-list>
              <v-list-item v-for="doc in documents" :key="doc.id" :to="`/viewer/documents/${doc.id}`">
                <v-list-item-title>{{ doc.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ doc.type }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </ViewerLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import ViewerLayout from '@/modules/clienta/components/layouts/ViewerLayout.vue'

  const processes = ref([
    { id: 1, name: 'Gestion des commandes', category: 'Opérationnel' },
    { id: 2, name: 'Production', category: 'Opérationnel' },
    { id: 3, name: 'Contrôle qualité', category: 'Support' },
  ])

  const documents = ref([
    { id: 1, title: 'Manuel Qualité', type: 'Manuel' },
    { id: 2, title: 'Politique QHSE', type: 'Politique' },
    { id: 3, title: 'Procédure de contrôle', type: 'Procédure' },
  ])
</script>
