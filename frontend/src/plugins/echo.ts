import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for Laravel Echo
;(window as any).Pusher = Pusher

// Get auth token from localStorage
const USE_HTTPONLY_COOKIE_AUTH = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false') === 'true'

function getAuthToken (): string | null {
  if (USE_HTTPONLY_COOKIE_AUTH) {
    return null
  }
  return localStorage.getItem('access_token')
}

export const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY || 'lfqdfhkkgnbkyzbggpmo',
  wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
  wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel: any) => {
    return {
      authorize: (socketId: string, callback: Function) => {
        const token = getAuthToken()
        fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api/broadcasting/auth`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'X-Socket-Id': socketId,
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then(response => response.json())
          .then(data => {
            callback(null, data)
          })
          .catch(error => {
            callback(error, null)
          })
      },
    }
  },
})

export default echo
