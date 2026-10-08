<template>
  <div class="progress-card">
    <div class="progress-header">
      <div class="progress-label">{{ label }}</div>
      <div class="progress-percentage">{{ completion }}%</div>
    </div>
    <v-progress-linear
      bg-color="#e2e8f0"
      class="mb-3"
      color="#5b8dd9"
      height="8"
      :model-value="completion"
      rounded
    />
    <div class="progress-footer">
      <div class="progress-value">{{ value }}</div>
      <div class="last-saved">
        <v-icon size="14">mdi-clock-outline</v-icon>
        {{ lastSaved }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  defineProps<{
    completion: number
    label: string
    value: string
    lastSaved: string
  }>()
</script>

<style scoped>
.progress-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  padding: 24px;
  transition: all 0.2s;
}

.progress-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.progress-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
}

.progress-percentage {
  font-size: 1.5rem;
  font-weight: 700;
  color: #5b8dd9;
}

.progress-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.last-saved {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  color: #94a3b8;
}
</style>
