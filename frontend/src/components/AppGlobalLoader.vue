<template>
  <v-fade-transition>
    <div v-if="isLoading">
      <v-overlay
        class="app-global-loader"
        :model-value="isLoading"
        persistent
        scrim="rgba(15, 23, 42, 0.24)"
      >
        <UnifiedLoader
          description="Veuillez patienter un instant"
          :show-skeleton="true"
          :title="message"
          variant="global"
        />
      </v-overlay>
    </div>
  </v-fade-transition>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { useGlobalLoaderStore } from '@/stores/globalLoader'

  const loaderStore = useGlobalLoaderStore()
  const isLoading = computed(() => loaderStore.isLoading)
  const message = computed(() => loaderStore.message)
</script>

<style scoped>
.app-global-loader {
  z-index: 5000 !important;
}

.app-global-loader :deep(.v-overlay__content) {
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
