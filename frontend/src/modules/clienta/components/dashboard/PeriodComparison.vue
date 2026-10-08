<template>
  <v-card class="comparison-card" elevation="2" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon color="primary">mdi-compare</v-icon>
        <span class="text-h6 font-weight-bold">Comparaison de périodes</span>
      </div>
    </v-card-title>
    <v-divider />

    <v-card-text class="pa-6">
      <v-row>
        <v-col
          v-for="metric in metrics"
          :key="metric.id"
          cols="12"
          md="6"
        >
          <div class="metric-comparison">
            <div class="metric-header">
              <v-icon :color="metric.iconColor" size="20">{{ metric.icon }}</v-icon>
              <span class="metric-label">{{ metric.label }}</span>
            </div>

            <div class="metric-values">
              <div class="current-period">
                <span class="period-label">Période actuelle</span>
                <span class="period-value">{{ metric.current }}</span>
              </div>

              <v-icon class="comparison-arrow" color="grey-lighten-1">
                mdi-arrow-right
              </v-icon>

              <div class="previous-period">
                <span class="period-label">Période précédente</span>
                <span class="period-value">{{ metric.previous }}</span>
              </div>
            </div>

            <div class="metric-change">
              <v-chip
                :color="metric.changeColor"
                size="small"
                variant="flat"
              >
                <v-icon size="16" start>{{ metric.changeIcon }}</v-icon>
                {{ metric.changePercent }}%
              </v-chip>
              <span class="change-text">{{ metric.changeText }}</span>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface Metric {
    id: string
    label: string
    icon: string
    iconColor: string
    current: number | string
    previous: number | string
    changePercent: number
    changeText: string
    changeIcon: string
    changeColor: string
  }

  defineProps<{
    metrics: Metric[]
  }>()
</script>

<style scoped>
.comparison-card {
  border: 1px solid rgba(148, 163, 184, 0.15);
}

.metric-comparison {
  padding: 16px;
  background: rgba(248, 250, 252, 0.5);
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.1);
}

.metric-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.metric-label {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.metric-values {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.current-period,
.previous-period {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.period-label {
  font-size: 0.75rem;
  color: #64748b;
}

.period-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.comparison-arrow {
  flex-shrink: 0;
}

.metric-change {
  display: flex;
  align-items: center;
  gap: 8px;
}

.change-text {
  font-size: 0.75rem;
  color: #64748b;
}
</style>
