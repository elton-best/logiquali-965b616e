<template>
  <v-container class="py-6" max-width="860">
    <div class="d-flex align-center mb-6 gap-3">
      <v-btn icon size="small" variant="text" @click="router.back()">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <div>
        <h1 class="text-h5 font-weight-bold">Import de documents</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Importez vos documents en masse via un fichier Excel ou CSV
        </p>
      </div>
    </div>

    <DocumentImportWizard
      :sites="sites"
      @close="router.push('/company/documents')"
      @completed="onCompleted"
    />
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { apiClient } from '@/api/client'
  import DocumentImportWizard from '@/components/documents/DocumentImportWizard.vue'

  const router = useRouter()
  const sites = ref<{ id: number, name: string }[]>([])

  onMounted(async () => {
    try {
      const res = await apiClient.get('/api/v1/sites')
      sites.value = (res.data?.data ?? res.data ?? []).map((s: any) => ({
        id: s.id,
        name: s.name ?? s.nom,
      }))
    } catch {
    // silencieux — le wizard affichera un select vide
    }
  })

  function onCompleted () {
  // Optionnel : notification globale déjà gérée par le wizard
  }
</script>
