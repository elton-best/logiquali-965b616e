<template>
  <v-menu
    v-model="menu"
    :close-on-content-click="false"
    location="bottom end"
    offset="10"
  >
    <template #activator="{ props }">
      <v-btn class="notif-trigger" icon variant="text" v-bind="props">
        <v-badge v-if="unreadCount > 0" color="#4471c4" :content="unreadCount" floating>
          <v-icon icon="mdi-bell-ring" />
        </v-badge>
        <v-icon v-else icon="mdi-bell-outline" />
      </v-btn>
    </template>

    <v-card class="notif-panel" max-height="560" max-width="420" rounded="xl">
      <div class="notif-header">
        <div>
          <div class="notif-title">Notifications</div>
          <div class="notif-subtitle">{{ unreadCount }} non lue(s)</div>
        </div>
        <v-btn
          v-if="unreadCount > 0"
          class="mark-all-btn"
          prepend-icon="mdi-check-all"
          size="small"
          variant="tonal"
          @click="markAllAsRead"
        >
          Tout lire
        </v-btn>
      </div>

      <div class="notif-list-wrap">
        <div v-if="notifications.length === 0" class="empty-state">
          <v-icon color="grey-lighten-1" size="52">mdi-bell-off-outline</v-icon>
          <p>Aucune notification</p>
        </div>

        <div v-else class="notif-list">
          <article
            v-for="notification in notifications"
            :key="notification.id"
            :class="['notif-item', !notification.is_read ? 'is-unread' : '']"
            @click="handleNotificationClick(notification)"
          >
            <v-avatar :color="getNotificationColor(notification.type, notification.workflow_type)" size="34">
              <v-icon color="white" :icon="getNotificationIcon(notification.type, notification.workflow_type)" size="18" />
            </v-avatar>

            <div class="notif-main">
              <div class="notif-row">
                <h4 class="notif-item-title">
                  {{ notification.data?.workflow_type ? getNotificationLabel(notification.data.workflow_type) : notification.title }}
                </h4>
                <span class="notif-time">{{ formatDate(notification.created_at) }}</span>
              </div>
              <p class="notif-item-text">{{ notification.message }}</p>
              <div v-if="notification.data?.document_title" class="notif-meta">
                📄 {{ notification.data.document_title }}
              </div>
            </div>
          </article>
        </div>
      </div>

      <div v-if="notifications.length > 0" class="notif-footer">
        <v-btn block variant="text" @click="viewAll">
          Voir toutes les notifications
        </v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
  import { formatDistanceToNow } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { useAuthStore } from '@/stores/auth'
  import { inferBlockingCodeFromUser, shouldBlockNotificationsForCode } from '@/utils/blockingAccess'

  interface Notification {
    id: number
    type: string
    title?: string
    message?: string
    data?: any
    read_at: string | null
    created_at: string
    is_read?: boolean
    workflow_type?: string
  }

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const menu = ref(false)
  const notifications = ref<Notification[]>([])
  let refreshInterval: number | null = null
  const blockingCode = computed(() => typeof route.query.blocking === 'string' ? route.query.blocking : '')
  const inferredBlockingCode = computed(() => inferBlockingCodeFromUser(authStore.user))
  const shouldSkipNotifications = computed(() =>
    shouldBlockNotificationsForCode(blockingCode.value || inferredBlockingCode.value || ''),
  )

  const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read_at).length
  })

  function getNotificationIcon (type: string, workflowType?: string): string {
    const icons: Record<string, string> = {
      verification_request: 'mdi-eye-check',
      approval_request: 'mdi-pencil-check',
      rejection_decision_required: 'mdi-alert-octagon',
      publication: 'mdi-check-circle',
      nc: 'mdi-alert-circle',
      audit: 'mdi-clipboard-check',
      document: 'mdi-file-document',
      user: 'mdi-account',
      risk: 'mdi-alert-octagon',
      system: 'mdi-information',
    }
    if (workflowType && icons[workflowType]) {
      return icons[workflowType]
    }
    return icons[type] || 'mdi-bell'
  }

  function getNotificationColor (type: string, workflowType?: string): string {
    const colors: Record<string, string> = {
      verification_request: 'warning',
      approval_request: 'error',
      rejection_decision_required: 'error',
      publication: 'success',
      nc: 'error',
      audit: 'info',
      document: 'primary',
      user: 'success',
      risk: 'warning',
      system: 'grey',
    }
    if (workflowType && colors[workflowType]) {
      return colors[workflowType]
    }
    return colors[type] || 'grey'
  }

  function getNotificationLabel (workflowType?: string): string {
    const labels: Record<string, string> = {
      verification_request: '⏱️ Vérification requise',
      approval_request: '✏️ Approbation requise',
      rejection_decision_required: '❌ Décision de rejet',
      publication: '✅ Document publié',
    }
    return labels[workflowType ?? ''] ?? ''
  }

  function formatDate (date: string): string {
    try {
      return formatDistanceToNow(new Date(date), {
        addSuffix: true,
        locale: fr,
      })
    } catch {
      return date
    }
  }

  async function loadNotifications () {
    if (shouldSkipNotifications.value) {
      notifications.value = []
      return
    }

    try {
      const response = await api.get('/notifications', {
        params: { unread_only: false, per_page: 10 },
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      const payload = response.data?.data
      const rows = Array.isArray(payload?.data)
        ? payload.data
        : (Array.isArray(payload) ? payload : [])

      notifications.value = rows.map((notification: any) => ({
        id: notification.id,
        type: notification.type || notification.data?.type || 'system',
        title: notification.title || notification.data?.title || 'Notification',
        message: notification.message || notification.data?.message || '',
        data: notification.data || {},
        read_at: notification.read_at || null,
        created_at: notification.created_at,
        workflow_type: notification.data?.workflow_type,
        is_read: !!notification.read_at,
      }))
    } catch (error: any) {
      // Silently handle known unavailable/blocked states.
      if (![404, 423].includes(error.response?.status)) {
        console.error('Erreur chargement notifications:', error)
      }
      notifications.value = []
    }
  }

  async function markAsRead (notificationId: number) {
    if (shouldSkipNotifications.value) {
      return
    }

    try {
      await api.post(`/notifications/${notificationId}/mark-as-read`)
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.read_at = new Date().toISOString()
      }
    } catch (error: any) {
      if (error.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur marquage notification:', error)
    }
  }

  async function markAllAsRead () {
    if (shouldSkipNotifications.value) {
      return
    }

    try {
      await api.post('/notifications/mark-all-as-read')
      for (const n of notifications.value) {
        n.read_at = new Date().toISOString()
      }
    } catch (error: any) {
      if (error.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur marquage toutes notifications:', error)
    }
  }

  async function handleNotificationClick (notification: Notification) {
    // Marquer comme lue
    if (!notification.read_at) {
      await markAsRead(notification.id)
    }

    // Route pour notifications de workflow de document
    if (notification.data?.workflow_type && notification.data?.entity_id) {
      const docId = notification.data.entity_id
      const action = {
        verification_request: 'verify',
        approval_request: 'approve',
        rejection_decision_required: 'handle_rejection',
        publication: undefined,
      }[notification.data.workflow_type]

      const url = action
        ? `/documents/${docId}?action=${action}&notif_id=${notification.id}`
        : `/documents/${docId}?notif_id=${notification.id}`

      await router.push(url)
      menu.value = false
      return
    }

    // Naviguer vers la ressource liée (legacy)
    const rawUrl = typeof notification.data?.url === 'string'
      ? notification.data.url.trim()
      : ''

    if (rawUrl) {
      try {
        if (rawUrl.startsWith('/')) {
          await router.push(rawUrl)
        } else {
          const parsed = new URL(rawUrl, window.location.origin)
          const targetPath = `${parsed.pathname}${parsed.search}${parsed.hash}`
          if (parsed.origin === window.location.origin) {
            await router.push(targetPath)
          } else {
            window.open(parsed.toString(), '_blank', 'noopener,noreferrer')
          }
        }
      } catch (error) {
        console.warn('NotificationsMenu: URL invalide ignorée', rawUrl, error)
      }
    } else if (notification.data?.resource_type && notification.data?.resource_id) {
      const routes: Record<string, string> = {
        non_conformity: '/company/nonconformities',
        audit: '/company/audits',
        document: '/company/documents',
        user: '/company/users',
        risk: '/company/risks',
      }
      const basePath = routes[notification.data.resource_type]
      if (basePath) {
        await router.push(`${basePath}/${notification.data.resource_id}`)
      }
    }

    menu.value = false
  }

  function viewAll () {
    router.push('/company/notifications')
    menu.value = false
  }

  onMounted(() => {
    loadNotifications()
    // Rafraîchir toutes les 60 secondes
    refreshInterval = window.setInterval(() => {
      loadNotifications()
    }, 60_000)
  })

  onUnmounted(() => {
    if (refreshInterval) {
      clearInterval(refreshInterval)
    }
  })

  watch(shouldSkipNotifications, shouldSkip => {
    if (shouldSkip) {
      notifications.value = []
    } else {
      loadNotifications()
    }
  })
</script>

<style scoped>
  .notif-trigger {
    border-radius: 12px;
  }

  .notif-panel {
    backdrop-filter: blur(12px);
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(15, 23, 42, 0.08);
    overflow: hidden;
  }

  .notif-header {
    align-items: center;
    backdrop-filter: blur(10px);
    background: linear-gradient(120deg, rgba(68, 113, 196, 0.95), rgba(68, 113, 196, 0.72));
    color: #fff;
    display: flex;
    justify-content: space-between;
    padding: 14px 16px;
  }

  .notif-title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .notif-subtitle {
    font-size: 0.76rem;
    opacity: 0.85;
  }

  .mark-all-btn {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
  }

  .notif-list-wrap {
    max-height: 430px;
    overflow-y: auto;
  }

  .empty-state {
    align-items: center;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 34px 12px;
    text-align: center;
  }

  .notif-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
  }

  .notif-item {
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    display: grid;
    gap: 10px;
    grid-template-columns: auto 1fr;
    padding: 10px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  }

  .notif-item:hover {
    border-color: #94a3b8;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
  }

  .notif-item.is-unread {
    background: linear-gradient(180deg, rgba(68, 113, 196, 0.14) 0%, #ffffff 88%);
    border-color: rgba(68, 113, 196, 0.42);
  }

  .notif-row {
    align-items: baseline;
    display: flex;
    gap: 8px;
    justify-content: space-between;
  }

  .notif-item-title {
    color: #0f172a;
    font-size: 0.86rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
  }

  .notif-time {
    color: #64748b;
    font-size: 0.72rem;
    white-space: nowrap;
  }

  .notif-item-text {
    color: #475569;
    font-size: 0.79rem;
    line-height: 1.32;
    margin: 4px 0 0;
  }

  .notif-footer {
    border-top: 1px solid #e2e8f0;
    padding: 6px 8px;
  }
</style>
