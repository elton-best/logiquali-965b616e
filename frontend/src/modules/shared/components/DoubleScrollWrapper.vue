<template>
  <div class="double-scroll-wrapper">
    <!-- Top Horizontal Scrollbar -->
    <div
      v-if="hasTopScroll && showScrollbar"
      ref="topScrollRef"
      class="double-scroll-top-bar"
      @scroll="onTopScroll"
    >
      <div class="double-scroll-spacer" :style="{ width: `${contentWidth}px` }" />
    </div>

    <!-- Main Content Container with Native Scroll -->
    <div
      ref="mainScrollRef"
      class="double-scroll-main-container"
      :class="{
        'with-sticky-first': stickyFirstCol,
        'with-sticky-last': stickyLastCol,
      }"
      @scroll="onMainScroll"
    >
      <div ref="contentRef" class="double-scroll-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    hasTopScroll?: boolean
    stickyFirstCol?: boolean
    stickyLastCol?: boolean
  }>(),
  {
    hasTopScroll: true,
    stickyFirstCol: false,
    stickyLastCol: false,
  },
)

const topScrollRef = ref<HTMLElement | null>(null)
const mainScrollRef = ref<HTMLElement | null>(null)
const contentRef = ref<HTMLElement | null>(null)

const contentWidth = ref(0)
const showScrollbar = ref(false)

let isSyncingTop = false
let isSyncingMain = false
let resizeObserver: ResizeObserver | null = null

function updateWidths() {
  if (!mainScrollRef.value || !contentRef.value) return
  const scrollWidth = contentRef.value.scrollWidth || contentRef.value.offsetWidth
  const clientWidth = mainScrollRef.value.clientWidth

  contentWidth.value = scrollWidth
  showScrollbar.value = scrollWidth > clientWidth
}

function onTopScroll() {
  if (isSyncingTop) {
    isSyncingTop = false
    return
  }
  if (!topScrollRef.value || !mainScrollRef.value) return

  isSyncingMain = true
  mainScrollRef.value.scrollLeft = topScrollRef.value.scrollLeft
}

function onMainScroll() {
  if (isSyncingMain) {
    isSyncingMain = false
    return
  }
  if (!topScrollRef.value || !mainScrollRef.value) return

  isSyncingTop = true
  topScrollRef.value.scrollLeft = mainScrollRef.value.scrollLeft
}

onMounted(() => {
  nextTick(() => {
    updateWidths()
    if (contentRef.value && typeof ResizeObserver !== 'undefined') {
      resizeObserver = new ResizeObserver(() => {
        updateWidths()
      })
      resizeObserver.observe(contentRef.value)
    }
  })
})

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
})
</script>

<style scoped>
.double-scroll-wrapper {
  position: relative;
  width: 100%;
}

.double-scroll-top-bar {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  height: 14px;
  margin-bottom: 2px;
  background-color: rgba(var(--v-theme-surface-variant, 240, 240, 240), 0.5);
  border-radius: 4px;
}

.double-scroll-top-bar::-webkit-scrollbar {
  height: 10px;
}

.double-scroll-top-bar::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.double-scroll-top-bar::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 4px;
}

.double-scroll-top-bar::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

.double-scroll-spacer {
  height: 1px;
}

.double-scroll-main-container {
  width: 100%;
  overflow-x: auto;
  position: relative;
}

.double-scroll-content {
  min-width: 100%;
  display: inline-block;
}

/* Sticky column classes */
:deep(.double-scroll-main-container.with-sticky-first th:first-child),
:deep(.double-scroll-main-container.with-sticky-first td:first-child) {
  position: sticky;
  left: 0;
  z-index: 2;
  background-color: rgb(var(--v-theme-surface, 255, 255, 255));
  box-shadow: 2px 0 4px -1px rgba(0, 0, 0, 0.08);
}

:deep(.double-scroll-main-container.with-sticky-first th:first-child) {
  z-index: 3;
}

:deep(.double-scroll-main-container.with-sticky-last th:last-child),
:deep(.double-scroll-main-container.with-sticky-last td:last-child) {
  position: sticky;
  right: 0;
  z-index: 2;
  background-color: rgb(var(--v-theme-surface, 255, 255, 255));
  box-shadow: -2px 0 4px -1px rgba(0, 0, 0, 0.08);
}

:deep(.double-scroll-main-container.with-sticky-last th:last-child) {
  z-index: 3;
}
</style>

