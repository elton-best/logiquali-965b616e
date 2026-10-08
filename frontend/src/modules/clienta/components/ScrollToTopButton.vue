<template>
  <!-- Scroll to top button -->
  <v-btn
    v-if="show"
    aria-label="Retour en haut"
    class="scroll-to-top"
    color="primary"
    elevation="4"
    icon
    size="large"
    @click="$emit('scroll-to-top')"
  >
    <v-icon>mdi-chevron-up</v-icon>
  </v-btn>
</template>

<script setup lang="ts">
  defineProps<{
    show?: boolean
  }>()

  defineEmits<{
    'scroll-to-top': []
  }>()
</script>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-to-top:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(var(--v-theme-primary), 0.3) !important;
}

.scroll-to-top:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
</style>
