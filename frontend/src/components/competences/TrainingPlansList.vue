<template>
  <div class="training-plans-list">
    <v-data-table
      :headers="headers"
      item-key="id"
      :items="trainingPlans"
      :loading="loading"
    >
      <template #item.employee="{ item }">
        <div class="d-flex align-center">
          <v-avatar class="mr-2" size="32">
            {{ item.employee.name.charAt(0) }}
          </v-avatar>
          <span>{{ item.employee.name }}</span>
        </div>
      </template>

      <template #item.priority="{ item }">
        <v-chip :color="getPriorityColor(item.priority)" size="small">
          {{ item.priority }}
        </v-chip>
      </template>

      <template #item.cost="{ item }">
        <span class="font-weight-medium">{{ formatCurrency(item.estimated_cost) }}</span>
      </template>

      <template #item.duration="{ item }">
        <span>{{ item.estimated_duration }} jours</span>
      </template>

      <template #item.actions="{ item }">
        <v-btn icon size="small" @click="downloadPlan(item)">
          <v-icon>mdi-download</v-icon>
        </v-btn>
        <v-btn icon size="small" @click="viewDetails(item)">
          <v-icon>mdi-eye</v-icon>
        </v-btn>
      </template>
    </v-data-table>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'

  const loading = ref(false)
  const trainingPlans = ref([])

  const headers = [
    { title: 'Employé', key: 'employee' },
    { title: 'Priorité', key: 'priority' },
    { title: 'Formations', key: 'training_count' },
    { title: 'Durée', key: 'duration' },
    { title: 'Coût estimé', key: 'cost' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function getPriorityColor (priority) {
    const colors = {
      high: 'error',
      medium: 'warning',
      low: 'success',
    }
    return colors[priority] || 'default'
  }

  function formatCurrency (amount) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
    }).format(amount)
  }

  function downloadPlan (plan) {
    console.log('Downloading plan:', plan)
  }

  function viewDetails (plan) {
    console.log('Viewing plan details:', plan)
  }

  onMounted(() => {
    // Load training plans
    trainingPlans.value = [
      {
        id: 1,
        employee: { name: 'Jean Dupont' },
        priority: 'high',
        training_count: 3,
        estimated_duration: 15,
        estimated_cost: 2500,
      },
    ]
  })
</script>
