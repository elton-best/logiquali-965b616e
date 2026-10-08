<template>
  <QualityLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Système de management</div>
          <h1 class="role-dashboard__title">Vue d'ensemble qualité</h1>
          <p class="role-dashboard__subtitle">Gardez une vision claire de la conformité, des écarts et des audits.</p>
        </div>
      </div>
      <div class="role-dashboard__kpis grid grid-cols-1 md:grid-cols-4 gap-6">
      <AppWidget
        v-for="kpi in kpis"
        :key="kpi.title"
        clickable
        :icon="kpi.icon"
        :title="kpi.title"
        :value="kpi.value"
        :variant="kpi.variant"
        @click="kpi.onClick"
      />
      </div>

    <v-row>
      <v-col cols="12" md="6">
        <v-card title="Non-conformités par gravité">
          <v-card-text>
            <v-list>
              <v-list-item v-for="nc in ncByGravity" :key="nc.level">
                <template #prepend>
                  <v-chip :color="nc.color" size="small">{{ nc.count }}</v-chip>
                </template>
                <v-list-item-title>{{ nc.level }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card title="Prochains audits">
          <v-card-text>
            <v-list>
              <v-list-item v-for="audit in upcomingAudits" :key="audit.id">
                <v-list-item-title>{{ audit.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ audit.date }} - {{ audit.auditor }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </QualityLayout>
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, ClipboardCheck, Clock } from 'lucide-vue-next'
  import { ref } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'
  import QualityLayout from '@/modules/clienta/components/layouts/QualityLayout.vue'

  const kpis = ref([
    { title: 'Taux de conformité', value: '94%', icon: CheckCircle, variant: 'success', onClick: () => console.log('View conformity') },
    { title: 'NC ouvertes', value: '8', icon: AlertCircle, variant: 'error', onClick: () => console.log('View NC') },
    { title: 'Actions en retard', value: '3', icon: Clock, variant: 'warning', onClick: () => console.log('View late actions') },
    { title: 'Audits réalisés', value: '12/15', icon: ClipboardCheck, variant: 'audit', onClick: () => console.log('View audits') },
  ])

  const ncByGravity = ref([
    { level: 'Critique', count: 2, color: 'error' },
    { level: 'Majeure', count: 3, color: 'warning' },
    { level: 'Mineure', count: 3, color: 'info' },
  ])

  const upcomingAudits = ref([
    { id: 1, title: 'Audit ISO 9001', date: '15/01/2025', auditor: 'Jean Dupont' },
    { id: 2, title: 'Audit ISO 14001', date: '22/01/2025', auditor: 'Marie Martin' },
  ])
</script>
