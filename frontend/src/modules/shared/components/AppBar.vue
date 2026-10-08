<template>
  <v-app-bar class="border-b" elevation="0" height="64">
    <v-container class="d-flex align-center px-6" fluid>
      <!-- Search Bar -->
      <v-text-field
        class="mr-4"
        density="compact"
        hide-details
        placeholder="Rechercher..."
        prepend-inner-icon="mdi-magnify"
        single-line
        style="max-width: 500px;"
        variant="outlined"
      />

      <v-spacer />

      <!-- Right Actions -->
      <v-menu offset-y>
        <template #activator="{ props: menuProps }">
          <v-btn class="mr-2" icon variant="text" v-bind="menuProps">
            <v-badge color="error" :content="unreadCount" dot>
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
          </v-btn>
        </template>
        <v-card max-width="400" min-width="350">
          <v-card-title class="d-flex align-center justify-space-between bg-surface-variant">
            <span class="font-weight-bold">Notifications</span>
            <v-btn color="primary" size="small" variant="text" @click="$emit('mark-all-read')">
              Tout marquer comme lu
            </v-btn>
          </v-card-title>
          <v-list class="overflow-y-auto" lines="two" max-height="400">
            <v-list-item
              v-for="notif in notifications"
              :key="notif.id"
              :class="{ 'bg-blue-lighten-5': !notif.read }"
            >
              <template #prepend>
                <v-avatar :color="notif.color" size="40" variant="tonal">
                  <v-icon :color="notif.color">{{ notif.icon }}</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-medium">
                {{ notif.title }}
              </v-list-item-title>
              <v-list-item-subtitle>{{ notif.message }}</v-list-item-subtitle>
              <template #append>
                <span class="text-caption text-grey">{{ notif.time }}</span>
              </template>
            </v-list-item>
          </v-list>
          <v-divider />
          <v-card-actions>
            <v-btn block color="primary" to="/notifications" variant="text">
              Voir toutes les notifications
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-menu>

      <v-btn class="mr-2" icon variant="text" @click="toggleDarkMode">
        <v-icon>{{ darkMode ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent' }}</v-icon>
      </v-btn>

      <UserMenu :user-email="userEmail" :user-initials="userInitials" :user-name="userName" />
    </v-container>
  </v-app-bar>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useTheme } from 'vuetify'
  import UserMenu from './UserMenu.vue'

  interface Notification {
    id: number
    title: string
    message: string
    time: string
    icon: string
    color: string
    read: boolean
  }

  const props = defineProps<{
    userName: string
    userEmail: string
    userInitials: string
    notifications?: Notification[]
  }>()

  defineEmits<{
    'mark-all-read': []
  }>()

  const theme = useTheme()
  const darkMode = ref(theme.global.current.value.dark)

  const unreadCount = computed(() => {
    return props.notifications?.filter(n => !n.read).length || 0
  })

  function toggleDarkMode () {
    darkMode.value = !darkMode.value
    theme.global.name.value = darkMode.value ? 'dark' : 'light'
  }
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
