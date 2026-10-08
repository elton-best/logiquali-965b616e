<template>
  <ClientALayout current-page="leadership">
    <div class="leadership-page">
      <div class="content-shell">
        <v-alert
          v-if="blockingMessage"
          color="warning"
          icon="mdi-alert-circle-outline"
          variant="tonal"
        >
          <v-alert-title>Action requise</v-alert-title>
          {{ blockingMessage }}
        </v-alert>
        <slot />
      </div>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRoute } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { getBlockingMessage } from '@/utils/blockingAccess'

  const route = useRoute()
  const blockingMessage = computed(() => getBlockingMessage(
    typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
  ))
</script>

<style scoped>
.leadership-page {
  background: #f8fafc;
  min-height: 100vh;
  padding: 32px 24px;
}

.content-shell {
  max-width: 1600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

@media (max-width: 768px) {
  .leadership-page { padding: 16px; }
}
</style>
