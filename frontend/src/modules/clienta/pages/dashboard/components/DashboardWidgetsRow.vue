<template>
  <v-row class="mb-6">
    <v-col v-if="showTasks" cols="12" lg="4" md="6">
      <PriorityTasksCard :priority-tasks="priorityTasks" />
    </v-col>

    <v-col cols="12" lg="4" md="6">
      <QuickActionsCard :quick-actions="quickActions" @action="emit('quick-action', $event)" />
    </v-col>

    <v-col cols="12" lg="4" md="6">
      <LeadershipQuickCard
        :can-view-leadership-org-chart="canViewLeadershipOrgChart"
        :can-view-leadership-policy="canViewLeadershipPolicy"
        :can-view-leadership-roles="canViewLeadershipRoles"
        :employees-with-job-description="employeesWithJobDescription"
        :job-description-progress="jobDescriptionProgress"
        :leadership-quick-items-available="leadershipQuickItemsAvailable"
        :org-chart-color="orgChartColor"
        :org-chart-status="orgChartStatus"
        :policy-status-color="policyStatusColor"
        :policy-status-icon="policyStatusIcon"
        :policy-status-label="policyStatusLabel"
        :policy-status-text="policyStatusText"
        :total-employees="totalEmployees"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import LeadershipQuickCard from '@/modules/clienta/pages/dashboard/components/LeadershipQuickCard.vue'
  import PriorityTasksCard, {
    type PriorityTask,
  } from '@/modules/clienta/pages/dashboard/components/PriorityTasksCard.vue'
  import QuickActionsCard, {
    type QuickAction,
  } from '@/modules/clienta/pages/dashboard/components/QuickActionsCard.vue'

  defineProps<{
    showTasks: boolean
    priorityTasks: PriorityTask[]
    quickActions: QuickAction[]
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

  const emit = defineEmits<{
    (event: 'quick-action', id: string): void
  }>()
</script>
