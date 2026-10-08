<template>
  <ClientALayout current-page="processes">
    <v-container class="pa-6" fluid>
      <!-- Navigation Header -->
      <div class="d-flex flex-wrap justify-space-between align-center mb-6 gap-3">
        <div class="d-flex align-center gap-3">
          <v-btn
            icon="mdi-arrow-left"
            size="small"
            variant="text"
            @click="router.push('/company/context/management-system')"
          />
          <div>
            <h1 class="text-h4 font-weight-bold text-slate-800">Cartographie des Processus</h1>
            <p class="text-body-1 text-medium-emphasis mb-0">
              Visualisez l'architecture globale de vos processus et leurs interactions dynamiques (ISO 9001 §4.4)
            </p>
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <v-btn
            color="primary"
            prepend-icon="mdi-clipboard-text-search-outline"
            variant="tonal"
            @click="router.push('/company/performance/surveillance/process-review')"
          >
            Revues de Processus
          </v-btn>
          <v-btn
            color="secondary"
            prepend-icon="mdi-format-list-bulleted"
            variant="outlined"
            @click="router.push('/company/context/management-system')"
          >
            Liste des Processus
          </v-btn>
        </div>
      </div>

      <!-- Main Cartography Viewer Component -->
      <ProcessCartographyViewer
        :process-id="targetProcessId"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcessCartographyViewer from '@/modules/clienta/components/processes/ProcessCartographyViewer.vue'

  const route = useRoute()
  const router = useRouter()

  const targetProcessId = computed(() => {
    const raw = route.query.process_id
    return raw ? Number(raw) : null
  })
</script>
