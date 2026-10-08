/**
 * Storage utility for managing localStorage/sessionStorage
 */

type StorageType = 'local' | 'session'

class StorageManager {
  set<T>(key: string, value: T, type: StorageType = 'local'): void {
    try {
      const storage = this.getStorage(type)
      storage.setItem(key, JSON.stringify(value))
    } catch (error) {
      console.error(`Error saving to ${type}Storage:`, error)
    }
  }

  get<T>(key: string, type: StorageType = 'local'): T | null {
    try {
      const storage = this.getStorage(type)
      const item = storage.getItem(key)

      if (!item || item === 'undefined' || item === 'null') {
        return null
      }

      try {
        return JSON.parse(item)
      } catch {
        // Corrupted data - remove it silently
        console.warn(`Corrupted data found for key "${key}", removing...`)
        storage.removeItem(key)
        return null
      }
    } catch (error) {
      console.error(`Error reading from ${type}Storage:`, error)
      return null
    }
  }

  remove (key: string, type: StorageType = 'local'): void {
    try {
      const storage = this.getStorage(type)
      storage.removeItem(key)
    } catch (error) {
      console.error(`Error removing from ${type}Storage:`, error)
    }
  }

  clear (type: StorageType = 'local'): void {
    try {
      const storage = this.getStorage(type)
      storage.clear()
    } catch (error) {
      console.error(`Error clearing ${type}Storage:`, error)
    }
  }

  has (key: string, type: StorageType = 'local'): boolean {
    const storage = this.getStorage(type)
    return storage.getItem(key) !== null
  }

  private getStorage (type: StorageType = 'local'): Storage {
    return type === 'local' ? localStorage : sessionStorage
  }
}

export const storage = new StorageManager()
