<template>
  <Transition
    :mode="mode"
    :name="transitionName"
    @after-enter="onAfterEnter"
    @after-leave="onAfterLeave"
    @before-enter="onBeforeEnter"
    @before-leave="onBeforeLeave"
    @enter="onEnter"
    @leave="onLeave"
  >
    <slot />
  </Transition>
</template>

<script setup lang="ts">
/**
 * Composant pour les transitions de page avec animations fluides
 *
 * @example
 * // Dans router/index.ts
 * <router-view v-slot="{ Component }">
 *   <PageTransition>
 *     <component :is="Component" />
 *   </PageTransition>
 * </router-view>
 *
 * @example
 * // Transition personnalisée
 * <PageTransition name="slide-fade" mode="out-in">
 *   <div v-if="show">Content</div>
 * </PageTransition>
 */

  interface Props {
    /** Nom de la transition (fade, slide, scale, etc.) */
    name?: 'fade' | 'slide' | 'slide-up' | 'slide-down' | 'scale' | 'zoom'
    /** Mode de transition */
    mode?: 'in-out' | 'out-in' | 'default'
    /** Durée de la transition en ms */
    duration?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    name: 'fade',
    mode: 'out-in',
    duration: 300,
  })

  const emit = defineEmits<{
    'before-enter': []
    'enter': []
    'after-enter': []
    'before-leave': []
    'leave': []
    'after-leave': []
  }>()

  const transitionName = props.name

  const onBeforeEnter = () => emit('before-enter')
  const onEnter = () => emit('enter')
  const onAfterEnter = () => emit('after-enter')
  const onBeforeLeave = () => emit('before-leave')
  const onLeave = () => emit('leave')
  const onAfterLeave = () => emit('after-leave')
</script>

<style scoped>
/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide transition */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from {
  transform: translateX(20px);
  opacity: 0;
}

.slide-leave-to {
  transform: translateX(-20px);
  opacity: 0;
}

/* Slide up transition */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

/* Slide down transition */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-enter-from {
  transform: translateY(-20px);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

/* Scale transition */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scale-enter-from,
.scale-leave-to {
  transform: scale(0.95);
  opacity: 0;
}

/* Zoom transition */
.zoom-enter-active,
.zoom-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.zoom-enter-from {
  transform: scale(0.8);
  opacity: 0;
}

.zoom-leave-to {
  transform: scale(1.1);
  opacity: 0;
}
</style>
