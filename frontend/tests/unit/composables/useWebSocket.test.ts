import { beforeEach, describe, expect, it, vi } from 'vitest'

// Mock Vue lifecycle hooks to avoid warnings
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    onMounted: vi.fn(),
    onUnmounted: vi.fn(),
  }
})

// Mock stores
vi.mock('@/stores/habilitationStore', () => ({
  useHabilitationStore: vi.fn(() => ({
    fetchAll: vi.fn(),
    invalidateUser: vi.fn(),
  })),
}))

vi.mock('@/stores/competenceStore', () => ({
  useCompetenceStore: vi.fn(() => ({
    invalidateUser: vi.fn(),
  })),
}))

vi.mock('@/plugins/notification', () => ({
  useNotification: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}))

// Mock WebSocket
class MockWebSocket {
  listeners = {}

  constructor (url) {
    this.url = url
    this.readyState = WebSocket.CONNECTING
    setTimeout(() => {
      this.readyState = WebSocket.OPEN
      this.onopen?.()
      this.listeners.open?.()
    }, 0)
  }

  addEventListener (event, cb) {
    this.listeners[event] = cb
  }

  close () {
    this.readyState = WebSocket.CLOSED
    this.onclose?.()
    this.listeners.close?.()
  }
}

global.WebSocket = MockWebSocket

describe('useWebSocket', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should initialize with disconnected state', async () => {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    const { connected } = useWebSocket()
    expect(connected.value).toBe(false)
  })

  it('should connect successfully', async () => {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    const { connected, connect } = useWebSocket()

    connect()
    await new Promise(resolve => setTimeout(resolve, 10))

    expect(connected.value).toBe(true)
  })

  it('should disconnect properly', async () => {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    const { connected, disconnect } = useWebSocket()

    disconnect()
    expect(connected.value).toBe(false)
  })
})
