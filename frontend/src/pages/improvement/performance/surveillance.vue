<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-chart-box-outline" title="Surveillance, Mesure, Analyse et Évaluation">
        <template #subtitle>
          Suivi des indicateurs de performance et analyse des résultats (M13-D3/4/6)
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouveau suivi
          </v-btn>
        </template>
      </PageHeader>

      <v-tabs v-model="activeTab" class="mt-6" color="primary">
        <v-tab value="indicators">Indicateurs</v-tab>
        <v-tab value="satisfaction">Satisfaction Client</v-tab>
        <v-tab value="analysis">Analyses</v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="mt-6">
        <!-- Indicateurs -->
        <v-window-item value="indicators">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="indicatorHeaders"
                :items="indicators"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewIndicator(item)" />
                  <v-btn icon="mdi-pencil" size="small" variant="text" @click="editIndicator(item)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Satisfaction Client -->
        <v-window-item value="satisfaction">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="satisfactionHeaders"
                :items="satisfactions"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.score`]="{ item }">
                  <v-chip :color="getScoreColor(item.score)" size="small" variant="tonal">
                    {{ item.score }}/5
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewSatisfaction(item)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Analyses -->
        <v-window-item value="analysis">
          <v-row>
            <v-col cols="12" md="6">
              <v-card rounded="xl">
                <v-card-title class="pa-6">Tendances</v-card-title>
                <v-card-text>
                  <div class="text-center pa-8 text-grey">
                    Graphique des tendances
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="6">
              <v-card rounded="xl">
                <v-card-title class="pa-6">Synthèse</v-card-title>
                <v-card-text>
                  <v-list>
                    <v-list-item v-for="item in synthese" :key="item.label">
                      <template #prepend>
                        <v-icon :color="item.color">{{ item.icon }}</v-icon>
                      </template>
                      <v-list-item-title>{{ item.label }}</v-list-item-title>
                      <v-list-item-subtitle>{{ item.value }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>
      </v-window>

      <v-dialog v-model="showDialog" max-width="800">
        <v-card rounded="xl">
          <v-card-title class="pa-6">{{ editingId ? 'Modifier' : 'Créer' }} un suivi</v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12">
                <v-text-field v-model="form.title" label="Titre" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.type"
                  :items="['Indicateur', 'Satisfaction', 'Analyse']"
                  label="Type"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.date" label="Date" type="date" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.description" label="Description" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="save">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { onMounted, reactive, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'

  const activeTab = ref('indicators')
  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const editingId = ref<number | null>(null)

  const indicators = ref<any[]>([])
  const satisfactions = ref<any[]>([])

  const indicatorHeaders = [
    { title: 'Indicateur', key: 'name' },
    { title: 'Valeur', key: 'value' },
    { title: 'Objectif', key: 'target' },
    { title: 'Statut', key: 'status' },
    { title: 'Date', key: 'date' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const satisfactionHeaders = [
    { title: 'Client', key: 'client' },
    { title: 'Score', key: 'score' },
    { title: 'Date', key: 'date' },
    { title: 'Commentaire', key: 'comment' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const synthese = ref([
    { label: 'Indicateurs suivis', value: '12', icon: 'mdi-chart-line', color: 'primary' },
    { label: 'Objectifs atteints', value: '8/12', icon: 'mdi-target', color: 'success' },
    { label: 'Satisfaction moyenne', value: '4.2/5', icon: 'mdi-star', color: 'warning' },
  ])

  const form = reactive({
    title: '',
    type: '',
    date: '',
    description: '',
  })

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      'Atteint': 'success',
      'En cours': 'warning',
      'Non atteint': 'error',
    }
    return colors[status] || 'grey'
  }

  function getScoreColor (score: number) {
    if (score >= 4) return 'success'
    if (score >= 3) return 'warning'
    return 'error'
  }

  function openCreateDialog () {
    editingId.value = null
    Object.assign(form, { title: '', type: '', date: '', description: '' })
    showDialog.value = true
  }

  function editIndicator (item: any) {
    editingId.value = item.id
    Object.assign(form, item)
    showDialog.value = true
  }

  function viewIndicator (item: any) {
    console.log('View:', item)
  }

  function viewSatisfaction (item: any) {
    console.log('View satisfaction:', item)
  }

  async function save () {
    saving.value = true
    try {
      // API call here
      await new Promise(resolve => setTimeout(resolve, 1000))
      showDialog.value = false
    } finally {
      saving.value = false
    }
  }

  onMounted(() => {
    // Load data
  })
</script>
