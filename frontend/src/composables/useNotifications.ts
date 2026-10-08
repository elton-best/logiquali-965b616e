import type { AxiosError } from 'axios'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { apiClient } from '@/api/client'

export interface UserNotification {
  id: number
  type: string
  title: string
  message: string
  data?: Record<string, any>
  action_url?: string
  is_read: boolean
  created_at: string
  read_at?: string
  workflow_type?: string
}

export interface NotificationStats {
  unread_count: number
  total_count: number
}

export function useNotifications () {
  const notifications = ref<UserNotification[]>([])
  const stats = ref<NotificationStats>({ unread_count: 0, total_count: 0 })
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pollingInterval = ref<number | null>(null)

  const unreadCount = computed(() => stats.value.unread_count)
  const hasUnread = computed(() => stats.value.unread_count > 0)
  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.is_read),
  )
  const readNotifications = computed(() =>
    notifications.value.filter(n => n.is_read),
  )

  const fetchNotifications = async (params?: { page?: number, per_page?: number }) => {
    loading.value = true
    error.value = null

    try {
      const response = await apiClient.get<{ data: UserNotification[], meta?: any }>(
        '/notifications',
        { params: { per_page: 50, ...params } },
      )

      notifications.value = response.data.data || []
      updateStats()
    } catch (error_) {
      const axiosError = error_ as AxiosError<{ message: string }>
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement des notifications'
      console.error('Failed to fetch notifications:', error_)
    } finally {
      loading.value = false
    }
  }

  const markAsRead = async (notificationId: number) => {
    try {
      await apiClient.post(`/notifications/${notificationId}/mark-as-read`)

      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.is_read = true
        notification.read_at = new Date().toISOString()
      }

      updateStats()
    } catch (error_) {
      console.error('Failed to mark notification as read:', error_)
      throw error_
    }
  }

  const markAsUnread = async (notificationId: number) => {
    try {
      await apiClient.post(`/notifications/${notificationId}/mark-as-unread`)

      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.is_read = false
        notification.read_at = undefined
      }

      updateStats()
    } catch (error_) {
      console.error('Failed to mark notification as unread:', error_)
      throw error_
    }
  }

  const markAllAsRead = async () => {
    try {
      await apiClient.post('/notifications/mark-all-as-read')

      for (const n of notifications.value) {
        n.is_read = true
        n.read_at = new Date().toISOString()
      }

      updateStats()
    } catch (error_) {
      console.error('Failed to mark all notifications as read:', error_)
      throw error_
    }
  }

  const deleteNotification = async (notificationId: number) => {
    try {
      await apiClient.delete(`/notifications/${notificationId}`)

      notifications.value = notifications.value.filter(n => n.id !== notificationId)
      updateStats()
    } catch (error_) {
      console.error('Failed to delete notification:', error_)
      throw error_
    }
  }

  const updateStats = () => {
    stats.value = {
      unread_count: notifications.value.filter(n => !n.is_read).length,
      total_count: notifications.value.length,
    }
  }

  const startPolling = (intervalMs = 30_000) => {
    if (pollingInterval.value) {
      stopPolling()
    }

    pollingInterval.value = window.setInterval(() => {
      fetchNotifications()
    }, intervalMs)
  }

  const stopPolling = () => {
    if (pollingInterval.value) {
      clearInterval(pollingInterval.value)
      pollingInterval.value = null
    }
  }

  const getNotificationIcon = (type: string, workflowType?: string): string => {
    const iconMap: Record<string, string> = {
      verification_request: 'mdi-eye-check',
      approval_request: 'mdi-pencil-check',
      rejection_decision_required: 'mdi-alert-octagon',
      publication: 'mdi-check-circle',
      document_workflow: 'mdi-file-document-check',
      action_assigned: 'mdi-clipboard-check',
      audit_scheduled: 'mdi-calendar-check',
      complaint_received: 'mdi-alert-circle',
      subscription_expiring: 'mdi-alert',
      default: 'mdi-bell',
    }

    if (workflowType && iconMap[workflowType]) {
      return iconMap[workflowType]
    }
    return iconMap[type] || iconMap.default
  }

  const getNotificationColor = (type: string, workflowType?: string): string => {
    const colorMap: Record<string, string> = {
      verification_request: 'warning',
      approval_request: 'error',
      rejection_decision_required: 'error',
      publication: 'success',
      document_workflow: 'primary',
      action_assigned: 'warning',
      audit_scheduled: 'info',
      complaint_received: 'error',
      subscription_expiring: 'warning',
      default: 'grey',
    }

    if (workflowType && colorMap[workflowType]) {
      return colorMap[workflowType]
    }
    return colorMap[type] || colorMap.default
  }

  const getNotificationLabel = (workflowType?: string): string => {
    const labelMap: Record<string, string> = {
      verification_request: '⏱️ Vérification requise',
      approval_request: '✏️ Approbation requise',
      rejection_decision_required: '❌ Décision de rejet',
      publication: '✅ Document publié',
    }

    return labelMap[workflowType ?? ''] ?? 'Notification'
  }

  const filterByWorkflowType = (workflowType: string) => {
    return notifications.value.filter(n =>
      n.data?.workflow_type === workflowType,
    )
  }

  const getActionUrlForNotification = (notification: UserNotification): string => {
    if (notification.action_url) {
      return notification.action_url
    }

    const docId = notification.data?.entity_id || notification.data?.document_id
    if (!docId) {
      return '/'
    }

    switch (notification.data?.workflow_type) {
      case 'verification_request':
        return `/documents/${docId}?action=verify&notif_id=${notification.id}`
      case 'approval_request':
        return `/documents/${docId}?action=approve&notif_id=${notification.id}`
      case 'rejection_decision_required':
        return `/documents/${docId}?action=handle_rejection&notif_id=${notification.id}`
      case 'publication':
        return `/documents/${docId}?notif_id=${notification.id}`
      default:
        return `/documents/${docId}`
    }
  }

  const handleNotificationClick = async (notification: UserNotification) => {
    if (!notification.is_read) {
      await markAsRead(notification.id)
    }

    const url = getActionUrlForNotification(notification)
    window.location.href = url
  }

  const formatRelativeTime = (dateString: string): string => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60_000)
    const diffHours = Math.floor(diffMs / 3_600_000)
    const diffDays = Math.floor(diffMs / 86_400_000)

    if (diffMins < 1) {
      return 'À l\'instant'
    }
    if (diffMins < 60) {
      return `Il y a ${diffMins} min`
    }
    if (diffHours < 24) {
      return `Il y a ${diffHours}h`
    }
    if (diffDays < 7) {
      return `Il y a ${diffDays}j`
    }

    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
    })
  }

  onMounted(() => {
    fetchNotifications()
    startPolling()
  })

  onUnmounted(() => {
    stopPolling()
  })

  return {
    notifications,
    stats,
    loading,
    error,
    unreadCount,
    hasUnread,
    unreadNotifications,
    readNotifications,
    fetchNotifications,
    markAsRead,
    markAsUnread,
    markAllAsRead,
    deleteNotification,
    startPolling,
    stopPolling,
    getNotificationIcon,
    getNotificationColor,
    getNotificationLabel,
    filterByWorkflowType,
    getActionUrlForNotification,
    handleNotificationClick,
    formatRelativeTime,
  }
}
