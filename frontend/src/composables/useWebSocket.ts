import { onMounted, onUnmounted, ref } from 'vue'
import { useNotification } from '@/plugins/notification'
import { useHabilitationStore } from '@/stores/habilitationStore'

export function useWebSocket () {
  const socket = ref<WebSocket | null>(null)
  const connected = ref(false)
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 5

  const habilitationStore = useHabilitationStore()
  const notification = useNotification()

  const connect = () => {
    try {
      const wsUrl = import.meta.env.VITE_WS_URL || 'ws://localhost:8080'
      socket.value = new WebSocket(`${wsUrl}/app/competences`)

      socket.value.addEventListener('open', () => {
        connected.value = true
        reconnectAttempts.value = 0
        console.log('WebSocket connected')
      })

      socket.value.addEventListener('message', event => {
        try {
          const data = JSON.parse(event.data)
          handleMessage(data)
        } catch (error) {
          console.error('Error parsing WebSocket message:', error)
        }
      })

      socket.value.addEventListener('close', () => {
        connected.value = false
        console.log('WebSocket disconnected')

        if (reconnectAttempts.value < maxReconnectAttempts) {
          setTimeout(() => {
            reconnectAttempts.value++
            connect()
          }, Math.pow(2, reconnectAttempts.value) * 1000)
        }
      })

      socket.value.addEventListener('error', error => {
        console.error('WebSocket error:', error)
      })
    } catch (error) {
      console.error('Failed to connect WebSocket:', error)
    }
  }

  const handleMessage = (data: any) => {
    switch (data.type) {
      case 'habilitation.expiring': {
        handleHabilitationExpiring(data.payload)
        break
      }
      case 'habilitation.expired': {
        handleHabilitationExpired(data.payload)
        break
      }
      case 'habilitation.updated': {
        handleHabilitationUpdated(data.payload)
        break
      }
      case 'competence.gap_detected': {
        handleCompetenceGap(data.payload)
        break
      }
      default: {
        console.log('Unknown message type:', data.type)
      }
    }
  }

  const handleHabilitationExpiring = (habilitation: any) => {
    notification.warning(
      `L'habilitation de ${habilitation.user.name} expire le ${new Date(habilitation.expiry_date).toLocaleDateString('fr-FR')}`,
      6000,
    )
  }

  const handleHabilitationExpired = (habilitation: any) => {
    notification.error(
      `L'habilitation de ${habilitation.user.name} a expiré`,
      8000,
    )
    habilitationStore.fetchAll(true)
  }

  const handleHabilitationUpdated = (habilitation: any) => {
    habilitationStore.fetchAll(true)
    notification.success(`Habilitation de ${habilitation.user.name} mise à jour`)
  }

  const handleCompetenceGap = (gap: any) => {
    notification.info(`Écart de compétence: ${gap.user.name} - ${gap.competence.name}`)
  }

  const disconnect = () => {
    if (socket.value) {
      socket.value.close()
      socket.value = null
    }
  }

  onMounted(() => {
    connect()
  })

  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    connect,
    disconnect,
  }
}
