<template>
  <v-container class="py-6" max-width="900">
    <div class="d-flex align-center mb-6 gap-3">
      <v-btn icon size="small" variant="text" @click="router.back()">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <div>
        <h1 class="text-h5 font-weight-bold">Recyclage des codes</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Gérez les codes libérés et disponibles pour réutilisation
        </p>
      </div>
    </div>

    <CodeRecyclingPanel />
  </v-container>
</template>

<script setup lang="ts">
  import { useRouter } from 'vue-router'
  import CodeRecyclingPanel from '@/components/documents/CodeRecyclingPanel.vue'

  const router = useRouter()
</script>
