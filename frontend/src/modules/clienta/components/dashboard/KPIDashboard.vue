<template>
  <v-card class="kpi-dashboard" elevation="2" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon color="primary">mdi-chart-box</v-icon>
        <span class="text-h6 font-weight-bold">Indicateurs clés de performance</span>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        rounded="lg"
        size="small"
        variant="tonal"
        @click="$emit('add-kpi')"
      >
        Ajouter KPI
      </v-btn>
    </v-card-title>
    <v-divider />

    <v-card-text class="pa-6">
      <v-row>
        <v-col
          v-for="kpi in kpis"
          :key="kpi.id"
          cols="12"
          lg="3"
          md="6"
        >
          <div class="kpi-card">
            <div class="kpi-header">
              <v-icon :color="kpi.color" size="24">{{ kpi.icon }}</v-icon>
              <v-chip
                :color="kpi.trendColor"
                size="x-small"
                variant="flat"
              >
                <v-icon size="12" start>{{ kpi.trendIcon }}</v-icon>
                {{ kpi.trend }}%
              </v-chip>
            </div>

            <div class="kpi-value">
              {{ kpi.value }}
              <span class="kpi-unit">{{ kpi.unit }}</span>
            </div>

            <div class="kpi-label">{{ kpi.label }}</div>

            <div class="kpi-progress">
              <v-progress-linear
                :color="kpi.color"
                height="6"
                :model-value="kpi.progress"
                rounded
              />
              <span class="progress-text">{{ kpi.progress }}% de l'objectif</span>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface KPI {
    id: string | number
    label: string
    value: number | string
    unit?: string
    icon: string
    color: string
    trend: number
    trendIcon: string
    trendColor: string
    progress: number
  }

  defineProps<{
    kpis: KPI[]
  }>()

  defineEmits<{
    'add-kpi': []
  }>()
</script>

<style scoped>
.kpi-dashboard {
  border: 1px solid rgba(148, 163, 184, 0.15);
}

.kpi-card {
  padding: 20px;
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.6));
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.1);
  transition: all 0.3s ease;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.kpi-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.kpi-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.kpi-unit {
  font-size: 1rem;
  font-weight: 400;
  color: #64748b;
  margin-left: 4px;
}

.kpi-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 12px;
}

.kpi-progress {
  margin-top: 12px;
}

.progress-text {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 4px;
  display: block;
}
</style>
