import { ref } from 'vue'

type LockMap = Record<string, boolean>

export function useActionLock () {
  const locks = ref<LockMap>({})

  function isLocked (key: string) {
    return !!locks.value[key]
  }

  function lock (key: string) {
    locks.value[key] = true
  }

  function unlock (key: string) {
    locks.value[key] = false
  }

  async function run<T> (key: string, action: () => Promise<T>): Promise<T | null> {
    if (isLocked(key)) {
      return null
    }
    lock(key)
    try {
      return await action()
    } finally {
      unlock(key)
    }
  }

  return {
    isLocked,
    lock,
    unlock,
    run,
  }
}
