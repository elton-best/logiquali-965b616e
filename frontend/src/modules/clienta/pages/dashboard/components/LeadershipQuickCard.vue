<template>
  <v-card v-if="leadershipQuickItemsAvailable" class="dashboard-panel h-100" elevation="0" rounded="lg">
    <v-card-title class="pa-4">
      <v-icon color="primary" size="20">mdi-tie</v-icon>
      <span class="ml-2 text-subtitle-1 font-weight-bold">Leadership (Point 5)</span>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-2">
      <v-list class="py-0">
        <v-list-item
          v-if="canViewLeadershipPolicy"
          class="quick-action-item"
          rounded="lg"
          :to="'/company/leadership/policy'"
        >
          <template #prepend>
            <v-avatar :color="policyStatusColor" size="36" variant="tonal">
              <v-icon :color="policyStatusColor" size="18">{{ policyStatusIcon }}</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">
            Politique QHSE
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ policyStatusText }}
          </v-list-item-subtitle>
          <template #append>
            <v-chip :color="policyStatusColor" size="small" variant="flat">
              {{ policyStatusLabel }}
            </v-chip>
          </template>
        </v-list-item>

        <v-list-item
          v-if="canViewLeadershipOrgChart"
          class="quick-action-item"
          rounded="lg"
          :to="'/company/leadership/organization-chart'"
        >
          <template #prepend>
            <v-avatar :color="orgChartColor" size="36" variant="tonal">
              <v-icon :color="orgChartColor" size="18">mdi-sitemap</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">
            Organigramme
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ orgChartStatus }}
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item
          v-if="canViewLeadershipRoles"
          class="quick-action-item"
          rounded="lg"
          :to="'/company/leadership/roles'"
        >
          <template #prepend>
            <v-avatar color="info" size="36" variant="tonal">
              <v-icon color="info" size="18">mdi-account-star</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">
            Rôles et Responsabilités
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ employeesWithJobDescription }} / {{ totalEmployees }} collaborateurs
          </v-list-item-subtitle>
          <template #append>
            <v-progress-circular
              :color="jobDescriptionProgress === 100 ? 'success' : 'warning'"
              :model-value="jobDescriptionProgress"
              size="32"
              width="3"
            >
              <span class="text-caption">{{ jobDescriptionProgress }}%</span>
            </v-progress-circular>
          </template>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  const props = defineProps<{
    leadershipQuickItemsAvailable: boolean
    canViewLeadershipPolicy: boolean
    canViewLeadershipOrgChart: boolean
    canViewLeadershipRoles: boolean
    policyStatusColor: string
    policyStatusIcon: string
    policyStatusLabel: string
    policyStatusText: string
    orgChartColor: string
    orgChartStatus: string
    employeesWithJobDescription: number
    totalEmployees: number
    jobDescriptionProgress: number
  }>()
</script>

<style scoped>
.quick-action-item {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  margin-bottom: 4px;
}

.quick-action-item:hover {
  background: rgba(91, 141, 217, 0.04);
  border-color: rgba(91, 141, 217, 0.2);
  transform: translateX(4px);
}

.quick-action-item:active {
  transform: translateX(2px) scale(0.98);
}
</style>
