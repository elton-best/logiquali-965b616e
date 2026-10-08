<script setup lang="ts">
  import { onMounted } from 'vue'
  import { useRouter } from 'vue-router'

  const router = useRouter()

  onMounted(() => {
    router.replace('/company/performance/audits')
  })
</script>

<template>
  <div class="pa-6 text-center">
    Redirection vers le module réel des audits internes...
  </div>
</template>
