<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-gauge</v-icon>
            Indicateurs de Performance Énergétique (IPÉ)
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvel IPÉ
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="store.ipes"
              :loading="store.loading"
            >
              <template #item.actif="{ item }">
                <v-chip
                  :color="item.actif ? 'success' : 'grey'"
                  size="small"
                >
                  {{ item.actif ? 'Actif' : 'Inactif' }}
                </v-chip>
              </template>

              <template #item.actions="{ item }">
                <v-btn icon size="small" @click="viewTendance(item)">
                  <v-icon>mdi-chart-line</v-icon>
                </v-btn>
                <v-btn icon size="small" @click="addValeur(item)">
                  <v-icon>mdi-plus-circle</v-icon>
                </v-btn>
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
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvel' }} IPÉ</v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.nom"
                  label="Nom *"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.formule_calcul"
                  hint="Ex: kWh/m² ou kWh/unité produite"
                  label="Formule de calcul *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.unite"
                  label="Unité *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.periodicite"
                  :items="['journalier', 'hebdomadaire', 'mensuel', 'trimestriel', 'annuel']"
                  label="Périodicité *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.valeur_reference"
                  label="Valeur de référence"
                  type="number"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.objectif_cible"
                  label="Objectif cible"
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

    <v-dialog v-model="dialogValeur" max-width="600">
      <v-card>
        <v-card-title>Ajouter une valeur</v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="valeurData.periode_debut"
                  label="Période début *"
                  type="date"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="valeurData.periode_fin"
                  label="Période fin *"
                  type="date"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model.number="valeurData.valeur"
                  label="Valeur *"
                  type="number"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="valeurData.commentaire"
                  label="Commentaire"
                  rows="2"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialogValeur = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveValeur">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import { useIpeStore } from '@/stores/ipeStore'

  const store = useIpeStore()
  const toast = useToast()

  const dialogCreate = ref(false)
  const dialogValeur = ref(false)
  const editedItem = ref(null)
  const selectedIpe = ref(null)
  const formData = ref({
    nom: '',
    formule_calcul: '',
    unite: '',
    periodicite: 'mensuel',
    valeur_reference: null,
    objectif_cible: null,
  })
  const valeurData = ref({
    periode_debut: '',
    periode_fin: '',
    valeur: 0,
    commentaire: '',
  })

  const headers = [
    { title: 'Nom', key: 'nom' },
    { title: 'Formule', key: 'formule_calcul' },
    { title: 'Unité', key: 'unite' },
    { title: 'Périodicité', key: 'periodicite' },
    { title: 'Statut', key: 'actif' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function editItem (item: any) {
    editedItem.value = item
    formData.value = { ...item }
    dialogCreate.value = true
  }

  function addValeur (item: any) {
    selectedIpe.value = item
    valeurData.value = { periode_debut: '', periode_fin: '', valeur: 0, commentaire: '' }
    dialogValeur.value = true
  }

  async function saveItem () {
    try {
      if (editedItem.value) {
        await store.updateIpe(editedItem.value.id, formData.value)
        toast.success('IPÉ modifié')
      } else {
        await store.createIpe(formData.value)
        toast.success('IPÉ créé')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function saveValeur () {
    try {
      await store.addValeur(selectedIpe.value.id, valeurData.value)
      toast.success('Valeur ajoutée')
      dialogValeur.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cet IPÉ ?')) {
      try {
        await store.deleteIpe(item.id)
        toast.success('IPÉ supprimé')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  function viewTendance (_item: any) {
    // TODO: Afficher graphique tendance
    toast.info('Graphique en développement')
  }

  onMounted(() => {
    store.fetchIpes()
  })
</script>
