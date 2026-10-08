<template>
  <v-menu :close-on-content-click="false" max-width="400" offset-y>
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        :class="{ 'notification-pulse': unreadCount > 0 }"
        icon
        variant="text"
      >
        <v-badge
          v-if="unreadCount > 0"
          color="error"
          :content="unreadCount > 99 ? '99+' : unreadCount"
          overlap
        >
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
        <v-icon v-else>mdi-bell-outline</v-icon>
      </v-btn>
    </template>

    <v-card elevation="8" style="border-radius: 12px">
      <v-card-title class="d-flex align-center justify-space-between pa-4">
        <div class="d-flex align-center">
          <v-icon class="mr-2" color="primary">mdi-bell</v-icon>
          <span class="font-weight-bold">Notifications</span>
          <v-chip
            v-if="unreadCount > 0"
            class="ml-2"
            color="error"
            size="x-small"
            variant="flat"
          >
            {{ unreadCount }}
          </v-chip>
        </div>
        <v-btn
          v-if="notifications.length > 0"
          color="primary"
          size="small"
          variant="text"
          @click="markAllAsRead"
        >
          Tout marquer lu
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-0" style="max-height: 400px; overflow-y: auto">
        <v-list v-if="notifications.length > 0" class="py-0">
          <v-list-item
            v-for="notification in notifications"
            :key="notification.id"
            class="notification-item"
            :class="{ 'bg-surface-variant': !notification.read_at }"
            @click="handleNotificationClick(notification)"
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

            <v-list-item-title class="font-weight-medium mb-1">
              {{ notification.title }}
            </v-list-item-title>
            <v-list-item-subtitle class="text-caption">
              {{ notification.message }}
            </v-list-item-subtitle>
            <v-list-item-subtitle class="text-caption text-disabled mt-1">
              {{ formatDate(notification.created_at) }}
            </v-list-item-subtitle>

            <template #append>
              <v-btn
                v-if="!notification.read_at"
                color="primary"
                icon="mdi-circle"
                size="x-small"
                variant="text"
              />
            </template>
          </v-list-item>
        </v-list>

        <div v-else class="pa-8 text-center">
          <v-icon color="grey-lighten-1" size="48">mdi-bell-off-outline</v-icon>
          <p class="text-body-2 text-medium-emphasis mt-4">
            Aucune notification
          </p>
        </div>
      </v-card-text>

      <v-divider v-if="notifications.length > 0" />

      <!-- Removed: "Voir toutes les notifications" link as page doesn't exist -->
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'

  interface Notification {
    id: number
    type: 'kyc' | 'subscription' | 'payment' | 'system'
    title: string
    message: string
    read_at: string | null
    created_at: string
    link?: string
  }

  const router = useRouter()

  // Empty notifications for now - will be loaded from API
  const notifications = ref<Notification[]>([])

  const unreadCount = computed(() =>
    notifications.value.filter(n => !n.read_at).length,
  )

  function getNotificationColor (type: string): string {
    const colors: Record<string, string> = {
      kyc: 'warning',
      subscription: 'info',
      payment: 'success',
      system: 'primary',
    }
    return colors[type] || 'primary'
  }

  function getNotificationIcon (type: string): string {
    const icons: Record<string, string> = {
      kyc: 'mdi-file-document-alert',
      subscription: 'mdi-calendar-clock',
      payment: 'mdi-cash-check',
      system: 'mdi-information',
    }
    return icons[type] || 'mdi-bell'
  }

  function formatDate (dateString: string): string {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60_000)
    const diffHours = Math.floor(diffMs / 3_600_000)
    const diffDays = Math.floor(diffMs / 86_400_000)

    if (diffMins < 1) return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    if (diffHours < 24) return `Il y a ${diffHours}h`
    if (diffDays < 7) return `Il y a ${diffDays}j`

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
    }).format(date)
  }

  function handleNotificationClick (notification: Notification) {
    // Marquer comme lu
    notification.read_at = new Date().toISOString()

    // Naviguer vers le lien si disponible
    if (notification.link) {
      router.push(notification.link)
    }
  }

  function markAllAsRead () {
    for (const n of notifications.value) {
      if (!n.read_at) {
        n.read_at = new Date().toISOString()
      }
    }
  }

  // TODO: Charger les vraies notifications depuis l'API
  onMounted(() => {
  // loadNotifications()
  })
</script>

<style scoped>
.notification-pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.notification-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: rgba(var(--v-theme-surface-variant), 0.5) !important;
}
</style>
