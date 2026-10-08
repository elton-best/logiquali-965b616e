<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6 process-review-entry" fluid>
      <PageHeader
        icon="mdi-clipboard-text-search-outline"
        subtitle="Sélectionnez un processus et démarrez la revue par sections."
        title="Revue Processus"
      />

      <v-card class="mt-4 mb-4" rounded="xl" variant="outlined">
        <v-card-text class="d-flex flex-wrap align-center justify-space-between ga-3">
          <div>
            <div class="text-overline text-medium-emphasis">Titre dynamique</div>
            <div class="text-h6 font-weight-bold">
              {{ selectedProcess ? `Revue de : ${selectedProcess.title}` : 'Revue de : [Sélectionnez un processus]' }}
            </div>
          </div>
          <div class="d-flex ga-2">
            <v-btn
              :disabled="isLoading"
              prepend-icon="mdi-refresh"
              variant="tonal"
              @click="loadProcesses"
            >
              Actualiser
            </v-btn>
            <v-btn
              color="primary"
              :disabled="!selectedProcess || isLoading"
              prepend-icon="mdi-play-circle-outline"
              @click="openSelectedReview"
            >
              Ouvrir la revue
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <v-alert
        v-if="errorMessage"
        class="mb-4"
        density="comfortable"
        type="error"
        variant="tonal"
      >
        {{ errorMessage }}
      </v-alert>

      <v-row v-if="isLoading" dense>
        <v-col cols="12" md="4">
          <v-skeleton-loader type="card" />
        </v-col>
        <v-col cols="12" md="4">
          <v-skeleton-loader type="card" />
        </v-col>
        <v-col cols="12" md="4">
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>

      <v-alert
        v-else-if="processes.length === 0"
        density="comfortable"
        icon="mdi-information-outline"
        type="info"
        variant="tonal"
      >
        Aucun processus disponible pour ce site.
      </v-alert>

      <ProcessFamilyBoard
        v-else
        :families="families"
        :selected-process-id="selectedProcess?.id || null"
        @select="onSelectProcess"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ProcessFamilyBoard from '@/modules/clienta/pages/performance/surveillance/process-review/components/ProcessFamilyBoard.vue'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  interface ProcessItem {
    id: number
    title: string
    code: string
    category: string
  }

  const router = useRouter()
  const authStore = useAuthStore()

  const processes = ref<ProcessItem[]>([])
  const selectedProcess = ref<ProcessItem | null>(null)
  const isLoading = ref(false)
  const errorMessage = ref('')

  const families = computed(() => {
    const management = processes.value.filter(p => ['pilotage', 'mesure_amelioration'].includes(String(p.category || '').toLowerCase()))
    const realization = processes.value.filter(p => ['operationnel'].includes(String(p.category || '').toLowerCase()))
    const support = processes.value.filter(p => ['support'].includes(String(p.category || '').toLowerCase()))

    return [
      { key: 'management', label: 'Famille Management', color: '#7c3aed', items: management },
      { key: 'realization', label: 'Famille Réalisation', color: '#0d9488', items: realization },
      { key: 'support', label: 'Famille Support', color: '#1d4ed8', items: support },
    ]
  })

  function onSelectProcess (process: ProcessItem) {
    selectedProcess.value = process
  }

  function openSelectedReview () {
    if (!selectedProcess.value) return
    router.push(`/company/performance/surveillance/process-review/${selectedProcess.value.id}`)
  }

  async function loadProcesses () {
    isLoading.value = true
    errorMessage.value = ''
    try {
      const siteId = authStore.currentSiteId || Number(localStorage.getItem('current_site_id') || 0)
      const response = await processService.getProcesses(siteId ? { site_id: siteId } : {})
      processes.value = (response.data || []).map((p: any) => ({
        id: Number(p.id),
        title: String(p.title || p.name || `Processus #${p.id}`),
        code: String(p.code || p.ref || ''),
        category: String(p.category || ''),
      }))
      if (selectedProcess.value && !processes.value.some(p => p.id === selectedProcess.value?.id)) {
        selectedProcess.value = null
      }
    } catch {
      processes.value = []
      selectedProcess.value = null
      errorMessage.value = 'Impossible de charger les processus. Vérifiez votre connexion ou réessayez.'
    } finally {
      isLoading.value = false
    }
  }

  onMounted(async () => {
    await loadProcesses()
  })
</script>

<style scoped>
.process-review-entry {
  background:
    radial-gradient(circle at 92% 6%, rgba(14, 165, 233, 0.08), transparent 38%),
    radial-gradient(circle at 10% 24%, rgba(124, 58, 237, 0.08), transparent 38%);
}
</style>
