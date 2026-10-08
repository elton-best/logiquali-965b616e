<template>
  <div class="context-list pa-4">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2" size="large">mdi-chart-box</v-icon>
        <span class="text-h5">Analyses Contexte (SWOT / PESTEL)</span>
        <v-spacer />
        <v-btn-group>
          <v-btn color="primary" @click="createSwot">
            <v-icon left>mdi-chart-box-outline</v-icon>
            SWOT
          </v-btn>
          <v-btn color="secondary" @click="createPestel">
            <v-icon left>mdi-radar</v-icon>
            PESTEL
          </v-btn>
        </v-btn-group>
      </v-card-title>

      <v-card-text>
        <!-- Filters -->
        <v-row dense>
          <v-col cols="12" md="3">
            <v-select
              v-model="typeFilter"
              clearable
              density="compact"
              :items="['swot', 'pestel']"
              label="Type d'analyse"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="yearFilter"
              clearable
              density="compact"
              :items="yearOptions"
              label="Année"
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
        </v-row>
      </v-card-text>

      <!-- Cards Grid -->
      <v-container>
        <v-row>
          <v-col
            v-for="context in filteredContexts"
            :key="context.id"
            cols="12"
            lg="4"
            md="6"
          >
            <v-card :color="context.type === 'swot' ? 'blue-lighten-5' : 'green-lighten-5'" hover>
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-2" :color="context.type === 'swot' ? 'blue' : 'green'">
                  {{ context.type === 'swot' ? 'mdi-chart-box-outline' : 'mdi-radar' }}
                </v-icon>
                {{ context.title }}
              </v-card-title>

              <v-card-subtitle>
                <v-chip class="mr-2" size="small">{{ context.year }}</v-chip>
                <v-chip :color="context.type === 'swot' ? 'blue' : 'green'" size="small">
                  {{ context.type?.toUpperCase() }}
                </v-chip>
              </v-card-subtitle>

              <v-card-text>
                <p class="text-caption">{{ context.description }}</p>
              </v-card-text>

              <v-card-actions>
                <v-btn size="small" @click="viewContext(context)">
                  <v-icon left>mdi-eye</v-icon>
                  Voir
                </v-btn>
                <v-btn size="small" @click="editContext(context)">
                  <v-icon left>mdi-pencil</v-icon>
                  Éditer
                </v-btn>
                <v-spacer />
                <v-btn color="error" icon size="small" @click="deleteContext(context)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-if="filteredContexts.length === 0">
          <v-col class="text-center py-8" cols="12">
            <v-icon color="grey-lighten-2" size="64">mdi-chart-box-outline</v-icon>
            <p class="text-h6 text-grey mt-4">Aucune analyse trouvée</p>
            <p class="text-caption text-grey">Créez votre première analyse SWOT ou PESTEL</p>
          </v-col>
        </v-row>
      </v-container>
    </v-card>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useContextStore } from '@/stores/contextStore'

  const router = useRouter()
  const store = useContextStore()

  const typeFilter = ref(null)
  const yearFilter = ref(null)
  const searchQuery = ref('')

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - i)
  })

  const normalizedContexts = computed(() => {
    return (store.contexts as any[]).map((item: any) => ({
      ...item,
      type: item.type || (item.swot_data ? 'swot' : 'pestel'),
      title: item.title || item.name || `Analyse ${item.year || ''}`.trim(),
      description: item.description || '',
    }))
  })

  const filteredContexts = computed(() => {
    let items = normalizedContexts.value

    if (typeFilter.value) {
      items = items.filter(item => item.type === typeFilter.value)
    }

    if (yearFilter.value) {
      items = items.filter(item => item.year === yearFilter.value)
    }

    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      items = items.filter(item =>
        item.title?.toLowerCase().includes(query)
        || item.description?.toLowerCase().includes(query),
      )
    }

    return items
  })

  const createSwot = () => router.push('/contexts/create?type=swot')
  const createPestel = () => router.push('/contexts/create?type=pestel')
  const viewContext = (item: any) => router.push(`/contexts/${item.id}`)
  const editContext = (item: any) => router.push(`/contexts/${item.id}/edit`)
  async function deleteContext (item: any) {
    if (confirm(`Supprimer l'analyse "${item.title}"?`)) {
      await store.deleteContext(item.id)
    }
  }

  onMounted(() => {
    store.fetchContexts()
  })
</script>
