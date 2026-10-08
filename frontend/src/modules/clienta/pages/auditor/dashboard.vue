<template>
  <AuditorLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Pilotage des audits</div>
          <h1 class="role-dashboard__title">Bonjour, voici votre activité d'audit</h1>
          <p class="role-dashboard__subtitle">Suivez vos audits, vos constats et les actions à sécuriser.</p>
        </div>
      </div>
      <div class="role-dashboard__kpis grid grid-cols-1 md:grid-cols-4 gap-6">
      <AppWidget
        v-for="stat in stats"
        :key="stat.title"
        clickable
        :icon="stat.icon"
        :title="stat.title"
        :value="stat.value"
        :variant="stat.variant"
        @click="stat.onClick"
      />
      </div>

    <v-row>
      <v-col cols="12" md="8">
        <v-card title="Mes audits en cours">
          <v-card-text>
            <v-list>
              <v-list-item v-for="audit in ongoingAudits" :key="audit.id" :to="`/auditor/audits/${audit.id}`">
                <template #prepend>
                  <v-chip :color="audit.statusColor" size="small">{{ audit.status }}</v-chip>
                </template>
                <v-list-item-title>{{ audit.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ audit.date }} - {{ audit.scope }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card title="Programme annuel">
          <v-card-text>
            <div class="text-center mb-4">
              <v-progress-circular color="primary" :model-value="programProgress" size="100" width="10">
                {{ programProgress }}%
              </v-progress-circular>
            </div>
            <div class="text-center">
              <div class="text-h6">12 / 15 audits</div>
              <div class="text-caption">réalisés cette année</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </AuditorLayout>
</template>

<script setup lang="ts">
  import { AlertOctagon, Calendar, CheckCircle, Clock } from 'lucide-vue-next'
  import { ref } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'
  import AuditorLayout from '@/modules/clienta/components/layouts/AuditorLayout.vue'

  const stats = ref([
    { title: 'Audits planifiés', value: 3, icon: Calendar, variant: 'info', onClick: () => console.log('Filter planned') },
    { title: 'Audits en cours', value: 2, icon: Clock, variant: 'warning', onClick: () => console.log('Filter ongoing') },
    { title: 'Audits terminés', value: 12, icon: CheckCircle, variant: 'success', onClick: () => console.log('Filter completed') },
    { title: 'NC créées', value: 8, icon: AlertOctagon, variant: 'error', onClick: () => console.log('Filter NC') },
  ])

  const ongoingAudits = ref([
    { id: 1, title: 'Audit ISO 9001', date: '15/01/2025', scope: 'Production', status: 'En cours', statusColor: 'warning' },
    { id: 2, title: 'Audit ISO 14001', date: '20/01/2025', scope: 'Environnement', status: 'Planifié', statusColor: 'info' },
  ])

  const programProgress = ref(80)
</script>
