<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-certificate</v-icon>
            Habilitations du Personnel
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvelle Habilitation
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-row class="mb-4">
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="search"
                  clearable
                  hide-details
                  label="Rechercher"
                  prepend-inner-icon="mdi-magnify"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filterStatut"
                  clearable
                  hide-details
                  :items="['valide', 'expire', 'suspendu']"
                  label="Statut"
                />
              </v-col>
            </v-row>

            <v-data-table
              :headers="headers"
              :items="filteredHabilitations"
              :loading="store.loading"
              :search="search"
            >
              <template #item.user="{ item }">
                {{ item.user?.first_name }} {{ item.user?.last_name }}
              </template>

              <template #item.type="{ item }">
                <v-chip color="primary" size="small">{{ formatType(item.type) }}</v-chip>
              </template>

              <template #item.date_expiration="{ item }">
                <v-chip
                  :color="getExpirationColor(item.date_expiration)"
                  size="small"
                >
                  {{ formatDate(item.date_expiration) }}
                </v-chip>
              </template>

              <template #item.statut="{ item }">
                <v-chip
                  :color="item.statut === 'valide' ? 'success' : item.statut === 'expire' ? 'error' : 'warning'"
                  size="small"
                >
                  {{ item.statut }}
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

    <v-dialog v-model="dialogCreate" max-width="800">
      <v-card>
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvelle' }} Habilitation</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type"
                  :items="typesHabilitation"
                  label="Type d'habilitation *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.organisme_formateur"
                  label="Organisme formateur *"
                  :rules="[v => !!v || 'Requis']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.date_obtention"
                  label="Date obtention *"
                  :rules="[v => !!v || 'Requis']"
                  type="date"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.date_expiration"
                  label="Date expiration *"
                  :rules="[v => !!v || 'Requis']"
                  type="date"
                />
              </v-col>
              <v-col cols="12">
                <v-file-input
                  v-model="formData.document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  label="Document (PDF, JPG, PNG)"
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
  import { computed, onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import { useHabilitationStore } from '@/stores/habilitationStore'

  const store = useHabilitationStore()
  const toast = useToast()

  const search = ref('')
  const filterStatut = ref(null)
  const dialogCreate = ref(false)
  const editedItem = ref(null)
  const formData = ref({
    type: '',
    organisme_formateur: '',
    date_obtention: '',
    date_expiration: '',
    document: null,
  })

  const headers = [
    { title: 'Personnel', key: 'user' },
    { title: 'Type', key: 'type' },
    { title: 'Organisme', key: 'organisme_formateur' },
    { title: 'Expiration', key: 'date_expiration' },
    { title: 'Statut', key: 'statut' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const typesHabilitation = [
    'electrique_b0', 'electrique_b1', 'electrique_b2', 'caces_1', 'caces_3',
    'travail_hauteur', 'echafaudage', 'nacelle', 'soudure', 'autre',
  ]

  const filteredHabilitations = computed(() => {
    let items = store.habilitations
    if (filterStatut.value) {
      items = items.filter(h => h.statut === filterStatut.value)
    }
    return items
  })

  function formatType (type: string) {
    return type.replace(/_/g, ' ').toUpperCase()
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getExpirationColor (date: string) {
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
        await store.updateHabilitation(editedItem.value.id, fd)
        toast.success('Habilitation modifiée')
      } else {
        await store.createHabilitation(fd)
        toast.success('Habilitation créée')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cette habilitation ?')) {
      try {
        await store.deleteHabilitation(item.id)
        toast.success('Habilitation supprimée')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  onMounted(() => {
    store.fetchHabilitations()
  })
</script>
