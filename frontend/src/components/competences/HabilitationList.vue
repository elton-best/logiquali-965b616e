<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Habilitations</span>
      <v-btn color="primary" @click="openForm()">
        <v-icon>mdi-plus</v-icon>
        Nouvelle habilitation
      </v-btn>
    </v-card-title>

    <v-data-table
      :headers="headers"
      item-key="id"
      :items="habilitationStore.habilitations"
      :loading="habilitationStore.loading"
    >
      <template #item.status="{ item }">
        <v-chip :color="getStatusColor(item.status)" size="small">
          {{ item.status }}
        </v-chip>
      </template>

      <template #item.expiry_date="{ item }">
        <span :class="getExpiryClass(item.expiry_date)">
          {{ formatDate(item.expiry_date) }}
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

    <HabilitationForm
      v-model="showForm"
      :item="selectedItem"
      @saved="onSaved"
    />
  </v-card>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useCompetenceStore } from '@/stores/competenceStore'
  import { useHabilitationStore } from '@/stores/habilitationStore'
  import { useUserStore } from '@/stores/userStore'
  import HabilitationForm from './HabilitationForm.vue'

  const habilitationStore = useHabilitationStore()
  const userStore = useUserStore()
  const competenceStore = useCompetenceStore()

  const showForm = ref(false)
  const selectedItem = ref(null)

  const headers = [
    { title: 'Employé', key: 'user.name' },
    { title: 'Compétence', key: 'competence_requise.name' },
    { title: 'Niveau', key: 'level' },
    { title: 'Statut', key: 'status' },
    { title: 'Expiration', key: 'expiry_date' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function openForm (item = null) {
    selectedItem.value = item
    showForm.value = true
  }

  const editItem = item => openForm(item)

  async function deleteItem (item) {
    if (confirm('Supprimer cette habilitation ?')) {
      await habilitationStore.remove(item.id)
    }
  }

  function onSaved () {
    showForm.value = false
  }

  function getStatusColor (status) {
    const colors = {
      active: 'success',
      expired: 'error',
      suspended: 'warning',
    }
    return colors[status] || 'default'
  }

  function getExpiryClass (date) {
    if (!date) return ''
    const days = Math.ceil((new Date(date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
    if (days < 0) return 'text-error'
    if (days <= 30) return 'text-warning'
    return ''
  }

  function formatDate (date) {
    return date ? new Date(date).toLocaleDateString('fr-FR') : 'N/A'
  }

  onMounted(async () => {
    await Promise.all([
      habilitationStore.fetchAll(),
      userStore.fetchUsers(),
      competenceStore.fetchCompetencesRequises(),
    ])
  })
</script>
