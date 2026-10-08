<!-- App.vue integration example -->
<template>
  <v-app>
    <!-- Your existing layout with NotificationCenter already integrated -->
    <v-app-bar>
      <!-- Existing content -->
      <v-spacer />

      <!-- NotificationCenter already exists in layout -->
      <NotificationCenter />

      <!-- Existing user menu, etc. -->
    </v-app-bar>

    <v-main>
      <!-- Your existing router-view -->
      <router-view />

      <!-- AppNotification already exists for toast notifications -->
      <AppNotification />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { onMounted } from 'vue'
  import AppNotification from '@/components/AppNotification.vue'
  import NotificationCenter from '@/components/NotificationCenter.vue'
  import { useWebSocket } from '@/composables/useWebSocket'

  // Initialize WebSocket connection for real-time updates
  const { connect } = useWebSocket()

  onMounted(() => {
    connect()
  })
</script>
