<template>
  <div class="objective-tracker">
    <h3 class="tracker-title">Suivi des Objectifs</h3>

    <div class="objectives-list">
      <div v-for="obj in objectives" :key="obj.id" class="objective-card">
        <div class="objective-header">
          <h4 class="objective-title">{{ obj.title || obj.description }}</h4>
          <span class="objective-progress-text">{{ obj.progress }}%</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar" :style="{ width: `${obj.progress}%` }" />
        </div>
        <div class="objective-footer">
          <span class="objective-date">Échéance: {{ formatDate(obj.deadline) }}</span>
          <span :class="['objective-status', obj.progress >= 100 ? 'status-success' : 'status-warning']">
            {{ obj.progress >= 100 ? 'Atteint' : 'En cours' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Objective } from '@/types/improvement'
  import { computed } from 'vue'
  import { useObjectiveStore } from '@/stores/improvement/objectiveStore'

  const objectiveStore = useObjectiveStore()
  const objectives = computed<Objective[]>(() => objectiveStore.objectives)
  const formatDate = (date?: string) => (date ? new Date(date).toLocaleDateString('fr-FR') : '-')

  objectiveStore.fetchObjectives()
</script>

<style scoped>
.objective-tracker {
  padding: var(--spacing-4);
}

.tracker-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-4);
}

.objectives-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.objective-card {
  padding: var(--spacing-4);
  background: var(--color-background-secondary);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-normal);
}

.objective-card:hover {
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.objective-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-2);
}

.objective-title {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin: 0;
}

.objective-progress-text {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  font-weight: var(--font-weight-medium);
}

.progress-bar-container {
  width: 100%;
  height: 8px;
  background: var(--color-border);
  border-radius: var(--border-radius-full);
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary), var(--color-primary-dark));
  transition: width var(--transition-slow);
  border-radius: var(--border-radius-full);
}

.objective-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: var(--spacing-2);
  font-size: var(--font-size-xs);
}

.objective-date {
  color: var(--color-text-secondary);
}

.objective-status {
  font-weight: var(--font-weight-medium);
}

.status-success {
  color: var(--color-success);
}

.status-warning {
  color: var(--color-warning);
}

@media (max-width: 640px) {
  .objective-header {
    flex-direction: column;
    gap: var(--spacing-1);
  }
}
</style>
