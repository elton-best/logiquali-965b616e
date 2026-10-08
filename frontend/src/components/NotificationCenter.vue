<template>
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom end"
    offset="12"
    width="440"
  >
    <template #activator="{ props }">
      <v-btn
        class="notif-trigger"
        icon
        variant="text"
        v-bind="props"
      >
        <v-badge
          color="#d9480f"
          :content="unreadCount"
          floating
          :model-value="unreadCount > 0"
        >
          <v-icon size="22">{{ unreadCount > 0 ? 'mdi-bell-ring-outline' : 'mdi-bell-outline' }}</v-icon>
        </v-badge>
      </v-btn>
    </template>

    <v-card class="notif-popover" rounded="xl">
      <div class="notif-header">
        <div>
          <p class="notif-kicker">Centre d'alerte</p>
          <h3 class="notif-title">Notifications</h3>
        </div>
        <v-btn
          v-if="unreadCount > 0"
          class="notif-header-action"
          size="small"
          variant="tonal"
          @click="markAllAsRead"
        >
          Tout lire
        </v-btn>
      </div>

      <div class="notif-stats">
        <div class="notif-stat">
          <span class="notif-stat-label">Non lues</span>
          <strong class="notif-stat-value">{{ unreadCount }}</strong>
        </div>
        <div class="notif-stat">
          <span class="notif-stat-label">Total</span>
          <strong class="notif-stat-value">{{ notifications.length }}</strong>
        </div>
      </div>

      <div class="notif-filters">
        <v-btn
          class="notif-filter-btn"
          :color="activeFilter === 'all' ? 'primary' : 'default'"
          size="small"
          :variant="activeFilter === 'all' ? 'flat' : 'text'"
          @click="activeFilter = 'all'"
        >
          Tout
        </v-btn>
        <v-btn
          class="notif-filter-btn"
          :color="activeFilter === 'unread' ? 'primary' : 'default'"
          size="small"
          :variant="activeFilter === 'unread' ? 'flat' : 'text'"
          @click="activeFilter = 'unread'"
        >
          Non lues
        </v-btn>
      </div>

      <div class="notif-list-wrap">
        <div v-if="displayedNotifications.length === 0" class="notif-empty">
          <v-icon class="mb-2" color="medium-emphasis" size="36">mdi-bell-off-outline</v-icon>
          <p>{{ activeFilter === 'unread' ? 'Aucune notification non lue' : 'Aucune notification disponible' }}</p>
        </div>

        <div v-else class="notif-list">
          <button
            v-for="notification in displayedNotifications"
            :key="notification.id"
            class="notif-item"
            :class="{ 'notif-item--unread': !notification.read_at }"
            type="button"
            @click="handleNotificationClick(notification)"
          >
            <span class="notif-item-icon" :class="`notif-item-icon--${getNotificationColor(notification)}`">
              <v-icon size="18">{{ getNotificationIcon(notification) }}</v-icon>
            </span>

            <span class="notif-item-content">
              <span class="notif-item-title-row">
                <span class="notif-item-title">{{ getNotificationTitle(notification) }}</span>
                <span v-if="!notification.read_at" class="notif-dot" />
              </span>
              <span class="notif-item-message">{{ getNotificationMessage(notification) }}</span>
              <span class="notif-item-meta">
                <span>{{ formatTime(notification.created_at) }}</span>
                <span v-if="notification.data.actor_name && !isSystemNotification(notification)">• {{ notification.data.actor_name }}</span>
              </span>
            </span>

            <v-btn
              class="notif-delete"
              icon
              size="x-small"
              variant="text"
              @click.stop="deleteNotification(notification.id)"
            >
              <v-icon size="14">mdi-close</v-icon>
            </v-btn>
          </button>
        </div>
      </div>

      <div class="notif-footer">
        <v-btn
          block
          rounded="lg"
          variant="tonal"
          @click="goToNotificationsPage"
        >
          Voir tout l'historique
        </v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<script setup>
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { normalizeNotificationNavigationTarget, resolveNotificationActionUrl } from '@/modules/clienta/utils/notificationRouteResolver'
  import { useAuthStore } from '@/stores/auth'
  import {
    inferBlockingCodeFromUser,
    shouldBlockNotificationsForCode,
  } from '@/utils/blockingAccess'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const menuOpen = ref(false)
  const notifications = ref([])
  const refreshInterval = ref(null)
  const lastLoadedAt = ref(0)
  const NOTIFICATIONS_TTL_MS = 15_000
  const activeFilter = ref('all')
  let notificationsPromise = null

  const blockingCode = computed(() =>
    typeof route.query.blocking === 'string' ? route.query.blocking : '',
  )
  const inferredBlockingCode = computed(() =>
    inferBlockingCodeFromUser(authStore.user),
  )
  const shouldSkipNotifications = computed(() =>
    shouldBlockNotificationsForCode(
      blockingCode.value || inferredBlockingCode.value || '',
    ),
  )

  const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)

  const displayedNotifications = computed(() => {
    if (activeFilter.value === 'unread') {
      return notifications.value.filter(n => !n.read_at)
    }

    return notifications.value
  })

  function getNotificationMessage (notification) {
    const message = notification?.data?.message
    if (typeof message === 'string' && message.trim().length > 0) {
      return message
    }

    return 'Nouvelle activité détectée.'
  }

  async function loadNotifications (force = false) {
    if (shouldSkipNotifications.value) {
      notifications.value = []
      return
    }

    if (!force && notificationsPromise) {
      return notificationsPromise
    }

    if (
      !force
      && lastLoadedAt.value > 0
      && Date.now() - lastLoadedAt.value < NOTIFICATIONS_TTL_MS
    ) {
      return
    }

    notificationsPromise = (async () => {
      try {
        const response = await api.get('/notifications', {
          params: { unread_only: false, per_page: 10 },
          headers: { 'X-Skip-Error-Toast': 'true' },
        })
        notifications.value = response.data.data.data || []
        lastLoadedAt.value = Date.now()
      } catch (error) {
        if (error?.response?.status === 423) {
          notifications.value = []
          return
        }
        console.error('Erreur chargement notifications:', error)
      } finally {
        notificationsPromise = null
      }
    })()

    return notificationsPromise
  }

  async function markAllAsRead () {
    if (shouldSkipNotifications.value) {
      return
    }

    try {
      await api.post('/notifications/mark-all-as-read')
      for (const n of notifications.value) n.read_at = new Date().toISOString()
    } catch (error) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur marquage notifications:', error)
    }
  }

  async function handleNotificationClick (notification) {
    if (shouldSkipNotifications.value) {
      return
    }

    if (!notification.read_at) {
      try {
        await api.post(`/notifications/${notification.id}/mark-as-read`)
        notification.read_at = new Date().toISOString()
      } catch (error) {
        if (error?.response?.status === 423) {
          notifications.value = []
          return
        }
        console.error('Erreur marquage notification:', error)
      }
    }

    const resolvedActionUrl = resolveNotificationActionUrl(notification)
    if (!resolvedActionUrl) {
      return
    }

    try {
      const target = normalizeNotificationNavigationTarget(resolvedActionUrl)
      if (target.startsWith('/')) {
        await router.push(target)
        return
      }

      const parsed = new URL(target, window.location.origin)
      const targetPath = `${parsed.pathname}${parsed.search}${parsed.hash}`

      if (parsed.origin === window.location.origin) {
        await router.push(targetPath)
        return
      }

      window.open(parsed.toString(), '_blank', 'noopener,noreferrer')
    } catch (error) {
      console.warn('Notification action_url invalide:', resolvedActionUrl, error)
    }
  }

  async function deleteNotification (id) {
    if (shouldSkipNotifications.value) {
      return
    }

    try {
      await api.delete(`/notifications/${id}`)
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (error) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur suppression notification:', error)
    }
  }

  function getNotificationColor (notification) {
    const urgency = notification.data.urgency
    if (urgency === 'critical') return 'error'
    if (urgency === 'high') return 'warning'
    return 'info'
  }

  function getNotificationIcon (notification) {
    const type = notification.data.type
    if (type === 'subscription_expiring') {
      const urgency = notification.data.urgency
      if (urgency === 'critical') return 'mdi-alert-circle'
      if (urgency === 'high') return 'mdi-alert'
      return 'mdi-bell-ring'
    }
    return 'mdi-information-outline'
  }

  function getNotificationTitle (notification) {
    const type = notification.data.type
    if (type === 'subscription_expiring') {
      const days = notification.data.days_remaining
      return `Abonnement expire dans ${days} jour${days > 1 ? 's' : ''}`
    }
    return 'Notification'
  }

  function isSystemNotification (notification) {
    const systemTypes = [
      'subscription_expiring',
      'subscription_expired',
      'trial_expiring',
      'trial_expired',
      'action_deadline_reminder',
      'audit_deadline_reminder',
    ]
    return systemTypes.includes(notification.data.type)
  }

  function formatTime (dateString) {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now - date

    const minutes = Math.floor(diff / 60_000)
    const hours = Math.floor(diff / 3_600_000)
    const days = Math.floor(diff / 86_400_000)

    if (minutes < 1) return 'À l\'instant'
    if (minutes < 60) return `Il y a ${minutes} min`
    if (hours < 24) return `Il y a ${hours} h`
    if (days < 7) return `Il y a ${days} j`

    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
    })
  }

  function goToNotificationsPage () {
    menuOpen.value = false
    router.push('/company/notifications')
  }

  onMounted(() => {
    loadNotifications(true)
    refreshInterval.value = setInterval(loadNotifications, 60_000)
  })

  watch(shouldSkipNotifications, shouldSkip => {
    if (shouldSkip) {
      notifications.value = []
    } else {
      loadNotifications(true)
    }
  })

  onUnmounted(() => {
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
    }
  })
</script>

<style scoped>
.notif-trigger {
  border-radius: 12px;
}

.notif-popover {
  border: 1px solid rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 16px 10px;
  background: linear-gradient(120deg, rgba(2, 132, 199, 0.12), rgba(16, 185, 129, 0.1));
}

.notif-kicker {
  margin: 0;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(30, 41, 59, 0.72);
}

.notif-title {
  margin: 2px 0 0;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.1;
}

.notif-header-action {
  font-size: 12px;
}

.notif-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  padding: 12px 16px;
}

.notif-stat {
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(248, 250, 252, 0.75);
}

.notif-stat-label {
  display: block;
  color: rgba(71, 85, 105, 0.9);
  font-size: 12px;
}

.notif-stat-value {
  display: block;
  margin-top: 2px;
  font-size: 20px;
  line-height: 1;
}

.notif-filters {
  display: flex;
  gap: 8px;
  padding: 0 16px 10px;
}

.notif-filter-btn {
  flex: 1;
}

.notif-list-wrap {
  max-height: 380px;
  overflow-y: auto;
  padding: 0 12px 10px;
}

.notif-empty {
  display: grid;
  place-items: center;
  text-align: center;
  color: rgba(71, 85, 105, 0.9);
  padding: 24px 12px 18px;
}

.notif-list {
  display: grid;
  gap: 8px;
}

.notif-item {
  width: 100%;
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 14px;
  background: #fff;
  padding: 10px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: start;
  gap: 10px;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.notif-item:hover {
  border-color: rgba(2, 132, 199, 0.4);
  box-shadow: 0 8px 18px rgba(2, 132, 199, 0.14);
  transform: translateY(-1px);
}

.notif-item--unread {
  border-color: rgba(2, 132, 199, 0.45);
  background: linear-gradient(180deg, rgba(240, 249, 255, 0.95), rgba(255, 255, 255, 1));
}

.notif-item-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-grid;
  place-items: center;
  color: #fff;
}

.notif-item-icon--error {
  background: #dc2626;
}

.notif-item-icon--warning {
  background: #d97706;
}

.notif-item-icon--info {
  background: #0284c7;
}

.notif-item-content {
  display: grid;
  gap: 4px;
  min-width: 0;
}

.notif-item-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-item-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.25;
}

.notif-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: #0284c7;
  flex: 0 0 auto;
}

.notif-item-message {
  font-size: 12px;
  color: #475569;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notif-item-meta {
  font-size: 11px;
  color: #64748b;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.notif-delete {
  margin-top: -2px;
}

.notif-footer {
  border-top: 1px solid rgba(148, 163, 184, 0.2);
  padding: 12px;
}

@media (max-width: 600px) {
  .notif-popover {
    width: min(95vw, 440px);
  }

  .notif-list-wrap {
    max-height: 60vh;
  }
}
</style>
