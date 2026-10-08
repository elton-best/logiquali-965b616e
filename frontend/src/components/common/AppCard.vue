<template>
  <div class="app-card" :class="cardClasses">
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">
        <h3 class="card-title">{{ title }}</h3>
      </slot>
    </div>
    <div class="card-body" :class="{ 'card-body-padded': !noPadding }">
      <slot />
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    title?: string
    noPadding?: boolean
    hoverable?: boolean
    bordered?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    noPadding: false,
    hoverable: false,
    bordered: true,
  })

  const cardClasses = computed(() => ({
    'card-hoverable': props.hoverable,
    'card-bordered': props.bordered,
  }))
</script>

<style scoped>
.app-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
  transition: all 150ms;
}

.app-card.card-bordered {
  border: 1px solid #E5E7EB;
}

.app-card.card-hoverable {
  cursor: pointer;
}

.app-card.card-hoverable:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #E5E7EB;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  color: #1F2937;
  margin: 0;
}

.card-body {
  flex: 1;
}

.card-body-padded {
  padding: 24px;
}

.card-footer {
  padding: 16px 24px;
  border-top: 1px solid #E5E7EB;
  background: #F9FAFB;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

@media (prefers-reduced-motion: reduce) {
  .app-card { transition: none !important; }
  .app-card.card-hoverable:hover { transform: none !important; }
}
</style>
