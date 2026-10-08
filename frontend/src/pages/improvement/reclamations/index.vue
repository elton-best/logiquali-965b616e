<template>
  <div class="reclamations-page">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Réclamations</h1>
    </div>

    <ReclamationList @view="viewReclamation" />
  </div>
</template>

<script setup lang="ts">
  import ReclamationList from '@/components/improvement/Reclamation/ReclamationList.vue'

  const viewReclamation = (rec: any) => console.log('View:', rec)
</script>
