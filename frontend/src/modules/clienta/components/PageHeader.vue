<template>
  <div class="page-header d-flex align-center justify-space-between mb-6">
    <div class="d-flex align-center">
      <v-icon v-if="icon" class="mr-4" :color="iconColor" size="40">{{ icon }}</v-icon>
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">{{ title }}</h1>
        <p v-if="subtitle" class="text-body-1 text-medium-emphasis mb-0">{{ subtitle }}</p>
        <slot name="subtitle" />
      </div>
    </div>
    <div class="page-header-actions d-flex gap-2">
      <slot name="actions">
        <v-btn
          v-if="primaryAction"
          :color="primaryActionColor"
          :prepend-icon="primaryActionIcon"
          :variant="primaryActionVariant"
          @click="$emit('primary-action')"
        >
          {{ primaryAction }}
        </v-btn>
        <v-btn
          v-if="secondaryAction"
          :color="secondaryActionColor"
          :prepend-icon="secondaryActionIcon"
          :variant="secondaryActionVariant"
          @click="$emit('secondary-action')"
        >
          {{ secondaryAction }}
        </v-btn>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    title: string
    subtitle?: string
    icon?: string
    iconColor?: string
    primaryAction?: string
    primaryActionIcon?: string
    primaryActionColor?: string
    primaryActionVariant?: 'flat' | 'outlined' | 'text' | 'elevated' | 'tonal' | 'plain'
    secondaryAction?: string
    secondaryActionIcon?: string
    secondaryActionColor?: string
    secondaryActionVariant?: 'flat' | 'outlined' | 'text' | 'elevated' | 'tonal' | 'plain'
  }

  withDefaults(defineProps<Props>(), {
    iconColor: 'primary',
    primaryActionColor: 'primary',
    primaryActionVariant: 'flat',
    secondaryActionColor: 'default',
    secondaryActionVariant: 'outlined',
  })

  defineEmits<{
    (e: 'primary-action' | 'secondary-action'): void
  }>()
</script>

<style scoped>
.page-header {
  flex-wrap: wrap;
  gap: 16px;
}

.page-header-actions {
  flex-wrap: wrap;
  justify-content: flex-end;
}

@media (max-width: 900px) {
  .page-header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 600px) {
  .page-header-actions :deep(.v-btn) {
    flex: 1 1 200px;
  }
}
</style>
