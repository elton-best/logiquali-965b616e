<template>
  <div class="skeleton-loader" :class="{ 'skeleton-dark': isDark }">
    <!-- Card Skeleton -->
    <div v-if="type === 'card'" class="skeleton-card">
      <div v-for="i in count" :key="`card-${i}`" class="skeleton-card-item">
        <div class="skeleton skeleton-rect skeleton-image" />
        <div class="skeleton-card-body">
          <div class="skeleton skeleton-text skeleton-title" />
          <div class="skeleton skeleton-text skeleton-subtitle" />
          <div class="skeleton skeleton-text" style="width: 60%" />
        </div>
      </div>
    </div>

    <!-- Table Skeleton -->
    <div v-else-if="type === 'table'" class="skeleton-table">
      <div class="skeleton-table-header">
        <div v-for="col in columns" :key="`header-${col}`" class="skeleton skeleton-text" />
      </div>
      <div v-for="row in count" :key="`row-${row}`" class="skeleton-table-row">
        <div v-for="col in columns" :key="`cell-${row}-${col}`" class="skeleton skeleton-text" />
      </div>
    </div>

    <!-- List Skeleton -->
    <div v-else-if="type === 'list'" class="skeleton-list">
      <div v-for="i in count" :key="`list-${i}`" class="skeleton-list-item">
        <div class="skeleton skeleton-circle skeleton-avatar" />
        <div class="skeleton-list-content">
          <div class="skeleton skeleton-text skeleton-title" />
          <div class="skeleton skeleton-text" style="width: 70%" />
        </div>
      </div>
    </div>

    <!-- Text Lines Skeleton -->
    <div v-else-if="type === 'text'" class="skeleton-text-lines">
      <div
        v-for="i in count"
        :key="`text-${i}`"
        class="skeleton skeleton-text"
        :style="{ width: getTextWidth(i) }"
      />
    </div>

    <!-- Custom/Default Skeleton -->
    <div v-else>
      <slot>
        <div v-for="i in count" :key="`default-${i}`" class="skeleton skeleton-rect mb-4" />
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useTheme } from 'vuetify'

  /**
   * Composant de chargement skeleton avec effet shimmer
   *
   * @example
   * // Card skeleton
   * <SkeletonLoader type="card" :count="3" />
   *
   * @example
   * // Table skeleton
   * <SkeletonLoader type="table" :count="5" :columns="4" />
   *
   * @example
   * // List skeleton
   * <SkeletonLoader type="list" :count="10" />
   *
   * @example
   * // Text lines skeleton
   * <SkeletonLoader type="text" :count="5" />
   *
   * @example
   * // Custom skeleton with slot
   * <SkeletonLoader>
   *   <div class="skeleton skeleton-circle" style="width: 80px; height: 80px"></div>
   * </SkeletonLoader>
   */

  interface Props {
    /** Type de skeleton à afficher */
    type?: 'card' | 'table' | 'list' | 'text' | 'custom'
    /** Nombre d'éléments à afficher */
    count?: number
    /** Nombre de colonnes pour le type table */
    columns?: number
  }

  const _props = withDefaults(defineProps<Props>(), {
    type: 'card',
    count: 3,
    columns: 3,
  })

  const theme = useTheme()
  const isDark = computed(() => theme.global.current.value.dark)

  function getTextWidth (index: number) {
    const widths = ['100%', '90%', '80%', '95%', '85%', '100%', '70%']
    return widths[index % widths.length]
  }
</script>

<style scoped>
.skeleton-loader {
  width: 100%;
}

/* Base skeleton styles */
.skeleton {
  background: linear-gradient(
    90deg,
    rgba(var(--v-theme-surface-variant), 0.4) 0%,
    rgba(var(--v-theme-surface-variant), 0.6) 50%,
    rgba(var(--v-theme-surface-variant), 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 8px;
}

.skeleton-dark .skeleton {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.1) 50%,
    rgba(255, 255, 255, 0.05) 100%
  );
  background-size: 200% 100%;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Shape variants */
.skeleton-rect {
  height: 200px;
}

.skeleton-circle {
  border-radius: 50%;
}

.skeleton-text {
  height: 16px;
  margin-bottom: 12px;
}

.skeleton-title {
  height: 20px;
  width: 80%;
  margin-bottom: 8px;
}

.skeleton-subtitle {
  height: 16px;
  width: 60%;
  margin-bottom: 12px;
}

/* Card Skeleton */
.skeleton-card {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.skeleton-card-item {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 12px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
}

.skeleton-image {
  height: 180px;
  border-radius: 0;
}

.skeleton-card-body {
  padding: 16px;
}

/* Table Skeleton */
.skeleton-table {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 12px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
}

.skeleton-table-header {
  display: grid;
  grid-template-columns: repeat(var(--columns, 3), 1fr);
  gap: 16px;
  padding: 16px;
  background: rgb(var(--v-theme-surface-variant));
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
}

.skeleton-table-row {
  display: grid;
  grid-template-columns: repeat(var(--columns, 3), 1fr);
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}

.skeleton-table-row:last-child {
  border-bottom: none;
}

.skeleton-table .skeleton-text {
  margin-bottom: 0;
}

/* List Skeleton */
.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-list-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}

.skeleton-list-content {
  flex: 1;
  min-width: 0;
}

/* Text Lines Skeleton */
.skeleton-text-lines {
  display: flex;
  flex-direction: column;
}

/* Responsive */
@media (max-width: 768px) {
  .skeleton-card {
    grid-template-columns: 1fr;
  }

  .skeleton-table-header,
  .skeleton-table-row {
    grid-template-columns: 1fr;
  }
}

/* Utilities */
.mb-4 {
  margin-bottom: 16px;
}
</style>
