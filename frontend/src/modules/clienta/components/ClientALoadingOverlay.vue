<template>
  <div class="app-modern-loader-container">
    <!-- 1. Ultra-sleek Top Progress Bar (GitHub / Linear / Vercel style) -->
    <transition name="bar-fade">
      <div
        v-if="globalLoader.isTopBarLoading"
        class="top-progress-bar-track"
      >
        <div
          class="top-progress-bar-fill"
          :style="{ width: `${globalLoader.progress}%` }"
        >
          <div class="top-progress-bar-glow" />
        </div>
      </div>
    </transition>

    <!-- 2. Non-intrusive Floating Status Pill (only for longer queries > 1.8s, never blocks screen) -->
    <transition name="pill-slide">
      <div
        v-if="globalLoader.isLongLoading && !active"
        class="floating-sync-pill"
      >
        <span class="pulse-indicator">
          <span class="pulse-ring" />
          <span class="pulse-core" />
        </span>
        <span class="sync-label">{{ message || 'Synchronisation des données...' }}</span>
      </div>
    </transition>

    <!-- 3. Explicit Blocking Modal (ONLY when explicitly requested via isModalLoading) -->
    <transition name="modal-fade">
      <div v-if="active" class="modal-overlay">
        <div class="modal-card">
          <div class="modal-icon-wrapper">
            <div class="spinner-orbit">
              <span class="orbit-arc arc-blue" />
              <span class="orbit-arc arc-indigo" />
            </div>
            <div class="center-pulse" />
          </div>

          <div class="modal-title">{{ title }}</div>
          <div class="modal-subtitle">{{ message }}</div>

          <div class="modal-progress-track">
            <div class="modal-progress-fill" />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
  import { useGlobalLoaderStore } from '@/stores/globalLoader'

  withDefaults(defineProps<{
    active?: boolean
    title?: string
    message?: string
  }>(), {
    active: false,
    title: 'Traitement en cours',
    message: 'Veuillez patienter un instant...',
  })

  const globalLoader = useGlobalLoaderStore()
</script>

<style scoped>
.app-modern-loader-container {
  pointer-events: none;
}

/* --- 1. TOP PROGRESS BAR --- */
.top-progress-bar-track {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: transparent;
  z-index: 999999;
  pointer-events: none;
}

.top-progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6 0%, #6366f1 50%, #06b6d4 100%);
  border-radius: 0 4px 4px 0;
  transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.8), 0 0 4px rgba(6, 182, 212, 0.6);
}

.top-progress-bar-glow {
  position: absolute;
  right: 0;
  top: 0;
  width: 80px;
  height: 100%;
  box-shadow: 0 0 14px 2px #38bdf8;
  opacity: 0.85;
}

.bar-fade-enter-active,
.bar-fade-leave-active {
  transition: opacity 0.2s ease;
}

.bar-fade-enter-from,
.bar-fade-leave-to {
  opacity: 0;
}

/* --- 2. FLOATING SYNC PILL --- */
.floating-sync-pill {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(15, 23, 42, 0.88);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #f8fafc;
  padding: 8px 16px;
  border-radius: 9999px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25), 0 2px 8px rgba(0, 0, 0, 0.1);
  font-size: 0.82rem;
  font-weight: 500;
  z-index: 99999;
  pointer-events: none;
}

.pulse-indicator {
  position: relative;
  display: inline-flex;
  width: 10px;
  height: 10px;
}

.pulse-ring {
  position: absolute;
  inset: -2px;
  border-radius: 50%;
  background: #38bdf8;
  opacity: 0.75;
  animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.pulse-core {
  position: relative;
  display: inline-flex;
  border-radius: 50%;
  width: 10px;
  height: 10px;
  background: #0284c7;
}

.sync-label {
  letter-spacing: 0.01em;
}

.pill-slide-enter-active,
.pill-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.pill-slide-enter-from,
.pill-slide-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.95);
}

/* --- 3. EXPLICIT MODAL OVERLAY --- */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: grid;
  place-items: center;
  z-index: 2400;
  pointer-events: auto;
}

.modal-card {
  width: min(400px, 88vw);
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  padding: 2rem 2.25rem;
  box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
  text-align: center;
}

.modal-icon-wrapper {
  position: relative;
  width: 56px;
  height: 56px;
  margin: 0 auto 1.25rem;
}

.spinner-orbit {
  position: absolute;
  inset: 0;
}

.orbit-arc {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2.5px solid transparent;
}

.arc-blue {
  border-top-color: #3b82f6;
  animation: spin 1.2s linear infinite;
}

.arc-indigo {
  border-bottom-color: #6366f1;
  animation: spin 1.8s linear infinite reverse;
}

.center-pulse {
  position: absolute;
  inset: 18px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 50%;
  opacity: 0.85;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.35rem;
}

.modal-subtitle {
  font-size: 0.88rem;
  color: #64748b;
  margin-bottom: 1.25rem;
}

.modal-progress-track {
  height: 4px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}

.modal-progress-fill {
  height: 100%;
  width: 100%;
  background: linear-gradient(90deg, #3b82f6, #6366f1, #06b6d4);
  animation: progress-pulse 1.6s ease-in-out infinite;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

@keyframes progress-pulse {
  0% { transform: scaleX(0.2); transform-origin: left; }
  50% { transform: scaleX(0.85); transform-origin: center; }
  100% { transform: scaleX(0.2); transform-origin: right; }
}
</style>
