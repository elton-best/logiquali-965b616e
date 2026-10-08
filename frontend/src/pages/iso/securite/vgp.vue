<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-clipboard-check</v-icon>
            Vérifications Réglementaires (VGP)
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvelle Vérification
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="store.verifications"
              :loading="store.loading"
            >
              <template #item.equipement="{ item }">
                {{ item.equipement?.nom_commun }}
              </template>

              <template #item.type_verification="{ item }">
                <v-chip color="primary" size="small">{{ item.type_verification }}</v-chip>
              </template>

              <template #item.date_prochaine_verification="{ item }">
                <v-chip
                  :color="getDateColor(item.date_prochaine_verification)"
                  size="small"
                >
                  {{ formatDate(item.date_prochaine_verification) }}
                </v-chip>
              </template>

              <template #item.resultat="{ item }">
                <v-chip
                  :color="item.resultat === 'conforme' ? 'success' : item.resultat === 'non_conforme' ? 'error' : 'warning'"
                  size="small"
                >
                  {{ item.resultat }}
                </v-chip>
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
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvelle' }} Vérification</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type_verification"
                  :items="typesVerification"
                  label="Type de vérification *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.organisme_agree"
                  label="Organisme agréé *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.numero_rapport"
                  label="N° Rapport"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.resultat"
                  :items="['conforme', 'non_conforme', 'reserve']"
                  label="Résultat *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.date_verification"
                  label="Date vérification *"
                  :rules="[v => !!v || 'Requis']"
                  type="date"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.date_prochaine_verification"
                  label="Prochaine vérification *"
                  :rules="[v => !!v || 'Requis']"
                  type="date"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.observations"
                  label="Observations"
                  rows="3"
                />
              </v-col>
              <v-col cols="12">
                <v-file-input
                  v-model="formData.document"
                  accept=".pdf"
                  label="Rapport (PDF)"
                  prepend-icon="mdi-file-pdf-box"
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
  import { useVgpStore } from '@/stores/vgpStore'

  const store = useVgpStore()
  const toast = useToast()

  const dialogCreate = ref(false)
  const editedItem = ref(null)
  const formData = ref({
    type_verification: '',
    organisme_agree: '',
    numero_rapport: '',
    resultat: '',
    date_verification: '',
    date_prochaine_verification: '',
    observations: '',
    document: null,
  })

  const headers = [
    { title: 'Équipement', key: 'equipement' },
    { title: 'Type', key: 'type_verification' },
    { title: 'Organisme', key: 'organisme_agree' },
    { title: 'Prochaine VGP', key: 'date_prochaine_verification' },
    { title: 'Résultat', key: 'resultat' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const typesVerification = [
    'vgp',
    'vgp_approfondie',
    'epreuve_statique',
    'epreuve_dynamique',
    'verification_mise_service',
    'verification_remise_service',
  ]

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getDateColor (date: string) {
    const days = Math.floor((new Date(date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
    if (days < 0) return 'error'
    if (days < 30) return 'warning'
    return 'success'
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
        await store.updateVerification(editedItem.value.id, fd)
        toast.success('Vérification modifiée')
      } else {
        await store.createVerification(fd)
        toast.success('Vérification créée')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cette vérification ?')) {
      try {
        await store.deleteVerification(item.id)
        toast.success('Vérification supprimée')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  onMounted(() => {
    store.fetchVerifications()
  })
</script>
