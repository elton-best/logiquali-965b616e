<template>
  <ProcessLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Pilotage opérationnel</div>
          <h1 class="role-dashboard__title">Mes processus</h1>
          <p class="role-dashboard__subtitle">Surveillez la performance, les risques et les actions de vos processus.</p>
        </div>
      </div>

    <v-row>
      <v-col cols="12">
        <v-card title="Mes Processus">
          <v-card-text>
            <v-row>
              <v-col v-for="process in myProcesses" :key="process.id" cols="12" md="4">
                <v-card :to="`/process/my-processes/${process.id}`" variant="outlined">
                  <v-card-title>{{ process.name }}</v-card-title>
                  <v-card-text>
                    <div class="d-flex justify-space-between mb-2">
                      <span>Indicateurs</span>
                      <v-chip :color="process.indicatorStatus" size="small">{{ process.indicators }}</v-chip>
                    </div>
                    <div class="d-flex justify-space-between mb-2">
                      <span>Risques</span>
                      <v-chip color="warning" size="small">{{ process.risks }}</v-chip>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span>Actions</span>
                      <v-chip color="info" size="small">{{ process.actions }}</v-chip>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card title="Indicateurs de performance">
          <v-card-text>
            <v-list>
              <v-list-item v-for="ind in indicators" :key="ind.id">
                <v-list-item-title>{{ ind.name }}</v-list-item-title>
                <v-list-item-subtitle>
                  Cible: {{ ind.target }} | Actuel: {{ ind.current }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card title="Actions à traiter">
          <v-card-text>
            <v-list>
              <v-list-item v-for="action in actions" :key="action.id">
                <v-list-item-title>{{ action.title }}</v-list-item-title>
                <v-list-item-subtitle>Échéance: {{ action.deadline }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </ProcessLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import ProcessLayout from '@/modules/clienta/components/layouts/ProcessLayout.vue'

  const myProcesses = ref([
    { id: 1, name: 'Gestion des commandes', indicators: '3/4', indicatorStatus: 'success', risks: 2, actions: 5 },
    { id: 2, name: 'Contrôle qualité', indicators: '2/3', indicatorStatus: 'warning', risks: 1, actions: 3 },
  ])

  const indicators = ref([
    { id: 1, name: 'Taux de conformité', target: '95%', current: '92%' },
    { id: 2, name: 'Délai de traitement', target: '48h', current: '52h' },
  ])

  const actions = ref([
    { id: 1, title: 'Améliorer le processus de réception', deadline: '20/01/2025' },
    { id: 2, title: 'Former les opérateurs', deadline: '25/01/2025' },
  ])
</script>
