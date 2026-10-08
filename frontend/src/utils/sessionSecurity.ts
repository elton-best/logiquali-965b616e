const LOCAL_STORAGE_KEYS_TO_CLEAR = [
  'access_token',
  'user',
  'current_site_id',
  'active_site_id',
  'tenant_id',
  'refresh_token',
  'user_data',
  'token',
  'accessToken',
  'refreshToken',
  'sessionLocked',
  'sessionLockedAt',
  'returnPath',
  'lockedUserEmail',
  'cached_sub_modules',
  'cached_sub_modules_timestamp',
  'cached_sections',
  'cached_sections_timestamp',
]

/**
 * Purge les données sensibles de session côté navigateur.
 */
export function clearBrowserSessionData (): void {
  try {
    for (const key of LOCAL_STORAGE_KEYS_TO_CLEAR) {
      localStorage.removeItem(key)
    }
    sessionStorage.clear()

    if (typeof window !== 'undefined' && 'caches' in window) {
      window.caches.keys()
        .then(keys => Promise.all(keys.map(key => window.caches.delete(key))))
        .catch(() => {
          // no-op: le nettoyage cache ne doit jamais bloquer la déconnexion
        })
    }
  } catch (error) {
    console.error('[Security] Impossible de nettoyer la session locale', error)
  }
}
