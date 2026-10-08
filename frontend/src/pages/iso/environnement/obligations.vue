<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-gavel</v-icon>
            Obligations de Conformité Environnementale
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvelle Obligation
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="store.obligations"
              :loading="store.loading"
            >
              <template #item.type="{ item }">
                <v-chip color="primary" size="small">{{ item.type }}</v-chip>
              </template>

              <template #item.statut_conformite="{ item }">
                <v-chip
                  :color="getStatutColor(item.statut_conformite)"
                  size="small"
                >
                  {{ item.statut_conformite }}
                </v-chip>
              </template>

              <template #item.date_prochain_controle="{ item }">
                <span v-if="item.date_prochain_controle">
                  {{ formatDate(item.date_prochain_controle) }}
                </span>
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

    <v-dialog v-model="dialogCreate" max-width="900">
      <v-card>
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvelle' }} Obligation</v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type"
                  :items="['reglementaire', 'volontaire', 'contractuelle']"
                  label="Type *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.reference"
                  label="Référence *"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.titre"
                  label="Titre *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.autorite_competente"
                  label="Autorité compétente"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.statut_conformite"
                  :items="['conforme', 'non_conforme', 'en_cours', 'non_applicable']"
                  label="Statut *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.periodicite_controle"
                  :items="['mensuel', 'trimestriel', 'semestriel', 'annuel', 'ponctuel']"
                  label="Périodicité contrôle"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.date_prochain_controle"
                  label="Prochain contrôle"
                  type="date"
                />
              </v-col>
              <v-col cols="12">
                <v-file-input
                  v-model="formData.document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  label="Document justificatif"
                  prepend-icon="mdi-paperclip"
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
  import { useObligationConformiteStore } from '@/stores/obligationConformiteStore'

  const store = useObligationConformiteStore()
  const toast = useToast()

  const dialogCreate = ref(false)
  const editedItem = ref(null)
  const formData = ref({
    type: 'reglementaire',
    reference: '',
    titre: '',
    autorite_competente: '',
    statut_conformite: 'en_cours',
    periodicite_controle: null,
    date_prochain_controle: '',
    document: null,
  })

  const headers = [
    { title: 'Référence', key: 'reference' },
    { title: 'Titre', key: 'titre' },
    { title: 'Type', key: 'type' },
    { title: 'Autorité', key: 'autorite_competente' },
    { title: 'Statut', key: 'statut_conformite' },
    { title: 'Prochain contrôle', key: 'date_prochain_controle' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getStatutColor (statut: string) {
    const colors = {
      conforme: 'success',
      non_conforme: 'error',
      en_cours: 'warning',
      non_applicable: 'grey',
    }
    return colors[statut] || 'grey'
  }

  function editItem (item: any) {
    editedItem.value = item
    formData.value = { ...item, document: null }
    dialogCreate.value = true
  }

  async function saveItem () {
    const fd = new FormData()
    for (const key of Object.keys(formData.value)) {
      if (formData.value[key]) fd.append(key, formData.value[key])
    }

    try {
      if (editedItem.value) {
        await store.updateObligation(editedItem.value.id, fd)
        toast.success('Obligation modifiée')
      } else {
        await store.createObligation(fd)
        toast.success('Obligation créée')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cette obligation ?')) {
      try {
        await store.deleteObligation(item.id)
        toast.success('Obligation supprimée')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  onMounted(() => {
    store.fetchObligations()
  })
</script>
