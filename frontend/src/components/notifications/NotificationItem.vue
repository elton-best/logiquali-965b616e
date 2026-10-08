<template>
  <div
    class="notification-item"
    :class="{ 'unread': !notification.is_read }"
    @click="$emit('click')"
  >
    <div class="notification-content">
      <div class="notification-icon">
        <v-avatar :color="iconColor" size="40">
          <v-icon color="white" :icon="icon" size="20" />
        </v-avatar>
      </div>

      <div class="notification-body">
        <div class="notification-header">
          <h4 class="notification-title">{{ notification.title }}</h4>
          <span class="notification-time">{{ relativeTime }}</span>
        </div>

        <p class="notification-message">{{ notification.message }}</p>

        <div v-if="!notification.is_read" class="unread-indicator">
          <v-icon color="primary" size="8">mdi-circle</v-icon>
        </div>
      </div>
    </div>

    <div class="notification-actions">
      <v-menu location="bottom end">
        <template #activator="{ props }">
          <v-btn
            icon="mdi-dots-vertical"
            size="small"
            variant="text"
            v-bind="props"
            @click.stop
          />
        </template>

        <v-list density="compact">
          <v-list-item
            v-if="!notification.is_read"
            prepend-icon="mdi-check"
            @click.stop="$emit('mark-read')"
          >
            Marquer comme lu
          </v-list-item>

          <v-list-item
            v-else
            prepend-icon="mdi-email-outline"
            @click.stop="$emit('mark-unread')"
          >
            Marquer comme non lu
          </v-list-item>

          <v-divider />

          <v-list-item
            prepend-icon="mdi-delete-outline"
            @click.stop="$emit('delete')"
          >
            Supprimer
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { UserNotification } from '@/composables/useNotifications'
  import { computed } from 'vue'
  import { useNotifications } from '@/composables/useNotifications'

  const props = defineProps<{
    notification: UserNotification
  }>()

  defineEmits<{
    'click': []
    'mark-read': []
    'mark-unread': []
    'delete': []
  }>()

  const { getNotificationIcon, getNotificationColor, formatRelativeTime } = useNotifications()

  const icon = computed(() => getNotificationIcon(props.notification.type))
  const iconColor = computed(() => getNotificationColor(props.notification.type))
  const relativeTime = computed(() => formatRelativeTime(props.notification.created_at))
</script>

<style scoped>
.notification-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
}

.notification-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.notification-item.unread {
  background-color: rgba(33, 150, 243, 0.04);
}

.notification-item.unread:hover {
  background-color: rgba(33, 150, 243, 0.08);
}

.notification-content {
  display: flex;
  align-items: flex-start;
  flex: 1;
  gap: 12px;
}

.notification-icon {
  flex-shrink: 0;
}

.notification-body {
  flex: 1;
  min-width: 0;
  position: relative;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
}

.notification-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.notification-time {
  font-size: 0.75rem;
  color: #64748b;
  flex-shrink: 0;
}

.notification-message {
  font-size: 0.8125rem;
  color: #475569;
  margin: 0;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.unread-indicator {
  position: absolute;
  top: 2px;
  right: -8px;
}

.notification-actions {
  flex-shrink: 0;
  margin-left: 8px;
  opacity: 0;
  transition: opacity 0.2s;
}

.notification-item:hover .notification-actions {
  opacity: 1;
}
</style>
