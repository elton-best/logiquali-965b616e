<template>
  <v-navigation-drawer
    v-model="drawerModel"
    class="clienta-sidebar"
    :class="{ 'sidebar-collapsed': collapsed && !isMobile }"
    elevation="0"
    :permanent="!isMobile"
    :scrim="isMobile"
    style="position: fixed; height: 100vh; overflow-y: auto;"
    :temporary="isMobile"
    :width="isMobile ? 300 : (collapsed ? 72 : 340)"
  >
    <slot />
  </v-navigation-drawer>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    drawer: boolean
    collapsed: boolean
    isMobile: boolean
  }>()

  const emit = defineEmits<{
    'update:drawer': [value: boolean]
  }>()

  const drawerModel = computed({
    get: () => props.drawer,
    set: value => emit('update:drawer', value),
  })
</script>

<style scoped>
.clienta-sidebar {
  border-right: 1px solid rgba(var(--v-border-color), 0.3);
  background:
    radial-gradient(1200px 600px at -10% -20%, rgba(var(--v-theme-primary), 0.08), transparent 60%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(0, 0, 0, 0.02));
  backdrop-filter: blur(8px);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed {
  text-align: center;
}

.sidebar-collapsed :deep(.v-list-item__prepend) {
  margin-inline-end: 0 !important;
}

.sidebar-collapsed :deep(.v-list-item__append) {
  display: none;
}

.clienta-sidebar :deep(.v-list-item:hover) {
  background: transparent !important;
}

.clienta-sidebar :deep(.v-btn:hover) {
  background: transparent !important;
}

/* Hide browser tooltips (title attribute) */
.clienta-sidebar :deep([title]) {
  pointer-events: none;
}

.clienta-sidebar :deep([title]:hover)::before {
  display: none !important;
}

.clienta-sidebar::-webkit-scrollbar {
  width: 6px;
}

.clienta-sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.clienta-sidebar::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.2);
  border-radius: 3px;
}

.clienta-sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-primary), 0.3);
}

@media (max-width: 600px) {
  .clienta-sidebar {
    border-right: none;
  }
}
</style>
