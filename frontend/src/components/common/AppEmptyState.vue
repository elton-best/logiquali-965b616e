<template>
  <div class="app-empty-state">
    <div class="empty-icon">
      <component :is="icon" :size="64" />
    </div>
    <h3 class="empty-title">{{ title }}</h3>
    <p v-if="description" class="empty-description">{{ description }}</p>
    <div v-if="$slots.action" class="empty-action">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Inbox } from 'lucide-vue-next'

  interface Props {
    title: string
    description?: string
    icon?: any
  }

  withDefaults(defineProps<Props>(), {
    icon: Inbox,
  })
</script>

<style scoped>
.app-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.empty-icon {
  color: #D1D5DB;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.empty-description {
  font-size: 14px;
  color: #6B7280;
  margin: 0 0 24px 0;
  max-width: 400px;
}

.empty-action {
  display: flex;
  gap: 12px;
}
</style>
