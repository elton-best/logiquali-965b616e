import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

interface GlobalLoaderPayload {
  message?: string
  context?: string
  modal?: boolean
  immediate?: boolean
}

export const useGlobalLoaderStore = defineStore('globalLoader', () => {
  const pendingRequests = ref(0)
  const isModalActive = ref(false)
  const isBarActive = ref(false)
  const isLongLoading = ref(false)
  const message = ref('Chargement en cours...')
  const context = ref<string | null>(null)
  const progress = ref(0)

  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let longTimer: ReturnType<typeof setTimeout> | null = null
  let trickleTimer: ReturnType<typeof setInterval> | null = null

  const isLoading = computed(() => isModalActive.value)
  const isModalLoading = computed(() => isModalActive.value)
  const isTopBarLoading = computed(() => isBarActive.value)

  function startTrickle () {
    if (trickleTimer) clearInterval(trickleTimer)
    progress.value = 18
    trickleTimer = setInterval(() => {
      if (progress.value < 85) {
        const step = Math.max(1, Math.floor((90 - progress.value) / 8))
        progress.value += step
      }
    }, 180)
  }

  function stopTrickle () {
    if (trickleTimer) {
      clearInterval(trickleTimer)
      trickleTimer = null
    }
    progress.value = 100
    setTimeout(() => {
      isBarActive.value = false
      isLongLoading.value = false
      progress.value = 0
    }, 320)
  }

  function startLoading (payload: GlobalLoaderPayload = {}) {
    pendingRequests.value += 1
    if (payload.message) {
      message.value = payload.message
    }
    if (payload.context) {
      context.value = payload.context
    }
    if (payload.modal) {
      isModalActive.value = true
    }

    // Debounce anti-flicker: requests faster than 180ms don't trigger the loader
    if (payload.immediate) {
      isBarActive.value = true
      startTrickle()
    } else if (!isBarActive.value && !debounceTimer) {
      debounceTimer = setTimeout(() => {
        if (pendingRequests.value > 0) {
          isBarActive.value = true
          startTrickle()
        }
        debounceTimer = null
      }, 180)
    }

    // If request takes longer than 1800ms, show the subtle sync pill
    if (!longTimer) {
      longTimer = setTimeout(() => {
        if (pendingRequests.value > 0) {
          isLongLoading.value = true
        }
        longTimer = null
      }, 1800)
    }
  }

  function stopLoading () {
    pendingRequests.value = Math.max(0, pendingRequests.value - 1)
    if (pendingRequests.value === 0) {
      if (debounceTimer) {
        clearTimeout(debounceTimer)
        debounceTimer = null
      }
      if (longTimer) {
        clearTimeout(longTimer)
        longTimer = null
      }
      isModalActive.value = false
      stopTrickle()
      message.value = 'Chargement en cours...'
      context.value = null
    }
  }

  function resetLoading () {
    pendingRequests.value = 0
    if (debounceTimer) {
      clearTimeout(debounceTimer)
      debounceTimer = null
    }
    if (longTimer) {
      clearTimeout(longTimer)
      longTimer = null
    }
    isModalActive.value = false
    stopTrickle()
    message.value = 'Chargement en cours...'
    context.value = null
  }

  return {
    pendingRequests,
    isLoading,
    isModalLoading,
    isTopBarLoading,
    isLongLoading,
    progress,
    message,
    context,
    startLoading,
    stopLoading,
    resetLoading,
  }
})
