<template>
  <v-app>
    <router-view />
    <CookieConsentBanner />
    <ClientALoadingOverlay
      :active="globalLoader.isModalLoading"
      :message="globalLoader.message"
    />
    <AppNotification />
    <AppToast />
  </v-app>
</template>

<script lang="ts" setup>
  import AppNotification from '@/components/AppNotification.vue'
  import ClientALoadingOverlay from '@/modules/clienta/components/ClientALoadingOverlay.vue'
  import AppToast from '@/modules/shared/components/ui/AppToast.vue'
  import CookieConsentBanner from '@/modules/shared/components/ui/CookieConsentBanner.vue'
  import { useGlobalLoaderStore } from '@/stores/globalLoader'

  const globalLoader = useGlobalLoaderStore()
</script>
