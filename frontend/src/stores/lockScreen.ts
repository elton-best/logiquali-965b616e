import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLockScreenStore = defineStore('lockScreen', () => {
  const isLocked = ref(false)
  const lockedAt = ref<Date | null>(null)
  const returnPath = ref('/')
  const userEmail = ref('')

  function lockSession (currentPath = '/', email = '') {
    isLocked.value = true
    lockedAt.value = new Date()
    returnPath.value = currentPath
    userEmail.value = email
    localStorage.setItem('sessionLocked', 'true')
    localStorage.setItem('sessionLockedAt', lockedAt.value.toISOString())
    localStorage.setItem('returnPath', currentPath)
    localStorage.setItem('lockedUserEmail', email)
  }

  function unlockSession () {
    isLocked.value = false
    lockedAt.value = null
    userEmail.value = ''
    localStorage.removeItem('sessionLocked')
    localStorage.removeItem('sessionLockedAt')
    localStorage.removeItem('lockedUserEmail')
    const path = returnPath.value
    returnPath.value = '/'
    localStorage.removeItem('returnPath')
    return path
  }

  function checkLockStatus () {
    const locked = localStorage.getItem('sessionLocked') === 'true'
    if (locked) {
      isLocked.value = true
      const lockedAtStr = localStorage.getItem('sessionLockedAt')
      if (lockedAtStr) {
        lockedAt.value = new Date(lockedAtStr)
      }
      returnPath.value = localStorage.getItem('returnPath') || '/'
      userEmail.value = localStorage.getItem('lockedUserEmail') || ''
    }
    return locked
  }

  return {
    isLocked,
    lockedAt,
    returnPath,
    userEmail,
    lockSession,
    unlockSession,
    checkLockStatus,
  }
})
