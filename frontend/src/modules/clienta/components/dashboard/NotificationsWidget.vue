<template>
  <v-card class="notifications-card" elevation="2" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon color="warning">mdi-bell-ring</v-icon>
        <span class="text-h6 font-weight-bold">Notifications</span>
      </div>
      <v-chip color="warning" size="small" variant="flat">
        {{ unreadCount }}
      </v-chip>
    </v-card-title>
    <v-divider />

    <v-card-text class="pa-0">
      <v-list v-if="notifications.length > 0" class="py-0">
        <v-list-item
          v-for="(notification, index) in notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'unread': !notification.read, 'border-b': index < notifications.length - 1 }"
          @click="markAsRead(notification.id)"
        >
          <template #prepend>
            <v-avatar
              :color="getNotificationColor(notification.type)"
              size="40"
              variant="tonal"
            >
              <v-icon :color="getNotificationColor(notification.type)" size="20">
                {{ getNotificationIcon(notification.type) }}
              </v-icon>
            </v-avatar>
          </template>

          <v-list-item-title class="font-weight-medium">
            {{ notification.title }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ notification.message }}
          </v-list-item-subtitle>
          <v-list-item-subtitle class="text-caption mt-1">
            <v-icon size="12">mdi-clock-outline</v-icon>
            {{ formatTime(notification.createdAt) }}
          </v-list-item-subtitle>

          <template #append>
            <v-btn
              icon
              size="small"
              variant="text"
              @click.stop="dismissNotification(notification.id)"
            >
              <v-icon size="18">mdi-close</v-icon>
            </v-btn>
          </template>
        </v-list-item>
      </v-list>

      <div v-else class="text-center pa-8">
        <v-icon class="mb-3" color="grey-lighten-1" size="48">mdi-bell-off</v-icon>
        <p class="text-body-2 text-medium-emphasis">Aucune notification</p>
      </div>
    </v-card-text>

    <v-divider />
    <v-card-actions class="pa-4">
      <v-btn
        block
        color="primary"
        rounded="lg"
        variant="text"
        @click="$emit('view-all')"
      >
        Voir toutes les notifications
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Notification {
    id: string | number
    type: 'info' | 'warning' | 'error' | 'success'
    title: string
    message: string
    createdAt: string
    read: boolean
  }

  const props = defineProps<{
    notifications: Notification[]
  }>()

  const emit = defineEmits<{
    'mark-read': [id: string | number]
    'dismiss': [id: string | number]
    'view-all': []
  }>()

  const unreadCount = computed(() => {
    return props.notifications.filter(n => !n.read).length
  })

  function getNotificationIcon (type: string): string {
    const icons: Record<string, string> = {
      info: 'mdi-information',
      warning: 'mdi-alert',
      error: 'mdi-alert-circle',
      success: 'mdi-check-circle',
    }
    return icons[type] || 'mdi-bell'
  }

  function getNotificationColor (type: string): string {
    const colors: Record<string, string> = {
      info: 'info',
      warning: 'warning',
      error: 'error',
      success: 'success',
    }
    return colors[type] || 'primary'
  }

  function formatTime (dateString: string): string {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60_000)
    const diffHours = Math.floor(diffMs / 3_600_000)

    if (diffMins < 1) return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    if (diffHours < 24) return `Il y a ${diffHours}h`
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  }

  function markAsRead (id: string | number) {
    emit('mark-read', id)
  }

  function dismissNotification (id: string | number) {
    emit('dismiss', id)
  }
</script>

<style scoped>
.notifications-card {
  border: 1px solid rgba(148, 163, 184, 0.15);
}

.notification-item {
  padding: 12px 16px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.notification-item.unread {
  background: rgba(91, 141, 217, 0.05);
}

.notification-item:hover {
  background: rgba(91, 141, 217, 0.08);
}

.border-b {
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}
</style>
