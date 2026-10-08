<template>
  <div class="form-section">
    <div v-if="title || $slots.title" class="form-section__header">
      <slot name="title">
        <h3 class="form-section__title">{{ title }}</h3>
      </slot>
      <p v-if="description" class="form-section__description">{{ description }}</p>
    </div>

    <div class="form-section__content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    title?: string
    description?: string
  }

  defineProps<Props>()
</script>

<style scoped>
.form-section {
  @apply mb-8;
}

.form-section__header {
  @apply mb-6 pb-4 border-b border-gray-200;
}

.form-section__title {
  @apply text-xl font-semibold text-gray-900;
}

.form-section__description {
  @apply mt-2 text-sm text-gray-600;
}

.form-section__content {
  @apply space-y-4;
}
</style>
