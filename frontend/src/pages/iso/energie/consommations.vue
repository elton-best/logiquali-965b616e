<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
            Consommations Énergétiques
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvelle Consommation
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="store.consommations"
              :loading="store.loading"
            >
              <template #item.type_energie="{ item }">
                <v-chip color="primary" size="small">{{ item.type_energie }}</v-chip>
              </template>

              <template #item.valeur_consommation="{ item }">
                {{ item.valeur_consommation }} {{ item.unite }}
              </template>

              <template #item.mode_saisie="{ item }">
                <v-chip
                  :color="item.mode_saisie === 'releve_reel' ? 'success' : 'warning'"
                  size="small"
                >
                  {{ formatMode(item.mode_saisie) }}
                </v-chip>
              </template>

              <template #item.cout_euro="{ item }">
                <span v-if="item.cout_euro">{{ item.cout_euro }} €</span>
              </template>

              <template #item.actions="{ item }">
                <v-btn icon size="small" @click="editItem(item)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn color="error" icon size="small" @click="deleteItem(item)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="dialogCreate" max-width="800">
      <v-card>
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvelle' }} Consommation</v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type_energie"
                  :items="typesEnergie"
                  label="Type énergie *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.mode_saisie"
                  :items="modesSaisie"
                  label="Mode saisie *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.periode_debut"
                  label="Période début *"
                  type="date"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.periode_fin"
                  label="Période fin *"
                  type="date"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.valeur_consommation"
                  label="Consommation *"
                  type="number"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.unite"
                  :items="['kwh', 'm3', 'litres', 'tonnes']"
                  label="Unité *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.cout_euro"
                  label="Coût (€)"
                  type="number"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.emission_co2_kg"
                  label="Émission CO2 (kg)"
                  type="number"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialogCreate = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveItem">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import { useConsommationEnergieStore } from '@/stores/consommationEnergieStore'

  const store = useConsommationEnergieStore()
  const toast = useToast()

  const dialogCreate = ref(false)
  const editedItem = ref(null)
  const formData = ref({
    type_energie: 'electricite',
    mode_saisie: 'releve_reel',
    periode_debut: '',
    periode_fin: '',
    valeur_consommation: 0,
    unite: 'kwh',
    cout_euro: null,
    emission_co2_kg: null,
  })

  const headers = [
    { title: 'Période', key: 'periode_debut' },
    { title: 'Type', key: 'type_energie' },
    { title: 'Consommation', key: 'valeur_consommation' },
    { title: 'Mode', key: 'mode_saisie' },
    { title: 'Coût', key: 'cout_euro' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const typesEnergie = ['electricite', 'gaz', 'fuel', 'vapeur', 'air_comprime', 'autre']
  const modesSaisie = [
    { title: 'Relevé réel', value: 'releve_reel' },
    { title: 'Estimation', value: 'estimation' },
    { title: 'Import compteur', value: 'import_compteur' },
  ]

  function formatMode (mode: string) {
    return mode.replace(/_/g, ' ').toUpperCase()
  }

  function editItem (item: any) {
    editedItem.value = item
    formData.value = { ...item }
    dialogCreate.value = true
  }

  async function saveItem () {
    try {
      if (editedItem.value) {
        await store.updateConsommation(editedItem.value.id, formData.value as any)
        toast.success('Consommation modifiée')
      } else {
        await store.createConsommation(formData.value as any)
        toast.success('Consommation créée')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cette consommation ?')) {
      try {
        await store.deleteConsommation(item.id)
        toast.success('Consommation supprimée')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  onMounted(() => {
    store.fetchConsommations()
  })
</script>
