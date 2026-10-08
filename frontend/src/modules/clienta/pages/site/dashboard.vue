<template>
  <SiteLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Performance du site</div>
          <h1 class="role-dashboard__title">Bonjour, voici l'état de votre site</h1>
          <p class="role-dashboard__subtitle">Une vue rapide des processus, audits, actions et alertes opérationnelles.</p>
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
        <v-card title="Processus du site">
          <v-card-text>
            <v-list>
              <v-list-item v-for="p in processes" :key="p.id" :to="`/site/processes/${p.id}`">
                <template #prepend><v-icon>mdi-sync-circle</v-icon></template>
                <v-list-item-title>{{ p.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ p.owner }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card color="warning" title="Alertes" variant="tonal">
          <v-card-text>
            <v-list density="compact">
              <v-list-item v-for="alert in alerts" :key="alert.id">
                <v-list-item-title>{{ alert.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ alert.date }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </SiteLayout>
</template>

<script setup lang="ts">
  import { AlertOctagon, ClipboardCheck, ListChecks, Users } from 'lucide-vue-next'
  import { ref } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'
  import SiteLayout from '@/modules/clienta/components/layouts/SiteLayout.vue'

  const stats = ref([
    { title: 'NC Ouvertes', value: 5, icon: AlertOctagon, variant: 'error', onClick: () => console.log('View NC') },
    { title: 'Actions en cours', value: 12, icon: ListChecks, variant: 'warning', onClick: () => console.log('View actions') },
    { title: 'Audits planifiés', value: 3, icon: ClipboardCheck, variant: 'audit', onClick: () => console.log('View audits') },
    { title: 'Collaborateurs', value: 45, icon: Users, variant: 'success', onClick: () => console.log('View collaborators') },
  ])

  const processes = ref([
    { id: 1, name: 'Gestion des commandes', owner: 'Jean Dupont' },
    { id: 2, name: 'Production', owner: 'Marie Martin' },
    { id: 3, name: 'Contrôle qualité', owner: 'Paul Bernard' },
  ])

  const alerts = ref([
    { id: 1, title: 'Audit ISO 9001 dans 7 jours', date: '15/01/2025' },
    { id: 2, title: '3 documents à réviser', date: 'Cette semaine' },
  ])
</script>
