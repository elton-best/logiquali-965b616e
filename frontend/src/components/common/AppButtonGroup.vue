<template>
  <div :aria-label="ariaLabel" class="app-button-group" :class="groupClasses" role="group">
    <slot />
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    vertical?: boolean
    attached?: boolean
    ariaLabel?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    vertical: false,
    attached: true,
  })

  const groupClasses = computed(() => ({
    'button-group-vertical': props.vertical,
    'button-group-attached': props.attached,
  }))
</script>

<style scoped>
.app-button-group {
  display: inline-flex;
  gap: 8px;
}

.app-button-group.button-group-vertical {
  flex-direction: column;
}

.app-button-group.button-group-attached {
  gap: 0;
}

.app-button-group.button-group-attached :deep(.app-button),
.app-button-group.button-group-attached :deep(.app-icon-button) {
  border-radius: 0;
  margin-left: -2px;
}

.app-button-group.button-group-attached :deep(.app-button:first-child),
.app-button-group.button-group-attached :deep(.app-icon-button:first-child) {
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
  margin-left: 0;
}

.app-button-group.button-group-attached :deep(.app-button:last-child),
.app-button-group.button-group-attached :deep(.app-icon-button:last-child) {
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
}

.app-button-group.button-group-attached.button-group-vertical :deep(.app-button),
.app-button-group.button-group-attached.button-group-vertical :deep(.app-icon-button) {
  margin-left: 0;
  margin-top: -2px;
}

.app-button-group.button-group-attached.button-group-vertical :deep(.app-button:first-child),
.app-button-group.button-group-attached.button-group-vertical :deep(.app-icon-button:first-child) {
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  border-bottom-left-radius: 0;
  margin-top: 0;
}

.app-button-group.button-group-attached.button-group-vertical :deep(.app-button:last-child),
.app-button-group.button-group-attached.button-group-vertical :deep(.app-icon-button:last-child) {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
  border-top-right-radius: 0;
}
</style>
