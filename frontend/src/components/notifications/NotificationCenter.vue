<template>
  <div class="notification-center">
    <v-menu
      v-model="menuOpen"
      :close-on-content-click="false"
      location="bottom end"
      offset="8"
      width="420"
    >
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          class="notification-trigger"
          icon
          variant="text"
        >
          <v-badge
            color="error"
            :content="unreadCount"
            :model-value="hasUnread"
            overlap
          >
            <v-icon>mdi-bell-outline</v-icon>
          </v-badge>
        </v-btn>
      </template>

      <v-card class="notification-panel" elevation="8" rounded="lg">
        <!-- Header -->
        <v-card-title class="d-flex align-center justify-space-between pa-4 border-b">
          <div class="d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-bell</v-icon>
            <span class="text-h6">Notifications</span>
            <v-chip
              v-if="hasUnread"
              class="ml-2"
              color="error"
              size="small"
              variant="flat"
            >
              {{ unreadCount }}
            </v-chip>
          </div>

          <v-btn
            v-if="hasUnread"
            size="small"
            variant="text"
            @click="handleMarkAllAsRead"
          >
            Tout marquer lu
          </v-btn>
        </v-card-title>

        <!-- Tabs -->
        <v-tabs
          v-model="activeTab"
          bg-color="transparent"
          color="primary"
          density="compact"
        >
          <v-tab value="all">
            Toutes
            <v-chip
              v-if="stats.total_count > 0"
              class="ml-2"
              size="x-small"
              variant="tonal"
            >
              {{ stats.total_count }}
            </v-chip>
          </v-tab>
          <v-tab value="unread">
            Non lues
            <v-chip
              v-if="unreadCount > 0"
              class="ml-2"
              color="error"
              size="x-small"
              variant="flat"
            >
              {{ unreadCount }}
            </v-chip>
          </v-tab>
        </v-tabs>

        <v-divider />

        <!-- Content -->
        <v-card-text class="pa-0">
          <v-window v-model="activeTab">
            <!-- All Notifications -->
            <v-window-item value="all">
              <div class="notification-list">
                <div v-if="loading" class="text-center pa-4">
                  <v-progress-circular color="primary" indeterminate />
                </div>

                <div v-else-if="notifications.length === 0" class="empty-state pa-6 text-center">
                  <v-icon color="grey-lighten-1" size="64">mdi-bell-off-outline</v-icon>
                  <p class="text-body-2 text-grey mt-4">Aucune notification</p>
                </div>

                <div v-else>
                  <NotificationItem
                    v-for="notification in notifications"
                    :key="notification.id"
                    :notification="notification"
                    @click="handleNotificationClick(notification)"
                    @delete="handleDelete(notification.id)"
                    @mark-read="markAsRead(notification.id)"
                    @mark-unread="markAsUnread(notification.id)"
                  />
                </div>
              </div>
            </v-window-item>

            <!-- Unread Notifications -->
            <v-window-item value="unread">
              <div class="notification-list">
                <div v-if="loading" class="text-center pa-4">
                  <v-progress-circular color="primary" indeterminate />
                </div>

                <div v-else-if="unreadNotifications.length === 0" class="empty-state pa-6 text-center">
                  <v-icon color="success" size="64">mdi-check-circle-outline</v-icon>
                  <p class="text-body-2 text-grey mt-4">Toutes les notifications sont lues</p>
                </div>

                <div v-else>
                  <NotificationItem
                    v-for="notification in unreadNotifications"
                    :key="notification.id"
                    :notification="notification"
                    @click="handleNotificationClick(notification)"
                    @delete="handleDelete(notification.id)"
                    @mark-read="markAsRead(notification.id)"
                    @mark-unread="markAsUnread(notification.id)"
                  />
                </div>
              </div>
            </v-window-item>
          </v-window>
        </v-card-text>

        <!-- Footer -->
        <v-divider />
        <v-card-actions class="pa-3 justify-center">
          <v-btn
            block
            color="primary"
            variant="text"
            @click="goToNotificationsPage"
          >
            Voir toutes les notifications
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-menu>
  </div>
</template>

<script setup lang="ts">
  import type { UserNotification } from '@/composables/useNotifications'
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useNotifications } from '@/composables/useNotifications'
  import NotificationItem from './NotificationItem.vue'

  const router = useRouter()
  const menuOpen = ref(false)
  const activeTab = ref('all')

  const {
    notifications,
    stats,
    loading,
    unreadCount,
    hasUnread,
    unreadNotifications,
    markAsRead,
    markAsUnread,
    markAllAsRead,
    deleteNotification,
  } = useNotifications()

  async function handleNotificationClick (notification: UserNotification) {
    if (!notification.is_read) {
      await markAsRead(notification.id)
    }

    if (notification.action_url) {
      menuOpen.value = false
      router.push(notification.action_url)
    }
  }

  async function handleMarkAllAsRead () {
    try {
      await markAllAsRead()
    } catch (error) {
      console.error('Failed to mark all as read:', error)
    }
  }

  async function handleDelete (notificationId: number) {
    try {
      await deleteNotification(notificationId)
    } catch (error) {
      console.error('Failed to delete notification:', error)
    }
  }

  function goToNotificationsPage () {
    menuOpen.value = false
    router.push('/notifications')
  }
</script>

<style scoped>
.notification-center {
  position: relative;
}

.notification-trigger {
  position: relative;
}

.notification-panel {
  max-height: 600px;
  display: flex;
  flex-direction: column;
}

.notification-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-list::-webkit-scrollbar {
  width: 6px;
}

.notification-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.empty-state {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
</style>
