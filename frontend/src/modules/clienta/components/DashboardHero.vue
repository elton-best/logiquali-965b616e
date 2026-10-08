<template>
  <div class="dashboard-hero">
    <div class="hero-surface">
      <div class="hero-content">
        <div class="greeting-section">
          <div class="greeting-text">
            <h1 class="greeting-title">{{ greetingMessage }}, <span class="user-name">{{ userName }}</span></h1>
            <p class="greeting-subtitle">
              <v-icon class="mr-1" size="16">mdi-calendar</v-icon>
              {{ formattedDate }}
              <span v-if="weather" class="mx-2">•</span>
              <v-icon v-if="weather" class="mr-1" size="16">mdi-weather-partly-cloudy</v-icon>
              <span v-if="weather">{{ weather }}</span>
            </p>
          </div>
          <div v-if="quickActions && quickActions.length > 0" class="quick-actions-compact">
            <v-btn
              v-for="action in quickActions"
              :key="action.id"
              class="quick-action-btn"
              :color="action.color"
              size="small"
              variant="tonal"
              @click="$emit('action', action.id)"
            >
              <v-icon size="18" start>{{ action.icon }}</v-icon>
              {{ action.title }}
            </v-btn>
          </div>
        </div>
        <v-btn
          class="hero-refresh"
          color="primary"
          icon
          :loading="loading"
          size="large"
          variant="elevated"
          @click="$emit('refresh')"
        >
          <v-icon>mdi-refresh</v-icon>
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  defineProps<{
    userName: string
    loading?: boolean
    quickActions?: Array<{ id: string, title: string, icon: string, color: string }>
    weather?: string
  }>()

  defineEmits<{
    refresh: []
    action: [actionId: string]
  }>()

  const greetingMessage = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Bonjour'
    if (hour < 18) return 'Bon après-midi'
    return 'Bonsoir'
  })

  const formattedDate = computed(() => {
    const options: Intl.DateTimeFormatOptions = {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    }
    return new Date().toLocaleDateString('fr-FR', options)
  })
</script>

<style scoped>
.dashboard-hero {
  margin-bottom: 22px;
}

.hero-surface {
  background: transparent;
  border: 0;
  padding: 4px 0 0;
}

.hero-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.greeting-section {
  flex: 1;
}

.greeting-title {
  font-family: var(--font-display) !important;
  font-size: clamp(1.35rem, 2vw, 1.65rem);
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--color-ink, #0f172a);
  margin-bottom: 8px;
  line-height: 1.2;
}

.user-name {
  color: var(--color-primary-600, #365a9d);
}

.greeting-subtitle {
  display: flex;
  align-items: center;
  font-family: var(--font-sans);
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

.quick-actions-compact {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.quick-action-btn {
  transition: all 0.2s ease;
}

.quick-action-btn:hover {
  transform: translateY(-2px);
}

.hero-refresh {
  border: 1px solid rgba(var(--v-theme-primary), 0.18);
  box-shadow: none;
  transition: all 0.3s ease;
}

.hero-refresh:hover {
  transform: translateY(-1px);
  box-shadow: 0 5px 14px rgba(59, 130, 246, 0.18);
}

@media (max-width: 768px) {
  .hero-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .greeting-title {
    font-size: 1.5rem;
  }
}
</style>
