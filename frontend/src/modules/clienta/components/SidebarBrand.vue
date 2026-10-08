<template>
  <div class="pa-6 border-b sidebar-brand">
    <div class="d-flex align-center">
      <v-avatar
        class="brand-avatar"
        :class="collapsed ? 'mx-auto' : 'mr-3'"
        color="primary"
        size="48"
      >
        <v-img
          v-if="companyLogoUrl"
          cover
          :src="companyLogoUrl"
        />
        <img
          v-else
          alt="Logo BestQHSE"
          class="default-brand-mark"
          src="/branding/bestqhse-mark.png"
        >
      </v-avatar>
      <div v-if="!collapsed" class="flex-1">
        <h2 class="text-h6 font-weight-bold company-name">{{ companyName }}</h2>
        <p class="text-caption text-medium-emphasis mb-0">{{ userTitle }}</p>
      </div>
      <v-btn
        v-if="!isMobile"
        class="collapse-btn"
        icon
        size="small"
        variant="text"
        @click="$emit('toggle-collapse')"
      >
        <v-icon size="20">{{ collapsed ? 'mdi-menu-open' : 'mdi-menu' }}</v-icon>
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
  defineProps<{
    collapsed: boolean
    companyName: string
    userTitle: string
    companyLogoUrl?: string
    isMobile: boolean
  }>()

  defineEmits<{
    'toggle-collapse': []
  }>()
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.sidebar-brand {
  position: relative;
  transition: all 0.3s ease;
}

.company-name {
  transition: opacity 0.3s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.collapse-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.sidebar-brand:hover .collapse-btn {
  opacity: 1;
}

.brand-avatar {
  box-shadow: 0 8px 16px rgba(var(--v-theme-primary), 0.25);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.brand-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 12px 24px rgba(var(--v-theme-primary), 0.35);
}

.default-brand-mark {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
</style>
