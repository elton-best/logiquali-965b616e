<template>
  <div class="app-divider" :class="dividerClasses" role="separator">
    <span v-if="label || $slots.default" class="divider-label">
      <slot>{{ label }}</slot>
    </span>
  </div>
</template>

<script setup lang="ts">
  import { computed, useSlots } from 'vue'

  interface Props {
    label?: string
    vertical?: boolean
    dashed?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    vertical: false,
    dashed: false,
  })

  const slots = useSlots()

  const dividerClasses = computed(() => ({
    'divider-vertical': props.vertical,
    'divider-dashed': props.dashed,
    'divider-labeled': props.label || slots.default,
  }))
</script>

<style scoped>
.app-divider {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  border-top: 1px solid #e5e7eb;
  margin: 16px 0;
}

.app-divider.divider-vertical {
  width: auto;
  height: 100%;
  border-top: none;
  border-left: 1px solid #e5e7eb;
  margin: 0 16px;
}

.app-divider.divider-dashed {
  border-style: dashed;
}

.divider-label {
  padding: 0 12px;
  background: white;
  font-size: 14px;
  color: #6b7280;
  white-space: nowrap;
}
</style>
