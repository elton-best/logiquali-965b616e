<template>
  <v-card class="timeline-card" elevation="2" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon color="primary">mdi-timeline-clock</v-icon>
        <span class="text-h6 font-weight-bold">Chronologie des activités</span>
      </div>
      <v-chip color="primary" size="small" variant="tonal">
        {{ events.length }} événements
      </v-chip>
    </v-card-title>
    <v-divider />

    <v-card-text class="pa-6">
      <div class="timeline">
        <div
          v-for="(event, index) in events"
          :key="event.id"
          class="timeline-item"
          :class="{ 'last-item': index === events.length - 1 }"
        >
          <div class="timeline-marker">
            <v-avatar
              :color="event.color"
              size="40"
              variant="flat"
            >
              <v-icon color="white" size="20">{{ event.icon }}</v-icon>
            </v-avatar>
            <div v-if="index < events.length - 1" class="timeline-line" />
          </div>

          <div class="timeline-content">
            <div class="timeline-header">
              <h4 class="timeline-title">{{ event.title }}</h4>
              <span class="timeline-time">{{ formatTime(event.timestamp) }}</span>
            </div>
            <p class="timeline-description">{{ event.description }}</p>
            <div v-if="event.metadata" class="timeline-metadata">
              <v-chip
                v-for="(value, key) in event.metadata"
                :key="key"
                size="x-small"
                variant="outlined"
              >
                {{ key }}: {{ value }}
              </v-chip>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasMore" class="text-center mt-4">
        <v-btn
          color="primary"
          rounded="lg"
          variant="text"
          @click="$emit('load-more')"
        >
          Charger plus
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface TimelineEvent {
    id: string | number
    title: string
    description: string
    timestamp: string
    icon: string
    color: string
    metadata?: Record<string, string>
  }

  defineProps<{
    events: TimelineEvent[]
    hasMore?: boolean
  }>()

  defineEmits<{
    'load-more': []
  }>()

  function formatTime (timestamp: string): string {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60_000)
    const diffHours = Math.floor(diffMs / 3_600_000)
    const diffDays = Math.floor(diffMs / 86_400_000)

    if (diffMins < 1) return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    if (diffHours < 24) return `Il y a ${diffHours}h`
    if (diffDays === 1) return 'Hier'
    if (diffDays < 7) return `Il y a ${diffDays} jours`

    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
</script>

<style scoped>
.timeline-card {
  border: 1px solid rgba(148, 163, 184, 0.15);
}

.timeline {
  position: relative;
}

.timeline-item {
  display: flex;
  gap: 16px;
  position: relative;
}

.timeline-item:not(.last-item) {
  margin-bottom: 24px;
}

.timeline-marker {
  position: relative;
  flex-shrink: 0;
}

.timeline-line {
  position: absolute;
  left: 50%;
  top: 40px;
  bottom: -24px;
  width: 2px;
  background: linear-gradient(180deg, rgba(148, 163, 184, 0.3), rgba(148, 163, 184, 0.1));
  transform: translateX(-50%);
}

.timeline-content {
  flex: 1;
  padding-top: 4px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.timeline-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.timeline-time {
  font-size: 0.75rem;
  color: #64748b;
}

.timeline-description {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0 0 8px 0;
  line-height: 1.5;
}

.timeline-metadata {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>
