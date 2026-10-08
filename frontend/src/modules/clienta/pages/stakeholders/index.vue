<template>
  <ClientALayout current-page="stakeholders">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-handshake" title="Parties Intéressées">
        <template #subtitle>Registre des parties intéressées internes et externes</template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="showDialog = true">Ajouter</v-btn>
        </template>
      </PageHeader>

      <v-tabs v-model="tab" class="mt-6" color="primary">
        <v-tab value="internal">Internes ({{ internalStakeholders.length }})</v-tab>
        <v-tab value="external">Externes ({{ externalStakeholders.length }})</v-tab>
      </v-tabs>

      <v-card class="mt-4" rounded="xl">
        <v-card-text>
          <v-window v-model="tab">
            <v-window-item value="internal">
              <v-data-table class="elevation-0" :headers="headers" :items="internalStakeholders">
                <template #[`item.expectations`]="{ item }">
                  <v-chip v-for="exp in item.expectations.slice(0, 2)" :key="exp" size="small">{{ exp }}</v-chip>
                </template>
              </v-data-table>
            </v-window-item>
            <v-window-item value="external">
              <v-data-table class="elevation-0" :headers="headers" :items="externalStakeholders">
                <template #[`item.expectations`]="{ item }">
                  <v-chip v-for="exp in item.expectations.slice(0, 2)" :key="exp" size="small">{{ exp }}</v-chip>
                </template>
              </v-data-table>
            </v-window-item>
          </v-window>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showDialog" max-width="800">
        <v-card rounded="xl">
          <v-card-title class="pa-6">Nouvelle Partie Intéressée</v-card-title>
          <v-card-text class="px-6">
            <v-select v-model="form.type" :items="['Interne', 'Externe']" label="Type" variant="outlined" />
            <v-text-field v-model="form.name" label="Nom" variant="outlined" />
            <v-text-field v-model="form.category" label="Catégorie" variant="outlined" />
            <v-textarea v-model="form.expectations" label="Attentes" rows="3" variant="outlined" />
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" @click="createStakeholder">Créer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import ClientALayout from '../../components/ClientALayout.vue'
  import PageHeader from '../../components/PageHeader.vue'

  const toast = useToast()
  const showDialog = ref(false)
  const tab = ref('internal')

  const headers = [
    { title: 'Nom', value: 'name' },
    { title: 'Catégorie', value: 'category' },
    { title: 'Attentes', value: 'expectations' },
  ]

  const internalStakeholders = ref([
    { id: 1, name: 'Direction Générale', category: 'Direction', expectations: ['Performance', 'Conformité'] },
    { id: 2, name: 'Employés', category: 'Personnel', expectations: ['Formation', 'Sécurité'] },
  ])

  const externalStakeholders = ref([
    { id: 3, name: 'Clients', category: 'Commercial', expectations: ['Qualité', 'Délais'] },
    { id: 4, name: 'Fournisseurs', category: 'Commercial', expectations: ['Paiements', 'Volumes'] },
  ])

  const form = ref({ type: 'Externe', name: '', category: '', expectations: '' })

  function createStakeholder () {
    toast.success('Partie intéressée ajoutée')
    showDialog.value = false
  }
</script>
