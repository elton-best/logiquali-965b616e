<template>
  <div
    aria-label="Chargement en cours"
    class="app-widget-skeleton"
    :class="{ 'skeleton-compact': compact }"
    role="status"
  >
    <!-- Decorative circles (static) -->
    <div class="decorative-circle circle-1" />
    <div class="decorative-circle circle-2" />

    <!-- Content skeleton -->
    <div class="skeleton-content" :class="{ 'content-compact': compact }">
      <!-- Icon skeleton -->
      <div class="skeleton-icon" />

      <!-- Text skeleton -->
      <div v-if="!compact" class="skeleton-text">
        <div class="skeleton-value" />
        <div class="skeleton-title" />
      </div>

      <!-- Compact layout -->
      <div v-else class="skeleton-text-compact">
        <div class="skeleton-value-compact" />
        <div class="skeleton-title-compact" />
      </div>
    </div>

    <!-- Screen reader text -->
    <span class="sr-only">Chargement des données...</span>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    compact?: boolean
  }

  withDefaults(defineProps<Props>(), {
    compact: false,
  })
</script>

<style scoped>
.app-widget-skeleton {
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
  border: 1px solid rgba(226, 232, 240, 0.5);
  border-radius: 16px;
  padding: 24px;
  min-height: 140px;
  display: flex;
  flex-direction: column;
}

.app-widget-skeleton.skeleton-compact {
  border-radius: 12px;
  padding: 16px;
  min-height: 100px;
}

/* Decorative circles */
.decorative-circle {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(226, 232, 240, 0.3), transparent);
  opacity: 0.5;
  pointer-events: none;
  z-index: 0;
}

.circle-1 {
  width: 180px;
  height: 180px;
  top: -90px;
  right: -90px;
}

.circle-2 {
  width: 120px;
  height: 120px;
  bottom: -60px;
  left: -60px;
}

.skeleton-compact .circle-1 {
  width: 120px;
  height: 120px;
  top: -60px;
  right: -60px;
}

.skeleton-compact .circle-2 {
  width: 80px;
  height: 80px;
  bottom: -40px;
  left: -40px;
}

/* Content */
.skeleton-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex: 1;
}

.skeleton-content.content-compact {
  flex-direction: row;
  align-items: center;
  gap: 16px;
}

/* Icon skeleton */
.skeleton-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: linear-gradient(
    90deg,
    rgba(226, 232, 240, 0.4) 0%,
    rgba(226, 232, 240, 0.6) 50%,
    rgba(226, 232, 240, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  flex-shrink: 0;
}

.skeleton-compact .skeleton-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
}

/* Text skeleton (large) */
.skeleton-text {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
}

.skeleton-value {
  width: 120px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(
    90deg,
    rgba(226, 232, 240, 0.4) 0%,
    rgba(226, 232, 240, 0.6) 50%,
    rgba(226, 232, 240, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  animation-delay: 0.1s;
}

.skeleton-title {
  width: 160px;
  height: 14px;
  border-radius: 4px;
  background: linear-gradient(
    90deg,
    rgba(226, 232, 240, 0.4) 0%,
    rgba(226, 232, 240, 0.6) 50%,
    rgba(226, 232, 240, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  animation-delay: 0.2s;
}

/* Text skeleton (compact) */
.skeleton-text-compact {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 0;
}

.skeleton-value-compact {
  width: 80px;
  height: 24px;
  border-radius: 6px;
  background: linear-gradient(
    90deg,
    rgba(226, 232, 240, 0.4) 0%,
    rgba(226, 232, 240, 0.6) 50%,
    rgba(226, 232, 240, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  animation-delay: 0.1s;
}

.skeleton-title-compact {
  width: 120px;
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(
    90deg,
    rgba(226, 232, 240, 0.4) 0%,
    rgba(226, 232, 240, 0.6) 50%,
    rgba(226, 232, 240, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  animation-delay: 0.2s;
}

/* Shimmer animation */
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Screen reader only */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .app-widget-skeleton {
    min-height: 120px;
    padding: 20px;
  }

  .app-widget-skeleton.skeleton-compact {
    min-height: 90px;
    padding: 14px;
  }

  .skeleton-icon {
    width: 56px;
    height: 56px;
  }

  .skeleton-compact .skeleton-icon {
    width: 44px;
    height: 44px;
  }

  .skeleton-value {
    height: 28px;
  }

  .skeleton-value-compact {
    height: 20px;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .skeleton-icon,
  .skeleton-value,
  .skeleton-title,
  .skeleton-value-compact,
  .skeleton-title-compact {
    animation: none !important;
    background: rgba(226, 232, 240, 0.5);
  }
}
</style>
