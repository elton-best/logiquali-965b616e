<template>
  <div class="metrics-grid mb-6">
    <div class="metric-card">
      <div class="metric-icon-wrapper primary">
        <v-icon size="22">mdi-folder-multiple-outline</v-icon>
      </div>
      <div class="metric-content">
        <div class="metric-label">Activités</div>
        <div class="metric-value">{{ activitiesCount }}</div>
      </div>
    </div>

    <div class="metric-card">
      <div class="metric-icon-wrapper secondary">
        <v-icon size="22">mdi-format-list-checks</v-icon>
      </div>
      <div class="metric-content">
        <div class="metric-label">Sous-activités</div>
        <div class="metric-value">{{ subActivitiesCount }}</div>
      </div>
    </div>

    <div class="metric-card progress-card">
      <div class="metric-icon-wrapper success">
        <v-icon size="22">mdi-chart-line</v-icon>
      </div>
      <div class="metric-content flex-1">
        <div class="flex items-center justify-between mb-1">
          <div class="metric-label">Progression globale</div>
          <div class="metric-value-inline">{{ completionRate }}%</div>
        </div>
        <v-progress-linear
          class="progress-bar"
          color="success"
          height="10"
          :model-value="completionRate"
          rounded
        />
      </div>
    </div>

    <div class="metric-card distribution-card">
      <div class="metric-icon-wrapper info">
        <v-icon size="22">mdi-chart-donut</v-icon>
      </div>
      <div class="metric-content flex-1">
        <div class="metric-label mb-2">Répartition des tâches</div>
        <div class="distribution-chips">
          <div class="dist-chip success">
            <div class="dist-dot" />
            <span>{{ doneSubActivities }} Fait</span>
          </div>
          <div class="dist-chip warning">
            <div class="dist-dot" />
            <span>{{ inProgressSubActivities }} En cours</span>
          </div>
          <div class="dist-chip grey">
            <div class="dist-dot" />
            <span>{{ notStartedSubActivities }} À lancer</span>
          </div>
        </div>
        <div class="metric-footer">
          <v-icon class="mr-1" size="14">mdi-calendar-month</v-icon>
          {{ coveredMonthsCount }} mois planifiés{{ distributionLabel ? ` • ${distributionLabel}` : '' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  defineProps({
    activitiesCount: {
      type: Number,
      required: true,
    },
    subActivitiesCount: {
      type: Number,
      required: true,
    },
    completionRate: {
      type: Number,
      required: true,
    },
    doneSubActivities: {
      type: Number,
      required: true,
    },
    inProgressSubActivities: {
      type: Number,
      required: true,
    },
    notStartedSubActivities: {
      type: Number,
      required: true,
    },
    coveredMonthsCount: {
      type: Number,
      required: true,
    },
    distributionLabel: {
      type: String,
      default: '',
    },
  })
</script>
