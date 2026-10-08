<template>
  <div class="stakeholder-list pa-4">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2" size="large">mdi-account-group</v-icon>
        <span class="text-h5">Registre des Parties Intéressées</span>
        <v-spacer />
        <v-btn color="primary" @click="createNew">
          <v-icon left>mdi-plus</v-icon>
          Nouvelle Partie
        </v-btn>
      </v-card-title>

      <v-card-text>
        <!-- Filters -->
        <v-row dense>
          <v-col cols="12" md="3">
            <v-select
              v-model="typeFilter"
              clearable
              density="compact"
              :items="typeOptions"
              label="Type"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="relevanceFilter"
              clearable
              density="compact"
              :items="relevanceOptions"
              label="Pertinence"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="searchQuery"
              clearable
              density="compact"
              label="Rechercher"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn block color="success" @click="exportDocx">
              <v-icon left>mdi-file-word</v-icon>
              Export DOCX
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>

      <!-- Table -->
      <v-data-table
        class="elevation-0"
        :headers="headers"
        :items="filteredItems"
        :items-per-page="20"
        :loading="store.loading"
      >
        <template #item.type="{ item }">
          <v-chip :color="getTypeColor(item.type)" label size="small">
            {{ item.type }}
          </v-chip>
        </template>

        <template #item.relevance_degree="{ item }">
          <v-chip :color="getRelevanceColor(item.relevance_degree)" label size="small">
            {{ item.relevance_degree }}
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
    </v-card>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useStakeholderStore } from '@/stores/stakeholderStore'

  const router = useRouter()
  const store = useStakeholderStore()

  const typeFilter = ref(null)
  const relevanceFilter = ref(null)
  const searchQuery = ref('')

  const typeOptions = ['client', 'supplier', 'partner', 'regulator', 'employee', 'shareholder']
  const relevanceOptions = ['high', 'medium', 'low']

  const headers = [
    { title: 'Nom', key: 'name', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Pertinence', key: 'relevance_degree', sortable: true },
    { title: 'Besoins/Attentes', key: 'needs_expectations', sortable: false },
    { title: 'Responsable', key: 'responsible', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' as const },
  ] as const

  const filteredItems = computed(() => {
    let items = store.stakeholders

    if (typeFilter.value) {
      items = items.filter(item => item.type === typeFilter.value)
    }

    if (relevanceFilter.value) {
      items = items.filter(item => item.relevance_degree === relevanceFilter.value)
    }

    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      items = items.filter(item =>
        item.name?.toLowerCase().includes(query)
        || item.needs_expectations?.toLowerCase().includes(query),
      )
    }

    return items
  })

  function getTypeColor (type = '') {
    const colors: Record<string, string> = {
      client: 'blue',
      supplier: 'green',
      partner: 'purple',
      regulator: 'orange',
      employee: 'teal',
      shareholder: 'indigo',
    }
    const key = type
    return colors[key] || 'grey'
  }

  function getRelevanceColor (relevance = '') {
    const colors: Record<string, string> = {
      high: 'red',
      medium: 'orange',
      low: 'green',
    }
    const key = relevance
    return colors[key] || 'grey'
  }

  const createNew = () => router.push('/stakeholders/create')
  const editItem = (item: any) => router.push(`/stakeholders/${item.id}/edit`)
  async function deleteItem (item: any) {
    if (confirm(`Supprimer ${item.name}?`)) {
      await store.deleteStakeholder(item.id)
    }
  }

  async function exportDocx () {
    await store.exportStakeholdersDocx()
  }

  onMounted(() => {
    store.fetchStakeholders()
  })
</script>
