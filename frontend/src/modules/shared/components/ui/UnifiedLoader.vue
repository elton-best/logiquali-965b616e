<template>
  <div :class="['unified-loader', `unified-loader--${variant}`]">
    <div class="unified-loader__body">
      <v-progress-circular
        class="unified-loader__spinner"
        color="primary"
        indeterminate
        :size="spinnerSize"
        :width="spinnerWidth"
      />

      <div class="unified-loader__text">
        <div class="unified-loader__title">{{ title }}</div>
        <div v-if="description" class="unified-loader__description">
          {{ description }}
        </div>
      </div>

      <div v-if="showSkeleton" class="unified-loader__skeleton">
        <v-skeleton-loader class="unified-loader__skeleton-item" type="text" />
        <v-skeleton-loader class="unified-loader__skeleton-item" type="text" />
        <v-skeleton-loader
          class="unified-loader__skeleton-item unified-loader__skeleton-item--short"
          type="text"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    variant?: 'global' | 'local' | 'inline' | 'spinner'
    title?: string
    description?: string
    showSkeleton?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'local',
    title: 'Chargement en cours...',
    description: '',
    showSkeleton: true,
  })

  const spinnerSize = computed(() => {
    if (props.variant === 'inline' || props.variant === 'spinner') {
      return 20
    }
    if (props.variant === 'global') {
      return 64
    }
    return 40
  })

  const spinnerWidth = computed(() => {
    return props.variant === 'inline' || props.variant === 'spinner' ? 2 : 4
  })
</script>

<style scoped>
.unified-loader {
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 16px;
  background: linear-gradient(
    160deg,
    rgba(255, 255, 255, 0.95),
    rgba(248, 250, 252, 0.95)
  );
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
  backdrop-filter: blur(4px);
}

.unified-loader--inline {
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
}

.unified-loader__body {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 18px 20px;
  text-align: center;
}

.unified-loader--global .unified-loader__body {
  min-width: 260px;
  padding: 28px 26px;
}

.unified-loader--inline .unified-loader__body {
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0;
}

.unified-loader__spinner {
  filter: drop-shadow(0 4px 10px rgba(59, 130, 246, 0.32));
}

.unified-loader__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.unified-loader__title {
  font-size: 0.95rem;
  font-weight: 600;
  color: rgb(15, 23, 42);
}

.unified-loader__description {
  font-size: 0.78rem;
  color: rgba(30, 41, 59, 0.72);
}

.unified-loader__skeleton {
  width: 100%;
  max-width: 220px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.unified-loader__skeleton-item :deep(.v-skeleton-loader__text) {
  width: 100%;
}

.unified-loader__skeleton-item--short :deep(.v-skeleton-loader__text) {
  width: 65%;
}

.unified-loader--inline .unified-loader__skeleton {
  display: none;
}

.unified-loader--inline .unified-loader__title {
  font-size: 0.82rem;
}
</style>
