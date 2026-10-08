<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-leaf</v-icon>
            Aspects Environnementaux
            <v-spacer />
            <v-btn color="primary" @click="dialogCreate = true">
              <v-icon left>mdi-plus</v-icon>
              Nouvel Aspect
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="store.aspects"
              :loading="store.loading"
            >
              <template #item.type="{ item }">
                <v-chip color="primary" size="small">{{ formatType(item.type) }}</v-chip>
              </template>

              <template #item.criticite="{ item }">
                <v-chip
                  :color="getCriticiteColor(item.criticite)"
                  size="small"
                >
                  {{ item.criticite }}
                </v-chip>
              </template>

              <template #item.aspect_significatif="{ item }">
                <v-chip
                  :color="item.aspect_significatif ? 'error' : 'success'"
                  size="small"
                >
                  {{ item.aspect_significatif ? 'Significatif' : 'Non significatif' }}
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
        <v-card-title>{{ editedItem ? 'Modifier' : 'Nouvel' }} Aspect Environnemental</v-card-title>
        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type"
                  :items="typesAspect"
                  label="Type *"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.condition"
                  :items="['normale', 'anormale', 'urgence']"
                  label="Condition *"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.designation"
                  label="Désignation *"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="formData.gravite"
                  :items="[1,2,3,4,5]"
                  label="Gravité (1-3) *"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="formData.frequence"
                  :items="[1,2,3,4,5]"
                  label="Fréquence (1-3) *"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="formData.detectabilite"
                  :items="[1,2,3,4,5]"
                  label="Détectabilité (1-3) *"
                />
              </v-col>
              <v-col cols="12">
                <v-alert v-if="formData.gravite && formData.frequence && formData.detectabilite" type="info">
                  Criticité calculée: {{ formData.gravite * formData.frequence * formData.detectabilite }}
                  ({{ (formData.gravite * formData.frequence * formData.detectabilite) >= 50 ? 'Significatif' : 'Non significatif' }})
                </v-alert>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.mesures_maitrise"
                  label="Mesures de maîtrise"
                  rows="3"
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
  import { useAspectEnvironnementalStore } from '@/stores/aspectEnvironnementalStore'

  const store = useAspectEnvironnementalStore()
  const toast = useToast()

  const dialogCreate = ref(false)
  const editedItem = ref(null)
  const formData = ref({
    type: '',
    designation: '',
    condition: 'normale',
    gravite: 1,
    frequence: 1,
    detectabilite: 1,
    mesures_maitrise: '',
  })

  const headers = [
    { title: 'Désignation', key: 'designation' },
    { title: 'Type', key: 'type' },
    { title: 'Condition', key: 'condition' },
    { title: 'Criticité', key: 'criticite' },
    { title: 'Statut', key: 'aspect_significatif' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const typesAspect = [
    'emission_air', 'rejet_eau', 'dechet', 'bruit', 'odeur',
    'consommation_ressource', 'pollution_sol', 'autre',
  ]

  function formatType (type: string) {
    return type.replace(/_/g, ' ').toUpperCase()
  }

  function getCriticiteColor (criticite: number) {
    if (criticite >= 75) return 'error'
    if (criticite >= 50) return 'warning'
    return 'success'
  }

  function editItem (item: any) {
    editedItem.value = item
    formData.value = { ...item }
    dialogCreate.value = true
  }

  async function saveItem () {
    try {
      if (editedItem.value) {
        await store.updateAspect(editedItem.value.id, formData.value as any)
        toast.success('Aspect modifié')
      } else {
        await store.createAspect(formData.value as any)
        toast.success('Aspect créé')
      }
      dialogCreate.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  async function deleteItem (item: any) {
    if (confirm('Supprimer cet aspect ?')) {
      try {
        await store.deleteAspect(item.id)
        toast.success('Aspect supprimé')
      } catch {
        toast.error('Erreur')
      }
    }
  }

  onMounted(() => {
    store.fetchAspects()
  })
</script>
